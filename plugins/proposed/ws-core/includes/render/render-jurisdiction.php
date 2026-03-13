<?php
/**
 * File: render-jurisdiction.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Automatically assembles the public-facing Jurisdiction page by
 * conditionally rendering available datasets associated with a
 * jurisdiction record.
 *
 * This file intercepts WordPress content rendering for the
 * "jurisdiction" Custom Post Type and dynamically builds the page
 * structure using shortcodes.
 *
 * The goal is to eliminate the need to manually insert shortcodes
 * into jurisdiction posts. Instead, sections appear automatically
 * when their associated datasets are published.
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
 * Each dataset is stored as a separate Custom Post Type and linked
 * to the jurisdiction using ACF relationship fields defined in:
 *
 *      /includes/acf/acf-jurisdiction.php
 *
 *
 * RENDERING MODEL
 * ---------------
 *
 * When a visitor loads a jurisdiction page:
 *
 *      WordPress loads post content
 *            ↓
 *      this file intercepts via "the_content" filter
 *            ↓
 *      plugin checks for published datasets
 *            ↓
 *      plugin renders sections using shortcodes
 *
 *
 * CONDITIONAL RENDERING
 * ---------------------
 *
 * Sections are only displayed when their corresponding dataset:
 *
 *      1) exists
 *      2) is published
 *
 * Draft or unpublished datasets will never appear on the public site.
 *
 *
 * WORKFLOW BENEFIT
 * ----------------
 *
 * Editors do NOT need to manually insert shortcodes.
 *
 * Creating and publishing a dataset automatically updates the
 * jurisdiction page.
 *
 *
 * FILE RESPONSIBILITIES
 * ---------------------
 *
 * This file ONLY:
 *
 *      • detects jurisdiction pages
 *      • retrieves dataset relationships
 *      • verifies publish status
 *      • triggers shortcode rendering
 *
 * It does NOT:
 *
 *      • perform database queries
 *      • contain HTML templates
 *
 * Queries are handled in:
 *
 *      /includes/queries/query-jurisdiction.php
 *
 * Rendering is handled in:
 *
 *      /includes/shortcodes/shortcodes-jurisdiction.php
 *
 *
 * VERSION
 * -------
 * 2.1.0  Jurisdiction auto-render architecture introduced
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
Helper: Verify Published State
---------------------------------------------------------
*/

function ws_is_published($post)
{
    return ($post && $post->post_status === 'publish');
}


/*
---------------------------------------------------------
Main Jurisdiction Renderer
---------------------------------------------------------
*/

add_filter('the_content', 'ws_render_jurisdiction_content');

function ws_render_jurisdiction_content($content)
{

    /*
    Ensure this only runs on Jurisdiction pages
    */

    if (!is_singular('jurisdiction')) {
        return $content;
    }

    global $post;

    if (!$post) {
        return $content;
    }

    $output = '';


    /*
    -----------------------------------------------------
    Jurisdiction Header
    -----------------------------------------------------
    */

    $output .= do_shortcode('[ws_jx_header]');


    /*
    -----------------------------------------------------
    Summary Section
    -----------------------------------------------------
    */

    $summary = ws_get_jx_summary($post->ID);

    if (ws_is_published($summary)) {
        $output .= do_shortcode('[ws_jx_summary]');
    }


    /*
    -----------------------------------------------------
    Procedures Section
    -----------------------------------------------------
    */

    $procedures = ws_get_jx_procedures($post->ID);

    if (ws_is_published($procedures)) {
        $output .= do_shortcode('[ws_jx_procedures]');
    }


    /*
    -----------------------------------------------------
    Statutes Section
    -----------------------------------------------------
    */

    $statutes = ws_get_jx_statutes($post->ID);

    if (ws_is_published($statutes)) {
        $output .= do_shortcode('[ws_jx_statutes]');
    }


    /*
    -----------------------------------------------------
    Resources Section
    -----------------------------------------------------
    */

    $resources = ws_get_jx_resources($post->ID);

    if (ws_is_published($resources)) {
        $output .= do_shortcode('[ws_jx_resources]');
    }


    /*
    Return Fully Assembled Page
    */

    return $output;

}