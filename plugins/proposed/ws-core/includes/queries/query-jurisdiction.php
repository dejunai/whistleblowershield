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
 * ws_jx_code meta has been retired. All lookups use taxonomy queries.
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
 *      ws_all_jurisdictions_cache  — full post object list
 *      ws_jx_index_cache           — index data with counts
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
 * All dataset functions return a consistent base array. Note: 'record' keys
 * use short aliases — they are PHP array keys, not meta key names:
 *
 *      [
 *          'id'      => int,
 *          'title'   => string,
 *          'url'     => string,
 *          'status'  => string,
 *          'content' => string,  // raw post_content — apply the_content in render layer
 *          'record'  => [
 *              'create_author'      => int,    // WP user ID (ws_auto_create_author)
 *              'author_name'        => string, // display name resolved from create_author
 *              'date_created'       => string, // Y-m-d local (ws_auto_date_created)
 *              'last_edited_author' => int,    // WP user ID (ws_auto_last_edited_author)
 *              'editor_name'        => string, // display name resolved from last_edited_author
 *              'last_edited'        => string, // Y-m-d local (ws_auto_last_edited)
 *          ],
 *      ]
 *
 * CPTs with plain English fields additionally carry a 'plain' sub-array;
 * see ws_build_plain_english_array() below.
 *
 * ws_get_jx_statute_data() returns an array-of-arrays using the same shape,
 * plus an 'is_fed' boolean key. May contain two groups (state + federal).
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
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. Removed ws_get_jurisdiction_by_code
 *        in favor of ws_get_id_by_code. Updated field names to match
 *        acf-jurisdiction.php v2.1.0. Added record management fields to
 *        ws_get_jurisdiction_data. Updated dataset stubs to return consistent
 *        base arrays and accept ws_jx_code as input. Consolidated cache
 *        invalidation on save_post.
 * 2.1.1  ws_get_jx_statutes() now returns with Federal Statutes appended to
 *        Statutes of the specified Jurisdiction when !US is called.
 * 2.3.1  All content keys normalized to raw get_post_field('post_content').
 *        Render layer applies the_content filters. ws_get_jx_statutes()
 *        returns array-of-arrays; shape documented above.
 * 3.0.0  Architecture refactor (Phase 3.1+3.2):
 *        ws_jx_code meta retired as join mechanism. All jurisdiction lookups
 *        now use the ws_jurisdiction taxonomy. ws_get_id_by_code() migrated
 *        to taxonomy query; transient cache rekeyed to ws_id_for_term_{term_id}.
 *        ws_get_jurisdiction_data() and ws_get_jurisdiction_index_data() read
 *        jurisdiction code from taxonomy term slug. save_post_jurisdiction hook
 *        updated to clear per-term transient.
 * 3.1.0  Dropped meta_prefix from all stamp meta keys. All dataset functions
 *        now include a 'record' sub-array with unprefixed stamp fields.
 *        Added ws_resolve_display_name() and ws_build_record_array() private
 *        helpers; all datasets return author_name and last_edited_by as
 *        resolved display names. Added ws_build_plain_english_array() helper;
 *        all plain-English-capable CPTs return a 'plain' sub-array including
 *        plain_reviewed_by and plain_reviewed_name. ws_get_jurisdiction_data()
 *        updated to read stamp fields via get_post_meta() with unprefixed keys.
 *        Removed prefixed summarized key reads from ws_get_jx_summary_data();
 *        summarized_by/summarized_date dropped from summary return (jx-summary
 *        carries no has_plain_english toggle). Removed formatted date fields
 *        (fmt_created, fmt_reviewed, fmt_effective) — dates are stored as
 *        Y-m-d; render layer formats for display. ws_get_legal_updates_data()
 *        returns raw effective_date and post_date; formatting removed.
 *        save_post_jurisdiction hook consolidated: cache invalidation and
 *        ws_jx_term_id write combined in one callback.
 * 3.2.0  Legal update system overhaul and field name audit:
 *        - Fixed 8 get_field() calls missing the jx_ infix (ws_gov_portal_url,
 *          ws_executive_url, etc. → ws_jx_gov_portal_url, ws_jx_executive_url, etc.)
 *          to match acf-jurisdictions.php field names.
 *        - ws_jurisdiction_type → ws_jurisdiction_class throughout, matching the
 *          ACF field name as of acf-jurisdictions.php v3.0+.
 *        - ws_get_legal_updates_data(): new $public_only parameter; excludes
 *          update_type 'internal' and 'other' when true — for public shortcodes.
 *        - Jurisdiction filter replaced: meta_query on ws_update_jurisdiction term ID
 *          → tax_query on ws_jurisdiction taxonomy. Requires save_terms=1 (ACF v3.5.0)
 *          and wp_set_post_terms() in the hook (v1.1.0).
 *        - Return array expanded: added post_id, update_date, update_type,
 *          multi_jurisdiction; source_post_id and source_post_type moved from
 *          get_post_field() to get_post_meta() (correct function for custom meta).
 *        - ws_update_source_url renamed from ws_update_source to comply with
 *          project convention that all URL-valued meta keys end in _url.
 *        - Return key renamed summary_html → summary_wysiwyg to accurately
 *          reflect that the field is a wysiwyg ACF type (content is sanitized
 *          via wp_kses_post before return; safe to echo directly).
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Resolve Display Name
//
// Resolves a WordPress user ID to the user's display name.
// Returns an empty string if the user ID is falsy or the user does not exist.
// Used by all dataset functions so the render layer never calls get_userdata().
// ════════════════════════════════════════════════════════════════════════════

