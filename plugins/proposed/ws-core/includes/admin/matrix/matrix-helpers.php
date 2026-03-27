<?php
/**
 * matrix-helpers.php — Shared utility functions used by matrix seeder files.
 *
 * @package WhistleblowerShield
 * @since   3.4.0
 * @version 3.10.0
 */

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
