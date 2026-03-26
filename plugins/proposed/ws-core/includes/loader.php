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
 *      Universal layer (frontend + admin):
 *      1) CPT definitions
 *      2) Query layer
 *      3) Taxonomies
 *
 *      Admin layer (is_admin() only):
 *      4) Matrix seeders
 *      5) ACF field definitions
 *      6) Admin tools
 *
 *      Frontend layer (! is_admin() only):
 *      7) Rendering helpers
 *      8) Shortcodes
 *
 *
 * IMPORTANT — TAXONOMY TWO-PHASE BEHAVIOUR
 * -----------------------------------------
 * require_once on register-taxonomies.php registers the taxonomy
 * registration functions and hooks them to 'init'. It does NOT
 * immediately make terms available. Actual term seeding fires on
 * 'admin_init' inside self-gating closures. This means:
 *
 *      After require_once:   taxonomy slugs are registered,
 *                            CPT ↔ taxonomy associations exist,
 *                            wp_set_object_terms() will work.
 *
 *      Terms available:      only after admin_init fires, which
 *                            is after plugins_loaded → loader.php
 *                            has completed. Matrix seeders that call
 *                            ws_jx_term_by_code() are also gated on
 *                            admin_init, so terms are guaranteed to
 *                            exist before any seeder tries to read them.
 *
 *
 * MATRIX LAYER DEPENDENCY CHAIN
 * ------------------------------
 * The matrix load order is non-negotiable. Each level depends on the
 * previous level having run its admin_init gate first:
 *
 *      matrix-helpers.php
 *          Defines ws_matrix_assign_terms() and other shared utilities.
 *          No dependencies. Must load before every seeder.
 *
 *      matrix-jurisdictions.php
 *          Seeds the 57 ws_jurisdiction taxonomy terms (including 'us')
 *          and creates the 57 jurisdiction CPT posts.
 *          ALL other seeders depend on the 'us' term existing.
 *
 *      matrix-fed-statutes.php
 *          Seeds jx-statute posts scoped to the 'us' jurisdiction term.
 *          Depends on: matrix-jurisdictions (us term).
 *          matrix-ag-procedures depends on these posts existing.
 *
 *      matrix-federal-courts.php
 *      matrix-state-courts.php
 *          Define the $ws_court_matrix and $ws_state_court_matrix
 *          in-memory arrays only. NO posts are created. These are
 *          PHP variable definitions — no database writes, no admin_init
 *          gate. They must load before acf-jx-interpretations.php and
 *          query-jurisdiction.php consume ws_court_lookup(), but since
 *          those files are in the ACF and Universal layers respectively,
 *          loading courts here in the matrix block satisfies that need.
 *
 *      matrix-assist-orgs.php
 *          Seeds ws-assist-org posts. Depends on: us term.
 *          No dependency on fed-statutes or agencies — order relative
 *          to those two is arbitrary but kept stable for predictability.
 *
 *      matrix-agencies.php
 *          Seeds ws-agency posts. Depends on: us term.
 *          matrix-ag-procedures depends on these posts existing.
 *
 *      matrix-ag-procedures.php
 *          Seeds ws-ag-procedure posts. Hard dependencies:
 *            — matrix-agencies must have run (resolves agency_slug via
 *              get_page_by_path() against ws-agency posts).
 *            — matrix-fed-statutes must have run (resolves statute_slugs
 *              via ws_procedure_matrix_resolve_statute_ids() against
 *              jx-statute posts).
 *          Must be last post-creating seeder before admin-matrix-watch.
 *
 *      admin-matrix-watch.php
 *          Registers the save_post divergence detection hook and the
 *          dashboard widget. No seeding. Must load last so it does not
 *          flag the seeders above as divergences — the
 *          WS_MATRIX_SEEDING_IN_PROGRESS constant guards this.
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
 * 3.7.0  matrix-state-courts.php added to MATRIX LAYER between
 *        matrix-federal-courts.php and matrix-assist-orgs.php.
 * 3.9.0  cpt-ag-procedures added to CPT LAYER. query-agencies added to QUERY
 *        LAYER. acf-ag-procedures added to ACF LAYER. render-agency added to
 *        ASSEMBLY LAYER. admin-procedure-watch added to ADMIN LAYER.
 *        matrix-ag-procedures added to MATRIX LAYER (between matrix-agencies
 *        and admin-matrix-watch).
 * 3.10.0 LOADING STRATEGY section expanded with TAXONOMY TWO-PHASE BEHAVIOUR
 *        and MATRIX LAYER DEPENDENCY CHAIN documentation. Matrix layer comment
 *        block replaced with full per-file dependency rationale. Taxonomy layer
 *        inline comment clarified: require_once registers functions only —
 *        terms are not available until admin_init fires. admin-navigation
 *        must-load-first comment expanded to name the shared helper it defines.
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
		'cpt-jx-citations', 'cpt-agencies', 'cpt-ag-procedures', 'cpt-assist-orgs',
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
	// Load order is non-negotiable: helpers → shared → jurisdiction → agencies.
	//   query-helpers.php      — pure utilities (no WP meta reads)
	//   query-shared.php       — cross-CPT sub-array builders (depend on helpers)
	//   query-jurisdiction.php — jurisdiction dataset functions (depend on shared)
	//   query-agencies.php     — agency/procedure dataset functions (depend on shared)
	$query_files = [
		'query-helpers', 'query-shared', 'query-jurisdiction', 'query-agencies',
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
	// for URL rewriting, REST API, and frontend queries.
	//
	// IMPORTANT: require_once here only registers the taxonomy registration
	// functions and hooks them to 'init'. Terms are NOT yet available after
	// this line. Actual term seeding fires on 'admin_init' inside self-gating
	// closures in register-taxonomies.php — well after this loader completes.
	// See TAXONOMY TWO-PHASE BEHAVIOUR in the docblock above.
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

    // MATRIX LAYER LOAD ORDER — NON-NEGOTIABLE
	//
	// See MATRIX LAYER DEPENDENCY CHAIN in the docblock above for the full
	// rationale. Summary of hard constraints:
	//
	//   matrix-helpers        — shared utilities, must be first
	//   matrix-jurisdictions  — seeds 'us' term + jurisdiction posts;
	//                           all other seeders depend on 'us' term
	//   matrix-fed-statutes   — seeds jx-statute posts;
	//                           matrix-ag-procedures depends on these
	//   matrix-federal-courts — IN-MEMORY ONLY ($ws_court_matrix array)
	//   matrix-state-courts   — IN-MEMORY ONLY ($ws_state_court_matrix array)
	//   matrix-assist-orgs    — seeds ws-assist-org posts; depends on us term
	//   matrix-agencies       — seeds ws-agency posts;
	//                           matrix-ag-procedures depends on these
	//   matrix-ag-procedures  — depends on BOTH fed-statutes AND agencies posts
	//   admin-matrix-watch    — divergence detection only; must be last
	//
	// MATRIX Layer: Loaded from /includes/admin/matrix/
	$matrix_files = [
		'matrix-helpers',      // Shared helpers — must load before all seeders.
		'matrix-jurisdictions', 'matrix-fed-statutes', 'matrix-federal-courts', 'matrix-state-courts',
		'matrix-assist-orgs', 'matrix-agencies', 'matrix-ag-procedures', 'admin-matrix-watch',
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
	
    // ACF Layer: Huge memory save by keeping these out of the frontend.
    // BLIND SPOT: ACF field definitions are not registered in REST API or WP-CLI
    // contexts (both of which have is_admin() === false). REST endpoints will not
    // return ACF fields, and WP-CLI scripts will not have field definitions
    // available. If either context ever needs ACF field data, move the relevant
    // ACF load outside the is_admin() guard or add an explicit is_rest() / is_cli()
    // check here.
    $acf_files = [
        'acf-jurisdictions', 'acf-jx-summaries', 'acf-jx-statutes', 'acf-legal-updates',
        'acf-jx-citations', 'acf-agencies', 'acf-ag-procedures', 'acf-assist-orgs', 'acf-major-edit',
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
		'admin-navigation', // MUST load first — defines ws_get_attached_citation_count()
		                    // which is called by admin-columns.php and jurisdiction-dashboard.php.
		'admin-audit-trail', 'admin-columns', 'admin-feed-monitor',
		'admin-hooks', 'admin-interpretation-metabox', 'admin-citation-metabox', 'admin-url-monitor',
		'admin-major-edit-hook', 'admin-procedure-watch', 'jurisdiction-dashboard', 'admin-health-check',
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
	// render-directory.php — Directory page renderers (card grid, empty state, taxonomy guide stub)
	//
	// NOTE: Missing files in this layer are logged to the error log (see error_log() calls below)
	// but do NOT trigger admin_notices — this block runs only on ! is_admin(), so admin_notices
	// would never fire here. Check the server error log if assembly layer output is silently broken.
	$render_files = [
		'render-general', 'render-section', 'render-jurisdiction', 'render-directory', 'render-agency',
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
