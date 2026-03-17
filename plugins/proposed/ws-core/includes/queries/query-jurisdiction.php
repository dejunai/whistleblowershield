<?php
/**
 * query-jurisdiction.php
 *
 * Jurisdiction Query Layer
 *
 * PURPOSE
 * -------
 * Provides centralized functions for retrieving jurisdiction
 * records and their associated datasets.
 *
 * This file acts as the primary data access layer for the
 * WhistleblowerShield plugin. By consolidating queries here
 * we avoid repeating WP_Query logic throughout the plugin
 * and maintain consistent behavior across shortcodes and
 * templates.
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
 * ws_jx_code meta has been retired. All lookups use taxonomy queries.
 *
 * ws_jx_term_id post meta is written on each jurisdiction post (by the seeder
 * and the save_post_jurisdiction hook) as a convenience for direct term→post
 * lookups without a get_term_by() call.
 *
 * CACHING
 * -------
 * Transient caching is used for expensive or repeated queries.
 * All jurisdiction transients are invalidated on save_post
 * for the jurisdiction CPT.
 *
 * Transient keys:
 *      ws_id_for_term_{term_id}    — post ID lookup by taxonomy term ID
 *      ws_all_jurisdictions_cache  — full post object list
 *      ws_jx_index_cache           — index data with counts
 *
 * DATASET RETURN FORMAT
 * ---------------------
 * All dataset functions return a consistent base array:
 *
 *      [
 *          'id'      => int,
 *          'title'   => string,
 *          'url'     => string,
 *          'status'  => string,
 *          'content' => string,  // raw post_content — apply the_content in render layer
 *      ]
 *
 * ws_get_jx_statute_data() returns an array-of-arrays using the same shape,
 * plus an 'is_fed' boolean key. May contain two groups (state + federal).
 *
 * NOTE: Audit trail data (_ws_last_edited_by, _ws_edit_history) is stored
 * in wp_postmeta as private hidden keys and is NOT retrieved through this
 * query layer. Use ws_get_last_editor( $post_id ) and
 * ws_get_edit_history( $post_id ) defined in admin-audit-trail.php instead.
 *
 * @todo - Expand each dataset array as those CPTs are defined.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. Removed ws_get_jurisdiction_by_code
 *         in favor of ws_get_id_by_code. Updated field names to match
 *         acf-jurisdiction.php v2.1.0. Added record management fields to
 *         ws_get_jurisdiction_data. Updated dataset stubs to return consistent
 *         base arrays and accept ws_jx_code as input. Consolidated cache
 *         invalidation on save_post.
 * 2.1.1   ws_get_jx_statutes() now returns with Federal Statutes append to
 *         Statutes of the specified Jurisdiction when !US is called.
 * 2.3.1   All content keys normalized to raw get_post_field('post_content').
 *         Render layer applies the_content filters. ws_get_jx_statutes()
 *         returns array-of-arrays; shape documented above.
 * 3.0.0   Architecture refactor (Phase 3.1+3.2):
 *         ws_jx_code meta retired as join mechanism. All jurisdiction lookups
 *         now use the ws_jurisdiction taxonomy. ws_get_id_by_code() migrated
 *         to taxonomy query; transient cache rekeyed to ws_id_for_term_{term_id}.
 *         ws_get_jurisdiction_data() and ws_get_jurisdiction_index_data() read
 *         jurisdiction code from taxonomy term slug. save_post_jurisdiction hook
 *         updated to clear per-term transient.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction ID Lookup
//
// Retrieves the post ID for a jurisdiction by its two-letter USPS code.
// Uses the ws_jurisdiction taxonomy term (slug = lowercase USPS code) to
// locate the jurisdiction post via tax_query. Retired ws_jx_code meta query.
//
// Result is cached in a transient keyed by taxonomy term ID for 24 hours.
// Returns false if the term or jurisdiction post cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_id_by_code( $jx_code ) {

    if ( empty( $jx_code ) ) {
        return false;
    }

    $slug = strtolower( sanitize_text_field( $jx_code ) );
    $term = get_term_by( 'slug', $slug, 'ws_jurisdiction' );

    if ( ! $term || is_wp_error( $term ) ) {
        return false;
    }

    $term_id   = $term->term_id;
    $cache_key = 'ws_id_for_term_' . $term_id;
    $post_id   = get_transient( $cache_key );

    if ( false === $post_id ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy' => 'ws_jurisdiction',
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
// Resolves a mixed $input (numeric post ID or two-letter ws_jx_code string)
// to a jurisdiction post ID integer. Used by all dataset retrieval functions
// to eliminate the repeated is_numeric ternary.
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
// Accepts either a numeric post ID or a two-letter ws_jx_code string.
// Falls back to the global $post if no input is provided.
//
// Returns a structured array of all jurisdiction metadata, or false
// if the post cannot be resolved or is not a jurisdiction CPT record.
//
// Flag data is retrieved as an array from ACF (return_format: array)
// and destructured here for consistent downstream access.
//
// Record management fields (author, dates, last editor) are included
// for audit and display purposes.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_data( $input = null ) {

    // Resolve post ID from input, current post, or ws_jx_code
    if ( ! $input ) {
        global $post;
        $post_id = $post->ID ?? 0;
    } else {
        $post_id = ws_resolve_jx_id( $input );
    }

    // Bail if post ID is invalid or not a jurisdiction record
    if ( ! $post_id || get_post_type( $post_id ) !== 'jurisdiction' ) {
        return false;
    }

    // Retrieve flag as array — field return_format is set to 'array' in ACF
    $flag = get_field( 'ws_jx_flag', $post_id );

    // Derive jurisdiction code from the assigned ws_jurisdiction taxonomy term slug
    $jx_terms = wp_get_post_terms( $post_id, 'ws_jurisdiction', [ 'fields' => 'slugs' ] );
    $jx_code  = ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) ? strtoupper( $jx_terms[0] ) : '';

    return [

        // ── Identity ─────────────────────────────────────────────────────────
        'id'   => $post_id,
        'name' => get_the_title( $post_id ),
        'type' => get_field( 'ws_jurisdiction_type', $post_id ),
        'code' => $jx_code,

        // ── Flag ─────────────────────────────────────────────────────────────
        'flag' => [
            'url'             => ( is_array( $flag ) && ! empty( $flag['url'] ) ) ? $flag['url'] : '',
            'attribution'     => get_field( 'ws_jx_flag_attribution', $post_id ),
            'attribution_url' => get_field( 'ws_jx_flag_attribution_url', $post_id ),
            'license'         => get_field( 'ws_jx_flag_license', $post_id ),
        ],

        // ── Government Links ──────────────────────────────────────────────────
        'gov' => [
            'portal_url'        => get_field( 'ws_gov_portal_url', $post_id ),
            'portal_label'      => get_field( 'ws_gov_portal_label', $post_id ),
            'head_gov_url'      => get_field( 'ws_head_of_government_url', $post_id ),
            'head_gov_label'    => get_field( 'ws_head_of_government_label', $post_id ),
            'legal_auth_url'    => get_field( 'ws_legal_authority_url', $post_id ),
            'legal_auth_label'  => get_field( 'ws_legal_authority_label', $post_id ),
            'legislature_url'   => get_field( 'ws_legislature_url', $post_id ),
            'legislature_label' => get_field( 'ws_legislature_label', $post_id ),
        ],

        // ── Record Management ─────────────────────────────────────────────────
        // These fields are read-only in the UI and managed by save_post hooks.
        // ws_jx_author and ws_jx_last_editor store WordPress user IDs.
        'record' => [
            'author'           => get_field( 'ws_jx_author', $post_id ),
            'last_editor'      => get_field( 'ws_jx_last_editor', $post_id ),
            'date_created'     => get_field( 'ws_jx_date_created', $post_id ),
            'date_created_gmt' => get_field( 'ws_jx_date_created_gmt', $post_id ),
            'date_updated'     => get_field( 'ws_jx_date_updated', $post_id ),
            'date_updated_gmt' => get_field( 'ws_jx_date_updated_gmt', $post_id ),
        ],

    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Summary
//
// Retrieves the jx-summary post assigned to the given ws_jurisdiction term
// and returns a fully-hydrated data array.
//
// Phase 9.1 refactor:
//   - Renamed ws_get_jx_summary($input) → ws_get_jx_summary_data($jx_term_id).
//   - Accepts taxonomy term ID directly (same pattern as statute/citation/interp).
//   - Replaced get_field('ws_related_summary') relationship lookup with
//     get_posts() taxonomy query (ws_related_summary was removed in Phase 3.6).
//   - Returns all content and review fields needed by the shortcode layer so
//     shortcodes make zero direct get_field() / get_post_meta() calls.
//
// Returns false if no published jx-summary is found for the term.
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
            'taxonomy' => 'ws_jurisdiction',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    if ( empty( $ids ) ) {
        return false;
    }

    $sid = (int) $ids[0];

    // Author lookup: read the ws_jx_sum_create_author stamp written on first save.
    $create_author_id = (int) get_post_meta( $sid, 'ws_jx_sum_create_author', true );
    $author_name      = '';
    if ( $create_author_id ) {
        $user        = get_userdata( $create_author_id );
        $author_name = $user ? $user->display_name : '';
    }

    $date_created  = get_post_meta( $sid, 'ws_jx_sum_date_created',  true );
    $last_reviewed = get_post_meta( $sid, 'ws_jx_sum_last_reviewed', true );

    return [
        'id'             => $sid,
        'title'          => get_the_title( $sid ),
        'url'            => get_permalink( $sid ),
        'status'         => get_post_status( $sid ),
        // Content fields (stored in ACF postmeta)
        'content'        => get_post_meta( $sid, 'ws_jurisdiction_summary', true ),
        'sources'        => get_post_meta( $sid, 'ws_jx_summary_sources', true ),
        'limitations'    => get_post_meta( $sid, 'ws_jx_limitations', true ),
        // Authorship & dates
        'author_name'    => $author_name,
        'date_created'   => $date_created,
        'last_reviewed'  => $last_reviewed,
        'fmt_created'    => $date_created  ? date( 'F j, Y', strtotime( $date_created ) )  : '',
        'fmt_reviewed'   => $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '',
        // Plain language review fields (Phase 9.1)
        'plain_reviewed'   => (bool) get_post_meta( $sid, 'plain_reviewed', true ),
        'summarized_by'    => (int)  get_post_meta( $sid, 'ws_jx_sum_summarized_by', true ),
        'summarized_date'  => get_post_meta( $sid, 'ws_jx_sum_summarized_date', true ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Procedures
//
// Retrieves the related s post for the given jurisdiction.
// Accepts a numeric post ID or a two-letter ws_jx_code string.
// Returns a base array of post data, or false if not found.
//
// @todo - Update array as s CPT fields are defined.
// ════════════════════════════════════════════════════════════════════════════



// ════════════════════════════════════════════════════════════════════════════
// Dataset: Statutes
//
// Returns all published jx-statute records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true,
// sorted by order ASC.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of statute data arrays, or empty array if none found.
//
// Plain language fields are stubbed as false/empty; they will be
// extended in Phase 9 once ACF fields are registered.
//
// Federal append logic (is_fed flag) is implemented in Phase 3.5.2.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_statute_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    // Helper to query statutes for a given term and is_fed value.
    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-statute',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => 'ws_jurisdiction',
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
                'order'   => (int) get_post_meta( $sid, 'order', true ),
                'is_fed'  => $is_fed,
                // Statute-specific structured fields
                'official_name'       => get_post_meta( $sid, 'ws_jx_statute_official_name', true ),
                'limit_value'         => get_post_meta( $sid, 'ws_jx_statute_limit_value', true ),
                'limit_unit'          => get_post_meta( $sid, 'ws_jx_statute_limit_unit', true ),
                'trigger'             => get_post_meta( $sid, 'ws_jx_statute_trigger', true ),
                'tolling_notes'       => get_post_meta( $sid, 'ws_jx_statute_tolling_notes', true ),
                'exhaustion_required' => (bool) get_post_meta( $sid, 'ws_jx_statute_exhaustion_required', true ),
                'exhaustion_details'  => get_post_meta( $sid, 'ws_jx_statute_exhaustion_details', true ),
                'burden_of_proof'     => get_post_meta( $sid, 'ws_statute_burden_of_proof', true ),
                // Plain language fields (Phase 9.2)
                'has_plain_english' => (bool) get_post_meta( $sid, 'has_plain_english', true ),
                'plain_english'     => get_post_meta( $sid, 'plain_english',     true ),
                'plain_reviewed'    => (bool) get_post_meta( $sid, 'plain_reviewed',    true ),
                'summarized_by'     => (int)  get_post_meta( $sid, 'summarized_by',     true ),
                'summarized_date'   => get_post_meta( $sid, 'summarized_date',   true ),
            ];
        }
        return $rows;
    };

    // State/territory records — always fetch.
    $results = $fetch( $term_id, false );

    // Federal append: if this is not the US jurisdiction, also fetch US-scoped records.
    if ( $us_term_id && $term_id !== $us_term_id ) {
        $fed = $fetch( $us_term_id, true );
        $results = array_merge( $results, $fed );
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
    $terms = wp_get_post_terms( $post_id, 'ws_jurisdiction' );
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
// Reads the ws_us_term_id option written during taxonomy seeding in
// Phase 6.1. Falls back to a get_term_by() lookup if the option is not
// yet set (pre-seed or test environments).
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

    // Fallback: resolve by slug (pre-seed environments).
    $term = get_term_by( 'slug', 'us', 'ws_jurisdiction' );
    $us_term_id = ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
    return $us_term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Citations
//
// Returns all published jx-citation records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true,
// sorted by order ASC.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of citation arrays, or empty array if none found.
//
// Plain language fields are stubbed as false/empty; they will be
// extended in Phase 9 once ACF fields are registered.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_citation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    // Helper to query citations for a given term and is_fed value.
    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-citation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => 'ws_jurisdiction',
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
                'type'     => get_post_meta( $cid, 'ws_jx_cite_type',   true ),
                'label'    => get_post_meta( $cid, 'ws_jx_cite_label',  true ),
                'cite_url' => get_post_meta( $cid, 'ws_jx_cite_url',    true ),
                'is_pdf'   => (bool) get_post_meta( $cid, 'ws_jx_cite_is_pdf', true ),
                'order'    => (int)  get_post_meta( $cid, 'order',             true ),
                // Plain language fields (Phase 9.2)
                'has_plain_english' => (bool) get_post_meta( $cid, 'has_plain_english', true ),
                'plain_english'     => get_post_meta( $cid, 'plain_english',     true ),
                'plain_reviewed'    => (bool) get_post_meta( $cid, 'plain_reviewed',    true ),
                'summarized_by'     => (int)  get_post_meta( $cid, 'summarized_by',     true ),
                'summarized_date'   => get_post_meta( $cid, 'summarized_date',   true ),
            ];
        }
        return $rows;
    };

    // State/territory citations — always fetch.
    $results = $fetch( $term_id, false );

    // Federal append: if this is not the US jurisdiction, also fetch US-scoped citations.
    if ( $us_term_id && $term_id !== $us_term_id ) {
        $fed = $fetch( $us_term_id, true );
        $results = array_merge( $results, $fed );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Interpretations
//
// Returns all published jx-interpretation records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true,
// sorted by order ASC.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of interpretation data arrays, or empty array if none found.
//
// Plain language fields are stubbed as false/empty; they will be
// extended in Phase 9 once ACF fields are registered.
//
// Federal append logic (is_fed flag) is implemented in Phase 3.5.2.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_interpretation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    // Helper to query interpretations for a given term and is_fed value.
    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-interpretation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => 'ws_jurisdiction',
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
                'order'   => (int) get_post_meta( $iid, 'order', true ),
                'is_fed'  => $is_fed,
                // Interpretation-specific fields
                'case_name'   => get_post_meta( $iid, 'ws_interp_case_name', true ),
                'citation'    => get_post_meta( $iid, 'ws_interp_citation',  true ),
                'opinion_url' => get_post_meta( $iid, 'ws_interp_url',       true ),
                'court'       => get_post_meta( $iid, 'ws_interp_court',     true ),
                'year'        => get_post_meta( $iid, 'ws_interp_year',      true ),
                'favorable'   => (bool) get_post_meta( $iid, 'ws_interp_favorable', true ),
                'summary'     => get_post_meta( $iid, 'ws_interp_summary',   true ),
                'statute_id'  => (int) get_post_meta( $iid, 'ws_statute_id', true ),
                // Plain language fields (Phase 9.2)
                'has_plain_english' => (bool) get_post_meta( $iid, 'has_plain_english', true ),
                'plain_english'     => get_post_meta( $iid, 'plain_english',     true ),
                'plain_reviewed'    => (bool) get_post_meta( $iid, 'plain_reviewed',    true ),
                'summarized_by'     => (int)  get_post_meta( $iid, 'summarized_by',     true ),
                'summarized_date'   => get_post_meta( $iid, 'summarized_date',   true ),
            ];
        }
        return $rows;
    };

    // Local (US-only) records — always fetch.
    // Note: interpretations are always US-scoped (federal court decisions).
    // If term is not US, local fetch returns empty; federal append adds US records.
    $results = $fetch( $term_id, false );

    // Federal append: if this is not the US jurisdiction, also fetch US-scoped records.
    if ( $us_term_id && $term_id !== $us_term_id ) {
        $fed = $fetch( $us_term_id, true );
        $results = array_merge( $results, $fed );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Agencies (Phase 9.2)
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
            'taxonomy' => 'ws_jurisdiction',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $agency ) {
        $aid    = $agency->ID;
        $rows[] = [
            'id'             => $aid,
            'title'          => get_the_title( $aid ),
            'url'            => get_permalink( $aid ),
            'status'         => get_post_status( $aid ),
            // Agency fields
            'agency_code'    => get_post_meta( $aid, 'ws_agency_code',           true ),
            'agency_name'    => get_post_meta( $aid, 'ws_agency_name',           true ),
            'agency_url'     => get_post_meta( $aid, 'ws_agency_url',            true ),
            'reporting_url'  => get_post_meta( $aid, 'ws_agency_reporting_url',  true ),
            'phone'          => get_post_meta( $aid, 'ws_agency_phone',          true ),
            'anonymous'      => (bool) get_post_meta( $aid, 'ws_agency_anonymous_allowed', true ),
            'reward'         => (bool) get_post_meta( $aid, 'ws_agency_reward_program',    true ),
            // Plain language fields (Phase 9.2)
            'has_plain_english' => (bool) get_post_meta( $aid, 'has_plain_english', true ),
            'plain_english'     => get_post_meta( $aid, 'plain_english',     true ),
            'plain_reviewed'    => (bool) get_post_meta( $aid, 'plain_reviewed',    true ),
            'summarized_by'     => (int)  get_post_meta( $aid, 'summarized_by',     true ),
            'summarized_date'   => get_post_meta( $aid, 'summarized_date',   true ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Assist Organizations (Phase 9.2)
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
            'taxonomy' => 'ws_jurisdiction',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $org ) {
        $oid    = $org->ID;
        $rows[] = [
            'id'             => $oid,
            'title'          => get_the_title( $oid ),
            'url'            => get_permalink( $oid ),
            'status'         => get_post_status( $oid ),
            // Assist-org fields
            'ao_name'        => get_post_meta( $oid, 'ws_ao_name',         true ),
            'ao_url'         => get_post_meta( $oid, 'ws_ao_website_url',  true ),
            'ao_intake_url'  => get_post_meta( $oid, 'ws_ao_intake_url',   true ),
            'ao_phone'       => get_post_meta( $oid, 'ws_ao_phone',        true ),
            'ao_mission'     => get_post_meta( $oid, 'ws_ao_mission',      true ),
            'ao_provides'    => get_post_meta( $oid, 'ws_ao_provides',     true ),
            'ao_cost_model'  => get_post_meta( $oid, 'ws_ao_cost_model',   true ),
            'ao_anonymous'   => (bool) get_post_meta( $oid, 'ws_ao_accepts_anonymous', true ),
            // Plain language fields (Phase 9.2)
            'has_plain_english' => (bool) get_post_meta( $oid, 'has_plain_english', true ),
            'plain_english'     => get_post_meta( $oid, 'plain_english',     true ),
            'plain_reviewed'    => (bool) get_post_meta( $oid, 'plain_reviewed',    true ),
            'summarized_by'     => (int)  get_post_meta( $oid, 'summarized_by',     true ),
            'summarized_date'   => get_post_meta( $oid, 'summarized_date',   true ),
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
// Used for bulk operations and administrative views where full post
// objects are needed. For index display use ws_get_jurisdiction_index_data()
// which includes type counts and structured metadata.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_all_jurisdictions() {

    $cache_key     = 'ws_all_jurisdictions_cache';
    $jurisdictions = get_transient( $cache_key );

    if ( false === $jurisdictions ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true, // Skip total row count for performance
        ] );

        $jurisdictions = $query->posts;

        // Cache for 12 hours — invalidated on jurisdiction save
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
// Return shape:
//      [
//          'items'  => [ [ 'name', 'code', 'type', 'url' ], ... ],
//          'counts' => [ 'all', 'state', 'territory', 'district', 'federal' ]
//      ]
//
// Result is cached for 24 hours — invalidated on jurisdiction save.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_index_data() {

    $cache_key = 'ws_jx_index_cache';
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

                $type     = get_field( 'ws_jurisdiction_type', $post->ID ) ?: 'state';
                $jx_slugs = wp_get_post_terms( $post->ID, 'ws_jurisdiction', [ 'fields' => 'slugs' ] );
                $code     = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? strtoupper( $jx_slugs[0] ) : '';

                $index_items[] = [
                    'name' => get_the_title( $post->ID ),
                    'code' => $code,
                    'type' => $type,
                    'url'  => get_permalink( $post->ID ),
                ];

                // Increment type counts
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

        // Cache for 24 hours — invalidated on jurisdiction save
        set_transient( $cache_key, $cached, DAY_IN_SECONDS );
    }

    return $cached;
}


// ════════════════════════════════════════════════════════════════════════════
// Legal Updates Query
//
// Centralises all ws-legal-update field reads so the shortcode layer
// never calls get_field() or get_post_meta() directly.
//
// @param int $jx_id  Jurisdiction post ID to scope results. 0 = site-wide.
// @param int $count  Maximum number of records to return.
// @return array      Array of data items ready for the render layer.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_legal_updates_data( $jx_id = 0, $count = 5 ) {

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => max( 1, (int) $count ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    if ( $jx_id ) {
        // ws_legal_update_jurisdiction is an ACF relationship field that
        // serialises post IDs — LIKE with a quoted ID matches within it.
        $query_args['meta_query'] = [ [
            'key'     => 'ws_legal_update_jurisdiction',
            'value'   => '"' . (int) $jx_id . '"',
            'compare' => 'LIKE',
        ] ];
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return [];
    }

    $items = [];
    foreach ( $updates as $update ) {
        $effective_raw = get_post_meta( $update->ID, 'ws_legal_update_effective_date', true );
        $items[] = [
            'title'         => get_the_title( $update->ID ),
            'source_url'    => get_post_meta( $update->ID, 'ws_legal_update_source_url', true ) ?: '',
            'law_name'      => get_post_meta( $update->ID, 'ws_legal_update_law_name',   true ) ?: '',
            'fmt_effective' => $effective_raw ? date( 'F j, Y', strtotime( $effective_raw ) ) : '',
            'post_date'     => get_the_date( 'F j, Y', $update->ID ),
            'summary_html'  => wp_kses_post( get_post_meta( $update->ID, 'ws_legal_update_summary', true ) ?: '' ),
        ];
    }

    return $items;
}


// ════════════════════════════════════════════════════════════════════════════
// Cache Invalidation
//
// Clears all jurisdiction transients whenever a jurisdiction post is saved.
// Covers both the full post list cache and the index data cache, keeping
// them consistent with each other.
//
// Also clears the per-term transient for this jurisdiction so that
// ws_get_id_by_code() immediately reflects taxonomy reassignments.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'save_post_jurisdiction', function( $post_id ) {

    // Clear list and index caches
    delete_transient( 'ws_all_jurisdictions_cache' );
    delete_transient( 'ws_jx_index_cache' );

    // Clear the per-term transient for this jurisdiction
    $terms = wp_get_post_terms( $post_id, 'ws_jurisdiction' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        delete_transient( 'ws_id_for_term_' . $terms[0]->term_id );
    }

} );