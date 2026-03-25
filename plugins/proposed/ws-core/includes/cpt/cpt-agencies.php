<?php
/**
 * cpt-agencies.php
 *
 * Registers the Whistleblower Shield Agencies Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT serves as the central directory for government and non-government
 * agencies responsible for whistleblower intake, oversight, and protection.
 *
 * ARCHITECTURE
 * ------------
 * Unlike citations, agencies are a top-level directory. They are linked to
 * jurisdictions via the ws_jx_code array (USPS codes) and classified by
 * misconduct types via the ws_disclosure_cat taxonomy.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_agencies' );

function ws_register_cpt_agencies() {

    $labels = [
        'name'               => 'Agencies',
        'singular_name'      => 'Agency',
        'menu_name'          => 'Agencies',
        'name_admin_bar'     => 'Agency',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Agency',
        'edit_item'          => 'Edit Agency',
        'new_item'           => 'New Agency',
        'view_item'          => 'View Agency',
        'search_items'       => 'Search Agencies',
        'not_found'          => 'No agencies found',
        'not_found_in_trash' => 'No agencies found in trash',
        'all_items'          => 'All Agencies',
    ];

    $args = [
        'labels'             => $labels,

        // -- Visibility ----------------------------------------------------
        // Agencies are a public directory for users to find help.
        
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'has_archive'         => 'agencies', // Creates whistleblowershield.org/agencies/
        'query_var'           => true,

        // -- Editor --------------------------------------------------------
        // Title: Agency Name
        // Editor: General description/overview of the agency
        
        'supports'            => [ 'title', 'editor', 'thumbnail', 'revisions' ],
        'rewrite'             => [ 'slug' => 'agency', 'with_front' => false ],
        'capability_type'     => 'post',

        // -- REST ----------------------------------------------------------
        // Enabled for Block Editor and ACF AJAX support.
        
        'show_in_rest'        => true,

        // -- Admin Menu ----------------------------------------------------
        
        'menu_icon'           => 'dashicons-building', // Appropriate icon for government/offices
        'menu_position'       => 28, // Placed immediately after Citations (27)

        // -- Taxonomies ----------------------------------------------------
        
        'taxonomies'          => [ 'ws_disclosure_type' ],
    ];

    register_post_type( 'ws-agency', $args );
}

// Admin columns for ws-agency are registered in admin-columns.php (ws_add_agency_columns /
// ws_render_agency_column). Do not register manage_ws-agency_posts_columns or
// manage_ws-agency_posts_custom_column hooks here — duplicate registrations cause
// conflicting column sets. All CPT column logic lives in admin-columns.php.