<?php
/**
 * File: shortcodes-jurisdiction.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Registers the shortcodes responsible for rendering Jurisdiction
 * page components.
 *
 * These shortcodes are not intended for manual insertion by editors.
 * Instead they are executed automatically by:
 *
 *      render-jurisdiction.php
 *
 * Each shortcode retrieves the relevant dataset using the query layer
 * and passes the result to the section renderer.
 *
 *
 * ARCHITECTURE
 * ------------
 *
 * jurisdiction (public CPT)
 *      ├── jx_summary
 *      ├── jx_procedures
 *      ├── jx_statutes
 *      └── jx_resources
 *
 * Query Layer:
 *
 *      includes/queries/query-jurisdiction.php
 *
 * Section Renderer:
 *
 *      includes/render/section-renderer.php
 *
 *
 * DESIGN PRINCIPLE
 * ----------------
 *
 * Shortcodes should remain minimal and readable.
 *
 * Responsibilities of this file:
 *
 *      • retrieve datasets
 *      • prepare content
 *      • call render helpers
 *
 * Responsibilities NOT included:
 *
 *      • HTML layout (handled by section renderer)
 *      • database queries (handled by query layer)
 *
 *
 * VERSION
 * -------
 * 2.1.0  Refactored shortcode layer
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
Jurisdiction Header
---------------------------------------------------------
*/

add_shortcode('ws_jx_header', 'ws_shortcode_jx_header');

function ws_shortcode_jx_header()
{

    global $post;

    if (!$post) {
        return '';
    }

    return ws_render_jx_header($post->ID);

}



/*
---------------------------------------------------------
Summary
---------------------------------------------------------
*/

add_shortcode('ws_jx_summary', 'ws_shortcode_jx_summary');

function ws_shortcode_jx_summary()
{

    global $post;

    if (!$post) {
        return '';
    }

    $summary = ws_get_jx_summary($post->ID);

    if (!$summary) {
        return '';
    }

    $content = apply_filters('the_content', $summary->post_content);

    return ws_render_section(
        'Summary',
        $content
    );

}



/*
---------------------------------------------------------
Procedures
---------------------------------------------------------
*/

add_shortcode('ws_jx_procedures', 'ws_shortcode_jx_procedures');

function ws_shortcode_jx_procedures()
{

    global $post;

    if (!$post) {
        return '';
    }

    $procedures = ws_get_jx_procedures($post->ID);

    if (!$procedures) {
        return '';
    }

    $content = apply_filters('the_content', $procedures->post_content);

    return ws_render_section(
        'Procedures',
        $content
    );

}



/*
---------------------------------------------------------
Statutes
---------------------------------------------------------
*/

add_shortcode('ws_jx_statutes', 'ws_shortcode_jx_statutes');

function ws_shortcode_jx_statutes()
{

    global $post;

    if (!$post) {
        return '';
    }

    $statutes = ws_get_jx_statutes($post->ID);

    if (!$statutes) {
        return '';
    }

    $content = apply_filters('the_content', $statutes->post_content);

    return ws_render_section(
        'Relevant Statutes',
        $content
    );

}



/*
---------------------------------------------------------
Resources
---------------------------------------------------------
*/

add_shortcode('ws_jx_resources', 'ws_shortcode_jx_resources');

function ws_shortcode_jx_resources()
{

    global $post;

    if (!$post) {
        return '';
    }

    $resources = ws_get_jx_resources($post->ID);

    if (!$resources) {
        return '';
    }

    $content = apply_filters('the_content', $resources->post_content);

    return ws_render_section(
        'Resources',
        $content
    );

}