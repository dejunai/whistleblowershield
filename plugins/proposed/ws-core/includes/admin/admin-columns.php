<?php
/**
 * File: admin-columns.php
 *
 * Adds dataset status indicators to the Jurisdiction list
 * in the WordPress admin panel.
 */
/**
 * File: admin-columns.php
 * Updated: 2.1.3 (Visual Status Icons)
 */

add_action('manage_jurisdiction_posts_custom_column', function($column, $post_id) {
    // Map column names to ACF relationship field names
    $map = [
        'summary'    => 'ws_related_summary',
        'procedures' => 'ws_related_procedures',
        'statutes'   => 'ws_related_statutes',
        'resources'  => 'ws_related_resources'
    ];

    if (isset($map[$column])) {
        $related = get_field($map[$column], $post_id);
        
        if ($related) {
            $status = get_post_status($related->ID);
            $icon   = ($status === 'publish') ? 'dashicons-yes' : 'dashicons-warning';
            $color  = ($status === 'publish') ? '#46b450' : '#ffa500';
            $title  = ($status === 'publish') ? 'Published' : 'Draft';
            
            echo '<span class="dashicons ' . $icon . '" style="color:' . $color . ';" title="' . $title . '"></span>';
        } else {
            echo '<span class="dashicons dashicons-no-alt" style="color:#dc3232;" title="Missing"></span>';
        }
    }
}, 10, 2);