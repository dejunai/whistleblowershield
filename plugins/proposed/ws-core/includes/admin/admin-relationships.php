<?php
/**
 * admin-relationships.php
 *
 * Two-Way Relationship Synchronization for ws-core CPTs
 *
 * PURPOSE
 * -------
 * Maintains bidirectional consistency between Jurisdiction records
 * and their associated addendum CPTs (jx-summary, jx-statutes,
 * s).
 *
 * This file is backend-only. It produces no front-end output.
 *
 * PROBLEM
 * -------
 * ACF relationship fields are one-directional by default. The
 * relationship between a Jurisdiction and its addenda is defined
 * on the Jurisdiction side — an editor links a jx-* post to a
 * jurisdiction from the Jurisdiction edit screen.
 *
 * Without sync, if a jx-* post is saved independently the parent
 * Jurisdiction's relationship field is not automatically updated,
 * creating data inconsistency.
 *
 * SOLUTION
 * --------
 * Each jx-* ACF field group defines a ws_jurisdiction back-reference
 * field (post_object, post_type: jurisdiction, return_format: id).
 *
 * When a jx-* post is saved, ws_sync_jurisdiction_relationships()
 * reads ws_jurisdiction to identify the parent Jurisdiction, then
 * writes this post's ID into the corresponding relationship field
 * on the parent record.
 *
 * This keeps the relationship consistent regardless of which side
 * it is edited from.
 *
 * HOOK PRIORITY
 * -------------
 * Fires on acf/save_post at priority 20:
 *   - After ACF's default save cycle     (priority 10)
 *   - After ws_autofill_jx_record_fields (priority  5)
 *
 * This ensures all ACF data on the addendum is fully settled
 * before the sync reads from it.
 *
 * RELATIONSHIP MAP
 * ----------------
 * Addendum CPT    → Jurisdiction Relationship Field
 * ────────────────────────────────────────────────
 * jx-summary      → ws_jx_related_summary
 * jx-statute      → ws_jx_related_statutes
 *
 * NOTE: ws-legal-update is NOT included here. Legal Updates use a
 * many-to-many relationship (one update → many jurisdictions) managed
 * entirely from the update side via ws_update_jurisdictions. No
 * back-sync to individual jurisdiction records is needed.
 *
 * NOTE: jx-citation, jx-interpretation are NOT included here. These CPTs
 * use ws_jurisdiction taxonomy with the attach_flag pattern — no ACF
 * relationship field link to maintain on the parent jurisdiction record.
 *
 * NOTE: This entire file is pending removal in Phase 3.6 once Phase 3.5.1
 * confirms the relationship model is fully replaced by taxonomy scoping.
 *
 * @package    WhistleblowerShield
 * @since      2.1.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.1.0  Extracted from admin-audit-trail.php (formerly audit-trail.php).
 *        Back-reference field (ws_jurisdiction) defined on all jx-*
 *        ACF field groups. Sync hook activated.
 * 2.3.1  Added jx-citation exclusion note explaining the ws_jx_code
 *        string-key architecture and why no sync is needed.
 * 3.0.0  Architecture refactor (Phase 3.2): sync migrated from ws_jx_code
 *        string lookup to ws_jurisdiction taxonomy term lookup.
 *        Pending full removal in Phase 3.6.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Relationship Map
//
// Maps each addendum CPT slug to the corresponding ACF relationship field
// on the Jurisdiction CPT. Used by ws_sync_jurisdiction_relationships().
//
// Update this map if new addendum CPTs are added to ws-core.
// ════════════════════════════════════════════════════════════════════════════

function ws_addendum_relationship_map() {
    return [
        'jx-summary' => 'ws_jx_related_summary',
        'jx-statute' => 'ws_jx_related_statutes',
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Two-Way Relationship Sync
//
// When a jx-* addendum post is saved, reads the assigned ws_jurisdiction
// taxonomy term to identify the parent Jurisdiction, then writes this
// addendum's post ID into the corresponding relationship field on the
// Jurisdiction record.
//
// Guards:
//   - Skips CPTs not in the relationship map
//   - Skips if no ws_jurisdiction term is assigned
//   - Skips if the term slug doesn't resolve to a jurisdiction post
//
// NOTE: This entire file is pending removal in Phase 3.6 once the
// relationship model is confirmed fully replaced by taxonomy scoping.
//
// @param int $post_id  The post ID being saved.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_sync_jurisdiction_relationships', 20 );

function ws_sync_jurisdiction_relationships( $post_id ) {

    $post_type = get_post_type( $post_id );
    $map       = ws_addendum_relationship_map();

    // Only act on registered addendum CPTs
    if ( ! array_key_exists( $post_type, $map ) ) {
        return;
    }

    // Read the assigned ws_jurisdiction taxonomy term to identify the parent.
    $terms = wp_get_post_terms( $post_id, 'ws_jurisdiction' );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        // No jurisdiction term assigned — nothing to sync
        return;
    }

    $jurisdiction_id = ws_get_id_by_code( $terms[0]->slug );

    if ( ! $jurisdiction_id ) {
        // Term slug doesn't resolve to a known jurisdiction post
        return;
    }

    // Write this addendum's post ID into the correct relationship field
    // on the parent Jurisdiction record, completing the two-way link
    $relationship_field = $map[ $post_type ];
    update_field( $relationship_field, $post_id, $jurisdiction_id );
}
