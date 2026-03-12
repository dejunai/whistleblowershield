<?php
/**
 * cpt-jurisdiction.php
 *
 * Registers the `jurisdiction` Custom Post Type.
 *
 * v1.9.0 change: Removed the `jurisdiction-type` taxonomy entirely.
 * It was registered but never functionally used — jurisdiction type
 * classification is handled exclusively by the `ws_jurisdiction_type`
 * ACF select field on the `jurisdiction` CPT. The taxonomy was dead weight.
 * A one-time cleanup routine (ws_cleanup_jurisdiction_type_taxonomy) removes
 * the orphaned terms and term relationships from the database on the next
 * admin load after this version is deployed.
 */

defined( 'ABSPATH' ) || exit;

// ── Register CPT ─────────────────────────────────────────────────────────────

add_action( 'init', 'ws_register_jurisdiction_cpt' );
function ws_register_jurisdiction_cpt() {

    $labels = [
        'name'                  => 'Jurisdictions',
        'singular_name'         => 'Jurisdiction',
        'menu_name'             => 'Jurisdictions',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Jurisdiction',
        'edit_item'             => 'Edit Jurisdiction',
        'new_item'              => 'New Jurisdiction',
        'view_item'             => 'View Jurisdiction',
        'view_items'            => 'View Jurisdictions',
        'search_items'          => 'Search Jurisdictions',
        'not_found'             => 'No jurisdictions found.',
        'not_found_in_trash'    => 'No jurisdictions found in trash.',
        'all_items'             => 'All Jurisdictions',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'jurisdiction', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-shield',
        'supports'           => [ 'title', 'editor', 'thumbnail', 'revisions' ],
    ];

    register_post_type( 'jurisdiction', $args );
}

/**
 * BRIDGE: Migration routine to copy legacy taxonomy terms to ACF field.
 * Run during the same admin_init pass as the cleanup.
 */
add_action( 'admin_init', 'ws_bridge_taxonomy_to_acf', 5 ); // Priority 5 runs BEFORE cleanup
function ws_bridge_taxonomy_to_acf() {
    if ( get_option( 'ws_jx_type_taxonomy_cleanup' ) ) {
        return; // Don't run if cleanup is already done
    }

    $jurisdictions = get_posts([
        'post_type'      => 'jurisdiction',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ]);

    foreach ( $jurisdictions as $post ) {
        $terms = wp_get_post_terms( $post->ID, 'jurisdiction-type' );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $term_name = $terms[0]->name;
            
            // Map Taxonomy Name to ACF Select Value
            $acf_value = strtolower( str_replace( [' ', '.'], ['_', ''], $term_name ) );
            
            // Update the new ACF field
            update_field( 'field_ws_jurisdiction_type', $acf_value, $post->ID );
        }
    }
}// ── One-time cleanup: remove orphaned jurisdiction-type taxonomy data ─────────
//
// The `jurisdiction-type` taxonomy was registered in earlier versions but never
// used functionally. Type classification is handled by the ws_jurisdiction_type
// ACF field instead.
//
// This routine fires once on admin_init after v1.9.0 is deployed. It removes:
//   - All terms in the `jurisdiction-type` taxonomy (wp_terms)
//   - All term taxonomy records (wp_term_taxonomy)
//   - All term relationships on jurisdiction posts (wp_term_relationships)
//
// A completion flag (ws_jx_type_taxonomy_cleanup) prevents re-runs.

add_action( 'admin_init', 'ws_cleanup_jurisdiction_type_taxonomy' );
function ws_cleanup_jurisdiction_type_taxonomy() {

    if ( get_option( 'ws_jx_type_taxonomy_cleanup' ) ) {
        return; // Already ran — do nothing
    }

    // get_terms() won't work after the taxonomy is unregistered, so we go
    // directly to the database for a reliable cleanup.
    global $wpdb;

    // Find all term_taxonomy_ids for the jurisdiction-type taxonomy
    $tt_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
        'jurisdiction-type'
    ) );

    if ( ! empty( $tt_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $tt_ids ), '%d' ) );

        // Remove term relationships
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($placeholders)",
            ...$tt_ids
        ) );

        // Remove term taxonomy records
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id IN ($placeholders)",
            ...$tt_ids
        ) );
    }

    // Find and remove the terms themselves
    $term_ids = $wpdb->get_col(
        "SELECT term_id FROM {$wpdb->terms}
         WHERE name IN ('U.S. States','Federal','U.S. Territories','District of Columbia')
         AND term_id NOT IN (SELECT term_id FROM {$wpdb->term_taxonomy})"
    );

    if ( ! empty( $term_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->terms} WHERE term_id IN ($placeholders)",
            ...$term_ids
        ) );
    }

    // Mark complete — never runs again
    update_option( 'ws_jx_type_taxonomy_cleanup', true );

    // Admin notice confirming completion
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo '<strong>WhistleblowerShield Core:</strong> Removed orphaned <code>jurisdiction-type</code> taxonomy data from the database.';
        echo '</p></div>';
    } );
}
