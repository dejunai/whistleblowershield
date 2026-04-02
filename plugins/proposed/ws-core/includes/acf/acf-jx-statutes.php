<?php
defined( 'ABSPATH' ) || exit;

/**
 * acf-jx-statutes.php
 *
 * Registers ACF Pro fields for the `jx-statute` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for individual statutes, enabling granular
 * queries for deadlines, enforcement agencies, burden of proof standards,
 * and misconduct categories.
 *
 * FIELD SUMMARY
 * -------------
 * Legal Basis tab:
 *   ws_jx_statute_official_name      Official name (text, required)
 *   ws_jx_statute_citation           Official statute citation (text, optional)
 *   ws_jx_statute_common_name        Common/informal name (text, optional)
 *   ws_jx_statute_disclosure_type    Disclosure Categories taxonomy (multi_select)
 *   ws_jx_statute_protected_class    Protected Class taxonomy (multi_select)
 *   ws_jx_statute_protected_class_details Protected Class Detail (textarea, conditional on has-details term)
 *   ws_jx_statute_disclosure_targets Disclosure Targets taxonomy (multi_select)
 *   ws_jx_statute_disclosure_targets_details Disclosure Targets Detail (textarea, conditional on has-details term)
 *   ws_jx_statute_adverse_action_scope Free-text scope of covered adverse actions
 *   ws_attach_flag                   Editorial curation flag (true_false). Marks this
 *                                    record as one of the ~3–5 highlighted statutes shown
 *                                    on the jurisdiction summary page. NOT a visibility gate —
 *                                    unflagged statutes are accessible via taxonomy queries.
 *   ws_display_order                 Render order among flagged items (number, conditional on attach_flag)
 *
 * Jurisdiction scope is provided by the ws_jurisdiction taxonomy — the
 * taxonomy term is assigned via the WordPress taxonomy UI, not via an ACF field.
 *
 * Statute of Limitations tab:
 *   ws_jx_statute_sol_value          Filing Window Value (number)
 *   ws_jx_statute_sol_unit           Time Unit (select)
 *   ws_jx_statute_sol_trigger        Deadline Trigger (select)
 *   ws_jx_statute_sol_has_details    SOL has supplementary detail (true_false)
 *   ws_jx_statute_sol_details        SOL detail (textarea, conditional)
 *   ws_jx_statute_tolling_has_details Tolling provisions exist (true_false)
 *   ws_jx_statute_tolling_details    Tolling & Extension Details (textarea, conditional)
 *   ws_jx_statute_has_exhaustion     Exhaustion Required? (true_false)
 *   ws_jx_statute_exhaustion_details Exhaustion Procedure & Deadline (textarea, conditional)
 *
 * Enforcement tab:
 *   ws_jx_statute_process_type       Process Types taxonomy (checkbox)
 *   ws_jx_statute_adverse_action     Adverse Action Types taxonomy (checkbox)
 *   ws_jx_statute_adverse_action_details Adverse Action Detail (textarea, conditional on has-details term)
 *   ws_jx_statute_fee_shifting       Fee Shifting taxonomy (checkbox)
 *   ws_jx_statute_remedies           Available Remedies taxonomy (checkbox)
 *   ws_jx_statute_remedies_details   Remedies Detail (textarea, conditional on has-details term)
 *   ws_jx_statute_related_agencies   Primary Oversight Agencies (post_object)
 *
 * Burden of Proof tab:
 *   ws_jx_statute_employee_standard  Employee Standard taxonomy (checkbox)
 *   ws_jx_statute_employee_standard_details Employee Standard Detail (textarea, conditional on has-details term)
 *   ws_jx_statute_employer_defense   Employer Defense taxonomy (checkbox)
 *   ws_jx_statute_employer_defense_details Employer Defense Details (textarea, conditional on has-details term)
 *   ws_jx_statute_rebuttable_has_details Rebuttable presumption exists (true_false)
 *   ws_jx_statute_rebuttable_details Rebuttable Presumption Details (textarea, conditional)
 *   ws_jx_statute_bop_has_details    BOP has supplementary detail (true_false)
 *   ws_jx_statute_bop_details        BOP detail (textarea, conditional)
 *
 * Reward tab:
 *   ws_jx_statute_has_reward         Reward available (true_false)
 *   ws_jx_statute_reward_details     Reward Details (textarea, conditional)
 *
 * Links tab:
 *   ws_jx_statute_url                Statute URL (url)
 *   ws_jx_statute_url_is_pdf         PDF link toggle (true_false)
 *
 * @package    WhistleblowerShield
 * @since      2.0.0
 * @version 3.12.0
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
 * 3.1.1  Pass 2 ACF audit fixes:
 *        - Changed ws_jx_statute_remedies taxonomy from ws_remedy_type (deprecated)
 *          to ws_remedies.
 *        - Renamed tab key tab_jx_statute_plain_language_tab → field_jx_statute_plain_language_tab
 *          for convention consistency.
 *        - Removed resolved @todo scaffold comments.
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields — now registered
 *          centrally in acf-stamp-fields.php (group_stamp_metadata, menu_order 90).
 *        - Removed Plain Language tab and all plain English fields — now registered
 *          centrally in acf-plain-english-fields.php (group_plain_english_metadata, menu_order 85).
 * 3.4.1  Added defined( 'ABSPATH' ) || exit; guard at top of file.
 * 3.4.2  Field keys corrected: trigger → limit_trigger, order → display_order,
 *        statute_ref → jx_statute_ref.
 * 3.5.0  Full ACF overhaul to align with AI-assisted ingest schema:
 *        - Meta key renames: limit_* → sol_*, burden_of_proof → bop_standard,
 *          exhaustion_required → has_exhaustion (label retained: "Exhaustion Required?").
 *        - tolling_notes retired; replaced by tolling_has_details / tolling_details.
 *        - New tab: Enforcement — process_type, adverse_action, fee_shifting,
 *          remedies, related_agencies (moved from former Relationships tab).
 *        - New tab: Burden of Proof — bop_standard, employer_defense (new
 *          ws_employer_defense taxonomy stub), rebuttable_has_details /
 *          rebuttable_details, bop_has_details / bop_details.
 *        - New tab: Reward — has_reward / reward_details.
 *        - New tab: Links — statute_url, url_is_pdf.
 *        - New Legal Basis fields: protected_class, disclosure_targets,
 *          adverse_action_scope.
 *        - SOL supplementary detail pattern: sol_has_details / sol_details.
 *        - ws_employer_defense taxonomy stub registered in register-taxonomies.php.
 *        - Downstream consumers (query layer, matrix seeder, admin hooks) are
 *          deferred to a follow-up pass.
 * 3.12.0 ws_employee_standard taxonomy replaces ws_jx_statute_bop_standard select.
 *        has-details sentinel pattern added to six taxonomies: protected_class,
 *        disclosure_targets, adverse_action_types, remedies, employer_defense,
 *        employee_standard — each gains a _has_details toggle and conditional
 *        _details textarea. employer_defense_details made conditional.
 */

