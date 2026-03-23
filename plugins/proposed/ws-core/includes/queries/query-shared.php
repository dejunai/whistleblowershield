<?php
/**
 * query-shared.php
 *
 * Query Layer — Cross-CPT Sub-Array Builders
 *
 * PURPOSE
 * -------
 * Holds sub-array builder functions that are shared across multiple CPTs.
 * Each function here reads post meta and returns a structured array that
 * is embedded as a named key ('record', 'plain', 'verify') in the return
 * value of every dataset function in query-jurisdiction.php.
 *
 * These functions are CPT-agnostic — they operate on any post ID whose
 * meta keys conform to the ws_auto_ stamp convention. They do not belong
 * in query-jurisdiction.php because they are not jurisdiction-specific,
 * and they do not belong in query-helpers.php because they read WP meta.
 *
 * LOAD ORDER
 * ----------
 * Must be loaded after query-helpers.php (depends on ws_resolve_display_name)
 * and before query-jurisdiction.php (which calls all three functions here).
 *
 * FUNCTIONS
 * ---------
 *   ws_build_record_array()         Builds the 'record' sub-array (authorship stamps).
 *   ws_build_plain_english_array()  Builds the 'plain' sub-array (plain language workflow).
 *   ws_build_source_verify_array()  Builds the 'verify' sub-array (source & verification).
 *
 * RETURN SHAPE REFERENCE
 * ----------------------
 * See query-jurisdiction.php DATASET RETURN FORMAT for the canonical shape
 * of the 'record', 'plain', and 'verify' sub-arrays these functions produce.
 *
 * @package    WhistleblowerShield
 * @since      3.6.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.6.0  Extracted from query-jurisdiction.php as part of query-layer split.
 *        ws_build_record_array(), ws_build_plain_english_array(), and
 *        ws_build_source_verify_array() previously defined in that file.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Build Record Array
//
// Constructs the standard 'record' sub-array from stamp meta.
// Keys: created_by, created_by_name, created_date, edited_date, edited_by, edited_by_name.
// Used by every dataset function to guarantee a consistent return shape.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Builds the standard stamp record sub-array for a given post.
 *
 * @param  int   $post_id  Post ID.
 * @return array
 */
function ws_build_record_array( $post_id ) {

    $create_author_id      = (int) get_post_meta( $post_id, 'ws_auto_create_author',      true );
    $last_edited_author_id = (int) get_post_meta( $post_id, 'ws_auto_last_edited_author', true );

    return [
        'created_by'      => $create_author_id,
        'created_by_name' => ws_resolve_display_name( $create_author_id ),
        'created_date'    => get_post_meta( $post_id, 'ws_auto_date_created', true ),
        'edited_date'     => get_post_meta( $post_id, 'ws_auto_last_edited',  true ),
        'edited_by'       => $last_edited_author_id,
        'edited_by_name'  => ws_resolve_display_name( $last_edited_author_id ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Build Plain English Array
//
// Constructs the standard 'plain' sub-array for CPTs that carry the
// has_plain_english / plain_reviewed workflow fields.
// Keys: has_content, plain_content, written_by, written_by_name, written_date,
//       is_reviewed, reviewed_by, reviewed_by_name, reviewed_date.
//
// jx-summary calls this function but ignores has_plain_english —
// it is inherently plain English and uses the reviewed fields only.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Builds the standard plain English sub-array for a given post.
 *
 * @param  int   $post_id  Post ID.
 * @return array
 */
function ws_build_plain_english_array( $post_id ) {

    $plain_english_reviewed_by_id = (int) get_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by', true );
    $plain_english_by_id          = (int) get_post_meta( $post_id, 'ws_auto_plain_english_by',          true );

    return [
        'has_content'     => (bool) get_post_meta( $post_id, 'ws_has_plain_english',                   true ),
        'plain_content'   => get_post_meta( $post_id, 'ws_plain_english_wysiwyg',                      true ),
        'written_by'      => $plain_english_by_id,
        'written_by_name' => ws_resolve_display_name( $plain_english_by_id ),
        'written_date'    => get_post_meta( $post_id, 'ws_auto_plain_english_date',                    true ),
        'is_reviewed'     => (bool) get_post_meta( $post_id, 'ws_plain_english_reviewed',              true ),
        'reviewed_by'     => $plain_english_reviewed_by_id,
        'reviewed_by_name'=> ws_resolve_display_name( $plain_english_reviewed_by_id ),
        'reviewed_date'   => get_post_meta( $post_id, 'ws_auto_plain_english_reviewed_date',           true ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Build Source Verify Array
//
// Constructs the standard 'verify' sub-array for all CPTs that carry the
// source-verify field group (acf-source-verify.php). Covers provenance,
// review status, and verification workflow fields.
// Keys: source_method, source_name, verified_by, verified_by_name, verified_date,
//       verify_status, needs_review.
//
// ws_auto_verified_by is a WP user ID and is resolved to a display name
// here so the render layer never calls get_userdata() directly.
//
// Does not include the private GMT audit keys (_ws_auto_*), which are
// intentionally excluded from the query layer throughout this plugin.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Builds the standard source-verify sub-array for a given post.
 *
 * @param  int   $post_id  Post ID.
 * @return array
 */
function ws_build_source_verify_array( $post_id ) {

    $verified_by_id = (int) get_post_meta( $post_id, 'ws_auto_verified_by', true );

    return [
        'source_method'    => get_post_meta( $post_id, 'ws_auto_source_method',  true ),
        'source_name'      => get_post_meta( $post_id, 'ws_auto_source_name',    true ),
        'verified_by'      => $verified_by_id,
        'verified_by_name' => ws_resolve_display_name( $verified_by_id ),
        'verified_date'    => get_post_meta( $post_id, 'ws_auto_verified_date',  true ),
        'verify_status'    => get_post_meta( $post_id, 'ws_verification_status', true ),
        'needs_review'     => (bool) get_post_meta( $post_id, 'ws_needs_review', true ),
    ];
}
