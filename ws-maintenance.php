<?php
if ( defined( 'DOING_CRON' ) && DOING_CRON ) return;
if ( defined( 'WP_CLI' ) && WP_CLI ) return;

add_action( 'template_redirect', function() {
    if ( is_user_logged_in() ) return;
    wp_die(
        'This site is under construction. Please check back soon.',
        'Under Construction',
        [ 'response' => 503 ]
    );
} );
