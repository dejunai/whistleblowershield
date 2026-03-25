<?php
/**
 * admin-procedure-watch.php
 *
 * Admin Layer — Procedure Statute Link Validation
 *
 * PURPOSE
 * -------
 * Guards against inaccurate statute cross-references on ws-ag-procedure posts.
 * Procedures are public-facing guidance — a statute link that is wrong or
 * carelessly broad undermines the site's core editorial promise.
 *
 * This file owns three admin concerns only:
 *   1. Mismatch detection + publish demotion (acf/save_post hook)
 *   2. Publish gate (wp_insert_post_data filter)
 *   3. Admin notice display on the procedure edit screen
 *
 * Cache invalidation (statute transients) is handled separately by
 * query-agencies.php — the data layer owns its own cache lifecycle.
 *
 *
 * DETECTION LOGIC
 * ---------------
 * Hard mismatch: a linked jx-statute has zero disclosure-type term
 * intersection with this procedure's ws_proc_disclosure_types.
 *
 * Broad-scope advisory (soft — no demotion): procedure has no disclosure
 * types set AND has statute links. The picker showed everything when the
 * editor made selections — links cannot be automatically verified.
 *
 * Statutes with no ws_disclosure_type terms assigned are skipped by the
 * hard-mismatch check. The data problem is on the statute side; flagging
 * here would point the editor in the wrong direction. Incomplete statute
 * taxonomy data is a separate health-check concern.
 *
 *
 * FLOW: Hard mismatch found
 * -------------------------
 *   acf/save_post (priority 20)
 *     → writes ws_proc_stat_flagged = 1
 *     → writes ws_proc_stat_flag_detail (JSON mismatch list)
 *     → if post_status === 'publish': calls wp_update_post() to demote draft
 *
 *   wp_insert_post_data (all subsequent publish attempts)
 *     → reads ws_proc_stat_flagged
 *     → if set: forces post_status = 'draft'
 *     → exception: admin submits ws_proc_stat_override via ACF edit screen
 *
 *
 * FLOW: Admin override
 * --------------------
 *   Admin checks field_proc_stat_override (Admin Review tab) and saves:
 *
 *   wp_insert_post_data fires first:
 *     → reads $_POST['acf']['field_proc_stat_override'] (set before ACF saves)
 *     → if admin + override submitted: allows publish status through
 *
 *   acf/save_post (priority 20) fires after:
 *     → reads ws_proc_stat_override = 1 from post meta (ACF wrote it at p10)
 *     → writes audit log entry to ws_proc_stat_override_log
 *     → deletes ws_proc_stat_flagged + ws_proc_stat_flag_detail
 *     → resets ws_proc_stat_override to 0 (direct meta write, no ACF cycle)
 *     → returns without mismatch check
 *
 *
 * FLOW: Clean save (no mismatches, no override)
 * ----------------------------------------------
 *   acf/save_post (priority 20):
 *     → runs check, finds no mismatches
 *     → deletes ws_proc_stat_flagged + ws_proc_stat_flag_detail
 *     → deletes ws_proc_stat_broad_scope
 *     → post may publish normally
 *
 *
 * POST META KEYS WRITTEN BY THIS FILE
 * ------------------------------------
 *   ws_proc_stat_flagged       int (0|1)  Hard mismatch flag. Cleared on clean save.
 *   ws_proc_stat_flag_detail   string     JSON array of mismatch entries.
 *   ws_proc_stat_broad_scope   int (0|1)  Soft advisory: no disclosure types + has statute links.
 *   ws_proc_stat_override      int (0|1)  Admin override toggle. Always reset to 0 after save.
 *   ws_proc_stat_override_log  string     JSON append-only audit log of override events.
 *
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.9.0  Initial. Phase 3 of ws-ag-procedure feature build.
 *        Detection, demotion, gate, notice, override, audit log.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Detection + Demotion
//
// Runs after ACF writes all fields (priority 10). Reads the new statute IDs
// and disclosure types from post meta and checks for hard mismatches.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_proc_check_statute_links', 20 );

/**
 * Validates statute links on ws-ag-procedure save. Demotes to draft on mismatch.
 *
 * @param  int|string  $post_id  Post ID from acf/save_post.
 */