add_action( 'acf/init', 'ws_register_acf_jx_statutes' );

function ws_register_acf_jx_statutes() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_jx_statute_metadata',
        'title'                 => 'Statute Details',
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
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_legal_tab',
                'label' => 'Legal Basis',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_statute_official_name',
                'label'        => 'Official Name',
                'name'         => 'ws_jx_statute_official_name',
                'type'         => 'text',
                'instructions' => 'Use standard legal notation, e.g., "California Labor Code § 1102.5" or "5 U.S.C. § 2302".',
                'required'     => 1,
            ],

            [
                'key'          => 'field_jx_statute_citation',
                'label'        => 'Official Statute Citation',
                'name'         => 'ws_jx_statute_citation',
                'type'         => 'text',
                'instructions' => 'Short-form legal citation, e.g., "Cal. Lab. Code § 1102.5" or "42 U.S.C. § 5851".',
                'required'     => 0,
            ],

            [
                'key'          => 'field_jx_statute_common_name',
                'label'        => 'Common Name',
                'name'         => 'ws_jx_statute_common_name',
                'type'         => 'text',
                'instructions' => 'Informal or widely-used name for this statute, if one exists — e.g., "Sarbanes-Oxley" or "False Claims Act". Leave blank if no common name applies.',
                'required'     => 0,
            ],

            [
                'key'           => 'field_jx_statute_disclosure_type',
                'label'         => 'Disclosure Categories',
                'name'          => 'ws_jx_statute_disclosure_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_disclosure_type',
                'field_type'    => 'multi_select',
                'instructions'  => 'Classify the types of misconduct this law protects.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_jx_statute_protected_class',
                'label'         => 'Protected Class',
                'name'          => 'ws_jx_statute_protected_class',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_protected_class',
                'field_type'    => 'multi_select',
                'instructions'  => 'Select the employee types or worker classifications protected by this statute.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_protected_class_details',
                'label'        => 'Protected Class Details',
                'name'         => 'ws_jx_statute_protected_class_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe nuance in the covered worker classifications — e.g., eligibility thresholds, exclusions, or statutory language distinguishing coverage.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
            ],

            [
                'key'           => 'field_jx_statute_disclosure_targets',
                'label'         => 'Disclosure Targets',
                'name'          => 'ws_jx_statute_disclosure_targets',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_disclosure_targets',
                'field_type'    => 'multi_select',
                'instructions'  => 'Who must the disclosure be made to for protection to apply under this statute?',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_disclosure_targets_details',
                'label'        => 'Disclosure Targets Details',
                'name'         => 'ws_jx_statute_disclosure_targets_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe any conditions, ordering requirements, or statutory language that affects which reporting channels qualify for protection.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
            ],

            [
                'key'          => 'field_jx_statute_adverse_action_scope',
                'label'        => 'Adverse Action Scope',
                'name'         => 'ws_jx_statute_adverse_action_scope',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe the specific workplace actions this statute considers adverse, where the taxonomy terms do not fully capture the statutory scope or nuance.',
                'required'     => 0,
            ],

            [
                'key'           => 'field_jx_statute_attach_flag',
                'label'         => 'Attach to Jurisdiction Page',
                'name'          => 'ws_attach_flag',
                'type'          => 'true_false',
                'instructions'  => 'Enable to include this statute in the rendered statutes section on the jurisdiction page. Disable to store for reference only.',
                'ui'            => 1,
                'ui_on_text'    => 'Attached',
                'ui_off_text'   => 'Unattached',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_display_order',
                'label'             => 'Display Order',
                'name'              => 'ws_display_order',
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
            // Tab: Statute of Limitations
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_deadlines_tab',
                'label' => 'Statute of Limitations',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_statute_sol_value',
                'label'        => 'Filing Window Value',
                'name'         => 'ws_jx_statute_sol_value',
                'type'         => 'number',
                'instructions' => 'The numeric count for the deadline.',
                'min'          => 1,
                'step'         => 1,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'           => 'field_jx_statute_sol_unit',
                'label'         => 'Time Unit',
                'name'          => 'ws_jx_statute_sol_unit',
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
                'key'           => 'field_jx_statute_sol_trigger',
                'label'         => 'Deadline Trigger',
                'name'          => 'ws_jx_statute_sol_trigger',
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
                'key'           => 'field_jx_statute_sol_has_details',
                'label'         => 'SOL Has Supplementary Detail',
                'name'          => 'ws_jx_statute_sol_has_details',
                'type'          => 'true_false',
                'instructions'  => 'Enable to add a detail note — e.g., the deadline is derived from a general civil procedure statute rather than stated in this law.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_sol_details',
                'label'             => 'SOL Details',
                'name'              => 'ws_jx_statute_sol_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe anything a reviewer should know about this deadline — derivation source, dual-period situations, or other nuance.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_sol_has_details',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            [
                'key'           => 'field_jx_statute_tolling_has_details',
                'label'         => 'Tolling Provisions Exist',
                'name'          => 'ws_jx_statute_tolling_has_details',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this statute has identified tolling or extension conditions.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_tolling_details',
                'label'             => 'Tolling & Extension Details',
                'name'              => 'ws_jx_statute_tolling_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe specific conditions that pause or extend the statutory clock.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_tolling_has_details',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            [
                'key'           => 'field_jx_statute_has_exhaustion',
                'label'         => 'Exhaustion Required?',
                'name'          => 'ws_jx_statute_has_exhaustion',
                'type'          => 'true_false',
                'instructions'  => 'Must the whistleblower file with an agency before going to court?',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'               => 'field_jx_statute_exhaustion_details',
                'label'             => 'Exhaustion Procedure & Deadline',
                'name'              => 'ws_jx_statute_exhaustion_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe the agency filing deadline (e.g., 90 days to OSHA).',
                'required'          => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_has_exhaustion',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
                'wrapper'           => [ 'width' => '70' ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Enforcement
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_enforcement_tab',
                'label' => 'Enforcement',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_statute_process_type',
                'label'         => 'Process Types',
                'name'          => 'ws_jx_statute_process_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_process_type',
                'field_type'    => 'checkbox',
                'instructions'  => 'Which whistleblower process areas does this statute address?',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_jx_statute_adverse_action',
                'label'         => 'Adverse Action Types',
                'name'          => 'ws_jx_statute_adverse_action',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_adverse_action_types',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select the adverse actions covered by this statute.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_adverse_action_details',
                'label'        => 'Adverse Action Details',
                'name'         => 'ws_jx_statute_adverse_action_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe any statutory language, broad catch-all provisions, or nuance that the taxonomy terms do not fully capture.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
            ],

            [
                'key'           => 'field_jx_statute_fee_shifting',
                'label'         => 'Fee Shifting',
                'name'          => 'ws_jx_statute_fee_shifting',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_fee_shifting',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select the fee shifting rule that applies to this statute.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_jx_statute_remedies',
                'label'         => 'Available Remedies',
                'name'          => 'ws_jx_statute_remedies',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_remedies',
                'field_type'    => 'checkbox',
                'instructions'  => 'What can a whistleblower recover under this specific law?',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_remedies_details',
                'label'        => 'Remedies Details',
                'name'         => 'ws_jx_statute_remedies_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe caps, eligibility conditions, aggregation rules, or other nuance affecting available remedies.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
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
            // Tab: Burden of Proof
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_bop_tab',
                'label' => 'Burden of Proof',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_statute_employee_standard',
                'label'         => 'Employee Standard',
                'name'          => 'ws_jx_statute_employee_standard',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_employee_standard',
                'field_type'    => 'checkbox',
                'instructions'  => 'What standard must the whistleblower meet to succeed? Tag all that explicitly apply. Omit if no standard is named in the statute — do not infer.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_employee_standard_details',
                'label'        => 'Employee Standard Details',
                'name'         => 'ws_jx_statute_employee_standard_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe the split standard, burden shift, or other nuance — e.g., different standards applying to different claim types under this statute.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
            ],

            [
                'key'           => 'field_jx_statute_employer_defense',
                'label'         => 'Employer Defense',
                'name'          => 'ws_jx_statute_employer_defense',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_employer_defense',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select the defense standard(s) available to the employer under this statute.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_jx_statute_employer_defense_details',
                'label'        => 'Employer Defense Details',
                'name'         => 'ws_jx_statute_employer_defense_details',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe the specific defense standard — e.g., the evidentiary burden required, statutory language, or any procedural conditions attached to the defense.',
                // conditional_logic set dynamically — see ws_jx_statute_details_conditional()
            ],

            [
                'key'           => 'field_jx_statute_rebuttable_has_details',
                'label'         => 'Rebuttable Presumption Exists',
                'name'          => 'ws_jx_statute_rebuttable_has_details',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this statute creates a rebuttable presumption in favour of the whistleblower.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_rebuttable_details',
                'label'             => 'Rebuttable Presumption Details',
                'name'              => 'ws_jx_statute_rebuttable_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe the presumption and what the employer must do to rebut it.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_rebuttable_has_details',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            [
                'key'           => 'field_jx_statute_bop_has_details',
                'label'         => 'BOP Has Supplementary Detail',
                'name'          => 'ws_jx_statute_bop_has_details',
                'type'          => 'true_false',
                'instructions'  => 'Enable to add a note about a non-standard or otherwise notable burden of proof situation for this statute.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_bop_details',
                'label'             => 'BOP Details',
                'name'              => 'ws_jx_statute_bop_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe the notable burden of proof situation — e.g., a burden shift, a split standard, or statutory language that modifies the general standard.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_bop_has_details',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Reward
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_reward_tab',
                'label' => 'Reward',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_statute_has_reward',
                'label'         => 'Reward Available',
                'name'          => 'ws_jx_statute_has_reward',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this statute provides a monetary reward or bounty to the whistleblower (distinct from compensatory remedies).',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_statute_reward_details',
                'label'             => 'Reward Details',
                'name'              => 'ws_jx_statute_reward_details',
                'type'              => 'textarea',
                'rows'              => 3,
                'instructions'      => 'Describe the reward structure — e.g., percentage of collected sanctions, eligibility conditions, administering agency.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_statute_has_reward',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Links
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_statute_links_tab',
                'label' => 'Links',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_statute_url',
                'label'        => 'Statute URL',
                'name'         => 'ws_jx_statute_url',
                'type'         => 'url',
                'instructions' => 'Link to the official legislature source or best available approved source for this statute.',
            ],

            [
                'key'           => 'field_jx_statute_url_is_pdf',
                'label'         => 'PDF Link',
                'name'          => 'ws_jx_statute_url_is_pdf',
                'type'          => 'true_false',
                'instructions'  => 'Enable if the statute URL links directly to a PDF document.',
                'ui'            => 1,
                'ui_on_text'    => 'PDF',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            // ── Last Verified Date ────────────────────────────────────────
            //
            // Content-owned field — not a stamp. Editable by editors to
            // signal when the statute record was last meaningfully reviewed
            // for accuracy. Rendered inside the Links tab.

            [
                'key'          => 'field_jx_statute_last_reviewed',
                'label'        => 'Last Verified Date',
                'name'         => 'ws_jx_statute_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the statute record is meaningfully revised.',
            ],

            // Authorship & Review tab removed — registered centrally in
            // acf-stamp-fields.php (group_stamp_metadata, menu_order 90).

            // Plain Language tab removed — registered centrally in
            // acf-plain-english-fields.php (group_plain_english_metadata, menu_order 85).

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
                'key'           => 'field_jx_statute_ref_materials',
                'label'         => 'Reference Materials',
                'name'          => 'ws_ref_materials',
                'type'          => 'relationship',
                'post_type'     => [ 'ws-reference' ],
                'filters'       => [ 'search' ],
                'instructions'  => 'Attach external reference materials relevant to this record. Only approved references will display publicly. These are for researchers and legal professionals — not for primary users seeking guidance.',
                'min'           => 0,
                'max'           => 0,
                'return_format' => 'object',
            ],

        ],
    ] );

} // end ws_register_acf_jx_statutes


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


