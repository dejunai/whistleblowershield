<?php
/**
 * Plugin Name: WhistleblowerShield Core (ws-core)
 * Plugin URI:  https://whistleblowershield.org
 * Description: Core plugin for WhistleblowerShield.org. Registers all Custom Post Types,
 *              taxonomies, ACF field groups, shortcodes, and audit trail functionality.
 * Version:     1.9.0
 * Author:      Dejunai
 * Author URI:  https://whistleblowershield.org
 * License:     GPL-2.0+
 * Text Domain: ws-core
 *
 * Requires: Advanced Custom Fields Pro (ACF Pro)
 *
 * Version history:
 *   1.0.0 — Initial release. CPTs, taxonomies, ACF fields, shortcodes, audit trail.
 *            sources_public cleanup routine.
 *   1.1.0 — Added ws-core-front.css enqueue. Flag size and attribution layout fixes.
 *   1.2.0 — Expanded Meta Box orphan cleanup (ws_metabox_cleanup_v2).
 *   1.3.0 — Removed duplicate H1 from ws_jurisdiction_header shortcode.
 *            Added dynamic gov offices box with stacked links and type-based label.
 *   1.3.1 — Tightened gov link spacing in CSS.
 *   1.4.0 — Added [ws_footer] shortcode and footer CSS.
 *   1.4.1 — Added GeneratePress footer/header CSS overrides.
 *            .copyright-bar hidden. Footer centering fixed.
 *            Note: GP Elements now handles header disable at template level.
 *   1.5.0 — Restored ws_jurisdiction_name H1 above flag in jurisdiction header.
 *            Restructured header: title full-width on top, flag+offices row below.
 *   1.6.0 — Added [ws_jurisdiction_index] shortcode.
 *            Filter tabs by jurisdiction type + alphabetical grid.
 *            Tabs auto-hide if no jurisdictions of that type are published.
 *            Client-side filtering, no jQuery dependency.
 *   1.7.0 — Added [ws_disclaimer_notice] shortcode.
 *            Centralizes the "not legal advice" notice. Replaces per-summary
 *            inline <div> blocks.
 *   1.7.1 — Migrated .ws-term-highlight tooltip styles from Additional CSS
 *            into ws-core-front.css.
 *   1.8.0 — Renamed addendum CPTs to satisfy WordPress 20-character post type
 *            name limit (enforced since WP 4.2.0).
 *            jurisdiction-summary    → jx-summary
 *            jurisdiction-resources  → jx-resources
 *            jurisdiction-procedures → jx-procedures
 *            jurisdiction-statutes   → jx-statutes
 *            ACF relationship field post_type filters updated to match.
 *   1.9.0 — Completed codebase-wide naming convention pass.
 *            (a) Renamed `legal-update` CPT → `ws-update` for ws-* namespace
 *                consistency. Public archive slug: /ws-update/. ACF location
 *                rule and audit trail CPT list updated to match.
 *            (b) Removed `jurisdiction-type` taxonomy entirely. Type
 *                classification is handled by the ws_jurisdiction_type ACF
 *                select field; the taxonomy was registered but never queried.
 *                A one-time DB cleanup routine removes the orphaned terms,
 *                term_taxonomy records, and term_relationships on first
 *                admin_init after deployment.
 *
 * ── Post type naming conventions (v1.9.0+) ──────────────────────────────────
 *   jurisdiction   — the parent page CPT (public, has archive)
 *   jx-summary     — jurisdiction addendum: legal protections overview
 *   jx-resources   — jurisdiction addendum: resources (future)
 *   jx-procedures  — jurisdiction addendum: procedures (future)
 *   jx-statutes    — jurisdiction addendum: statutes of limitations (future)
 *   ws-update      — site-wide legal updates change log (public, has archive)
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION',  '1.9.0' );
define( 'WS_CORE_DIR',      plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL',      plugin_dir_url( __FILE__ ) );

// ── Load includes ────────────────────────────────────────────────────────────

require_once WS_CORE_DIR . 'includes/cpt-jurisdiction.php';
require_once WS_CORE_DIR . 'includes/cpt-summaries.php';
require_once WS_CORE_DIR . 'includes/cpt-legal-updates.php';
require_once WS_CORE_DIR . 'includes/acf-jurisdiction.php';
require_once WS_CORE_DIR . 'includes/acf-summary.php';
require_once WS_CORE_DIR . 'includes/acf-legal-updates.php';
require_once WS_CORE_DIR . 'includes/audit-trail.php';
require_once WS_CORE_DIR . 'includes/shortcodes.php';

// ── Enqueue frontend stylesheet ───────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'ws_core_enqueue_styles' );
function ws_core_enqueue_styles() {
    wp_enqueue_style(
        'ws-core-front',
        WS_CORE_URL . 'ws-core-front.css',
        [],
        WS_CORE_VERSION
    );
}

// ── Activation / flush rewrite rules ─────────────────────────────────────────

register_activation_hook( __FILE__, 'ws_core_activate' );
function ws_core_activate() {
    ws_register_jurisdiction_cpt();
    ws_register_summary_cpts();
    ws_register_legal_update_cpt();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

// ── ACF Pro dependency check ──────────────────────────────────────────────────

add_action( 'admin_notices', 'ws_core_acf_check' );
function ws_core_acf_check() {
    if ( ! class_exists( 'ACF' ) ) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WhistleblowerShield Core</strong> requires Advanced Custom Fields Pro to be installed and activated.';
        echo '</p></div>';
    }
}
