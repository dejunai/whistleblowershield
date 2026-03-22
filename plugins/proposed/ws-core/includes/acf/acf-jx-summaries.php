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
 *   date_created      Local date (Y-m-d)
 *   date_created_gmt  UTC date (Y-m-d)
 *   create_author     User ID of creating user
 *
 * Written on every save:
 *   last_edited       Local date (Y-m-d)
 *   last_edited_gmt   UTC date (Y-m-d)
 *   last_edited_author  User ID — stamped automatically,
 *                        visible and editable by admins only.
 *
 * date_created is rendered readonly at the bottom of the
 * Authorship & Review tab for admin reference. last_edited_author
 * also appears in Authorship & Review (readonly for non-admins,
 * editable for administrators). create_author is displayed readonly.
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
 * 3.1.1  Pass 2 ACF audit fixes:
 *        - Renamed field key field_jx_sum_plain_english_reviewed → field_plain_english_reviewed
 *          for consistency with all other CPTs. Removed inline @todo comments.
 *        - Renamed field key field_ws_jx_sum_plain_english_by_temp → field_plain_english_by.
 *          Corrected meta name from ws_jx_sum_create_author → plain_english_by to match
 *          the canonical name written by ws_acf_stamp_summarized_fields() in admin-hooks.php.
 *          Previously this field always displayed blank (stamp writes plain_english_by;
 *          field read ws_jx_sum_create_author — two different meta keys).
 * 3.1.2  Pass 3 ACF audit — instructions fixes:
 *        - plain_english_reviewed_by instructions: aligned with other CPTs
 *          ('Auto-stamped when Plain Language Reviewed is first enabled.').
 *        - plain_english_by instructions: aligned with other CPTs
 *          ('Auto-stamped on first save after plain language content is created.').
 * 3.4.0  Stamp field centralization:
 *        - Removed stamp fields (last_edited_author, date_created, last_edited,
 *          create_author) from Authorship & Review tab — now registered centrally
 *          in acf-stamp-fields.php (group_stamp_metadata, menu_order 90).
 *        - Renamed field key field_ws_jx_sum_plain_english_reviewed_by to
 *          field_plain_english_reviewed_by for consistency with all other CPTs.
 *          No downstream impact — admin-hooks.php references this field by meta
 *          name not ACF key; query and render layers read post meta directly.
 */

defined( 'ABSPATH' ) || exit;

// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_jx_summary' );

function ws_register_acf_jx_summary() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_jx_summary_metadata',
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
                'key'   => 'field_jx_sum_content_tab',
                'label' => 'Content',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_jurisdiction_summary',
                'label'        => 'Jurisdiction Summary',
                'name'         => 'ws_jurisdiction_summary_wysiwyg',
                'type'         => 'wysiwyg',
                'instructions' => '<strong>IMPORTANT:</strong> Use the editor toolbar for all formatting. Do NOT paste raw Markdown (**, ##, ---). Content must be clean HTML. This field is rendered directly on the jurisdiction page.',
                'required'     => 1,
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
                'delay'        => 0,
            ],
            [
                'key'          => 'field_jx_summary_sources',
                'label'        => 'Sources & Citations',
                'name'         => 'ws_jx_summary_sources',
                'type'         => 'textarea',
                'instructions' => 'List source citations, statute references, and attribution. One per line recommended.',
                'rows'         => 6,
            ],
            [
                'key'          => 'field_jx_summary_notes',
                'label'        => 'Internal Notes',
                'name'         => 'ws_jx_summary_notes',
                'type'         => 'textarea',
                'instructions' => 'Internal editorial notes only. Not displayed publicly.',
                'rows'         => 4,
            ],
            [
                'key'          => 'field_jx_limitations',
                'label'        => 'Limitations & Ramifications',
                'name'         => 'ws_jx_limitations_wysiwyg',
                'type'         => 'wysiwyg', // @todo - consider using 'repeater' type
                'instructions' => 'Content for the Limitations and Ramifications section. Rendered automatically after the case law section on the jurisdiction page via [ws_jx_limitations]. Use the editor toolbar for all formatting — do NOT paste raw Markdown.',
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
                'delay'        => 0,
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            //
            // jx-summary is the plain language document — it does not use
            // the has_plain_english / plain_english_reviewed pathway used
            // by other CPTs. Instead it carries its own reviewed fields here
            // with semantics appropriate to summary review (not translation).
            //
            // Stamp fields (last_edited_author, date_created, last_edited,
            // create_author) are registered centrally in acf-stamp-fields.php
            // and appear via that group's Authorship & Review tab (menu_order 90).

            [
                'key'   => 'field_jx_sum_authorship_tab',
                'label' => 'Summary Review',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_plain_english_reviewed',
                'label'         => 'Plain Language Reviewed',
                'name'          => 'plain_english_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human has reviewed and approved this plain-language summary.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_plain_english_by',
                'label'         => 'Summarized By',
                'name'          => 'plain_english_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped on first save after plain language content is created.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],
            [
                'key'           => 'field_plain_english_reviewed_by',
                'label'         => 'Reviewed By',
                'name'          => 'plain_english_reviewed_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped when Plain Language Reviewed is first enabled.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],


        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


