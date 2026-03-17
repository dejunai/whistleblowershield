<?php
/**
 * File: admin-hooks.php
 *
 * Purpose: Shared ACF admin hooks used across all jx-* CPTs and related
 * content types. Centralises four cross-CPT behaviours that were previously
 * duplicated in every ACF registration file:
 *
 *   1. URL pre-population   — fills ws_jx_code and post title on new-post
 *                             screens opened from the "Create Now" links in
 *                             admin-navigation.php.
 *
 *   2. Field locking        — makes date_created and last_edited_author
 *                             readonly + disabled for non-administrators.
 *
 *   3. Auto-fill today      — fills last_reviewed text fields with today's
 *                             date when the field is empty (new posts).
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
 *
 * FIELD NAMES
 * -----------
 * All jx-* CPTs (jx-summary, , , jx-statute,
 * jx-citation) use the same ACF field name for their jurisdiction
 * back-reference:
 *
 *   ws_jx_code   — plain text field on all jx-* CPTs
 *
 * A single acf/load_field filter covers all CPTs.
 *
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Fixed field name references to match actual ACF field names
 * 2.3.1  Corrected all four field names to ws_jurisdiction. Collapsed
 *        four separate filters into one.
 * 2.4.0  Replaced ws_jurisdiction (post_object) with ws_jx_code (text)
 *        across all jx-* CPTs. Collapsed ws_jurisdiction and ws_jx_code
 *        filters into a single ws_jx_code filter covering all CPTs.
 * 2.5.0  Consolidated field-lock, auto-fill, and stamp-field hooks from
 *        individual ACF files into this shared file.
 * 2.5.1  Added  and  to stamp config. Fixed admin
 *        stamp behaviour: last_edited_author now always stamps the current
 *        user unless an admin explicitly selects a different user for
 *        attribution. Added auto-fill-editor filter so the field pre-fills
 *        to the current user for admins, ensuring the override check works
 *        correctly on save. Added last_edited date to visible lock list.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Pre-populate ws_jx_code on new addendum and citation screens ──────────────
//
// All jx-* CPTs (jx-summary, , , jx-statute,
// jx-citation) now use ws_jx_code as their shared Jurisdiction Code field.
// The "Create Now" links in admin-navigation.php pass ws_jx_code as a query
// arg, as does the "Add Citation" button. One filter covers all CPTs.

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


// ── Field lock: readonly + disabled for non-administrators ────────────────────
//
// date_created and last_edited fields are system-stamped and must never be
// changed by editors. last_edited_author is auto-stamped but administrators
// may override it to preserve attribution for minor corrections. All three
// field types are locked to readonly + disabled for any role below
// administrator.
//
// ACF respects 'disabled' on save — a disabled field is not submitted, so
// the existing stored value is preserved even if someone manipulates the DOM.

$ws_lock_field_names = [
    // date_created — stamped once, never editable
    'ws_jx_sum_date_created',
    'ws_jx_cite_date_created',
    'ws_jx_statute_date_created',
    'date_created',
    'date_created',
    'ws_agency_date_created',
    'ws_lu_date_created',
    'ws_ao_date_created',
    'ws_interp_date_created',
    // last_edited — stamped on every save, never editable
    'ws_jx_sum_last_edited',
    'ws_jx_cite_last_edited',
    'ws_jx_statute_last_edited',
    'last_edited',
    'last_edited',
    'ws_agency_last_edited',
    'ws_lu_last_edited',
    'ws_ao_last_edited',
    'ws_interp_last_edited',
    // last_edited_author — auto-stamped, admin-overridable only
    'ws_jx_sum_last_edited_author',
    'ws_jx_cite_last_edited_author',
    'ws_jx_statute_last_edited_author',
    'last_edited_author',
    'last_edited_author',
    'ws_agency_last_edited_author',
    'ws_lu_last_edited_author',
    'ws_ao_last_edited_author',
    'ws_interp_last_edited_author',
];

foreach ( $ws_lock_field_names as $_ws_field_name ) {
    add_filter( "acf/load_field/name={$_ws_field_name}", 'ws_acf_lock_for_non_admins' );
}
unset( $_ws_field_name );

/**
 * Sets a field to readonly and disabled for any user below administrator.
 *
 * Registered via the loop above for all date_created, last_edited, and
 * last_edited_author fields. ACF's 'disabled' attribute prevents the value
 * from being submitted, preserving the stored value on save.
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


// ── Auto-fill today: last_reviewed text fields on new posts ───────────────────
//
// When a new jx-* record is opened for the first time, last_reviewed text
// fields default to today's date so editors don't have to fill them manually.
// The filter only fires when the stored value is empty, so existing records
// are not affected.

$ws_autofill_today_fields = [
    'ws_jx_sum_last_reviewed',
    'ws_jx_cite_last_reviewed',
    'ws_jx_statute_last_reviewed',
    'ws_agency_last_reviewed',
    'ws_ao_last_reviewed',
    'ws_interp_last_reviewed',
];

foreach ( $ws_autofill_today_fields as $_ws_field_name ) {
    add_filter( "acf/load_value/name={$_ws_field_name}", 'ws_acf_autofill_today', 10, 3 );
}
unset( $_ws_field_name );

/**
 * Returns today's date (Y-m-d) when a last_reviewed field has no stored value.
 *
 * @param  mixed  $value    Current field value.
 * @param  int    $post_id  Post being edited.
 * @param  array  $field    ACF field array.
 * @return mixed
 */
