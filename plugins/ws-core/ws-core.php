<?php
// =============================================================================
// !! DEVELOPMENT ONLY — NOT LIVE
//
// This plugin is NOT deployed to a live site. There is NO production database.
// NO user data exists. NO migration concerns apply. Architectural changes are
// free to be destructive until this notice is removed.
//
// When this changes: remove this block, audit all @todo items flagged
// "pre-launch only", and run the full testing pass documented in
// project-status.md before activating.
// =============================================================================
//
// !! THIS PLUGIN USES A QUERY LAYER.
//    Never call get_field(), get_post_meta(), or WP_Query directly in
//    shortcodes or render functions. All data retrieval goes through
//    includes/queries/. See README.md for the full architectural rules.
// =============================================================================

/**
 * Plugin Name:  WhistleblowerShield Core
 * Description:  Core architecture for WhistleblowerShield.org. Assembles
 *               public whistleblower protection pages for 57 U.S. jurisdictions.
 * Version:      3.13.0
 * Author:       Whistleblower Shield
 * Author URI:   https://whistleblowershield.org
 *
 * Architectural conventions, naming rules, and version history: see README.md.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION', '3.10.2' );
define( 'WS_CORE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL',     plugin_dir_url( __FILE__ ) );

// The registered taxonomy slug. Passed wherever WordPress expects a taxonomy
// identifier — wp_get_post_terms(), has_term(), tax_query 'taxonomy' key, etc.
define( 'WS_JURISDICTION_TAXONOMY', 'ws_jurisdiction' );

// Transient keys for the two jurisdiction-level query caches. Both are
// invalidated together by ws_invalidate_jurisdiction_caches() whenever a
// jurisdiction post is saved or deleted.
define( 'WS_CACHE_ALL_JURISDICTIONS', 'ws_all_jurisdictions_cache' );
define( 'WS_CACHE_JX_INDEX',          'ws_jx_index_cache'          );

// Transient key for the sitewide legal updates cache.
// Stores up to 100 items; sliced to the requested count on read.
// Sitewide calls with count > 100 bypass the cache entirely.
// Per-jurisdiction calls are never cached.
// Invalidated on every ws-legal-update save.
define( 'WS_CACHE_LEGAL_UPDATES_SITEWIDE', 'ws_legal_updates_sitewide' );

// CPT slugs that support a reference parent relationship. Used by
// ws_get_reference_parent_data() to gate lookups to valid parent types.
define( 'WS_REF_PARENT_TYPES', [ 'jx-statute', 'jx-citation', 'jx-interpretation' ] );

// ── Source Method Constants ───────────────────────────────────────────────────
//
// Values written to the ws_auto_source_method meta key. Defined here so they
// are available to all modules — including matrix files that load before
// admin-hooks.php. The method set is intentionally stable; prefer adding a new
// source_name under an existing method over introducing a new constant.
define( 'WS_SOURCE_MATRIX_SEED',   'matrix_seed'   );
define( 'WS_SOURCE_AI_ASSISTED',   'ai_assisted'   );
define( 'WS_SOURCE_BULK_IMPORT',   'bulk_import'   );
define( 'WS_SOURCE_FEED_IMPORT',   'feed_import'   );
define( 'WS_SOURCE_HUMAN_CREATED', 'human_created' );

// Auto-assigned source_name for matrix_seed and human_created posts.
// Signals that source and method are the same — no external origin.
define( 'WS_SOURCE_NAME_DIRECT', 'Direct' );

// Legal update types visible on public-facing pages. 'internal' and 'other'
// are intentionally excluded. Add a new type here when it is added to the
// ws_legal_update_type ACF select in acf-legal-updates.php.
define( 'WS_LEGAL_UPDATE_SUMMARY_TYPES', [
    'statute', 'citation', 'summary', 'interpretation', 'regulation', 'policy',
] );


// ── Activation Hook ───────────────────────────────────────────────────────────
//
// CPTs are registered on 'init', which has not fired when the activation hook
// runs. Set a flag here; ws_core_init() flushes rewrite rules on the next
// admin_init after CPTs are registered.

register_activation_hook( __FILE__, 'ws_core_activate' );

function ws_core_activate() {
    update_option( 'ws_core_flush_rewrite_rules', true );
}

// ── Deactivation Hooks ────────────────────────────────────────────────────────

register_deactivation_hook( __FILE__, 'ws_url_monitor_deactivate' );
register_deactivation_hook( __FILE__, 'ws_feed_monitor_deactivate' );

// ── Bootstrap ─────────────────────────────────────────────────────────────────
//
// plugins_loaded ensures ACF Pro and all other plugins are fully initialized
// before ws-core attempts to load its modules.

add_action( 'plugins_loaded', 'ws_core_init' );

function ws_core_init() {

    if ( ! class_exists( 'ACF' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>'
               . '<strong>WhistleblowerShield Core:</strong> '
               . 'ACF Pro is required and must be active.'
               . '</p></div>';
        } );
        return;
    }

    // ── Developer ───────────────────────────────────────────────────────────
	//
	// Sentry loaded by /vendor/autoload.php

	if (file_exists(__DIR__ . '/vendor/autoload.php')) {
		require_once __DIR__ . '/vendor/autoload.php';
	}
	define('WS_ENABLE_SENTRY', true);

    require_once WS_CORE_PATH . 'includes/loader.php';
	
	ws_sentry_init();

    // Flush rewrite rules once after activation — deferred so all CPTs are
    // registered before the flush runs.
    if ( is_admin() && get_option( 'ws_core_flush_rewrite_rules' ) ) {
        flush_rewrite_rules();
        delete_option( 'ws_core_flush_rewrite_rules' );
    }
}


// ── Frontend Assets ───────────────────────────────────────────────────────────
//
// ws-core-front-general.css — all singular posts/pages (is_singular())
// ws-core-front-jx.css      — jurisdiction CPT singles only; depends on general
// ws-core-front.js          — jurisdiction index filter tabs; self-exits when
//                             .ws-jx-filter-nav is absent

add_action( 'wp_enqueue_scripts', 'ws_core_enqueue_assets' );

function ws_core_enqueue_assets() {

    if ( is_admin() ) {

        wp_enqueue_style(
			'ws-core-admin',
			WS_CORE_URL . 'ws-core-admin.css',
			[ 'acf-input' ],
			WS_CORE_VERSION
		);
    }
	
    if ( is_singular() ) {

        wp_enqueue_style(
            'ws-core-front-general',
            WS_CORE_URL . 'ws-core-front-general.css',
            [],
            WS_CORE_VERSION
        );

        wp_enqueue_script(
            'ws-core-front',
            WS_CORE_URL . 'ws-core-front.js',
            [],
            WS_CORE_VERSION,
            true
        );
    }

    if ( is_singular( 'jurisdiction' ) ) {

        wp_enqueue_style(
            'ws-core-front-jx',
            WS_CORE_URL . 'ws-core-front-jx.css',
            [ 'ws-core-front-general' ],
            WS_CORE_VERSION
        );
    }
}
