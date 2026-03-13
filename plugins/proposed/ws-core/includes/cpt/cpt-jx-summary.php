<?php
/**
 * File: cpt-jx-summary.php
 *
 * Registers the Jurisdiction Summary Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores the plain-English legal summary explaining
 * whistleblower protections within a specific U.S. jurisdiction.
 *
 * Each jurisdiction should have exactly one associated summary record.
 *
 * The summary is not intended to be accessed directly by the public.
 * Instead it is rendered through the Jurisdiction page using
 * shortcodes or query layer functions.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx_summary (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Summary (jx_summary)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Summaries should be written in plain English and designed
 * for readers who may not have legal training.
 *
 * The purpose is to provide:
 *
 *      • an overview of whistleblower protections
 *      • key legal principles
 *      • important warnings or limitations
 *      • guidance toward official resources
 *
 * The summary should not attempt to replicate statutory text.
 *
 * VERSION
 * -------
 * 2.1.0  Refactored for ws-core architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'ws_register_cpt_jx_summary');

function ws_register_cpt_jx_summary() {

    $labels = array(

        'name'               => 'Jurisdiction Summaries',
        'singular_name'      => 'Jurisdiction Summary',
        'menu_name'          => 'JX Summaries',

        'add_new'            => 'Add Summary',
        'add_new_item'       => 'Add New Jurisdiction Summary',
        'edit_item'          => 'Edit Jurisdiction Summary',
        'new_item'           => 'New Jurisdiction Summary',
        'view_item'          => 'View Summary',
        'search_items'       => 'Search Summaries',
        'not_found'          => 'No summaries found',
        'not_found_in_trash' => 'No summaries found in trash'

    );

    $args = array(

        'labels' => $labels,

        /*
        ---------------------------------------------------------
        Visibility
        ---------------------------------------------------------
        */

        'public' => false,

        'show_ui' => true,

        'show_in_menu' => true,

        'publicly_queryable' => false,

        'exclude_from_search' => true,

        'has_archive' => false,

        /*
        ---------------------------------------------------------
        Editor
        ---------------------------------------------------------
        */

        'supports' => array(
            'title',
            'editor',
            'revisions'
        ),

        /*
        ---------------------------------------------------------
        REST Support
        ---------------------------------------------------------
        */

        'show_in_rest' => true,

        /*
        ---------------------------------------------------------
        Menu Placement
        ---------------------------------------------------------
        */

        'menu_icon' => 'dashicons-media-text',

        'menu_position' => 26

    );

    register_post_type('jx_summary', $args);

}