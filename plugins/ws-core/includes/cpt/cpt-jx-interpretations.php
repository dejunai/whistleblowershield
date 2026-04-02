<?php
/**
 * cpt-jx-interpretations.php — Registers the jx-interpretation CPT.
 *
 * Stores court interpretations of whistleblower statutes. Each record
 * captures one case — citation, court, holding, favorable flag.
 * Linked to parent statute via ws_jx_interp_statute_id (post_object).
 * Scoped via ws_jurisdiction taxonomy term.
 *
 * Created via "Add New Interpretation" button in admin-interpretation-metabox.php
 * on the jx-statute edit screen.
 *
 * @package WhistleblowerShield
 * @since   2.4.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 2.4.0   Initial release.
 * 2.4.1   menu_position corrected from 28 to 29.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_interpretation' );

function ws_register_cpt_jx_interpretation() {

    $labels = [
        'name'               => 'Statute Interpretations',
        'singular_name'      => 'Statute Interpretation',
        'menu_name'          => 'JX Interpretations',
        'add_new'            => 'Add Interpretation',
        'add_new_item'       => 'Add New Statute Interpretation',
        'edit_item'          => 'Edit Statute Interpretation',
        'new_item'           => 'New Statute Interpretation',
        'view_item'          => 'View Interpretation',
        'search_items'       => 'Search Interpretations',
        'not_found'          => 'No interpretations found',
        'not_found_in_trash' => 'No interpretations found in trash',
        'all_items'          => 'All Interpretations',
    ];

    $args = [

        'labels'             => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — surfaced via the statute edit screen meta box
        // and the public jurisdiction page render layer.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title used as the case name in the admin list.
        // No post editor — all content managed via ACF fields.

        'supports'            => [ 'title', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'        => true,

        // ── Admin Menu ────────────────────────────────────────────────────
        // Citations 27 → Agencies 28 → Interpretations 29 → Assist Orgs 30

        'menu_icon'       => 'dashicons-hammer',
        'menu_position'   => 34,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    register_post_type( 'jx-interpretation', $args );
}
