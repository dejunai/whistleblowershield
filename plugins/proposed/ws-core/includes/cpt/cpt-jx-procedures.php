<?php
/**
 * cpt-jx-procedures.php
 *
 * Registers the Jurisdiction Procedures Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores procedural guidance explaining how a whistleblower
 * may report misconduct or seek protection within a specific
 * U.S. jurisdiction.
 *
 * Each jurisdiction should have one associated procedures record.
 *
 * These records are not intended to be accessed directly by visitors.
 * Instead they are rendered inside the Jurisdiction page using
 * shortcodes or the query layer.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-procedures (private dataset)
 *
 * Example relationship:
 *
 *      California (jurisdiction)
 *            ↳ California Procedures (jx-procedures)
 *
 * CONTENT GUIDELINES
 * ------------------
 * Procedures should focus on practical steps a potential whistleblower
 * might take, including:
 *
 *      • Where to report wrongdoing
 *      • Relevant oversight agencies
 *      • Filing deadlines or requirements
 *      • Important procedural cautions
 *
 * Content should be written in clear plain English suitable for
 * non-lawyers seeking guidance.
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
 *         to hyphenated convention: jx-procedures.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_procedures' );

function ws_register_cpt_jx_procedures() {

    $labels = [
        'name'               => 'Jurisdiction Procedures',
        'singular_name'      => 'Jurisdiction Procedure',
        'menu_name'          => 'JX Procedures',
        'add_new'            => 'Add Procedures',
        'add_new_item'       => 'Add New Jurisdiction Procedure',
        'edit_item'          => 'Edit Jurisdiction Procedure',
        'new_item'           => 'New Jurisdiction Procedure',
        'view_item'          => 'View Procedure',
        'all_items'          => 'All Procedures',
        'search_items'       => 'Search Procedures',
        'not_found'          => 'No procedures found',
        'not_found_in_trash' => 'No procedures found in trash',
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

        'menu_icon'       => 'dashicons-list-view',
        'menu_position'   => 27,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and relationship field post_type references throughout ws-core.
    register_post_type( 'jx-procedure', $args );
}
