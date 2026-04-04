<?php
/**
 * tool-ingest.php
 *
 * WhistleblowerShield Core Plugin — Admin Tool
 *
 * PURPOSE
 * -------
 * Processes validated JSON ingest files and writes statute records to
 * the jx-statute CPT with correct ACF field values, taxonomy assignments,
 * and source/verification stamps.
 *
 * RECORD TYPES SUPPORTED (this version)
 * --------------------------------------
 * - statute (jx-statute CPT)
 *
 * PIPELINE PHASES
 * ---------------
 * Phase 1 — Pre-Flight Validation
 *   IT-1: batch_completed sentinel check
 *   IT-2: record_count integrity check
 *   IT-3: with_errors advisory surface
 *   IT-4: proposed terms merge into log
 *   IT-5: Admin confirmation before Phase 2
 *
 * Phase 2 — Record Processing
 *   Create post → stamp source → map fields → assign taxonomies
 *
 * Phase 3 — Post-Run Report
 *
 * OUTPUT DIRECTORY
 * ----------------
 * Upload JSON files via the WordPress media library or FTP to a
 * staging path. This tool reads from a user-specified path.
 *
 * KEY ARCHITECTURAL RULES
 * -----------------------
 * - verification_status is always set to 'unverified' on ingest
 * - needs_review is always set to false on ingest
 * - _review_notes and _reconciled_notes are autostripped (never written)
 * - Proposed terms are logged, not inserted into the taxonomy
 * - Proposed terms in records are removed before writing
 * - The assistant's integrity block is advisory — ingest tool validates independently
 * - Version handlers are never modified after release
 *
 * @package    WhistleblowerShield
 * @since      3.14.0
 * @version    3.14.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.14.0  Initial release. Statute ingest only (json_format_version 2.0).
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_INGEST_VERSION',       '3.14.0' );
define( 'WS_INGEST_SCHEMA_VERSION', '2.0' );
define( 'WS_PROPOSED_TERMS_LOG',   WP_CONTENT_DIR . '/logs/ws-ingest/proposed-terms-log.json' );
define( 'WS_INGEST_LOG_DIR',       WP_CONTENT_DIR . '/logs/ws-ingest/' );


// ── Admin menu registration ───────────────────────────────────────────────────

add_action( 'admin_menu', 'ws_register_ingest_tool_page' );

function ws_register_ingest_tool_page() {
    add_submenu_page(
        'tools.php',
        'WS Ingest Tool',
        'WS Ingest Tool',
        'manage_options',
        'ws-ingest-tool',
        'ws_render_ingest_tool_page'
    );
}


// ── Log directory bootstrap ───────────────────────────────────────────────────

function ws_ingest_bootstrap_log_dir(): void {
    if ( ! file_exists( WS_INGEST_LOG_DIR ) ) {
        wp_mkdir_p( WS_INGEST_LOG_DIR );
        file_put_contents( WS_INGEST_LOG_DIR . '.htaccess', "Deny from all\n" );
    }
    if ( ! file_exists( WS_PROPOSED_TERMS_LOG ) ) {
        file_put_contents( WS_PROPOSED_TERMS_LOG, json_encode(
            [ 'proposed_terms' => [] ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) );
    }
}


// ── Proposed terms log ────────────────────────────────────────────────────────

function ws_ingest_load_proposed_terms_log(): array {
    if ( ! file_exists( WS_PROPOSED_TERMS_LOG ) ) {
        return [ 'proposed_terms' => [] ];
    }
    $raw = file_get_contents( WS_PROPOSED_TERMS_LOG );
    $log = json_decode( $raw, true );
    return is_array( $log ) ? $log : [ 'proposed_terms' => [] ];
}

function ws_ingest_save_proposed_terms_log( array $log ): bool {
    return file_put_contents(
        WS_PROPOSED_TERMS_LOG,
        json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
    ) !== false;
}

/**
 * Merges new_terms_proposed from a batch into the persistent log.
 * Deduplicates by term_id — appends seen_in values for existing entries.
 *
 * @return array [ 'merged' => int, 'new' => int ]
 */
function ws_ingest_merge_proposed_terms( array &$log, array $new_terms ): array {
    $counts = [ 'merged' => 0, 'new' => 0 ];

    foreach ( $new_terms as $proposal ) {
        $term_id = $proposal['term_id'] ?? '';
        if ( ! $term_id ) continue;

        $found = false;
        foreach ( $log['proposed_terms'] as &$existing ) {
            if ( $existing['term_id'] === $term_id ) {
                // Merge seen_in
                $new_seen = array_diff(
                    $proposal['seen_in'] ?? [],
                    $existing['seen_in'] ?? []
                );
                $existing['seen_in'] = array_values(
                    array_merge( $existing['seen_in'] ?? [], $new_seen )
                );
                $existing['count'] = count( $existing['seen_in'] );
                $counts['merged']++;
                $found = true;
                break;
            }
        }
        unset( $existing );

        if ( ! $found ) {
            $log['proposed_terms'][] = [
                'taxonomy'    => $proposal['taxonomy']   ?? '',
                'term_id'     => $term_id,
                'term_label'  => $proposal['term_label'] ?? '',
                'notes'       => $proposal['notes']      ?? '',
                'seen_in'     => $proposal['seen_in']    ?? [],
                'count'       => count( $proposal['seen_in'] ?? [] ),
                'status'      => 'pending',
                'resolved_on' => null,
                'resolution'  => null,
            ];
            $counts['new']++;
        }
    }

    return $counts;
}

