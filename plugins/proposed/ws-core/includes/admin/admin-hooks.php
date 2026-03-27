<?php
/**
 * File: admin-hooks.php
 *
 * Purpose: Shared ACF admin hooks used across all jx-* CPTs and related
 * content types. Centralises cross-CPT behaviours that were previously
 * duplicated in every ACF registration file:
 *
 *   1. URL pre-population   — auto-assigns ws_jurisdiction taxonomy term and
 *                             post title on new-post screens opened from the
 *                             "Create Now" links in admin-navigation.php.
 *
 *   2. Field locking        — makes ws_auto_date_created, ws_auto_last_edited,
 *                             ws_auto_last_edited_author, ws_auto_create_author,
 *                             ws_auto_plain_english_by, ws_auto_plain_english_date,
 *                             and ws_auto_plain_english_reviewed_by readonly + disabled
 *                             for non-administrators or non-editors.
 *
 *   3. Auto-fill today      — fills last_reviewed with today's date when the
 *                             field is empty and plain_reviewed is enabled on
 *                             an existing post.
 *
 *   4. Auto-fill editor     — pre-fills last_edited_author with the current
 *                             user for administrators so that saving without
 *                             an explicit change correctly stamps the saver.
 *                             Non-admins see the stored value (display only;
 *                             the field is locked and never submitted).
 *
 *   5. Stamp fields         — writes created/last-edited timestamps and
 *                             author IDs to post meta on every ACF save,
 *                             driven by a per-CPT configuration map.
 *
 *   6. Plain English guards — enforces has_plain_english / plain_reviewed /
 *                             plain_reviewed_by integrity rules on save.
 *
 *   7. Plain English stamps — writes ws_auto_plain_english_by /
 *                             ws_auto_plain_english_date to post meta once when
 *                             plain English content is first saved on supported CPTs.
 *
 *   8. Statute reverse index — maintains ws_jx_statute_citation_ids /
 *                             ws_jx_statute_interp_ids on jx-statute records,
 *                             rebuilt from scratch on every citation and
 *                             interpretation save and delete.
 *
 *
 * JURISDICTION TAXONOMY
 * ---------------------
 * ws_jx_code has been retired as the jurisdiction join mechanism. Addendum
 * CPTs (jx-summary, jx-statute, jx-citation, jx-interpretation) now use the
 * ws_jurisdiction taxonomy to identify their parent jurisdiction.
 *
 * Auto-assignment on Create Now flow: when a new addendum post is created from
 * the admin navigation panel, ws_jx_term (term slug) is passed as a URL query
 * arg. The wp_insert_post hook below reads this arg and assigns the matching
 * ws_jurisdiction term immediately on post creation.
 *
 * ws_jx_term_id post meta: written on every jurisdiction save via the
 * save_post_jurisdiction hook in query-jurisdiction.php. Provides a direct
 * post->term_id mapping for seeder and query layer use.
 *
 *
 * STAMP META KEYS
 * ---------------
 * All CPTs share identical canonical stamp meta key names. WordPress post meta
 * is scoped to post_id so there is no collision risk across CPTs. ACF field
 * keys follow the field_[meta_name] convention matching ws-jurisdiction.
 *
 *   ws_auto_date_created              — local date (Y-m-d), written once
 *   _ws_auto_date_created_gmt         — GMT date (Y-m-d), written once, private/hidden
 *   ws_auto_create_author             — WP user ID, written once
 *   ws_auto_last_edited               — local date (Y-m-d), written every save
 *   _ws_auto_last_edited_gmt          — GMT date (Y-m-d), written every save, private/hidden
 *   ws_auto_last_edited_author        — WP user ID, written every save (admin-overridable)
 *   ws_auto_plain_english_by          — WP user ID, written once on first plain English save
 *   ws_auto_plain_english_date        — local date (Y-m-d), written once on first plain English save
 *   ws_auto_plain_english_reviewed_by   — WP user ID, written once when plain_reviewed first enabled
 *   ws_auto_plain_english_reviewed_date — local date (Y-m-d), written once when plain_reviewed first enabled
 *
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation.
 * 2.1.3  Fixed field name references to match actual ACF field names.
 * 2.3.1  Corrected all four field names to ws_jurisdiction. Collapsed
 *        four separate filters into one.
 * 2.4.0  Replaced ws_jurisdiction (post_object) with ws_jx_code (text)
 *        across all jx-* CPTs. Collapsed ws_jurisdiction and ws_jx_code
 *        filters into a single ws_jx_code filter covering all CPTs.
 * 2.5.0  Consolidated field-lock, auto-fill, and stamp-field hooks from
 *        individual ACF files into this shared file.
 * 2.5.1  Added stamp config entries. Fixed admin stamp behaviour:
 *        last_edited_author now always stamps the current user unless an
 *        admin explicitly selects a different user for attribution. Added
 *        auto-fill-editor filter. Added last_edited date to visible lock list.
 * 3.0.0  Architecture refactor (Phase 3.2):
 *        - Removed ws_jx_code pre-populate filter (ws_jx_code retired).
 *        - Removed deleted CPT entries from stamp config.
 *        - Added save_post_jurisdiction hook writing ws_jx_term_id post meta.
 *        - Added wp_insert_post hook auto-assigning ws_jurisdiction taxonomy
 *          term on new addendum post creation (reads ws_jx_term URL arg).
 * 3.0.1  Phase 8: Added ws_languages "additional" term auto-assign/unassign
 *        hook for ws-agency (ws_agency_additional_languages) and
 *        ws-assist-org (ws_ao_additional_languages).
 * 3.1.0  Dropped meta_prefix from all stamp meta keys — all CPTs now share
 *        identical unprefixed stamp key names (date_created, create_author,
 *        last_edited, last_edited_author, etc.). Removed jx-summary from
 *        ws_acf_stamp_summarized_fields(). Added plain_reviewed_by stamp field.
 *        Added plain English integrity guards (has_plain_english enforcement,
 *        plain_reviewed_by rank check, toggle-off cleanup). Fixed last_reviewed
 *        autofill guard to require plain_reviewed. Fixed date functions to use
 *        current_time() / gmdate(). jurisdiction CPT added to stamp config;
 *        save_post_jurisdiction retained for ws_jx_term_id write. Collapsed
 *        per-CPT field-name arrays into single shared field names; lock filters
 *        now register once per shared field name and apply across all CPTs.
 * 3.1.1  Bug fix: corrected 'ws-jurisdiction' CPT slug references to 'jurisdiction'
 *        in Restrict Manual Creation hooks and Identity Field Enforcement save hook.
 *        Slug mismatch caused Add New removal, manual-creation block, and identity
 *        field re-enforcement to silently not fire.
 * 3.4.0  Stamp field centralization:
 *        - Updated $ws_stamp_cpts entry for ws-reference: author_acf_key changed
 *          from field_ws_ref_last_edited_author to field_last_edited_author.
 *          Unique key retired; ws-reference now uses shared field keys.
 *        - Removed ws_ref_approved from field locking foreach loop.
 *          ws_ref_approved field retired entirely — Approval tab removed from
 *          acf-references.php.
 * 3.5.0  ws_auto_ prefix pass (ws-core v3.2.0):
 *        - All stamp meta keys prefixed with ws_auto_ to signal system-only
 *          writes: date_created → ws_auto_date_created, etc.
 *        - GMT audit keys prefixed _ws_auto_ (leading underscore = WP hidden
 *          meta convention): date_created_gmt → _ws_auto_date_created_gmt.
 *        - Source-verify keys ws_auto_ prefixed: source_method, source_name,
 *          verified_by, verified_date.
 *        - source_name locked readonly/disabled; admin-only visibility added
 *          for source_method and source_name via ws_hide_source_fields_for_non_admins().
 *        - Plain English stamp keys ws_auto_ prefixed: plain_english_by,
 *          plain_english_date, plain_english_reviewed_by.
 * 3.5.1  Bug fix: ws_ao_additional_languages → ws_aorg_additional_languages in
 *        ws_sync_additional_languages_term(). Stale key caused additional-language
 *        term sync for ws-assist-org to silently fail.
 *        Added inline comments to direct meta reads explaining why the query
 *        layer is not used in save/filter hook context.
 * 3.6.0  Added statute reverse indexes (ws_jx_statute_citation_ids,
 *        ws_jx_statute_interp_ids). Stash-and-rebuild pattern via four
 *        acf/save_post hooks (priorities 5 and 25) for citations and
 *        interpretations. Delete hooks maintain integrity on post removal.
 * 3.9.0  Rule 3b added to ws_acf_plain_english_guards(): substantial content
 *        change (similar_text() < 75%) resets plain_english_reviewed and its
 *        stamps. Admin notice queued on trigger. Typos and minor edits do not
 *        fire — only rewrites that materially change the plain English content.
 * 3.10.0 ws-ag-procedure added to $ws_stamp_cpts. acf-stamp-fields.php already
 *        attached stamp fields to this CPT via its location rules — this entry
 *        was the missing counterpart that enables ws_acf_write_stamp_fields()
 *        to actually write created/last-edited meta on procedure saves.
 *        ws-ag-procedure added to ws_source_verify_post_types(). Omission —
 *        matrix-seeded procedures require source verification and ws_needs_review
 *        coverage identical to other seeded CPTs.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Auto-assign ws_jurisdiction taxonomy on new addendum post creation ────────
//
// When the "Create Now" or "Add Citation" link is clicked from the jurisdiction
// admin navigation panel, the URL includes ws_jx_term (jurisdiction term slug).
// This hook fires on wp_insert_post for new posts and assigns the matching
// ws_jurisdiction term immediately, so the addendum is correctly scoped.

add_action( 'wp_insert_post', function( $post_id, $post, $update ) {
    if ( $update ) return;
    if ( ! isset( $_GET['ws_jx_term'] ) ) return;

    $addendum_types = [ 'jx-summary', 'jx-statute', 'jx-citation', 'jx-interpretation' ];
    if ( ! in_array( $post->post_type, $addendum_types, true ) ) return;

    $term_slug = sanitize_key( $_GET['ws_jx_term'] );
    $term      = ws_jx_term_by_code( $term_slug );
    if ( $term && ! is_wp_error( $term ) ) {
        wp_set_object_terms( $post_id, $term->term_id, WS_JURISDICTION_TAXONOMY );
    }
}, 10, 3 );


// ── Pre-populate post title if passed via URL ─────────────────────────────────

add_filter( 'default_title', function( $title ) {
    if ( isset( $_GET['post_title'] ) ) {
        return sanitize_text_field( $_GET['post_title'] );
    }
    return $title;
} );


// ── Field locking ─────────────────────────────────────────────────────────────
//
// Stamp fields are system-managed and must not be altered through the ACF UI.
// Two lock tiers apply:
//
//   Admin-only fields  — ws_auto_date_created, ws_auto_last_edited,
//                        ws_auto_last_edited_author, ws_auto_create_author,
//                        ws_auto_plain_english_by, ws_auto_plain_english_date.
//                        Locked for any role below administrator.
//                        ws_auto_last_edited_author is admin-overridable for
//                        attribution correction on minor edits.
//
//   Editor-only fields — last_reviewed, ws_auto_plain_english_reviewed_by.
//                        Locked for any role below editor. plain_reviewed is not
//                        listed here because it is a checkbox that the toggle-off
//                        guard clears automatically; the field itself is hidden
//                        from authors by ACF conditional logic.
//
// ACF respects 'disabled' on save — a disabled field is not submitted, so the
// existing stored value is preserved even if someone manipulates the DOM.
//
// All CPTs share these field names (unprefixed), so a single filter registration
// per name applies across every post type that carries the field.

foreach ( [ 'ws_auto_date_created', 'ws_auto_last_edited', 'ws_auto_last_edited_author', 'ws_auto_create_author', 'ws_auto_plain_english_by', 'ws_auto_plain_english_date' ] as $_ws_f ) {
    add_filter( "acf/load_field/name={$_ws_f}", 'ws_acf_lock_for_non_admins' );
}
unset( $_ws_f );

// Legal update visibility control is admin-only.
add_filter( 'acf/load_field/name=ws_legal_update_hide_public', 'ws_acf_lock_for_non_admins' );

foreach ( [ 'last_reviewed', 'ws_auto_plain_english_reviewed_by', 'ws_auto_plain_english_reviewed_date' ] as $_ws_f ) {
    add_filter( "acf/load_field/name={$_ws_f}", 'ws_acf_lock_for_non_editors' );
}
unset( $_ws_f );

// ── Source & Verification visibility — admin only ─────────────────────────────
//
// ws_auto_source_method and ws_auto_source_name are not visible to any role
// below administrator. acf/prepare_field returning false hides the field
// entirely from the ACF UI without affecting stored values.

foreach ( [ 'ws_auto_source_method', 'ws_auto_source_name' ] as $_ws_f ) {
    add_filter( "acf/prepare_field/name={$_ws_f}", 'ws_hide_source_fields_for_non_admins' );
}
unset( $_ws_f );
add_filter( 'acf/prepare_field/name=ws_proc_stat_override', 'ws_hide_source_fields_for_non_admins' );

/**
 * Hides source_method and source_name fields from any user below administrator.
 *
 * @param  array $field  ACF field array.
 * @return array|false   False hides the field entirely.
 */
