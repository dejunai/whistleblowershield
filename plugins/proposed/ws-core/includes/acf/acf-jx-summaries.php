<?php
/**
 * acf-jx-summaries.php — ACF Pro fields for the jx-summary CPT.
 *
 * Group key: group_jx_summary_metadata
 * Stamp fields: group_stamp_metadata (acf-stamp-fields.php, menu_order 90)
 * Source verify: group_source_verify_metadata (acf-source-verify.php)
 * Major edit: group_major_edit_metadata (acf-major-edit.php, menu_order 99)
 *
 * Note: jx-summary IS the plain language document. Plain English fields
 * (group_plain_english_metadata) do NOT attach here — the summary carries
 * its own plain_reviewed toggle in this group instead.
 *
 * @package WhistleblowerShield
 * @since   2.1.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 2.1.0   Initial release.
 * 3.0.0   ws_jx_code back-reference and admin-relationships sync removed.
 * 3.4.0   Stamp fields centralized to acf-stamp-fields.php.
 * 3.10.0  Inline comments updated for consistency with current conventions.
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
                'key'          => 'field_jx_limitations',
                'label'        => 'Limitations & Ramifications',
                'name'         => 'ws_jx_limitations',
                'type'         => 'repeater',
                'instructions' => 'Each row is one limitation. Label: short name shown in bold (e.g. "Media Reporting"). Description: plain-language explanation. Rendered automatically after the case law section via [ws_jx_limitations].',
                'button_label' => 'Add Limitation',
                'layout'       => 'table',
                'min'          => 0,
                'max'          => 0,
                'sub_fields'   => [
                    [
                        'key'          => 'field_jx_limit_label',
                        'label'        => 'Label',
                        'name'         => 'ws_jx_limit_label',
                        'type'         => 'text',
                        'instructions' => 'Short bold heading (e.g. "Media Reporting", "Personal Grievances").',
                        'required'     => 1,
                        'wrapper'      => [ 'width' => '25' ],
                    ],
                    [
                        'key'          => 'field_jx_limit_text',
                        'label'        => 'Description',
                        'name'         => 'ws_jx_limit_text',
                        'type'         => 'textarea',
                        'instructions' => 'Plain-language explanation. No HTML.',
                        'required'     => 1,
                        'rows'         => 3,
                        'wrapper'      => [ 'width' => '75' ],
                    ],
                ],
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
                'key'           => 'field_jx_sum_plain_reviewed',
                'label'         => 'Plain Language Reviewed',
                'name'          => 'ws_plain_english_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human has reviewed and approved this plain-language summary.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


