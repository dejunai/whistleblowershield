<?php
/**
 * acf-ag-procedures.php
 *
 * Registers ACF Pro fields for the `ws-ag-procedure` CPT.
 *
 * FIELD GROUPS
 * ------------
 * group_ag_procedure_metadata  →  Procedure Details
 *
 * PARENT AGENCY PRE-FILL
 * ----------------------
 * When a new procedure is created from the agency navigation box, the URL
 * carries ?agency_id={post_id}. The acf/load_value hook below pre-fills
 * ws_proc_agency_id on auto-draft posts, matching the pattern used by
 * ws_interp_prefill_statute_id() in acf-jx-interpretations.php.
 *
 * STAMP FIELDS
 * ------------
 * ws_needs_review, ws_auto_source_method, and all other stamp fields are
 * registered centrally in acf-stamp-fields.php (group_stamp_metadata,
 * menu_order 90) and attach to ws-ag-procedure via that file's location rules.
 * Do not duplicate stamp fields here.
 *
 * PLAIN ENGLISH
 * -------------
 * Procedures use ws_proc_walkthrough (registered in this file) as their
 * plain-English content. The central acf-plain-english-fields.php group is
 * NOT applied to this CPT — the walkthrough IS the plain-English layer.
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.9.0  Initial registration. Phase 1 of ws-ag-procedure feature build.
 * 3.10.0 ws_proc_type select field replaced with ws_procedure_type taxonomy
 *        field (radio UI, save_terms: 1, load_terms: 1). Field name changed
 *        from ws_proc_type to ws_procedure_type to match taxonomy slug.
 */

defined( 'ABSPATH' ) || exit;


// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_ag_procedures' );

