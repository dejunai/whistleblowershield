<?php
/**
 * cpt-ws-legal-update.php
 *
 * Registers the Legal Update Custom Post Type.
 *
 * PURPOSE
 * -------
 * The Legal Update dataset records significant developments
 * in whistleblower law across U.S. jurisdictions.
 *
 * These records allow the project to track:
 *
 *      • statutory amendments
 *      • new legislation
 *      • major court rulings
 *      • regulatory changes
 *      • enforcement policy changes
 *
 * Each update may be linked to one or more jurisdictions
 * through ACF relationship fields.
 *
 * VISIBILITY
 * ----------
 * Legal Updates are currently:
 *
 *      • visible in the WordPress admin interface
 *      • not publicly accessible on the front-end
 *
 * This allows updates to be archived internally before
 * future publication or indexing features are developed.
 *
 * FUTURE USE
 * ----------
 * Potential front-end uses include:
 *
 *      • jurisdiction update timelines
 *      • recent law change feeds
 *      • journalist research tools
 *      • legal history tracking
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
 * 1.9.0  Renamed from legal-update to ws-legal-update.
 * 2.1.0  Refactored for ws-core architecture. CPT slug standardized
 *         to hyphenated convention: ws-legal-update. File renamed
 *         from cpt-legal-update.php to cpt-ws-legal-update.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_legal_update' );

function ws_register_cpt_legal_update() {

    $labels = [
        'name'               => 'Legal Updates',
        'singular_name'      => 'Legal Update',
        'menu_name'          => 'Legal Updates',
        'add_new'            => 'Add Legal Update',
        'add_new_item'       => 'Add New Legal Update',
        'edit_item'          => 'Edit Legal Update',
        'new_item'           => 'New Legal Update',
        'view_item'          => 'View Legal Update',
        'search_items'       => 'Search Legal Updates',
        'not_found'          => 'No legal updates found',
        'not_found_in_trash' => 'No legal updates found in Trash',
    ];

    $args = [

        'labels'          => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Internal records only — not publicly accessible.
        // Intended for admin and editorial use until front-end
        // publication features are defined.

        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'has_archive'     => false,
        'rewrite'         => false,

        // ── Editor ────────────────────────────────────────────────────────

        'supports'        => [ 'title', 'editor', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'    => true,

        // ── Capabilities ─────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'       => 'dashicons-media-document',
        'menu_position'   => 30,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and audit trail CPT list in admin-audit-trail.php.
    register_post_type( 'ws-legal-update', $args );
}
