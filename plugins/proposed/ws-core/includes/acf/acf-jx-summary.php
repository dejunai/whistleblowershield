<?php
/**
 * acf-jx-summary.php
 *
 * Registers ACF Pro fields for the `jx-summary` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Jurisdiction Summary records.
 * The main editorial content is written in the WordPress block
 * editor. These fields supply supporting metadata for content
 * management, legal review tracking, and relationship management.
 *
 * BACK-REFERENCE FIELD
 * --------------------
 * ws_jurisdiction links this summary back to its parent
 * Jurisdiction record. This field is required by
 * ws_sync_jurisdiction_relationships() in admin-relationships.php
 * to maintain two-way relationship consistency.
 *
 * When this record is saved, the sync function reads ws_jurisdiction
 * and writes this post's ID into ws_related_summary on the parent
 * Jurisdiction record automatically.
 *
 * STAMP FIELDS
 * ------------
 * The following fields are written server-side via acf/save_post
 * at priority 20 (after ACF commits its own fields at priority 10).
 *
 * Written once, never overwritten:
 *   ws_jx_sum_date_created      Local date (Y-m-d)
 *   ws_jx_sum_date_created_gmt  UTC date (Y-m-d)
 *   ws_jx_sum_create_author     User ID of creating user
 *
 * Written on every save:
 *   ws_jx_sum_last_edited       Local date (Y-m-d)
 *   ws_jx_sum_last_edited_gmt   UTC date (Y-m-d)
 *   ws_jx_sum_last_edited_author  User ID — stamped automatically,
 *                                 visible and editable by admins only.
 *
 * ws_jx_sum_date_created is rendered readonly at the bottom of the
 * Authorship & Review tab for admin reference. The remaining stamp
 * fields are not shown in the form, with the exception of
 * ws_jx_sum_last_edited_author which also appears in Authorship &
 * Review (readonly for non-admins, editable for administrators).
 * ws_jx_sum_last_reviewed appears as an editable text field above
 * the date stamps in the same tab.
 *
 * @package    WhistleblowerShield
 * @since      2.1.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.1.0  Initial release in ws-core architecture. CPT slug
 *        corrected to jx-summary (hyphenated). Added ws_jurisdiction
 *        back-reference field to support two-way relationship
 *        sync via admin-relationships.php.
 * 2.3.0  Added ws_jx_limitations wysiwyg field to Content tab.
 *        Rendered via [ws_jx_limitations] shortcode in the assembler
 *        after [ws_jx_case_law]. ws-case-law section removed from
 *        ws_jurisdiction_summary wysiwyg — now managed entirely
 *        via jx-citation CPT records.
 */

defined( 'ABSPATH' ) || exit;

// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_jx_summary' );

