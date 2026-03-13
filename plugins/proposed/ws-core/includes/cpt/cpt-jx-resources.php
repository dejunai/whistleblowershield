<?php
/**
 * File: cpt-jx-resources.php
 *
 * Registers the Jurisdiction Resources Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores external resources relevant to whistleblowers
 * within a specific U.S. jurisdiction.
 *
 * Resources may include:
 *
 *      • Government oversight agencies
 *      • Reporting portals
 *      • Inspector General offices
 *      • Ethics commissions
 *      • Legal aid organizations
 *      • Whistleblower advocacy groups
 *
 * Each jurisdiction should have one associated resources record.
 *
 * These records are not intended to be accessed directly by visitors.
 * Instead they are rendered within the Jurisdiction page through
 * shortcodes or the internal query layer.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx_resources (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Resources (jx_resources)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Resource entries should prioritize authoritative sources such as:
 *
 *      • official government reporting portals
 *      • inspector general offices
 *      • enforcement agencies
 *      • recognized whistleblower support organizations
 *
 * External links should be verified periodically to ensure accuracy.
 *
 * VERSION
 * -------
 * 2.1.0  Refactored for ws-core architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'ws_register_cpt_jx_resources');

function ws_register_cpt_jx_resources() {

    $labels = array(

        'name'               => 'Jurisdiction Resources',
        'singular_name'      => 'Jurisdiction Resources',
        'menu_name'          => 'JX Resources',

        'add_new'            => 'Add Resources',
        'add_new_item'       => 'Add New Jurisdiction Resources',
        'edit_item'          => 'Edit Jurisdiction Resources',
        'new_item'           => 'New Jurisdiction Resources',
        'view_item'          => 'View Resources',
        'search_items'       => 'Search Resources',
        'not_found'          => 'No resources found',
        'not_found_in_trash' => 'No resources found in trash'

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

        'menu_icon' => 'dashicons-admin-links',

        'menu_position' => 29

    );

    register_post_type('jx_resources', $args);

}