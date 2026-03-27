<?php
/**
 * admin-procedure-watch.php — Procedure statute link validation + publish gate.
 *
 * Guards against inaccurate statute cross-references on ws-ag-procedure posts.
 * Owns three concerns: mismatch detection, publish gate, admin notice display.
 * Cache invalidation is handled by query-agencies.php (data layer owns cache).
 *
 * DETECTION LOGIC
 * ---------------
 * Hard mismatch: linked jx-statute has zero ws_disclosure_type term intersection
 * with this procedure's ws_proc_disclosure_types.
 *   → sets ws_proc_stat_flagged = 1
 *   → demotes published post to draft
 *   → publish gate blocks all subsequent publish attempts
 *
 * Broad-scope advisory (soft — no demotion): procedure has no disclosure types
 * set AND has statute links. Links cannot be automatically verified.
 *   → sets ws_proc_stat_broad_scope = 1
 *   → admin notice only
 *
 * Statutes with no ws_disclosure_type terms assigned are skipped — the data
 * problem is on the statute side, not the procedure side.
 *
 * ADMIN OVERRIDE FLOW
 * -------------------
 * Admin checks field_proc_stat_override (Admin Review tab) and saves:
 *   wp_insert_post_data fires first — reads $_POST['acf'] directly (before ACF
 *   saves at priority 10) — allows publish through if admin + override set.
 *   acf/save_post (priority 20) fires after — writes override audit log to
 *   ws_proc_stat_override_log, clears mismatch flag, resets override to 0.
 *
 * @package WhistleblowerShield
 * @since   3.9.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 3.9.0   Initial release. Phase 3 of ws-ag-procedure feature build.
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
    // Direct meta and taxonomy reads — acf/save_post context; validation logic that
    // runs during a save cannot route through the query layer's frontend read functions.

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

    // Direct meta read — wp_insert_post_data fires before the post is saved; reading the flag
    // state here to gate publish. Query layer is not appropriate in this filter context.
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
    // Direct meta reads — admin notice display only; query layer is for front-end shortcode rendering.
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
