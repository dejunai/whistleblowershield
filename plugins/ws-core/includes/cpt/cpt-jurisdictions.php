<?php
/**
 * cpt-jurisdictions.php — Registers the jurisdiction CPT.
 *
 * Represents one of 57 U.S. jurisdictions: 50 states, DC, 5 territories,
 * federal. Jurisdiction code is the slug of the assigned ws_jurisdiction
 * taxonomy term (e.g. 'ca', 'us') — not ws_jx_code post meta.
 *
 * @package WhistleblowerShield
 * @since   1.0.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 1.0.0   Initial release.
 * 2.1.0   ws-core refactor.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jurisdiction' );

function ws_register_cpt_jurisdiction() {

    $labels = [
        'name'               => 'Jurisdictions',
        'singular_name'      => 'Jurisdiction',
        'menu_name'          => 'Jurisdictions',
        'name_admin_bar'     => 'Jurisdiction',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Jurisdiction',
        'new_item'           => 'New Jurisdiction',
        'edit_item'          => 'Edit Jurisdiction',
        'view_item'          => 'View Jurisdiction',
        'all_items'          => 'All Jurisdictions',
        'search_items'       => 'Search Jurisdictions',
        'not_found'          => 'No jurisdictions found',
        'not_found_in_trash' => 'No jurisdictions found in trash',
    ];

    $args = [

        'labels' => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Public CPT — jurisdiction pages are the primary front-end output.

        'public'              => true,
        'show_ui'             => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'has_archive'         => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title: jurisdiction name. Editor: optional notes — primary content
        // served via ACF fields.

        'supports' => [ 'title', 'editor', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest' => true,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────

        'show_in_menu'  => true,
        'hierarchical'  => false,
        'menu_icon'     => 'dashicons-location-alt',
        'menu_position' => 27,

        // ── Rewrite ───────────────────────────────────────────────────────

        'rewrite' => [
            'slug'       => 'jurisdiction',
            'with_front' => false,
        ],

    ];

    register_post_type( 'jurisdiction', $args );
}
