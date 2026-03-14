<?php
/**
 * cpt-statutes.php
 * Registers the individual Statute Custom Post Type.
 */

add_action( 'init', 'ws_register_cpt_statutes' );

function ws_register_cpt_statutes() {
    $labels = [
        'name'               => 'Statutes',
        'singular_name'      => 'Statute',
        'menu_name'          => 'Statutes',
        'add_new'            => 'Add New Statute',
        'edit_item'          => 'Edit Statute',
        'search_items'       => 'Search Statutes',
        'all_items'          => 'All Statutes',
    ];

    $args = [
        'labels'              => $labels,
        'public'              => false, // Private dataset
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'supports'            => [ 'title', 'editor', 'revisions' ],
        'menu_icon'           => 'dashicons-gavel',
        'menu_position'       => 26, 
    ];

    register_post_type( 'ws-statute', $args );
}