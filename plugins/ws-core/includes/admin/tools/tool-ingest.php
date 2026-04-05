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
define( 'WS_INGEST_INBOX_DIR',     WP_CONTENT_DIR . '/logs/ws-ingest/inbox/' );
define( 'WS_INGEST_ARCHIVE_DIR',   WP_CONTENT_DIR . '/logs/ws-ingest/archive/' );
define( 'WS_INGEST_CONFIRM_TTL',   30 * MINUTE_IN_SECONDS );


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
    if ( ! file_exists( WS_INGEST_INBOX_DIR ) ) {
        wp_mkdir_p( WS_INGEST_INBOX_DIR );
        file_put_contents( trailingslashit( WS_INGEST_INBOX_DIR ) . '.htaccess', "Deny from all\n" );
    }
    if ( ! file_exists( WS_INGEST_ARCHIVE_DIR ) ) {
        wp_mkdir_p( WS_INGEST_ARCHIVE_DIR );
        file_put_contents( trailingslashit( WS_INGEST_ARCHIVE_DIR ) . '.htaccess', "Deny from all\n" );
    }
    if ( ! file_exists( WS_PROPOSED_TERMS_LOG ) ) {
        file_put_contents( WS_PROPOSED_TERMS_LOG, json_encode(
            [ 'proposed_terms' => [] ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) );
    }
}

function ws_ingest_get_inbox_files(): array {
    ws_ingest_bootstrap_log_dir();

    $files = glob( trailingslashit( WS_INGEST_INBOX_DIR ) . '*.json' );
    if ( ! is_array( $files ) ) {
        return [];
    }

    $files = array_values( array_filter( $files, 'is_file' ) );
    sort( $files, SORT_NATURAL | SORT_FLAG_CASE );
    return $files;
}

function ws_ingest_decode_json_payload( string $raw ): array {
    $corrections = [];

    // Remove UTF-8 BOM from FTP-uploaded files when present.
    if ( strncmp( $raw, "\xEF\xBB\xBF", 3 ) === 0 ) {
        $raw = substr( $raw, 3 );
        $corrections[] = 'Removed UTF-8 BOM from file payload.';
    }

    $data = json_decode( $raw, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return [
            'ok'          => false,
            'data'        => null,
            'json'        => $raw,
            'corrections' => $corrections,
            'error'       => json_last_error_msg(),
        ];
    }

    return [
        'ok'          => true,
        'data'        => $data,
        'json'        => $raw,
        'corrections' => $corrections,
        'error'       => '',
    ];
}

function ws_ingest_apply_safe_json_corrections( array $data ): array {
    $notes = [];

    if ( isset( $data['meta']['jurisdiction_id'] ) && is_string( $data['meta']['jurisdiction_id'] ) ) {
        $normalized = strtoupper( trim( $data['meta']['jurisdiction_id'] ) );
        if ( $normalized !== $data['meta']['jurisdiction_id'] ) {
            $data['meta']['jurisdiction_id'] = $normalized;
            $notes[] = 'Normalized meta.jurisdiction_id to uppercase.';
        }
    }

    if ( ! empty( $data['records'] ) && is_array( $data['records'] ) ) {
        foreach ( $data['records'] as $i => $record ) {
            if ( isset( $record['jurisdiction_id'] ) && is_string( $record['jurisdiction_id'] ) ) {
                $normalized = strtoupper( trim( $record['jurisdiction_id'] ) );
                if ( $normalized !== $record['jurisdiction_id'] ) {
                    $data['records'][ $i ]['jurisdiction_id'] = $normalized;
                    $notes[] = sprintf( 'Normalized records[%d].jurisdiction_id to uppercase.', $i );
                }
            }
        }
    }

    if ( isset( $data['meta'] ) && is_array( $data['meta'] ) && isset( $data['records'] ) && is_array( $data['records'] ) ) {
        $actual = count( $data['records'] );
        $declared = isset( $data['meta']['record_count'] ) ? (int) $data['meta']['record_count'] : null;
        if ( $declared !== $actual ) {
            $data['meta']['record_count'] = $actual;
            $notes[] = sprintf( 'Adjusted meta.record_count from %s to %d.', (string) $declared, $actual );
        }
    }

    if ( isset( $data['meta'] ) && is_array( $data['meta'] ) && empty( $data['meta']['batch_completed'] ) ) {
        $data['meta']['batch_completed'] = gmdate( 'Y-m-d H:i UTC' );
        $notes[] = 'Filled missing meta.batch_completed with current UTC timestamp.';
    }

    return [ 'data' => $data, 'notes' => array_values( array_unique( $notes ) ) ];
}