function ws_hide_source_fields_for_non_admins( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }
    return $field;
}

/**
 * Sets a field to readonly and disabled for any user below administrator.
 *
 * @param  array $field  ACF field array.
 * @return array
 */
function ws_acf_lock_for_non_admins( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}

/**
 * Sets a field to readonly and disabled for any user below editor.
 *
 * @param  array $field  ACF field array.
 * @return array
 */
function ws_acf_lock_for_non_editors( $field ) {
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}


// ── Auto-fill today: last_reviewed on existing posts when plain_english_reviewed is on ─
//
// Fills last_reviewed with today's date only when all three conditions hold:
//   1. The stored value is empty (never reviewed or cleared by toggle-off).
//   2. The post exists (post_id > 0 — excludes new-post / options context).
//   3. plain_english_reviewed is already enabled on this post.
//
// This prevents last_reviewed from pre-filling on posts where plain English
// content has not yet been reviewed, and never fires on brand-new posts.

add_filter( 'acf/load_value/name=last_reviewed', 'ws_acf_autofill_today', 10, 3 );

/**
 * Returns today's date (Y-m-d) when last_reviewed is empty and plain_english_reviewed
 * is enabled on the post.
 *
 * @param  mixed  $value    Current field value.
 * @param  int    $post_id  Post being edited.
 * @param  array  $field    ACF field array.
 * @return mixed
 */
