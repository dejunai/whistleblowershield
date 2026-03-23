<?php
/**
 * File: jurisdiction-dashboard.php
 *
 * Provides a simple overview dashboard for all jurisdictions.
 * Purpose: Completion Tracker for the 57 Jurisdictions
 *
 * CACHING
 * -------
 * The full rendered HTML table is cached as a 10-minute transient
 * (ws_jx_dashboard_html). The cache is invalidated automatically whenever
 * any of the tracked CPTs is saved or deleted via save_post / delete_post.
 * A "Refresh" button on the page clears it on demand.
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Integrated menu & health tracker
 * 2.3.1  Fixed status checks: query layer returns arrays, not WP_Post objects.
 *        Added Citations column using ws_get_attached_citation_count()
 *        (defined in admin-navigation.php, which loads first).
 * 3.0.0  Removed Resources column (CPT deleted). Added Interpretations, Legal
 *        Updates, Agencies, and Assist-Orgs count columns. All counts use
 *        ws_jurisdiction taxonomy queries — no ACF relationship fields.
 * 3.6.1  Added 10-minute transient cache with auto-invalidation on save/delete
 *        and manual refresh button.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Register the Menu Item
 * This is the function you wrote—it is definitely NOT obsolete. 
 * It places the "Jurisdiction Status" link in your Admin Sidebar.
 */
add_action('admin_menu', function() {
    add_menu_page(
        'Jurisdiction Dashboard',   // Page Title
        'Jurisdiction Status',    // Menu Title
        'manage_options',          // Capability required
        'ws-jurisdiction-dashboard', // Menu Slug
        'ws_render_jurisdiction_dashboard', // The function that draws the page
        'dashicons-clipboard',     // Icon
        25                         // Position
    );
});

/**
 * 2. Render the Dashboard
 * This is the cleaned-up version that loops through your 57 jurisdictions
 * and shows the visual "Health Matrix."
 *
 * The table HTML is cached as a 10-minute transient. A "Refresh" button
 * clears it on demand; save/delete hooks clear it automatically.
 */
function ws_render_jurisdiction_dashboard() {

    // ── Manual cache clear ────────────────────────────────────────────────
    if (
        isset( $_POST['ws_jx_dash_refresh'] ) &&
        check_admin_referer( 'ws_jx_dash_refresh' )
    ) {
        delete_transient( 'ws_jx_dashboard_html' );
    }

    echo '<div class="wrap">';
    echo '<h1>Jurisdiction Data Health</h1>';
    echo '<p>Visual status of the 57 core jurisdictions and their associated datasets.</p>';

    // ── Refresh button ────────────────────────────────────────────────────
    echo '<form method="post" style="display:inline-block;margin-bottom:12px;">';
    wp_nonce_field( 'ws_jx_dash_refresh' );
    echo '<input type="hidden" name="ws_jx_dash_refresh" value="1">';
    submit_button( 'Refresh Data', 'secondary small', '', false );
    echo '</form>';

    // ── Serve from cache if available ─────────────────────────────────────
    $cached = get_transient( 'ws_jx_dashboard_html' );
    if ( false !== $cached ) {
        echo $cached; // Already escaped when built.
        echo '</div>';
        return;
    }

    // ── Build table ───────────────────────────────────────────────────────
    $jurisdictions = ws_get_all_jurisdictions();

    if ( empty( $jurisdictions ) ) {
        echo '<div class="notice notice-warning"><p>No jurisdictions found. Please create one to begin tracking.</p></div>';
        echo '</div>';
        return;
    }

    ob_start();

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
    echo '<thead><tr>
            <th style="width:16%;">Jurisdiction</th>
            <th>Summary</th>
            <th>Statutes</th>
            <th>Citations</th>
            <th>Interp.</th>
            <th>Updates</th>
            <th>Agencies</th>
            <th>Orgs</th>
          </tr></thead>';
    echo '<tbody>';

    foreach ( $jurisdictions as $jx ) {

        // Resolve ws_jurisdiction term for this jurisdiction post.
        $terms   = wp_get_post_terms( $jx->ID, WS_JURISDICTION_TERM_ID );
        $term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0]->term_id : 0;

        echo '<tr>';
        echo '<td><strong>' . esc_html( $jx->post_title ) . '</strong></td>';

        // ── Summary (one-to-one: show publish/draft/missing) ──────────────
        $summary_status = ws_jx_dashboard_one_status( $term_id, 'jx-summary' );
        echo ws_jx_dashboard_status_cell( $summary_status );

        // ── Statutes (count of editorially curated / attach_flag = 1) ─────
        $statute_count = ws_jx_dashboard_count( $term_id, 'jx-statute', true );
        echo ws_jx_dashboard_count_cell( $statute_count );

        // ── Citations (curated count via shared helper) ────────────────────
        $cite_count = ws_get_attached_citation_count( $jx->ID );
        echo ws_jx_dashboard_count_cell( $cite_count );

        // ── Interpretations (count of editorially curated) ────────────────
        $interp_count = ws_jx_dashboard_count( $term_id, 'jx-interpretation', true );
        echo ws_jx_dashboard_count_cell( $interp_count );

        // ── Legal Updates (any published for this jurisdiction) ───────────
        $update_count = ws_jx_dashboard_count( $term_id, 'ws-legal-update', false );
        echo ws_jx_dashboard_count_cell( $update_count );

        // ── Agencies (any published for this jurisdiction) ────────────────
        $agency_count = ws_jx_dashboard_count( $term_id, 'ws-agency', false );
        echo ws_jx_dashboard_count_cell( $agency_count );

        // ── Assist-Orgs (any published for this jurisdiction) ─────────────
        $org_count = ws_jx_dashboard_count( $term_id, 'ws-assist-org', false );
        echo ws_jx_dashboard_count_cell( $org_count );

        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p style="color:#999;font-size:11px;margin-top:8px;">Cached for 10 minutes. Use Refresh to rebuild.</p>';

    $html = ob_get_clean();
    set_transient( 'ws_jx_dashboard_html', $html, 10 * MINUTE_IN_SECONDS );
    echo $html;

    echo '</div>';
}