function ws_register_acf_jx_summary() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_jx_summary',
        'title'                 => 'Jurisdiction Summary Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-summary',
        ] ] ],

        'fields' => [

            // ── Tab: Content ──────────────────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_content',
                'label' => 'Content',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_ws_jx_sum_jurisdiction_type',
                'label'         => 'Jurisdiction Type',
                'name'          => 'ws_jx_sum_jurisdiction_type',
                'type'          => 'select',
                'required'      => 1,
                'choices'       => [
                    'state'     => 'U.S. State',
                    'federal'   => 'Federal',
                    'territory' => 'U.S. Territory',
                    'district'  => 'District (D.C.)',
                ],
                'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
            ],
            [
                'key'          => 'field_ws_jx_sum_jurisdiction',
                'label'        => 'Jurisdiction',
                'name'         => 'ws_jx_sum_jurisdiction',
                'type'         => 'text',
                'instructions' => 'Jurisdiction display name — e.g., California.',
                'required'     => 1,
            ],
            [
                'key'          => 'field_ws_jurisdiction_summary',
                'label'        => 'Jurisdiction Summary',
                'name'         => 'ws_jurisdiction_summary',
                'type'         => 'wysiwyg',
                'instructions' => '<strong>IMPORTANT:</strong> Use the editor toolbar for all formatting. Do NOT paste raw Markdown (**, ##, ---). Content must be clean HTML. This field is rendered directly on the jurisdiction page.',
                'required'     => 1,
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
                'delay'        => 0,
            ],
            [
                'key'          => 'field_ws_jx_summary_sources',
                'label'        => 'Sources & Citations',
                'name'         => 'ws_jx_summary_sources',
                'type'         => 'textarea',
                'instructions' => 'List source citations, statute references, and attribution. One per line recommended.',
                'rows'         => 6,
            ],
            [
                'key'          => 'field_ws_jx_summary_notes',
                'label'        => 'Internal Notes',
                'name'         => 'ws_jx_summary_notes',
                'type'         => 'textarea',
                'instructions' => 'Internal editorial notes only. Not displayed publicly.',
                'rows'         => 4,
            ],
            [
                'key'          => 'field_ws_jx_limitations',
                'label'        => 'Limitations & Ramifications',
                'name'         => 'ws_jx_limitations',
                'type'         => 'wysiwyg',
                'instructions' => 'Content for the Limitations and Ramifications section. Rendered automatically after the case law section on the jurisdiction page via [ws_jx_limitations]. Use the editor toolbar for all formatting — do NOT paste raw Markdown.',
                'required'     => 0,
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
                'delay'        => 0,
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_authorship',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_ws_jx_sum_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_jx_sum_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'allow_null'    => 0,
                'multiple'      => 0,
                'return_format' => 'array',
            ],
            [
                'key'           => 'field_ws_jx_sum_human_reviewed',
                'label'         => 'Human Reviewed',
                'name'          => 'ws_jx_sum_human_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human (non-AI) has reviewed and approved this summary.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_ws_jx_sum_legal_review_completed',
                'label'         => 'Legal Review Completed',
                'name'          => 'ws_jx_sum_legal_review_completed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a licensed attorney has reviewed this summary.',
                'ui'            => 1,
                'ui_on_text'    => 'Completed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'               => 'field_ws_jx_sum_legal_reviewer',
                'label'             => 'Legal Reviewer',
                'name'              => 'ws_jx_sum_legal_reviewer',
                'type'              => 'text',
                'instructions'      => 'Full name of the licensed attorney who reviewed this summary. Populate only when Legal Review Completed is checked.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_ws_jx_sum_legal_review_completed',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ── Dates (bottom of Authorship & Review) ─────────────────────
            //
            // Both fields are text type to support readonly rendering.
            // ws_jx_sum_date_created is stamped once on first save and
            // never overwritten. ws_jx_sum_last_reviewed auto-fills today
            // on new posts and is updated manually on meaningful revisions.
            // GMT variants and last_edited are written server-side only
            // and do not appear in the form.

            [
                'key'          => 'field_ws_jx_sum_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_sum_date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_ws_jx_sum_last_reviewed',
                'label'        => 'Last Reviewed',
                'name'         => 'ws_jx_sum_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the summary content is meaningfully revised. This date is displayed publicly on the jurisdiction page.',
            ],

            // ── Tab: Relationships ────────────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_relationships',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_ws_summary_jurisdiction',
                'label'         => 'Parent Jurisdiction',
                'name'          => 'ws_jurisdiction',
                'type'          => 'post_object',
                'instructions'  => 'Select the Jurisdiction this summary belongs to. Required for relationship sync.',
                'required'      => 1,
                'post_type'     => [ 'jurisdiction' ],
                'allow_null'    => 0,
                'multiple'      => 0,
                'return_format' => 'id',
                'ui'            => 1,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary


// ── Readonly: lock date_created for non-admins ────────────────────────────────
//
// Both readonly and disabled are set to prevent the field from being
// submitted with a changed value. ACF respects 'disabled' on save.

add_filter( 'acf/load_field/name=ws_jx_sum_date_created', 'ws_jx_sum_lock_date_created' );
function ws_jx_sum_lock_date_created( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}


// ── Readonly: lock last_edited_author for non-admins ─────────────────────────

add_filter( 'acf/load_field/name=ws_jx_sum_last_edited_author', 'ws_jx_sum_lock_last_edited_author' );
function ws_jx_sum_lock_last_edited_author( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}


// ── Auto-fill: ws_jx_sum_author (new posts only) ──────────────────────────────

add_filter( 'acf/load_value/name=ws_jx_sum_author', 'ws_jx_sum_autofill_author', 10, 3 );
function ws_jx_sum_autofill_author( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = get_current_user_id();
    }
    return $value;
}


// ── Auto-fill: ws_jx_sum_last_reviewed (new posts only) ──────────────────────

