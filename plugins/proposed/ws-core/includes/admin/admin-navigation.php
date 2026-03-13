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
 *      ├── jx_summary
 *      ├── jx_procedures
 *      ├── jx_statutes
 *      └── jx_resources
 *
 * Relationship fields are defined in:
 *
 *      /includes/acf/acf-jurisdiction.php
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
 */
/**
 * File: admin-navigation.php
 * Updated: 2.1.3 (Smart Creation Support)
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
    
    ws_render_admin_link('Summary',    $summary,    'jx_summary',    $post->ID);
    ws_render_admin_link('Procedures', $procedures, 'jx_procedures', $post->ID);
    ws_render_admin_link('Statutes',   $statutes,   'jx_statutes',   $post->ID);
    ws_render_admin_link('Resources',  $resources,  'jx_resources',  $post->ID);

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