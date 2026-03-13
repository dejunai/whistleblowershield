<?php
/**
 * File: admin-hooks.php
 * Purpose: Handle auto-linking of new addendums to parent Jurisdictions.
 */

if (!defined('ABSPATH')) exit;

/**
 * Pre-populate the ACF Relationship field on New Post screens
 */
add_filter('acf/load_field/name=ws_related_jurisdiction', function($field) {
    // Check if we are on a 'new post' screen and have our custom ID
    if (isset($_GET['ws_parent_id']) && is_numeric($_GET['ws_parent_id'])) {
        $field['default_value'] = [ intval($_GET['ws_parent_id']) ];
    }
    return $field;
});

/**
 * Pre-populate the Post Title if passed via URL
 */
add_filter('default_title', function($title) {
    if (isset($_GET['post_title'])) {
        return sanitize_text_field($_GET['post_title']);
    }
    return $title;
});