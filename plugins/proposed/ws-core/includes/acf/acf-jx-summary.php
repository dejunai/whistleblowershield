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
 * management, plain-language review tracking, and stamp data.
 *
 * JURISDICTION SCOPING
 * --------------------
 * jx-summary records are scoped to their parent jurisdiction via the
 * ws_jurisdiction taxonomy term. The ws_jx_code back-reference field
 * and admin-relationships.php sync logic were removed in Phase 3.2/3.6.
 * Auto-assignment of the ws_jurisdiction term on "Create Now" is
 * handled by the wp_insert_post hook in admin-hooks.php.
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
 * 3.0.0  Phase 9.0+9.1 refactor:
 *        - Removed ws_jx_sum_legal_review_completed and ws_jx_sum_legal_reviewer
 *          (legal review badge system removed entirely).
 *        - Replaced ws_jx_sum_human_reviewed with canonical plain_reviewed toggle.
 *        - Added summarized_by (user, stamped once) and summarized_date (text, stamped once).
 *        - Removed Relationships tab and ws_jx_code field (retired in Phase 3.2;
 *          jx-summary is now scoped via ws_jurisdiction taxonomy).
 *        - Back-reference note in PURPOSE removed (admin-relationships.php deleted Phase 3.6).
 */

defined( 'ABSPATH' ) || exit;

// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_jx_summary' );

function ws_register_acf_jx_summary() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_jx_summary',
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
                'key'   => 'field_ws_jx_sum_content_tab',
                'label' => 'Content',
                'type'  => 'tab',
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
                'type'         => 'wysiwyg', // @todo - consider using 'repeater' type
                'instructions' => 'Content for the Limitations and Ramifications section. Rendered automatically after the case law section on the jurisdiction page via [ws_jx_limitations]. Use the editor toolbar for all formatting — do NOT paste raw Markdown.',
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
                'delay'        => 0,
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_authorship_tab',
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
                'return_format' => 'array',
            ],
            [
                'key'           => 'field_ws_jx_sum_plain_reviewed',
                'label'         => 'Plain Language Reviewed', //@todo - should only be readonly to author-rank
                'name'          => 'plain_reviewed',          //        while summary exists but is not reviewed, should be tracked in admin-panel
                'type'          => 'true_false',              //        we need to capture user and store at 'ws_jx_sum_last_reviewed_by'
															  //        'ws_jx_sum_last_reviewed_by' needs to reveal on toggle, autostamp current user,
															  //        and be readonly -- editable only by admin
                'instructions'  => 'Check when a human has reviewed and approved this plain-language summary.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],
			//@todo - 'ws_jx_sum_create_author' - inadverantly removed, re-add
            [
                'key'          => 'field_ws_jx_summarized_by',
                'label'        => 'Summarized By',
                'name'         => 'ws_jx_summarized_by', //@todo - duplicate data to 'ws_jx_sum_create_author', unecessary meta_data
                'type'         => 'user',
                'instructions' => 'Stamped automatically on first save. Identifies who created the plain-language content.',
                'role'         => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'     => 1,
                'disabled'     => 1,
            ],
            [
                'key'          => 'field_ws_jx_summarized_date', //@todo - duplicate data to 'ws_jx_sum_date_created', unecessary meta_data
                'label'        => 'Summarized Date',
                'name'         => 'ws_jx_summarized_date',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            // ── Dates (bottom of Authorship & Review) ─────────────────────
            //
            // All date fields are text type to support readonly rendering.
            // ws_jx_sum_date_created is stamped once on first save.
            // ws_jx_sum_last_edited is stamped on every save.
            // ws_jx_sum_last_reviewed is editable — update manually on
            // meaningful content revisions. GMT variants and create_author
            // are written server-side only and do not appear in the form.

            [
                'key'          => 'field_ws_jx_sum_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_sum_date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.', //@todo - should be hidden
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '50' ],
            ],
            [
                'key'          => 'field_ws_jx_sum_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'ws_jx_sum_last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '50' ],
            ],
            [
                'key'          => 'field_ws_jx_sum_last_reviewed', //@todo - should be hidden until plain_reviewed is true, at which should autofill and be readonly, instructions need to updated.
                'label'        => 'Last Reviewed',
                'name'         => 'ws_jx_sum_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the summary content is meaningfully revised. This date is displayed publicly on the jurisdiction page.',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


