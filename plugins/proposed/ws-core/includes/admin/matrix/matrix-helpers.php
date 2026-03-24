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
// @param  string $court_key  The stored ws_jx_interp_court meta value.
// @return array|null         Court entry array, or null if not found.
// ════════════════════════════════════════════════════════════════════════════

function ws_court_lookup( $court_key ) {
    global $ws_court_matrix, $ws_state_court_matrix;
    if ( ! $court_key ) {
        return null;
    }
    if ( ! empty( $ws_court_matrix[ $court_key ] ) ) {
        return $ws_court_matrix[ $court_key ];
    }
    if ( ! empty( $ws_state_court_matrix[ $court_key ] ) ) {
        return $ws_state_court_matrix[ $court_key ];
    }
    return null;
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
