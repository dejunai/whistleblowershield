<?php
/**
 * File: admin-navigation.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Adds an administrative navigation panel inside the Jurisdiction
 * editor to quickly access related dataset records.
 *
 * Without this panel, editors must manually navigate WordPress
 * menus to locate related records such as:
 *
 *      • Summary
 *      • Statutes
 *      • Resources
 *
 * This file improves workflow by providing direct edit links to
 * related datasets.
 *
 *
 * ARCHITECTURE
 * ------------
 *
 * jurisdiction (core record)
 *      ├── jx-summary      (one-to-one, ACF relationship)
 *      ├── jx-statute      (one-to-one, ACF relationship)
 *      ├── s    (one-to-one, ACF relationship)
 *      └── jx-citation     (many-to-one, scoped by ws_jurisdiction taxonomy)
 *
 *
 * SHARED HELPER
 * -------------
 * ws_get_attached_citation_count( $post_id )
 *
 * Returns the number of published jx-citation records assigned the same
 * ws_jurisdiction term as the given jurisdiction post with attach_flag = 1.
 * Defined here and shared by admin-columns.php and
 * jurisdiction-dashboard.php (both load after this file).
 *
 *
 * WORKFLOW BENEFIT
 * ----------------
 *
 * Editors can jump directly between related records while
 * developing jurisdiction content.
 *
 *
 * VERSION
 * -------
 * 2.1.0  Initial admin navigation implementation
 * 2.1.3  Smart creation support (Create Now links)
 * 2.3.1  Fixed CPT slug typos (jx_summary → jx-summary for all four).
 *        Added ws_get_attached_citation_count() shared helper.
 *        Added Citations row: count badge + Add Citation + View All.
 * 3.0.0  Architecture refactor (Phase 3.2):
 *        - Resources row removed (CPT deleted).
 *        - Create Now and Add Citation URLs migrated from ws_jx_code query
 *          arg to ws_jx_term (taxonomy term slug).
 *        - ws_get_attached_citation_count() migrated from ws_jx_code meta
 *          query to ws_jurisdiction taxonomy query with attach_flag meta.
 */
if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
Add Meta Box
---------------------------------------------------------
*/

add_action('add_meta_boxes', 'ws_add_jx_navigation_box');

function ws_add_jx_navigation_box()
{

    add_meta_box(
        'ws_jx_navigation',
        'Jurisdiction Data Navigation',
        'ws_render_jx_navigation_box',
        'jurisdiction',
        'side',
        'high'
    );

}


/*
---------------------------------------------------------
Render Navigation Box
---------------------------------------------------------
*/

