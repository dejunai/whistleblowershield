<?php
/**
 * acf-jx-statutes.php
 *
 * Registers ACF Pro fields for the `jx-statute` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for individual statutes, moving away from
 * the "blob" model. This enables granular queries for deadlines,
 * enforcement agencies, and misconduct categories.
 *
 * FIELD SUMMARY
 * -------------
 * Legal Basis tab:
 *   ws_jx_statute_official_name   Official statutory name (text, required)
 *   ws_jx_statute_disclosure_type Disclosure Categories taxonomy (multi_select)
 *   attach_flag                   Attach to jurisdiction page (true_false)
 *   order                         Render order (number, conditional on attach_flag)
 *
 * Jurisdiction scope is provided by the ws_jurisdiction taxonomy — the
 * taxonomy term is assigned via the WordPress taxonomy UI, not via an ACF field.
 *
 * Statutes of Limitations tab:
 *   ws_jx_statute_limit_value         Filing Window Value (number)
 *   ws_jx_statute_limit_unit          Time Unit (select)
 *   ws_jx_statute_trigger             Deadline Trigger (select)
 *   ws_jx_statute_tolling_notes       Tolling & Extension Notes (textarea)
 *   ws_jx_statute_exhaustion_required Administrative Exhaustion Required? (true_false)
 *   ws_jx_statute_exhaustion_details  Exhaustion Procedure & Deadline (textarea)
 *   ws_statute_burden_of_proof        Burden of Proof (select)
 *   ws_jx_statute_remedies            Available Remedies (taxonomy checkbox)
 *
 * Relationships tab:
 *   ws_jx_statute_related_agencies    Primary Oversight Agencies (post_object)
 *
 * Authorship & Review tab:
 *   ws_jx_statute_last_edited_author  Last edited by (user, readonly non-admins)
 *   ws_jx_statute_date_created        Date created (text, readonly)
 *   ws_jx_statute_last_edited         Last edited (text, readonly)
 *   ws_jx_statute_last_reviewed       Last reviewed (text)
 *
 * @package    WhistleblowerShield
 * @since      2.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.0.0  Initial release.
 * 3.0.0  Architecture refactor (Phase 3.4):
 *        - Removed ws_jx_code text field (retired; scope now via ws_jurisdiction taxonomy).
 *        - Added attach_flag toggle and order number field.
 *        - Updated docblock to match Phase 3 conventions.
 */

add_action( 'acf/init', 'ws_register_acf_jx_statutes' );

