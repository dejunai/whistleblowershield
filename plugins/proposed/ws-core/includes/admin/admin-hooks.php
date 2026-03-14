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
 * When an editor clicks "Add Citation", the link passes:
 *
 *   ws_jx_code     — the jurisdiction USPS code (e.g. "CA")
 *
 * This file pre-populates ACF fields on new-post screens so editors
 * do not have to locate and set the relationship manually.
 *
 *
 * FIELD NAMES
 * -----------
 * All four addendum CPTs (jx-summary, jx-procedures, jx-statutes,
 * jx-resources) share the same ACF field name for their jurisdiction
 * back-reference:
 *
 *   ws_jurisdiction   — post_object field on all four addendum CPTs
 *
 * A single acf/load_field filter covers all four CPTs.
 *
 * jx-citation uses a text field instead of a relationship:
 *
 *   ws_jx_code        — text field pre-populated from the query arg
 *
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Fixed field name references to match actual ACF field names
 * 2.3.1  Corrected all four field names to ws_jurisdiction (they were
 *        wrong: ws_jx_sum_jurisdiction, ws_procedure_jurisdiction, etc.
 *        — none of these fields exist; pre-population was silently
 *        broken for all four CPTs).
 *        Collapsed four separate filters into one (same field name).
 *        Added ws_jx_code pre-population for new jx-citation screens.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Pre-populate jurisdiction relationship on new addendum screens ─────────────
//
// All four addendum CPTs use the same field name: ws_jurisdiction.
// One filter covers jx-summary, jx-procedures, jx-statutes, jx-resources.

add_filter( 'acf/load_field/name=ws_jurisdiction', function( $field ) {
    if ( isset( $_GET['ws_parent_id'] ) && is_numeric( $_GET['ws_parent_id'] ) ) {
        $field['default_value'] = intval( $_GET['ws_parent_id'] );
    }
    return $field;
} );


// ── Pre-populate ws_jx_code on new jx-citation screens ───────────────────────
//
// The "Add Citation" button in admin-navigation.php passes ws_jx_code as a
// query arg. Pre-populate the matching ACF text field so editors can confirm
// and save without manually entering the code.

add_filter( 'acf/load_field/name=ws_jx_code', function( $field ) {
    if ( isset( $_GET['ws_jx_code'] ) ) {
        $field['default_value'] = sanitize_text_field( $_GET['ws_jx_code'] );
    }
    return $field;
} );


// ── Pre-populate post title if passed via URL ─────────────────────────────────

add_filter( 'default_title', function( $title ) {
    if ( isset( $_GET['post_title'] ) ) {
        return sanitize_text_field( $_GET['post_title'] );
    }
    return $title;
} );