function ws_render_jx_navigation_box($post) {

    // Resolve taxonomy term slug for this jurisdiction (used to scope addendum queries).
    $jx_slugs  = wp_get_post_terms( $post->ID, WS_JURISDICTION_TERM_ID, [ 'fields' => 'slugs' ] );
    $term_slug = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? $jx_slugs[0] : '';
    $term      = $term_slug ? get_term_by( 'slug', $term_slug, WS_JURISDICTION_TERM_ID ) : null;
    $term_id   = ( $term && ! is_wp_error( $term ) ) ? $term->term_id : 0;

    // Look up existing addendum posts via taxonomy (replaces get_field on relationship fields).
    $summary_post  = null;
    $statutes_post = null;

    if ( $term_id ) {
        $summary_ids = get_posts( [
            'post_type'      => 'jx-summary',
            'post_status'    => [ 'publish', 'draft', 'pending' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => [ [ 'taxonomy' => WS_JURISDICTION_TERM_ID, 'field' => 'term_id', 'terms' => $term_id ] ],
        ] );
        if ( ! empty( $summary_ids ) ) {
            $summary_post = get_post( $summary_ids[0] );
        }

        $statute_ids = get_posts( [
            'post_type'      => 'jx-statute',
            'post_status'    => [ 'publish', 'draft', 'pending' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => [ [ 'taxonomy' => WS_JURISDICTION_TERM_ID, 'field' => 'term_id', 'terms' => $term_id ] ],
        ] );
        if ( ! empty( $statute_ids ) ) {
            $statutes_post = get_post( $statute_ids[0] );
        }
    }

    echo '<div class="ws-admin-nav-wrapper" style="line-height:1.6;">';

    ws_render_admin_link('Summary',  $summary_post,  'jx-summary', $post->ID);
    ws_render_admin_link('Statutes', $statutes_post, 'jx-statute', $post->ID);

    ws_render_citation_row( $post->ID );

    echo '</div>';
}


/*
---------------------------------------------------------
Helper: Render Link
---------------------------------------------------------
*/

function ws_render_admin_link($label, $related, $post_type, $parent_id) {
    echo '<div style="margin-bottom: 12px; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff;">';
    echo '<strong style="display: block; margin-bottom: 5px;">' . esc_html($label) . '</strong>';

    if ($related) {
        $status = get_post_status($related->ID);
        $color  = ($status === 'publish') ? '#46b450' : '#ffa500';
        echo '<span style="font-size: 11px; color: ' . $color . ';">● ' . ucfirst($status) . '</span><br>';
        echo '<a class="button button-small" href="' . get_edit_post_link($related->ID) . '">Edit Record</a>';
    } else {
        // Build the Smart Link — passes ws_jx_term (taxonomy term slug) so the
        // new-post screen auto-assigns the ws_jurisdiction taxonomy term via
        // the wp_insert_post hook in admin-hooks.php.
        $parent_name = get_the_title( $parent_id );
        $jx_slugs    = wp_get_post_terms( $parent_id, WS_JURISDICTION_TERM_ID, [ 'fields' => 'slugs' ] );
        $term_slug   = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? $jx_slugs[0] : '';
        $create_url  = add_query_arg( [
            'post_type'  => $post_type,
            'ws_jx_term' => $term_slug,
            'post_title' => "{$parent_name} {$label}",
        ], admin_url( 'post-new.php' ) );

        echo '<span style="font-size: 11px; color: #dc3232;">● Not Created</span><br>';
        echo '<a class="button button-small button-primary" href="' . esc_url($create_url) . '">Create Now</a>';
    }
    echo '</div>';
}


/*
---------------------------------------------------------
Shared Helper: Attached Citation Count
---------------------------------------------------------
Returns the number of published jx-citation records that
are scoped to this jurisdiction (ws_jurisdiction taxonomy)
and have attach_flag set to true.

Shared by:
    admin-columns.php          — jurisdiction list table column
    jurisdiction-dashboard.php — health matrix row
---------------------------------------------------------
*/

function ws_get_attached_citation_count( $post_id ) {

    $terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TERM_ID );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return 0;
    }

    $term_id = $terms[0]->term_id;

    $query = new WP_Query( [
        'post_type'      => 'jx-citation',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
        'tax_query' => [ [
            'taxonomy' => WS_JURISDICTION_TERM_ID,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    return (int) $query->found_posts;
}


/*
---------------------------------------------------------
Render: Citations Row
---------------------------------------------------------
Always rendered — shows attached count with color-coded
threshold badge, plus Add Citation and View All buttons.

    0 citations   → red badge
    1–2 citations → orange badge
    3+ citations  → green badge

"Add Citation" links to new jx-citation with ws_jx_term in the URL.
"View All" filters the jx-citation list by taxonomy term.
---------------------------------------------------------
*/

function ws_render_citation_row( $post_id ) {

    $jx_slugs  = wp_get_post_terms( $post_id, WS_JURISDICTION_TERM_ID, [ 'fields' => 'slugs' ] );
    $term_slug = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? $jx_slugs[0] : '';
    $count     = ws_get_attached_citation_count( $post_id );

    if ( $count === 0 ) {
        $badge_color = '#dc3232'; // red
    } elseif ( $count <= 2 ) {
        $badge_color = '#ffa500'; // orange
    } else {
        $badge_color = '#46b450'; // green
    }

    $add_url = add_query_arg( [
        'post_type'  => 'jx-citation',
        'ws_jx_term' => $term_slug,
    ], admin_url( 'post-new.php' ) );

    $all_url = add_query_arg( [
        'post_type'      => 'jx-citation',
        WS_JURISDICTION_TERM_ID => $term_slug,
    ], admin_url( 'edit.php' ) );

    echo '<div style="margin-bottom: 12px; padding: 8px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff;">';
    echo '<strong style="display: block; margin-bottom: 5px;">Citations</strong>';
    echo '<span style="font-size: 11px; color: ' . esc_attr( $badge_color ) . ';">● ' . (int) $count . ' attached</span><br>';
    echo '<div style="margin-top: 5px; display: flex; gap: 5px; flex-wrap: wrap;">';
    echo '<a class="button button-small button-primary" href="' . esc_url( $add_url ) . '">Add Citation</a>';
    echo '<a class="button button-small" href="' . esc_url( $all_url ) . '">View All</a>';
    echo '</div>';
    echo '</div>';
}