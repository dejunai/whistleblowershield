<?php
/**
 * query-jurisdiction.php
 *
 * Jurisdiction Query Layer
 *
 * PURPOSE
 * -------
 * Provides centralized functions for retrieving jurisdiction records and
 * their associated datasets.
 *
 * This file acts as the primary data access layer for the WhistleblowerShield
 * plugin. By consolidating queries here we avoid repeating WP_Query logic
 * throughout the plugin and maintain consistent behavior across shortcodes
 * and templates.
 *
 * ARCHITECTURE
 * ------------
 *
 * jurisdiction (public CPT)
 *      ├── jx-summary       (attach via ws_jurisdiction taxonomy)
 *      ├── jx-statute       (attach_flag + order, ws_jurisdiction taxonomy scope)
 *      ├── jx-citation      (attach_flag + order, ws_jurisdiction taxonomy scope)
 *      └── jx-interpretation (attach_flag + order, ws_jurisdiction taxonomy scope)
 *
 * JURISDICTION IDENTITY
 * ---------------------
 * The canonical two-letter code for each jurisdiction is the slug of its
 * assigned ws_jurisdiction taxonomy term (e.g., 'ca', 'tx', 'us').
 * All lookups use taxonomy queries.
 *
 * ws_jx_term_id post meta is written on each jurisdiction post (by the seeder
 * and the save_post_jurisdiction hook below) as a convenience for direct
 * post->term_id lookups without a get_term_by() call at runtime.
 *
 * CACHING
 * -------
 * Transient caching is used for expensive or repeated queries.
 * All jurisdiction transients are invalidated on save_post for the
 * jurisdiction CPT.
 *
 * Transient keys:
 *      ws_id_for_term_{term_id}    — post ID lookup by taxonomy term ID
 *      WS_CACHE_ALL_JURISDICTIONS  — full post object list
 *      WS_CACHE_JX_INDEX           — index data with counts
 *
 * STAMP META KEYS
 * ---------------
 * All CPTs share identical ws_auto_ prefixed stamp meta keys (see ws-core.php
 * META KEY NAMING RULES). The GMT audit keys are private (_ws_auto_*) and are
 * not exposed through the query layer:
 *
 *      ws_auto_date_created        — local date (Y-m-d), written once
 *      ws_auto_create_author       — WP user ID, written once
 *      ws_auto_last_edited         — local date (Y-m-d), written every save
 *      ws_auto_last_edited_author  — WP user ID, written every save (admin-overridable)
 *
 * DATASET RETURN FORMAT
 * ---------------------
 * All dataset functions return a consistent base array. Keys are plain PHP
 * array keys — the ws_ / ws_auto_ meta key prefixes are stripped at this
 * layer and must not reappear downstream.
 *
 *      [
 *          'id'      => int,
 *          'title'   => string,
 *          'url'     => string,
 *          'status'  => string,  // WP post status
 *          'content' => string,  // raw post_content — apply the_content in render layer
 *          'record'  => [
 *              'created_by'      => int,    // WP user ID (ws_auto_create_author)
 *              'created_by_name' => string, // display name resolved from created_by
 *              'created_date'    => string, // Y-m-d local (ws_auto_date_created)
 *              'edited_by'       => int,    // WP user ID (ws_auto_last_edited_author)
 *              'edited_by_name'  => string, // display name resolved from edited_by
 *              'edited_date'     => string, // Y-m-d local (ws_auto_last_edited)
 *          ],
 *          'plain'  => [ ... ],  // CPTs with plain English workflow — see ws_build_plain_english_array()
 *          'verify' => [ ... ],  // all CPTs — see ws_build_source_verify_array()
 *      ]
 *
 * ws_get_jx_statute_data(), ws_get_jx_citation_data(), and
 * ws_get_jx_interpretation_data() return arrays-of-arrays using the same
 * shape, plus an 'is_fed' boolean key. Each may contain two groups
 * (jurisdiction-scoped + federal append).
 *
 * Expand each dataset array as those CPTs are defined.
 *
 * NOTE: Audit trail data (_ws_last_edited_by, _ws_edit_history) is stored
 * in wp_postmeta as private hidden keys and is NOT retrieved through this
 * query layer. Use ws_get_last_editor( $post_id ) and
 * ws_get_edit_history( $post_id ) defined in admin-audit-trail.php instead.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @version 3.10.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0   Initial release.
 * 2.1.0   Refactored for ws-core architecture.
 * 2.3.1   Content keys normalized to raw post_content.
 * 3.0.0   Taxonomy-based lookups throughout; post meta join removed.
 * 3.1.0   record sub-array added; stamp fields unprefixed in return keys.
 * 3.2.0   Legal update system overhaul; tax_query jurisdiction filter.
 * 3.3.2   ws_/ws_auto_ prefixes stripped from all query layer return keys.
 * 3.5.0   ws_get_jx_statute_data() rebuilt for ingest alignment.
 * 3.6.0   Query layer split: helpers → shared → jurisdiction → agencies.
 * 3.7.0   ws_get_nationwide_assist_org_data() added.
 * 3.8.0   Court label resolution via ws_court_lookup(). Reference page anchor support.
 * 3.9.0   Summary gate on index. Frontend repeater fallback. Services via taxonomy.
 * 3.10.0  ws_procedure_type taxonomy reads added.
 *
 * @package WhistleblowerShield
 * @since   1.0.0
 * @version 3.10.2
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Term ID Lookup by Code
//
// Resolves a two-letter USPS code to the ws_jurisdiction taxonomy term ID.
// This is step one of the code → post ID chain and is exposed as its own
// helper so callers that need only the term ID (e.g. to scope a dataset
// query directly) can stop here without the extra post lookup.
//
// Returns the integer term ID, or 0 if the term cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Resolves a two-letter USPS jurisdiction code to its ws_jurisdiction term ID.
 *
 * @param  string $jx_code  Two-letter USPS code (case-insensitive).
 * @return int               Term ID, or 0 if not found.
 */
function ws_get_term_id_by_code( $jx_code ) {

    if ( empty( $jx_code ) ) {
        return 0;
    }

    $term = ws_jx_term_by_code( sanitize_text_field( $jx_code ) );

    if ( ! $term || is_wp_error( $term ) ) {
        return 0;
    }

    return (int) $term->term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction Post ID Lookup by Code
//
// Resolves a two-letter USPS code to the jurisdiction post ID.
// Composes ws_get_term_id_by_code() with a cached tax_query post lookup.
//
// Result is cached in a transient keyed by taxonomy term ID for 24 hours.
// Returns false if the term or jurisdiction post cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Resolves a two-letter USPS jurisdiction code to its jurisdiction post ID.
 *
 * @param  string    $jx_code  Two-letter USPS code (case-insensitive).
 * @return int|false           Post ID, or false if not found.
 */
function ws_get_id_by_code( $jx_code ) {

    $term_id = ws_get_term_id_by_code( $jx_code );

    if ( ! $term_id ) {
        return false;
    }

    $cache_key = 'ws_id_for_term_' . $term_id;
    $post_id   = get_transient( $cache_key );

    if ( false === $post_id ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ],
        ] );

        $post_id = ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
        set_transient( $cache_key, $post_id, DAY_IN_SECONDS );
    }

    return $post_id ?: false;
}


