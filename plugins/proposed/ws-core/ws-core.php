<?php
/**
//  !! DEVELOPMENT ONLY — NOT LIVE
// ==================================================================
// This plugin is NOT deployed to a live site. There is NO
// production database. NO user data exists. NO migration
// concerns apply. Architectural changes are free to be
// destructive until this notice is removed.
//
// When this changes: remove this block, audit all @todo items
// flagged "pre-launch only", and run the full testing pass
// documented in project-status.md before activating.
// ===================================================================
//	
//	**THIS PLUGIN USES A QUERY-LAYER. DO NOT USE DIRECT CALLS TO META**
//
//	/includes/queries/query-jurisdiction.php is the query-layer,
//	if it does not return the necessary data, extend or add functions
//	
// ----------------------------------------------------------------------
 *
 * Plugin Name: WhistleblowerShield Core
 * Description: Core architecture for WhistleblowerShield. Proposed replacement
 *              plugin — radical refactor of v2.3.1. Not an upgrade of the live plugin.
 *              Assembles public whistleblower protection pages for 57 U.S. jurisdictions.
 * Version:     3.5.0
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
 *      attach_flag (true_false) + order (number) fields. Flagged records are
 *      editorially curated highlights — typically 3–5 items per section — that
 *      the assembler surfaces on the jurisdiction summary page. This is NOT a
 *      publish/visibility gate. Records without the flag are fully accessible
 *      via taxonomy-driven user queries; the flag only controls what appears
 *      on the curated summary view.
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
 *   8. Trust badge: ws_render_plain_english_reviewed_badge() replaces the removed
 *      legal review badge system. Legal review badge removed entirely.
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
 *
 * ACF KEY NAMING RULES (v3.1.0 sanity pass)
 * ------------------------------------------
 * Established during a consistency audit of all ACF registration files.
 * These rules govern all current and future ACF field key values:
 *
 *   1. No ws_ prefix on field keys. The `field_` prefix is sufficient
 *      namespacing. `field_ws_foo` → `field_foo`.
 *
 *   2. Group keys must be logically descriptive and end with `_metadata`.
 *      `group_ws_foo` → `group_foo_metadata`.
 *      `group_foo_fields` → `group_foo_metadata`.
 *
 *   3. Tab field keys: `_tab` appears only at the end of the key.
 *      `field_tab_foo` or `tab_foo` → `field_foo_tab`.
 *      `field_` prefix is required on all tab keys.
 *
 *   4. Field key = `field_` + meta name with `ws_` prefix stripped.
 *      e.g., name `ws_jx_statute_official_name` → key `field_jx_statute_official_name`.
 *      For fields whose meta name appears in multiple groups (e.g. `ws_attach_flag`,
 *      `ws_display_order`, `ws_ref_materials`), prepend CPT context to disambiguate:
 *      `field_{cpt_context}_{name_without_ws_prefix}`.
 *
 * NOTE: These rules apply to ACF `key` values only — not `name` (meta key),
 * `label`, or any other property. Meta key names are governed separately below.
 *
 * META KEY NAMING RULES (v3.2.0 ws_auto_ pass)
 * -----------------------------------------------
 * Established during a full audit of all ACF field `name` values and their
 * downstream consumers (hook layer, query layer). These rules govern all
 * current and future post meta key values:
 *
 *   1. All custom meta keys carry a ws_ prefix. No bare unprefixed keys.
 *
 *   2. Auto-stamp keys — values written exclusively by hook logic, never
 *      by human input — carry the ws_auto_ prefix:
 *        ws_auto_date_created, ws_auto_last_edited, ws_auto_create_author,
 *        ws_auto_last_edited_author, ws_auto_source_method, ws_auto_source_name,
 *        ws_auto_verified_by, ws_auto_verified_date,
 *        ws_auto_plain_english_by, ws_auto_plain_english_date,
 *        ws_auto_plain_english_reviewed_by.
 *      If a meta key is exclusively system-written, it belongs in this group.
 *
 *   3. Private audit-only keys (no ACF field, never read by render or query
 *      layer) additionally carry a leading underscore per the WordPress
 *      hidden-meta convention: _ws_auto_date_created_gmt, _ws_auto_last_edited_gmt.
 *
 *   4. Content CPT meta keys carry a CPT infix: ws_jx_*, ws_agency_*,
 *      ws_aorg_*, ws_legal_update_*, ws_jx_interp_*, ws_jx_citation_*, etc.
 *
 *   5. Data-type suffixes: _url (URL string), _wysiwyg (rich-text content),
 *      _id (integer foreign key or term ID).
 *
 *   6. Plural vs. singular: PHP source filenames and directory names may be
 *      plural (acf-assist-orgs.php, /admin/matrix/). Meta key infixes, CPT
 *      slugs, and taxonomy slugs are always singular:
 *        ws_aorg_*    (not ws_aorgs_*)
 *        ws_agency_*  (not ws_agencies_*)
 *        ws_jx_*      (not ws_jxs_*)
 *      When in doubt, singular wins at the database layer.
 *
 * DATE STAMP CONVENTION
 * ----------------------
 * All date values written to post meta by plugin code use:
 *
 *   current_time( 'Y-m-d' )   — local site date, date-only (no time component)
 *
 * GMT audit timestamps (hidden _ws_auto_*_gmt keys) use gmdate( 'Y-m-d' ).
 * The full MySQL datetime current_time( 'mysql' ) is reserved for
 * wp_insert_post / wp_update_post post_date arguments only — never for
 * custom meta keys.
 *
 * QUERY LAYER RETURN KEYS (v3.3.2)
 * ----------------------------------
 * The query layer (query-jurisdiction.php) strips all ws_ and ws_auto_
 * meta key prefixes from PHP array return keys. Meta key naming rules
 * above govern what is stored in the database; they do not govern what
 * is exposed through the query layer API.
 *
 * Within each sub-array the keys are scoped to their context and carry
 * no plugin-namespace prefix:
 *
 *   record  — created_by, created_by_name, created_date,
 *              edited_by,  edited_by_name,  edited_date
 *
 *   plain   — has_content, plain_content, written_by, written_by_name,
 *              written_date, is_reviewed, reviewed_by, reviewed_by_name
 *
 *   verify  — source_method, source_name, verified_by, verified_by_name,
 *              verified_date, verify_status, needs_review
 *
 * Top-level CPT-type prefixes (agency_, ao_) are also dropped where the
 * caller is already inside the CPT’s own data array. See the DATASET
 * RETURN FORMAT section in query-jurisdiction.php for the complete
 * per-function key reference.
 *
 * Rationale: the ws_ / ws_auto_ prefix prevents WordPress meta key
 * collisions in wp_postmeta. Inside a PHP return array there is no
 * collision risk, and the prefix adds noise that makes shortcode
 * authoring unnecessarily verbose.
 *
 * ADMIN LAYER UPDATE PASS (v3.4.0)
 * ----------------------------------
 * Coordinated audit of admin-only files:
 *
 *   1. Query-layer comment enforcement: admin files that read post meta
 *      directly (admin-columns.php, admin-hooks.php, admin-url-monitor.php,
 *      admin-interpretation-metabox.php, admin-major-edit-hook.php) now carry
 *      inline comments explaining why direct meta reads are used rather than
 *      the query layer.
 *
 *   2. ACF field key convention enforcement: all field keys corrected to
 *      follow rule 4 above. Files updated: acf-assist-orgs.php
 *      (field_ao_* → field_aorg_*), acf-jx-citations.php,
 *      acf-jx-interpretations.php, acf-jx-statutes.php.
 *
 *   3. Stale meta key fix: ws_ao_additional_languages →
 *      ws_aorg_additional_languages in admin-hooks.php. Stale key caused
 *      the additional-language term sync for ws-assist-org to silently fail.
 *
 *   4. Matrix seeder taxonomy coverage: matrix-fed-statutes.php and
 *      matrix-assist-orgs.php now assign taxonomy terms per record via
 *      ws_matrix_assign_terms(). Both seeders bumped to gate 1.1.0.
 *
 * JX-STATUTE INGEST ALIGNMENT (v3.5.0)
 * --------------------------------------
 * Full refactor of jx-statute ACF fields, query layer, and matrix seeder
 * to support AI-assisted ingest of structured statute data:
 *
 *   1. ACF overhaul (acf-jx-statutes.php 3.5.0): meta key renames
 *      (limit_* → sol_*, burden_of_proof → bop_standard,
 *      exhaustion_required → has_exhaustion), new tabs (Enforcement, Burden
 *      of Proof, Reward, Links), new fields across all tabs, toggle+conditional
 *      pattern applied to sol, tolling, exhaustion, bop, rebuttable, and reward.
 *
 *   2. New taxonomy: ws_employer_defense (flat, jx-statute only). Seeded with
 *      four initial terms. Registered in register-taxonomies.php v3.2.0.
 *
 *   3. Query layer (query-jurisdiction.php 3.5.0): ws_get_jx_statute_data()
 *      return array rebuilt to match new ACF field set. Pre-existing bug fixed:
 *      remedies were read via get_post_meta() and therefore invisible when
 *      assigned by the matrix seeder via wp_set_object_terms().
 *
 *   4. Matrix seeder (matrix-fed-statutes.php 3.2.0): meta key renames applied,
 *      ws_employer_defense taxonomy assignment added, pre-existing
 *      ws_jx_statute_trigger key mismatch corrected. Gate bumped to 1.2.0.
 *
 * RENDER FUNCTION NAMING RULES (v3.6.0)
 * --------------------------------------
 * Established to eliminate the mental translation layer between function
 * names and the data they process. These rules govern all current and
 * future render function names:
 *
 *   1. Render functions must be named after their ingest data type, not
 *      the page section they produce. The data type is unambiguous;
 *      the section name requires context to interpret.
 *      e.g., ws_render_jx_citations()  not  ws_render_jx_case_law()
 *            ws_render_jx_statutes()   not  ws_render_jx_relevant_law()
 *
 *   2. Exception: wrapper functions that compose multiple data types into
 *      a named page region may use a section name, provided the docblock
 *      explicitly lists every data type the function consumes.
 *
 * @todo - fix function names pre-launch
 *
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION', '3.5.0' );
define( 'WS_CORE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WS_CORE_URL',     plugin_dir_url( __FILE__ ) );

// Taxonomy slug for the jurisdiction vocabulary. Used everywhere a taxonomy
// argument is required — wp_get_post_terms(), tax_query, get_term_by(), etc.
define( 'WS_JURISDICTION_TERM_ID', 'ws_jurisdiction' );

// Transient keys for the two jurisdiction-level query caches. Both are
// invalidated together by ws_invalidate_jurisdiction_caches() whenever a
// jurisdiction post is saved or deleted.
define( 'WS_CACHE_ALL_JURISDICTIONS', 'ws_all_jurisdictions_cache' );
define( 'WS_CACHE_JX_INDEX',          'ws_jx_index_cache'          );

// Transient key for the sitewide legal updates cache.
// All sitewide calls ($jx_id = 0) are cached; per-jurisdiction calls are not.
// Invalidated on every ws-legal-update save.
define( 'WS_CACHE_LEGAL_UPDATES_SITEWIDE', 'ws_legal_updates_sitewide' );

// CPT slugs that can carry a reference parent relationship. Statutes,
// citations, and interpretations all support ws_jx_*_ref_id parent linking;
// summaries do not. Used by ws_get_reference_parent_data() to gate lookups.
define( 'WS_REF_PARENT_TYPES', [ 'jx-statute', 'jx-citation', 'jx-interpretation' ] );

// ── Source Method Constants ────────────────────────────────────────────────────
//
// Values written to the ws_auto_source_method meta key. Defined here so they
// are available to all modules — including matrix files that load before
// admin-hooks.php. See admin-hooks.php for the full source method table and
// source_name convention documentation.
//
// The method set is intentionally stable. Prefer adding a new source_name
// under an existing method over introducing a new constant.
define( 'WS_SOURCE_MATRIX_SEED',   'matrix_seed'   );
define( 'WS_SOURCE_AI_ASSISTED',   'ai_assisted'   );
define( 'WS_SOURCE_BULK_IMPORT',   'bulk_import'   );
define( 'WS_SOURCE_FEED_IMPORT',   'feed_import'   );
define( 'WS_SOURCE_HUMAN_CREATED', 'human_created' );

// source_name value auto-assigned to matrix_seed and human_created posts.
// Signals that source and method are the same — no external origin involved.
define( 'WS_SOURCE_NAME_DIRECT', 'Direct' );

// Legal update types that are visible on public-facing pages and jurisdiction
// shortcodes. 'internal' and 'other' are intentionally excluded. When a new
// type is added to the ws_legal_update_type ACF select in acf-legal-updates.php,
// add it here to make it public. The query layer applies this constant
// automatically for all per-jurisdiction calls.
define( 'WS_LEGAL_UPDATE_SUMMARY_TYPES', [ 'statute', 'citation', 'summary', 'interpretation', 'regulation', 'policy' ] );


// ── Activation Hook ───────────────────────────────────────────────────────────
//
// CPTs are registered on 'init', which has not fired when the activation hook
// runs. Calling flush_rewrite_rules() here would flush against incomplete rules.
// Instead, set a flag that ws_core_init() checks on the next admin_init and
// flushes after CPTs are registered.

register_activation_hook( __FILE__, 'ws_core_activate' );

function ws_core_activate() {
    update_option( 'ws_core_flush_rewrite_rules', true );
}

// ── Deactivation Hooks ────────────────────────────────────────────────────────

register_deactivation_hook( __FILE__, 'ws_url_monitor_deactivate' );
register_deactivation_hook( __FILE__, 'ws_feed_monitor_deactivate' );

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

    // Flush rewrite rules once after activation (deferred from activation hook
    // so all CPTs are registered before the flush runs).
    if ( is_admin() && get_option( 'ws_core_flush_rewrite_rules' ) ) {
        flush_rewrite_rules();
        delete_option( 'ws_core_flush_rewrite_rules' );
    }
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
