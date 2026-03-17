<?php
/**
 * File: admin-hooks.php
 *
 * Purpose: Shared ACF admin hooks used across all jx-* CPTs and related
 * content types. Centralises four cross-CPT behaviours that were previously
 * duplicated in every ACF registration file:
 *
 *   1. URL pre-population   — auto-assigns ws_jurisdiction taxonomy term and
 *                             post title on new-post screens opened from the
 *                             "Create Now" links in admin-navigation.php.
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
 * save_post_jurisdiction hook below. Provides a direct post→term_id mapping
 * for seeder and query layer use.
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
 * 3.0.0  Architecture refactor (Phase 3.2):
 *        - Removed ws_jx_code pre-populate filter (ws_jx_code retired).
 *        - Removed deleted CPT entries from stamp config.
 *        - Added save_post_jurisdiction hook writing ws_jx_term_id post meta.
 *        - Added wp_insert_post hook auto-assigning ws_jurisdiction taxonomy
 *          term on new addendum post creation (reads ws_jx_term URL arg).
 * 3.0.1  Phase 8: Added ws_languages "additional" term auto-assign/unassign
 *        hook for ws-agency (ws_agency_additional_languages) and
 *        ws-assist-org (ws_ao_additional_languages).
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
    $term      = get_term_by( 'slug', $term_slug, 'ws_jurisdiction' );
    if ( $term && ! is_wp_error( $term ) ) {
        wp_set_object_terms( $post_id, $term->term_id, 'ws_jurisdiction' );
    }
}, 10, 3 );


// ── Write ws_jx_term_id post meta on jurisdiction save ────────────────────────
//
// Stores the term ID of the assigned ws_jurisdiction term as post meta on each
// jurisdiction post. Provides a direct post→term_id mapping used by seeders and
// admin tooling without requiring a get_term_by() lookup at runtime.

add_action( 'save_post_jurisdiction', function( $post_id ) {
    if ( wp_is_post_revision( $post_id ) ) return;

    $terms = wp_get_post_terms( $post_id, 'ws_jurisdiction' );
    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        update_post_meta( $post_id, 'ws_jx_term_id', $terms[0]->term_id );
    }
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
    // summarized_by / summarized_date — stamped once (Phase 9.1), never editable
    'summarized_by',
    'summarized_date',
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


// ── summarized_by + summarized_date one-time stamps (Phase 9) ────────────────
//
// Stamped once when plain language content is first saved on a supported CPT.
// For jx-summary the stamp fires on every first save (it IS the plain language doc).
// For all other CPTs the stamp is deferred until has_plain_english is enabled.
//
// Meta keys: jx-summary uses ws_jx_sum_summarized_by / ws_jx_sum_summarized_date
// (prefixed to avoid collision with the standard stamp system). All other CPTs
// use the generic summarized_by / summarized_date keys matching the ACF field names.
//
// Runs at priority 25 (after ACF fields at 10, after main stamps at 20).

add_action( 'acf/save_post', 'ws_acf_stamp_summarized_fields', 25 );

function ws_acf_stamp_summarized_fields( $post_id ) {

    $supported = [
        'jx-summary',
        'jx-statute', 'jx-citation', 'jx-interpretation',
        'ws-agency', 'ws-assist-org',
    ];

    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, $supported, true ) ) {
        return;
    }

    $is_summary = ( $post_type === 'jx-summary' );

    $date_key = $is_summary ? 'ws_jx_sum_summarized_date' : 'summarized_date';
    $by_key   = $is_summary ? 'ws_jx_sum_summarized_by'   : 'summarized_by';

    // Only stamp once.
    if ( get_post_meta( $post_id, $date_key, true ) ) {
        return;
    }

    // Non-summary CPTs: defer stamp until has_plain_english is enabled.
    if ( ! $is_summary && ! get_post_meta( $post_id, 'has_plain_english', true ) ) {
        return;
    }

    update_post_meta( $post_id, $date_key, current_time( 'Y-m-d' ) );
    update_post_meta( $post_id, $by_key,   get_current_user_id() );
}


// ── Auto-assign ws_languages "additional" term ────────────────────────────────
//
// When ws_agency_additional_languages (ws-agency) or ws_ao_additional_languages
// (ws-assist-org) is non-empty, the "additional" ws_languages term is assigned
// automatically so the taxonomy filter can surface these records.
// When the field is cleared, the term is removed.
//
// Runs at priority 25 (after ACF fields commit at 10, after stamps at 20).

add_action( 'acf/save_post', 'ws_sync_additional_languages_term', 25 );

function ws_sync_additional_languages_term( $post_id ) {

    $post_type = get_post_type( $post_id );

    if ( $post_type === 'ws-agency' ) {
        $meta_key = 'ws_agency_additional_languages';
    } elseif ( $post_type === 'ws-assist-org' ) {
        $meta_key = 'ws_ao_additional_languages';
    } else {
        return;
    }

    $additional_term = get_term_by( 'slug', 'additional', 'ws_languages' );
    if ( ! $additional_term || is_wp_error( $additional_term ) ) {
        return; // Taxonomy not yet seeded — bail silently.
    }

    $value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );

    if ( $value !== '' ) {
        // Non-empty: ensure "additional" term is assigned (append=true preserves existing terms).
        wp_set_object_terms( $post_id, $additional_term->term_id, 'ws_languages', true );
    } else {
        // Empty: remove "additional" term only.
        wp_remove_object_terms( $post_id, $additional_term->term_id, 'ws_languages' );
    }
}
