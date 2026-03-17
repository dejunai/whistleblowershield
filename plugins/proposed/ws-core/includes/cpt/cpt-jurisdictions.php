<?php
/**
 * File: cpt-jurisdiction.php
 *
 * Registers the Jurisdiction Core CPT.
 *
 * This CPT represents the canonical record for each U.S. jurisdiction
 * supported by the WhistleblowerShield legal archive.
 *
 * Supported jurisdictions:
 * - 50 U.S. states
 * - Federal government (US)
 * - District of Columbia (DC)
 * - U.S. Territories:
 *     Puerto Rico (PR)
 *     Guam (GU)
 *     U.S. Virgin Islands (VI)
 *     American Samoa (AS)
 *     Northern Mariana Islands (MP)
 *
 * Each jurisdiction record acts as the parent reference for:
 *
 *   jx-summary
 *   jx-statute
 *   
 *   
 *   ws-legal-update
 *
 * Internal Identifier:
 *   ws_jx_code (2-letter USPS style code)
 *
 * Examples:
 *   CA = California
 *   TX = Texas
 *   NY = New York
 *   US = Federal
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
 * 2.1.0  Refactored for ws-core architecture.
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
        'menu_position' => 25,

        // ── Rewrite ───────────────────────────────────────────────────────

        'rewrite' => [
            'slug'       => 'jurisdiction',
            'with_front' => false,
        ],

    ];

    register_post_type( 'jurisdiction', $args );
}