add_filter( 'acf/load_value/name=ws_jx_sum_last_reviewed', 'ws_jx_sum_autofill_last_reviewed', 10, 3 );
function ws_jx_sum_autofill_last_reviewed( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = date( 'Y-m-d' );
    }
    return $value;
}


// ── Stamp fields: written via acf/save_post priority 20 ──────────────────────
//
// Runs after ACF commits its own fields at priority 10, ensuring
// ACF-managed values are already in post meta before we read or
// write anything here.
//
// Created stamps  — written once on first save (empty check).
// Last-edited stamps — written on every save, no empty check.
// GMT values use current_time( 'mysql', true ) which returns UTC.
//
// ws_jx_sum_last_edited_author: if an admin explicitly changed the
// field via ACF this request, that value is already in post meta
// at priority 20 and we leave it alone. For non-admins (whose field
// is disabled and therefore not submitted), we stamp current user.

add_action( 'acf/save_post', 'ws_jx_sum_write_stamp_fields', 20 );
function ws_jx_sum_write_stamp_fields( $post_id ) {

    if ( get_post_type( $post_id ) !== 'jx-summary' ) {
        return;
    }

    $now_local = current_time( 'Y-m-d' );
    $now_gmt   = current_time( 'mysql', true );
    $now_gmt_d = substr( $now_gmt, 0, 10 );
    $user_id   = get_current_user_id();

    // ── Created stamps (once only) ────────────────────────────────────────

    if ( ! get_post_meta( $post_id, 'ws_jx_sum_date_created', true ) ) {
        update_post_meta( $post_id, 'ws_jx_sum_date_created',     $now_local );
        update_post_meta( $post_id, 'ws_jx_sum_date_created_gmt', $now_gmt_d );
        update_post_meta( $post_id, 'ws_jx_sum_create_author',    $user_id );
    }

    // ── Last-edited stamps (every save) ───────────────────────────────────

    update_post_meta( $post_id, 'ws_jx_sum_last_edited',     $now_local );
    update_post_meta( $post_id, 'ws_jx_sum_last_edited_gmt', $now_gmt_d );

    // Stamp last_edited_author only if the field was not submitted
    // (i.e. non-admin whose field is disabled). Admins who changed
    // the field via ACF already have their chosen value in post meta.
    $submitted = isset( $_POST['acf'] ) &&
                 isset( $_POST['acf']['field_ws_jx_sum_last_edited_author'] );

    if ( ! $submitted ) {
        update_post_meta( $post_id, 'ws_jx_sum_last_edited_author', $user_id );
    }
}


// ── Cleanup: retire deprecated meta keys ─────────────────────────────────────
//
// Runs once per site, gated by option flag ws_jx_summary_cleanup_v1.
//
// Keys retired in v2.2.0:
//   ws_summary_last_review  — replaced by ws_jx_sum_last_reviewed
//   ws_summary_notes        — replaced by ws_jx_summary_notes
//
// Once you confirm this has run on the live site (the admin notice
// below will confirm), remove this function and its add_action call.

add_action( 'admin_init', 'ws_jx_summary_cleanup_v1' );
function ws_jx_summary_cleanup_v1() {

    if ( get_option( 'ws_jx_summary_cleanup_v1' ) ) {
        return;
    }

    global $wpdb;

    $retired_keys = [
        'ws_summary_last_review',
        'ws_summary_notes',
    ];

    $total_deleted = 0;
    $results       = [];

    foreach ( $retired_keys as $key ) {
        $deleted = $wpdb->delete(
            $wpdb->postmeta,
            [ 'meta_key' => $key ],
            [ '%s' ]
        );
        if ( $deleted ) {
            $results[ $key ] = $deleted;
            $total_deleted  += $deleted;
        }
    }

    update_option( 'ws_jx_summary_cleanup_v1', true );

    if ( $total_deleted > 0 && is_admin() ) {
        add_action( 'admin_notices', function() use ( $total_deleted, $results ) {
            $detail = implode( ', ', array_map(
                fn( $k, $v ) => "<code>{$k}</code> ({$v})",
                array_keys( $results ),
                $results
            ) );
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>WhistleblowerShield:</strong> Removed ' . $total_deleted . ' retired meta ';
            echo 'entries: ' . $detail . '.';
            echo '</p></div>';
        } );
    }
}