function ws_acf_autofill_today( $value, $post_id, $field ) {
    // Direct meta read — acf/load_value fires before ACF renders the field; get_post_meta()
    // is the correct way to read sibling field state in this filter context.
    if ( empty( $value ) && $post_id > 0 && get_post_meta( $post_id, 'ws_plain_english_reviewed', true ) ) {
        $value = current_time( 'Y-m-d' );
    }
    return $value;
}


// ── Auto-fill editor: pre-fill last_edited_author for administrators ──────────
//
// Administrators see last_edited_author pre-filled with their own user ID.
// This ensures that saving without an explicit change correctly stamps the
// current admin — the submitted value will match $user_id in
// ws_acf_write_stamp_fields(), so the "honour override" branch only triggers
// when the admin deliberately selects a different user.
//
// Non-admins: field is locked (disabled) and never submitted; the stored
// value is displayed for reference only.

add_filter( 'acf/load_value/name=ws_auto_last_edited_author', 'ws_acf_autofill_current_editor', 10, 3 );

/**
 * Pre-fills last_edited_author with the current user for administrators.
 *
 * @param  mixed  $value    Current stored value.
 * @param  int    $post_id  Post being edited.
 * @param  array  $field    ACF field array.
 * @return mixed
 */
function ws_acf_autofill_current_editor( $value, $post_id, $field ) {
    if ( current_user_can( 'manage_options' ) ) {
        return get_current_user_id();
    }
    return $value;
}


// ── Plain English integrity guard (priority 5 — before ACF commits at 10) ────
//
// Enforces the following rules before ACF writes field values:
//
//   Rule 1 — has_plain_english requires a non-empty plain_english string.
//             If submitted plain_english is empty, has_plain_english is forced
//             to 0 and the ACF checkbox value is cleared so it resets visually.
//
//   Rule 2 — plain_english_reviewed requires editor rank or above. If the submitted
//             value is 1 but the current user is below editor, plain_english_reviewed
//             and plain_english_reviewed_by are wiped and an admin notice is queued.
//
//   Rule 3 — plain_english_reviewed toggle-off cleanup. If has_plain_english transitions
//             from 1 to 0, plain_english_reviewed and plain_english_reviewed_by are cleared.
//             The plain_english string is preserved in case the admin re-enables.
//             plain_english_by and plain_english_date are also cleared.
//
//   Rule 3b — Substantial content change resets review stamp. If has_plain_english
//             remains on but the plain_english content has changed significantly
//             (similar_text() similarity drops below 75%), plain_english_reviewed
//             and its associated stamps are cleared. Typos and minor edits do not
//             trigger this — only rewrites that materially change the content.
//             An admin notice is queued so the editor knows why the stamp cleared.
//
// Applies to: jx-statute, jx-citation, jx-interpretation, ws-agency, ws-assist-org.
// jx-summary is excluded — it is inherently plain English and carries no
// has_plain_english toggle.

add_action( 'acf/save_post', 'ws_acf_plain_english_guards', 5 );

