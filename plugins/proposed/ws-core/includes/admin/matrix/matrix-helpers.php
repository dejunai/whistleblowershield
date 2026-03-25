<?php
// ════════════════════════════════════════════════════════════════════════════
// Matrix Helpers
//
// Shared utility functions used by two or more matrix seeder files.
// This file is loaded first in the matrix load order (loader.php) so all
// seeders can rely on these helpers unconditionally — no function_exists
// guards required at call sites.
//
// LOAD ORDER
// ----------
// matrix-helpers.php   ← this file, first
// matrix-jurisdictions.php
// matrix-fed-statutes.php
// matrix-federal-courts.php
// matrix-state-courts.php
// matrix-assist-orgs.php
// matrix-agencies.php
// admin-matrix-watch.php
//
// VERSION
// -------
// 3.4.0  ws_matrix_assign_terms() extracted here from matrix-assist-orgs.php
//         and matrix-fed-statutes.php (was duplicated inline in each seeder).
// 3.8.0  ws_court_lookup() added — shared dual-matrix lookup used by
//         acf-jx-interpretations.php, query-jurisdiction.php,
//         admin-interpretation-metabox.php, and admin-columns.php.
//         matrix-state-courts.php added to LOAD ORDER.
// 3.8.1  ws_jx_codes values in both matrix files normalized to lowercase
//         (matching ws_jurisdiction taxonomy term slugs). Silent strtolower
//         normalization removed from ws_court_lookup(); replaced with a
//         WP_DEBUG E_USER_WARNING so future uppercase entries are caught
//         in development rather than silently swallowed.
//         Redundant strtolower() removed from acf-jx-interpretations.php
//         consumer — trusts the source data contract.
// ════════════════════════════════════════════════════════════════════════════

if ( ! defined( 'ABSPATH' ) ) exit;


// ════════════════════════════════════════════════════════════════════════════
// ws_court_lookup()
//
// Returns the court entry array for a given court key, checking both
// $ws_court_matrix (federal) and $ws_state_court_matrix (state) in order.
// Returns null if the key is not found in either matrix.
//
// Used by acf-jx-interpretations.php (save hook), query-jurisdiction.php,
// admin-interpretation-metabox.php, and admin-columns.php so that dual-matrix
// lookup is not duplicated across call sites.
//
// ws_jx_codes CONTRACT
// --------------------
// ws_jx_codes values MUST be lowercase in the matrix source files
// (e.g. 'ca', 'tx', 'us') to match ws_jurisdiction taxonomy term slugs.
// This function does NOT silently normalize — it asserts in WP_DEBUG mode
// so typos are caught during development, not silently swallowed.
//
// @param  string $court_key  The stored ws_jx_interp_court meta value.
// @return array|null         Court entry array, or null if not found.
// ════════════════════════════════════════════════════════════════════════════

function ws_court_lookup( $court_key ) {
    global $ws_court_matrix, $ws_state_court_matrix;
    if ( ! $court_key ) {
        return null;
    }
    $court = null;
    if ( ! empty( $ws_court_matrix[ $court_key ] ) ) {
        $court = $ws_court_matrix[ $court_key ];
    } elseif ( ! empty( $ws_state_court_matrix[ $court_key ] ) ) {
        $court = $ws_state_court_matrix[ $court_key ];
    }
    return $court;
}


// ════════════════════════════════════════════════════════════════════════════
// ws_matrix_assign_terms()
//
// Resolves an array of term slugs to term IDs and assigns them to a post
// via wp_set_object_terms(). Silently skips any slug that does not exist
// in the given taxonomy — seeders will not fatal if a term is missing.
//
// @param int    $post_id   Post to assign terms to.
// @param array  $slugs     Term slugs to resolve and assign.
// @param string $taxonomy  Taxonomy slug.
// ════════════════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════════════════
// ws_jx_term_by_code()
//
// Single chokepoint for all ws_jurisdiction term lookups by USPS code.
// Accepts any case — normalises to lowercase internally — but fires an
// E_USER_WARNING in WP_DEBUG so uppercase inputs are caught in development
// and fixed at the source rather than silently corrected at runtime.
//
// Use this everywhere a USPS code needs to resolve to a ws_jurisdiction term.
// For callers that only need the term ID, use ws_get_term_id_by_code() in
// query-jurisdiction.php, which delegates here.
//
// @param  string      $code  USPS jurisdiction code (e.g. 'ca', 'us', 'tx').
// @return WP_Term|false      Term object, or false if not found.
// ════════════════════════════════════════════════════════════════════════════

function ws_jx_term_by_code( $code ) {
    $code = (string) $code;
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $code !== strtolower( $code ) ) {
        trigger_error(
            "ws_jx_term_by_code(): uppercase code '{$code}' — use lowercase to match ws_jurisdiction taxonomy slugs.",
            E_USER_WARNING
        );
    }
    return get_term_by( 'slug', strtolower( $code ), WS_JURISDICTION_TAXONOMY );
}


// ════════════════════════════════════════════════════════════════════════════
// ws_matrix_assign_terms()
// ════════════════════════════════════════════════════════════════════════════

function ws_matrix_assign_terms( $post_id, array $slugs, $taxonomy ) {
    $term_ids = [];
    foreach ( $slugs as $slug ) {
        $term = get_term_by( 'slug', $slug, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            $term_ids[] = (int) $term->term_id;
        }
    }
    if ( ! empty( $term_ids ) ) {
        wp_set_object_terms( $post_id, $term_ids, $taxonomy );
    }
}
