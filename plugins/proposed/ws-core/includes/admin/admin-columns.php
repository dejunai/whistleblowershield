<?php
/**
 * File: admin-columns.php
 *
 * Adds dataset status columns to the Jurisdiction list table
 * in the WordPress admin. Each column shows a visual indicator
 * for whether the corresponding addendum (summary, procedures,
 * statutes, resources) exists and is published.
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Added column header registration (was missing)
 *         Visual status icons via dashicons
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Register custom columns ───────────────────────────────────────────────────

add_filter( 'manage_jurisdiction_posts_columns', 'ws_add_jx_status_columns' );
function ws_add_jx_status_columns( $columns ) {
    // Insert after the title column
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['summary']    = 'Summary';
            $new['procedures'] = 'Procedures';
            $new['statutes']   = 'Statutes';
            $new['resources']  = 'Resources';
        }
    }
    return $new;
}


// ── Render column content ─────────────────────────────────────────────────────

add_action( 'manage_jurisdiction_posts_custom_column', 'ws_render_jx_status_column', 10, 2 );
function ws_render_jx_status_column( $column, $post_id ) {

    $map = [
        'summary'    => 'ws_related_summary',
        'procedures' => 'ws_related_procedures',
        'statutes'   => 'ws_related_statutes',
        'resources'  => 'ws_related_resources',
    ];

    if ( ! isset( $map[ $column ] ) ) return;

    $related = get_field( $map[ $column ], $post_id );

    if ( $related ) {
        $status = get_post_status( $related->ID );
        if ( $status === 'publish' ) {
            echo '<span class="dashicons dashicons-yes" style="color:#46b450;" title="Published"></span>';
        } else {
            echo '<span class="dashicons dashicons-warning" style="color:#ffa500;" title="' . esc_attr( ucfirst( $status ) ) . '"></span>';
        }
    } else {
        echo '<span class="dashicons dashicons-no-alt" style="color:#dc3232;" title="Missing"></span>';
    }
}


// ── Make columns sortable (optional — non-sortable by default) ────────────────
// These columns are status indicators, not data fields, so sorting is omitted.