/**
 * Builds a blacklist of pending proposed term slugs.
 * Used to strip proposed terms from taxonomy arrays before writing records.
 *
 * @return array [ 'term_id' => 'taxonomy_slug' ]
 */
function ws_ingest_build_blacklist( array $log ): array {
    $blacklist = [];
    foreach ( $log['proposed_terms'] as $term ) {
        if ( ( $term['status'] ?? 'pending' ) === 'pending' ) {
            $blacklist[ $term['term_id'] ] = $term['taxonomy'] ?? '';
        }
    }
    return $blacklist;
}


// ── JSON validation ───────────────────────────────────────────────────────────

/**
 * Runs all pre-flight checks on a decoded JSON array.
 *
 * @return array [ 'pass' => bool, 'errors' => string[], 'warnings' => string[] ]
 */
function ws_ingest_preflight( array $data ): array {
    $result = [ 'pass' => true, 'errors' => [], 'warnings' => [] ];

    $meta      = $data['meta']      ?? null;
    $records   = $data['records']   ?? null;
    $integrity = $data['integrity'] ?? null;

    // Structure check
    if ( ! is_array( $meta ) || ! is_array( $records ) || ! is_array( $integrity ) ) {
        $result['errors'][] = 'JSON missing required top-level keys: meta, records, integrity.';
        $result['pass'] = false;
        return $result;
    }

    // IT-1: batch_completed sentinel
    if ( empty( $meta['batch_completed'] ) ) {
        $result['errors'][] = 'IT-1 FAILED: batch_completed is missing or empty. Batch may be truncated.';
        $result['pass'] = false;
    }

    // Version check
    $version = $meta['json_format_version'] ?? '';
    if ( $version !== WS_INGEST_SCHEMA_VERSION ) {
        $result['errors'][] = sprintf(
            'Unsupported json_format_version "%s". This tool handles version %s only.',
            esc_html( $version ),
            WS_INGEST_SCHEMA_VERSION
        );
        $result['pass'] = false;
    }

    // IT-2: record_count integrity
    $declared = (int) ( $meta['record_count'] ?? -1 );
    $actual   = count( $records );
    if ( $declared !== $actual ) {
        $result['errors'][] = sprintf(
            'IT-2 FAILED: record_count mismatch — declared %d, found %d.',
            $declared,
            $actual
        );
        $result['pass'] = false;
    }

    // IT-3: with_errors advisory
    if ( ! empty( $integrity['with_errors'] ) ) {
        $result['warnings'][] = 'Assistant reported with_errors: true.';
        foreach ( $integrity['error_details'] ?? [] as $detail ) {
            $result['warnings'][] = '  → ' . $detail;
        }
    }

    // Record type check — statute only for this version
    foreach ( $records as $i => $record ) {
        $jx = $record['jurisdiction_id'] ?? '';
        $sid = $record['statute_id'] ?? "record[$i]";
        if ( empty( $jx ) ) {
            $result['warnings'][] = "$sid: missing jurisdiction_id.";
        }
        if ( empty( $sid ) || $sid === "record[$i]" ) {
            $result['warnings'][] = "record[$i]: missing statute_id.";
        }
    }

    return $result;
}


// ── Field map — JSON key → ACF meta key ──────────────────────────────────────

/**
 * Returns the complete statute field map for json_format_version 2.0.
 *
 * Format:
 *   'json.path' => [ 'meta_key', 'type' ]
 *
 * Types:
 *   text    — update_post_meta() with sanitize_text_field()
 *   textarea — update_post_meta() with sanitize_textarea_field()
 *   url     — update_post_meta() with esc_url_raw()
 *   bool    — update_post_meta() with (int)(bool) cast
 *   number  — update_post_meta() with (float) cast
 *   tax     — wp_set_object_terms() with taxonomy name in [2]
 *   derived — set by ingest logic, not directly from JSON
 *   omit    — not written to DB
 */
