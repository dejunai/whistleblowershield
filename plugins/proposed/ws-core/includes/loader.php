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
 *		Taxonomies       → allows labeling data objects by category
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
 *		taxonomies/
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
 *      6) taxonomies
 *      7) admin tools
 *
 *
 * VERSION
 * -------
 * 2.1.0  Modular loader introduced
 * 2.1.3  Optimized for exclusive automatic assembly and advanced admin UX
 * 2.1.4  Added taxonomy layer
 * 2.3.1  Moved taxonomy loading to Universal Layer so ws_disclosure_cat
 *        and ws_process_type are registered on both frontend and admin.
 *        Removed duplicate stale docblock.
 * 2.4.0  Added acf-jx-interpretations to ACF load list (Bug #6 fix).
 * 2.4.1  Added error reporting to loading calls, see /logs/ws-core-error.log
 * 3.0.0  Added acf-source-verify to ACF load list (was registered but never loaded).
 * 3.4.0  Added acf-stamp-fields and acf-plain-english-fields to ACF load list.
 *        These centralize stamp and plain language fields previously duplicated
 *        across individual CPT ACF files.
 * 3.6.0  Query layer split: single query-jurisdiction load replaced with ordered
 *        array load of query-helpers → query-shared → query-jurisdiction.
 *        render-directory.php stub added to ASSEMBLY LAYER render_files.
 * 3.6.1  admin-health-check.php added to ADMIN LAYER.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
---------------------------------------------------------
1. UNIVERSAL LAYER (Necessary for Permalinks & API)
---------------------------------------------------------
*/
	// CPT Layer: Must load everywhere so WordPress understands the URLs
	$cpt_files = [
		'cpt-jurisdictions', 'cpt-jx-summaries', 'cpt-jx-statutes', 'cpt-legal-updates',
		'cpt-jx-citations', 'cpt-agencies', 'cpt-assist-orgs',
		'cpt-jx-interpretations', 'cpt-references',
	];
	foreach ( $cpt_files as $file ) {
		$path = WS_CORE_PATH . "includes/cpt/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing CPT file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			// Only fires on back-end
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing CPT file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}

	// QUERY Layer: The "Data API" for both Admin and Frontend
	// Load order is non-negotiable: helpers → shared → jurisdiction.
	//   query-helpers.php      — pure utilities (no WP meta reads)
	//   query-shared.php       — cross-CPT sub-array builders (depend on helpers)
	//   query-jurisdiction.php — jurisdiction dataset functions (depend on shared)
	$query_files = [
		'query-helpers', 'query-shared', 'query-jurisdiction',
	];
	foreach ( $query_files as $file ) {
		$path = WS_CORE_PATH . "includes/queries/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing QUERY file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			// Only fires on back-end
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing QUERY file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}

	// TAXONOMY Layer: Must load everywhere — registers CPT taxonomy associations
	// for URL rewriting, REST API, and frontend queries. Seeding functions inside
	// this file run only on admin_init and are self-gating.
	$taxonomy_files = [
		'register-taxonomies', 'register-glossary',
	];
	foreach ( $taxonomy_files as $file ) {
		$path = WS_CORE_PATH . "includes/taxonomies/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing TAXONOMY file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			// Only fires on back-end
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing TAXONOMY file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}


/*
---------------------------------------------------------
2. ADMIN LAYER (Only for Editor/Dashboard)
---------------------------------------------------------
*/
if ( is_admin() ) {

    // LOAD ORDER IS NON-NEGOTIABLE: taxonomies must be registered before any seeder runs.
    // register-taxonomies.php is loaded in the Universal Layer above — it always runs first.
    // jurisdiction-matrix.php seeds ws_jurisdiction terms (including US) first — load before
    // agency, fed-statutes, and assist-org matrices which depend on the US term existing.
	//
	// MATRIX Layer: Loaded from /includes/admin/matrix/
	$matrix_files = [
		'matrix-helpers',      // Shared helpers — must load before all seeders.
		'matrix-jurisdictions', 'matrix-fed-statutes', 'matrix-courts', 'matrix-assist-orgs',
		'matrix-agencies', 'admin-matrix-watch',
	];
	foreach ( $matrix_files as $file ) {
		$path = WS_CORE_PATH . "includes/admin/matrix/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing MATRIX file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing MATRIX file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}
	
    // ACF Layer: Huge memory save by keeping these out of the frontend
    $acf_files = [
        'acf-jurisdictions', 'acf-jx-summaries', 'acf-jx-statutes', 'acf-legal-updates',
        'acf-jx-citations', 'acf-agencies', 'acf-assist-orgs', 'acf-major-edit',
        'acf-source-verify', 'acf-jx-interpretations', 'acf-references',
        'acf-stamp-fields', 'acf-plain-english-fields',
    ];
	
	foreach ( $acf_files as $file ) {
		$path = WS_CORE_PATH . "includes/acf/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing ACF file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing ACF file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}

    // Admin Tools & Workflow Improvements
	//
	// ADMIN Layer: Loaded from /includes/admin/
	$admin_files = [
		'admin-navigation', // Note: admin-navigation.php MUST load first
		'admin-audit-trail', 'admin-columns', 'admin-feed-monitor',
		'admin-hooks', 'admin-interpretation-metabox', 'admin-citation-metabox', 'admin-url-monitor',
		'admin-major-edit-hook', 'jurisdiction-dashboard', 'admin-health-check',
	];
	foreach ( $admin_files as $file ) {
		$path = WS_CORE_PATH . "includes/admin/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing ADMIN file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
			add_action( 'admin_notices', function() use ( $file ) {
				echo '<div class="notice notice-error"><p>';
				printf(
					'<strong>WhistleblowerShield:</strong> Missing ADMIN file: <code>%s.php</code> — check error log for details.',
					esc_html( $file )
				);
				echo '</p></div>';
			} );
		}
	}
	
    // admin-relationships.php removed — Phase 3.6: relationship model replaced by taxonomy scoping.

}


/*
---------------------------------------------------------
3. ASSEMBLY LAYER - for front-end users only
---------------------------------------------------------
*/
if ( ! is_admin() ) {
	// ASSEMBLY/RENDER Layer: (Only for Public Display)
	// render-general.php  — general-page renderers (footer, disclaimer, legal updates, jx index)
	// render-section.php  — jurisdiction-page section renderers (header, summary, statutes, etc.)
	// render-jurisdiction.php — the_content assembler that stitches shortcodes together
	// render-directory.php — Directory page stub (no render functions yet)
	$render_files = [
		'render-general', 'render-section', 'render-jurisdiction', 'render-directory',
	];
	foreach ( $render_files as $file ) {
		$path = WS_CORE_PATH . "includes/render/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing ASSEMBLY/RENDER file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
		}
	}
	// ASSEMBLY/SHORTCODES Layer: (Used internally by the Assembler)
	$shortcode_files = [
		'shortcodes-jurisdiction', 'shortcodes-general',
	];
	foreach ( $shortcode_files as $file ) {
		$path = WS_CORE_PATH . "includes/shortcodes/{$file}.php";
		if ( file_exists( $path ) ) {
			require_once $path;
		} else {
			error_log( sprintf(
				'[ws-core] Missing ASSEMBLY/SHORTCODES file: %s (expected at %s, referenced from %s line %d)',
				$file . '.php',
				$path,
				__FILE__,
				__LINE__
			) );
		}
	}

}