/**
 * Enforces plain English field integrity before ACF commits field values.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_plain_english_guards( $post_id ) {

    $plain_english_cpts = [
        'jx-statute', 'jx-citation', 'jx-interpretation', 'ws-agency', 'ws-assist-org',
    ];

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, $plain_english_cpts, true ) ) {
        return;
    }

    if ( ! isset( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
        return;
    }

    $submitted_plain_english  = '';
    $submitted_has_plain      = 0;
    $submitted_plain_reviewed = 0;

    // Walk submitted ACF fields to resolve field names from keys.
    foreach ( $_POST['acf'] as $field_key => $field_value ) {
        $field_obj = acf_get_field( $field_key );
        if ( ! $field_obj ) continue;

        switch ( $field_obj['name'] ) {
            case 'ws_plain_english_wysiwyg':
                $submitted_plain_english = trim( (string) $field_value );
                break;
            case 'ws_has_plain_english':
                $submitted_has_plain = (int) $field_value;
                break;
            case 'ws_plain_english_reviewed':
                $submitted_plain_reviewed = (int) $field_value;
                break;
        }
    }

    // ── Rule 1: has_plain_english requires non-empty plain_english ────────────

    if ( $submitted_has_plain && $submitted_plain_english === '' ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( $field_obj && $field_obj['name'] === 'ws_has_plain_english' ) {
                $_POST['acf'][ $field_key ] = 0;
                $submitted_has_plain        = 0;
                break;
            }
        }
    }

    // ── Rule 2: plain_reviewed requires editor rank ───────────────────────────

    if ( $submitted_plain_reviewed && ! current_user_can( 'edit_others_posts' ) ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( ! $field_obj ) continue;
            if ( in_array( $field_obj['name'], [ 'ws_plain_english_reviewed', 'ws_auto_plain_english_reviewed_by' ], true ) ) {
                $_POST['acf'][ $field_key ] = 0;
            }
        }
        set_transient( 'ws_plain_reviewed_rank_notice_' . get_current_user_id(), true, 30 );
    }

    // ── Rule 3: has_plain_english toggle-off clears plain_reviewed fields ─────

    $stored_has_plain = (int) get_post_meta( $post_id, 'ws_has_plain_english', true );

    if ( $stored_has_plain && ! $submitted_has_plain ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( ! $field_obj ) continue;
            if ( in_array( $field_obj['name'], [ 'ws_plain_english_reviewed', 'ws_auto_plain_english_reviewed_by' ], true ) ) {
                $_POST['acf'][ $field_key ] = 0;
            }
        }
        // Clear plain English stamp meta written by ws_acf_stamp_summarized_fields()
        // and ws_acf_stamp_plain_reviewed_by().
        delete_post_meta( $post_id, 'ws_auto_plain_english_by' );
        delete_post_meta( $post_id, 'ws_auto_plain_english_date' );
        delete_post_meta( $post_id, 'ws_auto_plain_english_reviewed_date' );
        delete_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by' );
    }

    // ── Rule 3b: substantial content change resets review stamp ───────────────
    //
    // Only runs when:
    //   - has_plain_english is still on (toggle-off is handled by Rule 3 above)
    //   - plain_english_reviewed is currently 1 in stored meta (nothing to reset otherwise)
    //   - a previous plain_english value exists to compare against (new records are skipped)
    //
    // Comparison strips HTML tags and normalises case before calling similar_text()
    // so tag changes in the wysiwyg do not skew the score. Threshold: 75%.

    if ( $submitted_has_plain && $submitted_plain_english !== '' ) {

        $stored_plain_reviewed = (int) get_post_meta( $post_id, 'ws_plain_english_reviewed', true );

        if ( $stored_plain_reviewed ) {

            $stored_content    = strtolower( trim( strip_tags( get_post_meta( $post_id, 'ws_plain_english_wysiwyg', true ) ) ) );
            $submitted_content = strtolower( trim( strip_tags( $submitted_plain_english ) ) );

            // Skip if no previous content to compare against (first-time save).
            if ( $stored_content !== '' ) {

                similar_text( $stored_content, $submitted_content, $similarity_pct );

                if ( $similarity_pct < 75.0 ) {

                    foreach ( $_POST['acf'] as $field_key => $field_value ) {
                        $field_obj = acf_get_field( $field_key );
                        if ( ! $field_obj ) continue;
                        if ( in_array( $field_obj['name'], [ 'ws_plain_english_reviewed', 'ws_auto_plain_english_reviewed_by' ], true ) ) {
                            $_POST['acf'][ $field_key ] = 0;
                        }
                    }

                    delete_post_meta( $post_id, 'ws_auto_plain_english_reviewed_date' );
                    delete_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by' );

                    set_transient( 'ws_plain_rewrite_notice_' . get_current_user_id(), true, 30 );
                }
            }
        }
    }
}


// ── Admin notice: plain_reviewed rank violation ───────────────────────────────

add_action( 'admin_notices', function() {
    $transient = 'ws_plain_reviewed_rank_notice_' . get_current_user_id();
    if ( ! get_transient( $transient ) ) {
        return;
    }
    delete_transient( $transient );
    echo '<div class="notice notice-warning is-dismissible">'
        . '<p><strong>WhistleblowerShield:</strong> '
        . 'The Plain Reviewed flag requires Editor access or above. '
        . 'The plain_english_reviewed and plain_english_reviewed_by fields were not saved.</p>'
        . '</div>';
} );


// ── Admin notice: plain_reviewed cleared due to substantial content change ────

add_action( 'admin_notices', function() {
    $transient = 'ws_plain_rewrite_notice_' . get_current_user_id();
    if ( ! get_transient( $transient ) ) {
        return;
    }
    delete_transient( $transient );
    echo '<div class="notice notice-warning is-dismissible">'
        . '<p><strong>WhistleblowerShield:</strong> '
        . 'The Plain Language content has changed substantially. '
        . 'The Plain Reviewed flag has been cleared — please re-review before marking as reviewed.</p>'
        . '</div>';
} );


// ── Stamp fields: created + last-edited metadata on every ACF save ────────────
//
// Handles created stamps (written once) and last-edited stamps (written on
// every save) for all supported CPTs.
//
// Configuration map: CPT slug => author_acf_key
//
//   author_acf_key — ACF field key for the last_edited_author user field.
//                    Used to detect whether an administrator explicitly
//                    submitted a different user via the ACF UI; if so, that
//                    choice is preserved rather than overwriting with the
//                    current user ID.
//
// All CPTs share identical ws_auto_ prefixed stamp meta key names. WordPress post
// meta is scoped to post_id so there is no collision risk across post types.
//
// To add stamp support to a new CPT, add one entry to $ws_stamp_cpts.

$ws_stamp_cpts = [
    'jurisdiction'      => [ 'author_acf_key' => 'field_jx_last_edited_author' ],
    'jx-summary'        => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-citation'       => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-statute'        => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-interpretation' => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-agency'         => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-ag-procedure'   => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-legal-update'   => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-assist-org'     => [ 'author_acf_key' => 'field_last_edited_author' ],
    // ws-reference uses shared field keys — unique key retired in v3.4.0.
    'ws-reference'      => [ 'author_acf_key' => 'field_last_edited_author' ],
];

add_action( 'acf/save_post', 'ws_acf_write_stamp_fields', 20 );

/**
 * Writes created and last-edited stamp fields for all supported CPTs.
 *
 * Runs at priority 20 (after ACF commits its own fields at priority 10).
 * CPT support is declared in the $ws_stamp_cpts configuration map above.
 *
 * last_edited_author logic:
 *   - Non-admin saves: field is disabled and not submitted ($posted_user = 0)
 *     => always stamp current user.
 *   - Admin saves without changing: ws_acf_autofill_current_editor() pre-fills
 *     the field with the current admin, so submitted value equals $user_id
 *     => falls to else, stamps current user.
 *   - Admin saves with a deliberately different user selected: submitted value
 *     differs from $user_id => honour the attribution override.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_write_stamp_fields( $post_id ) {

    global $ws_stamp_cpts;

    $post_type = get_post_type( $post_id );
    if ( ! isset( $ws_stamp_cpts[ $post_type ] ) ) {
        return;
    }

    $acf_key   = $ws_stamp_cpts[ $post_type ]['author_acf_key'];
    $now_local = current_time( 'Y-m-d' );
    $now_gmt   = gmdate( 'Y-m-d' );
    $user_id   = get_current_user_id();

    // ── Created stamps (once only) ────────────────────────────────────────

    if ( ! get_post_meta( $post_id, 'ws_auto_date_created', true ) ) {
        update_post_meta( $post_id, 'ws_auto_date_created',      $now_local );
        update_post_meta( $post_id, '_ws_auto_date_created_gmt', $now_gmt );
        update_post_meta( $post_id, 'ws_auto_create_author',     $user_id );
    }

    // ── Last-edited stamps (every save) ───────────────────────────────────

    update_post_meta( $post_id, 'ws_auto_last_edited',      $now_local );
    update_post_meta( $post_id, '_ws_auto_last_edited_gmt', $now_gmt );

    // ── Last-edited author ────────────────────────────────────────────────
    // Honour admin attribution override; stamp current user in all other cases.

    $posted_user = isset( $_POST['acf'][ $acf_key ] ) ? (int) $_POST['acf'][ $acf_key ] : 0;
    $is_admin    = current_user_can( 'manage_options' );

    if ( $is_admin && $posted_user && $posted_user !== $user_id ) {
        update_post_meta( $post_id, 'ws_auto_last_edited_author', $posted_user );
    } else {
        update_post_meta( $post_id, 'ws_auto_last_edited_author', $user_id );
    }
}


// ── plain_english_reviewed_by one-time stamp ──────────────────────────────────
//
// Stamped once when plain_english_reviewed is first enabled on a supported CPT.
// Cleared by ws_acf_plain_english_guards() (priority 5) when plain_english_reviewed
// is toggled off, allowing a fresh stamp on the next toggle-on.
//
// Applies to: jx-statute, jx-citation, jx-interpretation, ws-agency, ws-assist-org.
// jx-summary is excluded — it is inherently plain English.
//
// Runs at priority 25 (after ACF fields at 10, after main stamps at 20).

add_action( 'acf/save_post', 'ws_acf_stamp_plain_reviewed_by', 25 );

/**
 * Writes plain_english_reviewed_by once when plain_english_reviewed is first enabled.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_stamp_plain_reviewed_by( $post_id ) {

    $supported = [
        'jx-statute', 'jx-citation', 'jx-interpretation', 'ws-agency', 'ws-assist-org',
    ];

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, $supported, true ) ) {
        return;
    }

    if ( ! get_post_meta( $post_id, 'ws_plain_english_reviewed', true ) ) {
        return;
    }

    if ( get_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by', true ) ) {
        return;
    }

    // Safety net: require editor rank (primary enforcement is at priority 5).
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        return;
    }

    update_post_meta( $post_id, 'ws_auto_plain_english_reviewed_by',   get_current_user_id() );
    update_post_meta( $post_id, 'ws_auto_plain_english_reviewed_date', current_time( 'Y-m-d' ) );
}


// ── plain_english_by + plain_english_date one-time stamps ────────────────────
//
// Stamped once when plain English content is first saved on a supported CPT
// and has_plain_english is enabled.
//
// jx-summary is excluded — it is inherently plain English and carries no
// has_plain_english toggle.
//
// Cleared on has_plain_english toggle-off by ws_acf_plain_english_guards() at
// priority 5 (deletes plain_english_by and plain_english_date from post meta).
//
// Runs at priority 25 (after ACF fields at 10, after main stamps at 20).

add_action( 'acf/save_post', 'ws_acf_stamp_summarized_fields', 25 );

/**
 * Writes summarized_by and summarized_date once when plain English is first
 * saved on a supported CPT.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_stamp_summarized_fields( $post_id ) {

    $supported = [
        'jx-statute', 'jx-citation', 'jx-interpretation', 'ws-agency', 'ws-assist-org',
    ];

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, $supported, true ) ) {
        return;
    }

    if ( ! get_post_meta( $post_id, 'ws_has_plain_english', true ) ) {
        return;
    }

    if ( get_post_meta( $post_id, 'ws_auto_plain_english_date', true ) ) {
        return;
    }

    update_post_meta( $post_id, 'ws_auto_plain_english_date', current_time( 'Y-m-d' ) );
    update_post_meta( $post_id, 'ws_auto_plain_english_by',   get_current_user_id() );
}


// ── Auto-assign ws_languages "additional" term ────────────────────────────────
//
// When ws_agency_additional_languages (ws-agency) or ws_aorg_additional_languages
// (ws-assist-org) is non-empty, the "additional" ws_languages term is assigned
// automatically so the taxonomy filter can surface these records.
// When the field is cleared, the term is removed.
//
// Runs at priority 25 (after ACF fields commit at 10, after stamps at 20).

add_action( 'acf/save_post', 'ws_sync_additional_languages_term', 25 );

/**
 * Syncs the ws_languages "additional" term based on the additional-languages
 * field value for ws-agency and ws-assist-org posts.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_sync_additional_languages_term( $post_id ) {

    $post_type = get_post_type( $post_id );

    if ( $post_type === 'ws-agency' ) {
        $meta_key = 'ws_agency_additional_languages';
    } elseif ( $post_type === 'ws-assist-org' ) {
        $meta_key = 'ws_aorg_additional_languages';
    } else {
        return;
    }

    $additional_term = get_term_by( 'slug', 'additional', 'ws_languages' );
    if ( ! $additional_term || is_wp_error( $additional_term ) ) {
        return; // Taxonomy not yet seeded — bail silently.
    }

    $value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );

    if ( $value !== '' ) {
        wp_set_object_terms( $post_id, $additional_term->term_id, 'ws_languages', true );
    } else {
        wp_remove_object_terms( $post_id, $additional_term->term_id, 'ws_languages' );
    }
}

// ── Auto-assign ws_aorg_service "additional" term ─────────────────────────────
//
// When ws_aorg_additional_services (ws-assist-org) is non-empty, the "additional"
// ws_aorg_service term is assigned automatically so the taxonomy filter can surface
// these records. When the field is cleared, the term is removed.
//
// Runs at priority 25 (after ACF fields commit at 10, after stamps at 20).

add_action( 'acf/save_post', 'ws_sync_additional_services_term', 25 );

/**
 * Syncs the ws_aorg_service "additional" term based on the additional-services
 * field value for ws-assist-org posts.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_sync_additional_services_term( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-assist-org' ) {
        return;
    }

    $additional_term = get_term_by( 'slug', 'additional', 'ws_aorg_service' );
    if ( ! $additional_term || is_wp_error( $additional_term ) ) {
        return; // Taxonomy not yet seeded — bail silently.
    }

    $value = trim( (string) get_post_meta( $post_id, 'ws_aorg_additional_services', true ) );

    if ( $value !== '' ) {
        wp_set_object_terms( $post_id, $additional_term->term_id, 'ws_aorg_service', true );
    } else {
        wp_remove_object_terms( $post_id, $additional_term->term_id, 'ws_aorg_service' );
    }
}

// ---------------------------------------------------------------
// ACF Taxonomy Field Query Overrides
// ---------------------------------------------------------------
/**
 * Apply display_order sort to the ws_jurisdiction taxonomy ACF select field.
 */
