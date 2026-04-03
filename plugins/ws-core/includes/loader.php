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
 * Six layers, loaded in strict dependency order:
 *
 *      Universal Layer   — CPTs, taxonomies, ACF field groups, query functions
 *                          Loaded on both frontend and admin.
 *      Matrix Layer      — Idempotent seeders. Admin only.
 *      Admin Layer       — ACF hooks, audit trail, monitoring, dashboard. Admin only.
 *      Assembly Layer    — Render functions + shortcodes → HTML. Frontend only.
 *      Assets            — Conditionally loaded CSS + JS.
 *
 * ASSEMBLY LAYER DEFINITION: render functions + shortcode files only.
 * The query layer is the Universal Layer — a prerequisite of the Assembly
 * Layer, not part of it. Never refer to the query layer as "assembly."
 *
 *
 * DIRECTORY STRUCTURE
 * -------------------
 *
 * includes/
 *      acf/
 *          workflow/       shared field groups (stamp, plain English, source verify, major edit)
 *      admin/
 *          matrix/         seeders and divergence watch
 *          monitors/       URL health monitor, feed monitor
 *      cpt/
 *      queries/
 *      render/
 *      shortcodes/
 *      taxonomies/
 *
 *
 * LOADING STRATEGY
 * ----------------
 *
 * Files are loaded in dependency order:
 *
 *      Universal Layer (frontend + admin):
 *      1) CPT definitions
 *      2) Query layer (helpers → shared → jurisdiction → agencies)
 *      3) Taxonomies
 *
 *      Admin Layer (is_admin() only):
 *      4) Matrix Layer (helpers first, jurisdictions second, all others after)
 *      5) ACF Layer (CPT-specific groups, then workflow/ shared groups)
 *      6) Admin tools (navigation first — defines shared helpers others depend on)
 *      7) Monitors (admin/monitors/)
 *
 *      Assembly Layer (! is_admin() only):
 *      8) Render functions
 *      9) Shortcodes
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
 * 2.1.0   Modular loader introduced.
 * 2.3.1   Taxonomy layer moved to Universal Layer.
 * 2.4.1   Error reporting added to all load calls.
 * 3.0.0   acf-source-verify added to ACF load list.
 * 3.4.0   acf-stamp-fields and acf-plain-english-fields added.
 * 3.6.0   Query layer load order: helpers → shared → jurisdiction.
 * 3.6.1   admin-health-check added.
 * 3.7.0   matrix-state-courts added.
 * 3.9.0   cpt/query/acf/render/admin files for ws-ag-procedure added.
 * 3.10.0  TAXONOMY TWO-PHASE BEHAVIOUR and MATRIX LAYER DEPENDENCY CHAIN
 *         sections added. acf/workflow/ and admin/monitors/ subdirectory
 *         load blocks added.
 * 3.10.2  ws-statute-bold added to render files.
 * 3.13.0  cpt-jx-common-law and acf-jx-common-law added.
 *
 * @package WhistleblowerShield
 * @since   2.1.0
 * @version 3.13.0
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
		'cpt-jx-interpretations', 'cpt-jx-common-law', 'cpt-references',
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

	// CRON SCHEDULES: Must register universally — WP-Cron fires on frontend
	// requests where is_admin() is false and admin-url-monitor.php never loads.
	add_filter( 'cron_schedules', function( $schedules ) {
		$schedules['ws_every_ten_days'] = [
			'interval' => 864000,
			'display'  => 'Once every ten days',
		];
		$schedules['ws_every_three_days'] = [
			'interval' => 259200,
			'display'  => 'Once every three days',
		];
		return $schedules;
	} );
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
    // CPT-specific ACF field groups
    $acf_files = [
        'acf-jurisdictions', 'acf-jx-summaries', 'acf-jx-statutes', 'acf-legal-updates',
        'acf-jx-citations', 'acf-agencies', 'acf-ag-procedures', 'acf-assist-orgs',
        'acf-jx-interpretations', 'acf-jx-common-law', 'acf-references',
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

    // Shared workflow ACF field groups — includes/acf/workflow/
    // Load order: stamp-fields first (plain English stamp writes depend on it).
    $acf_workflow_files = [
        'acf-stamp-fields', 'acf-plain-english-fields',
        'acf-source-verify', 'acf-major-edit',
    ];
    foreach ( $acf_workflow_files as $file ) {
        $path = WS_CORE_PATH . "includes/acf/workflow/{$file}.php";
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( sprintf(
                '[ws-core] Missing ACF WORKFLOW file: %s (expected at %s, referenced from %s line %d)',
                $file . '.php',
                $path,
                __FILE__,
                __LINE__
            ) );
            add_action( 'admin_notices', function() use ( $file ) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    '<strong>WhistleblowerShield:</strong> Missing ACF workflow file: <code>%s.php</code> — check error log for details.',
                    esc_html( $file )
                );
                echo '</p></div>';
            } );
        }
    }

    // Admin Layer — includes/admin/
	$admin_files = [
		'admin-navigation', // MUST load first — defines ws_get_attached_citation_count()
		                    // which is called by admin-columns.php and jurisdiction-dashboard.php.
		'admin-audit-trail', 'admin-columns',
		'admin-hooks', 'admin-interpretation-metabox', 'admin-citation-metabox',
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

    // Monitors — includes/admin/monitors/
    $monitor_files = [
        'admin-url-monitor', 'admin-feed-monitor',
    ];
    foreach ( $monitor_files as $file ) {
        $path = WS_CORE_PATH . "includes/admin/monitors/{$file}.php";
        if ( file_exists( $path ) ) {
            require_once $path;
        } else {
            error_log( sprintf(
                '[ws-core] Missing MONITOR file: %s (expected at %s, referenced from %s line %d)',
                $file . '.php',
                $path,
                __FILE__,
                __LINE__
            ) );
            add_action( 'admin_notices', function() use ( $file ) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    '<strong>WhistleblowerShield:</strong> Missing monitor file: <code>%s.php</code> — check error log for details.',
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
		'render-common-law', 'ws-statute-bold',
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
