<?php
/**
 * cpt-assist-orgs.php — Registers the ws-assist-org CPT.
 *
 * Directory of organizations that help whistleblowers: legal aid clinics,
 * nonprofits, advocacy groups, law firms, government ombudsmen.
 * Distinct from ws-agency (enforcement bodies). Agencies receive reports.
 * Assist organizations help the whistleblower navigate the process.
 *
 * Scoped via ws_jurisdiction taxonomy. Nationwide orgs carry the 'us' term
 * and ws_aorg_serves_nationwide = 1.
 *
 * @package WhistleblowerShield
 * @since   1.0.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 1.0.0   Initial release.
 * 1.0.1   menu_position corrected from 31 to 30.
 * 3.7.0   ws_employment_sector taxonomy added.
 *)

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_assist_org' );

function ws_register_cpt_assist_org() {

    $labels = [
        'name'               => 'Assistance Organizations',
        'singular_name'      => 'Assistance Organization',
        'menu_name'          => 'Assist Orgs',
        'name_admin_bar'     => 'Assistance Org',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Assistance Organization',
        'new_item'           => 'New Assistance Organization',
        'edit_item'          => 'Edit Assistance Organization',
        'view_item'          => 'View Assistance Organization',
        'all_items'          => 'All Assistance Organizations',
        'search_items'       => 'Search Assistance Organizations',
        'not_found'          => 'No assistance organizations found',
        'not_found_in_trash' => 'No assistance organizations found in trash',
    ];

    $args = [

        'labels' => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Public directory — each organization has its own findable page
        // and the archive serves as the full directory listing.

        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'has_archive'         => 'assistance-organizations',
        'query_var'           => true,

        // ── Editor ────────────────────────────────────────────────────────
        // Title: organization name.
        // Editor: extended description visible on the public directory page.
        // Thumbnail: organization logo or representative image.

        'supports' => [ 'title', 'editor', 'thumbnail', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest' => true,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────
        // Citations 27 → Agencies 28 → Interpretations 29 → Assist Orgs 30

        'menu_icon'     => 'dashicons-groups',
        'menu_position' => 30,

        // ── Rewrite ───────────────────────────────────────────────────────

        'rewrite' => [
            'slug'       => 'assistance-organization',
            'with_front' => false,
        ],

        // ── Taxonomies ────────────────────────────────────────────────────

        'taxonomies' => [ 'ws_disclosure_type' ],

    ];

    register_post_type( 'ws-assist-org', $args );
}