/**
 * Returns the display name for a given WP user ID.
 *
 * @param  int    $user_id  WordPress user ID.
 * @return string           Display name, or empty string if not resolvable.
 */
function ws_resolve_display_name( $user_id ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) {
        return '';
    }
    $user = get_userdata( $user_id );
    return $user ? $user->display_name : '';
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Build Record Array
//
// Constructs the standard 'record' sub-array from unprefixed stamp meta keys.
// Used by every dataset function to guarantee a consistent return shape.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Builds the standard stamp record sub-array for a given post.
 *
 *
 * @param  int   $post_id  Post ID.
 * @return array
 */
function ws_build_record_array( $post_id ) {

    $create_author_id      = (int) get_post_meta( $post_id, 'ws_auto_create_author',      true );
    $last_edited_author_id = (int) get_post_meta( $post_id, 'ws_auto_last_edited_author', true );

    return [
        'create_author'      => $create_author_id,
        'author_name'        => ws_resolve_display_name( $create_author_id ),
        'date_created'       => get_post_meta( $post_id, 'ws_auto_date_created', true ),
        'last_edited'        => get_post_meta( $post_id, 'ws_auto_last_edited',  true ),
        'last_edited_author' => $last_edited_author_id,
        'editor_name'        => ws_resolve_display_name( $last_edited_author_id ),
		];
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Build Plain English Array
//
// Constructs the standard 'plain' sub-array for CPTs that carry the
// has_plain_english / plain_reviewed workflow fields.
//
// Does not apply to jx-summary, which is inherently plain English and
// carries no has_plain_english toggle.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Builds the standard plain English sub-array for a given post.
 *
 * @param  int   $post_id  Post ID.
 * @return array
 */
function ws_build_plain_english_array( $post_id ) {

    $plain_english_reviewed_by_id = (int) get_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by', true );
    $plain_english_by_id          = (int) get_post_meta( $post_id, 'ws_auto_plain_english_by',          true );

    return [
        'has_plain_english'             => (bool) get_post_meta( $post_id, 'has_plain_english', true ),
        'plain_english_wysiwyg'         => get_post_meta( $post_id, 'plain_english_wysiwyg', true ),
        'plain_english_by'              => $plain_english_by_id,
        'plain_english_by_name'         => ws_resolve_display_name( $plain_english_by_id ),
        'plain_english_date'            => get_post_meta( $post_id, 'ws_auto_plain_english_date', true ),
        'plain_english_reviewed'        => (bool) get_post_meta( $post_id, 'plain_english_reviewed', true ),
        'plain_english_reviewed_by'     => $plain_english_reviewed_by_id,
        'plain_english_reviewed_name'   => ws_resolve_display_name( $plain_english_reviewed_by_id ),
  ];
}


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction ID Lookup
//
// Retrieves the post ID for a jurisdiction by its two-letter USPS code.
// Uses the ws_jurisdiction taxonomy term (slug = lowercase USPS code) to
// locate the jurisdiction post via tax_query.
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

    $flag     = get_field( 'ws_jx_flag', $post_id );
    $jx_terms = wp_get_post_terms( $post_id, 'ws_jurisdiction', [ 'fields' => 'slugs' ] );
    $jx_code  = ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) ? strtoupper( $jx_terms[0] ) : '';

    return [

        // ── Identity ─────────────────────────────────────────────────────────
        'id'    => $post_id,
        'name'  => get_the_title( $post_id ),
        'class' => get_field( 'ws_jurisdiction_class', $post_id ),
        'code'  => $jx_code,

        // ── Flag ─────────────────────────────────────────────────────────────
        'flag' => [
            'url'         => ( is_array( $flag ) && ! empty( $flag['url'] ) ) ? $flag['url'] : '',
            'attribution' => get_field( 'ws_jx_flag_attribution', $post_id ),
            'source_url'  => get_field( 'ws_jx_flag_source_url',  $post_id ),
            'license'     => get_field( 'ws_jx_flag_license',     $post_id ),
        ],

        // ── Government Links ─────────────────────────────────────────────────
        'gov' => [
            'portal_url'        => get_field( 'ws_jx_gov_portal_url',     $post_id ),
            'portal_label'      => get_field( 'ws_jx_gov_portal_label',   $post_id ),
            'executive_url'     => get_field( 'ws_jx_executive_url',      $post_id ),
            'executive_label'   => get_field( 'ws_jx_executive_label',    $post_id ),
            'wb_auth_url'       => get_field( 'ws_jx_wb_authority_url',   $post_id ),
            'wb_auth_label'     => get_field( 'ws_jx_wb_authority_label', $post_id ),
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
            'taxonomy' => 'ws_jurisdiction',
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
        'limitations'   => get_post_meta( $sid, 'ws_jx_limitations_wysiwyg',       true ),
        // Review field — jx-summary uses last_reviewed directly (no has_plain_english gate)
		// 'has_plain_english' is always true for jx-summary no meta flag required
        // 'last_reviewed' => get_post_meta( $sid, 'last_reviewed', true ), // - depreciated
		// Plain language fields
        'plain'         => ws_build_plain_english_array( $sid ),
        // Record management
        'record'        => ws_build_record_array( $sid ),
    ];
}


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
                'official_name'           => get_post_meta( $sid, 'ws_jx_statute_official_name',              true ),
                'limit_value'             => get_post_meta( $sid, 'ws_jx_statute_limit_value',                true ),
                'limit_unit'              => get_post_meta( $sid, 'ws_jx_statute_limit_unit',                 true ),
                'trigger'                 => get_post_meta( $sid, 'ws_jx_statute_limit_trigger',               true ),
                'tolling_notes'           => get_post_meta( $sid, 'ws_jx_statute_tolling_notes',              true ),
                'exhaustion_required'     => (bool) get_post_meta( $sid, 'ws_jx_statute_exhaustion_required', true ),
                'exhaustion_details'      => get_post_meta( $sid, 'ws_jx_statute_exhaustion_details',         true ),
                'burden_of_proof'         => get_post_meta( $sid, 'ws_jx_statute_burden_of_proof',            true ),
                'burden_of_proof_details' => get_post_meta( $sid, 'ws_statute_burden_of_proof_details',       true ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $sid ),
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
                'type'     => get_post_meta( $cid, 'ws_jx_citation_type',   true ),
                'label'    => get_post_meta( $cid, 'ws_jx_citation_label',  true ),
                'cite_url' => get_post_meta( $cid, 'ws_jx_citation_url',    true ),
                'is_pdf'   => (bool) get_post_meta( $cid, 'ws_jx_citation_is_pdf', true ),
                'order'    => (int)  get_post_meta( $cid, 'order',             true ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $cid ),
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
// Returns all published jx-interpretation records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true,
// sorted by order ASC.
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
                'case_name'   => get_post_meta( $iid, 'ws_jx_interp_case_name',     true ),
                'citation'    => get_post_meta( $iid, 'ws_jx_interp_case_citation', true ),
                'opinion_url' => get_post_meta( $iid, 'ws_jx_interp_url',           true ),
                'court'       => get_post_meta( $iid, 'ws_jx_interp_court',         true ),
                'year'        => get_post_meta( $iid, 'ws_jx_interp_year',          true ),
                'favorable'   => (bool) get_post_meta( $iid, 'ws_jx_interp_favorable', true ),
                'summary'     => get_post_meta( $iid, 'ws_jx_interp_summary',       true ),
                'statute_id'  => (int) get_post_meta( $iid, 'ws_jx_interp_statute_id', true ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $iid ),
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
            'taxonomy' => 'ws_jurisdiction',
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
            'agency_code'   => get_post_meta( $aid, 'ws_agency_code',            true ),
            'agency_name'   => get_post_meta( $aid, 'ws_agency_name',            true ),
            'agency_url'    => get_post_meta( $aid, 'ws_agency_url',             true ),
            'reporting_url' => get_post_meta( $aid, 'ws_agency_reporting_url',   true ),
            'phone'         => get_post_meta( $aid, 'ws_agency_phone',           true ),
            'anonymous'     => (bool) get_post_meta( $aid, 'ws_agency_accepts_anonymous', true ),
            'reward'        => (bool) get_post_meta( $aid, 'ws_agency_reward_program',    true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $aid ),
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
            'taxonomy' => 'ws_jurisdiction',
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
            'ao_name'       => get_post_meta( $oid, 'ws_aorg_name',             true ),
            'ao_url'        => get_post_meta( $oid, 'ws_aorg_website_url',      true ),
            'ao_intake_url' => get_post_meta( $oid, 'ws_aorg_intake_url',       true ),
            'ao_phone'      => get_post_meta( $oid, 'ws_aorg_phone',            true ),
            'ao_mission'    => get_post_meta( $oid, 'ws_aorg_mission',          true ),
            'ao_provides'   => get_post_meta( $oid, 'ws_aorg_provides',         true ),
            'ao_cost_model' => get_post_meta( $oid, 'ws_aorg_cost_model',       true ),
            'ao_anonymous'  => (bool) get_post_meta( $oid, 'ws_aorg_accepts_anonymous', true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $oid ),
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

    $cache_key     = 'ws_all_jurisdictions_cache';
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

                $type     = get_field( 'ws_jurisdiction_class', $post->ID ) ?: 'state';
                $jx_slugs = wp_get_post_terms( $post->ID, 'ws_jurisdiction', [ 'fields' => 'slugs' ] );
                $code     = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? strtoupper( $jx_slugs[0] ) : '';

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
// Legal Updates Query
//
// Centralises all ws-legal-update field reads so the shortcode layer
// never calls get_field() or get_post_meta() directly.
//
// Jurisdiction filtering uses a tax_query on ws_jurisdiction (not post_meta).
// This requires save_terms=1 on the ACF jurisdiction field and wp_set_post_terms()
// in the hook — both enforced as of acf-legal-updates.php v3.5.0 and
// admin-major-edit-hook.php v1.1.0.
//
// Dates are returned as stored (Y-m-d / MySQL datetime). The render layer
// is responsible for formatting dates for display.
//
// @param int  $jx_id       Jurisdiction post ID to scope results. 0 = site-wide.
// @param int  $count       Maximum number of records to return.
// @param bool $public_only If true, excludes 'internal' and 'other' update types.
//                          Pass true for all public-facing shortcode/render calls.
// @return array            Array of data items ready for the render layer.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_legal_updates_data( $jx_id = 0, $count = 5, $public_only = false ) {

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => max( 1, (int) $count ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    if ( $jx_id ) {
        // Convert jurisdiction post ID → ws_jurisdiction term ID.
        // Legal updates are linked via the taxonomy table (save_terms=1).
        $jx_terms = wp_get_post_terms( $jx_id, 'ws_jurisdiction', [ 'fields' => 'ids' ] );
        if ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'ws_jurisdiction',
                'field'    => 'term_id',
                'terms'    => (int) $jx_terms[0],
            ] ];
        }
    }

    if ( $public_only ) {
        $query_args['meta_query'] = [ [
            'key'     => 'ws_legal_update_type',
            'value'   => [ 'internal', 'other' ],
            'compare' => 'NOT IN',
        ] ];
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return [];
    }

    $items = [];
    foreach ( $updates as $update ) {
        $uid     = $update->ID;
        $items[] = [
            // ── Identity ──────────────────────────────────────────────────
            'post_id'            => $uid,
            'title'              => get_the_title( $uid ),

            // ── Dates ─────────────────────────────────────────────────────
            'update_date'        => get_post_meta( $uid, 'ws_legal_update_date',                    true ),
            'effective_date'     => get_post_meta( $uid, 'ws_legal_update_effective_date',    true ),
            'post_date'          => get_post_field( 'post_date', $uid ),

            // ── Classification ────────────────────────────────────────────
            'update_type'        => get_post_meta( $uid, 'ws_legal_update_type',                    true ),
            'multi_jurisdiction' => (bool) get_post_meta( $uid, 'ws_legal_update_multi_jurisdiction', true ),

            // ── Content ───────────────────────────────────────────────────
            'law_name'           => get_post_meta( $uid, 'ws_legal_update_law_name',          true ) ?: '',
            'source_url'         => get_post_meta( $uid, 'ws_legal_update_source_url',              true ) ?: '',
            'summary_wysiwyg'    => wp_kses_post( get_post_meta( $uid, 'ws_legal_update_summary_wysiwyg', true ) ?: '' ),

            // ── Source post backlink ──────────────────────────────────────
            'source_post_id'     => (int) get_post_meta( $uid, 'ws_legal_update_source_post_id',   true ),
            'source_post_type'   => get_post_meta(       $uid, 'ws_legal_update_source_post_type', true ),

            // ── Record management ─────────────────────────────────────────
            'record'             => ws_build_record_array( $uid ),
        ];
    }

    return $items;
}


