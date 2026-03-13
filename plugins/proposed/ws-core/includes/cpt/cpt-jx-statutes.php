<?php
/**
 * cpt-jx-statutes.php
 *
 * Registers the Jurisdiction Statutes Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores statutory citations and legal references related to
 * whistleblower protections within a specific U.S. jurisdiction.
 *
 * Each jurisdiction should have one associated statutes record.
 *
 * These records are not intended to be accessed directly by visitors.
 * Instead they are rendered inside the Jurisdiction page using
 * shortcodes or the query layer.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-statutes (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Statutes (jx-statutes)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Statutes entries should reference relevant laws including:
 *
 *      • Whistleblower protection statutes
 *      • Anti-retaliation provisions
 *      • Relevant criminal or administrative codes
 *      • Cross-referenced federal statutes where applicable
 *
 * The goal is to provide clear references and citations without
 * attempting to reproduce entire statutory texts.
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
 *         to hyphenated convention: jx-statutes.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_statutes' );

function ws_register_cpt_jx_statutes() {

    $labels = [
        'name'               => 'Jurisdiction Statutes',
        'singular_name'      => 'Jurisdiction Statutes',
        'menu_name'          => 'JX Statutes',
        'add_new'            => 'Add Statutes',
        'add_new_item'       => 'Add New Jurisdiction Statutes',
        'edit_item'          => 'Edit Jurisdiction Statutes',
        'new_item'           => 'New Jurisdiction Statutes',
        'view_item'          => 'View Statutes',
        'search_items'       => 'Search Statutes',
        'not_found'          => 'No statutes found',
        'not_found_in_trash' => 'No statutes found in trash',
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

        'menu_icon'           => 'dashicons-book',
        'menu_position'       => 28,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and relationship field post_type references throughout ws-core.
    register_post_type( 'jx-statutes', $args );
}