function ws_ingest_statute_field_map_v2(): array {
    return [
        // ── Legal Basis ───────────────────────────────────────────────────
        'official_name'                          => [ 'ws_jx_statute_official_name',            'text'     ],
        'common_name'                            => [ 'ws_jx_statute_common_name',               'text'     ],
        'legal_basis.statute_citation'           => [ 'ws_jx_statute_citation',                 'text'     ],
        'legal_basis.disclosure_types'           => [ 'ws_jx_statute_disclosure_type',          'tax', 'ws_disclosure_type'     ],
        'legal_basis.protected_class'            => [ 'ws_jx_statute_protected_class',          'tax', 'ws_protected_class'     ],
        'legal_basis.protected_class_details'    => [ 'ws_jx_statute_protected_class_details',  'textarea' ],
        'legal_basis.disclosure_targets'         => [ 'ws_jx_statute_disclosure_targets',       'tax', 'ws_disclosure_targets'  ],
        'legal_basis.disclosure_targets_details' => [ 'ws_jx_statute_disclosure_targets_details','textarea'],
        'legal_basis.adverse_action_scope'       => [ 'ws_jx_statute_adverse_action_scope',     'textarea' ],

        // ── SOL ───────────────────────────────────────────────────────────
        'statute_of_limitations.limit_value'       => [ 'ws_jx_statute_sol_value',          'number'  ],
        'statute_of_limitations.limit_unit'        => [ 'ws_jx_statute_sol_unit',           'text'    ],
        'statute_of_limitations.limit_ambiguous'   => [ 'ws_jx_statute_limit_ambiguous',    'bool'    ],
        'statute_of_limitations.limit_details'     => [ 'ws_jx_statute_limit_details',      'textarea'],
        'statute_of_limitations.trigger'           => [ 'ws_jx_statute_sol_trigger',        'text'    ],
        'statute_of_limitations.exhaustion_required' => [ 'ws_jx_statute_exhaustion_required', 'bool' ],
        'statute_of_limitations.exhaustion_details'  => [ 'ws_jx_statute_exhaustion_details',  'textarea'],
        'statute_of_limitations.tolling_notes'     => [ 'ws_jx_statute_tolling_notes',      'textarea'],
        // tolling_has_notes derived: set to 1 when tolling_notes is present

        // ── Enforcement ───────────────────────────────────────────────────
        'enforcement.process_type'           => [ 'ws_jx_statute_process_type',      'tax', 'ws_process_type'         ],
        'enforcement.adverse_action'         => [ 'ws_jx_statute_adverse_action',    'tax', 'ws_adverse_action_types'  ],
        'enforcement.adverse_action_details' => [ 'ws_jx_statute_adverse_action_details', 'textarea' ],
        'enforcement.fee_shifting'           => [ 'ws_jx_statute_fee_shifting',      'tax', 'ws_fee_shifting'          ],
        'enforcement.remedies'               => [ 'ws_jx_statute_remedies',          'tax', 'ws_remedies'              ],
        'enforcement.remedies_details'       => [ 'ws_jx_statute_remedies_details',  'textarea' ],
        'enforcement.primary_agency'         => [ null, 'omit' ], // no ACF field — log in run report

        // ── Burden of Proof ───────────────────────────────────────────────
        'burden_of_proof.employee_standard'         => [ 'ws_jx_statute_employee_standard',         'tax', 'ws_employee_standard' ],
        'burden_of_proof.employee_standard_details' => [ 'ws_jx_statute_employee_standard_details',  'textarea' ],
        'burden_of_proof.employer_defense'          => [ 'ws_jx_statute_employer_defense',           'tax', 'ws_employer_defense'  ],
        'burden_of_proof.employer_defense_details'  => [ 'ws_jx_statute_employer_defense_details',   'textarea' ],
        'burden_of_proof.rebuttable_presumption'    => [ 'ws_jx_statute_rebuttable_presumption',     'textarea' ],
        // rebuttable_has_presumption derived: set to 1 when rebuttable_presumption present
        'burden_of_proof.burden_of_proof_details'   => [ 'ws_jx_statute_burden_of_proof_details',   'textarea' ],
        // bop_has_details derived: set to 1 when burden_of_proof_details present
        'burden_of_proof.burden_of_proof_flag'      => [ 'ws_jx_statute_bop_flag',                  'text'     ],

        // ── Reward ────────────────────────────────────────────────────────
        'reward.available'      => [ 'ws_jx_statute_reward_available', 'bool'     ],
        'reward.reward_details' => [ 'ws_jx_statute_reward_details',   'textarea' ],

        // ── Links ─────────────────────────────────────────────────────────
        'links.statute_url' => [ 'ws_jx_statute_url',    'url'  ],
        'links.is_pdf'      => [ 'ws_jx_statute_is_pdf', 'bool' ],
        'links.is_official' => [ null, 'omit' ], // advisory
        'links.url_source'  => [ null, 'omit' ], // advisory

        // ── Autostripped ──────────────────────────────────────────────────
        '_review_notes'     => [ null, 'omit' ],
        '_reconciled_notes' => [ null, 'omit' ],

        // ── Citations ─────────────────────────────────────────────────────
        'citations.attached_citations' => [ null, 'omit' ], // separate CPT — not ingested here
        'citations.citation_count'     => [ null, 'omit' ], // advisory
    ];
}


// ── Taxonomy validation ───────────────────────────────────────────────────────

/**
 * Returns all registered term slugs for a taxonomy.
 * Used to validate ingest values before writing.
 */
function ws_ingest_get_valid_slugs( string $taxonomy ): array {
    static $cache = [];
    if ( isset( $cache[ $taxonomy ] ) ) return $cache[ $taxonomy ];

    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'fields'     => 'slugs',
    ] );

    $cache[ $taxonomy ] = is_wp_error( $terms ) ? [] : $terms;
    return $cache[ $taxonomy ];
}

/**
 * Validates and filters a taxonomy array.
 * Removes: invalid slugs, parent slugs, blacklisted proposed terms.
 * has-details sentinel is only valid when a companion _details field is non-empty.
 *
 * @return array [ 'valid' => string[], 'removed' => [ slug => reason ] ]
 */
