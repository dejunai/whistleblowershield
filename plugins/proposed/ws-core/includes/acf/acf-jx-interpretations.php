<?php
/**
 * acf-jx-interpretations.php
 *
 * Registers ACF Pro fields for the `jx-interpretation` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for individual federal court interpretations
 * of whistleblower statutes. Fields cover case identity, the holding,
 * process type classification, and authorship stamps.
 *
 * FIELD SUMMARY
 * -------------
 * Case Identity tab:
 *   ws_interp_court      Court (select, populated by ws_interp_load_court_choices)
 *   ws_interp_year       Decision Year (number)
 *   ws_interp_favorable  Favorable to Whistleblower? (true_false)
 *   ws_interp_case_name  Case Name (text)
 *   ws_interp_citation   Citation (text)
 *   ws_interp_url        Opinion URL (url)
 *
 * Summary tab:
 *   ws_interp_summary    Summary (textarea)
 *   ws_process_type      Process Type (taxonomy multi_select)
 *   attach_flag          Attach to jurisdiction page (true_false)
 *   order                Render order (number, conditional on attach_flag)
 *
 * Relationships tab:
 *   ws_statute_id        Parent Statute (post_object, jx-statute)
 *
 * Jurisdiction scope is provided by the ws_jurisdiction taxonomy — always
 * the US term for federal court interpretations; assigned via the taxonomy
 * UI or auto-assigned on Create Now flow.
 *
 * Authorship & Review tab:
 *   ws_interp_last_edited_author  Last edited by (user, readonly non-admins)
 *   ws_interp_date_created        Date created (text, readonly)
 *   ws_interp_last_edited         Last edited (text, readonly)
 *   ws_interp_last_reviewed       Last verified date (text)
 *
 * WORKFLOW
 * --------
 * Records are created via the "Add New Interpretation" meta box on the
 * jx-statute edit screen. The statute_id URL parameter pre-fills the
 * parent statute relationship field.
 *
 * PRE-POPULATION
 * --------------
 * ws_statute_id is pre-populated via acf/load_value (not acf/load_field).
 * acf/load_field cannot pre-select stored post IDs on post_object fields —
 * it only sets a default_value which ACF ignores when a relationship is
 * rendered. Instead, acf/load_value writes the statute_id from the URL
 * parameter into the field's live value when the post is an auto-draft,
 * which ACF then renders as the pre-selected item.
 *
 * @package    WhistleblowerShield
 * @since      2.4.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.4.0  Initial release.
 * 2.4.1  Bug #5 fix: replaced acf/load_field pre-population of
 *         ws_statute_id with acf/load_value checked against auto-draft
 *         status. acf/load_field default_value is silently ignored by
 *         ACF for post_object fields and does not pre-select anything.
 * 3.0.0  Architecture refactor (Phase 3.5):
 *        - Removed ws_jx_code field (retired; scope now via ws_jurisdiction taxonomy).
 *        - Added attach_flag toggle and order number field.
 *        - Updated Relationships tab comment; updated docblock.
 * 3.1.1  Pass 2 ACF audit fix:
 *        - Renamed tab key tab_ws_interp_plain_language → field_ws_interp_plain_language
 *          for convention consistency.
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields — now registered
 *          centrally in acf-stamp-fields.php (group_ws_stamp_fields, menu_order 90).
 *        - Removed Plain Language tab and all plain English fields — now registered
 *          centrally in acf-plain-english-fields.php (menu_order 85).
 *        - ws_interp_last_reviewed retained as a content-owned field.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jx_interpretations' );

function ws_register_acf_jx_interpretations() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_jx_interpretation_details',
        'title'                 => 'Interpretation Details',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-interpretation',
        ] ] ],

        'fields' => [

            // ────────────────────────────────────────────────────────────────
            // Tab: Case Identity
            //
            // Core bibliographic data — the court, case name, citation,
            // year, and a link to the full opinion.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_interp_tab_case_identity',
                'label' => 'Case Identity',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_interp_court',
                'label'         => 'Court',
                'name'          => 'ws_interp_court',
                'type'          => 'select',
                'instructions'  => 'Select the federal court that issued this decision.',
                'choices'       => [],  // populated by ws_interp_load_court_choices()
                'allow_null'    => 0,
                'required'      => 1,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '50' ],
            ],

            [
                'key'          => 'field_ws_interp_year',
                'label'        => 'Decision Year',
                'name'         => 'ws_interp_year',
                'type'         => 'number',
                'instructions' => 'Four-digit year the decision was issued.',
                'required'     => 1,
                'min'          => 1900,
                'max'          => 2099,
                'step'         => 1,
                'wrapper'      => [ 'width' => '25' ],
            ],

            [
                'key'           => 'field_ws_interp_favorable',
                'label'         => 'Favorable to Whistleblower?',
                'name'          => 'ws_interp_favorable',
                'type'          => 'true_false',
                'instructions'  => 'Does this ruling support the whistleblower\'s position?',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
                'wrapper'       => [ 'width' => '25' ],
            ],

            [
                'key'          => 'field_ws_interp_case_name',
                'label'        => 'Case Name',
                'name'         => 'ws_interp_case_name',
                'type'         => 'text',
                'instructions' => 'Full case name, e.g., "Bechtel v. Administrative Review Board".',
                'required'     => 1,
                'wrapper'      => [ 'width' => '70' ],
            ],

            [
                'key'          => 'field_ws_interp_citation',
                'label'        => 'Citation',
                'name'         => 'ws_interp_citation',
                'type'         => 'text',
                'instructions' => 'Standard legal citation, e.g., "710 F.3d 443 (1st Cir. 2013)".',
                'required'     => 1,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_ws_interp_url',
                'label'        => 'Opinion URL',
                'name'         => 'ws_interp_url',
                'type'         => 'url',
                'instructions' => 'Link to the full opinion (CourtListener, Google Scholar, PACER, etc.).',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Summary
            //
            // The substance of the ruling — what legal question the court
            // decided and how it relates to process type.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_interp_tab_summary',
                'label' => 'Summary',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_interp_summary',
                'label'        => 'Summary',
                'name'         => 'ws_interp_summary',
                'type'         => 'textarea',
                'instructions' => 'Summarize what the court decided in plain language. One paragraph. Focus on what this ruling means for whistleblowers — not legal procedure. Citation is captured above.',
                'required'     => 1,
                'rows'         => 5,
            ],

            [
                'key'           => 'field_ws_interp_process_type',
                'label'         => 'Process Type',
                'name'          => 'ws_process_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_process_type',
                'field_type'    => 'multi_select',
                'instructions'  => 'Which whistleblower process areas does this ruling address?',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_ws_interp_attach_flag',
                'label'         => 'Attach to Jurisdiction Page',
                'name'          => 'attach_flag',
                'type'          => 'true_false',
                'instructions'  => 'Enable to include this interpretation in the rendered section on the jurisdiction page. Disable to store for reference only.',
                'ui'            => 1,
                'ui_on_text'    => 'Attached',
                'ui_off_text'   => 'Unattached',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_ws_interp_order',
                'label'             => 'Display Order',
                'name'              => 'order',
                'type'              => 'number',
                'instructions'      => 'Set the order in which this interpretation appears on the jurisdiction page. Lower numbers appear first.',
                'min'               => 1,
                'step'              => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_ws_interp_attach_flag',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Relationships
            //
            // Links this interpretation back to its parent statute.
            // Jurisdiction scope is provided by ws_jurisdiction taxonomy.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_interp_tab_relationships',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_interp_statute_id',
                'label'         => 'Parent Statute',
                'name'          => 'ws_statute_id',
                'type'          => 'post_object',
                'post_type'     => [ 'jx-statute' ],
                'instructions'  => 'The federal statute this case interprets.',
                'required'      => 1,
                'multiple'      => 0,
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'id',
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            // Removed — registered centrally in acf-stamp-fields.php
            // (group_ws_stamp_fields, menu_order 90).

            // ── Last Verified Date ────────────────────────────────────────
            //
            // Content-owned field — not a stamp. Retained here in the
            // interpretation's own group.

            [
                'key'          => 'field_ws_interp_last_reviewed',
                'label'        => 'Last Verified Date',
                'name'         => 'ws_interp_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the record is meaningfully revised.',
            ],

            // ── Tab: Plain Language ───────────────────────────────────────
            // Removed — registered centrally in acf-plain-english-fields.php
            // (group_ws_plain_english_fields, menu_order 85).

            // ── Tab: Reference Materials ───────────────────────────────────
            //
            // Links this interpretation to ws-reference records for researchers
            // and legal professionals. Not rendered on jurisdiction pages.
            // Only approved references display publicly via [ws_reference_page].

            [
                'key'   => 'field_jx_interp_ref_materials_tab',
                'label' => 'Reference Materials',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_interp_ref_materials',
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

} // end ws_register_acf_jx_interpretations


// ── Court choices: filter to US federal courts only ───────────────────────────
//
// Reads $ws_court_matrix and returns only courts whose ws_jx_codes includes
// 'US' or is null (SCOTUS). Sorted by level ascending (SCOTUS first, then
// appellate, then district).

add_filter( 'acf/load_field/key=field_ws_interp_court', 'ws_interp_load_court_choices' );

function ws_interp_load_court_choices( $field ) {
    global $ws_court_matrix;
    if ( empty( $ws_court_matrix ) ) {
        return $field;
    }

    $filtered = array_filter( $ws_court_matrix, function( $court ) {
        return $court['ws_jx_codes'] === null || in_array( 'US', $court['ws_jx_codes'], true );
    } );

    uasort( $filtered, function( $a, $b ) {
        return $a['level'] <=> $b['level'];
    } );

    $choices = [];
    foreach ( $filtered as $key => $court ) {
        $choices[ $key ] = $court['short'];
    }

    $field['choices'] = $choices;
    return $field;
}


// ── Pre-populate ws_statute_id from ?statute_id= URL parameter ────────────────
//
// Bug #5 fix: acf/load_field cannot pre-select a post ID on post_object fields
// because ACF ignores the default_value property when rendering a relationship
// selector — it only shows a pre-selected item when the value is already stored
// in post meta.
//
// The correct hook is acf/load_value, which returns the live value ACF will
// render as the field's current selection. When the post is an auto-draft and
// statute_id is present in the URL, we return that ID as the field value so
// ACF renders the statute pre-selected. On saved posts, or when no URL
// parameter is present, we return $value unchanged.

add_filter( 'acf/load_value/key=field_ws_interp_statute_id', 'ws_interp_prefill_statute_id', 5, 3 );

function ws_interp_prefill_statute_id( $value, $post_id, $field ) {

    // Only pre-fill on brand-new auto-draft posts.
    if ( get_post_status( $post_id ) !== 'auto-draft' ) {
        return $value;
    }

    // Only act when the URL carries a valid statute_id.
    if ( ! isset( $_GET['statute_id'] ) ) {
        return $value;
    }

    $statute_id = absint( $_GET['statute_id'] );

    if ( $statute_id && get_post_type( $statute_id ) === 'jx-statute' ) {
        return $statute_id;
    }

    return $value;
}
