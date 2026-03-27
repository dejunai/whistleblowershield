<?php
/**
 * cpt-jx-citations.php — Registers the jx-citation CPT.
 *
 * Stores case law and regulatory citations for a jurisdiction.
 * Scoped via ws_jurisdiction taxonomy term. Not publicly queryable —
 * content surfaces on jurisdiction pages via the Assembly Layer only.
 * attach_flag + ws_display_order control what appears on curated summary views.
 *
 * @package WhistleblowerShield
 * @since   2.3.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 2.3.0   Initial release.
 * 3.0.0   ws_jx_code join retired; ws_jurisdiction taxonomy used throughout.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_citation' );

function ws_register_cpt_jx_citation() {

    $labels = [
        'name'               => 'Jurisdiction Citations',
        'singular_name'      => 'Jurisdiction Citation',
        'menu_name'          => 'JX Citations',
        'add_new'            => 'Add Citation',
        'add_new_item'       => 'Add New Jurisdiction Citation',
        'edit_item'          => 'Edit Jurisdiction Citation',
        'new_item'           => 'New Jurisdiction Citation',
        'view_item'          => 'View Citation',
        'search_items'       => 'Search Citations',
        'not_found'          => 'No citations found',
        'not_found_in_trash' => 'No citations found in trash',
        'all_items'          => 'All Citations',
    ];

    $args = [

        'labels'             => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — rendered through the jurisdiction page assembler.
        // Not directly accessible to the public.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title used as the citation display label in the admin list.
        // No post editor — all content managed via ACF fields.

        'supports'            => [ 'title', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'        => true,

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'       => 'dashicons-book-alt',
        'menu_position'   => 27,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    register_post_type( 'jx-citation', $args );
}
