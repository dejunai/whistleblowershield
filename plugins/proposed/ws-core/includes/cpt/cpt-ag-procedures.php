<?php
/**
 * cpt-ag-procedures.php
 *
 * Registers the Agency Filing Procedures Custom Post Type.
 *
 * PURPOSE
 * -------
 * Stores structured filing procedures for whistleblower agencies. Each
 * procedure represents a distinct intake path at a parent agency —
 * differentiated by procedure type (disclosure vs. retaliation), disclosure
 * category, filing deadline, identity policy, and step-by-step guidance.
 *
 * ARCHITECTURE
 * ------------
 * Procedures are child records of ws-agency, linked via the ws_proc_agency_id
 * ACF post_object field (acf-ag-procedures.php). A single agency may have
 * multiple procedures covering different statutes, disclosure categories, or
 * entry points.
 *
 * PHASES
 * ------
 * Phase 1 (3.9.0): CPT + ACF structure, admin columns, agency nav box.
 * Phase 2 (3.9.0): Query layer (query-agencies.php) + front-end render (render-agency.php).
 * Phase 3: Statute cross-reference (ws_proc_statute_ids relationship field).
 * Phase 4: Seeder (matrix-ag-procedures.php).
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_ag_procedures' );

function ws_register_cpt_ag_procedures() {

    $labels = [
        'name'               => 'Procedures',
        'singular_name'      => 'Procedure',
        'menu_name'          => 'Procedures',
        'name_admin_bar'     => 'Procedure',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Procedure',
        'edit_item'          => 'Edit Procedure',
        'new_item'           => 'New Procedure',
        'view_item'          => 'View Procedure',
        'search_items'       => 'Search Procedures',
        'not_found'          => 'No procedures found',
        'not_found_in_trash' => 'No procedures found in trash',
        'all_items'          => 'All Procedures',
    ];

    $args = [
        'labels'             => $labels,

        // -- Visibility ----------------------------------------------------

        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false, // Phase 2: individual procedure posts are publicly accessible.
        'has_archive'         => false,
        'query_var'           => true,

        // -- Editor --------------------------------------------------------
        // No editor — all content is stored in ACF fields.

        'supports'            => [ 'title', 'revisions' ],
        'rewrite'             => [ 'slug' => 'procedure', 'with_front' => false ],
        'capability_type'     => 'post',

        // -- REST ----------------------------------------------------------
        // Enabled for Block Editor and ACF AJAX support.

        'show_in_rest'        => true,

        // -- Admin Menu ----------------------------------------------------

        'menu_icon'           => 'dashicons-clipboard',
        'menu_position'       => 29, // Placed immediately after ws-agency at 28.

        // -- Taxonomies ----------------------------------------------------

        'taxonomies'          => [ 'ws_disclosure_type' ],
    ];

    register_post_type( 'ws-ag-procedure', $args );
}

// Admin columns for ws-ag-procedure are registered in admin-columns.php. Do not register
// manage_ws-ag-procedure_posts_columns or manage_ws-ag-procedure_posts_custom_column hooks
// here — duplicate registrations cause conflicting column sets. All CPT column logic lives
// in admin-columns.php.