function ws_proc_check_statute_links( $post_id ) {

    $post_id = (int) $post_id;

    if ( get_post_type( $post_id ) !== 'ws-ag-procedure' ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // ── Admin override: honour, log, reset, skip check ────────────────────
    //
    // ACF saved ws_proc_stat_override at priority 10. If it is 1 the admin
    // has explicitly acknowledged the mismatch. Clear the flag, write the
    // audit log, reset the override to 0, then return without checking.

    $override = (bool) get_post_meta( $post_id, 'ws_proc_stat_override', true );

    if ( $override && current_user_can( 'manage_options' ) ) {

        // Append to audit log before clearing the flag detail.
        $existing_detail = get_post_meta( $post_id, 'ws_proc_stat_flag_detail', true );
        $log_entry = [
            'user_id'                  => get_current_user_id(),
            'user_name'                => wp_get_current_user()->display_name,
            'timestamp'                => current_time( 'Y-m-d H:i:s' ),
            'mismatches_at_override'   => $existing_detail ? json_decode( $existing_detail, true ) : [],
        ];
        $existing_log = get_post_meta( $post_id, 'ws_proc_stat_override_log', true );
        $log          = $existing_log ? json_decode( $existing_log, true ) : [];
        if ( ! is_array( $log ) ) $log = [];
        $log[] = $log_entry;
        update_post_meta( $post_id, 'ws_proc_stat_override_log', wp_json_encode( $log ) );

        // Clear flag and reset override.
        delete_post_meta( $post_id, 'ws_proc_stat_flagged' );
        delete_post_meta( $post_id, 'ws_proc_stat_flag_detail' );
        delete_post_meta( $post_id, 'ws_proc_stat_broad_scope' );
        update_post_meta( $post_id, 'ws_proc_stat_override', 0 );

        return;
    }

    // ── Read statute IDs and procedure disclosure types ────────────────────

    $statute_ids_raw = get_post_meta( $post_id, 'ws_proc_statute_ids', true );
    $statute_ids     = is_array( $statute_ids_raw ) ? array_map( 'intval', array_filter( $statute_ids_raw ) ) : [];

    // No statute links — clear all flags and return clean.
    if ( empty( $statute_ids ) ) {
        delete_post_meta( $post_id, 'ws_proc_stat_flagged' );
        delete_post_meta( $post_id, 'ws_proc_stat_flag_detail' );
        delete_post_meta( $post_id, 'ws_proc_stat_broad_scope' );
        return;
    }

    $proc_disc_types = wp_get_object_terms( $post_id, 'ws_disclosure_type', [ 'fields' => 'ids' ] );
    if ( is_wp_error( $proc_disc_types ) ) {
        $proc_disc_types = [];
    }

    // ── Broad-scope advisory: no disclosure types + statute links ─────────
    //
    // The auto-scoping hook in acf-ag-procedures.php had no taxonomy scope
    // to filter by — the picker showed everything. Any statute links made
    // under these conditions cannot be automatically verified.

    if ( empty( $proc_disc_types ) ) {
        update_post_meta( $post_id, 'ws_proc_stat_broad_scope', 1 );
    } else {
        delete_post_meta( $post_id, 'ws_proc_stat_broad_scope' );
    }

    // ── Hard mismatch check: disclosure-type intersection per statute ──────
    //
    // Skip statutes with no ws_disclosure_type terms — the incomplete data
    // is a statute-side concern. Flagging the procedure for a statute's
    // missing taxonomy data misdirects the editor.

    $mismatches = [];

    foreach ( $statute_ids as $statute_id ) {

        if ( ! $statute_id ) continue;

        $statute_disc_types = wp_get_object_terms( $statute_id, 'ws_disclosure_type', [ 'fields' => 'ids' ] );

        if ( is_wp_error( $statute_disc_types ) || empty( $statute_disc_types ) ) {
            continue; // Skip: statute has no disclosure types — data incomplete on statute side.
        }

        if ( ! empty( $proc_disc_types ) ) {
            $intersection = array_intersect(
                array_map( 'intval', $proc_disc_types ),
                array_map( 'intval', $statute_disc_types )
            );
            if ( empty( $intersection ) ) {
                $mismatches[] = [
                    'statute_id'    => $statute_id,
                    'statute_title' => get_the_title( $statute_id ),
                    'reason'        => 'disclosure_type_mismatch',
                ];
            }
        }
    }

    // ── Act on results ─────────────────────────────────────────────────────

    if ( ! empty( $mismatches ) ) {

        update_post_meta( $post_id, 'ws_proc_stat_flagged',     1 );
        update_post_meta( $post_id, 'ws_proc_stat_flag_detail', wp_json_encode( $mismatches ) );

        // Demote to draft if currently published.
        // Static flag prevents wp_update_post() from re-triggering this hook.
        if ( 'publish' === get_post_status( $post_id ) ) {
            static $demoting = false;
            if ( ! $demoting ) {
                $demoting = true;
                wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
                $demoting = false;
            }
        }

    } else {

        // Clean save — clear any existing hard-mismatch flag.
        delete_post_meta( $post_id, 'ws_proc_stat_flagged' );
        delete_post_meta( $post_id, 'ws_proc_stat_flag_detail' );

    }
}


// ════════════════════════════════════════════════════════════════════════════
// Publish Gate
//
// Intercepts wp_insert_post_data before any write to the procedures post
// table. Catches publish attempts from quick edit, bulk action, REST API,
// and programmatic wp_publish_post() — contexts where acf/save_post never
// fires and the demotion above cannot run.
//
// Exception: admins submitting the ACF override field in a single step
// (publish + override checked) bypass the gate so the publish goes through.
// The detection hook (priority 20) then clears the flag and resets override.
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'wp_insert_post_data', 'ws_proc_gate_publish', 10, 2 );

