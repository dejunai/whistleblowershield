<?php
/**
 * File: jurisdiction-dashboard.php
 *
 * Provides a simple overview dashboard for all jurisdictions.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function(){

add_menu_page(
'Jurisdiction Dashboard',
'Jurisdiction Status',
'manage_options',
'ws-jurisdiction-dashboard',
'ws_render_jurisdiction_dashboard'
);

});

function ws_render_jurisdiction_dashboard(){

echo '<div class="wrap">';
echo '<h1>Jurisdiction Status</h1>';

$query = new WP_Query(array(
'post_type' => 'jurisdiction',
'posts_per_page' => -1
));

if($query->have_posts()){

echo '<ul>';

while($query->have_posts()){
$query->the_post();
echo '<li>' . get_the_title() . '</li>';
}

echo '</ul>';

}

echo '</div>';

}