function ws_acf_autofill_today( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = date( 'Y-m-d' );
    }
    return $value;
}


// ── Auto-fill editor: pre-fill last_edited_author for administrators ──────────
//
// Administrators see the last_edited_author field pre-filled with themselves.
// This ensures that saving without an explicit change correctly stamps the
// current admin as the last editor — the submitted value will match $user_id
// in ws_acf_write_stamp_fields(), so the "honor override" branch is only
// triggered when the admin has deliberately chosen a different user.
//
// Non-admins: field is locked (disabled) and never submitted, so the stored
// value is displayed for reference and the stamp function handles the write.

$ws_autofill_editor_fields = [
    'ws_jx_sum_last_edited_author',
    'ws_jx_cite_last_edited_author',
    'ws_jx_statute_last_edited_author',
    'last_edited_author',
    'last_edited_author',
    'ws_agency_last_edited_author',
    'ws_lu_last_edited_author',
    'ws_ao_last_edited_author',
    'ws_interp_last_edited_author',
];

foreach ( $ws_autofill_editor_fields as $_ws_field_name ) {
    add_filter( "acf/load_value/name={$_ws_field_name}", 'ws_acf_autofill_current_editor', 10, 3 );
}
unset( $_ws_field_name );

/**
 * Pre-fills last_edited_author with the current user for administrators.
 *
 * Non-admins receive the stored value unchanged (the field is displayed
 * readonly so they can see who last edited the record).
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


// ── Stamp fields: created + last-edited metadata on every ACF save ────────────
//
// Handles created stamps (written once) and last-edited stamps (written every
// save) for jx-* CPTs that carry authorship metadata.
//
// Configuration map: CPT slug → [ meta_prefix, author_acf_key ]
//
//   meta_prefix     — shared prefix for all stamp meta keys on that CPT.
//                     Keys written: {prefix}_date_created,
//                                   {prefix}_date_created_gmt,
//                                   {prefix}_create_author,
//                                   {prefix}_last_edited,
//                                   {prefix}_last_edited_gmt,
//                                   {prefix}_last_edited_author.
//
//   author_acf_key  — ACF field key for the last_edited_author user field.
//                     Used to detect whether an administrator explicitly
//                     submitted a different user via the ACF UI; if so, that
//                     choice is preserved rather than overwriting with the
//                     current user ID.
//
// To add stamp support to a new CPT, add one entry to $ws_stamp_cpts.

$ws_stamp_cpts = [
    'jx-summary'        => [ 'meta_prefix' => 'ws_jx_sum',     'author_acf_key' => 'field_ws_jx_sum_last_edited_author'    ],
    'jx-citation'       => [ 'meta_prefix' => 'ws_jx_cite',    'author_acf_key' => 'field_ws_jx_cite_last_edited_author'   ],
    'jx-statute'        => [ 'meta_prefix' => 'ws_jx_statute', 'author_acf_key' => 'field_jx_statute_last_edited_author'   ],
    ''      => [ 'meta_prefix' => 'ws_jx_proc',    'author_acf_key' => 'field_last_edited_author'   ],
    ''       => [ 'meta_prefix' => 'ws_jx_res',     'author_acf_key' => 'field_last_edited_author'    ],
    'jx-interpretation' => [ 'meta_prefix' => 'ws_interp',     'author_acf_key' => 'field_ws_interp_last_edited_author'    ],
    'ws-agency'         => [ 'meta_prefix' => 'ws_agency',     'author_acf_key' => 'field_ws_agency_last_edited_author'    ],
    'ws-legal-update'   => [ 'meta_prefix' => 'ws_lu',         'author_acf_key' => 'field_ws_lu_last_edited_author'        ],
    'ws-assist-org'     => [ 'meta_prefix' => 'ws_ao',         'author_acf_key' => 'field_ws_ao_last_edited_author'        ],
];

add_action( 'acf/save_post', 'ws_acf_write_stamp_fields', 20 );

/**
 * Writes created and last-edited stamp fields for supported jx-* CPTs.
 *
 * Runs at priority 20 (after ACF commits its own fields at priority 10).
 * CPT support is declared in the $ws_stamp_cpts configuration map above.
 *
 * last_edited_author logic:
 *   - Non-admin saves: field is disabled and not submitted ($posted_user = 0)
 *     → always stamp current user.
 *   - Admin saves without changing: ws_acf_autofill_current_editor() pre-fills
 *     the field with the current admin, so submitted value equals $user_id
 *     → falls to else, stamps current user.
 *   - Admin saves with a deliberately different user selected: submitted value
 *     differs from $user_id → honor the attribution override.
 *
 * @param  int|string $post_id  Post ID passed by acf/save_post.
 */
