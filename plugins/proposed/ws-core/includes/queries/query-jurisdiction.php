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
 *      ├── jx-summary
 *      ├── s
 *      ├── jx-statutes
 *      └── s
 *
 * Each dataset is connected to the jurisdiction record via
 * ACF relationship fields defined in acf-jurisdiction.php.
 *
 * INTERNAL IDENTIFIER
 * -------------------
 * ws_jx_code is the canonical two-letter machine identifier
 * used across the plugin.
 *
 * Examples:
 *      CA  = California
 *      TX  = Texas
 *      NY  = New York
 *      US  = Federal Government
 *      DC  = District of Columbia
 *      PR  = Puerto Rico
 *
 * CACHING
 * -------
 * Transient caching is used for expensive or repeated queries.
 * All jurisdiction transients are invalidated on save_post
 * for the jurisdiction CPT.
 *
 * Transient keys:
 *      ws_id_for_{JX_CODE}         — post ID lookup by code
 *      ws_all_jurisdictions_cache  — full post object list
 *      ws_jx_index_cache           — index data with counts
 *
 * DATASET RETURN FORMAT
 * ---------------------
 * All dataset functions (summary, procedures, resources) return a
 * consistent base array:
 *
 *      [
 *          'id'      => int,
 *          'title'   => string,
 *          'url'     => string,
 *          'status'  => string,
 *          'content' => string,  // raw post_content — apply the_content in render layer
 *      ]
 *
 * ws_get_jx_statutes() returns an array-of-arrays using the same shape,
 * plus an 'is_fed' boolean key. May contain two entries (state + federal).
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
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction ID Lookup
//
// Retrieves the post ID for a jurisdiction by its two-letter ws_jx_code.
// Result is cached in a transient for 24 hours to avoid repeated meta queries.
// Returns false if no matching jurisdiction is found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_id_by_code( $jx_code ) {

    if ( empty( $jx_code ) ) {
        return false;
    }

    $jx_code   = strtoupper( sanitize_text_field( $jx_code ) );
    $cache_key = 'ws_id_for_' . $jx_code;
    $post_id   = get_transient( $cache_key );

    if ( false === $post_id ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'meta_key'       => 'ws_jx_code',
            'meta_value'     => $jx_code,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true, // Skip total row count for performance
        ] );

        $post_id = ! empty( $query->posts ) ? $query->posts[0] : 0;
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

    return [

        // ── Identity ─────────────────────────────────────────────────────────
        'id'   => $post_id,
        'name' => get_the_title( $post_id ),
        'type' => get_field( 'ws_jurisdiction_type', $post_id ),
        'code' => get_field( 'ws_jx_code', $post_id ),

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
// Retrieves the related jx-summary post for the given jurisdiction.
// Accepts a numeric post ID or a two-letter ws_jx_code string.
// Returns a base array of post data, or false if not found.
//
// @todo - Update array as jx-summary CPT fields are defined.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_summary( $input ) {

    $post_id = ws_resolve_jx_id( $input );

    if ( ! $post_id ) {
        return false;
    }

    $related = get_field( 'ws_related_summary', $post_id );

    if ( ! $related ) {
        return false;
    }

    // @todo - Update array as jx-summary ACF fields are expanded.
    // Note: summary content is stored in ACF fields, not post_content.
    // The shortcode layer reads ACF fields directly from $data['id'].
    return [
        'id'      => $related->ID,
        'title'   => get_the_title( $related->ID ),
        'url'     => get_permalink( $related->ID ),
        'status'  => get_post_status( $related->ID ),
        'content' => get_post_field( 'post_content', $related->ID ),
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

function ws_get_jx_procedures( $input ) {

    $post_id = ws_resolve_jx_id( $input );

    if ( ! $post_id ) {
        return false;
    }

    $related = get_field( 'ws_related_procedures', $post_id );

    if ( ! $related ) {
        return false;
    }

    // @todo - Update array as s CPT fields are defined.
    return [
        'id'      => $related->ID,
        'title'   => get_the_title( $related->ID ),
        'url'     => get_permalink( $related->ID ),
        'status'  => get_post_status( $related->ID ),
        'content' => get_post_field( 'post_content', $related->ID ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Statutes
//
// Retrieves the related jx-statutes post for the given jurisdiction.
// If the jurisdiction is NOT 'US', it automatically merges the Federal 
// statutes into the return array to ensure comprehensive coverage.
//
// Returns an array of statute data arrays, or false if none found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_statutes( $input ) {

    // 1. Resolve the primary requested Jurisdiction ID
    $post_id = ws_resolve_jx_id( $input );
    $jx_code = is_numeric( $input ) ? get_field( 'ws_jx_code', $post_id ) : strtoupper( $input );

    if ( ! $post_id ) {
        return false;
    }

    // Each entry: [ 'post' => WP_Post, 'is_fed' => bool ]
    // is_fed is set at collection time — jx-statutes posts do not carry
    // ws_jx_code on their own meta, so it cannot be derived from the
    // statute post itself after the fact.
    $statutes_to_process = [];

    // 2. Fetch the primary (State/Territory) related statute
    $primary_related = get_field( 'ws_related_statutes', $post_id );
    if ( $primary_related ) {
        $statutes_to_process[] = [ 'post' => $primary_related, 'is_fed' => false ];
    }

    // 3. Logic: If NOT 'US', fetch the Federal 'US' statute record as well
    if ( $jx_code !== 'US' ) {
        $fed_id = ws_get_id_by_code( 'US' );
        if ( $fed_id ) {
            $fed_related = get_field( 'ws_related_statutes', $fed_id );
            if ( $fed_related ) {
                $statutes_to_process[] = [ 'post' => $fed_related, 'is_fed' => true ];
            }
        }
    }

    if ( empty( $statutes_to_process ) ) {
        return false;
    }

    $output = [];

    // 4. Transform all identified records into the dataset format.
    // is_fed was set at collection time above — do not re-derive here.
    foreach ( $statutes_to_process as $item ) {
        $statute_post = $item['post'];
        $output[] = [
            'id'      => $statute_post->ID,
            'title'   => get_the_title( $statute_post->ID ),
            'url'     => get_permalink( $statute_post->ID ),
            'status'  => get_post_status( $statute_post->ID ),
            'content' => get_post_field( 'post_content', $statute_post->ID ),
            'is_fed'  => $item['is_fed'],
        ];
    }

    return $output;
}

// ════════════════════════════════════════════════════════════════════════════
// Dataset: Resources
//
// Retrieves the related s post for the given jurisdiction.
// Accepts a numeric post ID or a two-letter ws_jx_code string.
// Returns a base array of post data, or false if not found.
//
// @todo - Update array as s CPT fields are defined.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_resources( $input ) {

    $post_id = ws_resolve_jx_id( $input );

    if ( ! $post_id ) {
        return false;
    }

    $related = get_field( '', $post_id );

    if ( ! $related ) {
        return false;
    }

    // @todo - Update array as s CPT fields are defined.
    return [
        'id'      => $related->ID,
        'title'   => get_the_title( $related->ID ),
        'url'     => get_permalink( $related->ID ),
        'status'  => get_post_status( $related->ID ),
        'content' => get_post_field( 'post_content', $related->ID ),
    ];
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

                $type = get_field( 'ws_jurisdiction_type', $post->ID ) ?: 'state';
                $code = get_field( 'ws_jx_code', $post->ID );

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
// Cache Invalidation
//
// Clears all jurisdiction transients whenever a jurisdiction post is saved.
// Covers both the full post list cache and the index data cache, keeping
// them consistent with each other.
//
// Also clears the per-code transient for the saved post so that
// ws_get_id_by_code() immediately reflects any ws_jx_code changes.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'save_post_jurisdiction', function( $post_id ) {

    // Clear list and index caches
    delete_transient( 'ws_all_jurisdictions_cache' );
    delete_transient( 'ws_jx_index_cache' );

    // Clear the per-code transient for this specific jurisdiction
    $jx_code = get_field( 'ws_jx_code', $post_id );
    if ( $jx_code ) {
        delete_transient( 'ws_id_for_' . strtoupper( $jx_code ) );
    }

} );