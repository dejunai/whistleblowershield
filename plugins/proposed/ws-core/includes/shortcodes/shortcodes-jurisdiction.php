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
            'source_url' => $jx_data['flag']['source_url'],
            'attr_str'   => $jx_data['flag']['attr_str'],
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

    $sid = $summary_post->ID;

    // Content fields
    $summary_content = get_field( 'ws_jurisdiction_summary', $sid );
    $sources         = get_field( 'ws_jx_summary_sources',   $sid );

    if ( ! $summary_content ) return '';

    // Date fields
    $date_created  = get_field( 'ws_jx_sum_date_created', $sid );
    $last_reviewed = get_field( 'ws_last_reviewed',        $sid );

    // Author field
    $author_data = get_field( 'ws_jx_sum_author', $sid );
    $author_name = '';
    if ( is_array( $author_data ) && ! empty( $author_data['display_name'] ) ) {
        $author_name = esc_html( $author_data['display_name'] );
    } elseif ( is_numeric( $author_data ) ) {
        $user = get_userdata( (int) $author_data );
        if ( $user ) $author_name = esc_html( $user->display_name );
    }

    // Review status fields
    $human_reviewed = get_field( 'ws_jx_sum_human_reviewed',          $sid );
    $legal_reviewed = get_field( 'ws_jx_sum_legal_review_completed',  $sid );
    $legal_reviewer = get_field( 'ws_jx_sum_legal_reviewer',          $sid );

    // Format dates
    $fmt_created  = $date_created  ? date( 'F j, Y', strtotime( $date_created ) )  : '';
    $fmt_reviewed = $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '';

    // Build review HTML to pass to renderer
    ob_start();
    ?>
    <div class="ws-jx-summary-footer">

        <?php if ( $author_name ) : ?>
        <p class="ws-jx-summary-author">
            <strong>Author:</strong> <?php echo $author_name; ?>
        </p>
        <?php endif; ?>

        <?php if ( $fmt_created ) : ?>
        <p class="ws-jx-summary-date-created">
            <strong>Date Created:</strong> <?php echo esc_html( $fmt_created ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $fmt_reviewed ) : ?>
        <p class="ws-jx-summary-last-reviewed">
            <strong>Last Reviewed:</strong> <?php echo esc_html( $fmt_reviewed ); ?>
        </p>
        <?php endif; ?>

        <div class="ws-review-badges">
            <?php if ( $human_reviewed ) : ?>
                <span class="ws-badge ws-badge-reviewed">&#10003; Human Reviewed</span>
            <?php else : ?>
                <span class="ws-badge ws-badge-pending">&#9679; Pending Human Review</span>
            <?php endif; ?>

            <?php if ( $legal_reviewed ) : ?>
                <span class="ws-badge ws-badge-legal-reviewed">
                    &#10003; Legally Reviewed
                    <?php if ( $legal_reviewer ) : ?>
                        &mdash; <?php echo esc_html( $legal_reviewer ); ?>
                    <?php endif; ?>
                </span>
            <?php else : ?>
                <span class="ws-badge ws-badge-pending">&#9679; Pending Legal Review</span>
            <?php endif; ?>
        </div>

        <?php if ( $sources ) : ?>
        <div class="ws-jx-summary-sources">
            <strong>Sources &amp; Citations:</strong>
            <pre class="ws-jx-sources-text"><?php echo esc_html( $sources ); ?></pre>
        </div>
        <?php endif; ?>

    </div>
    <?php
    $footer_html = ob_get_clean();

    return ws_render_jx_summary_section( wp_kses_post( $summary_content ), $footer_html );

} );


// ── [ws_jx_procedures] ────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_procedures', 'ws_shortcode_jx_procedures' );
function ws_shortcode_jx_procedures() {

    global $post;
    if ( ! $post ) return '';

    $procedures = ws_get_jx_procedures( $post->ID );
    if ( ! $procedures ) return '';

    $content = apply_filters( 'the_content', $procedures->post_content );
    return ws_render_section( 'Reporting Procedures', $content );
}


// ── [ws_jx_statutes] ─────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_statutes', 'ws_shortcode_jx_statutes' );
function ws_shortcode_jx_statutes() {

    global $post;
    if ( ! $post ) return '';

    $statutes = ws_get_jx_statutes( $post->ID );
    if ( ! $statutes ) return '';

    $content = apply_filters( 'the_content', $statutes->post_content );
    return ws_render_section( 'Relevant Statutes', $content );
}


// ── [ws_jx_resources] ────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_resources', 'ws_shortcode_jx_resources' );
function ws_shortcode_jx_resources() {

    global $post;
    if ( ! $post ) return '';

    $resources = ws_get_jx_resources( $post->ID );
    if ( ! $resources ) return '';

    $content = apply_filters( 'the_content', $resources->post_content );
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

    return ws_render_jx_flag( $jx_data['flag_data'] );

} );


// ── [ws_jx_review_status] ────────────────────────────────────────────────────

add_shortcode( 'ws_jx_review_status', function() {

    global $post;
    if ( ! $post ) return '';

    $summary = ws_get_jx_summary( $post->ID );
    if ( ! $summary ) return '';

    $sid            = $summary->ID;
    $human_reviewed = get_field( 'ws_jx_sum_human_reviewed',         $sid );
    $legal_reviewed = get_field( 'ws_jx_sum_legal_review_completed', $sid );
    $legal_reviewer = get_field( 'ws_jx_sum_legal_reviewer',         $sid );
    $last_reviewed  = get_field( 'ws_last_reviewed',                 $sid );
    $fmt_reviewed   = $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '';

    ob_start();
    ?>
    <div class="ws-review-status">

        <?php if ( $fmt_reviewed ) : ?>
        <p class="ws-jx-summary-last-reviewed">
            <strong>Last Reviewed:</strong> <?php echo esc_html( $fmt_reviewed ); ?>
        </p>
        <?php endif; ?>

        <div class="ws-review-badges">
            <?php if ( $human_reviewed ) : ?>
                <span class="ws-badge ws-badge-reviewed">&#10003; Human Reviewed</span>
            <?php else : ?>
                <span class="ws-badge ws-badge-pending">&#9679; Pending Human Review</span>
            <?php endif; ?>

            <?php if ( $legal_reviewed ) : ?>
                <span class="ws-badge ws-badge-legal-reviewed">
                    &#10003; Legally Reviewed
                    <?php if ( $legal_reviewer ) : ?>
                        &mdash; <?php echo esc_html( $legal_reviewer ); ?>
                    <?php endif; ?>
                </span>
            <?php else : ?>
                <span class="ws-badge ws-badge-pending">&#9679; Pending Legal Review</span>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();

} );


// ── [ws_jurisdiction_index] ───────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_index', function() {
    $data = ws_get_jurisdiction_index_data();
    return ws_render_jurisdiction_index( $data );
} );
