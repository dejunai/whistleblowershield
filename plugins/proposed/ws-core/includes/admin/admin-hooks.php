<?php
/**
 * File: admin-hooks.php
 *
 * Purpose: Handle auto-linking of new addendum posts to their parent
 * Jurisdiction when created via the "Create Now" link in the admin
 * navigation box (admin-navigation.php).
 *
 * When an editor clicks "Create Now" for a missing addendum, the link
 * passes two URL parameters:
 *
 *   ws_parent_id   — the parent jurisdiction post ID
 *   post_title     — a pre-filled title (e.g. "California Summary")
 *
 * This file intercepts the new post screen and pre-populates:
 *   1) The jurisdiction relationship field on the new addendum post
 *   2) The post title
 *
 * FIELD NAMES (ACF relationship field on each addendum CPT):
 *
 *   jx-summary      → ws_jx_sum_jurisdiction
 *   jx-procedures   → ws_procedure_jurisdiction
 *   jx-statutes     → ws_statute_jurisdiction
 *   jx-resources    → ws_resource_jurisdiction
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Fixed field name references to match actual ACF field names
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Pre-populate jurisdiction relationship fields on new addendum screens ─────
//
// Each addendum CPT has its own field name for the jurisdiction relationship.
// We register a filter for each one individually.

$ws_jx_relationship_fields = [
    'ws_jx_sum_jurisdiction',   // jx-summary
    'ws_procedure_jurisdiction', // jx-procedures
    'ws_statute_jurisdiction',   // jx-statutes
    'ws_resource_jurisdiction',  // jx-resources
];

foreach ( $ws_jx_relationship_fields as $field_name ) {
    add_filter(
        'acf/load_field/name=' . $field_name,
        function( $field ) {
            if ( isset( $_GET['ws_parent_id'] ) && is_numeric( $_GET['ws_parent_id'] ) ) {
                $field['default_value'] = [ intval( $_GET['ws_parent_id'] ) ];
            }
            return $field;
        }
    );
}


// ── Pre-populate post title if passed via URL ─────────────────────────────────

add_filter( 'default_title', function( $title ) {
    if ( isset( $_GET['post_title'] ) ) {
        return sanitize_text_field( $_GET['post_title'] );
    }
    return $title;
} );