// ════════════════════════════════════════════════════════════════════════════
// Input Resolver
//
// Resolves a mixed $input (numeric post ID or two-letter USPS code string)
// to a jurisdiction post ID integer. Used by all dataset retrieval functions
// to eliminate the repeated is_numeric ternary.
//
// For callers that need a term ID rather than a post ID, use
// ws_get_term_id_by_code() directly.
//
// Returns 0 if input is empty or the code cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

function ws_resolve_jx_id( $input ) {
    if ( ! $input ) return 0;
    return is_numeric( $input ) ? (int) $input : (int) ws_get_id_by_code( (string) $input );
}


// ════════════════════════════════════════════════════════════════════════════
// Master Jurisdiction Data Fetcher
//
// Accepts either a numeric post ID or a two-letter USPS code string.
// Falls back to the global $post if no input is provided.
//
// Returns a structured array of all jurisdiction metadata, or false if the
// post cannot be resolved or is not a jurisdiction CPT record.
//
// Flag data is retrieved as an array from ACF (return_format: array)
// and destructured here for consistent downstream access.
//
// Record management fields use unprefixed stamp meta keys via get_post_meta(),
// consistent with all other dataset functions.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_data( $input = null ) {

    if ( ! $input ) {
        global $post;
        $post_id = $post->ID ?? 0;
    } else {
        $post_id = ws_resolve_jx_id( $input );
    }

    if ( ! $post_id || get_post_type( $post_id ) !== 'jurisdiction' ) {
        return false;
    }

    $flag_id  = (int) get_post_meta( $post_id, 'ws_jx_flag', true );
    $jx_terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY, [ 'fields' => 'slugs' ] );
    $jx_code  = ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) ? strtoupper( $jx_terms[0] ) : '';

    return [

        // ── Identity ─────────────────────────────────────────────────────────
        'id'         => $post_id,
        'name'       => get_the_title( $post_id ),
        'class'      => get_field( 'ws_jurisdiction_class', $post_id ),
        'code'       => $jx_code,
        // ws_jx_term_id is the ws_jurisdiction taxonomy term ID written by
        // the seeder and save_post_jurisdiction hook. Returned here for
        // callers that need the term ID directly without a get_term_by() call.
        'jx_term_id' => (int) get_post_meta( $post_id, 'ws_jx_term_id', true ),

        // ── Flag ─────────────────────────────────────────────────────────────
        // ACF returns the raw attachment ID for image fields in some contexts;
        // bypass get_field() and resolve the URL directly via WP core.
        'flag' => [
            'url'         => $flag_id ? wp_get_attachment_image_url( $flag_id, 'full' ) : '',
            'attribution' => get_post_meta( $post_id, 'ws_jx_flag_attribution', true ),
            'source_url'  => get_post_meta( $post_id, 'ws_jx_flag_source_url',  true ),
            'license'     => get_post_meta( $post_id, 'ws_jx_flag_license',     true ),
        ],

        // ── Government Links ─────────────────────────────────────────────────
        'gov' => [
            'portal_url'        => get_field( 'ws_jx_gov_portal_url',     $post_id ),
            'portal_label'      => get_field( 'ws_jx_gov_portal_label',   $post_id ),
            'executive_url'     => get_field( 'ws_jx_executive_url',      $post_id ),
            'executive_label'   => get_field( 'ws_jx_executive_label',    $post_id ),
            'authority_url'     => get_field( 'ws_jx_wb_authority_url',   $post_id ),
            'authority_label'   => get_field( 'ws_jx_wb_authority_label', $post_id ),
            'legislature_url'   => get_field( 'ws_jx_legislature_url',    $post_id ),
            'legislature_label' => get_field( 'ws_jx_legislature_label',  $post_id ),
        ],

        // ── Record Management ─────────────────────────────────────────────────
        // Read via get_post_meta() using unprefixed stamp keys, consistent with
        // all other dataset functions. Stamp values are written by
        // ws_acf_write_stamp_fields() in admin-hooks.php.
        'record' => ws_build_record_array( $post_id ),

    ];
}


// ════════════════════════════════════════════════════════════════════════════
// ws_parse_jx_limitations_meta()
//
// Frontend fallback for the ws_jx_limitations ACF repeater.
//
// ACF field definitions are only registered in the admin layer, so
// get_field() returns false on the frontend for repeater fields. This
// function reads the raw post meta keys that ACF writes for repeaters
// and returns them in the same shape get_field() would return:
//   [ ['ws_jx_limit_label' => '...', 'ws_jx_limit_text' => '...'], ... ]
//
// Only called when get_field() returns false (frontend or WP-CLI context).
//
// @param  int    $sid  jx-summary post ID.
// @return array        Rows array, or empty array if none saved.
// ════════════════════════════════════════════════════════════════════════════

