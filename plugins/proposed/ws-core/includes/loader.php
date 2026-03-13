<?php
<?php
/**
 * File: loader.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Centralized loader for all plugin components.
 *
 * This file is responsible for including every module used by
 * the ws-core plugin. By consolidating file loading here, the
 * main plugin file remains small and easy to understand.
 *
 *
 * ARCHITECTURE
 * ------------
 *
 * The plugin is divided into functional layers:
 *
 *      CPT Layer        → registers custom post types
 *      ACF Layer        → defines custom fields
 *      Query Layer      → retrieves structured data
 *      Rendering Layer  → builds jurisdiction pages
 *      Shortcode Layer  → renders individual sections
 *      Admin Layer      → improves editorial workflow
 *
 *
 * DIRECTORY STRUCTURE
 * -------------------
 *
 * includes/
 *
 *      acf/
 *      admin/
 *      cpt/
 *      queries/
 *      render/
 *      shortcodes/
 *
 *
 * LOADING STRATEGY
 * ----------------
 *
 * Files are loaded in dependency order:
 *
 *      1) CPT definitions
 *      2) ACF field definitions
 *      3) query layer
 *      4) rendering helpers
 *      5) shortcodes
 *      6) admin tools
 *
 *
 * VERSION
 * -------
 * 2.1.0  Modular loader introduced
 */
/**
 * File: loader.php
 * Updated: 2.1.3
 * * Optimized for Exclusive Automatic Assembly & Advanced Admin UX.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
---------------------------------------------------------
1. UNIVERSAL LAYER (Necessary for Permalinks & API)
---------------------------------------------------------
*/
// CPT Layer: Must load everywhere so WordPress understands the URLs
$cpt_files = [
    'cpt-jurisdiction', 'cpt-jx-summary', 'cpt-jx-procedures', 
    'cpt-jx-statutes', 'cpt-jx-resources', 'cpt-legal-update'
];
foreach ( $cpt_files as $file ) {
    require_once WS_CORE_PATH . "includes/cpt/{$file}.php";
}

// Query Layer: The "Data API" for both Admin and Frontend
require_once WS_CORE_PATH . 'includes/queries/query-jurisdiction.php';


/*
---------------------------------------------------------
2. ADMIN LAYER (Only for Editor/Dashboard)
---------------------------------------------------------
*/
if ( is_admin() ) {
    // ACF Layer: Huge memory save by keeping these out of the frontend
    $acf_files = [
        'acf-jurisdiction', 'acf-jx-summary', 'acf-jx-procedures', 
        'acf-jx-statutes', 'acf-jx-resources', 'acf-legal-update'
    ];
    foreach ( $acf_files as $file ) {
        require_once WS_CORE_PATH . "includes/acf/{$file}.php";
    }

    // Admin Tools & Workflow Improvements
    require_once WS_CORE_PATH . 'includes/admin/admin-navigation.php';
    require_once WS_CORE_PATH . 'includes/admin/admin-columns.php';
    require_once WS_CORE_PATH . 'includes/admin/admin-hooks.php';
    require_once WS_CORE_PATH . 'includes/admin/admin-audit-trail.php';
    require_once WS_CORE_PATH . 'includes/admin/admin-relationships.php';
    require_once WS_CORE_PATH . 'includes/admin/jurisdiction-dashboard.php';
}


/*
---------------------------------------------------------
3. ASSEMBLY LAYER (Only for Public Display)
---------------------------------------------------------
*/
if ( ! is_admin() ) {
    // The HTML Templates
    require_once WS_CORE_PATH . 'includes/render/section-renderer.php';
    
    // The "Automatic Assembler" (The the_content filter)
    require_once WS_CORE_PATH . 'includes/render/render-jurisdiction.php';
    
    // Shortcodes (Used internally by the Assembler)
    require_once WS_CORE_PATH . 'includes/shortcodes/shortcodes-jurisdiction.php';
    require_once WS_CORE_PATH . 'includes/shortcodes/shortcodes-general.php';
}