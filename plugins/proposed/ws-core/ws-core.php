<?php
/**
 * Plugin Name: WhistleblowerShield Core
 * Description: Core architecture for WhistleblowerShield. Optimized for
 *              automatic assembly of 57 jurisdictions.
 * Version:     2.1.3
 * Author:      Whistleblower Shield
 * Author URI:  https://whistleblowershield.org
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION', '2.1.3' );
define( 'WS_CORE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL',     plugin_dir_url( __FILE__ ) );


// ── Bootstrap ─────────────────────────────────────────────────────────────────
//
// Using plugins_loaded ensures ACF Pro and all other plugins are
// fully initialized before ws-core attempts to load its modules.

add_action( 'plugins_loaded', 'ws_core_init' );

function ws_core_init() {

    // Require ACF Pro — all field registration depends on it
    if ( ! class_exists( 'ACF' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>'
               . '<strong>WhistleblowerShield Core:</strong> '
               . 'ACF Pro is required and must be active.'
               . '</p></div>';
        } );
        return;
    }

    require_once WS_CORE_PATH . 'includes/loader.php';
}


// ── Frontend Assets ───────────────────────────────────────────────────────────
//
// ws-core-front.css is loaded globally on all public-facing pages.
//
// @todo - Revisit before launch. Narrow to specific page types once
//         shortcode usage across page templates has been fully audited.
//         Candidate conditional: is_singular('jurisdiction') plus any
//         other page types confirmed to use ws-core shortcodes.

add_action( 'wp_enqueue_scripts', 'ws_core_enqueue_assets' );

function ws_core_enqueue_assets() {
    wp_enqueue_style(
        'ws-core-front',
        WS_CORE_URL . 'ws-core-front.css',
        [],
        WS_CORE_VERSION
    );
}