function ws_parse_jx_limitations_meta( $sid ) {
    $count = (int) get_post_meta( $sid, 'ws_jx_limitations', true );
    if ( ! $count ) {
        return [];
    }
    $rows = [];
    for ( $i = 0; $i < $count; $i++ ) {
        $label = (string) get_post_meta( $sid, "ws_jx_limitations_{$i}_ws_jx_limit_label", true );
        $text  = (string) get_post_meta( $sid, "ws_jx_limitations_{$i}_ws_jx_limit_text",  true );
        if ( $label || $text ) {
            $rows[] = [
                'ws_jx_limit_label' => $label,
                'ws_jx_limit_text'  => $text,
            ];
        }
    }
    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Summary
//
// Retrieves the jx-summary post assigned to the given ws_jurisdiction term
// and returns a fully-hydrated data array.
//
// jx-summary is inherently plain English. It does not use the has_plain_english
// / plain_reviewed workflow and carries no summarized_by / summarized_date
// stamps.
//
// Returns false if no jx-summary is found for the term.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_summary_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return false;
    }

    $ids = get_posts( [
        'post_type'      => 'jx-summary',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    if ( empty( $ids ) ) {
        return false;
    }

    $sid = (int) $ids[0];

    return [
        'id'            => $sid,
        'title'         => get_the_title( $sid ),
        'url'           => get_permalink( $sid ),
        'status'        => get_post_status( $sid ),
        // Content fields
        'content'       => get_post_meta( $sid, 'ws_jurisdiction_summary_wysiwyg', true ),
        'sources'       => get_post_meta( $sid, 'ws_jx_summary_sources',   true ),
        'limitations'   => ws_parse_jx_limitations_meta( $sid ),
        'notes'         => get_post_meta( $sid, 'ws_jx_summary_notes',        true ),
        // jx-summary is inherently plain English; ws_has_plain_english is
        // implicitly true and no per-record toggle is stored or returned here.
        // Plain language fields
        'plain'         => ws_build_plain_english_array( $sid ),
        // Source & verification
        'verify'        => ws_build_source_verify_array( $sid ),
        // Record management
        'record'        => ws_build_record_array( $sid ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Statutes
//
// Returns the editorially curated jx-statute records for the jurisdiction
// summary page — published records assigned to the given ws_jurisdiction
// taxonomy term that have attach_flag = true, sorted by order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 statutes an editor
// has chosen to highlight on the summary page. All other statutes remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of statute data arrays, or empty array if none found.
//
// Federal append logic: if the requested jurisdiction is not US, US-scoped
// statutes are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_statute_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-statute',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $statute ) {
            $sid    = $statute->ID;
            $rows[] = [
                'id'      => $sid,
                'title'   => get_the_title( $sid ),
                'url'     => get_permalink( $sid ),
                'status'  => get_post_status( $sid ),
                'content' => get_post_field( 'post_content', $sid ),
                'order'   => (int) get_post_meta( $sid, 'ws_display_order', true ),
                'is_fed'  => $is_fed,

                // ── Legal Basis ───────────────────────────────────────────
                'official_name'        => get_post_meta( $sid, 'ws_jx_statute_official_name',       true ),
                'citation'             => get_post_meta( $sid, 'ws_jx_statute_citation',             true ),
                'common_name'          => get_post_meta( $sid, 'ws_jx_statute_common_name',          true ),
                'disclosure_type'      => get_field( 'ws_jx_statute_disclosure_type',      $sid ),
                'protected_class'      => get_field( 'ws_jx_statute_protected_class',      $sid ),
                'disclosure_targets'   => get_field( 'ws_jx_statute_disclosure_targets',   $sid ),
                'adverse_action_scope' => get_post_meta( $sid, 'ws_jx_statute_adverse_action_scope', true ),
                'attach_flag'          => (bool) get_post_meta( $sid, 'ws_attach_flag',              true ),

                // ── Statute of Limitations ────────────────────────────────
                'sol_value'           => get_post_meta( $sid, 'ws_jx_statute_sol_value',           true ),
                'sol_unit'            => get_post_meta( $sid, 'ws_jx_statute_sol_unit',            true ),
                'sol_trigger'         => get_post_meta( $sid, 'ws_jx_statute_sol_trigger',         true ),
                'sol_has_details'     => (bool) get_post_meta( $sid, 'ws_jx_statute_limit_ambiguous',     true ),
                'sol_details'         => get_post_meta( $sid, 'ws_jx_statute_limit_details',         true ),
                'tolling_has_details' => (bool) get_post_meta( $sid, 'ws_jx_statute_tolling_has_notes', true ),
                'tolling_details'     => get_post_meta( $sid, 'ws_jx_statute_tolling_notes',     true ),
                'has_exhaustion'      => (bool) get_post_meta( $sid, 'ws_jx_statute_exhaustion_required',      true ),
                'exhaustion_details'  => get_post_meta( $sid, 'ws_jx_statute_exhaustion_details',  true ),

                // ── Enforcement ───────────────────────────────────────────
                'process_type'     => get_field( 'ws_jx_statute_process_type',     $sid ),
                'adverse_action'   => get_field( 'ws_jx_statute_adverse_action',   $sid ),
                'fee_shifting'     => get_field( 'ws_jx_statute_fee_shifting',     $sid ),
                'remedies'         => get_field( 'ws_jx_statute_remedies',         $sid ),
                'related_agencies' => get_field( 'ws_jx_statute_related_agencies', $sid ),

                // ── Burden of Proof ───────────────────────────────────────
                'bop_standard'             => get_post_meta( $sid, 'ws_jx_statute_bop_standard',             true ),
                'employer_defense'         => get_field( 'ws_jx_statute_employer_defense', $sid ),
                'employer_defense_details' => get_post_meta( $sid, 'ws_jx_statute_employer_defense_details', true ),
                'rebuttable_has_details'   => (bool) get_post_meta( $sid, 'ws_jx_statute_rebuttable_has_presumption', true ),
                'rebuttable_details'       => get_post_meta( $sid, 'ws_jx_statute_rebuttable_presumption',       true ),
                'bop_has_details'          => (bool) get_post_meta( $sid, 'ws_jx_statute_bop_has_details',   true ),
                'bop_details'              => get_post_meta( $sid, 'ws_jx_statute_burden_of_proof_details',              true ),

                // ── Reward ────────────────────────────────────────────────
                'has_reward'     => (bool) get_post_meta( $sid, 'ws_jx_statute_reward_available',     true ),
                'reward_details' => get_post_meta( $sid, 'ws_jx_statute_reward_details', true ),

                // ── Links ─────────────────────────────────────────────────
                'statute_url' => get_post_meta( $sid, 'ws_jx_statute_url',        true ),
                'url_is_pdf'  => (bool) get_post_meta( $sid, 'ws_jx_statute_is_pdf', true ),

                'last_reviewed' => get_post_meta( $sid, 'ws_jx_statute_last_reviewed', true ),
                'ref_materials' => ws_get_ref_materials( $sid ),

                // Plain language fields
                'plain'  => ws_build_plain_english_array( $sid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $sid ),
                // Record management
                'record' => ws_build_record_array( $sid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction Term ID Helper
//
// Returns the ws_jurisdiction taxonomy term ID assigned to a jurisdiction
// post. Used by shortcodes to resolve the term_id before calling data
// functions without making taxonomy calls inside the shortcode itself.
//
// Returns 0 if the post has no term assigned.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_term_id( $post_id ) {
    $terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return 0;
    }
    return (int) $terms[0]->term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// US Term ID Helper
//
// Returns the ws_jurisdiction taxonomy term ID for the 'us' term.
// Result is cached in a static variable — one DB read per request.
//
// Reads the ws_us_term_id option written during taxonomy seeding.
// Falls back to a get_term_by() lookup if the option is not yet set
// (pre-seed or test environments).
//
// Used by data functions to determine whether federal append logic applies.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_us_term_id() {
    static $us_term_id = null;
    if ( $us_term_id !== null ) {
        return $us_term_id;
    }

    $stored = (int) get_option( 'ws_us_term_id', 0 );
    if ( $stored ) {
        $us_term_id = $stored;
        return $us_term_id;
    }

    $term = ws_jx_term_by_code( 'us' );
    $us_term_id = ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
    return $us_term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Citations
//
// Returns the editorially curated jx-citation records for the jurisdiction
// summary page — published records assigned to the given ws_jurisdiction
// taxonomy term that have attach_flag = true, sorted by order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 citations an editor
// has chosen to highlight on the summary page. All other citations remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of citation arrays, or empty array if none found.
//
// Federal append logic: if the requested jurisdiction is not US, US-scoped
// citations are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_citation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-citation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $citation ) {
            $cid    = $citation->ID;
            $rows[] = [
                'id'      => $cid,
                'title'   => get_the_title( $cid ),
                'url'     => get_permalink( $cid ),
                'status'  => get_post_status( $cid ),
                'content' => get_post_field( 'post_content', $cid ),
                'is_fed'  => $is_fed,
                // Citation-specific fields
                'type'            => get_post_meta( $cid, 'ws_jx_citation_type',          true ),
                'disclosure_type' => get_field( 'ws_jx_citation_disclosure_type', $cid ),
                'official_name'   => get_post_meta( $cid, 'ws_jx_citation_official_name',           true ),
                'common_name'     => get_post_meta( $cid, 'ws_jx_citation_common_name',             true ),
                'label'           => get_post_meta( $cid, 'ws_jx_citation_common_name',           true )
                                   ?: get_post_meta( $cid, 'ws_jx_citation_official_name',             true )
                                   ?: get_the_title( $cid ),
                'cite_url'        => get_post_meta( $cid, 'ws_jx_citation_url',           true ),
                'is_pdf'          => (bool) get_post_meta( $cid, 'ws_jx_citation_is_pdf', true ),
                'attach_flag'     => (bool) get_post_meta( $cid, 'ws_attach_flag',        true ),
                'order'           => (int)  get_post_meta( $cid, 'ws_display_order',      true ),
                'last_reviewed'   => get_post_meta( $cid, 'ws_jx_citation_last_reviewed', true ),
                'ref_materials'   => ws_get_ref_materials( $cid ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $cid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $cid ),
                // Record management
                'record' => ws_build_record_array( $cid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Interpretations
//
// Returns the editorially curated jx-interpretation records for the
// jurisdiction summary page — published records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true, sorted by
// order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 interpretations an
// editor has chosen to highlight on the summary page. All others remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of interpretation data arrays, or empty array if none found.
//
// Note: interpretations are US federal court decisions. When querying a
// non-US jurisdiction, local records are returned first and US-scoped records
// are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_interpretation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-interpretation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $interp ) {
            $iid    = $interp->ID;
            $rows[] = [
                'id'      => $iid,
                'title'   => get_the_title( $iid ),
                'url'     => get_permalink( $iid ),
                'status'  => get_post_status( $iid ),
                'content' => get_post_field( 'post_content', $iid ),
                'order'   => (int) get_post_meta( $iid, 'ws_display_order', true ),
                'is_fed'  => $is_fed,
                // Interpretation-specific fields
                'official_name' => get_post_meta( $iid, 'ws_jx_interp_official_name',            true ),
                'common_name'   => get_post_meta( $iid, 'ws_jx_interp_common_name',              true ),
                'citation'      => get_post_meta( $iid, 'ws_jx_interp_case_citation',    true ),
                'opinion_url'   => get_post_meta( $iid, 'ws_jx_interp_url',              true ),
                'court'         => ( ( $_ck = get_post_meta( $iid, 'ws_jx_interp_court', true ) ) === 'other' )
                                        ? ( get_post_meta( $iid, 'ws_jx_interp_court_name', true ) ?: 'Other' )
                                        : ( ( $crt = ws_court_lookup( $_ck ) ) ? $crt['short'] : $_ck ),
                'year'          => get_post_meta( $iid, 'ws_jx_interp_year',             true ),
                'favorable'     => (bool) get_post_meta( $iid, 'ws_jx_interp_favorable', true ),
                'summary'       => get_post_meta( $iid, 'ws_jx_interp_summary',          true ),
                'parent_statute_id' => (int) get_post_meta( $iid, 'ws_jx_interp_statute_id', true ),
                'process_type'  => ( ( $_pt = wp_get_object_terms( $iid, 'ws_process_type', [ 'fields' => 'slugs' ] ) ) && ! is_wp_error( $_pt ) && ! empty( $_pt ) ) ? $_pt[0] : '',
                'attach_flag'   => (bool) get_post_meta( $iid, 'ws_attach_flag',         true ),
                'last_reviewed' => get_post_meta( $iid, 'ws_jx_interp_last_reviewed',    true ),
                'ref_materials' => ws_get_ref_materials( $iid ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $iid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $iid ),
                // Record management
                'record' => ws_build_record_array( $iid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Agencies
//
// Returns all published ws-agency records assigned to the given
// ws_jurisdiction taxonomy term, ordered alphabetically.
//
// For jurisdiction pages, pass the US term ID to surface nationwide agencies.
// Records without the term assigned are excluded.
// Returns an array of agency data arrays, or empty array if none found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_agency_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return [];
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-agency',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $agency ) {
        $aid    = $agency->ID;
        $rows[] = [
            'id'            => $aid,
            'title'         => get_the_title( $aid ),
            'url'           => get_permalink( $aid ),
            'status'        => get_post_status( $aid ),
            // Agency fields
            'code'                  => get_post_meta( $aid, 'ws_agency_code',                    true ),
            'name'                  => get_post_meta( $aid, 'ws_agency_name',                    true ),
            'logo'                  => get_field( 'ws_agency_logo', $aid ),
            'disclosure_type'       => get_field( 'ws_agency_disclosure_type', $aid ),
            'process_type'          => ( ( $_pt = wp_get_object_terms( $aid, 'ws_process_type', [ 'fields' => 'slugs' ] ) ) && ! is_wp_error( $_pt ) && ! empty( $_pt ) ) ? $_pt[0] : '',
            'website_url'           => get_post_meta( $aid, 'ws_agency_url',                     true ),
            'reporting_url'         => get_post_meta( $aid, 'ws_agency_reporting_url',           true ),
            'phone'                 => get_post_meta( $aid, 'ws_agency_phone',                   true ),
            'confidentiality_notes' => get_post_meta( $aid, 'ws_agency_confidentiality_notes',   true ),
            'anonymous'             => (bool) get_post_meta( $aid, 'ws_agency_accepts_anonymous', true ),
            'reward'                => (bool) get_post_meta( $aid, 'ws_agency_reward_program',    true ),
            'languages'             => get_field( 'ws_languages', $aid ),
            'additional_languages'  => get_post_meta( $aid, 'ws_agency_additional_languages',    true ),
            'last_reviewed'         => get_post_meta( $aid, 'ws_agency_last_reviewed',           true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $aid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $aid ),
            // Record management
            'record' => ws_build_record_array( $aid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Assist Organizations
//
// Returns all published ws-assist-org records assigned to the given
// ws_jurisdiction taxonomy term, ordered alphabetically.
//
// For jurisdiction pages, pass the US term ID to surface nationwide orgs.
// Records without the term assigned are excluded.
// Returns an array of assist-org data arrays, or empty array if none found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_assist_org_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return [];
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-assist-org',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $org ) {
        $oid    = $org->ID;
        $rows[] = [
            'id'            => $oid,
            'title'         => get_the_title( $oid ),
            'url'           => get_permalink( $oid ),
            'status'        => get_post_status( $oid ),
            // Assist-org fields
            'internal_id'          => get_post_meta( $oid, 'ws_aorg_internal_id',               true ),
            'type'                 => ( ( $_aorg_type = get_the_terms( $oid, 'ws_aorg_type' ) ) && ! is_wp_error( $_aorg_type ) ) ? $_aorg_type[0] : null,
            'description'          => get_post_meta( $oid, 'ws_aorg_description',                true ),
            'logo'                 => get_field( 'ws_aorg_logo', $oid ),
            'serves_nationwide'    => (bool) get_post_meta( $oid, 'ws_aorg_serves_nationwide',   true ),
            'disclosure_type'      => get_field( 'ws_aorg_disclosure_type', $oid ),
            'services'             => wp_get_object_terms( $oid, 'ws_aorg_service', [ 'fields' => 'names' ] ),
            'additional_services'  => get_post_meta( $oid, 'ws_aorg_additional_services',        true ),
            'employment_sectors'   => wp_get_object_terms( $oid, 'ws_employment_sector', [ 'fields' => 'names' ] ),
            'website_url'          => get_post_meta( $oid, 'ws_aorg_website_url',                true ),
            'intake_url'           => get_post_meta( $oid, 'ws_aorg_intake_url',                 true ),
            'phone'                => get_post_meta( $oid, 'ws_aorg_phone',                      true ),
            'email'                => get_post_meta( $oid, 'ws_aorg_email',                      true ),
            'mailing_address'      => get_post_meta( $oid, 'ws_aorg_mailing_address',            true ),
            'languages'            => get_field( 'ws_languages', $oid ),
            'additional_languages' => get_post_meta( $oid, 'ws_aorg_additional_languages',       true ),
            'cost_model'           => wp_get_object_terms( $oid, 'ws_aorg_cost_model', [ 'fields' => 'names' ] ),
            'income_limit'         => get_post_meta( $oid, 'ws_aorg_income_limit',               true ),
            'income_limit_notes'   => get_post_meta( $oid, 'ws_aorg_income_limit_notes',         true ),
            'anonymous'            => (bool) get_post_meta( $oid, 'ws_aorg_accepts_anonymous',   true ),
            'eligibility_notes'    => get_post_meta( $oid, 'ws_aorg_eligibility_notes',          true ),
            'licensed_attorneys'   => (bool) get_post_meta( $oid, 'ws_aorg_licensed_attorneys',  true ),
            'accreditation'        => get_post_meta( $oid, 'ws_aorg_accreditation',              true ),
            'bar_states'           => get_post_meta( $oid, 'ws_aorg_bar_states',                 true ),
            'verify_url'           => get_post_meta( $oid, 'ws_aorg_verify_url',                 true ),
            'last_reviewed'        => get_post_meta( $oid, 'ws_aorg_last_reviewed',              true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $oid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $oid ),
            // Record management
            'record' => ws_build_record_array( $oid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Nationwide Assist Organizations (Directory)
//
// Returns all published ws-assist-org records where ws_aorg_serves_nationwide
// is true, ordered alphabetically. Accepts an optional $filters array to
// narrow results before returning.
//
// NATIONWIDE vs FEDERAL-SCOPE
// us jurisdiction tag = federal law scope (federal workers only).
// ws_aorg_serves_nationwide = 1 = org operates across all 57 jurisdictions.
// This function gates on serves_nationwide, not the us jurisdiction term.
// Federal-scope-only orgs (OSC, GAO FraudNet, etc.) have is_nationwide = 0
// and are intentionally excluded here; they surface on jurisdiction pages.
//
// Intended for the [ws_assist_org_directory] shortcode. For jurisdiction-page
// renders, use ws_get_assist_org_data() with the jurisdiction term ID instead.
//
// $filters keys (all optional):
//   'type'       — ws_aorg_type slug (e.g. 'nonprofit', 'legal-aid')
//   'sector'     — ws_employment_sector slug (e.g. 'federal-employee')
//   'stage'      — ws_case_stage slug (e.g. 'pre-report', 'retaliation-active')
//   'cost_model' — cost model slug (e.g. 'pro-bono', 'free', 'contingency')
//
// All filtering is performed at the query level via tax_query / meta_query.
// No post-query filtering is used.
//
// Returns an array of assist-org data arrays (identical shape to
// ws_get_assist_org_data()).
// ════════════════════════════════════════════════════════════════════════════

function ws_get_nationwide_assist_org_data( $filters = [] ) {

    $query_args = [
        'post_type'      => 'ws-assist-org',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        // Gate: only orgs that operate across all 57 jurisdictions.
        // Federal-scope-only orgs (is_nationwide = 0) are excluded.
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'ws_aorg_serves_nationwide',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];

    // Optional taxonomy filter: org type.
    if ( ! empty( $filters['type'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_aorg_type',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['type'] ),
        ];
    }

    // Optional taxonomy filter: employment sector.
    if ( ! empty( $filters['sector'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_employment_sector',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['sector'] ),
        ];
    }

    // Optional taxonomy filter: case stage.
    if ( ! empty( $filters['stage'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_case_stage',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['stage'] ),
        ];
    }

    // Optional taxonomy filter: cost model.
    if ( ! empty( $filters['cost_model'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_aorg_cost_model',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['cost_model'] ),
        ];
    }

    $q    = new WP_Query( $query_args );
    $rows = [];

    foreach ( $q->posts as $org ) {
        $oid    = $org->ID;
        $rows[] = [
            'id'     => $oid,
            'title'  => get_the_title( $oid ),
            'url'    => get_permalink( $oid ),
            'status' => get_post_status( $oid ),
            // Assist-org fields — identical shape to ws_get_assist_org_data().
            'internal_id'          => get_post_meta( $oid, 'ws_aorg_internal_id',               true ),
            'type'                 => ( ( $_t = get_the_terms( $oid, 'ws_aorg_type' ) ) && ! is_wp_error( $_t ) ) ? $_t[0] : null,
            'description'          => get_post_meta( $oid, 'ws_aorg_description',                true ),
            'logo'                 => get_field( 'ws_aorg_logo', $oid ),
            'serves_nationwide'    => (bool) get_post_meta( $oid, 'ws_aorg_serves_nationwide',   true ),
            'disclosure_type'      => get_field( 'ws_aorg_disclosure_type', $oid ),
            'services'             => wp_get_object_terms( $oid, 'ws_aorg_service', [ 'fields' => 'names' ] ),
            'additional_services'  => get_post_meta( $oid, 'ws_aorg_additional_services',        true ),
            'employment_sectors'   => wp_get_object_terms( $oid, 'ws_employment_sector', [ 'fields' => 'names' ] ),
            'website_url'          => get_post_meta( $oid, 'ws_aorg_website_url',                true ),
            'intake_url'           => get_post_meta( $oid, 'ws_aorg_intake_url',                 true ),
            'phone'                => get_post_meta( $oid, 'ws_aorg_phone',                      true ),
            'email'                => get_post_meta( $oid, 'ws_aorg_email',                      true ),
            'mailing_address'      => get_post_meta( $oid, 'ws_aorg_mailing_address',            true ),
            'languages'            => get_field( 'ws_languages', $oid ),
            'additional_languages' => get_post_meta( $oid, 'ws_aorg_additional_languages',       true ),
            'cost_model'           => wp_get_object_terms( $oid, 'ws_aorg_cost_model', [ 'fields' => 'names' ] ),
            'income_limit'         => get_post_meta( $oid, 'ws_aorg_income_limit',               true ),
            'income_limit_notes'   => get_post_meta( $oid, 'ws_aorg_income_limit_notes',         true ),
            'anonymous'            => (bool) get_post_meta( $oid, 'ws_aorg_accepts_anonymous',   true ),
            'eligibility_notes'    => get_post_meta( $oid, 'ws_aorg_eligibility_notes',          true ),
            'licensed_attorneys'   => (bool) get_post_meta( $oid, 'ws_aorg_licensed_attorneys',  true ),
            'accreditation'        => get_post_meta( $oid, 'ws_aorg_accreditation',              true ),
            'bar_states'           => get_post_meta( $oid, 'ws_aorg_bar_states',                 true ),
            'verify_url'           => get_post_meta( $oid, 'ws_aorg_verify_url',                 true ),
            'last_reviewed'        => get_post_meta( $oid, 'ws_aorg_last_reviewed',              true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $oid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $oid ),
            // Record management
            'record' => ws_build_record_array( $oid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Get All Jurisdictions
//
// Returns a list of all published jurisdiction post objects ordered
// alphabetically by title. Result is cached for 12 hours.
//
// Used for bulk operations and administrative views where full post objects
// are needed. For index display use ws_get_jurisdiction_index_data() which
// includes type counts and structured metadata.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_all_jurisdictions() {

    $cache_key     = WS_CACHE_ALL_JURISDICTIONS;
    $jurisdictions = get_transient( $cache_key );

    if ( false === $jurisdictions ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );

        $jurisdictions = $query->posts;

        // Cache for 12 hours — invalidated on jurisdiction save.
        set_transient( $cache_key, $jurisdictions, 12 * HOUR_IN_SECONDS );
    }

    return $jurisdictions;
}


// ════════════════════════════════════════════════════════════════════════════
// Get Jurisdiction Index Data
//
// Returns a structured array containing all jurisdictions as index items
// plus a count breakdown by type. Used to power the jurisdiction index
// shortcode and any type-filtered display views.
//
// SUMMARY GATE
// ------------
// A published jurisdiction post with no linked jx-summary is a stub —
// it has no useful content for end users. Only jurisdictions with a
// linked jx-summary are included in the index. Jurisdictions that have
// not yet been summarised are silently excluded.
//
// Return shape:
//      [
//          'items'  => [ [ 'name', 'code', 'type', 'url' ], ... ],
//          'counts' => [ 'all', 'state', 'territory', 'district', 'federal' ]
//      ]
//
// Result is cached for 24 hours — invalidated on jurisdiction/jx-summary
// saves and jx-summary deletes. The summary check per jurisdiction runs
// only at cache fill time.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_index_data() {

    $cache_key = WS_CACHE_JX_INDEX;
    $cached    = get_transient( $cache_key );

    if ( false === $cached ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );

        $index_items = [];
        $counts      = [
            'all'       => 0,
            'state'     => 0,
            'territory' => 0,
            'district'  => 0,
            'federal'   => 0,
        ];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {

                $jx_terms = wp_get_post_terms( $post->ID, WS_JURISDICTION_TAXONOMY );

                if ( is_wp_error( $jx_terms ) || empty( $jx_terms ) ) {
                    continue;
                }

                $jx_term = $jx_terms[0];

                // Gate: exclude stubs — jurisdiction must have a linked jx-summary.
                if ( ! ws_get_jx_summary_data( $jx_term->term_id ) ) {
                    continue;
                }

                $type = get_field( 'ws_jurisdiction_class', $post->ID ) ?: 'state';
                $code = strtoupper( $jx_term->slug );

                $index_items[] = [
                    'name' => get_the_title( $post->ID ),
                    'code' => $code,
                    'type' => $type,
                    'url'  => get_permalink( $post->ID ),
                ];

                $counts['all']++;
                if ( isset( $counts[ $type ] ) ) {
                    $counts[ $type ]++;
                }
            }
        }

        $cached = [
            'items'  => $index_items,
            'counts' => $counts,
        ];

        // Cache for 24 hours — invalidated on jurisdiction save.
        set_transient( $cache_key, $cached, DAY_IN_SECONDS );
    }

    return $cached;
}


// ════════════════════════════════════════════════════════════════════════════
// ws_get_legal_updates_data( $jx_id );  // jurisdiction summary — auto summary types
// ws_get_legal_updates_data();          // sitewide changelog — auto all types
//
// Returns structured legal update data for the render layer.
//
// @param int  $jx_id       Jurisdiction post ID to scope results. 0 = site-wide.
// @param int  $count       Number of updates to collect, defaults to 100 if !$jx_id,
//                          defaults to 5 if $jx_id
// @return array            Array of data items ready for the render layer.
//
// CACHING
// Sitewide calls ($jx_id = 0) with $count ≤ 100 are served from a single
// 100-item transient (WS_CACHE_LEGAL_UPDATES_SITEWIDE). The result is sliced
// to $count before returning — no per-count keys needed, single delete on save.
// Calls with $count > 100 bypass the cache and query directly.
// Per-jurisdiction calls are never cached.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_legal_updates_data( $jx_id = 0, $count = 0 ) {

	if ( !$count ) {
		$count = $jx_id ? 5 : 100;
	}

    // ── Sitewide cache ────────────────────────────────────────────────────
    // One 100-item transient covers all sitewide requests ≤ 100 — slice on
    // the way out. Requests > 100 skip the cache and query at full count.
    if ( ! $jx_id && $count <= 100 ) {
        $cached = get_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE );
        if ( false !== $cached ) {
            return array_slice( $cached, 0, $count );
        }
    }

    // Always fetch 100 for sitewide cacheable calls so the stored set covers
    // any subsequent request ≤ 100. Fetch exact $count otherwise.
    $fetch_count = ( ! $jx_id && $count <= 100 ) ? 100 : $count;

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => $fetch_count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        // Hidden updates remain in admin but are excluded from public render.
        'meta_query'     => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key'     => 'ws_legal_update_hide_public',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'ws_legal_update_hide_public',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ],
    ];

    if ( $jx_id ) {
        $term_id = ws_get_jx_term_id( $jx_id );
        if ( $term_id ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ];
        }
        // Jurisdiction-scoped calls restrict to summary-safe update types only.
        $query_args['meta_query'][] = [
            'key'     => 'ws_legal_update_type',
            'value'   => WS_LEGAL_UPDATE_SUMMARY_TYPES,
            'compare' => 'IN',
        ];
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return [];
    }

    $items = [];
    foreach ( $updates as $update ) {
        $uid     = $update->ID;
        $items[] = [
            // ── Identity ─────────────────────────────────────────────────
            'id'                 => $uid,
            'title'              => get_the_title( $uid ),

            // ── Dates ────────────────────────────────────────────────────
            'update_date'        => get_post_meta( $uid, 'ws_legal_update_date',             true ),
            'effective_date'     => get_post_meta( $uid, 'ws_legal_update_effective_date',   true ),
            'post_date'          => get_post_field( 'post_date', $uid ),

            // ── Classification ───────────────────────────────────────────
            'type'        => get_post_meta( $uid, 'ws_legal_update_type',                      true ),
            'multi_jurisdiction' => (bool) get_post_meta( $uid, 'ws_legal_update_multi_jurisdiction', true ),

            // ── Content ──────────────────────────────────────────────────
            'law_name'           => get_post_meta( $uid, 'ws_legal_update_law_name',         true ) ?: '',
            'source_url'         => get_post_meta( $uid, 'ws_legal_update_source_url',       true ) ?: '',
            'summary'            => wp_kses_post( get_post_meta( $uid, 'ws_legal_update_summary_wysiwyg', true ) ?: '' ),

            // ── Source post backlink ──────────────────────────────────────
            'source_post_id'     => (int) get_post_meta( $uid, 'ws_legal_update_source_post_id',   true ),
            'source_post_type'   => get_post_meta(       $uid, 'ws_legal_update_source_post_type', true ),

            // ── Source & verification ────────────────────────────────────
            'verify'             => ws_build_source_verify_array( $uid ),

            // ── Record management ────────────────────────────────────────
            'record'             => ws_build_record_array( $uid ),
        ];
    }

    // Cache the full 100-item set for sitewide cacheable calls.
    if ( ! $jx_id && $count <= 100 ) {
        set_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE, $items, HOUR_IN_SECONDS );
        return array_slice( $items, 0, $count );
    }

    return $items;
}

// ════════════════════════════════════════════════════════════════════════════
// Reference Materials
//
// ws_get_ref_materials( $post_id )
//     Returns an array of ws-reference items linked to a parent post via
//     the ws_ref_materials ACF relationship field. All linked references
//     are returned — the approval gate (ws_ref_approved) was retired in
//     v3.4.0; editors are trusted users and the parent record's review
//     workflow is the quality gate.
//     Returns [] if no references are linked.
//
// ws_get_reference_page_data( $parent_post_id )
//     Builds the full data payload for the [ws_reference_page] shortcode.
//     Returns null if the post type is not jx-statute, jx-citation, or
//     jx-interpretation. Returns an array with parent_title, parent_url,
//     and references keys otherwise.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_ref_materials( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) return [];

    $refs = get_field( 'ws_ref_materials', $post_id );
    if ( ! is_array( $refs ) || empty( $refs ) ) return [];

    $items = [];
    foreach ( $refs as $ref ) {
        if ( ! ( $ref instanceof WP_Post ) ) continue;
        $rid = $ref->ID;

        $title = get_post_meta( $rid, 'ws_ref_title', true );
        if ( empty( $title ) ) {
            $title = get_the_title( $rid );
        }

        $items[] = [
            'title'       => sanitize_text_field( $title ),
            'url'         => esc_url_raw( get_post_meta( $rid, 'ws_ref_url', true ) ),
            'description' => sanitize_textarea_field( get_post_meta( $rid, 'ws_ref_description', true ) ),
            'type'        => sanitize_text_field( get_post_meta( $rid, 'ws_ref_type', true ) ),
            'source_name' => sanitize_text_field( get_post_meta( $rid, 'ws_ref_source_name', true ) ),
        ];
    }

    return $items;
}

function ws_get_reference_page_data( $parent_post_id ) {
    $parent_post_id = (int) $parent_post_id;
    if ( ! $parent_post_id ) return null;

    $allowed_types = WS_REF_PARENT_TYPES;
    if ( ! in_array( get_post_type( $parent_post_id ), $allowed_types, true ) ) return null;

    return [
        'parent_title' => get_the_title( $parent_post_id ),
        'parent_url'   => get_permalink( $parent_post_id ),
        'references'   => ws_get_ref_materials( $parent_post_id ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Cache Invalidation + ws_jx_term_id Write
//
// Clears jurisdiction/index transients when jurisdiction or jx-summary
// content changes so summary-gated index rows stay accurate.
//
// Also handles per-term ID cache clear + ws_jx_term_id write on
// save_post_jurisdiction.
//
// Also writes ws_jx_term_id post meta, providing a direct post->term_id
// mapping for seeders and admin tooling without a get_term_by() call at
// runtime.
// ════════════════════════════════════════════════════════════════════════════

// Invalidate the sitewide legal updates cache whenever any legal update post
// is saved. Single key — all count variants are served from this one transient.
add_action( 'save_post_ws-legal-update', function() {
    delete_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE );
} );
add_action( 'before_delete_post', function( $post_id ) {
    if ( get_post_type( $post_id ) === 'ws-legal-update' ) {
        delete_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE );
    }
} );

function ws_invalidate_jurisdiction_list_and_index_caches() {
    delete_transient( WS_CACHE_ALL_JURISDICTIONS );
    delete_transient( WS_CACHE_JX_INDEX );
}


add_action( 'save_post_jurisdiction', function( $post_id ) {

    // Clear list and index caches.
    ws_invalidate_jurisdiction_list_and_index_caches();

    // Resolve the assigned ws_jurisdiction term once for both operations.
    $terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        // Clear the per-term ID cache so ws_get_id_by_code() reflects any
        // taxonomy reassignment immediately.
        delete_transient( 'ws_id_for_term_' . $terms[0]->term_id );

        // Write the direct post->term_id mapping.
        update_post_meta( $post_id, 'ws_jx_term_id', $terms[0]->term_id );
    }

} );

// Index membership is gated by linked jx-summary existence/publication.
// Invalidate jurisdiction list + index whenever a summary is changed or removed.
add_action( 'save_post_jx-summary', 'ws_invalidate_jurisdiction_list_and_index_caches' );
add_action( 'before_delete_post', function( $post_id ) {
    if ( get_post_type( $post_id ) === 'jx-summary' ) {
        ws_invalidate_jurisdiction_list_and_index_caches();
    }
} );


// ════════════════════════════════════════════════════════════════════════════
// Common Law Protection Data
//
// Returns all attached jx-common-law records for a jurisdiction, appending
// federal common law doctrine records the same way ws_get_jx_statute_data()
// appends federal statutes.
//
// @param int $jx_term_id  The ws_jurisdiction term ID for the jurisdiction.
// @return array           Flat array of common law doctrine row arrays.
//                         Empty array if no records exist.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_common_law_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-common-law',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $record ) {
            $rid    = $record->ID;
            $rows[] = [
                'id'      => $rid,
                'title'   => get_the_title( $rid ),
                'url'     => get_permalink( $rid ),
                'status'  => get_post_status( $rid ),
                'content' => get_post_field( 'post_content', $rid ),
                'order'   => (int) get_post_meta( $rid, 'ws_display_order', true ),
                'is_fed'  => $is_fed,

                // ── Legal Basis ───────────────────────────────────────────
                'doctrine_name'          => get_post_meta( $rid, 'ws_cl_doctrine_name',          true ),
                'doctrine_id'            => get_post_meta( $rid, 'ws_cl_doctrine_id',             true ),
                'common_name'            => get_post_meta( $rid, 'ws_cl_common_name',             true ),
                'precedent_url'          => get_post_meta( $rid, 'ws_cl_precedent_url',           true ),
                'public_policy_sources'  => get_post_meta( $rid, 'ws_cl_public_policy_sources',  true ),
                'other_sources'          => get_post_meta( $rid, 'ws_cl_other_sources',           true ),
                'doctrine_basis'         => get_post_meta( $rid, 'ws_cl_doctrine_basis',          true ),
                'recognition_status'     => get_post_meta( $rid, 'ws_cl_recognition_status',      true ),
                'disclosure_type'      => get_field( 'ws_cl_disclosure_type',      $rid ),
                'protected_class'      => get_field( 'ws_cl_protected_class',      $rid ),
                'disclosure_targets'   => get_field( 'ws_cl_disclosure_targets',   $rid ),
                'adverse_action_scope' => get_post_meta( $rid, 'ws_cl_adverse_action_scope',  true ),
                'attach_flag'          => (bool) get_post_meta( $rid, 'ws_attach_flag',        true ),

                // ── Statute of Limitations ────────────────────────────────
                'sol_value'           => get_post_meta( $rid, 'ws_cl_sol_value',           true ),
                'sol_unit'            => get_post_meta( $rid, 'ws_cl_sol_unit',            true ),
                'sol_trigger'         => get_post_meta( $rid, 'ws_cl_sol_trigger',         true ),
                'sol_has_details'     => (bool) get_post_meta( $rid, 'ws_cl_limit_ambiguous',     true ),
                'sol_details'         => get_post_meta( $rid, 'ws_cl_limit_details',         true ),
                'tolling_has_details' => (bool) get_post_meta( $rid, 'ws_cl_tolling_has_notes', true ),
                'tolling_details'     => get_post_meta( $rid, 'ws_cl_tolling_notes',     true ),
                'has_exhaustion'      => (bool) get_post_meta( $rid, 'ws_cl_exhaustion_required',      true ),
                'exhaustion_details'  => get_post_meta( $rid, 'ws_cl_exhaustion_details',  true ),

                // ── Enforcement ───────────────────────────────────────────
                'process_type'     => get_field( 'ws_cl_process_type',     $rid ),
                'adverse_action'   => get_field( 'ws_cl_adverse_action',   $rid ),
                'fee_shifting'     => get_field( 'ws_cl_fee_shifting',     $rid ),
                'remedies'         => get_field( 'ws_cl_remedies',         $rid ),
                'related_agencies' => get_field( 'ws_cl_related_agencies', $rid ),

                // ── Burden of Proof ───────────────────────────────────────
                'statutory_preclusion'         => (bool) get_post_meta( $rid, 'ws_cl_statutory_preclusion',         true ),
                'statutory_preclusion_details' => get_post_meta( $rid, 'ws_cl_statutory_preclusion_details', true ),
                'employee_standard'        => get_field( 'ws_cl_employee_standard',        $rid ),
                'employer_defense'         => get_field( 'ws_cl_employer_defense',         $rid ),
                'employer_defense_details' => get_post_meta( $rid, 'ws_cl_employer_defense_details', true ),
                'rebuttable_has_details'   => (bool) get_post_meta( $rid, 'ws_cl_rebuttable_has_presumption', true ),
                'rebuttable_details'       => get_post_meta( $rid, 'ws_cl_rebuttable_presumption',       true ),
                'bop_has_details'          => (bool) get_post_meta( $rid, 'ws_cl_bop_has_details',   true ),
                'bop_details'              => get_post_meta( $rid, 'ws_cl_burden_of_proof_details',              true ),

                // ── Reward ────────────────────────────────────────────────
                'has_reward'     => (bool) get_post_meta( $rid, 'ws_cl_reward_available',     true ),
                'reward_details' => get_post_meta( $rid, 'ws_cl_reward_details', true ),

                // Record management
                'plain'  => ws_build_plain_english_array( $rid ),
                'verify' => ws_build_source_verify_array( $rid ),
                'record' => ws_build_record_array( $rid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}
