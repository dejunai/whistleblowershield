<?php
/**
 * File: jurisdiction-dashboard.php
 *
 * Provides a simple overview dashboard for all jurisdictions.
 * Purpose: Completion Tracker for the 57 Jurisdictions
 */
/**
 * File: jurisdiction-dashboard.php
 * Updated: 2.1.3 (Integrated Menu & Health Tracker)
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
            <th style="width: 20%;">Jurisdiction</th>
            <th>Summary</th>
            <th>Procedures</th>
            <th>Statutes</th>
            <th>Resources</th>
          </tr></thead>';
    echo '<tbody>';

    foreach ($jurisdictions as $jx) {
        echo '<tr>';
        echo '<td><strong>' . esc_html($jx->post_title) . '</strong></td>';

        // Check each addendum type
        $types = ['summary', 'procedures', 'statutes', 'resources'];
        foreach ($types as $type) {
            $get_func = "ws_get_jx_{$type}";
            $related = $get_func($jx->ID);
            
            if ($related && $related->post_status === 'publish') {
                echo '<td style="color: #46b450;">✔ Published</td>';
            } elseif ($related) {
                echo '<td style="color: #ffa500;">? Draft</td>';
            } else {
                echo '<td style="color: #dc3232;">✘ Missing</td>';
            }
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}