function ws_ingest_validate_taxonomy_array( array $slugs, string $taxonomy, array $blacklist, array $record ): array {
    $valid_slugs  = ws_ingest_get_valid_slugs( $taxonomy );
    $valid        = [];
    $removed      = [];

    // Parent slugs — structural labels only, never valid record values
    $parent_slugs = [
        'workplace-employment', 'financial-corporate', 'government-accountability',
        'public-health-safety', 'privacy-data-integrity', 'national-security',
        'public-sector', 'private-sector', 'healthcare-staff', 'special-status',
        'internal', 'external-agency', 'legislative', 'judicial', 'public',
    ];

    foreach ( $slugs as $slug ) {
        if ( in_array( $slug, $parent_slugs, true ) ) {
            $removed[ $slug ] = 'parent slug';
            continue;
        }
        if ( isset( $blacklist[ $slug ] ) ) {
            $removed[ $slug ] = 'proposed term (pending)';
            continue;
        }
        if ( ! in_array( $slug, $valid_slugs, true ) ) {
            $removed[ $slug ] = 'unregistered slug';
            continue;
        }
        $valid[] = $slug;
    }

    return [ 'valid' => $valid, 'removed' => $removed ];
}


// ── Value extractor ───────────────────────────────────────────────────────────

/**
 * Extracts a value from a nested record array using dot-notation path.
 * Returns null if path not found.
 */
function ws_ingest_get_value( array $record, string $path ) {
    $parts   = explode( '.', $path );
    $current = $record;
    foreach ( $parts as $part ) {
        if ( ! is_array( $current ) || ! array_key_exists( $part, $current ) ) {
            return null;
        }
        $current = $current[ $part ];
    }
    return $current;
}


// ── Post title builder ────────────────────────────────────────────────────────

function ws_ingest_build_post_title( array $record ): string {
    $jx      = strtoupper( $record['jurisdiction_id'] ?? '' );
    $sid     = $record['statute_id']    ?? '';
    $name    = $record['official_name'] ?? '';
    $common  = $record['common_name']   ?? '';

    if ( $common ) {
        return trim( "$jx — $name ($common)" );
    }
    return trim( "$jx — $name" );
}


// ── Core record processor ─────────────────────────────────────────────────────

/**
 * Processes a single statute record.
 * Creates a WP post, stamps source fields, maps all JSON fields to ACF meta,
 * assigns taxonomy terms, and derives companion boolean fields.
 *
 * @return array [ 'success' => bool, 'post_id' => int|null, 'log' => string[], 'warnings' => string[] ]
 */