function ws_register_acf_jx_statutes() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_jx_statute_metadata',
        'title'                 => 'Statute Details & Deadlines',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-statute',
        ] ] ],

        'fields' => [

            // ────────────────────────────────────────────────────────────────
            // Tab: Legal Basis
            //
            // Identifies the official name of the law and links it to the
            // canonical jurisdiction code and misconduct taxonomy.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_legal_tab',
                'label' => 'Legal Basis',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_statute_official_name',
                'label'        => 'Official Statutory Name',
                'name'         => 'ws_jx_statute_official_name',
                'type'         => 'text',
                'instructions' => 'Use standard legal notation, e.g., "California Labor Code § 1102.5" or "5 U.S.C. § 2302".',
                'required'     => 1,
            ],

            [
                'key'          => 'field_jx_statute_disclosure_type',
                'label'        => 'Disclosure Categories',
                'name'         => 'ws_jx_statute_disclosure_type',
                'type'         => 'taxonomy',
                'taxonomy'     => 'ws_disclosure_type',
                'field_type'   => 'multi_select',
                'instructions' => 'Classify the types of misconduct this law protects.',
                'add_term'     => 0, //@todo - taxonomy capabilities is handle in register-taxonomies.php by ws_get_taxonomy_caps()
                'save_terms'   => 0, //        consider removal of term attributes
                'load_terms'   => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_jx_statute_attach_flag',
                'label'         => 'Attach to Jurisdiction Page',
                'name'          => 'attach_flag',
                'type'          => 'true_false',
                'instructions'  => 'Enable to include this statute in the rendered statutes section on the jurisdiction page. Disable to store for reference only.',
                'ui'            => 1,
                'ui_on_text'    => 'Attached',
                'ui_off_text'   => 'Unattached',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_order',
                'label'             => 'Display Order',
                'name'              => 'order',
                'type'              => 'number',
                'instructions'      => 'Set the order in which this statute appears on the jurisdiction page. Lower numbers appear first.',
                'min'               => 1,
                'step'              => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_attach_flag',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Statutes of Limitations
            //
            // Structured data for critical filing deadlines. This replaces
            // prose with queryable time units and triggers.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_tab_statute_deadlines',
                'label' => 'Statutes of Limitations',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_statute_limit_value',
                'label'        => 'Filing Window Value',
                'name'         => 'ws_jx_statute_limit_value',
                'type'         => 'number',
                'instructions' => 'The numeric count for the deadline.',
                'min'          => 1,
                'step'         => 1,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'           => 'field_jx_statute_limit_unit',
                'label'         => 'Time Unit',
                'name'          => 'ws_jx_statute_limit_unit',
                'type'          => 'select',
                'choices'       => [
                    'days'   => 'Days',
                    'months' => 'Months',
                    'years'  => 'Years',
                ],
                'default_value' => 'days',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'           => 'field_jx_statute_trigger',
                'label'         => 'Deadline Trigger',
                'name'          => 'ws_jx_statute_trigger',
                'type'          => 'select',
                'instructions'  => 'When does the clock start ticking?',
                'choices'       => [
                    'adverse_action' => 'Date of Adverse Action',
                    'discovery'      => 'Date of Discovery',
                    'violation'      => 'Date of Violation',
                ],
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '40' ],
            ],

            [
                'key'          => 'field_jx_statute_tolling_notes',
                'label'        => 'Tolling & Extension Notes',
                'name'         => 'ws_jx_statute_tolling_notes',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe specific conditions that pause the statutory clock.',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Statutes of Limitations (Additions)
            // ────────────────────────────────────────────────────────────────

            [
                'key'           => 'field_jx_statute_exhaustion_required',
                'label'         => 'Administrative Exhaustion Required?',
                'name'          => 'ws_jx_statute_exhaustion_required',
                'type'          => 'true_false',
                'instructions'  => 'Must the whistleblower file with an agency before going to court?',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'           => 'field_jx_statute_exhaustion_details',
                'label'         => 'Exhaustion Procedure & Deadline',
                'name'          => 'ws_jx_statute_exhaustion_details',
                'type'          => 'textarea',
                'rows'          => 3,
                'instructions'  => 'Describe the agency filing deadline (e.g., 90 days to OSHA).',
                'required'      => 1,
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'field_jx_statute_exhaustion_required',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
                'wrapper'       => [ 'width' => '70' ],
            ],

            [
                'key'           => 'field_jx_statute_burden_of_proof',
                'label'         => 'Burden of Proof',
                'name'          => 'ws_statute_burden_of_proof',
                'type'          => 'select',
                'instructions'  => 'What standard must the whistleblower meet to succeed? "Contributing Factor" is the most employee-friendly; "But-For" is employer-friendly.',
                'choices'       => [
                    'contributing_factor' => 'Contributing Factor (employee-friendly)',
                    'motivating_factor'   => 'Motivating Factor',
                    'but_for'             => 'But-For Causation (employer-friendly)',
                    'preponderance'       => 'Preponderance of Evidence',
                    'varies'              => 'Varies by Claim Type',  //@todo - selection of 'varies' should reveal hiddten textarea for burden_of_proof_details
                ],
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            [
                'key'           => 'field_jx_statute_remedies',
                'label'         => 'Available Remedies',
                'name'          => 'ws_jx_statute_remedies',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_remedy_type',
                'field_type'    => 'checkbox',
                'instructions'  => 'What can a whistleblower recover under this specific law?',
                'add_term'      => 0, //@todo - taxonomy capabilities is handle in register-taxonomies.php by ws_get_taxonomy_caps()
                'save_terms'    => 0, //        consider removal of term attributes
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Relationships
            //
            // Links this statute to enforcement agencies and cross-references
            // the parent jurisdiction record.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_tab_statute_rel',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_statute_related_agencies',
                'label'         => 'Primary Oversight Agencies',
                'name'          => 'ws_jx_statute_related_agencies',
                'type'          => 'post_object',
                'post_type'     => [ 'ws-agency' ],
                'instructions'  => 'Select agencies that enforce or provide intake for this statute.',
                'multiple'      => 1,
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'id',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Authorship & Review
            //
            // Administrative metadata for data integrity and review cycles.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_tab_statute_review',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            [
                'key'          => 'field_date_created',
                'label'        => 'Date Created',
                'name'         => 'date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'           => 'field_create_author',
                'label'         => 'Created By',
                'name'          => 'create_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on first save. Read only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
                'wrapper'       => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_jx_statute_last_reviewed', //@todo - duplicate purpose of 'plain_reviewed'
                'label'        => 'Last Verified Date',
                'name'         => 'ws_jx_statute_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the statute record is meaningfully revised.',
            ],

            // ── Tab: Plain Language (Phase 9.2) ───────────────────────────

            [
                'key'   => 'tab_jx_statute_plain_language_tab',
                'label' => 'Plain Language',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_has_plain_english',
                'label'         => 'Has Plain Language Version',
                'name'          => 'has_plain_english',
                'type'          => 'true_false',
                'instructions'  => 'Enable when a plain-language version of this statute has been written below.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'               => 'field_plain_english_wysiwyg',
                'label'             => 'Plain Language Content',
                'name'              => 'plain_english_wysiwyg',
                'type'              => 'wysiwyg',
                'instructions'      => 'Plain-language explanation of this statute for non-experts.',
                'tabs'              => 'all',
                'toolbar'           => 'full',
                'media_upload'      => 0,
                'conditional_logic' => [ [ [
                    'field'    => 'field_has_plain_english',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],
            [
                'key'           => 'field_plain_english_reviewed',
                'label'         => 'Plain Language Reviewed',
                'name'          => 'plain_english_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human has reviewed and approved the plain-language content.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
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
                'key'          => 'field_plain_english_date',
                'label'        => 'Summarized Date',
                'name'         => 'plain_english_date',
                'type'         => 'text',
                'instructions' => 'Auto-stamped on first save after plain language content is created. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            // ── Tab: Reference Materials ───────────────────────────────────
            //
            // Links this statute to ws-reference records for researchers and
            // legal professionals. Not rendered on jurisdiction pages.
            // Only approved references display publicly via [ws_reference_page].

            [
                'key'   => 'field_jx_statute_ref_materials_tab',
                'label' => 'Reference Materials',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_statute_ref_materials',
                'label'        => 'Reference Materials',
                'name'         => 'ws_ref_materials',
                'type'         => 'relationship',
                'post_type'    => [ 'ws-reference' ],
                'filters'      => [ 'search' ],
                'instructions' => 'Attach external reference materials relevant to this record. Only approved references will display publicly. These are for researchers and legal professionals — not for primary users seeking guidance.',
                'min'          => 0,
                'max'          => 0,
                'return_format' => 'object',
            ],

        ],
    ] );

} // end ws_register_acf_jx_statutes


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().