add_filter( 'acf/fields/taxonomy/query/key=field_jurisdiction_tax', function( $args, $field, $post_id ) {
    $args['meta_key'] = 'display_order';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'ASC';
    return $args;
}, 10, 3 );

// ---------------------------------------------------------------
// Jurisdiction CPT — Restrict Manual Creation
// ---------------------------------------------------------------
// Remove 'Add New' from the jurisdiction CPT menu entirely
add_action( 'admin_menu', function() {
    remove_submenu_page( 'edit.php?post_type=jurisdiction', 'post-new.php?post_type=jurisdiction' );
} );

// Redirect anyone who reaches the new post screen anyway
add_action( 'load-post-new.php', function() {
    if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'jurisdiction' ) {
        wp_die(
            __( '<strong>WhistleblowerShield:</strong> New Jurisdiction records cannot be created manually. All 57 jurisdictions are seeded at installation. If a jurisdiction is missing, re-run the seeder via WP-CLI or contact the site administrator.' ),
            __( 'Action Not Permitted' ),
            [ 'back_link' => true ]
        );
    }
} );
// ---------------------------------------------------------------
// Jurisdiction CPT — Identity Field Enforcement
// ---------------------------------------------------------------

add_filter( 'acf/prepare_field/key=field_jx_code', function( $field ) {
    $field['readonly'] = true;
    $field['disabled'] = true;
    return $field;
} );
add_filter( 'acf/prepare_field/key=field_jurisdiction_name', function( $field ) {
    $field['readonly'] = true;
    $field['disabled'] = true;
    return $field;
} );
add_filter( 'acf/prepare_field/key=field_jurisdiction_class', function( $field ) {
    $field['readonly'] = true;
    $field['disabled'] = true;
    return $field;
} );
// ── Jurisdiction CPT — Conditional Button to Wikimedia Flag when URL is present
// ---------------------------------------------------------------
// Direct get_post_meta() call is intentional here. ws_matrix_source is an
// administrative flag...
// ---------------------------------------------------------------
// ACF Field Presentation Overrides
// ---------------------------------------------------------------
add_filter( 'acf/prepare_field/key=field_jx_flag_source_url', function( $field ) {
    $post_id = get_the_ID();
    $url     = get_post_meta( $post_id, 'ws_jx_flag_source_url', true );
    if ( $url ) {
        $field['instructions'] .= ' <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer nofollow">Open Commons page &rarr;</a>';
    }
    return $field;
} );
// ════════════════════════════════════════════════════════════════════════════
// SOURCE & VERIFICATION HOOKS (5 of 5)
//
// Hook logic for the Source & Verification field group (acf-source-verify.php).
// Fire order at priority 5: stamp_source_method → stamp_source_name →
// default_verification_status. Priority 20: stamp_verified_by_date,
// enforce_source_verify_roles.
//
// WS_SOURCE_* constants are defined in ws-core.php — see the Source Method
// Constants block there for the full method table and source_name convention.
// ════════════════════════════════════════════════════════════════════════════