function ws_ingest_process_statute_record( array $record, array $meta, array $blacklist ): array {
    $result = [
        'success'  => false,
        'post_id'  => null,
        'log'      => [],
        'warnings' => [],
    ];

    $sid = $record['statute_id'] ?? 'UNKNOWN';

    // ── Step 1: Check for duplicate ──────────────────────────────────────
    $existing = get_posts( [
        'post_type'   => 'jx-statute',
        'post_status' => 'any',
        'meta_query'  => [ [
            'key'     => 'ws_jx_statute_citation',
            'value'   => $record['legal_basis']['statute_citation'] ?? '',
            'compare' => '=',
        ] ],
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ] );

    if ( ! empty( $existing ) ) {
        $result['warnings'][] = "$sid: duplicate detected (post #{$existing[0]}) — skipped.";
        return $result;
    }

    // ── Step 2: Create post ──────────────────────────────────────────────
    $post_id = wp_insert_post( [
        'post_type'   => 'jx-statute',
        'post_status' => 'draft',
        'post_title'  => ws_ingest_build_post_title( $record ),
        'post_author' => get_current_user_id(),
    ] );

    if ( is_wp_error( $post_id ) ) {
        $result['warnings'][] = "$sid: wp_insert_post failed — " . $post_id->get_error_message();
        return $result;
    }

    $result['post_id'] = $post_id;

    // ── Step 3: Source stamps ────────────────────────────────────────────
    update_post_meta( $post_id, 'ws_auto_source_method', sanitize_text_field( $meta['source_method'] ?? 'ai_assisted' ) );
    update_post_meta( $post_id, 'ws_auto_source_name',   sanitize_text_field( $meta['source_name']   ?? '' ) );
    update_post_meta( $post_id, 'ws_verification_status', 'unverified' );
    update_post_meta( $post_id, 'ws_needs_review',        0 );

    // ── Step 4: Assign ws_jurisdiction taxonomy term ──────────────────────
    $jx_slug = strtolower( $record['jurisdiction_id'] ?? '' );
    if ( $jx_slug ) {
        $term = get_term_by( 'slug', $jx_slug, WS_JURISDICTION_TAXONOMY );
        if ( $term && ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, $term->term_id, WS_JURISDICTION_TAXONOMY );
            $result['log'][] = "jurisdiction: assigned '{$jx_slug}'";
        } else {
            $result['warnings'][] = "$sid: jurisdiction term '{$jx_slug}' not found in ws_jurisdiction taxonomy.";
        }
    }

    // ── Step 5: Field map ────────────────────────────────────────────────
    $field_map      = ws_ingest_statute_field_map_v2();
    $tax_removals   = [];
    $omitted_fields = [];

    foreach ( $field_map as $json_path => $field_def ) {
        $meta_key  = $field_def[0];
        $type      = $field_def[1];
        $taxonomy  = $field_def[2] ?? null;

        if ( $type === 'omit' || $meta_key === null ) {
            // Log omitted fields that have non-empty values (for run report)
            $val = ws_ingest_get_value( $record, $json_path );
            if ( $val !== null && $val !== '' && $val !== [] ) {
                $omitted_fields[ $json_path ] = $val;
            }
            continue;
        }

        $value = ws_ingest_get_value( $record, $json_path );
        if ( $value === null ) continue;

        switch ( $type ) {
            case 'text':
                if ( $value !== '' ) {
                    update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
                }
                break;

            case 'textarea':
                if ( $value !== '' ) {
                    update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $value ) );
                }
                break;

            case 'url':
                if ( $value !== '' ) {
                    update_post_meta( $post_id, $meta_key, esc_url_raw( $value ) );
                }
                break;

            case 'bool':
                update_post_meta( $post_id, $meta_key, (int)(bool) $value );
                break;

            case 'number':
                if ( $value !== '' && $value !== null ) {
                    update_post_meta( $post_id, $meta_key, (float) $value );
                }
                break;

            case 'tax':
                if ( ! is_array( $value ) || empty( $value ) ) break;
                $validated = ws_ingest_validate_taxonomy_array( $value, $taxonomy, $blacklist, $record );
                if ( ! empty( $validated['removed'] ) ) {
                    foreach ( $validated['removed'] as $slug => $reason ) {
                        $tax_removals[] = "$sid [$taxonomy]: removed '$slug' ($reason)";
                    }
                }
                if ( ! empty( $validated['valid'] ) ) {
                    // Convert slugs to term IDs
                    $term_ids = [];
                    foreach ( $validated['valid'] as $slug ) {
                        $term = get_term_by( 'slug', $slug, $taxonomy );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $term_ids[] = $term->term_id;
                        }
                    }
                    if ( $term_ids ) {
                        wp_set_object_terms( $post_id, $term_ids, $taxonomy );
                    }
                }
                break;
        }
    }

    // ── Step 6: Derived boolean companions ───────────────────────────────

    // tolling_has_notes: 1 when tolling_notes is present
    $tolling = ws_ingest_get_value( $record, 'statute_of_limitations.tolling_notes' );
    if ( $tolling ) {
        update_post_meta( $post_id, 'ws_jx_statute_tolling_has_notes', 1 );
    }

    // rebuttable_has_presumption: 1 when rebuttable_presumption is present
    $rebuttable = ws_ingest_get_value( $record, 'burden_of_proof.rebuttable_presumption' );
    if ( $rebuttable ) {
        update_post_meta( $post_id, 'ws_jx_statute_rebuttable_has_presumption', 1 );
    }

    // bop_has_details: 1 when burden_of_proof_details is present
    $bop_details = ws_ingest_get_value( $record, 'burden_of_proof.burden_of_proof_details' );
    if ( $bop_details ) {
        update_post_meta( $post_id, 'ws_jx_statute_bop_has_details', 1 );
    }

    // ── Step 7: Log tax removals ─────────────────────────────────────────
    foreach ( $tax_removals as $removal ) {
        $result['warnings'][] = $removal;
    }

    // ── Step 8: Log omitted fields with values ───────────────────────────
    foreach ( $omitted_fields as $path => $val ) {
        $display = is_array( $val ) ? implode( ', ', $val ) : $val;
        $result['log'][] = "omitted (no ACF field): $path = " . substr( $display, 0, 80 );
    }

    $result['success'] = true;
    $result['log'][]   = "$sid: created as post #$post_id (draft, unverified)";

    return $result;
}



// ── Run log writer ────────────────────────────────────────────────────────────

/**
 * Writes a persistent run log to wp-content/logs/ws-ingest/.
 * Filename: [JX]-[YYYYMMDD-HHmm]-ingest.txt
 * FTP-accessible, .htaccess protected.
 */
