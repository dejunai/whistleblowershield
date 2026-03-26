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
        'all_items'          => 'All Legal Updates',
    ];

    $args = [

        'labels'          => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Internal records only — not publicly accessible.
        // Intended for admin and editorial use until front-end
        // publication features are defined.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,

        // ── Editor ────────────────────────────────────────────────────────

        'supports'        => [ 'title', 'editor', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'    => true,

        // ── Capabilities ─────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────
        // Position 25: before the content CPT directory block (Citations 27,
        // Agencies 28, Interpretations 29, Assist Orgs 30). Legal Updates is
        // an editorial/changelog tool — positioned separately from the public
        // directory CPTs to avoid the menu_position 30 collision with
        // ws-assist-org (cpt-assist-orgs.php).

        'menu_icon'       => 'dashicons-media-document',
        'menu_position'   => 25,

    ];

    // Slug uses hyphen convention — must match ACF location rules
    // and audit trail CPT list in admin-audit-trail.php.
    register_post_type( 'ws-legal-update', $args );
}

// ── Deletion Lock: Legal Updates are immutable changelog records ────────────
//
// Legal updates must never be deleted (including by administrators). If a
// record must be removed from public render, use the "hide from public log"
// flag in the Legal Update metadata.

add_filter( 'pre_trash_post', 'ws_block_legal_update_trash', 10, 3 );
add_filter( 'pre_delete_post', 'ws_block_legal_update_delete', 10, 3 );

/**
 * Blocks trash attempts for ws-legal-update posts.
 *
 * @param  mixed    $trash            Short-circuit value from prior filters.
 * @param  WP_Post  $post             Post object being trashed.
 * @param  string   $previous_status  Prior post status before trash.
 * @return mixed
 */
function ws_block_legal_update_trash( $trash, $post, $previous_status ) {
    if ( $post instanceof WP_Post && $post->post_type === 'ws-legal-update' ) {
        ws_queue_legal_update_delete_block_notice();
        return false;
    }
    return $trash;
}

/**
 * Blocks permanent delete attempts for ws-legal-update posts.
 *
 * @param  mixed    $delete       Short-circuit value from prior filters.
 * @param  WP_Post  $post         Post object being deleted.
 * @param  bool     $force_delete True when bypassing trash.
 * @return mixed
 */
function ws_block_legal_update_delete( $delete, $post, $force_delete ) {
    if ( $post instanceof WP_Post && $post->post_type === 'ws-legal-update' ) {
        ws_queue_legal_update_delete_block_notice();
        return false;
    }
    return $delete;
}

/**
 * Stores a one-time admin notice for the current user after a blocked delete.
 *
 * @return void
 */
function ws_queue_legal_update_delete_block_notice() {
    if ( ! is_admin() || ! is_user_logged_in() ) {
        return;
    }
    set_transient( 'ws_legal_update_delete_blocked_' . get_current_user_id(), 1, 2 * MINUTE_IN_SECONDS );
}

// Remove delete/trash affordances from Legal Updates list rows.
add_filter( 'post_row_actions', function( $actions, $post ) {
    if ( $post instanceof WP_Post && $post->post_type === 'ws-legal-update' ) {
        unset( $actions['trash'], $actions['delete'] );
    }
    return $actions;
}, 10, 2 );

// Remove bulk delete/trash actions on the Legal Updates list table.
add_filter( 'bulk_actions-edit-ws-legal-update', function( $actions ) {
    unset( $actions['trash'], $actions['delete'] );
    return $actions;
} );

// If a blocked delete/trash attempt occurs, show a clear admin notice.
add_action( 'admin_notices', function() {
    if ( ! is_user_logged_in() ) {
        return;
    }
    $key = 'ws_legal_update_delete_blocked_' . get_current_user_id();
    if ( ! get_transient( $key ) ) {
        return;
    }
    delete_transient( $key );
    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo esc_html__( 'Legal Updates are a permanent sitewide changelog and cannot be deleted. If one should be hidden from public output, ask an administrator to enable the "Hide from Public Change Log" flag.', 'ws-core' );
    echo '</p></div>';
} );
