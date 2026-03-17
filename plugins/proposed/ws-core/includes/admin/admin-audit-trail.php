<?php
/**
 * admin-audit-trail.php
 *
 * Tamper-Resistant Audit Trail for ws-core CPTs
 *
 * PURPOSE
 * -------
 * Records immutable audit metadata on every save of any ws-core CPT.
 * Provides a permanent, append-only history of who edited each post
 * and when, independent of WordPress core revision tracking.
 *
 * This file is backend-only. It produces no front-end output.
 *
 * STORAGE
 * -------
 * Two private meta keys are written directly to wp_postmeta.
 * These are NOT ACF fields and are never exposed in the WordPress
 * admin UI or ACF field groups.
 *
 *   _ws_last_edited_by
 *       The WordPress user ID and display name of whoever most
 *       recently saved the post. Overwritten on each save.
 *
 *       Shape: {
 *           user_id      => int,
 *           display_name => string,
 *           timestamp    => string (UTC ISO 8601)
 *       }
 *
 *   _ws_edit_history
 *       An append-only log. Each save appends one entry.
 *       Never overwritten. Grows for the lifetime of the post.
 *
 *       Shape: array of {
 *           user_id      => int,
 *           display_name => string,
 *           timestamp    => string (UTC ISO 8601)
 *       }
 *
 * HOOK PRIORITY
 * -------------
 * Audit trail writes fire on save_post at priority 99 — after ACF
 * has finished writing its own fields — to avoid any race condition
 * with ACF data. This is intentional and should not be changed.
 *
 * AUDITED POST TYPES
 * ------------------
 * Defined in ws_audited_post_types(). Currently covers:
 *
 *   jurisdiction
 *   jx-summary
 *   jx-statutes
 *   jx-citation
 *   jx-interpretation
 *   ws-legal-update
 *   ws-agencies
 *
 * RETRIEVAL
 * ---------
 * Audit data is intentionally NOT routed through the query layer
 * (query-jurisdiction.php), which handles ACF and WP_Query reads.
 * Use the retrieval functions defined at the bottom of this file:
 *
 *   ws_get_last_editor( $post_id )   — returns last edit entry or null
 *   ws_get_edit_history( $post_id )  — returns full history array or []
 *
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 1.8.0  Addendum CPTs renamed to jx-* prefix.
 * 1.9.0  ws-legal-update renamed from legal-update.
 *         jurisdiction-type taxonomy removed.
 * 1.9.2  ws-update renamed to ws-legal-update.
 * 2.1.0  Refactored for ws-core architecture. Renamed from audit-trail.php
 *         to admin-audit-trail.php. Relationship sync logic moved to
 *         admin-relationships.php. Header and inline comments added.
 * 2.3.1  Added jx-citation and ws-agencies to audited post types.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Audited Post Types
//
// Returns the list of CPT slugs that participate in audit trail tracking.
// Add new CPTs here as they are introduced to ws-core.
// ════════════════════════════════════════════════════════════════════════════

function ws_audited_post_types() {
    return [
        'jurisdiction',
        'jx-summary',
        'jx-statute',
        'jx-citation',
		'jx-interpretation',
        'ws-legal-update',
        'ws-agency',
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// Audit Trail: Write on Save
//
// Fires on save_post at priority 99 — after ACF has completed its own
// save cycle — to ensure audit data reflects the final saved state.
//
// Guards:
//   - Skips autosaves
//   - Skips post revisions
//   - Skips non-audited CPTs
//   - Skips saves by users without edit_post capability
//
// Writes:
//   _ws_last_edited_by  — overwritten on every qualifying save
//   _ws_edit_history    — one entry appended on every qualifying save
// ════════════════════════════════════════════════════════════════════════════

add_action( 'save_post', 'ws_record_audit_trail', 99, 2 );

function ws_record_audit_trail( $post_id, $post ) {

    // Skip autosaves — these are not intentional user edits
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Skip revisions — audit trail tracks canonical saves only
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Skip CPTs not enrolled in audit tracking
    if ( ! in_array( $post->post_type, ws_audited_post_types(), true ) ) {
        return;
    }

    // Skip if the current user cannot edit this post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Build the audit entry for this save
    $current_user = wp_get_current_user();
    $entry        = [
        'user_id'      => (int) $current_user->ID,
        'display_name' => sanitize_text_field( $current_user->display_name ),
        'timestamp'    => gmdate( 'c' ), // UTC ISO 8601
    ];

    // Overwrite last editor with the current save's entry
    update_post_meta( $post_id, '_ws_last_edited_by', $entry );

    // Append to the edit history log — never overwrite existing entries
    $history = get_post_meta( $post_id, '_ws_edit_history', true );

    if ( ! is_array( $history ) ) {
        $history = [];
    }

    $history[] = $entry;

    update_post_meta( $post_id, '_ws_edit_history', $history );
}


// ════════════════════════════════════════════════════════════════════════════
// Retrieval: Last Editor
//
// Returns the audit entry for the most recent save of the given post,
// or null if no audit data has been recorded yet.
//
// @param  int        $post_id  The post ID to retrieve audit data for.
// @return array|null           Audit entry array or null.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_last_editor( $post_id ) {
    $data = get_post_meta( (int) $post_id, '_ws_last_edited_by', true );
    return is_array( $data ) ? $data : null;
}


// ════════════════════════════════════════════════════════════════════════════
// Retrieval: Edit History
//
// Returns the full append-only edit history for the given post as an
// array of audit entries, oldest first. Returns an empty array if no
// history has been recorded yet.
//
// @param  int   $post_id  The post ID to retrieve history for.
// @return array           Array of audit entry arrays, or [].
// ════════════════════════════════════════════════════════════════════════════

function ws_get_edit_history( $post_id ) {
    $data = get_post_meta( (int) $post_id, '_ws_edit_history', true );
    return is_array( $data ) ? $data : [];
}
