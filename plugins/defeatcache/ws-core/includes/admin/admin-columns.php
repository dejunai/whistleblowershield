<?php
/**
 * File: admin-columns.php
 *
 * Adds dataset status columns to the Jurisdiction list table
 * in the WordPress admin. Each column shows a visual indicator
 * for whether the corresponding addendum (summary, statutes,
 * resources) exists and is published.
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Added column header registration (was missing)
 *        Visual status icons via dashicons
 * 2.3.1  Added Citations column. Uses ws_get_attached_citation_count()
 *        (defined in admin-navigation.php, which loads first).
 *        Badge shows count with red/orange/green thresholds (0/1-2/3+).
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
            $new['summary']   = 'Summary';
            $new['statutes']  = 'Statutes';
            $new['resources'] = 'Resources';
            $new['citations'] = 'Citations';
        }
    }
    return $new;
}


// ── Render column content ─────────────────────────────────────────────────────

add_action( 'manage_jurisdiction_posts_custom_column', 'ws_render_jx_status_column', 10, 2 );
function ws_render_jx_status_column( $column, $post_id ) {

    $map = [
        'summary'  => 'ws_related_summary',
        'statutes' => 'ws_related_statutes',
        'resources' => '',
    ];

    // Citations column uses count-based display, not an ACF relationship field.
    if ( $column === 'citations' ) {
        $count = ws_get_attached_citation_count( $post_id );
        if ( $count === 0 ) {
            echo '<span style="color:#dc3232; font-weight:600;">0</span>';
        } elseif ( $count <= 2 ) {
            echo '<span style="color:#ffa500; font-weight:600;">' . $count . '</span>';
        } else {
            echo '<span style="color:#46b450; font-weight:600;">' . $count . '</span>';
        }
        return;
    }

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
