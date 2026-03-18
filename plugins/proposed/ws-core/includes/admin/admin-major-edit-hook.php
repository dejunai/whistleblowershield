<?php
// ════════════════════════════════════════════════════════════════════════════
// Major Edit Logger
//
// Fires on acf/save_post at priority 20 for the four content CPTs that feed
// the ws-legal-update changelog system.
//
// BEHAVIOR
// --------
// If is_major_edit = 1 and major_edit_description is non-empty:
//   1. Creates a ws-legal-update post (published) with auto-stamped metadata.
//   2. Resets is_major_edit and major_edit_description on the source post.
//   3. Queues an admin_notices confirmation.
//
// If is_major_edit = 1 but major_edit_description is empty:
//   1. Resets is_major_edit to 0 on the source post.
//   2. Queues an admin_notices warning — no changelog entry is created.
//      An empty description is worse than no entry at all.
//
// SUPPORTED CPTs
// --------------
// jx-summary, jx-statute, jx-citation, jx-interpretation
//
// AUTO-STAMPED FIELDS ON ws-legal-update
// ---------------------------------------
// post_title                        — "[Source Title] — [CPT Label] Update"
// post_date / post_author           — current time / current user (WP core)
// ws_legal_update_summary           — the description text
// ws_legal_update_effective_date    — today (Y-m-d local)
// ws_legal_update_source_post_id    — source post ID
// ws_legal_update_source_post_type  — source post type slug
// stamp fields (date_created etc.)  — written by ws_acf_write_stamp_fields()
//                                     at priority 20 on the new post's next
//                                     acf/save_post — not triggered here since
//                                     we use wp_insert_post directly. Stamp
//                                     fields are written manually below.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_acf_log_major_edit', 20 );

/**
 * Creates a ws-legal-update changelog entry when is_major_edit is flagged
 * on a supported content CPT save.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_log_major_edit( $post_id ) {

	$supported = [ 'jx-summary', 'jx-statute', 'jx-citation', 'jx-interpretation' ];

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, $supported, true ) ) {
		return;
	}

	$is_major = (int) get_post_meta( $post_id, 'is_major_edit', true );
	if ( ! $is_major ) {
		return;
	}

	$description = trim( (string) get_post_meta( $post_id, 'major_edit_description', true ) );

	// ── Always reset the flag and description field ───────────────────────
	//
	// Reset unconditionally so a second save never double-logs even if the
	// notice was dismissed without being seen.

	update_post_meta( $post_id, 'is_major_edit',            0  );
	update_post_meta( $post_id, 'major_edit_description',   '' );

	// ── Bail with warning if description is empty ─────────────────────────

	if ( $description === '' ) {
		set_transient(
			'ws_major_edit_notice_' . get_current_user_id(),
			'missing_description',
			30
		);
		return;
	}

	// ── Resolve CPT display label for post title ──────────────────────────

	$pt_object  = get_post_type_object( $post_type );
	$pt_label   = $pt_object ? $pt_object->labels->singular_name : $post_type;
	$source_title = get_the_title( $post_id );

	// ── Create the ws-legal-update post ──────────────────────────────────

	$user_id    = get_current_user_id();
	$now_local  = current_time( 'Y-m-d' );
	$now_mysql  = current_time( 'mysql' );
	$now_gmt    = gmdate( 'Y-m-d' );

	$update_id = wp_insert_post( [
		'post_type'   => 'ws-legal-update',
		'post_status' => 'publish',
		'post_title'  => $source_title . ' — ' . $pt_label . ' Update',
		'post_date'   => $now_mysql,
		'post_author' => $user_id,
	], true );

	if ( is_wp_error( $update_id ) ) {
		error_log( sprintf(
			'[ws-core] ws_acf_log_major_edit: wp_insert_post failed for source post %d — %s (in %s line %d)',
			$post_id,
			$update_id->get_error_message(),
			__FILE__,
			__LINE__
		) );
		set_transient(
			'ws_major_edit_notice_' . $user_id,
			'insert_failed',
			30
		);
		return;
	}

	// ── Write meta on the new update post ────────────────────────────────

	update_post_meta( $update_id, 'ws_legal_update_summary',          $description  );
	update_post_meta( $update_id, 'ws_legal_update_effective_date',   $now_local    );
	update_post_meta( $update_id, 'ws_legal_update_source_post_id',   $post_id      );
	update_post_meta( $update_id, 'ws_legal_update_source_post_type', $post_type    );

	// ── Stamp fields (written manually — wp_insert_post bypasses acf/save_post) ──

	update_post_meta( $update_id, 'date_created',      $now_local );
	update_post_meta( $update_id, 'date_created_gmt',  $now_gmt   );
	update_post_meta( $update_id, 'create_author',     $user_id   );
	update_post_meta( $update_id, 'last_edited',       $now_local );
	update_post_meta( $update_id, 'last_edited_gmt',   $now_gmt   );
	update_post_meta( $update_id, 'last_edited_author',$user_id   );

	// ── Queue success notice ──────────────────────────────────────────────

	set_transient(
		'ws_major_edit_notice_' . $user_id,
		'success',
		30
	);
}


// ── Admin notices: major edit outcomes ───────────────────────────────────────

add_action( 'admin_notices', function() {

	$transient = 'ws_major_edit_notice_' . get_current_user_id();
	$state     = get_transient( $transient );

	if ( ! $state ) {
		return;
	}

	delete_transient( $transient );

	if ( $state === 'success' ) {
		echo '<div class="notice notice-success is-dismissible">'
			. '<p><strong>WhistleblowerShield:</strong> '
			. 'Major edit logged — a Legal Updates entry has been created.</p>'
			. '</div>';
		return;
	}

	if ( $state === 'missing_description' ) {
		echo '<div class="notice notice-warning is-dismissible">'
			. '<p><strong>WhistleblowerShield:</strong> '
			. 'Major Edit flag was set but no description was provided. '
			. 'No changelog entry was created. Re-check the flag and add a description before saving again.</p>'
			. '</div>';
		return;
	}

	if ( $state === 'insert_failed' ) {
		echo '<div class="notice notice-error is-dismissible">'
			. '<p><strong>WhistleblowerShield:</strong> '
			. 'Major Edit flag was set but the Legal Updates entry could not be created. '
			. 'Check the error log for details.</p>'
			. '</div>';
	}

} );
