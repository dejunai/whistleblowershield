<?php
/**
 * cpt-jx-resources.php
 *
 * Registers the Jurisdiction Resources Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores external resources relevant to whistleblowers
 * within a specific U.S. jurisdiction.
 *
 * Resources may include:
 *
 *      • Government oversight agencies
 *      • Reporting portals
 *      • Inspector General offices
 *      • Ethics commissions
 *      • Legal aid organizations
 *      • Whistleblower advocacy groups
 *
 * Each jurisdiction should have one associated resources record.
 *
 * These records are not intended to be accessed directly by visitors.
 * Instead they are rendered within the Jurisdiction page through
 * shortcodes or the internal query layer.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-resources (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Resources (jx-resources)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Resource entries should prioritize authoritative sources such as:
 *
 *      • official government reporting portals
 *      • inspector general offices
 *      • enforcement agencies
 *      • recognized whistleblower support organizations
 *
 * External links should be verified periodically to ensure accuracy.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. CPT slug standardized
 *         to hyphenated convention: jx-resources.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_resources' );

function ws_register_cpt_jx_resources() {

    $labels = [
        'name'               => 'Jurisdiction Resources',
        'singular_name'      => 'Jurisdiction Resource',
        'menu_name'          => 'JX Resources',
        'add_new'            => 'Add Resources',
        'add_new_item'       => 'Add New Jurisdiction Resource',
        'edit_item'          => 'Edit Jurisdiction Resource',
        'new_item'           => 'New Jurisdiction Resource',
        'view_item'          => 'View Resource',
        'all_items'          => 'All Resources',
        'search_items'       => 'Search Resources',
        'not_found'          => 'No resources found',
        'not_found_in_trash' => 'No resources found in trash',
    ];

    $args = [

        'labels'              => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — rendered through the parent jurisdiction page.
        // Not directly accessible to the public.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,

        // ── Editor ────────────────────────────────────────────────────────

        'supports'            => [ 'title', 'editor', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'        => true,

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'       => 'dashicons-admin-links',
        'menu_position'   => 29,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and relationship field post_type references throughout ws-core.
    register_post_type( 'jx-resource', $args );
}
