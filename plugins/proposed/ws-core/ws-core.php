<?php
/**
 * Plugin Name: WhistleblowerShield Core
 * Description: Core architecture for WhistleblowerShield. Proposed replacement
 *              plugin — radical refactor of v2.3.1. Not an upgrade of the live plugin.
 *              Assembles public whistleblower protection pages for 57 U.S. jurisdictions.
 * Version:     3.0.0
 * Author:      Whistleblower Shield
 * Author URI:  https://whistleblowershield.org
 *
 * ARCHITECTURE CHANGES (v2.3.1 → v3.0.0)
 * ----------------------------------------
 * This version is a proposed replacement, not an in-place upgrade. Key changes:
 *
 *   1. Jurisdiction join: ws_jx_code meta retired. All CPT-to-jurisdiction
 *      scoping now uses the ws_jurisdiction taxonomy (private, non-hierarchical,
 *      slug = USPS code). Slugs are lowercase (e.g., 'ca', 'us').
 *
 *   2. Attach-flag pattern: jx-citation, jx-statute, jx-interpretation each have
 *      attach_flag (true_false) + order (number) fields. Only flagged records
 *      appear on the public jurisdiction page.
 *
 *   3. Federal append (is_fed): ws_get_jx_statute_data(), ws_get_jx_citation_data(),
 *      and ws_get_jx_interpretation_data() automatically append US-scoped records
 *      to state pages. is_fed flag distinguishes them in the render layer.
 *
 *   4. Relationship fields removed: ws_jx_related_* ACF relationship fields and
 *      admin-relationships.php sync logic removed. Relationships are now implicit
 *      via taxonomy term assignment.
 *
 *   5. Data seeders: Four matrix seeders ship with the plugin:
 *      jurisdiction-matrix.php, agency-matrix.php, fed-statutes-matrix.php,
 *      assist-org-matrix.php. All use the Unified Option-Gate Method.
 *
 *   6. Matrix divergence monitoring: admin-matrix-watch.php detects manual
 *      edits to seeded records and surfaces them in a dashboard widget.
 *
 *   7. Plain language system: All six content CPTs now carry has_plain_english,
 *      plain_english (wysiwyg), plain_reviewed, summarized_by, summarized_date.
 *      jx-summary is the plain language document; the other CPTs have optional
 *      plain language overlays toggled per-record.
 *
 *   8. Trust badge: ws_render_plain_reviewed_badge() replaces the removed legal
 *      review badge system. Legal review badge removed entirely.
 *
 *   9. Query layer: ws_get_jx_summary_data(), ws_get_agency_data(),
 *      ws_get_assist_org_data() added. ws_get_jx_summary() and
 *      ws_get_jx_statutes() removed (replaced by taxonomy-keyed equivalents).
 *
 *  10. Shortcode compliance: all shortcodes delegate field reads to the query
 *      layer. No direct get_field() or get_post_meta() calls in shortcodes.
 *
 *  11. Fallback placeholder: if a jurisdiction page has no assembled content
 *      sections, a single .ws-section--placeholder notice is rendered.
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION', '3.0.0' );
define( 'WS_CORE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL',     plugin_dir_url( __FILE__ ) );


// ── Deactivation Hooks ────────────────────────────────────────────────────────

register_deactivation_hook( __FILE__, 'ws_url_monitor_deactivate' );


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
// ws-core-front.css and ws-core-front.js are loaded globally on all
// public-facing pages.
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

    wp_enqueue_script(
        'ws-core-front',
        WS_CORE_URL . 'ws-core-front.js',
        [],             // No dependencies
        WS_CORE_VERSION,
        true            // Load in footer
    );
}
