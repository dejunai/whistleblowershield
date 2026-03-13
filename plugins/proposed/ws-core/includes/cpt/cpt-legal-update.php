<?php
/**
 * File: cpt-legal-update.php
 *
 * Registers the Legal Update Custom Post Type.
 *
 * PURPOSE
 * -------
 * The Legal Update dataset records significant developments
 * in whistleblower law across U.S. jurisdictions.
 *
 * These records allow the project to track:
 *
 * • statutory amendments
 * • new legislation
 * • major court rulings
 * • regulatory changes
 * • enforcement policy changes
 *
 * Each update may be linked to one or more jurisdictions
 * through ACF relationship fields.
 *
 *
 * VISIBILITY
 * ----------
 *
 * Legal Updates are currently:
 *
 * • visible in the WordPress admin interface
 * • not publicly accessible on the front-end
 *
 * This allows updates to be archived internally before
 * future publication or indexing features are developed.
 *
 *
 * FUTURE USE
 * ----------
 *
 * Potential front-end uses include:
 *
 * • jurisdiction update timelines
 * • recent law change feeds
 * • journalist research tools
 * • legal history tracking
 *
 *
 * VERSION
 * -------
 * 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'ws_register_cpt_legal_update');

function ws_register_cpt_legal_update() {

    $labels = array(
        'name'               => 'Legal Updates',
        'singular_name'      => 'Legal Update',
        'menu_name'          => 'Legal Updates',
        'add_new'            => 'Add Legal Update',
        'add_new_item'       => 'Add New Legal Update',
        'edit_item'          => 'Edit Legal Update',
        'new_item'           => 'New Legal Update',
        'view_item'          => 'View Legal Update',
        'search_items'       => 'Search Legal Updates',
        'not_found'          => 'No legal updates found',
        'not_found_in_trash' => 'No legal updates found in Trash'
    );

    $args = array(

        'labels' => $labels,

        'public' => false,

        'show_ui' => true,

        'show_in_menu' => true,

        'menu_position' => 25,

        'menu_icon' => 'dashicons-media-document',

        'supports' => array(
            'title',
            'editor',
            'revisions'
        ),

        'has_archive' => false,

        'rewrite' => false,

        'show_in_rest' => true,

        'capability_type' => 'post'
    );

    register_post_type('legal_update', $args);
}