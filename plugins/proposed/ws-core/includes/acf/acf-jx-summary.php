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
                'return_format' => 'value',
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
                'instructions' => 'Set automatically on first save. Read only.',
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
                'key'          => 'field_ws_summary_jx_code',
                'label'        => 'Jurisdiction Code',
                'name'         => 'ws_jx_code',
                'type'         => 'text',
                'instructions' => 'USPS code for the parent jurisdiction (e.g., CA, TX, US). Required for relationship sync. Pre-populated automatically when created via the Jurisdiction editor.',
                'required'     => 1,
                'maxlength'    => 2,
                'placeholder'  => 'CA',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