// ════════════════════════════════════════════════════════════════════════════
// HELPER — CPT list for source/verify hooks
//
// Centralised so the list only needs updating in one place if CPTs are
// added or removed. Must stay in sync with the location rules in
// acf-source-verify.php.
// ════════════════════════════════════════════════════════════════════════════

function ws_source_verify_post_types() {
    return [
        'jx-statute',
        'jx-citation',
        'jx-interpretation',
        'ws-agency',
        'ws-ag-procedure',
        'ws-assist-org',
        'jx-summary',
        'ws-reference',
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// HOOK 1 of 5 — Set source_method at post creation (priority 5, first save)
//
// Writes source_method once and never again. For jx-summary the value is
// always WS_SOURCE_HUMAN_CREATED regardless of context — this enforces
// the policy that summaries are always human-authored.
//
// For all other CPTs this hook assumes manual admin creation and writes
// WS_SOURCE_HUMAN_CREATED as the default. Programmatic sources must call
// ws_set_source_method( $post_id, WS_SOURCE_* ) directly — they must not
// rely on this hook.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_stamp_source_method', 5 );

function ws_stamp_source_method( $post_id ) {

    if ( ! in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        return;
    }

    // First save only — never overwrite.
    if ( get_post_meta( $post_id, 'ws_auto_source_method', true ) !== '' ) {
        return;
    }

    // jx-summary is always human_created by policy.
    // All posts created through the admin UI are always human_created.
    // jx-summary is called out explicitly in the comment above for clarity,
    // but the method is the same for all types covered by this hook.
    $method = WS_SOURCE_HUMAN_CREATED;

    update_post_meta( $post_id, 'ws_auto_source_method', $method );
}


// ════════════════════════════════════════════════════════════════════════════
// HOOK 2 of 5 — Set source_name at post creation (priority 5, first save)
//
// Must fire after ws_stamp_source_method() so source_method is already
// written when this hook runs.
//
// matrix_seed and human_created posts always receive WS_SOURCE_NAME_DIRECT
// ('Direct'). All other methods leave source_name empty — it must be
// supplied by the ingest tooling via ws_set_source_name() or by the
// editor in the admin UI.
//
// First save only — never overwrites an existing value.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_stamp_source_name', 6 );

function ws_stamp_source_name( $post_id ) {

    if ( ! in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        return;
    }

    // First save only — never overwrite.
    if ( get_post_meta( $post_id, 'ws_auto_source_name', true ) !== '' ) {
        return;
    }

    $method = get_post_meta( $post_id, 'ws_auto_source_method', true );

    if ( in_array( $method, [ WS_SOURCE_MATRIX_SEED, WS_SOURCE_HUMAN_CREATED ], true ) ) {
        update_post_meta( $post_id, 'ws_auto_source_name', WS_SOURCE_NAME_DIRECT );
    }
    // All other methods: leave empty — must be supplied externally.
}


// ════════════════════════════════════════════════════════════════════════════
// HOOK 3 of 5 — Default verification_status at creation (priority 5, first)
//
// Must fire after ws_stamp_source_method() and ws_stamp_source_name() so
// both fields are already written when this hook runs.
//
// human_created posts default to 'verified' — the act of manual creation
// implies editorial ownership. All other methods default to 'unverified'.
//
// For human_created posts, verified_by and verified_date are also stamped
// immediately so the provenance cluster is fully populated from first save.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_default_verification_status', 5 );

function ws_default_verification_status( $post_id ) {

    if ( ! in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        return;
    }

    // First save only.
    if ( get_post_meta( $post_id, 'ws_verification_status', true ) !== '' ) {
        return;
    }

    $source = get_post_meta( $post_id, 'ws_auto_source_method', true );

    if ( $source === WS_SOURCE_HUMAN_CREATED ) {
        update_post_meta( $post_id, 'ws_verification_status', 'verified' );
        update_post_meta( $post_id, 'ws_auto_verified_by',   get_current_user_id() );
        update_post_meta( $post_id, 'ws_auto_verified_date', current_time( 'Y-m-d' ) );
    } else {
        update_post_meta( $post_id, 'ws_verification_status', 'unverified' );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// HOOK 4 of 5 — Auto-stamp verified_by + verified_date on transition (p. 20)
//
// Fires on every save. Compares incoming verification_status against the
// pre-save value. Stamps only on a genuine transition TO 'verified' —
// not on saves where the status is already 'verified'.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_stamp_verified_by_date', 20 );

function ws_stamp_verified_by_date( $post_id ) {

    if ( ! in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        return;
    }

    $incoming = '';
    if ( ! empty( $_POST['acf'] ) ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( ! $field_obj ) continue;
            if ( $field_obj['name'] === 'ws_verification_status' ) {
                $incoming = sanitize_text_field( (string) $field_value );
                break;
            }
        }
    }

    if ( $incoming !== 'verified' ) {
        return;
    }

    $previous = get_post_meta( $post_id, 'ws_verification_status', true );

    if ( $previous === 'verified' ) {
        return; // Already verified — not a transition, do not re-stamp.
    }

    update_post_meta( $post_id, 'ws_auto_verified_by',   get_current_user_id() );
    update_post_meta( $post_id, 'ws_auto_verified_date', current_time( 'Y-m-d' ) );
}


// ════════════════════════════════════════════════════════════════════════════
// HOOK 5 of 5 — Enforce role restrictions + source_name gate (priority 20)
//
// Three server-side enforcements:
//
//   1. needs_review — admin only. Non-admin saves revert to pre-save value.
//      Pre-save value is stashed at priority 5 (before ACF writes at 10).
//
//   2. verification_status revert — non-admins cannot set status back to
//      'unverified' once it is 'verified'. Attempt is silently reverted.
//
//   3. source_name gate — no role may set verification_status to 'verified'
//      if source_name is empty. Attempt is silently reverted to 'unverified'.
//      This is the server-side enforcement of the ACF conditional logic gate
//      in acf-source-verify.php.
// ════════════════════════════════════════════════════════════════════════════

// Priority 5: stash ws_needs_review before ACF writes the submitted value at priority 10.
// ws_presave_needs_review() acts as both setter (priority-5 hook) and getter (priority-20).
add_action( 'acf/save_post', function( $post_id ) {
    if ( in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        ws_presave_needs_review( $post_id, get_post_meta( $post_id, 'ws_needs_review', true ) );
    }
}, 5 );

/**
 * Stash/retrieve the pre-save ws_needs_review value.
 * Called at priority 5 (setter) and priority 20 (getter).
 *
 * @param  int         $post_id
 * @param  string|null $set     Pass a string to store; omit (or null) to retrieve.
 * @return string|null          Stored value, or null if never set.
 */
function ws_presave_needs_review( $post_id, $set = null ) {
    static $stash = [];
    if ( $set !== null ) {
        $stash[ $post_id ] = $set;
    }
    return $stash[ $post_id ] ?? null;
}

add_action( 'acf/save_post', 'ws_enforce_source_verify_roles', 20 );

function ws_enforce_source_verify_roles( $post_id ) {

    if ( ! in_array( get_post_type( $post_id ), ws_source_verify_post_types(), true ) ) {
        return;
    }

    $is_admin = current_user_can( 'manage_options' );

    // Resolve the submitted verification_status by field name, not hardcoded key.
    $incoming_status = '';
    if ( ! empty( $_POST['acf'] ) ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( ! $field_obj ) continue;
            if ( $field_obj['name'] === 'ws_verification_status' ) {
                $incoming_status = sanitize_text_field( (string) $field_value );
                break;
            }
        }
    }

    // ── 1. needs_review: admin only ───────────────────────────────────────
    // Use the pre-save value stashed at priority 5 — get_post_meta() at priority 20
    // returns the submitted (ACF-written) value, not the pre-save value.
    if ( ! $is_admin ) {
        $previous_needs_review = ws_presave_needs_review( $post_id );
        if ( $previous_needs_review !== null ) {
            update_post_meta( $post_id, 'ws_needs_review', $previous_needs_review );
        }
    }

    // ── 2. verification_status: non-admins cannot revert to 'unverified' ─
    if ( ! $is_admin ) {
        $previous_status = get_post_meta( $post_id, 'ws_verification_status', true );
        $effective_status = $incoming_status !== '' ? $incoming_status : $previous_status;

        if ( $previous_status === 'verified' && $effective_status !== 'verified' ) {
            update_post_meta( $post_id, 'ws_verification_status', 'verified' );
        }
    }

    // ── 3. source_name gate: no role may verify without a source_name ─────
    $source_name = trim( (string) get_post_meta( $post_id, 'ws_auto_source_name', true ) );

    if ( $incoming_status === 'verified' && $source_name === '' ) {
        update_post_meta( $post_id, 'ws_verification_status', 'unverified' );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// PUBLIC API — ws_set_source_method()
//
// Called by matrix seed functions, bulk import routines, and AI-assisted
// ingest tooling to set source_method programmatically. Silently refuses
// to overwrite an existing value (immutability) or accept unknown values.
// jx-summary posts always receive WS_SOURCE_HUMAN_CREATED regardless of
// the value passed by the caller.
//
// Usage:
//   ws_set_source_method( $post_id, WS_SOURCE_AI_ASSISTED );
// ════════════════════════════════════════════════════════════════════════════

function ws_set_source_method( $post_id, $method ) {

    $allowed = [
        WS_SOURCE_MATRIX_SEED,
        WS_SOURCE_AI_ASSISTED,
        WS_SOURCE_BULK_IMPORT,
        WS_SOURCE_FEED_IMPORT,
        WS_SOURCE_HUMAN_CREATED,
    ];

    if ( ! in_array( $method, $allowed, true ) ) {
        return;
    }

    // Immutability guard.
    if ( get_post_meta( $post_id, 'ws_auto_source_method', true ) !== '' ) {
        return;
    }

    // jx-summary policy lock.
    if ( get_post_type( $post_id ) === 'jx-summary' ) {
        update_post_meta( $post_id, 'ws_auto_source_method', WS_SOURCE_HUMAN_CREATED );
        return;
    }

    update_post_meta( $post_id, 'ws_auto_source_method', $method );
}


// ════════════════════════════════════════════════════════════════════════════
// PUBLIC API — ws_set_source_name()
//
// Called by ingest tooling to set source_name programmatically. Typically
// read from the "meta" header block of a JSON ingest file. Silently refuses
// to overwrite an existing value (immutability) or accept an empty string.
//
// matrix_seed and human_created posts already have source_name = 'Direct'
// written by ws_stamp_source_name() — calls to this function for those
// posts will be silently ignored by the immutability guard.
//
// Usage:
//   ws_set_source_name( $post_id, 'Claude AI' );
// ════════════════════════════════════════════════════════════════════════════

function ws_set_source_name( $post_id, $name ) {

    $name = trim( (string) $name );

    if ( $name === '' ) {
        return;
    }

    // Immutability guard.
    if ( get_post_meta( $post_id, 'ws_auto_source_name', true ) !== '' ) {
        return;
    }

    update_post_meta( $post_id, 'ws_auto_source_name', $name );
}


// ════════════════════════════════════════════════════════════════════════════
// STATUTE REVERSE INDEXES
//
// jx-citation and jx-interpretation each store their parent statute as a
// forward relationship:
//   jx-citation       → ws_jx_citation_statute_ids  (post_object, multiple)
//   jx-interpretation → ws_jx_interp_statute_id     (post_object, single)
//
// These hooks maintain a reverse index on the jx-statute side:
//   ws_jx_statute_citation_ids  — PHP array of citation post IDs
//   ws_jx_statute_interp_ids    — PHP array of interpretation post IDs
//
// The index is always rebuilt from scratch — no append logic, no deduplication
// risk, no stale entries. Admin metaboxes read the index via get_post_meta()
// + post__in, replacing the fragile OR meta_query (plain-int vs. serialized)
// previously used.
//
// SAVE FLOW (citations)
//   Priority 5:  Stash pre-save ws_jx_citation_statute_ids before ACF
//                overwrites meta at priority 10.
//   Priority 25: Union pre-save + post-save statute IDs; rebuild each statute's
//                citation index. Handles reassignment: old statute loses the
//                citation, new statute gains it.
//
// SAVE FLOW (interpretations)
//   Priority 5:  Stash pre-save ws_jx_interp_statute_id.
//   Priority 25: Union old + new statute ID; rebuild each.
//
// DELETE FLOW
//   before_delete_post: Read forward relationship while meta still exists;
//                       stash statute IDs.
//   deleted_post:       Rebuild statute indexes — deleted post is now gone
//                       and will not appear in the rebuild query.
//
// VERSION
//   3.6.0  Initial implementation.
// ════════════════════════════════════════════════════════════════════════════


// ── Rebuild functions ─────────────────────────────────────────────────────────

/**
 * Rebuilds ws_jx_statute_citation_ids on a jx-statute post.
 *
 * Queries all jx-citation records whose ws_jx_citation_statute_ids field
 * references $statute_id (plain integer or serialized array) and writes
 * the resulting ID array to the statute's meta.
 *
 * @param int $statute_id  Post ID of the jx-statute to rebuild.
 */
function ws_rebuild_jx_statute_citation_index( $statute_id ) {
    $statute_id = (int) $statute_id;
    if ( ! $statute_id || get_post_type( $statute_id ) !== 'jx-statute' ) {
        return;
    }

    $ids = get_posts( [
        'post_type'      => 'jx-citation',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'ws_jx_citation_statute_ids',
                'value'   => $statute_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'ws_jx_citation_statute_ids',
                'value'   => serialize( $statute_id ),
                'compare' => 'LIKE',
            ],
        ],
    ] );

    update_post_meta( $statute_id, 'ws_jx_statute_citation_ids', array_map( 'intval', (array) $ids ) );
}

/**
 * Rebuilds ws_jx_statute_interp_ids on a jx-statute post.
 *
 * Queries all jx-interpretation records whose ws_jx_interp_statute_id equals
 * $statute_id and writes the resulting ID array to the statute's meta.
 *
 * @param int $statute_id  Post ID of the jx-statute to rebuild.
 */
function ws_rebuild_jx_statute_interp_index( $statute_id ) {
    $statute_id = (int) $statute_id;
    if ( ! $statute_id || get_post_type( $statute_id ) !== 'jx-statute' ) {
        return;
    }

    $ids = get_posts( [
        'post_type'      => 'jx-interpretation',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'ws_jx_interp_statute_id',
                'value'   => $statute_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ],
    ] );

    update_post_meta( $statute_id, 'ws_jx_statute_interp_ids', array_map( 'intval', (array) $ids ) );
}


// ── Save stash helpers ────────────────────────────────────────────────────────
//
// Static arrays pass pre-save statute IDs from the priority-5 capture hook to
// the priority-25 rebuild hook within the same acf/save_post call chain.
// Separate functions for citations (array) and interpretations (scalar).

function ws_citation_save_stash( $post_id, $ids = null ) {
    static $stash = [];
    if ( $ids !== null ) { $stash[ $post_id ] = $ids; }
    return $stash[ $post_id ] ?? [];
}

function ws_interp_save_stash( $post_id, $id = null ) {
    static $stash = [];
    if ( $id !== null ) { $stash[ $post_id ] = $id; }
    return $stash[ $post_id ] ?? 0;
}


// ── Delete stash helpers ──────────────────────────────────────────────────────
//
// Separate stash functions for the before_delete_post → deleted_post pair.
// before_delete_post captures statute IDs while meta is still intact;
// deleted_post reads the stash after the post is gone and triggers rebuilds.

function ws_citation_delete_stash( $post_id, $ids = null ) {
    static $stash = [];
    if ( $ids !== null ) { $stash[ $post_id ] = $ids; }
    return $stash[ $post_id ] ?? [];
}

function ws_interp_delete_stash( $post_id, $id = null ) {
    static $stash = [];
    if ( $id !== null ) { $stash[ $post_id ] = $id; }
    return $stash[ $post_id ] ?? 0;
}


// ── Citation save hooks ───────────────────────────────────────────────────────

// Priority 5: capture pre-save statute IDs before ACF writes at priority 10.
add_action( 'acf/save_post', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'jx-citation' ) { return; }
    $raw = get_post_meta( $post_id, 'ws_jx_citation_statute_ids', true );
    $ids = is_array( $raw ) ? array_map( 'intval', $raw ) : ( $raw ? [ (int) $raw ] : [] );
    ws_citation_save_stash( $post_id, $ids );
}, 5 );