function ws_ingest_write_run_log( array $result ): void {
    $summary = $result['summary'] ?? [];
    $jx      = strtoupper( $summary['jurisdiction'] ?? 'XX' );
    $ts      = date( 'Ymd-Hi' );
    $path    = WS_INGEST_LOG_DIR . "{$jx}-{$ts}-ingest.txt";

    $lines   = [];
    $lines[] = '================================================';
    $lines[] = 'WS INGEST RUN LOG';
    $lines[] = '================================================';
    $lines[] = 'Run timestamp:    ' . date( 'Y-m-d H:i:s' ) . ' UTC';
    $lines[] = 'Jurisdiction:     ' . $jx;
    $lines[] = 'Source:           ' . ( $summary['source_name']   ?? '' );
    $lines[] = 'Source method:    ' . ( $summary['source_method'] ?? '' );
    $lines[] = 'Batch completed:  ' . ( $summary['batch_completed'] ?? '' );
    $lines[] = '';
    $lines[] = '── SUMMARY ──────────────────────────────────────';
    $lines[] = 'Created:          ' . ( $summary['created']  ?? 0 );
    $lines[] = 'Skipped (dupe):   ' . ( $summary['skipped']  ?? 0 );
    $lines[] = 'Failed:           ' . ( $summary['failed']   ?? 0 );
    $lines[] = 'Proposed new:     ' . ( $summary['proposed_new']    ?? 0 );
    $lines[] = 'Proposed merged:  ' . ( $summary['proposed_merged'] ?? 0 );
    $lines[] = 'Blacklist size:   ' . ( $summary['blacklist_size']  ?? 0 );
    $lines[] = '';

    // Pre-flight warnings
    $preflight = $result['preflight'] ?? [];
    if ( ! empty( $preflight['warnings'] ) ) {
        $lines[] = '── PREFLIGHT WARNINGS ───────────────────────────';
        foreach ( $preflight['warnings'] as $w ) {
            $lines[] = '  ' . $w;
        }
        $lines[] = '';
    }

    // Per-record detail
    $lines[] = '── RECORD DETAIL ────────────────────────────────';
    foreach ( $result['records'] ?? [] as $rec ) {
        $status  = $rec['success'] ? '✓' : '✗';
        $post    = $rec['post_id'] ? ' [post #' . $rec['post_id'] . ']' : '';
        $lines[] = "{$status} {$rec['statute_id']}{$post}";
        foreach ( $rec['warnings'] as $w ) {
            $lines[] = '    ⚠ ' . $w;
        }
        foreach ( $rec['log'] as $l ) {
            $lines[] = '    · ' . $l;
        }
    }

    $lines[] = '';
    $lines[] = '================================================';
    $lines[] = 'END OF LOG';
    $lines[] = '================================================';

    file_put_contents( $path, implode( "\n", $lines ) . "\n" );
}


// ── Append-only ledger logs ───────────────────────────────────────────────────

/**
 * Appends a line to the preflight errors ledger.
 * One entry per failed preflight — filename, timestamp, reasons.
 */
function ws_ingest_log_preflight_failure( string $filename, array $errors ): void {
    $path   = WS_INGEST_LOG_DIR . 'preflight-errors.log';
    $ts     = date( 'Y-m-d H:i:s' );
    $reason = implode( ' | ', $errors );
    $line   = "[{$ts} UTC]  {$filename}  —  {$reason}" . PHP_EOL;
    file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
}

/**
 * Appends a line to the imported batches ledger.
 * One entry per successfully processed batch.
 */
function ws_ingest_log_imported_batch( string $filename, array $summary, bool $with_errors ): void {
    $path    = WS_INGEST_LOG_DIR . 'imported.log';
    $ts      = date( 'Y-m-d H:i:s' );
    $jx      = strtoupper( $summary['jurisdiction'] ?? 'XX' );
    $created = (int) ( $summary['created']  ?? 0 );
    $skipped = (int) ( $summary['skipped']  ?? 0 );
    $failed  = (int) ( $summary['failed']   ?? 0 );
    $errors  = $with_errors ? 'true' : 'false';
    $line    = "[{$ts} UTC]  {$filename}  {$jx}  created:{$created}  skipped:{$skipped}  failed:{$failed}  errors:{$errors}" . PHP_EOL;
    file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
}


/**
 * Appends citation breadcrumbs to citations-breadcrumbs.log.
 * One entry per statute that has attached_citations.
 * Human review trail only — not enough data for a jx-citation record.
 */
function ws_ingest_log_citation_breadcrumbs( string $filename, string $jx, string $statute_id, array $citations ): void {
    if ( empty( $citations ) ) return;

    $path = WS_INGEST_LOG_DIR . 'citations-breadcrumbs.log';
    $ts   = date( 'Y-m-d H:i:s' );

    $lines   = [];
    $lines[] = "[{$ts} UTC]  {$filename}  {$jx}  {$statute_id}";
    foreach ( $citations as $cite ) {
        $lines[] = '  ' . $cite;
    }
    $lines[] = '---';
    $lines[] = '';

    file_put_contents( $path, implode( PHP_EOL, $lines ) . PHP_EOL, FILE_APPEND | LOCK_EX );
}

// ── Main handler ──────────────────────────────────────────────────────────────

