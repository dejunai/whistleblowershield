<?php
/**
 * query-jurisdiction.php
 *
 * Jurisdiction Query Layer
 *
 * PURPOSE
 * -------
 * Provides centralized functions for retrieving jurisdiction records and
 * their associated datasets.
 *
 * This file acts as the primary data access layer for the WhistleblowerShield
 * plugin. By consolidating queries here we avoid repeating WP_Query logic
 * throughout the plugin and maintain consistent behavior across shortcodes
 * and templates.
 *
 * ARCHITECTURE
 * ------------
 *
 * jurisdiction (public CPT)
 *      ├── jx-summary       (attach via ws_jurisdiction taxonomy)
 *      ├── jx-statute       (attach_flag + order, ws_jurisdiction taxonomy scope)
 *      ├── jx-citation      (attach_flag + order, ws_jurisdiction taxonomy scope)
 *      └── jx-interpretation (attach_flag + order, ws_jurisdiction taxonomy scope)
 *
 * JURISDICTION IDENTITY
 * ---------------------
 * The canonical two-letter code for each jurisdiction is the slug of its
 * assigned ws_jurisdiction taxonomy term (e.g., 'ca', 'tx', 'us').
 * ws_jx_code meta has been retired. All lookups use taxonomy queries.
 *
 * ws_jx_term_id post meta is written on each jurisdiction post (by the seeder
 * and the save_post_jurisdiction hook below) as a convenience for direct
 * post->term_id lookups without a get_term_by() call at runtime.
 *
 * CACHING
 * -------
 * Transient caching is used for expensive or repeated queries.
 * All jurisdiction transients are invalidated on save_post for the
 * jurisdiction CPT.
 *
 * Transient keys:
 *      ws_id_for_term_{term_id}    — post ID lookup by taxonomy term ID
 *      WS_CACHE_ALL_JURISDICTIONS  — full post object list
 *      WS_CACHE_JX_INDEX           — index data with counts
 *
 * STAMP META KEYS
 * ---------------
 * All CPTs share identical ws_auto_ prefixed stamp meta keys (see ws-core.php
 * META KEY NAMING RULES). The GMT audit keys are private (_ws_auto_*) and are
 * not exposed through the query layer:
 *
 *      ws_auto_date_created        — local date (Y-m-d), written once
 *      ws_auto_create_author       — WP user ID, written once
 *      ws_auto_last_edited         — local date (Y-m-d), written every save
 *      ws_auto_last_edited_author  — WP user ID, written every save (admin-overridable)
 *
 * DATASET RETURN FORMAT
 * ---------------------
 * All dataset functions return a consistent base array. Keys are plain PHP
 * array keys — the ws_ / ws_auto_ meta key prefixes are stripped at this
 * layer and must not reappear downstream.
 *
 *      [
 *          'id'      => int,
 *          'title'   => string,
 *          'url'     => string,
 *          'status'  => string,  // WP post status
 *          'content' => string,  // raw post_content — apply the_content in render layer
 *          'record'  => [
 *              'created_by'      => int,    // WP user ID (ws_auto_create_author)
 *              'created_by_name' => string, // display name resolved from created_by
 *              'created_date'    => string, // Y-m-d local (ws_auto_date_created)
 *              'edited_by'       => int,    // WP user ID (ws_auto_last_edited_author)
 *              'edited_by_name'  => string, // display name resolved from edited_by
 *              'edited_date'     => string, // Y-m-d local (ws_auto_last_edited)
 *          ],
 *          'plain'  => [ ... ],  // CPTs with plain English workflow — see ws_build_plain_english_array()
 *          'verify' => [ ... ],  // all CPTs — see ws_build_source_verify_array()
 *      ]
 *
 * ws_get_jx_statute_data(), ws_get_jx_citation_data(), and
 * ws_get_jx_interpretation_data() return arrays-of-arrays using the same
 * shape, plus an 'is_fed' boolean key. Each may contain two groups
 * (jurisdiction-scoped + federal append).
 *
 * Expand each dataset array as those CPTs are defined.
 *
 * NOTE: Audit trail data (_ws_last_edited_by, _ws_edit_history) is stored
 * in wp_postmeta as private hidden keys and is NOT retrieved through this
 * query layer. Use ws_get_last_editor( $post_id ) and
 * ws_get_edit_history( $post_id ) defined in admin-audit-trail.php instead.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. Removed ws_get_jurisdiction_by_code
 *        in favor of ws_get_id_by_code. Updated field names to match
 *        acf-jurisdiction.php v2.1.0. Added record management fields to
 *        ws_get_jurisdiction_data. Updated dataset stubs to return consistent
 *        base arrays and accept ws_jx_code as input. Consolidated cache
 *        invalidation on save_post.
 * 2.1.1  ws_get_jx_statutes() now returns with Federal Statutes appended to
 *        Statutes of the specified Jurisdiction when !US is called.
 * 2.3.1  All content keys normalized to raw get_post_field('post_content').
 *        Render layer applies the_content filters. ws_get_jx_statutes()
 *        returns array-of-arrays; shape documented above.
 * 3.0.0  Architecture refactor (Phase 3.1+3.2):
 *        ws_jx_code meta retired as join mechanism. All jurisdiction lookups
 *        now use the ws_jurisdiction taxonomy. ws_get_id_by_code() migrated
 *        to taxonomy query; transient cache rekeyed to ws_id_for_term_{term_id}.
 *        ws_get_jurisdiction_data() and ws_get_jurisdiction_index_data() read
 *        jurisdiction code from taxonomy term slug. save_post_jurisdiction hook
 *        updated to clear per-term transient.
 * 3.1.0  Dropped meta_prefix from all stamp meta keys. All dataset functions
 *        now include a 'record' sub-array with unprefixed stamp fields.
 *        Added ws_resolve_display_name() and ws_build_record_array() private
 *        helpers; all datasets return author_name and last_edited_by as
 *        resolved display names. Added ws_build_plain_english_array() helper;
 *        all plain-English-capable CPTs return a 'plain' sub-array including
 *        plain_reviewed_by and plain_reviewed_name. ws_get_jurisdiction_data()
 *        updated to read stamp fields via get_post_meta() with unprefixed keys.
 *        Removed prefixed summarized key reads from ws_get_jx_summary_data();
 *        summarized_by/summarized_date dropped from summary return (jx-summary
 *        carries no has_plain_english toggle). Removed formatted date fields
 *        (fmt_created, fmt_reviewed, fmt_effective) — dates are stored as
 *        Y-m-d; render layer formats for display. ws_get_legal_updates_data()
 *        returns raw effective_date and post_date; formatting removed.
 *        save_post_jurisdiction hook consolidated: cache invalidation and
 *        ws_jx_term_id write combined in one callback.
 * 3.2.0  Legal update system overhaul and field name audit:
 *        - Fixed 8 get_field() calls missing the jx_ infix (ws_gov_portal_url,
 *          ws_executive_url, etc. → ws_jx_gov_portal_url, ws_jx_executive_url, etc.)
 *          to match acf-jurisdictions.php field names.
 *        - ws_jurisdiction_type → ws_jurisdiction_class throughout, matching the
 *          ACF field name as of acf-jurisdictions.php v3.0+.
 *        - ws_get_legal_updates_data(): new $public_only parameter; restricts
 *          results to types in WS_LEGAL_UPDATE_PUBLIC_TYPES when true.
 *        - Jurisdiction filter replaced: meta_query on ws_update_jurisdiction term ID
 *          → tax_query on ws_jurisdiction taxonomy. Requires save_terms=1 (ACF v3.5.0)
 *          and wp_set_post_terms() in the hook (v1.1.0).
 *        - Return array expanded: added post_id, update_date, update_type,
 *          multi_jurisdiction; source_post_id and source_post_type moved from
 *          get_post_field() to get_post_meta() (correct function for custom meta).
 *        - ws_update_source_url renamed from ws_update_source to comply with
 *          project convention that all URL-valued meta keys end in _url.
 *        - Return key renamed summary_html → summary_wysiwyg to accurately
 *          reflect that the field is a wysiwyg ACF type (content is sanitized
 *          via wp_kses_post before return; safe to echo directly).
 * 3.3.0  Dataset completeness pass:
 * 3.3.1  Query layer refactor — double-duty split:
 *         ws_get_term_id_by_code() extracted from ws_get_id_by_code() as a
 *         standalone helper for callers that need only the term ID.
 *         ws_get_id_by_code() refactored to compose it.
 *         ws_get_legal_updates_data() inline term ID lookup replaced with
 *         ws_get_jx_term_id() for consistency with all other dataset functions.
 *        - Added ws_build_source_verify_array() private helper; all dataset
 *          functions now return a 'verify' sub-array covering source_method,
 *          source_name, verified_by, verified_name, verified_date,
 *          verification_status, and needs_review.
 *        - ws_build_plain_english_array() return keys normalized to ws_ prefix
 *          throughout (plain_english_by → ws_auto_plain_english_by, etc.).
 *        - All dataset functions expanded to return every ACF field defined
 *          for the CPT. ws_statute_burden_of_proof_details phantom key removed
 *          (no corresponding ACF field exists — see inline note).
 *        - ws_jx_code intentionally omitted from ws_get_jurisdiction_data()
 *          return — see inline comment.
 * 3.3.2  Return key simplification pass — all dataset and helper functions
 *         updated. The ws_ / ws_auto_ meta key prefixes are no longer
 *         forwarded as PHP array keys; keys are now plain and context-scoped
 *         to their sub-array. See DATASET RETURN FORMAT above for the
 *         canonical key reference. See ws-core.php for the full policy note.
 * 3.3.3  ws_get_legal_updates_data() caching and default-count improvements:
 *         - $count parameter default changed from 5 to 0; function resolves
 *           0 to 100 for sitewide calls and 5 for per-jurisdiction calls.
 *         - Sitewide calls now cached via WS_CACHE_LEGAL_UPDATES_SITEWIDE transient.
 *           Exact result set is stored; no slicing. Invalidated on every legal update save.
 *         - ≥50 threshold and 100-record slice logic later removed (see 3.3.4).
 * 3.3.4  ws_get_legal_updates_data() caching simplified:
 *         - Removed ≥50-record threshold; all sitewide calls are cached regardless of count.
 *         - Removed 100-record fetch-and-slice pattern; cache stores the exact result set.
 *         - $public_only parameter removed; summary-type filtering is now implicit when
 *           $jx_id is provided (uses WS_LEGAL_UPDATE_SUMMARY_TYPES automatically).
 *         - [ws_legal_updates] shortcode attribute renamed jurisdiction → jx.
 * 3.5.0  ws_get_jx_statute_data() rebuilt to match ACF 3.5.0 ingest alignment:
 *        - Renamed keys: limit_value/unit/trigger → sol_*, burden_of_proof →
 *          bop_standard, exhaustion_required → has_exhaustion.
 *        - tolling_notes retired; replaced by tolling_has_details / tolling_details.
 *        - New fields added across Legal Basis, Enforcement, Burden of Proof,
 *          Reward, and Links sections. See acf-jx-statutes.php v3.5.0 for full
 *          field inventory.
 *        - remedies switched from get_post_meta() to get_field() — fixes pre-existing
 *          bug where matrix-seeded remedy terms were invisible to the query layer.
 * 3.6.0  Query layer split into three files:
 *        - query-helpers.php  — pure utilities (ws_resolve_display_name).
 *        - query-shared.php   — cross-CPT sub-array builders (ws_build_record_array,
 *                               ws_build_plain_english_array, ws_build_source_verify_array).
 *        - query-jurisdiction.php — jurisdiction-specific dataset functions (this file).
 *        Load order is non-negotiable: helpers → shared → jurisdiction.
 * 3.7.0  Added ws_get_nationwide_assist_org_data() — dedicated query function for
 *        the [ws_assist_org_directory] shortcode. Returns all published ws-assist-org
 *        records scoped to the 'us' ws_jurisdiction term, with optional $filters for
 *        type (ws_aorg_type slug), sector (ws_employment_sector slug), stage (ws_case_stage
 *        slug), and cost_model. Sector filtering uses tax_query on ws_employment_sector.
 *        Return shape is identical to ws_get_assist_org_data().
 * 3.8.0  court key in ws_get_jx_interpretation_data() resolved to short label via
 *        ws_court_lookup(); 'other' court key resolves to ws_jx_interp_court_name
 *        free-text value. Dead ws_ref_approved gate removed from ws_get_ref_materials()
 *        — was silently excluding all references. ws_get_reference_page_url() updated
 *        to accept $section param for anchor targeting.
 */


defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Term ID Lookup by Code
//
// Resolves a two-letter USPS code to the ws_jurisdiction taxonomy term ID.
// This is step one of the code → post ID chain and is exposed as its own
// helper so callers that need only the term ID (e.g. to scope a dataset
// query directly) can stop here without the extra post lookup.
//
// Returns the integer term ID, or 0 if the term cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Resolves a two-letter USPS jurisdiction code to its ws_jurisdiction term ID.
 *
 * @param  string $jx_code  Two-letter USPS code (case-insensitive).
 * @return int               Term ID, or 0 if not found.
 */
function ws_get_term_id_by_code( $jx_code ) {

    if ( empty( $jx_code ) ) {
        return 0;
    }

    $term = ws_jx_term_by_code( sanitize_text_field( $jx_code ) );

    if ( ! $term || is_wp_error( $term ) ) {
        return 0;
    }

    return (int) $term->term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction Post ID Lookup by Code
//
// Resolves a two-letter USPS code to the jurisdiction post ID.
// Composes ws_get_term_id_by_code() with a cached tax_query post lookup.
//
// Result is cached in a transient keyed by taxonomy term ID for 24 hours.
// Returns false if the term or jurisdiction post cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Resolves a two-letter USPS jurisdiction code to its jurisdiction post ID.
 *
 * @param  string    $jx_code  Two-letter USPS code (case-insensitive).
 * @return int|false           Post ID, or false if not found.
 */
function ws_get_id_by_code( $jx_code ) {

    $term_id = ws_get_term_id_by_code( $jx_code );

    if ( ! $term_id ) {
        return false;
    }

    $cache_key = 'ws_id_for_term_' . $term_id;
    $post_id   = get_transient( $cache_key );

    if ( false === $post_id ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ],
        ] );

        $post_id = ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
        set_transient( $cache_key, $post_id, DAY_IN_SECONDS );
    }

    return $post_id ?: false;
}


