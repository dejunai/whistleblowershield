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
 * Version:     3.10.0
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
 *   Applied (v3.6.0): ws_render_jx_case_law() → ws_render_jx_citations(),
 *      ws_shortcode_jx_case_law() → ws_shortcode_jx_citation(),
 *      shortcode tag [ws_jx_case_law] → [ws_jx_citation].
 *      CSS class .ws-case-law intentionally preserved (end-user facing).
 *
 * TAXONOMY CLEANUP + EMPLOYMENT SECTOR (v3.7.0)
 * -----------------------------------------------
 *   1. Deprecated taxonomy registrations removed: ws_remedy_type,
 *      ws_coverage_scope, ws_retaliation_forms — no live data, safe to drop.
 *
 *   2. New taxonomy: ws_employment_sector (flat, ws-assist-org only).
 *      Replaces the ws_aorg_employment_sectors ACF checkbox field.
 *      ACF field converted to taxonomy type with save_terms: 1 so that
 *      term relationships are written via wp_set_object_terms, enabling
 *      tax_query throughout the Phase 2 filter cascade. No meta_query
 *      required at any cascade level.
 *      Seeded with 6 terms: federal-employee, state-local-employee,
 *      private-sector, military-defense, nonprofit-ngo, all-sectors.
 *
 *   3. cpt-jx-statutes.php taxonomies array corrected — removed deprecated
 *      slugs, all current taxonomy slugs confirmed.
 *
 *   4. matrix-assist-orgs.php: sectors slugs remapped to new taxonomy slugs,
 *      wp_set_object_terms() call added, update_post_meta() for sectors removed.
 *      is_nationwide flag corrected: federal-scope-only orgs set to false.
 *
 * COURT MATRIX SPLIT + INTERPRETATION SYSTEM (v3.8.0)
 * -----------------------------------------------------
 *   1. matrix-courts.php renamed to matrix-federal-courts.php. State and
 *      territory courts extracted to new matrix-state-courts.php
 *      ($ws_state_court_matrix). Enables context-aware court select:
 *      federal statute = all courts merged; state statute = state courts only.
 *
 *   2. ws_court_lookup( $court_key ) added to matrix-helpers.php — checks
 *      both $ws_court_matrix and $ws_state_court_matrix, returns entry array
 *      or null. Used by ACF save hook, query layer, metabox, and admin columns
 *      to eliminate duplicated dual-matrix lookup logic.
 *
 *   3. 'other' sentinel added to $ws_court_matrix (ws_jx_codes = '__manual__',
 *      level = 99). Selecting it reveals ws_jx_interp_court_name free-text
 *      field (conditional on court == 'other'). Save hook skips
 *      auto-population of ws_jx_interp_affected_jx for this sentinel.
 *
 *   4. ws_interp_load_court_choices() rewritten: context-aware population of
 *      the court select from saved or URL-param parent statute. Merged matrix
 *      for federal statutes; state matrix only for state statutes.
 *
 *   5. ws_interp_auto_populate_affected_jx() updated to use ws_court_lookup();
 *      handles __manual__ sentinel (skip) and null (SCOTUS = all jx).
 *
 *   6. Dispatcher refactor: ws_handle_jurisdiction_render() reduced to a thin
 *      dispatcher. Curated render logic extracted to ws_render_jx_curated().
 *      ws_render_jx_filtered() signature updated to ( $post, $jx_term_id,
 *      $filter_context ). Phase 2 dispatch hook point added (dormant).
 *
 *   7. ws_render_jx_interpretations() added to render-section.php; wired into
 *      assembler after citations, before limitations. [ws_jx_interpretation]
 *      shortcode added to shortcodes-jurisdiction.php.
 *
 *   8. Section anchors added: id="ws-statutes", id="ws-citations",
 *      id="ws-interpretations" wrapper divs in the assembler.
 *
 *   9. ws_get_reference_page_url() updated to accept $section param for anchor
 *      targeting. [ws_reference_page] shortcode reads $section from URL and
 *      appends #ws-{section} to the back link. Dead ws_ref_approved gate
 *      removed from ws_get_ref_materials() — was silently excluding all refs.
 *
 *  10. court key in ws_get_jx_interpretation_data() resolved to short label
 *      via ws_court_lookup(); 'other' resolves to ws_jx_interp_court_name.
 *
 *  11. admin-columns.php and admin-interpretation-metabox.php updated to use
 *      ws_court_lookup() for label resolution; 'other' shows free-text name.
 *
 *  12. rel= audit: all external links carry rel="noopener noreferrer";
 *      internal same-domain links have no rel attribute.
 *
 *  13. Citation footnote return links upgraded from <span aria-hidden="true">
 *      to <a href="#{prefix}-fn-ref-{n}" aria-label="Return to in-text reference">.
 *
 *  14. [ws_reference_page] fully implemented: two disclaimers, trimmed list,
 *      target="_blank", window.opener JS for tab management.
 *
 *  15. ws_employment_sector taxonomy added to register-taxonomies.php (section
 *      13). Seeder and gate added. Replaces ws_aorg_employment_sectors ACF
 *      checkbox field. acf-assist-orgs.php field converted to taxonomy type.
 *
 * POST-AUDIT PASS (v3.8.1)
 * -------------------------
 * Full-codebase audit (Perplexity-assisted). All Tier 2 functional bugs and
 * Tier 3 code-quality items resolved. Tier 1 design decisions deferred.
 *
 * Functional fixes (Tier 2):
 *   1. PHP 8 fatal: operator precedence in $law_name ternary fixed in
 *      admin-major-edit-hook.php — jx-citation and jx-interpretation CPTs
 *      were reaching an unreachable branch and receiving the wrong name.
 *   2. Output escaping: three sinks in render-section.php wrapped with
 *      wp_kses_post() / esc_attr(); echo $notice in admin-feed-monitor.php
 *      wrapped with wp_kses_post().
 *   3. Race condition: LOCK_EX flag added to file_put_contents() in
 *      admin-feed-monitor.php (ws_feed_monitor_write_staged).
 *   4. Memory: get_posts( -1 ) removed from admin-url-monitor.php,
 *      jurisdiction-dashboard.php, and admin-navigation.php. Replaced with
 *      WP_Query( posts_per_page => 1 / 1000 ) as appropriate.
 *   5. Legal updates cache: single WS_CACHE_LEGAL_UPDATES_SITEWIDE key stores
 *      100 items; requests ≤ 100 served via array_slice(); requests > 100 or
 *      per-jurisdiction calls bypass the cache entirely.
 *   6. ws_presave_needs_review() stash helper added to admin-hooks.php:
 *      captures ws_needs_review value at acf/save_post priority 5 (before ACF
 *      writes at 10) so the priority-20 role gate can restore the pre-save
 *      value for non-admin users rather than reading the already-committed one.
 *   7. WS_MATRIX_SEEDING_IN_PROGRESS define moved outside the foreach loop in
 *      matrix-fed-statutes.php and matrix-agencies.php — was defined inside
 *      the loop so wp_update_post() calls on iterations 2+ did not see it.
 *   8. ACF shadow key added to matrix-assist-orgs.php for ws_aorg_services so
 *      ACF recognizes seeder-written meta without a first-save wipe.
 *   9. wp_date('Y') replaces date('Y') in shortcodes-general.php [ws_footer]
 *      so the copyright year respects the WP timezone setting.
 *
 * Code quality (Tier 3):
 *  10. Dead is_page() condition removed from ws_core_enqueue_assets() —
 *      is_page() is always true when is_singular() is true.
 *  11. Contact email in render-general.php wrapped in
 *      apply_filters( 'ws_contact_email', ... ) — overridable without
 *      editing plugin files.
 *  12. Duplicate ws-agency column hooks in cpt-agencies.php removed.
 *      ws_agency_code column migrated to admin-columns.php alongside all
 *      other CPT column definitions.
 *  13. ws-legal-update menu_position moved from 30 → 25, resolving
 *      collision with ws-assist-org (cpt-assist-orgs.php).
 *  14. ws-core-front.js: version history updated; HTML contract for filter
 *      tab <button> elements documented in comments.
 *  15. loader.php: assembly-layer silent-failure behavior and ACF admin-only
 *      scope (REST API / WP-CLI blind spot) documented in comments.
 *  16. ws_seed_disclosure_taxonomy() in register-taxonomies.php refactored
 *      to use ws_bulk_insert_hierarchical() — now consistent with all other
 *      hierarchical taxonomy seeders.
 *  17. cpt-jx-citations.php docblock updated — removed references to retired
 *      ws_jx_code join, [ws_jx_case_law] shortcode, ws_jx_cite_attach, and
 *      ws_jx_cite_position; replaced with current taxonomy/meta key names.
 *  18. register-glossary.php try/catch/finally block indentation normalized
 *      to consistent single-tab style.
 *
 * Tech debt (Tier 4):
 *  19. feed-staged.json unbounded growth resolved: ws_feed_monitor_poll()
 *      now prunes 'pending' items older than ws_feed_staged_max_age_days
 *      (default 90 days, filterable) on each write. Accepted/rejected items
 *      are still removed immediately on the admin action.
 *  20. Hardcoded 'US' jx_code default in ws_feed_monitor_poll() replaced
 *      with apply_filters( 'ws_feed_monitor_default_jx_code', 'US' ).
 *  21. URL monitor detected timestamp preserved across cron reruns.
 *      admin-url-monitor.php now retains the original 'detected' value for
 *      known warnings/failures — the admin UI shows first-seen date, not
 *      last-confirmed date.
 *
 * v3.9.0 — ws-ag-procedure: Filing Procedures Feature (Phases 1–3)
 * -----------------------------------------------------------------
 * Adds the ws-ag-procedure CPT, the full front-end render pipeline for
 * the "What do I do next?" end-user workflow on agency pages, and statute
 * cross-reference validation for editorial accuracy.
 *
 * Phase 1 — Structure:
 *   1. cpt-ag-procedures.php: registers ws-ag-procedure CPT.
 *      publicly_queryable, has_archive => false, no editor (ACF only).
 *      menu_position 29 (after ws-agency at 28).
 *   2. acf-ag-procedures.php: 17-field group covering procedure identity
 *      (type, jurisdiction, disclosure types), filing details (entry point,
 *      intake URL, phone, identity policy, intake-only flag, deadline days,
 *      deadline clock start, prerequisites), plain English walkthrough
 *      (wysiwyg), mutual exclusivity note, and last-verified date.
 *      Pre-fill hook (acf/load_value) reads ?agency_id= URL param on
 *      auto-draft posts — mirrors the jx-interpretation pre-fill pattern.
 *   3. admin-columns.php: Agency (linked), Type, Disclosure Types, Deadline
 *      columns added for ws-ag-procedure list screen.
 *   4. admin-navigation.php: Agency navigation box updated with Procedures
 *      section — lists procedures with type label, status badge, and
 *      [Add Procedure] button that passes ?agency_id= for pre-fill.
 *   5. acf-stamp-fields.php: ws-ag-procedure added as 9th CPT in location
 *      rules so stamp fields (created_by, created_date, etc.) are attached.
 *
 * Phase 2 — Query + Render:
 *   6. query-agencies.php (new, Universal Layer): ws_get_agency_procedures()
 *      queries published procedures for a given agency. Per-agency transient
 *      cache (ws_agency_procs_{id}, 24h). Invalidated on procedure save.
 *   7. render-agency.php (new, Assembly Layer): ws_handle_agency_render()
 *      intercepts the_content for ws-agency posts and appends a structured
 *      procedures section. Procedures grouped by type: disclosure first,
 *      retaliation second, both last. Each card renders: intake-only warning,
 *      identity policy, filing deadline with clock-start label, entry point,
 *      prerequisites notice, step-by-step walkthrough, mutual exclusivity
 *      note, CTA buttons (intake form URL, direct phone), last-verified date.
 *   8. cpt-ag-procedures.php: exclude_from_search set to false (Phase 2).
 *   9. loader.php: query-agencies added to Universal Layer query array;
 *      render-agency added to Assembly Layer render array.
 *
 * Phase 3 — Statute Cross-Reference:
 *  10. acf-ag-procedures.php: ws_proc_statute_ids relationship field added
 *      (Related Statutes tab). Auto-scoped via ws_proc_scope_statute_picker()
 *      — narrows picker to statutes matching the procedure's own jurisdiction
 *      and disclosure types. Manual taxonomy filter UI also available.
 *  11. query-agencies.php: ws_get_procedures_for_statute() added. Returns
 *      all published procedures that link a given jx-statute via
 *      ws_proc_statute_ids. Cached per statute (ws_statute_procs_{id}, 24h).
 *      Stash+diff cache invalidation pattern handles ACF edit-screen saves
 *      (old and new statute IDs cleared on each save). before_delete_post /
 *      deleted_post pair handles procedure deletion.
 *  12. render-section.php: ws_render_statute_procedures() added. Compact
 *      cross-reference panel rendered below each statute block on the
 *      jurisdiction page — procedure title, agency link, type badge,
 *      deadline badge, intake-only badge. Calls ws_get_procedures_for_statute()
 *      from the shortcodes-jurisdiction.php statute chunk builder.
 *  13. admin-procedure-watch.php (new, Admin Layer): statute link validation
 *      on acf/save_post. Hard mismatch (zero disclosure-type intersection)
 *      demotes to draft and sets ws_proc_stat_flagged. Broad-scope advisory
 *      (no disclosure types set + statute links) writes ws_proc_stat_broad_scope.
 *      wp_insert_post_data publish gate blocks flagged procedures from all
 *      publish paths (quick edit, bulk, REST, programmatic). Admin override
 *      field (field_proc_stat_override) clears the flag and writes an
 *      append-only audit log (ws_proc_stat_override_log) before resetting
 *      to 0. Admin notice on procedure edit screen surfaces both hard-mismatch
 *      and broad-scope advisory states.
 *  14. loader.php: admin-procedure-watch added to Admin Layer file array.
 *
 * Phase 4 — Seeder:
 *  15. register-taxonomies.php: ws-ag-procedure added to ws_jurisdiction and
 *      ws_disclosure_type object_type arrays so ACF save_terms/load_terms
 *      and the seeder's wp_set_object_terms calls resolve correctly.
 *  16. matrix-ag-procedures.php (new, Matrix Layer): seeds 10 procedures
 *      across nine federal agencies (SEC, OSHA, OSC ×2, MSPB, NLRB, CFTC,
 *      IRS, EPA, DOJ). Each procedure sets type, disclosure types, entry
 *      point, intake URL, identity policy, deadline, prerequisites,
 *      walkthrough, exclusivity note. Jurisdiction assigned to US.
 *      Statute cross-references written where applicable. Cache transients
 *      (agency and per-statute) cleared after each record. Gate:
 *      ws_seeded_procedure_matrix / 1.0.0.
 *  17. loader.php: matrix-ag-procedures added to Matrix Layer file array
 *      (between matrix-agencies and admin-matrix-watch).
 *
 * v3.10.0 — ws_procedure_type Taxonomy + Source Verify Coverage
 * --------------------------------------------------------------
 * Converts ws_proc_type ACF select field to a proper taxonomy and closes
 * two omissions in the ws-ag-procedure feature build.
 *
 *   1. register-taxonomies.php: ws_procedure_type taxonomy registered (flat,
 *      ws-ag-procedure only). Three terms: disclosure, retaliation, both.
 *      Replaces ws_proc_type ACF select field. Seeder: ws_seed_proc_type_taxonomy().
 *      Gate: ws_seeded_procedure_type / 1.0.0. Enables tax_query filtering
 *      in the Phase 2 filter cascade.
 *   2. acf-ag-procedures.php: ws_proc_type select field replaced with
 *      ws_procedure_type taxonomy field (radio UI, save_terms: 1,
 *      load_terms: 1). Field name changed from ws_proc_type to
 *      ws_procedure_type to match taxonomy slug.
 *   3. matrix-ag-procedures.php: ws_proc_type removed from scalar meta write.
 *      Procedure type now assigned via wp_set_object_terms( 'ws_procedure_type' ).
 *      Data array key ws_proc_type retained as slug source — resolved to a
 *      taxonomy term by the seeder loop.
 *   4. query-agencies.php: both get_post_meta( 'ws_proc_type' ) reads replaced
 *      with wp_get_object_terms( 'ws_procedure_type' ) in
 *      ws_build_agency_procedure_row() and ws_get_procedures_for_statute().
 *      Returns first term slug as plain string; empty string when unassigned.
 *   5. admin-columns.php: ws-ag-procedure Type column meta read replaced with
 *      wp_get_object_terms( 'ws_procedure_type' ).
 *   6. admin-hooks.php: ws-ag-procedure added to ws_source_verify_post_types().
 *      Omission — matrix-seeded procedures require source verification and
 *      ws_needs_review coverage identical to other seeded CPTs.
 *   7. acf-source-verify.php: ws-ag-procedure added to location rules.
 *      Same omission as above — source verify fields now attach to procedures.
 *
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ─────────────────────────────────────────────────────────────────