// ── Cache Invalidation ────────────────────────────────────────────────────────
//
// Clears the dashboard transient whenever any CPT tracked by the dashboard
// is saved or deleted. Covers all eight CPT types shown in the matrix.

add_action( 'save_post',   'ws_jx_dashboard_invalidate_cache' );
add_action( 'delete_post', 'ws_jx_dashboard_invalidate_cache' );

/**
 * Deletes the dashboard HTML transient when a tracked CPT post is modified.
 *
 * @param int $post_id
 */
function ws_jx_dashboard_invalidate_cache( $post_id ) {
    static $tracked = [
        'jurisdiction', 'jx-summary', 'jx-statute', 'jx-citation',
        'jx-interpretation', 'ws-legal-update', 'ws-agency', 'ws-assist-org',
    ];
    if ( in_array( get_post_type( $post_id ), $tracked, true ) ) {
        delete_transient( 'ws_jx_dashboard_html' );
    }
}


// ── Dashboard Helpers ─────────────────────────────────────────────────────────

/**
 * Returns the post status of the first record of $post_type assigned to $term_id,
 * or null if none exists. Used for one-to-one CPT relationships (e.g. jx-summary).
 *
 * @param  int    $term_id   ws_jurisdiction term ID.
 * @param  string $post_type CPT slug.
 * @return string|null       Post status string or null.
 */
function ws_jx_dashboard_one_status( $term_id, $post_type ) {
    if ( ! $term_id ) return null;
    $ids = get_posts( [
        'post_type'      => $post_type,
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'tax_query'      => [ [ 'taxonomy' => WS_JURISDICTION_TERM_ID, 'field' => 'term_id', 'terms' => $term_id ] ],
    ] );
    return ! empty( $ids ) ? get_post_status( $ids[0] ) : null;
}

/**
 * Returns the count of published records of $post_type for $term_id.
 * If $attach_only is true, also requires ws_attach_flag = 1.
 *
 * @param  int    $term_id     ws_jurisdiction term ID.
 * @param  string $post_type   CPT slug.
 * @param  bool   $attach_only Restrict to attach_flag = 1.
 * @return int
 */
function ws_jx_dashboard_count( $term_id, $post_type, $attach_only = false ) {
    if ( ! $term_id ) return 0;
    $args = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [ [ 'taxonomy' => WS_JURISDICTION_TERM_ID, 'field' => 'term_id', 'terms' => $term_id ] ],
    ];
    if ( $attach_only ) {
        $args['meta_query'] = [ [ 'key' => 'ws_attach_flag', 'value' => '1', 'compare' => '=' ] ];
    }
    return (int) count( get_posts( $args ) );
}

/**
 * Renders a status cell for a one-to-one relationship (publish/draft/missing).
 *
 * @param  string|null $status
 * @return string HTML <td>
 */
function ws_jx_dashboard_status_cell( $status ) {
    if ( $status === 'publish' ) {
        return '<td style="color:#46b450;">✔ Published</td>';
    } elseif ( $status ) {
        return '<td style="color:#ffa500;">⚠ ' . esc_html( ucfirst( $status ) ) . '</td>';
    }
    return '<td style="color:#dc3232;">✘ Missing</td>';
}

/**
 * Renders a count cell with red/orange/green thresholds (0 / 1-2 / 3+).
 *
 * @param  int $count
 * @return string HTML <td>
 */
function ws_jx_dashboard_count_cell( $count ) {
    if ( $count === 0 ) {
        return '<td style="color:#dc3232;font-weight:600;">0</td>';
    } elseif ( $count <= 2 ) {
        return '<td style="color:#ffa500;font-weight:600;">' . $count . '</td>';
    }
    return '<td style="color:#46b450;font-weight:600;">' . $count . '</td>';
}