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
 *   2. Field locking        — makes date_created, last_edited, last_edited_author,
 *                             plain_reviewed_by, and last_reviewed readonly +
 *                             disabled for non-administrators or non-editors.
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
 *   7. Summarized stamps    — writes summarized_by / summarized_date to post
 *                             meta once when plain English content is first
 *                             saved on supported CPTs.
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
 * All CPTs share identical unprefixed stamp meta key names. WordPress post meta
 * is scoped to post_id so there is no collision risk across CPTs.
 *
 *   date_created        — local date (Y-m-d), written once
 *   date_created_gmt    — GMT date (Y-m-d), written once
 *   create_author       — WP user ID, written once
 *   last_edited         — local date (Y-m-d), written every save
 *   last_edited_gmt     — GMT date (Y-m-d), written every save
 *   last_edited_author  — WP user ID, written every save (admin-overridable)
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
//   Admin-only fields  — date_created, last_edited, last_edited_author,
//                        summarized_by, summarized_date.
//                        Locked for any role below administrator.
//                        last_edited_author is admin-overridable for attribution
//                        correction on minor edits.
//
//   Editor-only fields — last_reviewed, plain_reviewed_by.
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

foreach ( [ 'date_created', 'last_edited', 'last_edited_author', 'summarized_by', 'summarized_date' ] as $_ws_f ) {
    add_filter( "acf/load_field/name={$_ws_f}", 'ws_acf_lock_for_non_admins' );
}
unset( $_ws_f );

foreach ( [ 'last_reviewed', 'plain_reviewed_by' ] as $_ws_f ) {
    add_filter( "acf/load_field/name={$_ws_f}", 'ws_acf_lock_for_non_editors' );
}
unset( $_ws_f );

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


// ── Auto-fill today: last_reviewed on existing posts when plain_reviewed is on ─
//
// Fills last_reviewed with today's date only when all three conditions hold:
//   1. The stored value is empty (never reviewed or cleared by toggle-off).
//   2. The post exists (post_id > 0 — excludes new-post / options context).
//   3. plain_reviewed is already enabled on this post.
//
// This prevents last_reviewed from pre-filling on posts where plain English
// content has not yet been reviewed, and never fires on brand-new posts.

add_filter( 'acf/load_value/name=last_reviewed', 'ws_acf_autofill_today', 10, 3 );

/**
 * Returns today's date (Y-m-d) when last_reviewed is empty and plain_reviewed
 * is enabled on the post.
 *
 * @param  mixed  $value    Current field value.
 * @param  int    $post_id  Post being edited.
 * @param  array  $field    ACF field array.
 * @return mixed
 */
