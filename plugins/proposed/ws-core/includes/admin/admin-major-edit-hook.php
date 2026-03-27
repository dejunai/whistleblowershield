<?php
/**
 * admin-major-edit-hook.php — Major Edit Logger — creates ws-legal-update posts on flagged saves.
 *
 * @package WhistleblowerShield
 * @since   3.2.0
 * @version 3.10.0
 */

add_action( 'acf/save_post', 'ws_acf_log_major_edit', 20 );

/**
 * Creates a ws-legal-update changelog entry when is_major_edit is flagged
 * on a supported content CPT save.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_log_major_edit( $post_id ) {

	$supported = [ 'jx-summary', 'jx-statute', 'jx-citation', 'jx-interpretation', 'ws-ag-procedure' ];

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, $supported, true ) ) {
		return;
	}

	// Direct meta reads — this hook fires on acf/save_post after ACF has written the fields.
	// Reading directly avoids a second ACF load cycle and is safe in this save context.
	$is_major = (int) get_post_meta( $post_id, 'ws_is_major_edit', true );
	if ( ! $is_major ) {
		return;
	}

	$description = trim( (string) get_post_meta( $post_id, 'ws_major_edit_description', true ) );

	// ── Always reset the flag and description field ───────────────────────
	//
	// Reset unconditionally so a second save never double-logs even if the
	// notice was dismissed without being seen.

	update_post_meta( $post_id, 'ws_is_major_edit',            0  );
	update_post_meta( $post_id, 'ws_major_edit_description',   '' );

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

	update_post_meta( $update_id, 'ws_legal_update_summary_wysiwyg',  $description  );
	update_post_meta( $update_id, 'ws_legal_update_effective_date',   $now_local    );
	update_post_meta( $update_id, 'ws_legal_update_source_post_id',   $post_id      );
	update_post_meta( $update_id, 'ws_legal_update_source_post_type', $post_type    );

	// ── Attach jurisdiction from source post ─────────────────────────────────────
	// Write to taxonomy table (save_terms=1 on the ACF field) so tax_query works.
	$jx_terms = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY, [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $jx_terms ) && ! empty( $jx_terms ) ) {
		wp_set_post_terms( $update_id, [ (int) $jx_terms[0] ], WS_JURISDICTION_TAXONOMY );
	}

	// ── Update date and type ──────────────────────────────────────────────────
	update_post_meta( $update_id, 'ws_legal_update_date', $now_local );
	$update_type = ( $post_type === 'ws-ag-procedure' )
		? 'procedure'
		: str_replace( 'jx-', '', $post_type );
	update_post_meta( $update_id, 'ws_legal_update_type', $update_type );

	// ── Law name — pull from the source post's best naming field ─────────────
	// Each law CPT has its own official_name field; try each in order, falling back to post title.
	// jx-summary has no naming field — fall back to post title.
	$law_name = ( $post_type !== 'jx-summary' )
		? (
			get_post_meta( $post_id, 'ws_jx_statute_official_name', true )
			?: get_post_meta( $post_id, 'ws_jx_citation_official_name', true )
			?: get_post_meta( $post_id, 'ws_jx_interp_official_name', true )
		)
		: '';
	if ( ! $law_name ) {
		$law_name = get_the_title( $post_id );
	}
	if ( $law_name ) {
		update_post_meta( $update_id, 'ws_legal_update_law_name', $law_name );
	}

	// ── Stamp fields (written manually — wp_insert_post bypasses acf/save_post) ──

	update_post_meta( $update_id, 'ws_auto_date_created',       $now_local );
	update_post_meta( $update_id, '_ws_auto_date_created_gmt',  $now_gmt   );
	update_post_meta( $update_id, 'ws_auto_create_author',      $user_id   );
	update_post_meta( $update_id, 'ws_auto_last_edited',        $now_local );
	update_post_meta( $update_id, '_ws_auto_last_edited_gmt',   $now_gmt   );
	update_post_meta( $update_id, 'ws_auto_last_edited_author', $user_id   );

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