function ws_ingest_stamp_archive_notes( array $data, array $notes ): array {
    if ( empty( $notes ) ) {
        return $data;
    }

    if ( empty( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
        $data['meta'] = [];
    }

    $existing = $data['meta']['ws_ingest_archive_notes'] ?? [];
    if ( ! is_array( $existing ) ) {
        $existing = [ (string) $existing ];
    }

    $merged = array_values( array_unique( array_merge( $existing, $notes ) ) );
    $data['meta']['ws_ingest_archive_notes'] = $merged;
    $data['meta']['ws_ingest_archive_corrected_on'] = gmdate( 'Y-m-d H:i UTC' );

    return $data;
}

function ws_ingest_archive_json_file( string $source_path, string $filename, array $data ): array {
    ws_ingest_bootstrap_log_dir();

    $stamp = gmdate( 'Ymd-His' );
    $target_path = trailingslashit( WS_INGEST_ARCHIVE_DIR ) . $stamp . '-' . basename( $filename );
    $encoded = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

    if ( ! is_string( $encoded ) || file_put_contents( $target_path, $encoded ) === false ) {
        return [ 'ok' => false, 'path' => '', 'error' => 'Failed to write archive JSON file.' ];
    }

    if ( ! @unlink( $source_path ) ) {
        return [ 'ok' => false, 'path' => $target_path, 'error' => 'Archived copy written, but failed to delete source file from inbox.' ];
    }

    return [ 'ok' => true, 'path' => $target_path, 'error' => '' ];
}

function ws_ingest_archive_raw_file( string $source_path, string $filename ): array {
    ws_ingest_bootstrap_log_dir();

    $stamp = gmdate( 'Ymd-His' );
    $target_path = trailingslashit( WS_INGEST_ARCHIVE_DIR ) . $stamp . '-' . basename( $filename );

    if ( @rename( $source_path, $target_path ) ) {
        return [ 'ok' => true, 'path' => $target_path, 'error' => '' ];
    }

    return [ 'ok' => false, 'path' => '', 'error' => 'Failed to move raw file to archive.' ];
}


// ── Confirmation payload helpers ─────────────────────────────────────────────

/**
 * Stores raw ingest JSON for confirmation step and returns the token key.
 */
function ws_ingest_store_confirm_payload( string $json, string $filename ): string {
    $token = strtolower( wp_generate_password( 20, false, false ) );
    $key   = 'ws_ingest_confirm_' . $token;

    set_transient( $key, [
        'user_id'  => get_current_user_id(),
        'json'     => $json,
        'filename' => $filename,
        'created'  => time(),
    ], WS_INGEST_CONFIRM_TTL );

    return $token;
}

/**
 * Loads a previously stored confirmation payload by token.
 */
function ws_ingest_load_confirm_payload( string $token ): ?array {
    $safe = preg_replace( '/[^a-z0-9]/', '', strtolower( $token ) );
    if ( empty( $safe ) ) {
        return null;
    }

    $data = get_transient( 'ws_ingest_confirm_' . $safe );
    if ( ! is_array( $data ) ) {
        return null;
    }

    if ( (int) ( $data['user_id'] ?? 0 ) !== get_current_user_id() ) {
        return null;
    }

    return $data;
}

/**
 * Deletes a stored confirmation payload token.
 */
function ws_ingest_delete_confirm_payload( string $token ): void {
    $safe = preg_replace( '/[^a-z0-9]/', '', strtolower( $token ) );
    if ( ! empty( $safe ) ) {
        delete_transient( 'ws_ingest_confirm_' . $safe );
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
 * Only blacklists terms that are BOTH pending AND not yet registered
 * in WordPress. A term that has been approved and seeded should never
 * be blacklisted even if its log status has not been updated.
 *
 * @return array [ 'term_id' => 'taxonomy_slug' ]
 */
function ws_ingest_build_blacklist( array $log ): array {
    $blacklist = [];
    foreach ( $log['proposed_terms'] as $term ) {
        $status   = $term['status']   ?? 'pending';
        $term_id  = $term['term_id']  ?? '';
        $taxonomy = $term['taxonomy'] ?? '';

        if ( $status !== 'pending' ) continue;
        if ( ! $term_id || ! $taxonomy ) continue;

        // Do not blacklist if the term is already registered in WordPress.
        // This protects against the case where a term was approved and seeded
        // but the log status was not updated before the next ingest run.
        if ( term_exists( $term_id, $taxonomy ) ) continue;

        $blacklist[ $term_id ] = $taxonomy;
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

    // Bypass object cache to ensure newly seeded terms are visible.
    // Without this, get_terms() may return stale results if the
    // persistent object cache has not been invalidated since seeding.
    clean_taxonomy_cache( $taxonomy );

    $terms = get_terms( [
        'taxonomy'        => $taxonomy,
        'hide_empty'      => false,
        'fields'          => 'slugs',
        'cache_results'   => false,
        'update_term_meta_cache' => false,
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
    $jx_slug     = strtolower( (string) ( $record['jurisdiction_id'] ?? '' ) );
    $record_key  = $jx_slug && $sid !== 'UNKNOWN' ? strtolower( $jx_slug . '|' . $sid ) : '';
    $duplicates  = [];

    if ( $record_key ) {
        $duplicates = get_posts( [
            'post_type'      => 'jx-statute',
            'post_status'    => 'any',
            'meta_query'     => [ [
                'key'     => 'ws_ingest_record_key',
                'value'   => $record_key,
                'compare' => '=',
            ] ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );
    }

    // Legacy fallback for records ingested before ws_ingest_record_key existed.
    if ( empty( $duplicates ) ) {
        $legacy_citation = (string) ( $record['legal_basis']['statute_citation'] ?? '' );
        if ( $legacy_citation !== '' ) {
            $duplicates = get_posts( [
                'post_type'      => 'jx-statute',
                'post_status'    => 'any',
                'meta_query'     => [ [
                    'key'     => 'ws_jx_statute_citation',
                    'value'   => $legacy_citation,
                    'compare' => '=',
                ] ],
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ] );
        }
    }

    if ( ! empty( $duplicates ) ) {
        $result['warnings'][] = "$sid: duplicate detected (post #{$duplicates[0]}) — skipped.";
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

    // Source chain — full provenance record of all contributing models.
    // Populated by NotebookLM from its input files. Stored as JSON string.
    if ( ! empty( $meta['source_chain'] ) && is_array( $meta['source_chain'] ) ) {
        update_post_meta( $post_id, 'ws_auto_source_chain', wp_json_encode( $meta['source_chain'] ) );
    }

    // ── Step 4: Assign ws_jurisdiction taxonomy term ──────────────────────
    if ( $jx_slug ) {
        $term = get_term_by( 'slug', $jx_slug, WS_JURISDICTION_TAXONOMY );
        if ( $term && ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, $term->term_id, WS_JURISDICTION_TAXONOMY );
            $result['log'][] = "jurisdiction: assigned '{$jx_slug}'";
        } else {
            $result['warnings'][] = "$sid: jurisdiction term '{$jx_slug}' not found in ws_jurisdiction taxonomy.";
        }
    }

    if ( $record_key ) {
        update_post_meta( $post_id, 'ws_ingest_record_key', $record_key );
    }

    if ( $sid !== '' && $sid !== 'UNKNOWN' ) {
        // Canonical hidden key used by prompt exclusions.
        update_post_meta( $post_id, '_ws_jx_statute_id', sanitize_text_field( $sid ) );
        delete_post_meta( $post_id, '_ws_jx_statute_id_missing' );
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
function ws_ingest_write_run_log( array $result ): bool {
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

    return file_put_contents( $path, implode( "\n", $lines ) . "\n" ) !== false;
}


// ── Append-only ledger logs ───────────────────────────────────────────────────

/**
 * Appends a line to the preflight errors ledger.
 * One entry per failed preflight — filename, timestamp, reasons.
 */
function ws_ingest_log_preflight_failure( string $filename, array $errors ): bool {
    $path   = WS_INGEST_LOG_DIR . 'preflight-errors.log';
    $ts     = date( 'Y-m-d H:i:s' );
    $reason = implode( ' | ', $errors );
    $line   = "[{$ts} UTC]  {$filename}  —  {$reason}" . PHP_EOL;
    return file_put_contents( $path, $line, FILE_APPEND | LOCK_EX ) !== false;
}

/**
 * Appends a line to the imported batches ledger.
 * One entry per successfully processed batch.
 */
function ws_ingest_log_imported_batch( string $filename, array $summary, bool $with_errors ): bool {
    $path    = WS_INGEST_LOG_DIR . 'imported.log';
    $ts      = date( 'Y-m-d H:i:s' );
    $jx      = strtoupper( $summary['jurisdiction'] ?? 'XX' );
    $created = (int) ( $summary['created']  ?? 0 );
    $skipped = (int) ( $summary['skipped']  ?? 0 );
    $failed  = (int) ( $summary['failed']   ?? 0 );
    $errors  = $with_errors ? 'true' : 'false';
    $line    = "[{$ts} UTC]  {$filename}  {$jx}  created:{$created}  skipped:{$skipped}  failed:{$failed}  errors:{$errors}" . PHP_EOL;
    return file_put_contents( $path, $line, FILE_APPEND | LOCK_EX ) !== false;
}


/**
 * Appends citation breadcrumbs to citations-breadcrumbs.log.
 * One entry per statute that has attached_citations.
 * Human review trail only — not enough data for a jx-citation record.
 */
function ws_ingest_log_citation_breadcrumbs( string $filename, string $jx, string $statute_id, array $citations ): bool {
    if ( empty( $citations ) ) return true;

    $path = WS_INGEST_LOG_DIR . 'citations-breadcrumbs.log';
    $ts   = date( 'Y-m-d H:i:s' );

    $lines   = [];
    $lines[] = "[{$ts} UTC]  {$filename}  {$jx}  {$statute_id}";
    foreach ( $citations as $cite ) {
        $lines[] = '  ' . $cite;
    }
    $lines[] = '---';
    $lines[] = '';

    return file_put_contents( $path, implode( PHP_EOL, $lines ) . PHP_EOL, FILE_APPEND | LOCK_EX ) !== false;
}

function ws_ingest_process_batch_data( array $data, string $batch_filename ): array {
    $result = [
        'phase'            => 'processing',
        'preflight'        => null,
        'records'          => [],
        'summary'          => [],
        'errors'           => [],
        'runtime_warnings' => [],
        'confirm_token'    => '',
    ];

    $log             = ws_ingest_load_proposed_terms_log();
    $new_terms       = $data['meta']['new_terms_proposed'] ?? [];
    $merge_counts    = ws_ingest_merge_proposed_terms( $log, $new_terms );
    if ( ! ws_ingest_save_proposed_terms_log( $log ) ) {
        $result['runtime_warnings'][] = 'Failed to persist proposed-terms log merge. Ingest continues, but review queue may be stale.';
    }

    $blacklist = ws_ingest_build_blacklist( $log );
    $meta      = $data['meta'];
    $records   = $data['records'];

    $created  = 0;
    $skipped  = 0;
    $failed   = 0;
    $all_logs = [];

    foreach ( $records as $record ) {
        $record_result = ws_ingest_process_statute_record( $record, $meta, $blacklist );
        $sid           = $record['statute_id'] ?? 'UNKNOWN';

        $raw_citations = $record['citations']['attached_citations'] ?? [];
        if ( ! empty( $raw_citations ) ) {
            if ( ! ws_ingest_log_citation_breadcrumbs( $batch_filename, $meta['jurisdiction_id'] ?? '', $sid, $raw_citations ) ) {
                $result['runtime_warnings'][] = "$sid: failed to append citation breadcrumb log.";
            }
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
        'source_name'     => $meta['source_name']      ?? '',
        'source_method'   => $meta['source_method']    ?? '',
        'jurisdiction'    => $meta['jurisdiction_id']  ?? '',
        'batch_completed' => $meta['batch_completed']  ?? '',
    ];

    if ( ! ws_ingest_write_run_log( $result ) ) {
        $result['runtime_warnings'][] = 'Failed to write detailed run log file.';
    }

    $has_warnings = ! empty( array_filter(
        array_column( $result['records'], 'warnings' ),
        fn( $w ) => ! empty( $w )
    ) );
    if ( ! ws_ingest_log_imported_batch( $batch_filename, $result['summary'], $has_warnings ) ) {
        $result['runtime_warnings'][] = 'Failed to append imported batch ledger log.';
    }

    return $result;
}

function ws_handle_ingest_folder_submission(): array {
    $result = [
        'phase'            => 'folder-processing',
        'preflight'        => null,
        'records'          => [],
        'summary'          => [],
        'errors'           => [],
        'runtime_warnings' => [],
        'confirm_token'    => '',
        'folder'           => [
            'inbox_count'        => 0,
            'processed_files'    => 0,
            'archived_files'     => 0,
            'corrected_files'    => 0,
            'ready_files'        => 0,
            'blocked_files'      => 0,
            'created_total'      => 0,
            'skipped_total'      => 0,
            'failed_total'       => 0,
            'limit'              => 0,
            'dry_run'            => false,
            'files'              => [],
        ],
    ];

    $limit = max( 1, min( 100, (int) ( $_POST['ws_ingest_folder_limit'] ?? 25 ) ) );
    $dry_run = ! empty( $_POST['ws_ingest_folder_dry_run'] );
    $inbox_files = ws_ingest_get_inbox_files();
    $result['folder']['inbox_count'] = count( $inbox_files );
    $result['folder']['limit'] = $limit;
    $result['folder']['dry_run'] = $dry_run;

    if ( empty( $inbox_files ) ) {
        $result['errors'][] = 'Inbox is empty. Upload JSON files to the ingest inbox folder first.';
        return $result;
    }

    $to_process = array_slice( $inbox_files, 0, $limit );

    foreach ( $to_process as $source_path ) {
        $filename = basename( $source_path );
        $file_report = [
            'filename'    => $filename,
            'status'      => 'unknown',
            'corrections' => [],
            'errors'      => [],
            'summary'     => [],
            'archive'     => '',
        ];

        $raw = @file_get_contents( $source_path );
        if ( $raw === false ) {
            $file_report['status'] = 'read-failed';
            $file_report['errors'][] = 'Unable to read file from inbox.';
            $result['folder']['files'][] = $file_report;
            continue;
        }

        $decoded = ws_ingest_decode_json_payload( (string) $raw );
        if ( ! $decoded['ok'] ) {
            $file_report['status'] = $dry_run ? 'invalid-json-dry-run' : 'invalid-json';
            $file_report['errors'][] = 'JSON parse error: ' . $decoded['error'];
            $file_report['corrections'] = $decoded['corrections'];

            if ( ! $dry_run ) {
                $archive_raw = ws_ingest_archive_raw_file( $source_path, $filename );
                if ( $archive_raw['ok'] ) {
                    $file_report['archive'] = $archive_raw['path'];
                    $result['folder']['archived_files']++;
                } else {
                    $file_report['errors'][] = $archive_raw['error'];
                }
            }

            $result['folder']['processed_files']++;
            $result['folder']['blocked_files']++;
            if ( ! $dry_run ) {
                $result['folder']['failed_total']++;
            }
            $result['folder']['files'][] = $file_report;
            continue;
        }

        $data = $decoded['data'];
        $all_corrections = $decoded['corrections'];

        $fixed = ws_ingest_apply_safe_json_corrections( $data );
        $data  = $fixed['data'];
        $all_corrections = array_values( array_unique( array_merge( $all_corrections, $fixed['notes'] ) ) );
        $file_report['corrections'] = $all_corrections;

        $preflight = ws_ingest_preflight( $data );
        if ( ! $preflight['pass'] ) {
            $file_report['status'] = $dry_run ? 'preflight-failed-dry-run' : 'preflight-failed';
            $file_report['errors'] = array_merge( $file_report['errors'], $preflight['errors'] );
            if ( ! $dry_run ) {
                if ( ! ws_ingest_log_preflight_failure( $filename, $preflight['errors'] ) ) {
                    $result['runtime_warnings'][] = "{$filename}: failed to append preflight failure ledger log.";
                }

                $archived_payload = ws_ingest_stamp_archive_notes( $data, $all_corrections );
                $archive_fail = ws_ingest_archive_json_file( $source_path, $filename, $archived_payload );
                if ( $archive_fail['ok'] ) {
                    $file_report['archive'] = $archive_fail['path'];
                    $result['folder']['archived_files']++;
                } else {
                    $file_report['errors'][] = $archive_fail['error'];
                }
            }

            $result['folder']['processed_files']++;
            $result['folder']['blocked_files']++;
            if ( ! $dry_run ) {
                $result['folder']['failed_total']++;
            }
            if ( ! empty( $all_corrections ) ) {
                $result['folder']['corrected_files']++;
            }
            $result['folder']['files'][] = $file_report;
            continue;
        }

        if ( $dry_run ) {
            $file_report['status'] = 'ready-dry-run';
            $file_report['summary'] = [
                'would_records'       => count( (array) ( $data['records'] ?? [] ) ),
                'preflight_warnings'  => count( (array) ( $preflight['warnings'] ?? [] ) ),
            ];

            $result['folder']['processed_files']++;
            $result['folder']['ready_files']++;
            if ( ! empty( $all_corrections ) ) {
                $result['folder']['corrected_files']++;
            }
            $result['folder']['files'][] = $file_report;
            continue;
        }

        $batch_result = ws_ingest_process_batch_data( $data, $filename );
        $file_report['status'] = 'processed';
        $file_report['summary'] = $batch_result['summary'] ?? [];
        if ( ! empty( $batch_result['runtime_warnings'] ) ) {
            $file_report['errors'] = array_merge( $file_report['errors'], $batch_result['runtime_warnings'] );
        }

        $result['folder']['created_total'] += (int) ( $batch_result['summary']['created'] ?? 0 );
        $result['folder']['skipped_total'] += (int) ( $batch_result['summary']['skipped'] ?? 0 );
        $result['folder']['failed_total']  += (int) ( $batch_result['summary']['failed'] ?? 0 );

        $archived_payload = ws_ingest_stamp_archive_notes( $data, $all_corrections );
        $archive_ok = ws_ingest_archive_json_file( $source_path, $filename, $archived_payload );
        if ( $archive_ok['ok'] ) {
            $file_report['archive'] = $archive_ok['path'];
            $result['folder']['archived_files']++;
        } else {
            $file_report['errors'][] = $archive_ok['error'];
        }

        $result['folder']['processed_files']++;
        if ( ! empty( $all_corrections ) ) {
            $result['folder']['corrected_files']++;
        }
        $result['folder']['files'][] = $file_report;
    }

    $result['summary'] = [
        'created' => $result['folder']['created_total'],
        'skipped' => $result['folder']['skipped_total'],
        'failed'  => $result['folder']['failed_total'],
    ];

    return $result;
}

// ── Main handler ──────────────────────────────────────────────────────────────

function ws_handle_ingest_submission(): array {
    $result = [
        'phase'     => '',
        'preflight' => null,
        'records'   => [],
        'summary'   => [],
        'errors'    => [],
        'runtime_warnings' => [],
        'confirm_token' => '',
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

    $mode = sanitize_key( wp_unslash( $_POST['ws_ingest_mode'] ?? 'manual' ) );
    if ( $mode === 'folder' ) {
        return ws_handle_ingest_folder_submission();
    }

    $batch_filename = sanitize_text_field( wp_unslash( $_POST['ws_ingest_filename'] ?? 'unknown' ) );

    // ── Read JSON ────────────────────────────────────────────────────────
    $confirmed      = ! empty( $_POST['ws_ingest_confirmed'] );
    $confirm_token  = sanitize_text_field( wp_unslash( $_POST['ws_ingest_confirm_token'] ?? '' ) );
    $json_input     = '';

    if ( $confirmed ) {
        if ( empty( $confirm_token ) ) {
            $result['errors'][] = 'Confirmation token missing. Please run pre-flight again.';
            return $result;
        }

        $payload = ws_ingest_load_confirm_payload( $confirm_token );
        if ( ! $payload ) {
            $result['errors'][] = 'Confirmation payload expired or invalid. Please run pre-flight again.';
            return $result;
        }

        $json_input = (string) ( $payload['json'] ?? '' );
        if ( $batch_filename === 'unknown' && ! empty( $payload['filename'] ) ) {
            $batch_filename = sanitize_text_field( $payload['filename'] );
        }

        // Single-use token.
        ws_ingest_delete_confirm_payload( $confirm_token );
    } else {
        $json_input = (string) wp_unslash( $_POST['ws_ingest_json'] ?? '' );
    }

    if ( trim( $json_input ) === '' ) {
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
        if ( ! ws_ingest_log_preflight_failure( $batch_filename, $preflight['errors'] ) ) {
            $result['runtime_warnings'][] = 'Failed to append preflight failure ledger log. Check filesystem permissions.';
        }
        return $result;
    }

    // Check if user confirmed after seeing preflight
    if ( ! $confirmed ) {
        $result['confirm_token'] = ws_ingest_store_confirm_payload( $json_input, $batch_filename );
        if ( empty( $result['confirm_token'] ) ) {
            $result['errors'][] = 'Failed to store confirmation payload. Please try again; if this persists, check object cache/transient storage.';
        }
        // Return preflight results — show confirmation UI
        return $result;
    }

    return ws_ingest_process_batch_data( $data, $batch_filename );
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
    $confirm_token     = $run_result['confirm_token'] ?? '';
    $show_confirmation = ( $phase === 'preflight' && $preflight && $preflight['pass'] && ! $confirmed && ! empty( $confirm_token ) );
    $json_input        = (string) wp_unslash( $_POST['ws_ingest_json'] ?? '' );
    $batch_filename    = sanitize_text_field( wp_unslash( $_POST['ws_ingest_filename'] ?? '' ) );
    $inbox_files       = ws_ingest_get_inbox_files();

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

        <?php if ( ! empty( $run_result['runtime_warnings'] ) ): ?>
            <div class="notice notice-warning">
                <p><strong>Ingest completed with runtime warnings.</strong></p>
                <?php foreach ( $run_result['runtime_warnings'] as $warning ): ?>
                    <p><?php echo esc_html( $warning ); ?></p>
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
                <?php if ( (int) $s['skipped'] > 0 ): ?>
                    <p>Batch process detected duplicates. Duplicates were skipped.</p>
                <?php endif; ?>
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

        <?php elseif ( $phase === 'folder-processing' && ! empty( $run_result['folder'] ) ): ?>
            <?php $f = $run_result['folder']; ?>
            <div class="notice notice-<?php echo ( (int) ( $f['failed_total'] ?? 0 ) > 0 ) ? 'warning' : 'success'; ?>">
                <?php if ( ! empty( $f['dry_run'] ) ): ?>
                    <p><strong>Folder ingest dry run complete.</strong></p>
                <?php else: ?>
                    <p><strong>Folder ingest iteration complete.</strong></p>
                <?php endif; ?>
                <p>
                    Files processed: <strong><?php echo (int) ( $f['processed_files'] ?? 0 ); ?></strong>
                    of <?php echo (int) ( $f['limit'] ?? 0 ); ?> requested
                    (inbox had <?php echo (int) ( $f['inbox_count'] ?? 0 ); ?>).
                </p>
                <?php if ( ! empty( $f['dry_run'] ) ): ?>
                    <p>
                        Ready files: <strong><?php echo (int) ( $f['ready_files'] ?? 0 ); ?></strong>
                        &nbsp;|&nbsp; Blocked files: <strong><?php echo (int) ( $f['blocked_files'] ?? 0 ); ?></strong>
                        &nbsp;|&nbsp; Corrected JSON previews: <strong><?php echo (int) ( $f['corrected_files'] ?? 0 ); ?></strong>
                    </p>
                    <p>No records were written and no files were moved in dry run mode.</p>
                    <?php if ( (int) ( $f['ready_files'] ?? 0 ) > 0 ): ?>
                        <form method="post" action="" style="margin:8px 0 0 0;">
                            <?php wp_nonce_field( 'ws_run_ingest', 'ws_ingest_nonce' ); ?>
                            <input type="hidden" name="ws_ingest_mode" value="folder">
                            <input type="hidden" name="ws_ingest_folder_limit" value="<?php echo esc_attr( (string) ( $f['limit'] ?? 25 ) ); ?>">
                            <input type="submit" class="button button-primary" value="Execute Now (Run Without Dry Run)">
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p>
                        Records — ✅ Created: <strong><?php echo (int) ( $f['created_total'] ?? 0 ); ?></strong>
                        &nbsp;|&nbsp; ⏭ Skipped: <strong><?php echo (int) ( $f['skipped_total'] ?? 0 ); ?></strong>
                        &nbsp;|&nbsp; ❌ Failed: <strong><?php echo (int) ( $f['failed_total'] ?? 0 ); ?></strong>
                    </p>
                    <?php if ( (int) ( $f['skipped_total'] ?? 0 ) > 0 ): ?>
                        <p>Batch process detected duplicates. Duplicates were skipped.</p>
                    <?php endif; ?>
                    <p>
                        Archived files: <strong><?php echo (int) ( $f['archived_files'] ?? 0 ); ?></strong>
                        &nbsp;|&nbsp; Corrected JSON files: <strong><?php echo (int) ( $f['corrected_files'] ?? 0 ); ?></strong>
                    </p>
                <?php endif; ?>
            </div>

            <?php foreach ( (array) ( $f['files'] ?? [] ) as $item ): ?>
                <div style="margin:10px 0;padding:10px;border:1px solid #ccd0d4;border-radius:4px;background:#fff;">
                    <p style="margin:0 0 8px 0;"><strong><?php echo esc_html( $item['filename'] ?? '' ); ?></strong> — <?php echo esc_html( $item['status'] ?? 'unknown' ); ?></p>

                    <?php if ( ! empty( $item['summary'] ) ): ?>
                        <p style="margin:0 0 6px 0;color:#555;">
                            <?php if ( ! empty( $f['dry_run'] ) ): ?>
                                would process <?php echo (int) ( $item['summary']['would_records'] ?? 0 ); ?> records,
                                preflight warnings <?php echo (int) ( $item['summary']['preflight_warnings'] ?? 0 ); ?>
                            <?php else: ?>
                                created <?php echo (int) ( $item['summary']['created'] ?? 0 ); ?>,
                                skipped <?php echo (int) ( $item['summary']['skipped'] ?? 0 ); ?>,
                                failed <?php echo (int) ( $item['summary']['failed'] ?? 0 ); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty( $item['corrections'] ) ): ?>
                        <p style="margin:0 0 6px 0;color:#555;"><strong>Corrections:</strong></p>
                        <ul style="margin:4px 0 8px 18px;color:#555;">
                            <?php foreach ( (array) $item['corrections'] as $note ): ?>
                                <li><?php echo esc_html( $note ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( ! empty( $item['errors'] ) ): ?>
                        <ul style="margin:4px 0 8px 18px;color:#c00;">
                            <?php foreach ( (array) $item['errors'] as $err ): ?>
                                <li><?php echo esc_html( $err ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( ! empty( $item['archive'] ) ): ?>
                        <p style="margin:0;color:#555;"><strong>Archived:</strong> <?php echo esc_html( str_replace( ABSPATH, '/', (string) $item['archive'] ) ); ?></p>
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
                <input type="hidden" name="ws_ingest_confirm_token" value="<?php echo esc_attr( $confirm_token ); ?>">
                <input type="hidden" name="ws_ingest_filename" value="<?php echo esc_attr( $batch_filename ); ?>">
                <p>
                    <input type="submit" class="button button-primary" value="✅ Confirm — Write Records">
                    &nbsp;
                    <a href="<?php echo admin_url( 'tools.php?page=ws-ingest-tool' ); ?>" class="button">Cancel</a>
                </p>
            </form>

        <?php else: ?>
            <?php // Initial form ?>

            <h2>Folder Batch Mode</h2>
            <p>Upload JSON files via FTP to the inbox directory. If the folder is non-empty, you can process files in iterations.</p>
            <p>
                <strong>Inbox:</strong> <code><?php echo esc_html( str_replace( ABSPATH, '/', WS_INGEST_INBOX_DIR ) ); ?></code><br>
                <strong>Archive:</strong> <code><?php echo esc_html( str_replace( ABSPATH, '/', WS_INGEST_ARCHIVE_DIR ) ); ?></code>
            </p>

            <?php if ( empty( $inbox_files ) ): ?>
                <div class="notice notice-info"><p>Inbox is currently empty.</p></div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><strong>Inbox ready:</strong> <?php echo (int) count( $inbox_files ); ?> file(s) available.</p>
                </div>
                <details style="margin:0 0 12px 0;">
                    <summary>Show inbox files</summary>
                    <ul style="margin:8px 0 0 18px;">
                        <?php foreach ( $inbox_files as $pending ): ?>
                            <li><?php echo esc_html( basename( $pending ) ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>

            <form method="post" action="" style="margin-bottom:20px;">
                <?php wp_nonce_field( 'ws_run_ingest', 'ws_ingest_nonce' ); ?>
                <input type="hidden" name="ws_ingest_mode" value="folder">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ws_ingest_folder_limit">Files This Iteration</label></th>
                        <td>
                            <input type="number" name="ws_ingest_folder_limit" id="ws_ingest_folder_limit"
                                   class="small-text" min="1" max="100" value="<?php echo esc_attr( (string) ( $_POST['ws_ingest_folder_limit'] ?? '25' ) ); ?>">
                            <p class="description">Processes the first N JSON files from inbox (alphabetical). Processed files are archived.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ws_ingest_folder_dry_run">Dry Run</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ws_ingest_folder_dry_run" id="ws_ingest_folder_dry_run" value="1" <?php checked( ! empty( $_POST['ws_ingest_folder_dry_run'] ) ); ?>>
                                Preflight and preview corrections only (no record writes, no archive moves)
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Run Folder Ingest Iteration" <?php disabled( empty( $inbox_files ) ); ?>>
                </p>
            </form>

            <hr>
            <h2>Single File / Manual Mode</h2>

            <form method="post" action="">
                <?php wp_nonce_field( 'ws_run_ingest', 'ws_ingest_nonce' ); ?>
                <input type="hidden" name="ws_ingest_mode" value="manual">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ws_ingest_filename">Batch Filename</label></th>
                        <td>
                            <input type="text" name="ws_ingest_filename" id="ws_ingest_filename"
                                   class="regular-text"
                                   placeholder="e.g. NJ-7-Statutes-NotebookLM-20260403-0843.json"
                                value="<?php echo esc_attr( $batch_filename ); ?>">
                            <p class="description">Used in the run logs for traceability. Paste the original filename of the JSON file.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ws_ingest_json">JSON Batch</label></th>
                        <td>
                            <textarea name="ws_ingest_json" id="ws_ingest_json"
                                      rows="20" class="large-text code"
                                      placeholder='Paste the complete JSON object here — {"meta":{...},"records":[...],"integrity":{...}}'
                                      required><?php echo esc_textarea( $json_input ); ?></textarea>
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