define( 'WS_CORE_VERSION', '3.10.0' );
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
// Two CSS files, one JS file:
//
//   ws-core-front-general.css — shortcodes usable on any page type:
//       disclaimer, footer, legal updates, jurisdiction index, directory,
//       reference materials page, ref-materials button, term tooltip.
//       Loaded on all singular posts/pages (is_singular()).
//
//   ws-core-front-jx.css — jurisdiction CPT pages only:
//       header, flag, gov offices, summary, trust badge, sources,
//       footnote indicator, responsive header stack.
//       Loaded only on is_singular('jurisdiction').
//
//   ws-core-front.js — jurisdiction index filter tab logic.
//       Self-exits when .ws-jx-filter-nav is absent, so safe to load
//       alongside general CSS on all singular/page contexts.

add_action( 'wp_enqueue_scripts', 'ws_core_enqueue_assets' );

function ws_core_enqueue_assets() {

    // General styles + JS: any singular post or page.
    // is_page() is always true when is_singular() is true — condition omitted.
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
            true    // Load in footer
        );
    }

    // Jurisdiction-only styles: jurisdiction CPT singles only.
    if ( is_singular( 'jurisdiction' ) ) {

        wp_enqueue_style(
            'ws-core-front-jx',
            WS_CORE_URL . 'ws-core-front-jx.css',
            [ 'ws-core-front-general' ],    // General loads first
            WS_CORE_VERSION
        );
    }
}
