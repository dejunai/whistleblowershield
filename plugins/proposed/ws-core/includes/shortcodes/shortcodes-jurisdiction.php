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
 *       Renders statutes content from the linked jx-statutes post.
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
 *       Queries published jx-citation records where ws_jx_code matches
 *       the current jurisdiction and ws_jx_cite_attach is true.
 *       Records are ordered by ws_jx_cite_position (ascending).
 *       Outputs citation body content, footnote anchors, and Unicode
 *       return links. Returns empty string if no attached citations exist.
 *
 *   [ws_jx_limitations]
 *       Renders the ws-limitations section for the current jurisdiction.
 *       Reads ws_jx_limitations wysiwyg from the linked jx-summary post.
 *       Returns empty string if the field is empty.
 *
 *   [ws_jurisdiction_index]
 *       Renders the full filterable jurisdiction index with type
 *       filter tabs and alphabetical grid.
 *
 *
 * ARCHITECTURE
 * ------------
 *
 *   Query layer:     includes/queries/query-jurisdiction.php
 *   Render layer:    includes/render/section-renderer.php
 *   Assembler:       includes/render/render-jurisdiction.php
 *
 *
 * DATA SOURCES
 * ------------
 *
 * Summary content comes from ACF fields on the jx-summary post:
 *
 *   ws_jurisdiction_summary      — WYSIWYG content (main body)
 *   ws_jx_summary_sources        — sources & citations textarea
 *   ws_jx_sum_date_created       — date created
 *   ws_jx_sum_last_reviewed      — last reviewed date (ws_last_reviewed)
 *   ws_jx_sum_author             — author user field
 *   ws_jx_sum_human_reviewed     — true/false toggle
 *   ws_jx_sum_legal_review_completed — true/false toggle
 *   ws_jx_sum_legal_reviewer     — conditional text field
 *
 * Case law content comes from published jx-citation records:
 *
 *   ws_jx_code               — jurisdiction code (query key)
 *   ws_jx_cite_attach        — attach toggle (true = render)
 *   ws_jx_cite_position      — display order (numeric, ascending)
 *   ws_jx_cite_type          — citation type
 *   ws_jx_cite_label         — display label
 *   ws_jx_cite_url           — source URL
 *   ws_jx_cite_is_pdf        — PDF toggle (appends "(PDF)" to link)
 *
 * Limitations content comes from the jx-summary ACF field:
 *
 *   ws_jx_limitations        — wysiwyg content
 *
 * Statutes and resources content comes from the post_content field of
 * their respective addendum CPTs, processed through the_content filters.
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
    $box_label = $labels[ $jx_data['type'] ] ?? 'Official Offices';

    // Map jurisdiction matrix keys to display labels.
    $gov_label_map = [
        'governor' => 'Office of the Governor',
        'mayor'    => 'Office of the Mayor',
    ];
    $legal_label_map = [
        'attorney'  => 'Office of the Attorney General',
        'inspector' => 'D.C. Office of the Inspector General',
        'secretary' => 'Office of the Secretary of Justice',
        'special'   => 'U.S. Office of Special Counsel',
    ];

    $head_label  = $gov_label_map[ $jx_data['gov']['head_gov_label'] ]   ?? 'Office of the Governor';
    $legal_label = $legal_label_map[ $jx_data['gov']['legal_auth_label'] ] ?? 'Office of the Attorney General';

    $render_data = [
        'jx_name'   => $jx_data['name'],
        'flag_data' => [
            'url'        => $jx_data['flag']['url'],
            'source_url' => $jx_data['flag']['source_url'],
            'attr_str'   => $jx_data['flag']['attribution'],     // fixed: was ['attr_str']
            'license'    => $jx_data['flag']['license'],
        ],
        'gov_data' => [
            'box_label' => $box_label,
            'links'     => [
                [ 'url' => $jx_data['gov']['portal_url'],     'label' => $jx_data['gov']['portal_label'] ?: 'Official Government Portal' ],
                [ 'url' => $jx_data['gov']['head_gov_url'],   'label' => $head_label ],
                [ 'url' => $jx_data['gov']['legal_auth_url'], 'label' => $legal_label ],
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
        'author_name'    => $data['author_name'],
        'fmt_created'    => $data['fmt_created'],
        'fmt_reviewed'   => $data['fmt_reviewed'],
        'plain_reviewed' => $data['plain_reviewed'],
        'sources'        => $data['sources'] ?: '',
    ] );

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

    if ( ! $has_fed ) {
        // Single-group render: no federal append.
        $content = '';
        foreach ( $statutes as $statute ) {
            $content .= apply_filters( 'the_content', $statute['content'] );
        }
        return ws_render_section( 'Relevant Statutes', $content );
    }

    // Two-group render: split local vs federal.
    $local_html = '';
    $fed_html   = '';
    foreach ( $statutes as $statute ) {
        $chunk = apply_filters( 'the_content', $statute['content'] );
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
    $build_items = function( $slice, $fn_start ) {
        $items    = [];
        $fn_index = $fn_start;
        foreach ( $slice as $citation ) {
            $label  = $citation['label'];
            $url    = $citation['cite_url'];
            $is_pdf = $citation['is_pdf'];

            $pdf_suffix = $is_pdf ? ' (PDF)' : '';
            $fn_id      = 'fn-' . $fn_index;
            $fn_ref_id  = 'fn-ref-' . $fn_index;

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

            $items[] = '<small id="' . esc_attr( $fn_id ) . '">'
                     . $return_link . ' '
                     . $fn_index . '. '
                     . $linked_label
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
        return ws_render_jx_case_law( $build_items( $citations, 1 ) );
    }

    // Two-group: local citations numbered from 1; federal citations numbered from 1.
    $out  = ws_render_jx_case_law( $build_items( $local, 1 ), 'ws-section--local' );
    $out .= ws_render_jx_case_law( $build_items( $fed,   1 ), 'ws-section--federal' );
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

    return ws_render_jx_limitations( wp_kses_post( $data['limitations'] ) );
}


// ── [ws_jurisdiction_index] ───────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_index', function() {
    $data = ws_get_jurisdiction_index_data();
    return ws_render_jurisdiction_index( $data );
} );