function ws_acf_write_stamp_fields( $post_id ) {

    global $ws_stamp_cpts;

    $post_type = get_post_type( $post_id );
    if ( ! isset( $ws_stamp_cpts[ $post_type ] ) ) {
        return;
    }

    $p       = $ws_stamp_cpts[ $post_type ]['meta_prefix'];
    $acf_key = $ws_stamp_cpts[ $post_type ]['author_acf_key'];

    $now_local = current_time( 'Y-m-d' );
    $now_gmt   = current_time( 'mysql', true );
    $now_gmt_d = substr( $now_gmt, 0, 10 );
    $user_id   = get_current_user_id();

    // ── Created stamps (once only) ────────────────────────────────────────

    if ( ! get_post_meta( $post_id, "{$p}_date_created", true ) ) {
        update_post_meta( $post_id, "{$p}_date_created",     $now_local );
        update_post_meta( $post_id, "{$p}_date_created_gmt", $now_gmt_d );
        update_post_meta( $post_id, "{$p}_create_author",    $user_id );
    }

    // ── Last-edited stamps (every save) ───────────────────────────────────

    update_post_meta( $post_id, "{$p}_last_edited",     $now_local );
    update_post_meta( $post_id, "{$p}_last_edited_gmt", $now_gmt_d );

    // ── Last-edited author ────────────────────────────────────────────────
    // Honor admin attribution override; stamp current user in all other cases.

    $posted_user = isset( $_POST['acf'][ $acf_key ] ) ? (int) $_POST['acf'][ $acf_key ] : 0;
    $is_admin    = current_user_can( 'manage_options' );

    if ( $is_admin && $posted_user && $posted_user !== $user_id ) {
        update_post_meta( $post_id, "{$p}_last_edited_author", $posted_user );
    } else {
        update_post_meta( $post_id, "{$p}_last_edited_author", $user_id );
    }
}