// ════════════════════════════════════════════════════════════════════════════
// Reference Materials
//
// ws_get_ref_materials( $post_id )
//     Returns an array of approved ws-reference items linked to a parent
//     post via the ws_ref_materials ACF relationship field.
//     Only approved references (ws_ref_approved == 1) are included.
//     Returns [] if no approved references exist.
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
        if ( ! (bool) get_post_meta( $rid, 'ws_ref_approved', true ) ) continue;

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

    $allowed_types = [ 'jx-statute', 'jx-citation', 'jx-interpretation' ];
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
// Fires on save_post_jurisdiction. Clears all jurisdiction transients to keep
// the list cache, index cache, and per-term ID cache consistent with the
// saved state.
//
// Also writes ws_jx_term_id post meta, providing a direct post->term_id
// mapping for seeders and admin tooling without a get_term_by() call at
// runtime.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'save_post_jurisdiction', function( $post_id ) {

    // Clear list and index caches.
    delete_transient( 'ws_all_jurisdictions_cache' );
    delete_transient( 'ws_jx_index_cache' );

    // Resolve the assigned ws_jurisdiction term once for both operations.
    $terms = wp_get_post_terms( $post_id, 'ws_jurisdiction' );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        // Clear the per-term ID cache so ws_get_id_by_code() reflects any
        // taxonomy reassignment immediately.
        delete_transient( 'ws_id_for_term_' . $terms[0]->term_id );

        // Write the direct post->term_id mapping.
        update_post_meta( $post_id, 'ws_jx_term_id', $terms[0]->term_id );
    }

} );