function ws_handle_ingest_submission(): array {
    $result = [
        'phase'     => '',
        'preflight' => null,
        'records'   => [],
        'summary'   => [],
        'errors'    => [],
    ];

    if ( empty( $_POST['ws_ingest_nonce'] ) || ! wp_verify_nonce( $_POST['ws_ingest_nonce'], 'ws_run_ingest' ) ) {
        $result['errors'][] = 'Security check failed.';
        return $result;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        $result['errors'][] = 'Insufficient permissions.';
        return $result;
    }

    ws_ingest_bootstrap_log_dir();

    $batch_filename = sanitize_text_field( wp_unslash( $_POST['ws_ingest_filename'] ?? 'unknown' ) );

    // ── Read JSON ────────────────────────────────────────────────────────
    $json_input = sanitize_textarea_field( wp_unslash( $_POST['ws_ingest_json'] ?? '' ) );
    if ( empty( $json_input ) ) {
        $result['errors'][] = 'No JSON provided.';
        return $result;
    }

    $data = json_decode( $json_input, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        $result['errors'][] = 'JSON parse error: ' . json_last_error_msg();
        return $result;
    }

    // ── Phase 1: Pre-Flight ──────────────────────────────────────────────
    $result['phase']     = 'preflight';
    $preflight           = ws_ingest_preflight( $data );
    $result['preflight'] = $preflight;

    if ( ! $preflight['pass'] ) {
        ws_ingest_log_preflight_failure( $batch_filename, $preflight['errors'] );
        return $result;
    }

    // Check if user confirmed after seeing preflight
    $confirmed = ! empty( $_POST['ws_ingest_confirmed'] );
    if ( ! $confirmed ) {
        // Return preflight results — show confirmation UI
        return $result;
    }

    // ── Phase 1 continued: Proposed terms merge ──────────────────────────
    $result['phase'] = 'processing';
    $log             = ws_ingest_load_proposed_terms_log();
    $new_terms       = $data['meta']['new_terms_proposed'] ?? [];
    $merge_counts    = ws_ingest_merge_proposed_terms( $log, $new_terms );
    ws_ingest_save_proposed_terms_log( $log );

    $blacklist = ws_ingest_build_blacklist( $log );

    // ── Phase 2: Record Processing ───────────────────────────────────────
    $meta    = $data['meta'];
    $records = $data['records'];

    $created  = 0;
    $skipped  = 0;
    $failed   = 0;
    $all_logs = [];

    foreach ( $records as $record ) {
        $record_result = ws_ingest_process_statute_record( $record, $meta, $blacklist );
        $sid           = $record['statute_id'] ?? 'UNKNOWN';

        // Log citation breadcrumbs
        $raw_citations = $record['citations']['attached_citations'] ?? [];
        if ( ! empty( $raw_citations ) ) {
            ws_ingest_log_citation_breadcrumbs( $batch_filename, $meta['jurisdiction_id'] ?? '', $sid, $raw_citations );
        }

        $all_logs[] = [
            'statute_id' => $sid,
            'success'    => $record_result['success'],
            'post_id'    => $record_result['post_id'],
            'log'        => $record_result['log'],
            'warnings'   => $record_result['warnings'],
        ];

        if ( $record_result['success'] ) {
            $created++;
        } elseif ( ! empty( $record_result['warnings'] ) &&
                   str_contains( implode( ' ', $record_result['warnings'] ), 'duplicate' ) ) {
            $skipped++;
        } else {
            $failed++;
        }
    }

    $result['records'] = $all_logs;
    $result['summary'] = [
        'created'         => $created,
        'skipped'         => $skipped,
        'failed'          => $failed,
        'proposed_new'    => $merge_counts['new'],
        'proposed_merged' => $merge_counts['merged'],
        'blacklist_size'  => count( $blacklist ),
        'source_name'     => $meta['source_name']     ?? '',
        'source_method'   => $meta['source_method']   ?? '',
        'jurisdiction'    => $meta['jurisdiction_id']  ?? '',
        'batch_completed' => $meta['batch_completed']  ?? '',
    ];

    // Write persistent run log and update ledgers
    if ( $result['phase'] === 'processing' ) {
        ws_ingest_write_run_log( $result );
        $has_warnings = ! empty( array_filter(
            array_column( $result['records'], 'warnings' ),
            fn( $w ) => ! empty( $w )
        ) );
        ws_ingest_log_imported_batch( $batch_filename, $result['summary'], $has_warnings );
    }

    return $result;
}


// ── Admin page renderer ───────────────────────────────────────────────────────

