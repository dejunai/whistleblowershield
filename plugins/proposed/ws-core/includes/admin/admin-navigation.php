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

function ws_render_jx_navigation_box($post)
{

    $summary     = get_field('ws_related_summary', $post->ID);
    $procedures  = get_field('ws_related_procedures', $post->ID);
    $statutes    = get_field('ws_related_statutes', $post->ID);
    $resources   = get_field('ws_related_resources', $post->ID);

    echo '<div style="line-height:1.6;">';

    ws_render_admin_link('Summary', $summary, 'jx_summary');
    ws_render_admin_link('Procedures', $procedures, 'jx_procedures');
    ws_render_admin_link('Statutes', $statutes, 'jx_statutes');
    ws_render_admin_link('Resources', $resources, 'jx_resources');

    echo '</div>';

}


/*
---------------------------------------------------------
Helper: Render Link
---------------------------------------------------------
*/

function ws_render_admin_link($label, $related_post, $post_type)
{

    echo '<p>';

    if ($related_post) {

        $url = get_edit_post_link($related_post->ID);

        echo '<strong>' . esc_html($label) . '</strong><br>';

        echo '<a href="' . esc_url($url) . '">Edit ' . esc_html($label) . '</a>';

    } else {

        $url = admin_url('post-new.php?post_type=' . $post_type);

        echo '<strong>' . esc_html($label) . '</strong><br>';

        echo '<a href="' . esc_url($url) . '">Create ' . esc_html($label) . '</a>';

    }

    echo '</p>';

}