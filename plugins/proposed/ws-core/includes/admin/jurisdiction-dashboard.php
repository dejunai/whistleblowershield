<?php
/**
 * File: jurisdiction-dashboard.php
 *
 * Provides a simple overview dashboard for all jurisdictions.
 * Purpose: Completion Tracker for the 57 Jurisdictions
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Integrated menu & health tracker
 * 2.3.1  Fixed status checks: query layer returns arrays, not WP_Post objects.
 *        Added Citations column using ws_get_attached_citation_count()
 *        (defined in admin-navigation.php, which loads first).
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
 */
function ws_render_jurisdiction_dashboard() {
    echo '<div class="wrap">';
    echo '<h1>Jurisdiction Data Health</h1>';
    echo '<p>Visual status of the 57 core jurisdictions and their associated datasets.</p>';

    // Pull from our Query Layer (which now uses Caching)
    $jurisdictions = ws_get_all_jurisdictions();

    if (empty($jurisdictions)) {
        echo '<div class="notice notice-warning"><p>No jurisdictions found. Please create one to begin tracking.</p></div>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
    echo '<thead><tr>
            <th style="width: 18%;">Jurisdiction</th>
            <th>Summary</th>
            <th>Statutes</th>
            <th>Resources</th>
            <th>Citations</th>
          </tr></thead>';
    echo '<tbody>';

    foreach ($jurisdictions as $jx) {
        echo '<tr>';
        echo '<td><strong>' . esc_html($jx->post_title) . '</strong></td>';

        // Check each addendum type.
        // Query layer returns arrays (['id', 'status', 'content']), not WP_Post objects.
        // ws_get_jx_statutes() returns array-of-arrays — check $related[0]['status'].
        $types = ['summary', 'statutes', 'resources'];
        foreach ($types as $type) {
            $get_func = "ws_get_jx_{$type}";
            $related  = $get_func( $jx->ID );

            // Extract status from query layer result.
            $status = null;
            if ( $related ) {
                if ( isset( $related[0] ) && is_array( $related[0] ) ) {
                    // Array-of-arrays (statutes merge): use the first record.
                    $status = $related[0]['status'] ?? null;
                } elseif ( is_array( $related ) ) {
                    $status = $related['status'] ?? null;
                }
            }

            if ( $status === 'publish' ) {
                echo '<td style="color: #46b450;">✔ Published</td>';
            } elseif ( $status ) {
                echo '<td style="color: #ffa500;">⚠ ' . esc_html( ucfirst( $status ) ) . '</td>';
            } else {
                echo '<td style="color: #dc3232;">✘ Missing</td>';
            }
        }

        // Citations column: count with color threshold.
        $cite_count = ws_get_attached_citation_count( $jx->ID );
        if ( $cite_count === 0 ) {
            echo '<td style="color: #dc3232; font-weight:600;">0</td>';
        } elseif ( $cite_count <= 2 ) {
            echo '<td style="color: #ffa500; font-weight:600;">' . $cite_count . '</td>';
        } else {
            echo '<td style="color: #46b450; font-weight:600;">' . $cite_count . '</td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}