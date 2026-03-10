<?php
/**
 * cpt-legal-updates.php
 *
 * Registers the `ws-legal-update` Custom Post Type.
 * Powers the Legal Updates change log — a timestamped, jurisdiction-tagged
 * record of significant legal changes affecting site content.
 *
 * v1.9.0 — Renamed from `legal-update` to `ws-update`.
 * v1.9.2 — Renamed from `ws-update` to `ws-legal-update` for full clarity.
 *           Public archive slug updated to /ws-legal-update/ accordingly.
 *
 * ⚠ Migration required before deploying to production:
 *   UPDATE wp_posts SET post_type = 'ws-legal-update' WHERE post_type = 'legal-update';
 *   UPDATE wp_posts SET post_type = 'ws-legal-update' WHERE post_type = 'ws-update';
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_legal_update_cpt' );
function ws_register_legal_update_cpt() {

    $labels = [
        'name'                  => 'Legal Updates',
        'singular_name'         => 'Legal Update',
        'menu_name'             => 'Legal Updates',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Legal Update',
        'edit_item'             => 'Edit Legal Update',
        'new_item'              => 'New Legal Update',
        'view_item'             => 'View Legal Update',
        'view_items'            => 'View Legal Updates',
        'search_items'          => 'Search Legal Updates',
        'not_found'             => 'No legal updates found.',
        'not_found_in_trash'    => 'No legal updates found in trash.',
        'all_items'             => 'All Legal Updates',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'ws-legal-update', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => true,   // /ws-legal-update/ archive = full change log
        'hierarchical'       => false,
        'menu_position'      => 7,
        'menu_icon'          => 'dashicons-update',
        'supports'           => [ 'title', 'editor', 'excerpt', 'revisions', 'author' ],
    ];

    register_post_type( 'ws-legal-update', $args );
}