// Priority 25: union old + new statute IDs; rebuild each statute's citation index.
add_action( 'acf/save_post', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'jx-citation' ) { return; }
    $raw_new = get_post_meta( $post_id, 'ws_jx_citation_statute_ids', true );
    $new_ids = is_array( $raw_new ) ? array_map( 'intval', $raw_new ) : ( $raw_new ? [ (int) $raw_new ] : [] );
    $all_ids = array_unique( array_merge( ws_citation_save_stash( $post_id ), $new_ids ) );
    foreach ( array_filter( $all_ids ) as $sid ) {
        ws_rebuild_jx_statute_citation_index( $sid );
    }
}, 25 );


// ── Interpretation save hooks ─────────────────────────────────────────────────

// Priority 5: capture pre-save statute ID.
add_action( 'acf/save_post', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'jx-interpretation' ) { return; }
    ws_interp_save_stash( $post_id, (int) get_post_meta( $post_id, 'ws_jx_interp_statute_id', true ) );
}, 5 );

// Priority 25: union old + new statute ID; rebuild each.
add_action( 'acf/save_post', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'jx-interpretation' ) { return; }
    $new_id  = (int) get_post_meta( $post_id, 'ws_jx_interp_statute_id', true );
    $all_ids = array_unique( array_filter( [ ws_interp_save_stash( $post_id ), $new_id ] ) );
    foreach ( $all_ids as $sid ) {
        ws_rebuild_jx_statute_interp_index( $sid );
    }
}, 25 );