// ── Conditional logic: has-details sentinel ───────────────────────────────────
//
// ACF conditional logic cannot reference taxonomy term IDs at registration time
// because term IDs are assigned at seed runtime, not at code registration time.
//
// This filter runs on each field load and dynamically injects conditional_logic
// into each _details textarea when the 'has-details' term is selected in its
// companion taxonomy multi-select field.
//
// Pattern: when the editor selects the 'has-details' term in a taxonomy field,
// the companion _details textarea becomes visible. No separate toggle needed.

add_filter( 'acf/load_field', 'ws_jx_statute_details_conditional' );

function ws_jx_statute_details_conditional( $field ) {

    // Map: details field key => [ taxonomy slug, trigger field key ]
    static $map = [
        'field_jx_statute_protected_class_details'    => [ 'ws_protected_class',     'field_jx_statute_protected_class' ],
        'field_jx_statute_disclosure_targets_details' => [ 'ws_disclosure_targets',   'field_jx_statute_disclosure_targets' ],
        'field_jx_statute_adverse_action_details'     => [ 'ws_adverse_action_types', 'field_jx_statute_adverse_action' ],
        'field_jx_statute_remedies_details'           => [ 'ws_remedies',             'field_jx_statute_remedies' ],
        'field_jx_statute_employee_standard_details'  => [ 'ws_employee_standard',    'field_jx_statute_employee_standard' ],
        'field_jx_statute_employer_defense_details'   => [ 'ws_employer_defense',     'field_jx_statute_employer_defense' ],
    ];

    if ( ! isset( $map[ $field['key'] ] ) ) {
        return $field;
    }

    [ $taxonomy, $trigger_key ] = $map[ $field['key'] ];

    $term = get_term_by( 'slug', 'has-details', $taxonomy );
    if ( ! $term || is_wp_error( $term ) ) {
        return $field;
    }

    $field['conditional_logic'] = [ [ [
        'field'    => $trigger_key,
        'operator' => '==',
        'value'    => (string) $term->term_id,
    ] ] ];

    return $field;
}
