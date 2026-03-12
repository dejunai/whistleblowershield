<?php
/**
 * cpt-legal-updates.php
 *
 * Registers the `ws-legal-update` Custom Post Type.
 * Powers the Legal Updates change log — a timestamped, jurisdiction-tagged
 * record of significant legal changes affecting site content.
 *
 * v1.9.0 — Renamed from `legal-update` to `ws-update`.
 * v1.9.2 — Renamed from `ws-update` to `ws-legal-update` for full clarity.
 *           Public archive slug updated to /ws-legal-update/ accordingly.
 * v2.0.0 — Replaced raw SQL migration comment with a proper one-time DB
 *           migration routine (ws_migrate_ws_update_cpt) that follows the
 *           same guarded admin_init pattern used by the taxonomy cleanup
 *           and Meta Box cleanup routines elsewhere in this plugin.
 *           Safe to deploy to any environment — the option flag prevents
 *           re-runs once the migration has completed successfully.
 */

defined( 'ABSPATH' ) || exit;

// ── Register CPT ─────────────────────────────────────────────────────────────

add_action( 'init', 'ws_register_legal_update_cpt' );
function ws_register_legal_update_cpt() {

    $labels = [
        'name'               => 'Legal Updates',
        'singular_name'      => 'Legal Update',
        'menu_name'          => 'Legal Updates',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Legal Update',
        'edit_item'          => 'Edit Legal Update',
        'new_item'           => 'New Legal Update',
        'view_item'          => 'View Legal Update',
        'view_items'         => 'View Legal Updates',
        'search_items'       => 'Search Legal Updates',
        'not_found'          => 'No legal updates found.',
        'not_found_in_trash' => 'No legal updates found in trash.',
        'all_items'          => 'All Legal Updates',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'ws-legal-update', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => true,   // /ws-legal-update/ archive = full change log
        'hierarchical'       => false,
        'menu_position'      => 7,
        'menu_icon'          => 'dashicons-update',
        'supports'           => [ 'title', 'editor', 'excerpt', 'revisions', 'author' ],
    ];

    register_post_type( 'ws-legal-update', $args );
}

// ── One-time migration: rename legacy CPT slugs in wp_posts ──────────────────
//
// Earlier versions of this plugin used two different post type slugs for what
// is now `ws-legal-update`:
//
//   v1.0–v1.8:  `legal-update`
//   v1.9.0–v1.9.1: `ws-update`
//
// This routine fires once on admin_init after v2.0.0 is deployed. It updates
// any wp_posts rows where post_type is still `legal-update` or `ws-update`
// to the current canonical slug `ws-legal-update`.
//
// A completion flag (ws_migrate_ws_update_cpt_v1) prevents re-runs.
// The routine is safe to deploy to any environment including production —
// if no legacy rows exist, it simply sets the flag and exits cleanly.

add_action( 'admin_init', 'ws_migrate_ws_update_cpt' );
function ws_migrate_ws_update_cpt() {

    if ( get_option( 'ws_migrate_ws_update_cpt_v1' ) ) {
        return; // Already ran — do nothing
    }

    global $wpdb;

    $legacy_slugs  = [ 'legal-update', 'ws-update' ];
    $total_updated = 0;
    $results       = [];

    foreach ( $legacy_slugs as $old_slug ) {
        $updated = $wpdb->update(
            $wpdb->posts,
            [ 'post_type' => 'ws-legal-update' ],
            [ 'post_type' => $old_slug ],
            [ '%s' ],
            [ '%s' ]
        );
        if ( $updated ) {
            $results[ $old_slug ] = $updated;
            $total_updated       += $updated;
        }
    }

    update_option( 'ws_migrate_ws_update_cpt_v1', true );

    if ( $total_updated > 0 ) {
        // Clean up rewrite cache so the new slug takes effect immediately
        flush_rewrite_rules();

        add_action( 'admin_notices', function() use ( $total_updated, $results ) {
            $detail = implode( ', ', array_map(
                fn( $k, $v ) => "<code>{$k}</code> ({$v} post" . ( $v !== 1 ? 's' : '' ) . ')',
                array_keys( $results ),
                $results
            ) );
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo "<strong>WhistleblowerShield Core:</strong> Migrated {$total_updated} post(s) to the ";
            echo "<code>ws-legal-update</code> post type: {$detail}.";
            echo '</p></div>';
        } );
    }
}
