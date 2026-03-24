<?php
/**
 * acf-jx-interpretations.php
 *
 * Registers ACF Pro fields for the `jx-interpretation` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for individual court interpretations of
 * whistleblower statutes. Fields cover case identity, the holding,
 * process type classification, and authorship stamps. Covers both federal
 * court decisions (SCOTUS, circuits, districts) and state court decisions.
 *
 * FIELD SUMMARY
 * -------------
 * Case Identity tab:
 *   ws_jx_interp_court         Court (select, populated by ws_interp_load_court_choices)
 *   ws_jx_interp_court_name    Court Name (text, conditional on court == 'other')
 *   ws_jx_interp_year          Decision Year (number)
 *   ws_jx_interp_favorable     Favorable to Whistleblower? (true_false)
 *   ws_jx_interp_official_name Official name — full case name (text, required)
 *   ws_jx_interp_common_name   Common/informal name (text, optional)
 *   ws_jx_interp_case_citation Citation (text)
 *   ws_jx_interp_url           Opinion URL (url)
 *
 * Summary tab:
 *   ws_jx_interp_summary  Summary (textarea)
 *   ws_process_type        Process Type (taxonomy multi_select)
 *   ws_attach_flag         Editorial curation flag (true_false). Marks this record as
 *                          one of the ~3–5 highlighted interpretations shown on the
 *                          jurisdiction summary page. NOT a visibility gate — unflagged
 *                          interpretations are accessible via taxonomy queries.
 *   ws_display_order       Render order among flagged items (number, conditional on ws_attach_flag)
 *
 * Relationships tab:
 *   ws_jx_interp_statute_id   Parent Statute (post_object, jx-statute)
 *   ws_jx_interp_affected_jx  Affected Jurisdictions (taxonomy multi_select, ws_jurisdiction)
 *                              Auto-computed on save from the court's ws_jx_codes in
 *                              $ws_court_matrix / $ws_state_court_matrix. Empty = SCOTUS
 *                              (all jurisdictions). __manual__ = skip auto-population (other).
 *
 * Jurisdiction scope is provided by the ws_jurisdiction taxonomy — US term
 * for federal court interpretations; state term for state court decisions.
 * Assigned via the taxonomy UI or auto-assigned on Create Now flow.
 *
 * Authorship & Review:
 *   Stamp fields registered centrally in acf-stamp-fields.php (menu_order 90).
 *   ws_jx_interp_last_reviewed    Last verified date (text) — content-owned,
 *                                  retained in this group.
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
 * 3.1.2  Field keys corrected to match naming convention (field_ + meta name without ws_ prefix).
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields — now registered
 *          centrally in acf-stamp-fields.php (group_stamp_metadata, menu_order 90).
 *        - Removed Plain Language tab and all plain English fields — now registered
 *          centrally in acf-plain-english-fields.php (menu_order 85).
 *        - ws_interp_last_reviewed retained as a content-owned field.
 * 3.6.0  FIELD SUMMARY corrected: all ws_interp_* meta names updated to
 *         ws_jx_interp_* to match actual ACF field name values. ws_statute_id
 *         corrected to ws_jx_interp_statute_id. Added ws_jx_interp_affected_jx
 *         (taxonomy multi_select, save_terms=0) with auto-population hook that
 *         resolves the court's ws_jx_codes from the court matrix on every save.
 * 3.7.0  Added ws_jx_interp_court_name conditional text field (reveals when
 *         court = 'other'). ws_interp_load_court_choices() rewritten — context-
 *         aware: federal statute merges both $ws_court_matrix and
 *         $ws_state_court_matrix; state statute uses $ws_state_court_matrix only.
 *         ws_interp_auto_populate_affected_jx() updated to use ws_court_lookup()
 *         and skip auto-population when ws_jx_codes === '__manual__' (other).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jx_interpretations' );

function ws_register_acf_jx_interpretations() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_jx_interpretation_metadata',
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
                'key'   => 'field_interp_case_identity_tab',
                'label' => 'Case Identity',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_interp_court',
                'label'         => 'Court',
                'name'          => 'ws_jx_interp_court',
                'type'          => 'select',
                'instructions'  => 'Select the court that issued this decision. For state court decisions, select "State Level" and enter the court name in the field below.',
                'choices'       => [],  // populated by ws_interp_load_court_choices()
                'allow_null'    => 0,
                'required'      => 1,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '50' ],
            ],

            [
                'key'               => 'field_jx_interp_court_name',
                'label'             => 'Court Name',
                'name'              => 'ws_jx_interp_court_name',
                'type'              => 'text',
                'instructions'      => 'Enter the court name. Include jurisdiction and level where relevant, e.g., "Superior Court of California, Sacramento County". This field only appears when "Other" is selected above.',
                'required'          => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_interp_court',
                    'operator' => '==',
                    'value'    => 'other',
                ] ] ],
                'wrapper'           => [ 'width' => '50' ],
            ],

            [
                'key'          => 'field_jx_interp_year',
                'label'        => 'Decision Year',
                'name'         => 'ws_jx_interp_year',
                'type'         => 'number',
                'instructions' => 'Four-digit year the decision was issued.',
                'required'     => 1,
                'min'          => 1900,
                'max'          => 2099,
                'step'         => 1,
                'wrapper'      => [ 'width' => '25' ],
            ],

            [
                'key'           => 'field_jx_interp_favorable',
                'label'         => 'Favorable to Whistleblower?',
                'name'          => 'ws_jx_interp_favorable',
                'type'          => 'true_false',
                'instructions'  => 'Does this ruling support the whistleblower\'s position?',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
                'wrapper'       => [ 'width' => '25' ],
            ],

            [
                'key'          => 'field_jx_interp_official_name',
                'label'        => 'Official Name',
                'name'         => 'ws_jx_interp_official_name',
                'type'         => 'text',
                'instructions' => 'Full case name, e.g., "Bechtel v. Administrative Review Board".',
                'required'     => 1,
                'wrapper'      => [ 'width' => '70' ],
            ],

            [
                'key'          => 'field_jx_interp_common_name',
                'label'        => 'Common Name',
                'name'         => 'ws_jx_interp_common_name',
                'type'         => 'text',
                'instructions' => 'Shortened or colloquial name if this case is commonly cited by a shorter title — e.g., "Bechtel". Leave blank if no common name applies.',
                'required'     => 0,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_jx_interp_case_citation',
                'label'        => 'Citation',
                'name'         => 'ws_jx_interp_case_citation',
                'type'         => 'text',
                'instructions' => 'Standard legal citation, e.g., "710 F.3d 443 (1st Cir. 2013)".',
                'required'     => 1,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_jx_interp_url',
                'label'        => 'Opinion URL',
                'name'         => 'ws_jx_interp_url',
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
                'key'   => 'field_interp_summary_tab',
                'label' => 'Summary',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_jx_interp_summary',
                'label'        => 'Summary',
                'name'         => 'ws_jx_interp_summary',
                'type'         => 'textarea',
                'instructions' => 'Summarize what the court decided in plain language. One paragraph. Focus on what this ruling means for whistleblowers — not legal procedure. Citation is captured above.',
                'required'     => 1,
                'rows'         => 5,
            ],

            [
                'key'           => 'field_jx_interp_process_type',
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
                'key'           => 'field_jx_interp_attach_flag',
                'label'         => 'Attach to Jurisdiction Page',
                'name'          => 'ws_attach_flag',
                'type'          => 'true_false',
                'instructions'  => 'Enable to include this interpretation in the rendered section on the jurisdiction page. Disable to store for reference only.',
                'ui'            => 1,
                'ui_on_text'    => 'Attached',
                'ui_off_text'   => 'Unattached',
                'default_value' => 0,
            ],

            [
                'key'               => 'field_jx_interp_display_order',
                'label'             => 'Display Order',
                'name'              => 'ws_display_order',
                'type'              => 'number',
                'instructions'      => 'Set the order in which this interpretation appears on the jurisdiction page. Lower numbers appear first.',
                'min'               => 1,
                'step'              => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_interp_attach_flag',
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
                'key'   => 'field_interp_relationships_tab',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_interp_statute_id',
                'label'         => 'Parent Statute',
                'name'          => 'ws_jx_interp_statute_id',
                'type'          => 'post_object',
                'post_type'     => [ 'jx-statute' ],
                'instructions'  => 'The federal statute this case interprets.',
                'required'      => 1,
                'multiple'      => 0,
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'id',
            ],

            [
                'key'           => 'field_jx_interp_affected_jx',
                'label'         => 'Affected Jurisdictions',
                'name'          => 'ws_jx_interp_affected_jx',
                'type'          => 'taxonomy',
                'taxonomy'      => WS_JURISDICTION_TERM_ID,
                'field_type'    => 'multi_select',
                'instructions'  => 'Jurisdictions bound by this ruling. Auto-computed on save from the selected court\'s geographic scope (federal or state court matrix). Empty = SCOTUS (all jurisdictions). Override manually only when the court\'s scope does not reflect the ruling\'s actual reach.',
                'required'      => 0,
                'add_term'      => 0,
                'save_terms'    => 0,
                'load_terms'    => 0,
                'return_format' => 'id',
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            // Removed — registered centrally in acf-stamp-fields.php
            // (group_stamp_metadata, menu_order 90).

            // ── Last Verified Date ────────────────────────────────────────
            //
            // Content-owned field — not a stamp. Retained here in the
            // interpretation's own group.

            [
                'key'          => 'field_jx_interp_last_reviewed',
                'label'        => 'Last Verified Date',
                'name'         => 'ws_jx_interp_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the record is meaningfully revised.',
            ],

            // ── Tab: Plain Language ───────────────────────────────────────
            // Removed — registered centrally in acf-plain-english-fields.php
            // (group_plain_english_metadata, menu_order 85).

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
                'key'           => 'field_jx_interp_ref_materials',
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


// ── Court choices: context-aware select population ────────────────────────────
//
// Builds the court select list based on the parent statute's scope:
//
//   Federal statute (has 'us' ws_jurisdiction term):
//     All federal courts (SCOTUS + circuits + districts) merged with all
//     state courts. $ws_court_matrix and $ws_state_court_matrix are merged.
//
//   State statute (does not have 'us' term):
//     State courts only ($ws_state_court_matrix). Federal courts do not
//     interpret state statutes.
//
//   Unknown (no parent statute resolved yet):
//     Defaults to showing all courts (federal + state) as a safe fallback.
//
// Parent statute is resolved from saved meta (existing records) or the
// statute_id URL parameter (new records created from the statute metabox).
//
// The 'other' entry (level=99) sorts last and reveals the free-text
// ws_jx_interp_court_name field for courts not in either matrix.
//
// Sorted by level ascending so SCOTUS / state supreme courts appear before
// appellate courts, which appear before district / trial courts.

add_filter( 'acf/load_field/key=field_jx_interp_court', 'ws_interp_load_court_choices' );

function ws_interp_load_court_choices( $field ) {
    global $ws_court_matrix, $ws_state_court_matrix, $post;

    if ( empty( $ws_court_matrix ) ) {
        return $field;
    }

    // Resolve parent statute ID — saved meta first, URL param fallback.
    $statute_id = 0;
    if ( $post && get_post_type( $post->ID ) === 'jx-interpretation' && get_post_status( $post->ID ) !== 'auto-draft' ) {
        $statute_id = (int) get_post_meta( $post->ID, 'ws_jx_interp_statute_id', true );
    }
    if ( ! $statute_id && isset( $_GET['statute_id'] ) ) {
        $statute_id = absint( $_GET['statute_id'] );
    }

    // Determine statute scope. Unknown parent defaults to showing all courts.
    $is_federal = ! $statute_id || has_term( 'us', WS_JURISDICTION_TERM_ID, $statute_id );

    $candidates = $is_federal
        ? array_merge( $ws_court_matrix, $ws_state_court_matrix ?: [] )
        : ( $ws_state_court_matrix ?: [] );

    uasort( $candidates, function( $a, $b ) {
        return $a['level'] <=> $b['level'];
    } );

    $choices = [];
    foreach ( $candidates as $key => $court ) {
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

add_filter( 'acf/load_value/key=field_jx_interp_statute_id', 'ws_interp_prefill_statute_id', 5, 3 );

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


// ── Auto-populate ws_jx_interp_affected_jx from court matrix on every save ────
//
// Runs at priority 20 (after ACF saves its fields at 10). Reads the court key
// saved to ws_jx_interp_court, looks it up via ws_court_lookup() (checks both
// $ws_court_matrix and $ws_state_court_matrix), and resolves ws_jx_codes to
// ws_jurisdiction taxonomy term IDs.
//
// SCOTUS (ws_jx_codes = null): writes an empty array. The query/render layer
// treats empty affected_jx + SCOTUS court as "all jurisdictions" — avoids
// storing all 60+ term IDs in meta unnecessarily.
//
// Recomputes on every save so the value stays in sync if the court is changed.

add_action( 'acf/save_post', 'ws_interp_auto_populate_affected_jx', 20 );

function ws_interp_auto_populate_affected_jx( $post_id ) {

    if ( get_post_type( $post_id ) !== 'jx-interpretation' ) {
        return;
    }

    $court_key  = get_post_meta( $post_id, 'ws_jx_interp_court', true );
    $court_data = ws_court_lookup( $court_key );

    if ( ! $court_data ) {
        return;
    }

    $jx_codes = $court_data['ws_jx_codes'];

    // Other: geographic scope is unknown — leave affected_jx for manual entry.
    if ( $jx_codes === '__manual__' ) {
        return;
    }

    // SCOTUS: null = all jurisdictions. Store empty to signal bind-all.
    if ( $jx_codes === null ) {
        update_post_meta( $post_id, 'ws_jx_interp_affected_jx', [] );
        return;
    }

    // Resolve each USPS code to a ws_jurisdiction term ID.
    $term_ids = [];
    foreach ( $jx_codes as $code ) {
        $term = get_term_by( 'slug', strtolower( $code ), WS_JURISDICTION_TERM_ID );
        if ( $term && ! is_wp_error( $term ) ) {
            $term_ids[] = $term->term_id;
        }
    }

    update_post_meta( $post_id, 'ws_jx_interp_affected_jx', $term_ids );
}
