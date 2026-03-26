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
// 3.9.0  ws_court_lookup() and ws_jx_term_by_code() moved to query-helpers.php.
//         Both are called from the Universal Layer (query-jurisdiction.php)
//         and must be available on the frontend, not just in admin.
// ════════════════════════════════════════════════════════════════════════════

if ( ! defined( 'ABSPATH' ) ) exit;


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
