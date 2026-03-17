<?php
/**
 * cpt-jx-statutes.php
 *
 * Registers the Jurisdiction Statute Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores individual statute records for a specific U.S.
 * jurisdiction. Each statute captures granular metadata — filing
 * deadlines, enforcement agencies, misconduct categories, and
 * available remedies — enabling structured queries that were
 * previously impossible with a prose-only model.
 *
 * Each jurisdiction may have many associated statute records.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-statute (private dataset, many per jurisdiction)
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
 *         to hyphenated convention: jx-statute.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_statutes' );

function ws_register_cpt_statutes() {

    $labels = [
        'name'               => 'Statutes',
        'singular_name'      => 'Statute',
        'menu_name'          => 'Statutes',
        'add_new'            => 'Add Statute',
        'add_new_item'       => 'Add New Statute',
        'new_item'           => 'New Statute',
        'edit_item'          => 'Edit Statute',
        'view_item'          => 'View Statute',
        'all_items'          => 'All Statutes',
        'search_items'       => 'Search Statutes',
        'not_found'          => 'No statutes found',
        'not_found_in_trash' => 'No statutes found in trash',
    ];

    $args = [

        'labels' => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — rendered through the jurisdiction page assembler.
        // Not directly accessible to the public.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title: official statutory name. All structured metadata via ACF.

        'supports'   => [ 'title', 'editor', 'revisions' ],
        'taxonomies' => [ 'ws_disclosure_type', 'ws_process_type', 'ws_remedy_type', 'ws_coverage_scope', 'ws_retaliation_forms' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest' => true,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'     => 'dashicons-gavel',
        'menu_position' => 26,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and relationship field post_type references throughout ws-core.
    register_post_type( 'jx-statute', $args );
}