/**
 * Prevents flagged ws-ag-procedure posts from being published.
 *
 * @param  array  $data     The post data array about to be written.
 * @param  array  $postarr  The raw submitted post array.
 * @return array
 */
function ws_proc_gate_publish( $data, $postarr ) {

    if ( ( $data['post_type'] ?? '' ) !== 'ws-ag-procedure' ) {
        return $data;
    }

    if ( ( $data['post_status'] ?? '' ) !== 'publish' ) {
        return $data;
    }

    $post_id = (int) ( $postarr['ID'] ?? 0 );
    if ( ! $post_id ) {
        return $data;
    }

    if ( ! get_post_meta( $post_id, 'ws_proc_stat_flagged', true ) ) {
        return $data; // Not flagged — allow publish.
    }

    // Flagged. Check for admin submitting override via the ACF edit screen.
    // $_POST['acf'] is only populated on full ACF form submits, not on
    // quick edit / bulk action / REST / programmatic saves — so non-admin
    // contexts naturally fail this check and are always gated.
    $override_submitted = current_user_can( 'manage_options' )
        && ! empty( $_POST['acf']['field_proc_stat_override'] );  // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( $override_submitted ) {
        return $data; // Admin override acknowledged — allow publish this once.
    }

    // Force draft. The editor will see the admin notice explaining why.
    $data['post_status'] = 'draft';

    return $data;
}


// ════════════════════════════════════════════════════════════════════════════
// Admin Notice
//
// Displays on the procedure edit screen (post.php) when flag meta is set.
// Scoped to ws-ag-procedure only — not a global admin notice.
//
// Hard mismatch: error-level notice listing each flagged statute with title.
// Broad-scope advisory: warning-level notice (no demotion, informational).
// Both may appear simultaneously.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_notices', 'ws_proc_stat_admin_notice' );

/**
 * Renders statute link validation notices on the procedure edit screen.
 */
function ws_proc_stat_admin_notice() {

    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->base || 'ws-ag-procedure' !== $screen->post_type ) {
        return;
    }

    global $post;
    if ( ! $post ) {
        return;
    }

    $post_id    = $post->ID;
    $is_admin   = current_user_can( 'manage_options' );
    $flagged    = (bool) get_post_meta( $post_id, 'ws_proc_stat_flagged',    true );
    $broad      = (bool) get_post_meta( $post_id, 'ws_proc_stat_broad_scope', true );
    $detail_raw = get_post_meta( $post_id, 'ws_proc_stat_flag_detail',       true );
    $mismatches = $detail_raw ? json_decode( $detail_raw, true ) : [];
    if ( ! is_array( $mismatches ) ) $mismatches = [];

    // ── Hard mismatch notice ───────────────────────────────────────────────

    if ( $flagged && ! empty( $mismatches ) ) {
        echo '<div class="notice notice-error">';
        echo '<p><strong>Statute Link Issue — This Procedure Is Saved as a Draft</strong></p>';
        echo '<p>The following statute links do not share a disclosure type with this procedure. ';
        echo 'Publishing is blocked until the issues are resolved or an administrator overrides.</p>';
        echo '<ul>';
        foreach ( $mismatches as $m ) {
            $statute_title = ! empty( $m['statute_title'] ) ? esc_html( $m['statute_title'] ) : 'Statute ID ' . absint( $m['statute_id'] );
            echo '<li><strong>' . $statute_title . '</strong> — disclosure type does not intersect with this procedure\'s categories.</li>';
        }
        echo '</ul>';

        if ( $is_admin ) {
            echo '<p>To resolve: fix the statute\'s or procedure\'s disclosure type taxonomy, then re-save. '
               . 'Alternatively, use the <strong>Admin Review</strong> tab to override if this link is intentionally unconventional.</p>';
        } else {
            echo '<p>To resolve: update the statute\'s or procedure\'s disclosure type category, then re-save. '
               . 'Contact an administrator if the link is correct but the taxonomy data needs adjustment.</p>';
        }

        echo '</div>';
    }

    // ── Broad-scope advisory notice ────────────────────────────────────────

    if ( $broad ) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Statute Links — Scope Advisory</strong></p>';
        echo '<p>This procedure has no Disclosure Types set. The statute picker showed all statutes ';
        echo 'when links were selected — automatic verification is not possible. ';
        echo 'Please confirm each linked statute is specifically applicable to this procedure.</p>';
        echo '</div>';
    }
}
