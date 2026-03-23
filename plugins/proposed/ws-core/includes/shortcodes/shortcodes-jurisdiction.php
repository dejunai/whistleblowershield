<?php
/**
 * File: shortcodes-jurisdiction.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Registers shortcodes responsible for rendering Jurisdiction page
 * sections. These shortcodes are called automatically by the assembler
 * in render-jurisdiction.php — editors do not insert them manually.
 *
 * Each shortcode retrieves its dataset via the query layer, then passes
 * content to the section renderer for output.
 *
 *
 * SHORTCODES REGISTERED
 * ---------------------
 *
 *   [ws_jx_header]
 *       Renders the full jurisdiction header: name (H1), flag with
 *       attribution, and government offices box. Called first by
 *       the assembler for every jurisdiction page.
 *
 *   [ws_jx_summary]
 *       Renders the jurisdiction summary: WYSIWYG content from the
 *       linked jx-summary post, plus review badges, author, dates,
 *       and sources & citations.
 *
 *   [ws_jx_statutes]
 *       Renders statutes content from the linked jx-statute post.
 *
 *   [ws_jx_flag jx="CA"]
 *       Standalone flag shortcode. Renders flag + attribution only.
 *       Accepts optional jx parameter (jurisdiction code or slug).
 *       Falls back to the current global $post if omitted.
 *
 *   [ws_jx_review_status]
 *       Renders human-reviewed and legal-review status badges.
 *       Reads from the linked jx-summary post.
 *
 *   [ws_jx_case_law]
 *       Renders the ws-case-law section for the current jurisdiction.
 *       Queries published jx-citation records scoped by ws_jurisdiction
 *       taxonomy with attach_flag = 1, ordered by display_order ascending.
 *       Outputs citation body content, footnote anchors, and Unicode
 *       return links. Returns empty string if no attached citations exist.
 *
 *   [ws_jx_limitations]
 *       Renders the ws-limitations section for the current jurisdiction.
 *       Reads the limitations key from ws_get_jx_summary_data().
 *       Returns empty string if the field is empty.
 *
 * ARCHITECTURE
 * ------------
 *
 *   Query layer:     includes/queries/query-jurisdiction.php
 *   Render layer:    includes/render/render-section.php
 *   Assembler:       includes/render/render-jurisdiction.php
 *
 *
 * DATA SOURCES
 * ------------
 *
 * All data is retrieved via the query layer (query-jurisdiction.php).
 * Shortcodes never call get_field() or get_post_meta() directly.
 *
 * Key contracts (return array keys from the query layer):
 *
 *   ws_get_jurisdiction_data()   → jx_term_id, name, gov{}, record{}
 *   ws_get_jx_summary_data()     → content, sources, limitations, plain{},
 *                                   record{}
 *   ws_get_jx_statute_data()     → array of statute entries, each with
 *                                   post_id, content, attach_flag,
 *                                   display_order, is_fed, record{},
 *                                   plain{}, ref_materials[]
 *   ws_get_jx_citation_data()    → array of citation entries, each with
 *                                   post_id, content, attach_flag,
 *                                   display_order, is_fed, record{},
 *                                   ref_materials[]
 *   ws_get_legal_updates_data()  → array of update entries, each with
 *                                   post_id, update_date, update_type,
 *                                   law_name, source_url, summary,
 *                                   source_post_id, source_post_type
 *
 * See query-jurisdiction.php file header for full return-array contracts.
 *
 *
 * VERSION
 * -------
 * 2.1.0  Refactored shortcode layer
 * 2.1.3  Fixed summary to read from ACF fields (not post_content)
 *         Fixed [ws_jx_review_status] field names
 *         Fixed [ws_jx_flag] to use correct ACF field names
 *         Added full summary footer: author, dates, badges, sources
 * 2.3.0  Added [ws_jx_case_law] and [ws_jx_limitations] shortcodes.
 *         Case law section moved out of ws_jurisdiction_summary wysiwyg
 *         and into jx-citation CPT records rendered via [ws_jx_case_law].
 *         Limitations section moved out of wysiwyg into ws_jx_limitations
 *         ACF field on jx-summary, rendered via [ws_jx_limitations].
 * 2.3.1  Fixed all shortcodes to use query layer array access instead of
 *         post object property access. Fixed field name ws_last_reviewed →
 *         ws_jx_sum_last_reviewed. Fixed author lookup to use
 *         ws_jx_sum_create_author stamp (ws_jx_sum_author is not a
 *         registered ACF field). Rewrote [ws_jx_statutes] to handle the
 *         array-of-arrays return from ws_get_jx_statutes() including the
 *         state + federal merge with per-record is_fed labeling.
 * 3.1.0  Added "→ External References" button to [ws_jx_statutes] and
 *         [ws_jx_case_law] per-record rendering. Button only renders when
 *         ws_get_ref_materials() returns non-empty results AND the
 *         reference materials page resolves via ws_get_reference_page_url().
 *         @todo Add "→ External References" button to jx-interpretation
 *         shortcode when that shortcode is implemented.
 * 3.3.2  Updated all query layer return key references to match the
 *         simplified key names introduced in query-jurisdiction.php v3.3.2.
 *         record: author_name → created_by_name, editor_name → edited_by_name,
 *         date_created → created_date, last_edited → edited_date.
 *         plain: ws_plain_english_reviewed → is_reviewed,
 *         plain_english_reviewed_name → reviewed_by_name.
 *         gov: wb_auth_url → authority_url, wb_auth_label → authority_label.
 * 3.3.3  DATA SOURCES docblock: corrected source_method value list
 *         (ai_assist → ai_assisted; added bulk_import).
 * 3.6.0  [ws_jurisdiction_index] moved to shortcodes-general.php — it is a
 *         site-wide listing shortcode, not a jurisdiction-page shortcode.
 *         QUERY LAYER RETURN REFERENCE block: added reviewed_date to PLAIN
 *         SUB-ARRAY; removed duplicate copy from shortcodes-general.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── [ws_jx_header] ────────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_header', function( $atts ) {

    $atts    = shortcode_atts( [ 'jx' => '' ], $atts );
    $jx_data = ws_get_jurisdiction_data( $atts['jx'] ?: null );

    if ( ! $jx_data ) return '';

    $labels = [
        'state'     => 'State Leadership Offices',
        'territory' => 'Territory Leadership Offices',
        'district'  => 'District Leadership Offices',
        'federal'   => 'Federal Offices',
    ];
    $box_label = $labels[ $jx_data['class'] ] ?? 'Official Leadership Offices';

    $head_label  = $jx_data['gov']['executive_label']   ?? 'Office of the Governor';
    $legal_label = $jx_data['gov']['authority_label']     ?? 'Office of the Attorney General';

    $render_data = [
        'jx_name'   => $jx_data['name'],
        'flag_data' => [
            'url'        => $jx_data['flag']['url'],
            'source_url' => $jx_data['flag']['source_url'],
            'attr_str'   => $jx_data['flag']['attribution'], 
            'license'    => $jx_data['flag']['license'],
        ],
        'gov_data' => [
            'box_label' => $box_label,
            'links'     => [
                [ 'url' => $jx_data['gov']['portal_url'],     'label' => $jx_data['gov']['portal_label'] ?: 'Official Government Portal' ],
                [ 'url' => $jx_data['gov']['executive_url'],  'label' => $head_label ],
                [ 'url' => $jx_data['gov']['authority_url'],    'label' => $legal_label ],
            ],
        ],
    ];

    return ws_render_jx_header( $render_data );

} );


// ── [ws_jx_summary] ───────────────────────────────────────────────────────────
//
// All field reads delegated to ws_get_jx_summary_data() in the query layer.
// Phase 9.1 refactor: no direct get_field() / get_post_meta() calls here.

add_shortcode( 'ws_jx_summary', function() {

    global $post;
    if ( ! $post ) return '';

    $term_id = ws_get_jx_term_id( $post->ID );
    if ( ! $term_id ) return '';

    $data = ws_get_jx_summary_data( $term_id );
    if ( ! $data || empty( $data['content'] ) ) return '';

    $footer_html = ws_render_jx_summary_footer( [
        'created_by_name'        => $data['record']['created_by_name'],
        'edited_by_name'         => $data['record']['edited_by_name'],
        'created_date'           => $data['record']['created_date'],
        'edited_date'            => $data['record']['edited_date'],
        'is_reviewed'            => $data['plain']['is_reviewed'],
        'reviewed_by_name'       => $data['plain']['reviewed_by_name'],
        'reviewed_date'          => $data['plain']['reviewed_date'] ?? '',
        'sources'                => $data['sources'] ?: '',
    ] );

    // wp_kses_post() is correct here — do not replace with apply_filters('the_content', ...).
    // Summary content comes from an ACF WYSIWYG meta field (ws_jurisdiction_summary_wysiwyg),
    // not from post_content. The HTML is already fully formed by the ACF editor. Running
    // the_content filters would double-wrap paragraphs via wpautop, expand any shortcodes
    // embedded in the legal text, and trigger block rendering — none of which is appropriate
    // for a meta-stored WYSIWYG field. wp_kses_post() sanitizes without over-processing.
    // Statute content uses apply_filters('the_content', ...) because it reads post_content
    // directly, which requires block rendering and wpautop.
    return ws_render_jx_summary_section( wp_kses_post( $data['content'] ), $footer_html );

} );


// ── [ws_jx_statutes] ─────────────────────────────────────────────────────────
//
// Fetches attached jx-statute records via the query layer and renders them
// using the two-group pattern. Local (state/territory) records are rendered
// first in .ws-section--local; federal (US) records follow in
// .ws-section--federal. When all records share the same scope (US jurisdiction
// or no federal records), a single flat section is rendered.

add_shortcode( 'ws_jx_statutes', 'ws_shortcode_jx_statutes' );
function ws_shortcode_jx_statutes() {

    global $post;
    if ( ! $post ) return '';

    $term_id = ws_get_jx_term_id( $post->ID );
    if ( ! $term_id ) return '';

    $statutes = ws_get_jx_statute_data( $term_id );
    if ( empty( $statutes ) ) return '';

    // Check whether any federal records exist.
    $has_fed = false;
    foreach ( $statutes as $s ) {
        if ( $s['is_fed'] ) { $has_fed = true; break; }
    }

    // Helper: build HTML for one statute block including the optional
    // "→ External References" button. The button is omitted when no
    // approved ws-reference items are linked to this statute record.
    $build_statute_chunk = function( $statute ) {
        $html = apply_filters( 'the_content', $statute['content'] );

        $refs     = ws_get_ref_materials( $statute['id'] );
        $ref_url  = ! empty( $refs ) ? ws_get_reference_page_url( $statute['id'] ) : '';

        if ( $ref_url ) {
            $html .= '<div class="ws-ref-materials-link">'
                   . '<a href="' . esc_url( $ref_url ) . '" class="ws-ref-materials-btn">'
                   . '&rarr; External References'
                   . '</a>'
                   . '</div>';
        }

        return $html;
    };

    if ( ! $has_fed ) {
        // Single-group render: no federal append.
        $content = '';
        foreach ( $statutes as $statute ) {
            $content .= $build_statute_chunk( $statute );
        }
        return ws_render_section( 'Relevant Statutes', $content );
    }

    // Two-group render: split local vs federal.
    $local_html = '';
    $fed_html   = '';
    foreach ( $statutes as $statute ) {
        $chunk = $build_statute_chunk( $statute );
        if ( $statute['is_fed'] ) {
            $fed_html   .= $chunk;
        } else {
            $local_html .= $chunk;
        }
    }

    return ws_render_section_two_group( 'Relevant Statutes', $local_html, 'Federal Statutes', $fed_html );
}


// ── [ws_jx_flag] ─────────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_flag', function( $atts ) {

    $atts = shortcode_atts( [ 'jx' => '' ], $atts );

    if ( $atts['jx'] ) {
        $jx_data = ws_get_jurisdiction_data( $atts['jx'] );
    } else {
        global $post;
        $jx_data = $post ? ws_get_jurisdiction_data( $post->ID ) : null;
    }

    if ( ! $jx_data ) return '';

    // Map query layer 'flag' array to the keys ws_render_jx_flag() expects.
    return ws_render_jx_flag( [
        'url'        => $jx_data['flag']['url'],
        'source_url' => $jx_data['flag']['source_url'],
        'attr_str'   => $jx_data['flag']['attribution'],
        'license'    => $jx_data['flag']['license'],
    ] );

} );


// [ws_jx_review_status] removed in Phase 9.0.
// This shortcode's sole purpose was rendering human + legal review badges.
// Legal review badge system was removed entirely. Plain-language review
// status is now rendered inline within [ws_jx_summary] via ws_render_jx_summary_footer().


// ── [ws_jx_case_law] ─────────────────────────────────────────────────────────
//
// Queries published jx-citation records for the current jurisdiction
// where ws_jx_cite_attach is true, ordered by ws_jx_cite_position.
// Renders the full ws-case-law section: footnote anchors in the body
// and a footnote list with Unicode return links (&#x21a9;) at the foot.
//
// Returns empty string silently if no attached citations exist.
// A warning notice on the jx-summary edit screen covers that gap
// — see ws_jx_cite_no_citations_notice() in acf-jx-citations.php.
//
// Unicode return character: ↩ (U+21A9) replaces the PNG workaround.
// Controlled via .ws-footnote-return in ws-core-front.css.

add_shortcode( 'ws_jx_case_law', 'ws_shortcode_jx_case_law' );
function ws_shortcode_jx_case_law() {

    global $post;
    if ( ! $post ) return '';

    // Resolve ws_jurisdiction taxonomy term for the current jurisdiction post.
    $term_id = ws_get_jx_term_id( $post->ID );
    if ( ! $term_id ) return '';

    // Fetch attached citations via query layer (ordered by 'order' ASC).
    // Returns mixed local + federal records with is_fed flag on each.
    $citations = ws_get_jx_citation_data( $term_id );

    if ( empty( $citations ) ) return '';

    // Helper: build an array of footnote item HTML strings from a citation slice.
    $build_items = function( $slice, $fn_start, $id_prefix ) {
        $items    = [];
        $fn_index = $fn_start;
        foreach ( $slice as $citation ) {
            $label  = $citation['label'];
            $url    = $citation['cite_url'];
            $is_pdf = $citation['is_pdf'];

            $pdf_suffix = $is_pdf ? ' (PDF)' : '';
            $fn_id      = $id_prefix . '-fn-' . $fn_index;
            $fn_ref_id  = $id_prefix . '-fn-ref-' . $fn_index;

            if ( $url ) {
                $linked_label = '<a href="' . esc_url( $url ) . '" target="_blank">'
                              . esc_html( $label ) . esc_html( $pdf_suffix ) . '</a>';
            } else {
                $linked_label = esc_html( $label ) . esc_html( $pdf_suffix );
            }

            // Unicode return link: ↩ (U+21A9), styled via .ws-footnote-return.
            // @todo fn-ref-X anchors not yet emitted inline — return links are
            // currently dead until in-text superscript anchors are implemented.
            $return_link = '<a href="#' . esc_attr( $fn_ref_id ) . '" '
                         . 'class="ws-footnote-return" '
                         . 'title="Return to text">&#x21a9;</a>';

            // "→ External References" button — only when approved references exist.
            $ref_btn = '';
            $refs    = ws_get_ref_materials( $citation['id'] );
            if ( ! empty( $refs ) ) {
                $ref_url = ws_get_reference_page_url( $citation['id'] );
                if ( $ref_url ) {
                    $ref_btn = ' <a href="' . esc_url( $ref_url ) . '" '
                             . 'class="ws-ref-materials-btn ws-ref-materials-btn--inline">'
                             . '&rarr; External References'
                             . '</a>';
                }
            }

            $items[] = '<small id="' . esc_attr( $fn_id ) . '">'
                     . $return_link . ' '
                     . $fn_index . '. '
                     . $linked_label
                     . $ref_btn
                     . '</small>';

            $fn_index++;
        }
        return $items;
    };

    // Check whether any federal records exist.
    $local = array_values( array_filter( $citations, fn( $c ) => ! $c['is_fed'] ) );
    $fed   = array_values( array_filter( $citations, fn( $c ) =>   $c['is_fed'] ) );

    if ( empty( $fed ) ) {
        // Single-group: no federal append.
        return ws_render_jx_case_law( $build_items( $citations, 1, 'all' ) );
    }

    // Two-group: local and federal citations keep independent visible numbering
    // but use distinct DOM ID prefixes so anchor targets remain unique.
    $out  = ws_render_jx_case_law( $build_items( $local, 1, 'local' ), 'ws-section--local' );
    $out .= ws_render_jx_case_law( $build_items( $fed,   1, 'fed' ), 'ws-section--federal' );
    return $out;
}


// ── [ws_jx_limitations] ──────────────────────────────────────────────────────
//
// Reads ws_jx_limitations wysiwyg from the linked jx-summary post.
// Renders the ws-limitations section wrapper around that content.
// Returns empty string silently if the field is empty or no summary
// is linked to the current jurisdiction.

add_shortcode( 'ws_jx_limitations', 'ws_shortcode_jx_limitations' );
function ws_shortcode_jx_limitations() {

    global $post;
    if ( ! $post ) return '';

    $term_id = ws_get_jx_term_id( $post->ID );
    if ( ! $term_id ) return '';

    $data = ws_get_jx_summary_data( $term_id );
    if ( ! $data || empty( $data['limitations'] ) ) return '';

    // wp_kses_post() is correct here for the same reason as ws_shortcode_jx_summary() —
    // limitations content is an ACF WYSIWYG meta field, not post_content.
    return ws_render_jx_limitations( wp_kses_post( $data['limitations'] ) );
}


// ============================================================================
// QUERY LAYER RETURN REFERENCE
//
// Quick reference for every key returned by the query layer. All keys are
// plain PHP array keys -- the ws_ / ws_auto_ meta key prefixes are stripped
// at the query layer and must not reappear here.
//
// Use this as a map when building new shortcodes. All data reads must go
// through the query layer functions below -- no direct get_field() or
// get_post_meta() calls in shortcodes.
//
// Query layer: includes/queries/query-jurisdiction.php
// ============================================================================
//
// UNIVERSAL KEYS (every dataset function)
// ----------------------------------------
//   id           int     Post ID
//   title        string  Post title (get_the_title)
//   url          string  Permalink
//   status       string  WP post status (publish, draft, pending, etc.)
//
// RECORD SUB-ARRAY  $data['record']
// Stamp fields written by ws_acf_write_stamp_fields() on every save.
// ----------------------------------------
//   created_by        int     WP user ID of the record creator
//   created_by_name   string  Display name resolved from created_by
//   created_date      string  Creation date (Y-m-d local)
//   edited_by         int     WP user ID of the most recent editor
//   edited_by_name    string  Display name resolved from edited_by
//   edited_date       string  Date of most recent edit (Y-m-d local)
//
// PLAIN SUB-ARRAY  $data['plain']
// Present on: jx-statute, jx-citation, jx-interpretation,
//             jx-summary, ws-agency, ws-assist-org.
// ----------------------------------------
//   has_content        bool    True when a plain-language version exists
//   plain_content      string  Plain-language wysiwyg body (safe to echo)
//   written_by         int     WP user ID of the plain-language author
//   written_by_name    string  Display name resolved from written_by
//   written_date       string  Date plain-language version was written (Y-m-d)
//   is_reviewed        bool    True when plain-language review is complete
//   reviewed_by        int     WP user ID of the plain-language reviewer
//   reviewed_by_name   string  Display name resolved from reviewed_by
//   reviewed_date      string  Date plain-language review was completed (Y-m-d)
//
// VERIFY SUB-ARRAY  $data['verify']
// Present on all CPTs.
// ----------------------------------------
//   source_method    string  How the record was created:
//                            human_created | matrix_seed | feed_import |
//                            ai_assisted | bulk_import
//   source_name      string  Human-readable source label (e.g. publication name)
//   verified_by      int     WP user ID of the person who verified the record
//   verified_by_name string  Display name resolved from verified_by
//   verified_date    string  Date the record was verified (Y-m-d)
//   verify_status    string  Verification workflow status value
//   needs_review     bool    True when the record has been flagged for re-review
//
// ws_get_jurisdiction_data( $input )
// ----------------------------------------
//   id           int     Jurisdiction post ID
//   name         string  Jurisdiction display name
//   class        string  Type: state | territory | district | federal
//   code         string  Two-letter USPS code, uppercased (e.g. CA, TX, US)
//   jx_term_id   int     ws_jurisdiction taxonomy term ID
//   flag[
//     url          string  Flag image URL
//     attribution  string  Attribution credit string
//     source_url   string  URL of the flag source
//     license      string  License name or identifier
//   ]
//   gov[
//     portal_url        string  Official government portal URL
//     portal_label      string  Display label for the portal link
//     executive_url     string  Head-of-government office URL
//     executive_label   string  Display label for the executive link
//     authority_url     string  Whistleblower authority office URL
//     authority_label   string  Display label for the authority link
//     legislature_url   string  Legislature URL
//     legislature_label string  Display label for the legislature link
//   ]
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_summary_data( $jx_term_id )
// ----------------------------------------
//   content      string  Summary body wysiwyg (raw -- apply wp_kses_post before echo)
//   sources      string  Sources & citations textarea
//   limitations  string  Limitations wysiwyg
//   notes        string  Internal notes
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_statute_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content              string  Statute body (raw post_content)
//   order                int     Display sort order
//   is_fed               bool    True when appended from the US federal scope
//   official_name        string  Full official statute name
//   disclosure_type      mixed   ACF select value (disclosure category)
//   attach_flag          bool    True when attached to this jurisdiction page
//   limit_value          string  Statute of limitations value
//   limit_unit           string  Statute of limitations unit (days, years, etc.)
//   trigger              string  Event that starts the limitations clock
//   tolling_notes        string  Notes on tolling or clock suspension
//   exhaustion_required  bool    True when administrative exhaustion is required
//   exhaustion_details   string  Details on exhaustion requirements
//   burden_of_proof      string  Burden of proof standard
//   remedies             string  Available remedies description
//   related_agencies     mixed   ACF relationship field value (agency post objects)
//   last_reviewed        string  Date last reviewed (Y-m-d)
//   ref_materials        array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_citation_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content        string  Citation body (raw post_content)
//   is_fed         bool    True when appended from the US federal scope
//   type           string  Citation type identifier
//   disclosure_type mixed  ACF select value (disclosure category)
//   label          string  Display label for this citation
//   cite_url       string  URL of the cited source
//   is_pdf         bool    True when the cited source is a PDF
//   attach_flag    bool    True when attached to this jurisdiction page
//   order          int     Display sort order
//   last_reviewed  string  Date last reviewed (Y-m-d)
//   ref_materials  array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_interpretation_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content            string  Interpretation body (raw post_content)
//   order              int     Display sort order
//   is_fed             bool    True when appended from the US federal scope
//   case_name          string  Full case name
//   citation           string  Legal citation string (e.g. 123 F.3d 456)
//   opinion_url        string  URL to the court opinion
//   court              string  Court name
//   year               string  Year of decision
//   favorable          bool    True when the outcome favors the whistleblower
//   summary            string  Plain-text summary of the holding
//   parent_statute_id  int     Post ID of the related jx-statute record
//   process_type       mixed   ACF select value (disclosure process type)
//   attach_flag        bool    True when attached to this jurisdiction page
//   last_reviewed      string  Date last reviewed (Y-m-d)
//   ref_materials      array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_agency_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   code                   string  Internal agency code
//   name                   string  Full agency name
//   logo                   mixed   ACF image field value
//   disclosure_type        mixed   ACF select value (disclosure category)
//   process_type           mixed   ACF select value (disclosure process type)
//   website_url            string  Agency main website URL
//   reporting_url          string  Direct URL to the reporting/complaint portal
//   phone                  string  Agency contact phone number
//   confidentiality_notes  string  Notes on confidentiality handling
//   anonymous              bool    True when anonymous reports are accepted
//   reward                 bool    True when a reward program exists
//   languages              mixed   ACF select value (supported languages)
//   additional_languages   string  Free-text additional language notes
//   last_reviewed          string  Date last reviewed (Y-m-d)
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_assist_org_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   internal_id          string  Internal reference ID
//   type                 string  Organization type identifier
//   logo                 mixed   ACF image field value
//   serves_nationwide    bool    True when the org serves all jurisdictions
//   disclosure_type      mixed   ACF select value (disclosure category)
//   services             string  Description of services offered
//   employment_sectors   string  Employment sectors served
//   website_url          string  Organization main website URL
//   intake_url           string  Direct URL to the intake or contact form
//   phone                string  Organization phone number
//   email                string  Organization contact email
//   mailing_address      string  Mailing address
//   languages            mixed   ACF select value (supported languages)
//   additional_languages string  Free-text additional language notes
//   cost_model           string  Cost model identifier (free, sliding_scale, etc.)
//   income_limit         string  Income threshold for eligibility (if applicable)
//   income_limit_notes   string  Notes on income limit or eligibility criteria
//   anonymous            bool    True when anonymous inquiries are accepted
//   eligibility_notes    string  General eligibility notes
//   licensed_attorneys   bool    True when licensed attorneys are on staff
//   accreditation        string  Accreditation body or status
//   bar_states           string  States where the org is bar-accredited
//   verify_url           string  URL to an external accreditation verification page
//   last_reviewed        string  Date last reviewed (Y-m-d)
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_legal_updates_data( $jx_id, $count, $public_only )  -- returns array of items
//   $jx_id       int   Jurisdiction post ID to scope results. 0 = site-wide.
//   $count       int   Maximum records to return (default 5).
//   $public_only bool  When true, restricts results to WS_LEGAL_UPDATE_PUBLIC_TYPES.
//                      Shortcode attribute: public_only="false" for full changelog.
//                      Defaults true -- safe for all public-facing placements.
// ----------------------------------------
//   id                  int     Post ID
//   title               string  Post title
//   update_date         string  Date of the legal change (Y-m-d)
//   effective_date      string  Date the change takes effect (Y-m-d)
//   post_date           string  WordPress publish date (MySQL datetime)
//   update_type         string  Update category (legislation, ruling, guidance, etc.)
//   multi_jurisdiction  bool    True when the update affects multiple jurisdictions
//   law_name            string  Name of the law or ruling
//   source_url          string  URL of the primary source
//   summary             string  Summary wysiwyg (wp_kses_post applied -- safe to echo)
//   source_post_id      int     Post ID of the originating CPT record (if any)
//   source_post_type    string  Post type of source_post_id (if any)
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_ref_materials( $post_id )  -- returns array of items
// ----------------------------------------
//   title        string  Reference item title
//   url          string  URL of the external resource
//   description  string  Brief description of the resource
//   type         string  Resource type (statute, ruling, article, etc.)
//   source_name  string  Name of the publishing source
//
// ws_get_reference_page_data( $parent_post_id )
// ----------------------------------------
//   parent_title  string  Title of the parent jx-statute / citation / interpretation
//   parent_url    string  Permalink of the parent post
//   references    array   Array of ref_materials items (see ws_get_ref_materials above)
// ============================================================================
