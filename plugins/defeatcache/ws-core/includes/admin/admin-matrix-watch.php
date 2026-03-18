<?php
/**
 * admin-matrix-watch.php
 *
 * Matrix Divergence Monitoring for ws-core seeded records.
 *
 * PURPOSE
 * -------
 * Detects when seeded records (posts with ws_matrix_source meta) have been
 * manually edited. This preserves the ability to identify which seeded
 * posts have diverged from the canonical matrix data, without preventing
 * editors from making necessary updates.
 *
 * BEHAVIOR
 * --------
 * On save_post: if the post has ws_matrix_source meta, set ws_matrix_divergence
 * to 1 and record the user ID that made the change.
 *
 * Resolution: set ws_matrix_divergence_resolved = 1 to acknowledge the
 * divergence (manual process — edit the post meta directly or via WP-CLI).
 *
 * Admin dashboard widget: lists all posts with unresolved divergences
 * (ws_matrix_divergence = 1 and ws_matrix_divergence_resolved != 1).
 *
 * @package    WhistleblowerShield
 * @since      3.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.0.0  Initial release (Phase 7).
 */

defined( 'ABSPATH' ) || exit;


// ── Detect divergence on save_post ────────────────────────────────────────────
//
// Fires on every save. Skips revisions, auto-drafts, and posts without
// ws_matrix_source meta (not seeded by a matrix). When a seeded post is
// saved, sets ws_matrix_divergence and records the editor's user ID.

add_action( 'save_post', 'ws_matrix_watch_detect_divergence', 20, 2 );

function ws_matrix_watch_detect_divergence( $post_id, $post ) {

    if ( wp_is_post_revision( $post_id ) ) return;
    if ( $post->post_status === 'auto-draft' ) return;

    // Only act on posts that were seeded by a matrix.
    $matrix_source = get_post_meta( $post_id, 'ws_matrix_source', true );
    if ( ! $matrix_source ) return;

    // Detect divergence: a seeded post has been manually edited.
    $current_user_id = get_current_user_id();

    // Only flag if not already marked as resolved.
    if ( get_post_meta( $post_id, 'ws_matrix_divergence_resolved', true ) !== '1' ) {
        update_post_meta( $post_id, 'ws_matrix_divergence',        '1' );
        update_post_meta( $post_id, 'ws_matrix_divergence_editor', $current_user_id );
    }
}


// ── Admin dashboard widget ─────────────────────────────────────────────────────
//
// Displays a list of seeded posts with unresolved divergences on the
// WordPress dashboard. Allows administrators to see at a glance which
// records have drifted from their seeded canonical values.

add_action( 'wp_dashboard_setup', 'ws_matrix_watch_register_widget' );

function ws_matrix_watch_register_widget() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    wp_add_dashboard_widget(
        'ws_matrix_divergence_widget',
        'WhistleblowerShield — Matrix Divergences',
        'ws_matrix_watch_render_widget'
    );
}

function ws_matrix_watch_render_widget() {

    $posts = get_posts( [
        'post_type'      => 'any',
        'post_status'    => 'any',
        'posts_per_page' => 50,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => 'ws_matrix_divergence',
                'value' => '1',
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'ws_matrix_divergence_resolved',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => 'ws_matrix_divergence_resolved',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ],
    ] );

    if ( empty( $posts ) ) {
        echo '<p style="color:#46b450;">&#10003; No unresolved matrix divergences.</p>';
        return;
    }

    echo '<p>The following seeded records have been manually edited and differ from their matrix source. '
       . 'Set <code>ws_matrix_divergence_resolved = 1</code> on a post to dismiss its entry here.</p>';
    echo '<ul style="margin:0; padding-left:1.2em;">';

    foreach ( $posts as $post ) {
        $source  = get_post_meta( $post->ID, 'ws_matrix_source', true );
        $editor  = (int) get_post_meta( $post->ID, 'ws_matrix_divergence_editor', true );
        $user    = $editor ? get_userdata( $editor ) : null;
        $by      = $user ? esc_html( $user->display_name ) : '(unknown)';
        $type    = get_post_type_object( $post->post_type );
        $pt_name = $type ? $type->labels->singular_name : $post->post_type;

        echo '<li style="margin-bottom:6px;">';
        echo '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a>';
        echo ' <span style="color:#999;font-size:11px;">(' . esc_html( $pt_name ) . ' — source: ' . esc_html( $source ) . ' — edited by: ' . $by . ')</span>';
        echo '</li>';
    }

    echo '</ul>';
}
