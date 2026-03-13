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

if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
CPT Layer
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/cpt/cpt-jurisdiction.php';

require_once WS_CORE_PATH . 'includes/cpt/cpt-jx-summary.php';
require_once WS_CORE_PATH . 'includes/cpt/cpt-jx-procedures.php';
require_once WS_CORE_PATH . 'includes/cpt/cpt-jx-statutes.php';
require_once WS_CORE_PATH . 'includes/cpt/cpt-jx-resources.php';
require_once WS_CORE_PATH . 'includes/cpt/cpt-legal-update.php';



/*
---------------------------------------------------------
ACF Field Definitions
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/acf/acf-jurisdiction.php';

require_once WS_CORE_PATH . 'includes/acf/acf-jx-summary.php';
require_once WS_CORE_PATH . 'includes/acf/acf-jx-procedures.php';
require_once WS_CORE_PATH . 'includes/acf/acf-jx-statutes.php';
require_once WS_CORE_PATH . 'includes/acf/acf-jx-resources.php';
require_once WS_CORE_PATH . 'includes/acf/acf-legal-update.php';



/*
---------------------------------------------------------
Query Layer
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/queries/query-jurisdiction.php';



/*
---------------------------------------------------------
Rendering Layer
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/render/section-renderer.php';
require_once WS_CORE_PATH . 'includes/render/render-jurisdiction.php';



/*
---------------------------------------------------------
Shortcodes
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/shortcodes/shortcodes-jurisdiction.php';



/*
---------------------------------------------------------
Admin Tools
---------------------------------------------------------
*/

require_once WS_CORE_PATH . 'includes/admin/admin-navigation.php';
require_once WS_CORE_PATH . 'includes/admin/admin-columns.php';
require_once WS_CORE_PATH . 'includes/admin/jurisdiction-dashboard.php';