// ── Delete hooks ──────────────────────────────────────────────────────────────

// before_delete_post: stash statute IDs while forward-relationship meta is intact.
add_action( 'before_delete_post', function( $post_id ) {
    $type = get_post_type( $post_id );
    if ( $type === 'jx-citation' ) {
        $raw = get_post_meta( $post_id, 'ws_jx_citation_statute_ids', true );
        $ids = is_array( $raw ) ? array_map( 'intval', $raw ) : ( $raw ? [ (int) $raw ] : [] );
        ws_citation_delete_stash( $post_id, $ids );
    } elseif ( $type === 'jx-interpretation' ) {
        ws_interp_delete_stash( $post_id, (int) get_post_meta( $post_id, 'ws_jx_interp_statute_id', true ) );
    }
} );

// deleted_post: rebuild statute indexes now that the deleted post is gone.
// Fires for every deleted post; stash functions return empty/zero for non-
// citation/interpretation posts, so unrelated deletions are no-ops.
add_action( 'deleted_post', function( $post_id ) {
    foreach ( array_filter( ws_citation_delete_stash( $post_id ) ) as $sid ) {
        ws_rebuild_jx_statute_citation_index( $sid );
    }
    $interp_sid = ws_interp_delete_stash( $post_id );
    if ( $interp_sid ) {
        ws_rebuild_jx_statute_interp_index( $interp_sid );
    }
} );
