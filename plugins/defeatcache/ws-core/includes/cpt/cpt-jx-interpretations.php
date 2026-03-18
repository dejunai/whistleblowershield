<?php
/**
 * cpt-jx-interpretations.php
 *
 * Registers the Jurisdiction Interpretation Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores federal court interpretations of whistleblower statutes.
 * Each record captures a single case — its citation, the court that decided
 * it, the holding, and whether the ruling is favorable to whistleblowers.
 *
 * ARCHITECTURE
 * ------------
 * jx-statute (federal statute, ws_jx_code = US)
 *      └── jx-interpretation (case record, many per statute)
 *
 * Interpretations are linked to their parent statute via ws_statute_id
 * (post_object → jx-statute) and carry ws_jx_code = 'US' to slot into
 * the standard jurisdiction query pattern.
 *
 * WORKFLOW
 * --------
 * New interpretations are created via a "Add New Interpretation" button
 * in a meta box on the jx-statute edit screen (admin-interpretation-metabox.php).
 * The button opens post-new.php?post_type=jx-interpretation&statute_id={ID}
 * in a new tab. Pre-population is handled by the acf/load_value filter in
 * acf-jx-interpretations.php and the ws_jx_code URL filter in admin-hooks.php.
 *
 * MENU POSITION
 * -------------
 * Citations 27 → Agencies 28 → Interpretations 29 → Assist Orgs 30
 *
 * @package    WhistleblowerShield
 * @since      2.4.0
 * @author     Dejunai
 *
 * VERSION
 * -------
 * 2.4.0  Initial release.
 * 2.4.1  Bug #10 fix: menu_position corrected from 28 to 29 to resolve
 *         conflict with cpt-agencies.php which also claimed position 28.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_interpretation' );

function ws_register_cpt_jx_interpretation() {

    $labels = [
        'name'               => 'Statute Interpretations',
        'singular_name'      => 'Statute Interpretation',
        'menu_name'          => 'Interpretations',
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
        'menu_position'   => 29,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    register_post_type( 'jx-interpretation', $args );
}