function ws_acf_autofill_today( $value, $post_id, $field ) {
    if ( empty( $value ) && $post_id > 0 && get_post_meta( $post_id, 'plain_reviewed', true ) ) {
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

add_filter( 'acf/load_value/name=last_edited_author', 'ws_acf_autofill_current_editor', 10, 3 );

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
//   Rule 2 — plain_reviewed requires editor rank or above. If the submitted
//             value is 1 but the current user is below editor, plain_reviewed
//             and plain_reviewed_by are wiped and an admin notice is queued.
//
//   Rule 3 — plain_reviewed toggle-off cleanup. If has_plain_english transitions
//             from 1 to 0, plain_reviewed and plain_reviewed_by are cleared.
//             The plain_english string is preserved in case the admin re-enables.
//             summarized_by and summarized_date are also cleared.
//
//   //@todo — Revisit Rule 3 when plain_english change-detection is implemented.
//             A future pass should compare pre/post save values of plain_english
//             to detect content changes and conditionally reset plain_reviewed.
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
            case 'plain_english':
                $submitted_plain_english = trim( (string) $field_value );
                break;
            case 'has_plain_english':
                $submitted_has_plain = (int) $field_value;
                break;
            case 'plain_reviewed':
                $submitted_plain_reviewed = (int) $field_value;
                break;
        }
    }

    // ── Rule 1: has_plain_english requires non-empty plain_english ────────────

    if ( $submitted_has_plain && $submitted_plain_english === '' ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( $field_obj && $field_obj['name'] === 'has_plain_english' ) {
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
            if ( in_array( $field_obj['name'], [ 'plain_reviewed', 'plain_reviewed_by' ], true ) ) {
                $_POST['acf'][ $field_key ] = 0;
            }
        }
        set_transient( 'ws_plain_reviewed_rank_notice_' . get_current_user_id(), true, 30 );
    }

    // ── Rule 3: has_plain_english toggle-off clears plain_reviewed fields ─────

    $stored_has_plain = (int) get_post_meta( $post_id, 'has_plain_english', true );

    if ( $stored_has_plain && ! $submitted_has_plain ) {
        foreach ( $_POST['acf'] as $field_key => $field_value ) {
            $field_obj = acf_get_field( $field_key );
            if ( ! $field_obj ) continue;
            if ( in_array( $field_obj['name'], [ 'plain_reviewed', 'plain_reviewed_by' ], true ) ) {
                $_POST['acf'][ $field_key ] = 0;
            }
        }
        // Clear summarized stamp meta written by ws_acf_stamp_summarized_fields().
        delete_post_meta( $post_id, 'summarized_by' );
        delete_post_meta( $post_id, 'summarized_date' );
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
        . 'The plain_reviewed and plain_reviewed_by fields were not saved.</p>'
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
// All CPTs share identical unprefixed stamp meta key names. WordPress post meta
// is scoped to post_id so there is no collision risk across post types.
//
// To add stamp support to a new CPT, add one entry to $ws_stamp_cpts.

$ws_stamp_cpts = [
    'jurisdiction'       => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-summary'         => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-citation'        => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-statute'         => [ 'author_acf_key' => 'field_last_edited_author' ],
    'jx-interpretation'  => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-agency'          => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-legal-update'    => [ 'author_acf_key' => 'field_last_edited_author' ],
    'ws-assist-org'      => [ 'author_acf_key' => 'field_last_edited_author' ],
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

    if ( ! get_post_meta( $post_id, 'date_created', true ) ) {
        update_post_meta( $post_id, 'date_created',     $now_local );
        update_post_meta( $post_id, 'date_created_gmt', $now_gmt );
        update_post_meta( $post_id, 'create_author',    $user_id );
    }

    // ── Last-edited stamps (every save) ───────────────────────────────────

    update_post_meta( $post_id, 'last_edited',     $now_local );
    update_post_meta( $post_id, 'last_edited_gmt', $now_gmt );

    // ── Last-edited author ────────────────────────────────────────────────
    // Honour admin attribution override; stamp current user in all other cases.

    $posted_user = isset( $_POST['acf'][ $acf_key ] ) ? (int) $_POST['acf'][ $acf_key ] : 0;
    $is_admin    = current_user_can( 'manage_options' );

    if ( $is_admin && $posted_user && $posted_user !== $user_id ) {
        update_post_meta( $post_id, 'last_edited_author', $posted_user );
    } else {
        update_post_meta( $post_id, 'last_edited_author', $user_id );
    }
}


// ── plain_reviewed_by one-time stamp ─────────────────────────────────────────
//
// Stamped once when plain_reviewed is first enabled on a supported CPT.
// Cleared by ws_acf_plain_english_guards() (priority 5) when plain_reviewed
// is toggled off, allowing a fresh stamp on the next toggle-on.
//
// Applies to: jx-statute, jx-citation, jx-interpretation, ws-agency, ws-assist-org.
// jx-summary is excluded — it is inherently plain English.
//
// Runs at priority 25 (after ACF fields at 10, after main stamps at 20).

add_action( 'acf/save_post', 'ws_acf_stamp_plain_reviewed_by', 25 );

/**
 * Writes plain_reviewed_by once when plain_reviewed is first enabled.
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

    if ( ! get_post_meta( $post_id, 'plain_reviewed', true ) ) {
        return;
    }

    if ( get_post_meta( $post_id, 'plain_reviewed_by', true ) ) {
        return;
    }

    // Safety net: require editor rank (primary enforcement is at priority 5).
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        return;
    }

    update_post_meta( $post_id, 'plain_reviewed_by', get_current_user_id() );
}


// ── summarized_by + summarized_date one-time stamps ───────────────────────────
//
// Stamped once when plain English content is first saved on a supported CPT
// and has_plain_english is enabled.
//
// jx-summary is excluded — it is inherently plain English and carries no
// has_plain_english toggle.
//
// Cleared on has_plain_english toggle-off by ws_acf_plain_english_guards()
// at priority 5.
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

    if ( ! get_post_meta( $post_id, 'has_plain_english', true ) ) {
        return;
    }

    if ( get_post_meta( $post_id, 'summarized_date', true ) ) {
        return;
    }

    update_post_meta( $post_id, 'summarized_date', current_time( 'Y-m-d' ) );
    update_post_meta( $post_id, 'summarized_by',   get_current_user_id() );
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
        wp_set_object_terms( $post_id, $additional_term->term_id, 'ws_languages', true );
    } else {
        wp_remove_object_terms( $post_id, $additional_term->term_id, 'ws_languages' );
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
// Remove 'Add New' from the ws-jurisdiction CPT menu entirely
add_action( 'admin_menu', function() {
    remove_submenu_page( 'edit.php?post_type=ws-jurisdiction', 'post-new.php?post_type=ws-jurisdiction' );
} );

// Redirect anyone who reaches the new post screen anyway
add_action( 'load-post-new.php', function() {
    if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'ws-jurisdiction' ) {
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
// ---------------------------------------------------------------
// Jurisdiction CPT — Enforce Manual Editing Restrictions
// ---------------------------------------------------------------
// Direct get_post_meta() call is intentional here. ws_matrix_source is an
// administrative flag written by the seeder and consumed exclusively by admin
// tooling. It is not jurisdiction content and does not belong in the query layer.

add_action( 'acf/save_post', function( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-jurisdiction' ) {
        return;
    }

    $matrix_source = get_post_meta( $post_id, 'ws_matrix_source', true );
    if ( ! $matrix_source ) {
        return;
    }

    $matrix = ws_get_jurisdiction_matrix();
    $key    = strtoupper( $matrix_source );

    if ( ! isset( $matrix[ $key ] ) ) {
        return;
    }

    $entry = $matrix[ $key ];

    update_field( 'field_jx_code',            $entry['ws_jx_code'],            $post_id );
    update_field( 'field_jurisdiction_class', $entry['ws_jurisdiction_class'], $post_id );
    update_field( 'field_jurisdiction_name',  $entry['ws_jurisdiction_name'],  $post_id );

}, 1 );
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