function ws_render_ingest_tool_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access denied.' );
    }

    $run_result = null;
    if ( isset( $_POST['ws_ingest_nonce'] ) ) {
        $run_result = ws_handle_ingest_submission();
    }

    $phase             = $run_result['phase']     ?? '';
    $preflight         = $run_result['preflight'] ?? null;
    $confirmed         = ! empty( $_POST['ws_ingest_confirmed'] );
    $show_confirmation = ( $phase === 'preflight' && $preflight && $preflight['pass'] && ! $confirmed );
    $json_input        = sanitize_textarea_field( wp_unslash( $_POST['ws_ingest_json'] ?? '' ) );

    ?>
    <div class="wrap">
        <h1>WS Ingest Tool <span style="font-size:13px;color:#666;font-weight:normal;">v<?php echo esc_html( WS_INGEST_VERSION ); ?> — schema <?php echo esc_html( WS_INGEST_SCHEMA_VERSION ); ?></span></h1>
        <p>Paste a validated JSON batch below. Pre-flight checks run first — you must confirm before records are written.</p>
        <p><strong>This version handles:</strong> <code>statute</code> records, <code>json_format_version 2.0</code> only.</p>

        <?php if ( ! empty( $run_result['errors'] ) ): ?>
            <div class="notice notice-error">
                <?php foreach ( $run_result['errors'] as $err ): ?>
                    <p><?php echo esc_html( $err ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( $preflight && ! $preflight['pass'] ): ?>
            <div class="notice notice-error">
                <p><strong>Pre-flight failed — ingest aborted.</strong></p>
                <?php foreach ( $preflight['errors'] as $err ): ?>
                    <p>⛔ <?php echo esc_html( $err ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( $phase === 'processing' && ! empty( $run_result['summary'] ) ): ?>
            <?php $s = $run_result['summary']; ?>
            <div class="notice notice-<?php echo $s['failed'] > 0 ? 'warning' : 'success'; ?>">
                <p><strong>Ingest complete.</strong></p>
                <p>
                    ✅ Created: <strong><?php echo (int) $s['created']; ?></strong> &nbsp;|&nbsp;
                    ⏭ Skipped (duplicate): <strong><?php echo (int) $s['skipped']; ?></strong> &nbsp;|&nbsp;
                    ❌ Failed: <strong><?php echo (int) $s['failed']; ?></strong>
                </p>
                <p>
                    Source: <strong><?php echo esc_html( $s['source_name'] ); ?></strong>
                    (<?php echo esc_html( $s['source_method'] ); ?>) &nbsp;|&nbsp;
                    Jurisdiction: <strong><?php echo esc_html( strtoupper( $s['jurisdiction'] ) ); ?></strong> &nbsp;|&nbsp;
                    Batch completed: <?php echo esc_html( $s['batch_completed'] ); ?>
                </p>
                <?php if ( $s['proposed_new'] > 0 || $s['proposed_merged'] > 0 ): ?>
                    <p>
                        Proposed terms: <strong><?php echo (int) $s['proposed_new']; ?></strong> new,
                        <strong><?php echo (int) $s['proposed_merged']; ?></strong> merged into existing entries.
                        Blacklist size: <?php echo (int) $s['blacklist_size']; ?> pending terms.
                    </p>
                <?php endif; ?>
            </div>

            <?php foreach ( $run_result['records'] as $rec ): ?>
                <div style="margin:10px 0;padding:10px;border:1px solid <?php echo $rec['success'] ? '#46b450' : '#dc3232'; ?>;border-radius:4px;background:#fff;">
                    <strong><?php echo esc_html( $rec['statute_id'] ); ?></strong>
                    <?php if ( $rec['post_id'] ): ?>
                        — <a href="<?php echo get_edit_post_link( $rec['post_id'] ); ?>">post #<?php echo (int) $rec['post_id']; ?></a>
                    <?php endif; ?>
                    <?php if ( ! empty( $rec['warnings'] ) ): ?>
                        <ul style="margin:5px 0 0 15px;color:#c00;">
                            <?php foreach ( $rec['warnings'] as $w ): ?>
                                <li><?php echo esc_html( $w ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ( ! empty( $rec['log'] ) ): ?>
                        <ul style="margin:5px 0 0 15px;color:#555;font-size:12px;">
                            <?php foreach ( $rec['log'] as $l ): ?>
                                <li><?php echo esc_html( $l ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php elseif ( $show_confirmation ): ?>
            <?php // Pre-flight passed — show results and ask for confirmation ?>
            <div class="notice notice-warning">
                <p><strong>Pre-flight passed. Review and confirm before records are written.</strong></p>
            </div>

            <?php if ( ! empty( $preflight['warnings'] ) ): ?>
                <div class="notice notice-info">
                    <p><strong>Assistant self-report / warnings:</strong></p>
                    <?php foreach ( $preflight['warnings'] as $w ): ?>
                        <p><?php echo esc_html( $w ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'ws_run_ingest', 'ws_ingest_nonce' ); ?>
                <input type="hidden" name="ws_ingest_confirmed" value="1">
                <input type="hidden" name="ws_ingest_filename" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_POST['ws_ingest_filename'] ?? '' ) ) ); ?>">
                <input type="hidden" name="ws_ingest_json" value="<?php echo esc_attr( $json_input ); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="✅ Confirm — Write Records">
                    &nbsp;
                    <a href="<?php echo admin_url( 'tools.php?page=ws-ingest-tool' ); ?>" class="button">Cancel</a>
                </p>
            </form>

        <?php else: ?>
            <?php // Initial form ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'ws_run_ingest', 'ws_ingest_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ws_ingest_filename">Batch Filename</label></th>
                        <td>
                            <input type="text" name="ws_ingest_filename" id="ws_ingest_filename"
                                   class="regular-text"
                                   placeholder="e.g. NJ-7-Statutes-NotebookLM-20260403-0843.json"
                                   value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_POST['ws_ingest_filename'] ?? '' ) ) ); ?>">
                            <p class="description">Used in the run logs for traceability. Paste the original filename of the JSON file.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ws_ingest_json">JSON Batch</label></th>
                        <td>
                            <textarea name="ws_ingest_json" id="ws_ingest_json"
                                      rows="20" class="large-text code"
                                      placeholder='Paste the complete JSON object here — {"meta":{...},"records":[...],"integrity":{...}}'
                                      required></textarea>
                            <p class="description">
                                Paste the complete JSON object from your research model or NotebookLM merge.
                                Pre-flight checks run first. You will be asked to confirm before any records are written.
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Run Pre-Flight Check">
                </p>
            </form>

        <?php endif; ?>

        <hr>
        <p style="color:#999;font-size:12px;">
            Proposed terms log: <code><?php echo esc_html( WS_PROPOSED_TERMS_LOG ); ?></code> &nbsp;|&nbsp;
            All ingested records are created as <strong>drafts</strong> with <strong>verification_status: unverified</strong>.
        </p>
    </div>
    <?php
}
