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
 *   [ws_jx_procedures]
 *       Renders procedures content from the linked jx-procedures post.
 *
 *   [ws_jx_statutes]
 *       Renders statutes content from the linked jx-statutes post.
 *
 *   [ws_jx_resources]
 *       Renders resources content from the linked jx-resources post.
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
 * Procedures, statutes, and resources content comes from the
 * post_content field of their respective addendum CPTs, processed
 * through the_content filters.
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

    // Resolve label strings from select field keys
    $gov_choices = [
        'governor' => 'Office of the Governor',
        'mayor'    => 'Office of the Mayor',
    ];
    $legal_choices = [
        'attorney'  => 'Office of the Attorney General',
        'inspector' => 'D.C. Office of the Inspector General',
        'secretary' => 'Office of the Secretary of Justice',
        'special'   => 'U.S. Office of Special Counsel',
    ];

    $head_label  = $gov_choices[ $jx_data['gov']['head_gov_label'] ]   ?? 'Office of the Governor';
    $legal_label = $legal_choices[ $jx_data['gov']['legal_auth_label'] ] ?? 'Office of the Attorney General';

    $render_data = [
        'jx_name'   => $jx_data['name'],
        'flag_data' => [
            'url'        => $jx_data['flag']['url'],
            'source_url' => $jx_data['flag']['attribution_url'], // fixed: was ['source_url']
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
// Reads content from ACF fields on the linked jx-summary post.
// Does NOT use post_content — all content is stored in ACF fields.

add_shortcode( 'ws_jx_summary', function() {

    global $post;
    $summary_post = ws_get_jx_summary( $post->ID );

    if ( ! $summary_post ) return '';

    // Query layer returns an array — use array key access, not object property.
    $sid = $summary_post['id'];

    // Content fields
    $summary_content = get_field( 'ws_jurisdiction_summary', $sid );
    $sources         = get_field( 'ws_jx_summary_sources',   $sid );

    if ( ! $summary_content ) return '';

    // Date fields
    $date_created  = get_field( 'ws_jx_sum_date_created',  $sid );
    $last_reviewed = get_field( 'ws_jx_sum_last_reviewed', $sid ); // fixed: was ws_last_reviewed

    // Author: ws_jx_sum_author is not a registered ACF field.
    // Use the ws_jx_sum_create_author stamp written on first save.
    $author_name     = '';
    $create_author_id = get_post_meta( $sid, 'ws_jx_sum_create_author', true );
    if ( $create_author_id ) {
        $user = get_userdata( (int) $create_author_id );
        if ( $user ) {
            $author_name = esc_html( $user->display_name );
        }
    }

    // Review status fields
    $human_reviewed = get_field( 'ws_jx_sum_human_reviewed',          $sid );
    $legal_reviewed = get_field( 'ws_jx_sum_legal_review_completed',  $sid );
    $legal_reviewer = get_field( 'ws_jx_sum_legal_reviewer',          $sid );

    // Format dates
    $fmt_created  = $date_created  ? date( 'F j, Y', strtotime( $date_created ) )  : '';
    $fmt_reviewed = $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '';

    $footer_html = ws_render_jx_summary_footer( [
        'author_name'    => $author_name,
        'fmt_created'    => $fmt_created,
        'fmt_reviewed'   => $fmt_reviewed,
        'human_reviewed' => $human_reviewed,
        'legal_reviewed' => $legal_reviewed,
        'legal_reviewer' => $legal_reviewer ?: '',
        'sources'        => $sources ?: '',
    ] );

    return ws_render_jx_summary_section( wp_kses_post( $summary_content ), $footer_html );

} );


// ── [ws_jx_procedures] ────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_procedures', 'ws_shortcode_jx_procedures' );
function ws_shortcode_jx_procedures() {

    global $post;
    if ( ! $post ) return '';

    $procedures = ws_get_jx_procedures( $post->ID );
    if ( ! $procedures ) return '';

    $content = apply_filters( 'the_content', $procedures['content'] );
    return ws_render_section( 'Reporting Procedures', $content );
}


// ── [ws_jx_statutes] ─────────────────────────────────────────────────────────
//
// ws_get_jx_statutes() returns an array-of-arrays. For any non-federal
// jurisdiction this contains up to two entries: the jurisdiction's own
// statutes record and the federal (US) statutes record. The 'is_fed' key
// distinguishes them so each can be labeled appropriately.
//
// State/territory record:  labeled 'Relevant Statutes'
// Federal record:          labeled 'Federal Statutes'
//
// Each entry is rendered as its own ws-jx-section block via ws_render_section().

add_shortcode( 'ws_jx_statutes', 'ws_shortcode_jx_statutes' );
function ws_shortcode_jx_statutes() {

    global $post;
    if ( ! $post ) return '';

    // Returns array-of-arrays or false.
    $statutes = ws_get_jx_statutes( $post->ID );
    if ( ! $statutes ) return '';

    $output = '';

    foreach ( $statutes as $statute ) {

        $content = apply_filters( 'the_content', $statute['content'] );
        if ( ! $content ) continue;

        // Federal record gets its own heading so it's visually distinct
        // from the jurisdiction-specific statutes block above it.
        $section_title = $statute['is_fed'] ? 'Federal Statutes' : 'Relevant Statutes';

        $output .= ws_render_section( $section_title, $content );
    }

    return $output;
}


// ── [ws_jx_resources] ────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_resources', 'ws_shortcode_jx_resources' );
function ws_shortcode_jx_resources() {

    global $post;
    if ( ! $post ) return '';

    $resources = ws_get_jx_resources( $post->ID );
    if ( ! $resources ) return '';

    $content = apply_filters( 'the_content', $resources['content'] );
    return ws_render_section( 'Resources', $content );
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
        'source_url' => $jx_data['flag']['attribution_url'],
        'attr_str'   => $jx_data['flag']['attribution'],
        'license'    => $jx_data['flag']['license'],
    ] );

} );