function ws_register_acf_ag_procedures() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ag_procedure_metadata',
        'title'                 => 'Procedure Details',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-ag-procedure',
        ] ] ],

        'fields' => [

            // ── Tab: Procedure Identity ───────────────────────────────────

            [
                'key'   => 'field_proc_identity_tab',
                'label' => 'Procedure Identity',
                'type'  => 'tab',
            ],

            // ── Parent Agency ─────────────────────────────────────────────
            //
            // Links this procedure to its owning agency. Pre-filled from
            // the ?agency_id= URL parameter when created via the agency
            // navigation box — see ws_proc_prefill_agency_id() below.

            [
                'key'           => 'field_proc_agency_id',
                'label'         => 'Parent Agency',
                'name'          => 'ws_proc_agency_id',
                'type'          => 'post_object',
                'instructions'  => 'The agency this procedure belongs to.',
                'required'      => 1,
                'post_type'     => [ 'ws-agency' ],
                'return_format' => 'id',
                'multiple'      => 0,
                'allow_null'    => 0,
                'ui'            => 1,
            ],
            [
                'key'           => 'field_proc_type',
                'label'         => 'Procedure Type',
                'name'          => 'ws_procedure_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_procedure_type',
                'field_type'    => 'radio',
                'instructions'  => 'Disclosure = reporting wrongdoing. Retaliation = filing a complaint after adverse action. Both = single procedure covers both.',
                'required'      => 1,
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 0,
            ],

            // ── Jurisdiction(s) ───────────────────────────────────────────
            //
            // Defaults to the parent agency's jurisdictions. Override only
            // when this procedure covers a narrower geographic scope.
            // save_terms=1 writes term assignments directly; load_terms=1
            // reflects current taxonomy state in the admin UI.

            [
                'key'           => 'field_proc_jurisdiction',
                'label'         => 'Jurisdiction(s)',
                'name'          => WS_JURISDICTION_TAXONOMY,
                'type'          => 'taxonomy',
                'taxonomy'      => WS_JURISDICTION_TAXONOMY,
                'field_type'    => 'multi_select',
                'instructions'  => 'Jurisdictions this procedure applies to. Defaults to the parent agency jurisdictions — override only when a procedure covers a narrower scope.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 1,
            ],
            [
                'key'           => 'field_proc_disclosure_types',
                'label'         => 'Disclosure Types Covered',
                'name'          => 'ws_proc_disclosure_types',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_disclosure_type',
                'field_type'    => 'multi_select',
                'instructions'  => 'Which disclosure categories this specific procedure accepts. May be narrower than the parent agency\'s overall disclosure categories.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 1,
            ],

            // ── Related Statutes ──────────────────────────────────────────
            //
            // Authoritative list of jx-statute posts this procedure operates
            // under. The relationship picker is auto-scoped via
            // ws_proc_scope_statute_picker() (below) — it pre-filters to
            // statutes matching this procedure's jurisdiction and disclosure
            // types so the editor sees a relevant subset. Manual taxonomy
            // filter UI (jurisdiction + disclosure type) is also available
            // in the picker for edge cases.
            //
            // Statute links are validated on save by admin-procedure-watch.php:
            // a hard mismatch (zero disclosure-type intersection) demotes the
            // procedure to draft and sets ws_proc_stat_flagged. The Admin
            // Review tab's override field allows admins to publish despite a
            // known mismatch when the link is intentionally unconventional.

            [
                'key'          => 'field_proc_statute_ids',
                'label'        => 'Related Statutes',
                'name'         => 'ws_proc_statute_ids',
                'type'         => 'relationship',
                'instructions' => 'Statutes this procedure specifically operates under. The picker is pre-filtered by this procedure\'s jurisdiction and disclosure types. Use the taxonomy dropdowns to refine further if needed.',
                'post_type'    => [ 'jx-statute' ],
                // 'search' provides a text box; 'taxonomy' adds dropdown filters
                // for ws_jurisdiction and ws_disclosure_type so editors can
                // narrow the list before selecting. Auto-scoping (see hook below)
                // applies the procedure\'s own taxonomy scope automatically.
                'filters'      => [ 'search', 'taxonomy' ],
                'taxonomy'     => [ WS_JURISDICTION_TAXONOMY, 'ws_disclosure_type' ],
                'min'          => 0,
                'max'          => 0,
                'return_format'=> 'id',
                'allow_null'   => 1,
                'multiple'     => 1,
                'elements'     => [],
            ],

            // ── Tab: Filing Details ───────────────────────────────────────

            [
                'key'   => 'field_proc_filing_tab',
                'label' => 'Filing Details',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_proc_entry_point',
                'label'        => 'Entry Point',
                'name'         => 'ws_proc_entry_point',
                'type'         => 'select',
                'instructions' => 'How the whistleblower initiates this procedure.',
                'choices'      => [
                    'online'    => 'Online — Web Form or Portal',
                    'mail'      => 'Mail — Written Submission',
                    'phone'     => 'Phone — Hotline or Direct Call',
                    'in_person' => 'In Person — Regional Office',
                    'multi'     => 'Multiple — More Than One Option',
                ],
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'value',
            ],
            [
                'key'          => 'field_proc_intake_url',
                'label'        => 'Intake / Form URL',
                'name'         => 'ws_proc_intake_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the intake form or portal specific to this procedure. Overrides the parent agency\'s general reporting URL for this procedure.',
            ],
            [
                'key'          => 'field_proc_phone',
                'label'        => 'Direct Phone Number',
                'name'         => 'ws_proc_phone',
                'type'         => 'text',
                'instructions' => 'Specific hotline or office number for this procedure, if different from the parent agency\'s main hotline.',
            ],
            [
                'key'           => 'field_proc_identity_policy',
                'label'         => 'Identity Policy',
                'name'          => 'ws_proc_identity_policy',
                'type'          => 'select',
                'instructions'  => 'Anonymous = agency never learns your identity. Confidential = agency knows but will not disclose. Identified = your identity is required to proceed.',
                'required'      => 1,
                'choices'       => [
                    'anonymous'    => 'Anonymous — Identity Never Disclosed',
                    'confidential' => 'Confidential — Identity Protected, Known to Agency',
                    'identified'   => 'Identified — Identity Required',
                    'varies'       => 'Varies — Depends on Circumstances',
                ],
                'allow_null'    => 0,
                'ui'            => 1,
                'default_value' => 'confidential',
                'return_format' => 'value',
            ],
            [
                'key'           => 'field_proc_intake_only',
                'label'         => 'Intake Only — Does Not Investigate',
                'name'          => 'ws_proc_intake_only',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this agency only receives and refers — it does not investigate or adjudicate complaints filed under this procedure. Displayed prominently to prevent users from filing here expecting enforcement action.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_proc_deadline_days',
                'label'         => 'Filing Deadline (Days)',
                'name'          => 'ws_proc_deadline_days',
                'type'          => 'number',
                'instructions'  => 'Statutory filing deadline in calendar days. Enter 0 if no deadline applies or deadline is unknown.',
                'default_value' => 0,
                'min'           => 0,
                'step'          => 1,
            ],

            // ── Deadline Clock Start ──────────────────────────────────────
            //
            // Only relevant when a deadline is set. Conditional on
            // field_proc_deadline_days being greater than 0.

            [
                'key'          => 'field_proc_deadline_clock_start',
                'label'        => 'Deadline Clock Start',
                'name'         => 'ws_proc_deadline_clock_start',
                'type'         => 'select',
                'instructions' => 'The event that starts the filing deadline clock.',
                'choices'      => [
                    'adverse_action' => 'Date of Adverse Action',
                    'knowledge'      => 'Date Complainant Learned of Action',
                    'last_act'       => 'Date of Last Act in a Pattern',
                    'varies'         => 'Varies — See Plain English Walkthrough',
                ],
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'value',
                'conditional_logic' => [ [ [
                    'field'    => 'field_proc_deadline_days',
                    'operator' => '>',
                    'value'    => '0',
                ] ] ],
            ],
            [
                'key'           => 'field_proc_prerequisites',
                'label'         => 'Prerequisites Required Before Filing',
                'name'          => 'ws_proc_prerequisites',
                'type'          => 'true_false',
                'instructions'  => 'Enable if the filer must exhaust internal remedies or satisfy other conditions before using this procedure.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'          => 'field_proc_prerequisites_note',
                'label'        => 'Prerequisites — Details',
                'name'         => 'ws_proc_prerequisites_note',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Briefly describe what prerequisites must be satisfied before filing.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_proc_prerequisites',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ── Tab: Plain English ────────────────────────────────────────

            [
                'key'   => 'field_proc_plain_english_tab',
                'label' => 'Plain English',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_proc_walkthrough',
                'label'        => 'Step-by-Step Walkthrough',
                'name'         => 'ws_proc_walkthrough',
                'type'         => 'wysiwyg',
                'instructions' => 'Plain-language guidance for a whistleblower using this procedure. Cover: what to prepare, how to submit, what happens after filing, and realistic timeline expectations. This is the core "what do I do next?" answer.',
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 0,
            ],
            [
                'key'          => 'field_proc_exclusivity_note',
                'label'        => 'Mutual Exclusivity Note',
                'name'         => 'ws_proc_exclusivity_note',
                'type'         => 'textarea',
                'rows'         => 4,
                'instructions' => 'Describe any remedies or procedures the filer may waive or foreclose by using this procedure. Critical for user safety — leave blank only if there are no known exclusivity implications.',
            ],

            // ── Tab: Last Verified ────────────────────────────────────────

            [
                'key'   => 'field_proc_review_tab',
                'label' => 'Last Verified',
                'type'  => 'tab',
            ],
            [
                'key'            => 'field_proc_last_reviewed',
                'label'          => 'Last Verified Date',
                'name'           => 'ws_proc_last_reviewed',
                'type'           => 'date_picker',
                'instructions'   => 'Update each time this procedure record is meaningfully verified against the source agency.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Tab: Admin Review ─────────────────────────────────────────
            //
            // Visible to administrators only — hidden from all other roles
            // via ws_hide_source_fields_for_non_admins() registered in
            // admin-hooks.php.
            //
            // When admin-procedure-watch.php detects a disclosure-type
            // mismatch between a linked statute and this procedure, it:
            //   1. Sets ws_proc_stat_flagged = 1 in post meta.
            //   2. Forces post_status back to 'draft'.
            //   3. Records mismatch detail in ws_proc_stat_flag_detail.
            //
            // The admin reviews the notice on this screen and either:
            //   A. Fixes the underlying data (resolves mismatches) — the flag
            //      clears automatically on the next clean save.
            //   B. Checks ws_proc_stat_override and saves — the flag is cleared
            //      and the override is logged. The procedure can then be published
            //      normally. The override resets to 0 after each save.

            [
                'key'   => 'field_proc_admin_review_tab',
                'label' => 'Admin Review',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_proc_stat_override',
                'label'         => 'Statute Link Override',
                'name'          => 'ws_proc_stat_override',
                'type'          => 'true_false',
                'instructions'  => 'Enable to acknowledge statute link warnings and allow publishing despite mismatches. Use only when the link is intentionally unconventional. Resets automatically after each save. Overrides are logged for audit.',
                'ui'            => 1,
                'ui_on_text'    => 'Override',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_ag_procedures


// ── Auto-scope the statute relationship picker ────────────────────────────────
//
// Before the field_proc_statute_ids relationship picker renders its results,
// this hook narrows the query to statutes that share the procedure's own
// jurisdiction AND disclosure type scope. The editor sees only relevant
// statutes without needing to manually apply filters first.
//
// Falls back to the full list (plus the manual filter UI) when:
//   — The procedure is a new auto-draft (no taxonomy terms saved yet).
//   — The procedure has no jurisdiction or disclosure types assigned.
//
// Note: the hook fires via AJAX when the editor interacts with the picker,
// so $post_id is the procedure post ID with its current saved taxonomy state.

add_filter(
    'acf/fields/relationship/query/key=field_proc_statute_ids',
    'ws_proc_scope_statute_picker',
    10, 3
);

function ws_proc_scope_statute_picker( $args, $field, $post_id ) {

    // Skip auto-draft — taxonomy terms not saved yet, nothing to scope by.
    if ( ! $post_id || 'auto-draft' === get_post_status( $post_id ) ) {
        return $args;
    }

    $jx_terms   = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY, [ 'fields' => 'ids' ] );
    $disc_types = wp_get_object_terms( $post_id, 'ws_disclosure_type',    [ 'fields' => 'ids' ] );

    $tax_query  = [ 'relation' => 'AND' ];
    $has_filter = false;

    if ( ! empty( $jx_terms ) && ! is_wp_error( $jx_terms ) ) {
        $tax_query[] = [
            'taxonomy' => WS_JURISDICTION_TAXONOMY,
            'field'    => 'term_id',
            'terms'    => $jx_terms,
        ];
        $has_filter = true;
    }

    if ( ! empty( $disc_types ) && ! is_wp_error( $disc_types ) ) {
        $tax_query[] = [
            'taxonomy' => 'ws_disclosure_type',
            'field'    => 'term_id',
            'terms'    => $disc_types,
        ];
        $has_filter = true;
    }

    if ( $has_filter ) {
        $args['tax_query'] = $tax_query;
    }

    return $args;
}


// ── Pre-populate ws_proc_agency_id from ?agency_id= URL parameter ─────────────
//
// When "Add Procedure" is clicked from the agency navigation box, the URL
// carries ?agency_id={post_id}. On auto-draft posts, this hook returns the
// agency ID as the field value so ACF renders the parent agency pre-selected.
// Mirrors ws_interp_prefill_statute_id() in acf-jx-interpretations.php.

add_filter( 'acf/load_value/key=field_proc_agency_id', 'ws_proc_prefill_agency_id', 5, 3 );

function ws_proc_prefill_agency_id( $value, $post_id, $field ) {
    if ( get_post_status( $post_id ) !== 'auto-draft' ) {
        return $value;
    }
    if ( ! isset( $_GET['agency_id'] ) ) {
        return $value;
    }
    $agency_id = absint( $_GET['agency_id'] );
    if ( $agency_id && get_post_type( $agency_id ) === 'ws-agency' ) {
        return $agency_id;
    }
    return $value;
}
