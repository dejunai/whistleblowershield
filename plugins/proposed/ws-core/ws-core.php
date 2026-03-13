<?php
/**
 * Plugin Name: WhistleblowerShield Core
 * Description: Core architecture for WhistleblowerShield. Optimized for automatic assembly of 57 jurisdictions.
 * Version: 2.1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Constants ────────────────────────────────────────────────────────────────
define( 'WS_CORE_VERSION', '2.1.2' );
define( 'WS_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize Plugin Logic
 * Using 'plugins_loaded' ensures all other plugins (like ACF Pro) are ready.
 */
add_action( 'plugins_loaded', 'ws_core_init' );

function ws_core_init() {
    // Check for ACF Pro dependency
    if ( ! class_exists( 'ACF' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>WhistleblowerShield Core:</strong> ACF Pro is required for automatic assembly.</p></div>';
        });
        return;
    }

    // Load the optimized Modular Loader
    require_once WS_CORE_PATH . 'includes/loader.php';
}

/**
 * Conditional Asset Loading
 * Only loads CSS on the specific pages being automatically assembled.
 */
add_action( 'wp_enqueue_scripts', 'ws_core_conditional_assets' );

function ws_core_conditional_assets() {
    if ( is_singular( 'jurisdiction' ) ) {
        wp_enqueue_style( 
            'ws-core-front', 
            WS_CORE_URL . 'ws-core-front.css', 
            [], 
            WS_CORE_VERSION 
        );
    }
}