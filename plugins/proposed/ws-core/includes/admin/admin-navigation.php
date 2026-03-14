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
 *      • Procedures
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
 *      ├── jx-procedures   (one-to-one, ACF relationship)
 *      ├── jx-statutes     (one-to-one, ACF relationship)
 *      ├── jx-resources    (one-to-one, ACF relationship)
 *      └── jx-citation     (many-to-one, queried by ws_jx_code)
 *
 * Relationship fields are defined in:
 *
 *      /includes/acf/acf-jurisdiction.php
 *
 *
 * SHARED HELPER
 * -------------
 * ws_get_attached_citation_count( $post_id )
 *
 * Returns the number of published jx-citation records where
 * ws_jx_code matches the given jurisdiction and ws_jx_cite_attach
 * is true. Defined here and shared by admin-columns.php and
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
    $summary    = get_field('ws_related_summary', $post->ID);
    $procedures = get_field('ws_related_procedures', $post->ID);
    $statutes   = get_field('ws_related_statutes', $post->ID);
    $resources  = get_field('ws_related_resources', $post->ID);

    echo '<div class="ws-admin-nav-wrapper" style="line-height:1.6;">';
    
    ws_render_admin_link('Summary',    $summary,    'jx-summary',    $post->ID);
    ws_render_admin_link('Procedures', $procedures, 'jx-procedures', $post->ID);
    ws_render_admin_link('Statutes',   $statutes,   'jx-statutes',   $post->ID);
    ws_render_admin_link('Resources',  $resources,  'jx-resources',  $post->ID);

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
        // Build the Smart Link
        $parent_name = get_the_title($parent_id);
        $create_url = add_query_arg([
            'post_type'    => $post_type,
            'ws_parent_id' => $parent_id,
            'post_title'   => "{$parent_name} {$label}"
        ], admin_url('post-new.php'));

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
are both matched to this jurisdiction (ws_jx_code) and
have ws_jx_cite_attach set to true.

Shared by:
    admin-columns.php          — jurisdiction list table column
    jurisdiction-dashboard.php — health matrix row
---------------------------------------------------------
*/

function ws_get_attached_citation_count( $post_id ) {

    $jx_code = get_post_meta( $post_id, 'ws_jx_code', true );

    if ( ! $jx_code ) {
        return 0;
    }

    $query = new WP_Query( [
        'post_type'      => 'jx-citation',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'ws_jx_code',
                'value'   => $jx_code,
                'compare' => '=',
            ],
            [
                'key'     => 'ws_jx_cite_attach',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
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

"Add Citation" pre-populates ws_jx_code on the new-post
screen via query arg (handled by admin-hooks.php).
"View All" filters the jx-citation list by ws_jx_code.
---------------------------------------------------------
*/

function ws_render_citation_row( $post_id ) {

    $jx_code = get_post_meta( $post_id, 'ws_jx_code', true );
    $count   = ws_get_attached_citation_count( $post_id );

    if ( $count === 0 ) {
        $badge_color = '#dc3232'; // red
    } elseif ( $count <= 2 ) {
        $badge_color = '#ffa500'; // orange
    } else {
        $badge_color = '#46b450'; // green
    }

    $add_url = add_query_arg( [
        'post_type'   => 'jx-citation',
        'ws_jx_code'  => $jx_code,
    ], admin_url( 'post-new.php' ) );

    $all_url = add_query_arg( [
        'post_type'   => 'jx-citation',
        'ws_jx_code'  => $jx_code,
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