// ════════════════════════════════════════════════════════════════════════════
// Input Resolver
//
// Resolves a mixed $input (numeric post ID or two-letter ws_jx_code string)
// to a jurisdiction post ID integer. Used by all dataset retrieval functions
// to eliminate the repeated is_numeric ternary.
//
// For callers that need a term ID rather than a post ID, use
// ws_get_term_id_by_code() directly.
//
// Returns 0 if input is empty or the code cannot be resolved.
// ════════════════════════════════════════════════════════════════════════════

function ws_resolve_jx_id( $input ) {
    if ( ! $input ) return 0;
    return is_numeric( $input ) ? (int) $input : (int) ws_get_id_by_code( (string) $input );
}


// ════════════════════════════════════════════════════════════════════════════
// Master Jurisdiction Data Fetcher
//
// Accepts either a numeric post ID or a two-letter ws_jx_code string.
// Falls back to the global $post if no input is provided.
//
// Returns a structured array of all jurisdiction metadata, or false if the
// post cannot be resolved or is not a jurisdiction CPT record.
//
// Flag data is retrieved as an array from ACF (return_format: array)
// and destructured here for consistent downstream access.
//
// Record management fields use unprefixed stamp meta keys via get_post_meta(),
// consistent with all other dataset functions.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_data( $input = null ) {

    if ( ! $input ) {
        global $post;
        $post_id = $post->ID ?? 0;
    } else {
        $post_id = ws_resolve_jx_id( $input );
    }

    if ( ! $post_id || get_post_type( $post_id ) !== 'jurisdiction' ) {
        return false;
    }

    $flag     = get_field( 'ws_jx_flag', $post_id );
    $jx_terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY, [ 'fields' => 'slugs' ] );
    $jx_code  = ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) ? strtoupper( $jx_terms[0] ) : '';

    return [

        // ── Identity ─────────────────────────────────────────────────────────
        'id'         => $post_id,
        'name'       => get_the_title( $post_id ),
        'class'      => get_field( 'ws_jurisdiction_class', $post_id ),
        'code'       => $jx_code,
        // ws_jx_term_id is the ws_jurisdiction taxonomy term ID written by
        // the seeder and save_post_jurisdiction hook. Returned here for
        // callers that need the term ID directly without a get_term_by() call.
        'jx_term_id' => (int) get_post_meta( $post_id, 'ws_jx_term_id', true ),
        // ws_jx_code (the ACF field) is intentionally omitted. It is kept
        // in the editor as a read-only display convenience; the canonical
        // USPS code is derived from the ws_jurisdiction taxonomy term slug
        // and is already present as 'code'. Returning the ACF field here
        // would introduce a redundant and potentially stale second source.
        // See the JURISDICTION IDENTITY section in the file header.

        // ── Flag ─────────────────────────────────────────────────────────────
        'flag' => [
            'url'         => ( is_array( $flag ) && ! empty( $flag['url'] ) ) ? $flag['url'] : '',
            'attribution' => get_field( 'ws_jx_flag_attribution', $post_id ),
            'source_url'  => get_field( 'ws_jx_flag_source_url',  $post_id ),
            'license'     => get_field( 'ws_jx_flag_license',     $post_id ),
        ],

        // ── Government Links ─────────────────────────────────────────────────
        'gov' => [
            'portal_url'        => get_field( 'ws_jx_gov_portal_url',     $post_id ),
            'portal_label'      => get_field( 'ws_jx_gov_portal_label',   $post_id ),
            'executive_url'     => get_field( 'ws_jx_executive_url',      $post_id ),
            'executive_label'   => get_field( 'ws_jx_executive_label',    $post_id ),
            'authority_url'     => get_field( 'ws_jx_wb_authority_url',   $post_id ),
            'authority_label'   => get_field( 'ws_jx_wb_authority_label', $post_id ),
            'legislature_url'   => get_field( 'ws_jx_legislature_url',    $post_id ),
            'legislature_label' => get_field( 'ws_jx_legislature_label',  $post_id ),
        ],

        // ── Record Management ─────────────────────────────────────────────────
        // Read via get_post_meta() using unprefixed stamp keys, consistent with
        // all other dataset functions. Stamp values are written by
        // ws_acf_write_stamp_fields() in admin-hooks.php.
        'record' => ws_build_record_array( $post_id ),

    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Summary
//
// Retrieves the jx-summary post assigned to the given ws_jurisdiction term
// and returns a fully-hydrated data array.
//
// jx-summary is inherently plain English. It does not use the has_plain_english
// / plain_reviewed workflow and carries no summarized_by / summarized_date
// stamps.
//
// Returns false if no jx-summary is found for the term.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_summary_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return false;
    }

    $ids = get_posts( [
        'post_type'      => 'jx-summary',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    if ( empty( $ids ) ) {
        return false;
    }

    $sid = (int) $ids[0];

    return [
        'id'            => $sid,
        'title'         => get_the_title( $sid ),
        'url'           => get_permalink( $sid ),
        'status'        => get_post_status( $sid ),
        // Content fields
        'content'       => get_post_meta( $sid, 'ws_jurisdiction_summary_wysiwyg', true ),
        'sources'       => get_post_meta( $sid, 'ws_jx_summary_sources',   true ),
        'limitations'   => get_post_meta( $sid, 'ws_jx_limitations_wysiwyg', true ),
        'notes'         => get_post_meta( $sid, 'ws_jx_summary_notes',        true ),
        // jx-summary is inherently plain English; ws_has_plain_english is
        // implicitly true and no per-record toggle is stored or returned here.
        // Plain language fields
        'plain'         => ws_build_plain_english_array( $sid ),
        // Source & verification
        'verify'        => ws_build_source_verify_array( $sid ),
        // Record management
        'record'        => ws_build_record_array( $sid ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Statutes
//
// Returns the editorially curated jx-statute records for the jurisdiction
// summary page — published records assigned to the given ws_jurisdiction
// taxonomy term that have attach_flag = true, sorted by order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 statutes an editor
// has chosen to highlight on the summary page. All other statutes remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of statute data arrays, or empty array if none found.
//
// Federal append logic: if the requested jurisdiction is not US, US-scoped
// statutes are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_statute_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-statute',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $statute ) {
            $sid    = $statute->ID;
            $rows[] = [
                'id'      => $sid,
                'title'   => get_the_title( $sid ),
                'url'     => get_permalink( $sid ),
                'status'  => get_post_status( $sid ),
                'content' => get_post_field( 'post_content', $sid ),
                'order'   => (int) get_post_meta( $sid, 'ws_display_order', true ),
                'is_fed'  => $is_fed,

                // ── Legal Basis ───────────────────────────────────────────
                'official_name'        => get_post_meta( $sid, 'ws_jx_statute_official_name',       true ),
                'citation'             => get_post_meta( $sid, 'ws_jx_statute_citation',             true ),
                'common_name'          => get_post_meta( $sid, 'ws_jx_statute_common_name',          true ),
                'disclosure_type'      => get_field( 'ws_jx_statute_disclosure_type',      $sid ),
                'protected_class'      => get_field( 'ws_jx_statute_protected_class',      $sid ),
                'disclosure_targets'   => get_field( 'ws_jx_statute_disclosure_targets',   $sid ),
                'adverse_action_scope' => get_post_meta( $sid, 'ws_jx_statute_adverse_action_scope', true ),
                'attach_flag'          => (bool) get_post_meta( $sid, 'ws_attach_flag',              true ),

                // ── Statute of Limitations ────────────────────────────────
                'sol_value'           => get_post_meta( $sid, 'ws_jx_statute_sol_value',           true ),
                'sol_unit'            => get_post_meta( $sid, 'ws_jx_statute_sol_unit',            true ),
                'sol_trigger'         => get_post_meta( $sid, 'ws_jx_statute_sol_trigger',         true ),
                'sol_has_details'     => (bool) get_post_meta( $sid, 'ws_jx_statute_sol_has_details',     true ),
                'sol_details'         => get_post_meta( $sid, 'ws_jx_statute_sol_details',         true ),
                'tolling_has_details' => (bool) get_post_meta( $sid, 'ws_jx_statute_tolling_has_details', true ),
                'tolling_details'     => get_post_meta( $sid, 'ws_jx_statute_tolling_details',     true ),
                'has_exhaustion'      => (bool) get_post_meta( $sid, 'ws_jx_statute_has_exhaustion',      true ),
                'exhaustion_details'  => get_post_meta( $sid, 'ws_jx_statute_exhaustion_details',  true ),

                // ── Enforcement ───────────────────────────────────────────
                'process_type'     => get_field( 'ws_jx_statute_process_type',     $sid ),
                'adverse_action'   => get_field( 'ws_jx_statute_adverse_action',   $sid ),
                'fee_shifting'     => get_field( 'ws_jx_statute_fee_shifting',     $sid ),
                'remedies'         => get_field( 'ws_jx_statute_remedies',         $sid ),
                'related_agencies' => get_field( 'ws_jx_statute_related_agencies', $sid ),

                // ── Burden of Proof ───────────────────────────────────────
                'bop_standard'             => get_post_meta( $sid, 'ws_jx_statute_bop_standard',             true ),
                'employer_defense'         => get_field( 'ws_jx_statute_employer_defense', $sid ),
                'employer_defense_details' => get_post_meta( $sid, 'ws_jx_statute_employer_defense_details', true ),
                'rebuttable_has_details'   => (bool) get_post_meta( $sid, 'ws_jx_statute_rebuttable_has_details', true ),
                'rebuttable_details'       => get_post_meta( $sid, 'ws_jx_statute_rebuttable_details',       true ),
                'bop_has_details'          => (bool) get_post_meta( $sid, 'ws_jx_statute_bop_has_details',   true ),
                'bop_details'              => get_post_meta( $sid, 'ws_jx_statute_bop_details',              true ),

                // ── Reward ────────────────────────────────────────────────
                'has_reward'     => (bool) get_post_meta( $sid, 'ws_jx_statute_has_reward',     true ),
                'reward_details' => get_post_meta( $sid, 'ws_jx_statute_reward_details', true ),

                // ── Links ─────────────────────────────────────────────────
                'statute_url' => get_post_meta( $sid, 'ws_jx_statute_url',        true ),
                'url_is_pdf'  => (bool) get_post_meta( $sid, 'ws_jx_statute_url_is_pdf', true ),

                'last_reviewed' => get_post_meta( $sid, 'ws_jx_statute_last_reviewed', true ),
                'ref_materials' => ws_get_ref_materials( $sid ),

                // Plain language fields
                'plain'  => ws_build_plain_english_array( $sid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $sid ),
                // Record management
                'record' => ws_build_record_array( $sid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Jurisdiction Term ID Helper
//
// Returns the ws_jurisdiction taxonomy term ID assigned to a jurisdiction
// post. Used by shortcodes to resolve the term_id before calling data
// functions without making taxonomy calls inside the shortcode itself.
//
// Returns 0 if the post has no term assigned.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_term_id( $post_id ) {
    $terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return 0;
    }
    return (int) $terms[0]->term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// US Term ID Helper
//
// Returns the ws_jurisdiction taxonomy term ID for the 'us' term.
// Result is cached in a static variable — one DB read per request.
//
// Reads the ws_us_term_id option written during taxonomy seeding.
// Falls back to a get_term_by() lookup if the option is not yet set
// (pre-seed or test environments).
//
// Used by data functions to determine whether federal append logic applies.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_us_term_id() {
    static $us_term_id = null;
    if ( $us_term_id !== null ) {
        return $us_term_id;
    }

    $stored = (int) get_option( 'ws_us_term_id', 0 );
    if ( $stored ) {
        $us_term_id = $stored;
        return $us_term_id;
    }

    $term = ws_jx_term_by_code( 'us' );
    $us_term_id = ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
    return $us_term_id;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Citations
//
// Returns the editorially curated jx-citation records for the jurisdiction
// summary page — published records assigned to the given ws_jurisdiction
// taxonomy term that have attach_flag = true, sorted by order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 citations an editor
// has chosen to highlight on the summary page. All other citations remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of citation arrays, or empty array if none found.
//
// Federal append logic: if the requested jurisdiction is not US, US-scoped
// citations are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_citation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-citation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $citation ) {
            $cid    = $citation->ID;
            $rows[] = [
                'id'      => $cid,
                'title'   => get_the_title( $cid ),
                'url'     => get_permalink( $cid ),
                'status'  => get_post_status( $cid ),
                'content' => get_post_field( 'post_content', $cid ),
                'is_fed'  => $is_fed,
                // Citation-specific fields
                'type'            => get_post_meta( $cid, 'ws_jx_citation_type',          true ),
                'disclosure_type' => get_field( 'ws_jx_citation_disclosure_type', $cid ),
                'official_name'   => get_post_meta( $cid, 'ws_jx_citation_official_name',           true ),
                'common_name'     => get_post_meta( $cid, 'ws_jx_citation_common_name',             true ),
                'label'           => get_post_meta( $cid, 'ws_jx_citation_common_name',           true )
                                   ?: get_post_meta( $cid, 'ws_jx_citation_official_name',             true )
                                   ?: get_the_title( $cid ),
                'cite_url'        => get_post_meta( $cid, 'ws_jx_citation_url',           true ),
                'is_pdf'          => (bool) get_post_meta( $cid, 'ws_jx_citation_is_pdf', true ),
                'attach_flag'     => (bool) get_post_meta( $cid, 'ws_attach_flag',        true ),
                'order'           => (int)  get_post_meta( $cid, 'ws_display_order',      true ),
                'last_reviewed'   => get_post_meta( $cid, 'ws_jx_citation_last_reviewed', true ),
                'ref_materials'   => ws_get_ref_materials( $cid ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $cid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $cid ),
                // Record management
                'record' => ws_build_record_array( $cid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Interpretations
//
// Returns the editorially curated jx-interpretation records for the
// jurisdiction summary page — published records assigned to the given
// ws_jurisdiction taxonomy term that have attach_flag = true, sorted by
// order ASC.
//
// attach_flag is NOT a publish gate. It marks the 3–5 interpretations an
// editor has chosen to highlight on the summary page. All others remain
// fully accessible via taxonomy-driven user queries.
//
// Accepts a taxonomy term ID integer as scope ($jx_term_id).
// Returns an array of interpretation data arrays, or empty array if none found.
//
// Note: interpretations are US federal court decisions. When querying a
// non-US jurisdiction, local records are returned first and US-scoped records
// are appended with is_fed = true.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jx_interpretation_data( $jx_term_id ) {

    $term_id    = (int) $jx_term_id;
    $us_term_id = ws_get_us_term_id();
    if ( ! $term_id ) {
        return [];
    }

    $fetch = function( $tid, $is_fed ) {
        $q = new WP_Query( [
            'post_type'      => 'jx-interpretation',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'ws_display_order',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [ [
                'key'     => 'ws_attach_flag',
                'value'   => '1',
                'compare' => '=',
            ] ],
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $tid,
            ] ],
        ] );
        $rows = [];
        foreach ( $q->posts as $interp ) {
            $iid    = $interp->ID;
            $rows[] = [
                'id'      => $iid,
                'title'   => get_the_title( $iid ),
                'url'     => get_permalink( $iid ),
                'status'  => get_post_status( $iid ),
                'content' => get_post_field( 'post_content', $iid ),
                'order'   => (int) get_post_meta( $iid, 'ws_display_order', true ),
                'is_fed'  => $is_fed,
                // Interpretation-specific fields
                'official_name' => get_post_meta( $iid, 'ws_jx_interp_official_name',            true ),
                'common_name'   => get_post_meta( $iid, 'ws_jx_interp_common_name',              true ),
                'citation'      => get_post_meta( $iid, 'ws_jx_interp_case_citation',    true ),
                'opinion_url'   => get_post_meta( $iid, 'ws_jx_interp_url',              true ),
                'court'         => ( ( $_ck = get_post_meta( $iid, 'ws_jx_interp_court', true ) ) === 'other' )
                                        ? ( get_post_meta( $iid, 'ws_jx_interp_court_name', true ) ?: 'Other' )
                                        : ( ( $crt = ws_court_lookup( $_ck ) ) ? $crt['short'] : $_ck ),
                'year'          => get_post_meta( $iid, 'ws_jx_interp_year',             true ),
                'favorable'     => (bool) get_post_meta( $iid, 'ws_jx_interp_favorable', true ),
                'summary'       => get_post_meta( $iid, 'ws_jx_interp_summary',          true ),
                'parent_statute_id' => (int) get_post_meta( $iid, 'ws_jx_interp_statute_id', true ),
                'process_type'  => get_field( 'ws_process_type', $iid ),
                'attach_flag'   => (bool) get_post_meta( $iid, 'ws_attach_flag',         true ),
                'last_reviewed' => get_post_meta( $iid, 'ws_jx_interp_last_reviewed',    true ),
                'ref_materials' => ws_get_ref_materials( $iid ),
                // Plain language fields
                'plain'  => ws_build_plain_english_array( $iid ),
                // Source & verification
                'verify' => ws_build_source_verify_array( $iid ),
                // Record management
                'record' => ws_build_record_array( $iid ),
            ];
        }
        return $rows;
    };

    $results = $fetch( $term_id, false );

    if ( $us_term_id && $term_id !== $us_term_id ) {
        $results = array_merge( $results, $fetch( $us_term_id, true ) );
    }

    return $results;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Agencies
//
// Returns all published ws-agency records assigned to the given
// ws_jurisdiction taxonomy term, ordered alphabetically.
//
// For jurisdiction pages, pass the US term ID to surface nationwide agencies.
// Records without the term assigned are excluded.
// Returns an array of agency data arrays, or empty array if none found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_agency_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return [];
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-agency',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $agency ) {
        $aid    = $agency->ID;
        $rows[] = [
            'id'            => $aid,
            'title'         => get_the_title( $aid ),
            'url'           => get_permalink( $aid ),
            'status'        => get_post_status( $aid ),
            // Agency fields
            'code'                  => get_post_meta( $aid, 'ws_agency_code',                    true ),
            'name'                  => get_post_meta( $aid, 'ws_agency_name',                    true ),
            'logo'                  => get_field( 'ws_agency_logo', $aid ),
            'disclosure_type'       => get_field( 'ws_agency_disclosure_type', $aid ),
            'process_type'          => get_field( 'ws_process_type', $aid ),
            'website_url'           => get_post_meta( $aid, 'ws_agency_url',                     true ),
            'reporting_url'         => get_post_meta( $aid, 'ws_agency_reporting_url',           true ),
            'phone'                 => get_post_meta( $aid, 'ws_agency_phone',                   true ),
            'confidentiality_notes' => get_post_meta( $aid, 'ws_agency_confidentiality_notes',   true ),
            'anonymous'             => (bool) get_post_meta( $aid, 'ws_agency_accepts_anonymous', true ),
            'reward'                => (bool) get_post_meta( $aid, 'ws_agency_reward_program',    true ),
            'languages'             => get_field( 'ws_languages', $aid ),
            'additional_languages'  => get_post_meta( $aid, 'ws_agency_additional_languages',    true ),
            'last_reviewed'         => get_post_meta( $aid, 'ws_agency_last_reviewed',           true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $aid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $aid ),
            // Record management
            'record' => ws_build_record_array( $aid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Assist Organizations
//
// Returns all published ws-assist-org records assigned to the given
// ws_jurisdiction taxonomy term, ordered alphabetically.
//
// For jurisdiction pages, pass the US term ID to surface nationwide orgs.
// Records without the term assigned are excluded.
// Returns an array of assist-org data arrays, or empty array if none found.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_assist_org_data( $jx_term_id ) {

    $term_id = (int) $jx_term_id;
    if ( ! $term_id ) {
        return [];
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-assist-org',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'tax_query'      => [ [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    $rows = [];
    foreach ( $q->posts as $org ) {
        $oid    = $org->ID;
        $rows[] = [
            'id'            => $oid,
            'title'         => get_the_title( $oid ),
            'url'           => get_permalink( $oid ),
            'status'        => get_post_status( $oid ),
            // Assist-org fields
            'internal_id'          => get_post_meta( $oid, 'ws_aorg_internal_id',               true ),
            'type'                 => ( ( $_aorg_type = get_the_terms( $oid, 'ws_aorg_type' ) ) && ! is_wp_error( $_aorg_type ) ) ? $_aorg_type[0] : null,
            'description'          => get_post_meta( $oid, 'ws_aorg_description',                true ),
            'logo'                 => get_field( 'ws_aorg_logo', $oid ),
            'serves_nationwide'    => (bool) get_post_meta( $oid, 'ws_aorg_serves_nationwide',   true ),
            'disclosure_type'      => get_field( 'ws_aorg_disclosure_type', $oid ),
            'services'             => get_post_meta( $oid, 'ws_aorg_services',                   true ),
            'employment_sectors'   => wp_get_object_terms( $oid, 'ws_employment_sector', [ 'fields' => 'slugs' ] ),
            'website_url'          => get_post_meta( $oid, 'ws_aorg_website_url',                true ),
            'intake_url'           => get_post_meta( $oid, 'ws_aorg_intake_url',                 true ),
            'phone'                => get_post_meta( $oid, 'ws_aorg_phone',                      true ),
            'email'                => get_post_meta( $oid, 'ws_aorg_email',                      true ),
            'mailing_address'      => get_post_meta( $oid, 'ws_aorg_mailing_address',            true ),
            'languages'            => get_field( 'ws_languages', $oid ),
            'additional_languages' => get_post_meta( $oid, 'ws_aorg_additional_languages',       true ),
            'cost_model'           => get_post_meta( $oid, 'ws_aorg_cost_model',                 true ),
            'income_limit'         => get_post_meta( $oid, 'ws_aorg_income_limit',               true ),
            'income_limit_notes'   => get_post_meta( $oid, 'ws_aorg_income_limit_notes',         true ),
            'anonymous'            => (bool) get_post_meta( $oid, 'ws_aorg_accepts_anonymous',   true ),
            'eligibility_notes'    => get_post_meta( $oid, 'ws_aorg_eligibility_notes',          true ),
            'licensed_attorneys'   => (bool) get_post_meta( $oid, 'ws_aorg_licensed_attorneys',  true ),
            'accreditation'        => get_post_meta( $oid, 'ws_aorg_accreditation',              true ),
            'bar_states'           => get_post_meta( $oid, 'ws_aorg_bar_states',                 true ),
            'verify_url'           => get_post_meta( $oid, 'ws_aorg_verify_url',                 true ),
            'last_reviewed'        => get_post_meta( $oid, 'ws_aorg_last_reviewed',              true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $oid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $oid ),
            // Record management
            'record' => ws_build_record_array( $oid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Dataset: Nationwide Assist Organizations (Directory)
//
// Returns all published ws-assist-org records where ws_aorg_serves_nationwide
// is true, ordered alphabetically. Accepts an optional $filters array to
// narrow results before returning.
//
// NATIONWIDE vs FEDERAL-SCOPE
// us jurisdiction tag = federal law scope (federal workers only).
// ws_aorg_serves_nationwide = 1 = org operates across all 57 jurisdictions.
// This function gates on serves_nationwide, not the us jurisdiction term.
// Federal-scope-only orgs (OSC, GAO FraudNet, etc.) have is_nationwide = 0
// and are intentionally excluded here; they surface on jurisdiction pages.
//
// Intended for the [ws_assist_org_directory] shortcode. For jurisdiction-page
// renders, use ws_get_assist_org_data() with the jurisdiction term ID instead.
//
// $filters keys (all optional):
//   'type'       — ws_aorg_type slug (e.g. 'nonprofit', 'legal-aid')
//   'sector'     — ws_employment_sector slug (e.g. 'federal-employee')
//   'stage'      — ws_case_stage slug (e.g. 'pre-report', 'retaliation-active')
//   'cost_model' — cost model value (e.g. 'pro_bono', 'free', 'contingency')
//
// All filtering is performed at the query level via tax_query / meta_query.
// No post-query filtering is used.
//
// Returns an array of assist-org data arrays (identical shape to
// ws_get_assist_org_data()).
// ════════════════════════════════════════════════════════════════════════════

function ws_get_nationwide_assist_org_data( $filters = [] ) {

    $query_args = [
        'post_type'      => 'ws-assist-org',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        // Gate: only orgs that operate across all 57 jurisdictions.
        // Federal-scope-only orgs (is_nationwide = 0) are excluded.
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'ws_aorg_serves_nationwide',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];

    // Optional taxonomy filter: org type.
    if ( ! empty( $filters['type'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_aorg_type',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['type'] ),
        ];
    }

    // Optional taxonomy filter: employment sector.
    if ( ! empty( $filters['sector'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_employment_sector',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['sector'] ),
        ];
    }

    // Optional taxonomy filter: case stage.
    if ( ! empty( $filters['stage'] ) ) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'ws_case_stage',
            'field'    => 'slug',
            'terms'    => sanitize_key( $filters['stage'] ),
        ];
    }

    // Optional meta filter: cost model (scalar string — safe for meta_query).
    if ( ! empty( $filters['cost_model'] ) ) {
        $query_args['meta_query'][] = [
            'key'     => 'ws_aorg_cost_model',
            'value'   => sanitize_text_field( $filters['cost_model'] ),
            'compare' => '=',
        ];
    }

    $q    = new WP_Query( $query_args );
    $rows = [];

    foreach ( $q->posts as $org ) {
        $oid    = $org->ID;
        $rows[] = [
            'id'     => $oid,
            'title'  => get_the_title( $oid ),
            'url'    => get_permalink( $oid ),
            'status' => get_post_status( $oid ),
            // Assist-org fields — identical shape to ws_get_assist_org_data().
            'internal_id'          => get_post_meta( $oid, 'ws_aorg_internal_id',               true ),
            'type'                 => ( ( $_t = get_the_terms( $oid, 'ws_aorg_type' ) ) && ! is_wp_error( $_t ) ) ? $_t[0] : null,
            'description'          => get_post_meta( $oid, 'ws_aorg_description',                true ),
            'logo'                 => get_field( 'ws_aorg_logo', $oid ),
            'serves_nationwide'    => (bool) get_post_meta( $oid, 'ws_aorg_serves_nationwide',   true ),
            'disclosure_type'      => get_field( 'ws_aorg_disclosure_type', $oid ),
            'services'             => get_post_meta( $oid, 'ws_aorg_services',                   true ),
            'employment_sectors'   => wp_get_object_terms( $oid, 'ws_employment_sector', [ 'fields' => 'slugs' ] ),
            'website_url'          => get_post_meta( $oid, 'ws_aorg_website_url',                true ),
            'intake_url'           => get_post_meta( $oid, 'ws_aorg_intake_url',                 true ),
            'phone'                => get_post_meta( $oid, 'ws_aorg_phone',                      true ),
            'email'                => get_post_meta( $oid, 'ws_aorg_email',                      true ),
            'mailing_address'      => get_post_meta( $oid, 'ws_aorg_mailing_address',            true ),
            'languages'            => get_field( 'ws_languages', $oid ),
            'additional_languages' => get_post_meta( $oid, 'ws_aorg_additional_languages',       true ),
            'cost_model'           => get_post_meta( $oid, 'ws_aorg_cost_model',                 true ),
            'income_limit'         => get_post_meta( $oid, 'ws_aorg_income_limit',               true ),
            'income_limit_notes'   => get_post_meta( $oid, 'ws_aorg_income_limit_notes',         true ),
            'anonymous'            => (bool) get_post_meta( $oid, 'ws_aorg_accepts_anonymous',   true ),
            'eligibility_notes'    => get_post_meta( $oid, 'ws_aorg_eligibility_notes',          true ),
            'licensed_attorneys'   => (bool) get_post_meta( $oid, 'ws_aorg_licensed_attorneys',  true ),
            'accreditation'        => get_post_meta( $oid, 'ws_aorg_accreditation',              true ),
            'bar_states'           => get_post_meta( $oid, 'ws_aorg_bar_states',                 true ),
            'verify_url'           => get_post_meta( $oid, 'ws_aorg_verify_url',                 true ),
            'last_reviewed'        => get_post_meta( $oid, 'ws_aorg_last_reviewed',              true ),
            // Plain language fields
            'plain'  => ws_build_plain_english_array( $oid ),
            // Source & verification
            'verify' => ws_build_source_verify_array( $oid ),
            // Record management
            'record' => ws_build_record_array( $oid ),
        ];
    }

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Get All Jurisdictions
//
// Returns a list of all published jurisdiction post objects ordered
// alphabetically by title. Result is cached for 12 hours.
//
// Used for bulk operations and administrative views where full post objects
// are needed. For index display use ws_get_jurisdiction_index_data() which
// includes type counts and structured metadata.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_all_jurisdictions() {

    $cache_key     = WS_CACHE_ALL_JURISDICTIONS;
    $jurisdictions = get_transient( $cache_key );

    if ( false === $jurisdictions ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );

        $jurisdictions = $query->posts;

        // Cache for 12 hours — invalidated on jurisdiction save.
        set_transient( $cache_key, $jurisdictions, 12 * HOUR_IN_SECONDS );
    }

    return $jurisdictions;
}


// ════════════════════════════════════════════════════════════════════════════
// Get Jurisdiction Index Data
//
// Returns a structured array containing all jurisdictions as index items
// plus a count breakdown by type. Used to power the jurisdiction index
// shortcode and any type-filtered display views.
//
// Return shape:
//      [
//          'items'  => [ [ 'name', 'code', 'type', 'url' ], ... ],
//          'counts' => [ 'all', 'state', 'territory', 'district', 'federal' ]
//      ]
//
// Result is cached for 24 hours — invalidated on jurisdiction save.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_jurisdiction_index_data() {

    $cache_key = WS_CACHE_JX_INDEX;
    $cached    = get_transient( $cache_key );

    if ( false === $cached ) {

        $query = new WP_Query( [
            'post_type'      => 'jurisdiction',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );

        $index_items = [];
        $counts      = [
            'all'       => 0,
            'state'     => 0,
            'territory' => 0,
            'district'  => 0,
            'federal'   => 0,
        ];

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {

                $type     = get_field( 'ws_jurisdiction_class', $post->ID ) ?: 'state';
                $jx_slugs = wp_get_post_terms( $post->ID, WS_JURISDICTION_TAXONOMY, [ 'fields' => 'slugs' ] );
                $code     = ( ! is_wp_error( $jx_slugs ) && ! empty( $jx_slugs ) ) ? strtoupper( $jx_slugs[0] ) : '';

                $index_items[] = [
                    'name' => get_the_title( $post->ID ),
                    'code' => $code,
                    'type' => $type,
                    'url'  => get_permalink( $post->ID ),
                ];

                $counts['all']++;
                if ( isset( $counts[ $type ] ) ) {
                    $counts[ $type ]++;
                }
            }
        }

        $cached = [
            'items'  => $index_items,
            'counts' => $counts,
        ];

        // Cache for 24 hours — invalidated on jurisdiction save.
        set_transient( $cache_key, $cached, DAY_IN_SECONDS );
    }

    return $cached;
}


// ════════════════════════════════════════════════════════════════════════════
// ws_get_legal_updates_data( $jx_id );  // jurisdiction summary — auto summary types
// ws_get_legal_updates_data();          // sitewide changelog — auto all types
//
// Returns structured legal update data for the render layer.
//
// @param int  $jx_id       Jurisdiction post ID to scope results. 0 = site-wide.
// @param int  $count       Number of updates to collect, defaults to 100 if !$jx_id,
//                          defaults to 5 if $jx_id
// @return array            Array of data items ready for the render layer.
//
// CACHING
// Sitewide calls ($jx_id = 0) with $count ≤ 100 are served from a single
// 100-item transient (WS_CACHE_LEGAL_UPDATES_SITEWIDE). The result is sliced
// to $count before returning — no per-count keys needed, single delete on save.
// Calls with $count > 100 bypass the cache and query directly.
// Per-jurisdiction calls are never cached.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_legal_updates_data( $jx_id = 0, $count = 0 ) {

	if ( !$count ) {
		$count = $jx_id ? 5 : 100;
	}

    // ── Sitewide cache ────────────────────────────────────────────────────
    // One 100-item transient covers all sitewide requests ≤ 100 — slice on
    // the way out. Requests > 100 skip the cache and query at full count.
    if ( ! $jx_id && $count <= 100 ) {
        $cached = get_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE );
        if ( false !== $cached ) {
            return array_slice( $cached, 0, $count );
        }
    }

    // Always fetch 100 for sitewide cacheable calls so the stored set covers
    // any subsequent request ≤ 100. Fetch exact $count otherwise.
    $fetch_count = ( ! $jx_id && $count <= 100 ) ? 100 : $count;

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => $fetch_count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    if ( $jx_id ) {
        $term_id = ws_get_jx_term_id( $jx_id );
        if ( $term_id ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ];
        }
        // Jurisdiction-scoped calls restrict to summary-safe update types only.
        $query_args['meta_query'] = [ [
            'key'     => 'ws_legal_update_type',
            'value'   => WS_LEGAL_UPDATE_SUMMARY_TYPES,
            'compare' => 'IN',
        ] ];
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return [];
    }

    $items = [];
    foreach ( $updates as $update ) {
        $uid     = $update->ID;
        $items[] = [
            // ── Identity ─────────────────────────────────────────────────
            'id'                 => $uid,
            'title'              => get_the_title( $uid ),

            // ── Dates ────────────────────────────────────────────────────
            'update_date'        => get_post_meta( $uid, 'ws_legal_update_date',             true ),
            'effective_date'     => get_post_meta( $uid, 'ws_legal_update_effective_date',   true ),
            'post_date'          => get_post_field( 'post_date', $uid ),

            // ── Classification ───────────────────────────────────────────
            'type'        => get_post_meta( $uid, 'ws_legal_update_type',                      true ),
            'multi_jurisdiction' => (bool) get_post_meta( $uid, 'ws_legal_update_multi_jurisdiction', true ),

            // ── Content ──────────────────────────────────────────────────
            'law_name'           => get_post_meta( $uid, 'ws_legal_update_law_name',         true ) ?: '',
            'source_url'         => get_post_meta( $uid, 'ws_legal_update_source_url',       true ) ?: '',
            'summary'            => wp_kses_post( get_post_meta( $uid, 'ws_legal_update_summary_wysiwyg', true ) ?: '' ),

            // ── Source post backlink ──────────────────────────────────────
            'source_post_id'     => (int) get_post_meta( $uid, 'ws_legal_update_source_post_id',   true ),
            'source_post_type'   => get_post_meta(       $uid, 'ws_legal_update_source_post_type', true ),

            // ── Source & verification ────────────────────────────────────
            'verify'             => ws_build_source_verify_array( $uid ),

            // ── Record management ────────────────────────────────────────
            'record'             => ws_build_record_array( $uid ),
        ];
    }

    // Cache the full 100-item set for sitewide cacheable calls.
    if ( ! $jx_id && $count <= 100 ) {
        set_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE, $items, HOUR_IN_SECONDS );
        return array_slice( $items, 0, $count );
    }

    return $items;
}

// ════════════════════════════════════════════════════════════════════════════
// Reference Materials
//
// ws_get_ref_materials( $post_id )
//     Returns an array of ws-reference items linked to a parent post via
//     the ws_ref_materials ACF relationship field. All linked references
//     are returned — the approval gate (ws_ref_approved) was retired in
//     v3.4.0; editors are trusted users and the parent record's review
//     workflow is the quality gate.
//     Returns [] if no references are linked.
//
// ws_get_reference_page_data( $parent_post_id )
//     Builds the full data payload for the [ws_reference_page] shortcode.
//     Returns null if the post type is not jx-statute, jx-citation, or
//     jx-interpretation. Returns an array with parent_title, parent_url,
//     and references keys otherwise.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_ref_materials( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) return [];

    $refs = get_field( 'ws_ref_materials', $post_id );
    if ( ! is_array( $refs ) || empty( $refs ) ) return [];

    $items = [];
    foreach ( $refs as $ref ) {
        if ( ! ( $ref instanceof WP_Post ) ) continue;
        $rid = $ref->ID;

        $title = get_post_meta( $rid, 'ws_ref_title', true );
        if ( empty( $title ) ) {
            $title = get_the_title( $rid );
        }

        $items[] = [
            'title'       => sanitize_text_field( $title ),
            'url'         => esc_url_raw( get_post_meta( $rid, 'ws_ref_url', true ) ),
            'description' => sanitize_textarea_field( get_post_meta( $rid, 'ws_ref_description', true ) ),
            'type'        => sanitize_text_field( get_post_meta( $rid, 'ws_ref_type', true ) ),
            'source_name' => sanitize_text_field( get_post_meta( $rid, 'ws_ref_source_name', true ) ),
        ];
    }

    return $items;
}

function ws_get_reference_page_data( $parent_post_id ) {
    $parent_post_id = (int) $parent_post_id;
    if ( ! $parent_post_id ) return null;

    $allowed_types = WS_REF_PARENT_TYPES;
    if ( ! in_array( get_post_type( $parent_post_id ), $allowed_types, true ) ) return null;

    return [
        'parent_title' => get_the_title( $parent_post_id ),
        'parent_url'   => get_permalink( $parent_post_id ),
        'references'   => ws_get_ref_materials( $parent_post_id ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Cache Invalidation + ws_jx_term_id Write
//
// Fires on save_post_jurisdiction. Clears all jurisdiction transients to keep
// the list cache, index cache, and per-term ID cache consistent with the
// saved state.
//
// Also writes ws_jx_term_id post meta, providing a direct post->term_id
// mapping for seeders and admin tooling without a get_term_by() call at
// runtime.
// ════════════════════════════════════════════════════════════════════════════

// Invalidate the sitewide legal updates cache whenever any legal update post
// is saved. Single key — all count variants are served from this one transient.
add_action( 'save_post_ws-legal-update', function() {
    delete_transient( WS_CACHE_LEGAL_UPDATES_SITEWIDE );
} );


add_action( 'save_post_jurisdiction', function( $post_id ) {

    // Clear list and index caches.
    delete_transient( WS_CACHE_ALL_JURISDICTIONS );
    delete_transient( WS_CACHE_JX_INDEX );

    // Resolve the assigned ws_jurisdiction term once for both operations.
    $terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        // Clear the per-term ID cache so ws_get_id_by_code() reflects any
        // taxonomy reassignment immediately.
        delete_transient( 'ws_id_for_term_' . $terms[0]->term_id );

        // Write the direct post->term_id mapping.
        update_post_meta( $post_id, 'ws_jx_term_id', $terms[0]->term_id );
    }

} );
