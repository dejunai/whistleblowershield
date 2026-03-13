<?php
/**
 * File: admin-columns.php
 *
 * Adds dataset status indicators to the Jurisdiction list
 * in the WordPress admin panel.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('manage_jurisdiction_posts_columns', function($columns){

$columns['summary'] = 'Summary';
$columns['procedures'] = 'Procedures';
$columns['statutes'] = 'Statutes';
$columns['resources'] = 'Resources';

return $columns;

});