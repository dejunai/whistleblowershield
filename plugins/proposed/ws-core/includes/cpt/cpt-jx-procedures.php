<?php
/**
 * File: cpt-jx-procedures.php
 *
 * Registers the Jurisdiction Procedures Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores procedural guidance explaining how a whistleblower
 * may report misconduct or seek protection within a specific
 * U.S. jurisdiction.
 *
 * Each jurisdiction should have one associated procedures record.
 *
 * These records are not intended to be accessed directly by visitors.
 * Instead they are rendered inside the Jurisdiction page using
 * shortcodes or the query layer.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx_procedures (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Procedures (jx_procedures)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Procedures should focus on practical steps a potential whistleblower
 * might take, including:
 *
 *      • Where to report wrongdoing
 *      • Relevant oversight agencies
 *      • Filing deadlines or requirements
 *      • Important procedural cautions
 *
 * Content should be written in clear plain English suitable for
 * non-lawyers seeking guidance.
 *
 * VERSION
 * -------
 * 2.1.0  Refactored for ws-core architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'ws_register_cpt_jx_procedures');

function ws_register_cpt_jx_procedures() {

    $labels = array(

        'name'               => 'Jurisdiction Procedures',
        'singular_name'      => 'Jurisdiction Procedures',
        'menu_name'          => 'JX Procedures',

        'add_new'            => 'Add Procedures',
        'add_new_item'       => 'Add New Jurisdiction Procedures',
        'edit_item'          => 'Edit Jurisdiction Procedures',
        'new_item'           => 'New Jurisdiction Procedures',
        'view_item'          => 'View Procedures',
        'search_items'       => 'Search Procedures',
        'not_found'          => 'No procedures found',
        'not_found_in_trash' => 'No procedures found in trash'

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
        Admin Menu
        ---------------------------------------------------------
        */

        'menu_icon' => 'dashicons-list-view',

        'menu_position' => 27

    );

    register_post_type('jx_procedures', $args);

}