// ── [ws_jx_review_status] ────────────────────────────────────────────────────

add_shortcode( 'ws_jx_review_status', function() {

    global $post;
    if ( ! $post ) return '';

    $summary = ws_get_jx_summary( $post->ID );
    if ( ! $summary ) return '';

    $sid            = $summary['id']; // query layer returns array
    $human_reviewed = get_field( 'ws_jx_sum_human_reviewed',         $sid );
    $legal_reviewed = get_field( 'ws_jx_sum_legal_review_completed', $sid );
    $legal_reviewer = get_field( 'ws_jx_sum_legal_reviewer',         $sid );
    $last_reviewed  = get_field( 'ws_jx_sum_last_reviewed',          $sid ); // fixed: was ws_last_reviewed
    $fmt_reviewed   = $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '';

    return ws_render_jx_review_status( [
        'fmt_reviewed'   => $fmt_reviewed,
        'human_reviewed' => $human_reviewed,
        'legal_reviewed' => $legal_reviewed,
        'legal_reviewer' => $legal_reviewer ?: '',
    ] );

} );


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

    // Resolve jurisdiction code from the current jurisdiction post.
    $jx_code = get_post_meta( $post->ID, 'ws_jx_code', true );
    if ( ! $jx_code ) return '';

    // Query attached citations ordered by display position.
    $citations = get_posts( [
        'post_type'      => 'jx-citation',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'ws_jx_cite_position',
        'order'          => 'ASC',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => 'ws_jx_code',
                'value' => $jx_code,
            ],
            [
                'key'   => 'ws_jx_cite_attach',
                'value' => '1',
            ],
        ],
    ] );

    if ( empty( $citations ) ) return '';

    // Build footnote anchors and footnote list in one pass.
    $footnote_items = [];
    $fn_index       = 1;

    foreach ( $citations as $citation ) {
        $cid    = $citation->ID;
        $label  = get_post_meta( $cid, 'ws_jx_cite_label',  true );
        $url    = get_post_meta( $cid, 'ws_jx_cite_url',    true );
        $is_pdf = get_post_meta( $cid, 'ws_jx_cite_is_pdf', true );

        $pdf_suffix = $is_pdf ? ' (PDF)' : '';
        $fn_id      = 'fn-' . $fn_index;
        $fn_ref_id  = 'fn-ref-' . $fn_index;

        // Build the linked label for the footnote list entry.
        if ( $url ) {
            $linked_label = '<a href="' . esc_url( $url ) . '" target="_blank">'
                          . esc_html( $label ) . esc_html( $pdf_suffix ) . '</a>';
        } else {
            $linked_label = esc_html( $label ) . esc_html( $pdf_suffix );
        }

        // Unicode return link: ↩ (U+21A9), styled via .ws-footnote-return.
        // To switch to CSS ::before pseudo-element in the future, remove
        // the <a> content here and target .ws-footnote-return::before.
        //
        // @todo The return link href="#fn-ref-X" targets IDs that currently have
        // no corresponding elements in the page — in-text superscript anchors
        // (e.g. <sup id="fn-ref-1">) are not yet emitted by this shortcode.
        // Until in-text anchors are implemented, these return links are dead.
        $return_link = '<a href="#' . esc_attr( $fn_ref_id ) . '" '
                     . 'class="ws-footnote-return" '
                     . 'title="Return to text">&#x21a9;</a>';

        $footnote_items[] = '<small id="' . esc_attr( $fn_id ) . '">'
                          . $return_link . ' '
                          . $fn_index . '. '
                          . $linked_label
                          . '</small>';

        $fn_index++;
    }

    return ws_render_jx_case_law( $footnote_items );
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

    $summary_post = ws_get_jx_summary( $post->ID );
    if ( ! $summary_post ) return '';

    $limitations = get_field( 'ws_jx_limitations', $summary_post['id'] ); // fixed: was ->ID
    if ( ! $limitations ) return '';

    return ws_render_jx_limitations( wp_kses_post( $limitations ) );
}


// ── [ws_jurisdiction_index] ───────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_index', function() {
    $data = ws_get_jurisdiction_index_data();
    return ws_render_jurisdiction_index( $data );
} );
