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
 * @package    WhistleblowerShield
 * @author     Dejunai
 */

add_action( 'acf/init', 'ws_register_acf_jx_statutes' );

function ws_register_acf_jx_statutes() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_jx_statute_details',
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
                'key'   => 'field_jx_tab_statute_legal',
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
                'wrapper'      => [ 'width' => '70' ],
            ],

            [
                'key'          => 'field_jx_statute_jx_code',
                'label'        => 'Jurisdiction Code',
                'name'         => 'ws_jx_code',
                'type'         => 'text',
                'instructions' => 'USPS code for the parent jurisdiction (e.g., CA, TX, US). Used for relationship sync and cross-CPT queries.',
                'required'     => 1,
                'maxlength'    => 2,
                'placeholder'  => 'CA',
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_jx_statute_disclosure_type',
                'label'        => 'Disclosure Categories',
                'name'         => 'ws_jx_statute_disclosure_type',
                'type'         => 'taxonomy',
                'taxonomy'     => 'ws_disclosure_type',
                'field_type'   => 'multi_select',
                'instructions' => 'Classify the types of misconduct this law protects.',
                'add_term'     => 0,
                'save_terms'   => 1,
                'load_terms'   => 1,
                'return_format' => 'id',
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
                    'varies'              => 'Varies by Claim Type',
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
                'add_term'      => 1,
                'save_terms'    => 1,
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
                'key'           => 'field_jx_statute_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_jx_statute_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            [
                'key'          => 'field_jx_statute_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_statute_date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_jx_statute_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'ws_jx_statute_last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_jx_statute_last_reviewed',
                'label'        => 'Last Verified Date',
                'name'         => 'ws_jx_statute_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the statute record is meaningfully revised.',
            ],
        ],
    ] );

} // end ws_register_acf_jx_statutes


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().
