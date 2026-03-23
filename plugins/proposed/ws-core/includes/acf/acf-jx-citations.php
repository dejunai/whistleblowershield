<?php
/**
 * acf-jx-citations.php
 *
 * Registers ACF Pro fields for the `jx-citation` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Jurisdiction Citation records.
 * Citations are rendered on the jurisdiction page via the
 * [ws_jx_case_law] shortcode, which queries attached citations
 * for the current jurisdiction and assembles the ws-case-law
 * section including footnote anchors and Unicode return links.
 *
 * FIELD SUMMARY
 * -------------
 * Content tab:
 *   ws_jx_cite_type          Citation type (select)
 *   ws_disclosure_type       Disclosure Categories taxonomy (checkbox)
 *   ws_jx_citation_official_name  Official name — full citation label (text, required)
 *   ws_jx_citation_common_name    Common/informal name (text, optional)
 *   ws_jx_cite_url           Source URL (url)
 *   ws_jx_cite_is_pdf        PDF link toggle (true_false)
 *   attach_flag              Editorial curation flag (true_false). Marks this record as
 *                            one of the ~3–5 highlighted citations shown on the jurisdiction
 *                            summary page. NOT a visibility gate — unflagged citations are
 *                            accessible via taxonomy queries.
 *   order                    Render order among flagged items (number, conditional on attach_flag)
 *
 * Relationships tab:
 *   ws_jx_citation_statute_ids  Related statutes (post_object, jx-statute, multiple, optional)
 *
 * Jurisdiction scope is provided by the ws_jurisdiction taxonomy — the taxonomy
 * term is assigned via the WordPress taxonomy UI, not via an ACF field.
 *
 * Authorship & Review tab:
 *   ws_jx_cite_last_edited_author  Last edited by (user, readonly non-admins)
 *   ws_jx_cite_date_created        Date created (text, readonly)
 *   ws_jx_cite_last_reviewed       Last reviewed (text)
 *
 * STAMP FIELDS
 * ------------
 * Written server-side via acf/save_post at priority 20.
 *
 * Written once, never overwritten:
 *   ws_jx_cite_date_created      Local date (Y-m-d)
 *   ws_jx_cite_date_created_gmt  UTC date (Y-m-d)
 *   ws_jx_cite_create_author     User ID of creating user
 *
 * Written on every save:
 *   ws_jx_cite_last_edited       Local date (Y-m-d)
 *   ws_jx_cite_last_edited_gmt   UTC date (Y-m-d)
 *   ws_jx_cite_last_edited_author  User ID — visible, admin-editable only
 *
 * ZERO CITATIONS NOTICE
 * ---------------------
 * An admin_notices hook fires on jx-summary edit screens to warn
 * the summary author when no attached citations exist for the
 * parent jurisdiction. See ws_jx_cite_no_citations_notice() below.
 *
 * @package    WhistleblowerShield
 * @since      2.3.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.3.0  Initial release.
 * 3.0.0  Architecture refactor (Phase 3.3):
 * 3.6.0  Added Relationships tab with ws_jx_citation_statute_ids (post_object,
 *         jx-statute, multiple, optional). Added acf/load_value hook to pre-fill
 *         from ?statute_id= URL param when opened via the statute citation metabox.
 *        - Removed Relationships tab: ws_jx_code text field and ws_jurisdiction
 *          post_object field retired. Scope now provided by ws_jurisdiction taxonomy.
 *        - Renamed ws_jx_cite_attach → attach_flag (field key unchanged).
 *        - Renamed ws_jx_cite_position → order (field key unchanged).
 *        - Admin notice updated to use taxonomy-based citation lookup.
 * 3.1.1  Pass 2 ACF audit fixes:
 *        - Renamed tab key tab_ws_jx_cite_plain_language → field_ws_jx_cite_plain_language
 *          for convention consistency.
 *        - Removed scaffold comment blocks around field_ws_jx_disclosure_cat.
 * 3.1.2  Field keys corrected to match naming convention (field_ + meta name without ws_ prefix).
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields (last_edited_author,
 *          date_created, last_edited, create_author) — now registered centrally
 *          in acf-stamp-fields.php (group_stamp_metadata, menu_order 90).
 *        - Removed Plain Language tab and all plain English fields — now registered
 *          centrally in acf-plain-english-fields.php (menu_order 85).
 *        - ws_jx_cite_last_reviewed retained as a content-owned field.
 */

defined( 'ABSPATH' ) || exit;

// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_jx_citations' );

function ws_register_acf_jx_citations() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_jx_citation_metadata',
        'title'                 => 'Jurisdiction Citation',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-citation',
        ] ] ],

        'fields' => [

            // ── Tab: Content ──────────────────────────────────────────────

            [
                'key'   => 'field_jx_cite_content_tab',
                'label' => 'Content',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_jx_citation_type',
                'label'        => 'Citation Type',
                'name'         => 'ws_jx_citation_type',
                'type'         => 'select',
                'required'     => 1,
                'instructions' => 'Select the type of source this citation references.',
                'choices'      => [
                    'case_law'   => 'Case Law',
                    'statute'    => 'Statute',
                    'regulatory' => 'Regulatory',
                    'secondary'  => 'Secondary Source',
                ],
                'default_value' => 'case_law',
                'allow_null'    => 0,
                'ui'            => 1,
            ],
			[
				'key'           => 'field_jx_citation_disclosure_type',
				'label'         => 'Disclosure Category',
				'name'          => 'ws_jx_citation_disclosure_type',
				'type'          => 'taxonomy',
				'taxonomy'      => 'ws_disclosure_type',
				'field_type'    => 'checkbox', // Use checkboxes for multiple categories
				'add_term'      => 0,
				'save_terms'    => 1,
				'load_terms'    => 1,
				'return_format' => 'id',
				'multiple'      => 1,
			],
            [
                'key'          => 'field_jx_citation_official_name',
                'label'        => 'Official Name',
                'name'         => 'ws_jx_citation_official_name',
                'type'         => 'text',
                'required'     => 1,
                'instructions' => 'The full citation as it will appear in the footnote — e.g., Lawson v. PPG Architectural Finishes, Inc., 12 Cal. 5th 703 (2022).',
            ],

            [
                'key'          => 'field_jx_citation_common_name',
                'label'        => 'Common Name',
                'name'         => 'ws_jx_citation_common_name',
                'type'         => 'text',
                'instructions' => 'Shortened or colloquial name for this citation if commonly referenced — e.g., "Lawson". Leave blank if no common name applies.',
                'required'     => 0,
            ],
            [
                'key'          => 'field_jx_citation_url',
                'label'        => 'Source URL',
                'name'         => 'ws_jx_citation_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the source document, case, or statute.',
            ],
            [
                'key'           => 'field_jx_citation_is_pdf',
                'label'         => 'PDF Link',
                'name'          => 'ws_jx_citation_is_pdf',
                'type'          => 'true_false',
                'instructions'  => 'Enable if the source URL links directly to a PDF document. Appends "(PDF)" to the rendered link.',
                'ui'            => 1,
                'ui_on_text'    => 'PDF',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_jx_citation_attach_flag',
                'label'         => 'Attach to Jurisdiction Page',
                'name'          => 'ws_attach_flag',
                'type'          => 'true_false',
                'instructions'  => 'Enable to include this citation in the rendered case law section on the jurisdiction page. Disable to store for reference only.',
                'ui'            => 1,
                'ui_on_text'    => 'Attached',
                'ui_off_text'   => 'Unattached',
                'default_value' => 0,
            ],
            [
                'key'               => 'field_jx_citation_display_order',
                'label'             => 'Display Order',
                'name'              => 'ws_display_order',
                'type'              => 'number',
                'instructions'      => 'Set the order in which this citation appears in the footnote list. Lower numbers appear first.',
                'min'               => 1,
                'step'              => 1,
                'conditional_logic' => [ [ [
                    'field'    => 'field_jx_citation_attach_flag',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            // Removed — registered centrally in acf-stamp-fields.php
            // (group_stamp_metadata, menu_order 90).

            // ── Last Reviewed ─────────────────────────────────────────────
            //
            // Content-owned field — not a stamp. Retained here in the
            // citation's own group.

            [
                'key'          => 'field_jx_citation_last_reviewed',
                'label'        => 'Last Reviewed',
                'name'         => 'ws_jx_citation_last_reviewed',
                'type'         => 'text',
                'instructions' => 'Update this date each time the citation is meaningfully revised.',
            ],

            // ── Tab: Plain Language ───────────────────────────────────────
            // Removed — registered centrally in acf-plain-english-fields.php
            // (group_plain_english_metadata, menu_order 85).

            // ── Tab: Relationships ────────────────────────────────────────
            //
            // Links this citation back to the statute(s) it interprets or
            // supports. Optional — a citation may be jurisdiction-wide without
            // a statute parent. Multiple — one citation may be relevant to
            // more than one statute in the same jurisdiction.

            [
                'key'   => 'field_jx_citation_relationships_tab',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_citation_statute_ids',
                'label'         => 'Related Statutes',
                'name'          => 'ws_jx_citation_statute_ids',
                'type'          => 'post_object',
                'post_type'     => [ 'jx-statute' ],
                'instructions'  => 'Link this citation to the statute(s) it interprets or supports. Optional — leave blank for jurisdiction-wide citations not tied to a specific statute.',
                'required'      => 0,
                'multiple'      => 1,
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'id',
            ],

            // ── Tab: Reference Materials ───────────────────────────────────
            //
            // Links this citation to ws-reference records for researchers and
            // legal professionals. Not rendered on jurisdiction pages.
            // Only approved references display publicly via [ws_reference_page].

            [
                'key'   => 'field_jx_citation_ref_materials_tab',
                'label' => 'Reference Materials',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_citation_ref_materials',
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

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_citations


// Field locking, auto-fill today, and stamp fields are handled centrally
// in admin-hooks.php via ws_acf_lock_for_non_admins(), ws_acf_autofill_today(),
// and ws_acf_write_stamp_fields().


// ── Pre-populate ws_jx_citation_statute_ids from ?statute_id= URL param ──────
//
// Mirrors ws_interp_prefill_statute_id() in acf-jx-interpretations.php.
// When a new citation is opened from the statute's citation metabox,
// statute_id is passed as a URL param. acf/load_value returns it as the
// field's live value so ACF renders the statute pre-selected.
// Returns an array — ws_jx_citation_statute_ids is a multiple post_object field.

add_filter( 'acf/load_value/key=field_jx_citation_statute_ids', 'ws_citation_prefill_statute_ids', 5, 3 );

function ws_citation_prefill_statute_ids( $value, $post_id, $field ) {

    if ( get_post_status( $post_id ) !== 'auto-draft' ) {
        return $value;
    }

    if ( ! isset( $_GET['statute_id'] ) ) {
        return $value;
    }

    $statute_id = absint( $_GET['statute_id'] );

    if ( $statute_id && get_post_type( $statute_id ) === 'jx-statute' ) {
        return [ $statute_id ];
    }

    return $value;
}


// ── Admin notice: zero attached citations ─────────────────────────────────────
//
// Fires on jx-summary edit screens only. Reads the assigned ws_jurisdiction
// taxonomy term on the jx-summary post, then queries for attached jx-citation
// records scoped to that same term.
//
// Displays a warning notice if zero attached citations are found,
// prompting the summary author to act before publishing.

add_action( 'admin_notices', 'ws_jx_cite_no_citations_notice' );
function ws_jx_cite_no_citations_notice() {

    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'jx-summary' || $screen->base !== 'post' ) {
        return;
    }

    global $post;
    if ( ! $post ) return;

    // Get the ws_jurisdiction taxonomy term assigned to this jx-summary.
    $terms = wp_get_post_terms( $post->ID, WS_JURISDICTION_TERM_ID );
    if ( empty( $terms ) || is_wp_error( $terms ) ) return;

    $term_id = $terms[0]->term_id;

    // Query for attached citations scoped to this jurisdiction term.
    $attached = get_posts( [
        'post_type'      => 'jx-citation',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'   => 'ws_attach_flag',
                'value' => '1',
            ],
        ],
        'tax_query' => [ [
            'taxonomy' => WS_JURISDICTION_TERM_ID,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ] ],
    ] );

    if ( ! empty( $attached ) ) {
        return;
    }

    // Resolve a display name for this jurisdiction from the term or its post.
    $jx_post_id = ws_get_id_by_code( $terms[0]->slug );
    $jx_name    = $jx_post_id ? get_the_title( $jx_post_id ) : strtoupper( $terms[0]->slug );

    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo '<strong>WhistleblowerShield — Citation Warning:</strong> ';
    echo 'No attached citations found for <strong>' . esc_html( $jx_name ) . '</strong>. ';
    echo 'The case law section will not render on the jurisdiction page until at least one ';
    echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=jx-citation' ) ) . '">citation record</a> ';
    echo 'is published with <em>Attach to Jurisdiction Page</em> enabled and the ';
    echo esc_html( strtoupper( $terms[0]->slug ) ) . ' jurisdiction term assigned.';
    echo '</p></div>';
}
