<?php
/**
 * render-section.php
 *
 * Render Layer — Jurisdiction Page Section Renderers
 *
 * PURPOSE
 * -------
 * Provides standardized HTML rendering functions for jurisdiction page
 * sections. All functions here are called exclusively by shortcodes in
 * shortcodes-jurisdiction.php or by the jurisdiction page assembler
 * (render-jurisdiction.php). General-purpose page renderers live in
 * render-general.php.
 *
 * By centralizing section layout here, the plugin avoids repeating
 * markup across multiple shortcode implementations and ensures consistent
 * structure, accessibility, and future redesign flexibility.
 *
 *
 * ARCHITECTURE ROLE
 * -----------------
 *
 *   Assembler:   render-jurisdiction.php  — triggers shortcodes
 *   Shortcodes:  shortcodes-jurisdiction.php — calls functions here
 *   Data:        query-jurisdiction.php — upstream data source
 *
 *
 * FUNCTIONS
 * ---------
 *   ws_render_section()                      Generic section wrapper (title + content).
 *   ws_render_section_two_group()            Local + federal two-group section pair.
 *   ws_render_jx_header()                    Jurisdiction page header (H1, flag, gov offices).
 *   ws_render_jx_flag()                      Flag image with Wikimedia attribution.
 *   ws_render_jx_gov_offices()               Leadership offices link box.
 *   ws_render_jx_summary_section()           Summary content + footer wrapper.
 *   ws_render_plain_english_reviewed_badge() Plain-language review status badge.
 *   ws_render_jx_summary_footer()            Summary footer (author, date, badge, sources).
 *   ws_render_jx_citations()                 Case law / citations footnote section.
 *   ws_render_jx_interpretations()           Court interpretations card section.
 *   ws_render_jx_limitations()               Limitations section wrapper.
 *
 *
 * DESIGN GOALS
 * ------------
 *
 * The rendered HTML structure should remain simple, accessible, and readable
 * for users who may be experiencing stress or urgency. WhistleblowerShield
 * prioritizes plain language presentation, clear section separation, and
 * predictable layout.
 *
 *
 * @package    WhistleblowerShield
 * @since      2.1.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 2.1.0  Initial section renderer implementation (as section-renderer.php).
 * 2.1.1  ws_render_legal_updates() @param docblock updated to match the
 *        expanded return array from ws_get_legal_updates_data() v3.2.0.
 * 3.3.2  ws_render_jx_summary_footer() updated to match simplified query
 *        layer return keys (query-jurisdiction.php v3.3.2).
 * 3.6.0  Renamed section-renderer.php → render-section.php for naming
 *        convention alignment (verb-noun, matches render-jurisdiction.php).
 *        General-purpose renderers (ws_render_nla_disclaimer, ws_render_footer,
 *        ws_render_legal_updates, ws_render_jurisdiction_index) moved to
 *        render-general.php. This file now contains jurisdiction-page
 *        section renderers only.
 * 3.9.0  ws_render_jx_limitations() updated: now accepts a repeater array
 *        instead of a sanitized HTML string. Renders label + description
 *        rows as <p><strong>Label:</strong> text</p>. h2 heading moved
 *        into this function (previously lived inside the wysiwyg content).
 * 3.7.0  ws_render_jx_interpretations() added. Renders court interpretation
 *        cards (case name, court/year/citation, favorable indicator, summary,
 *        External References button). Called by [ws_jx_interpretation].
 */

defined( 'ABSPATH' ) || exit;


/*
---------------------------------------------------------
Generic Section Renderer
---------------------------------------------------------

Renders a jurisdiction section with a standardized layout.

Parameters:

$title      Section title
$content    HTML content of the section

Returns:

HTML block ready for output
*/

function ws_render_section( $title, $content, $section_class = '' ) {

    if ( ! $content ) {
        return '';
    }

    $extra_class = $section_class ? ' ' . sanitize_html_class( $section_class ) : '';

    ob_start();
    ?>

    <section class="ws-jx-section<?php echo $extra_class; ?>">

        <h2 class="ws-jx-section-title">
            <?php echo esc_html($title); ?>
        </h2>

        <div class="ws-jx-section-content">
            <?php echo wp_kses_post($content); ?>
        </div>

    </section>

    <?php

    return ob_get_clean();

}


/**
 * Renders a local + federal two-group section pair.
 *
 * When a dataset contains both local (is_fed=false) and federal (is_fed=true)
 * records, call this function with the pre-built HTML for each group.
 * Wraps local content in .ws-section--local and federal content in
 * .ws-section--federal. Omits a group's block entirely if its HTML is empty.
 *
 * The section class logic lives here only — shortcodes pass pre-built
 * content strings and do not reference the class names directly.
 *
 * @param  string $title_local   Section heading for the local group.
 * @param  string $content_local HTML content for state/territory records.
 * @param  string $title_fed     Section heading for the federal group.
 * @param  string $content_fed   HTML content for US-scoped records.
 * @return string  HTML output (one or two section blocks).
 */
function ws_render_section_two_group( $title_local, $content_local, $title_fed, $content_fed ) {
    $out = '';
    if ( $content_local ) {
        $out .= ws_render_section( $title_local, $content_local, 'ws-section--local' );
    }
    if ( $content_fed ) {
        $out .= ws_render_section( $title_fed, $content_fed, 'ws-section--federal' );
    }
    return $out;
}


/**
 * Renders the primary jurisdiction header block.
 *
 * Layout: H1 title → [flag column] [government offices box]
 * Called by the [ws_jx_header] shortcode, which is always the
 * first thing emitted by the auto-assembler in render-jurisdiction.php.
 *
 * @param  array $data {
 *     @type string $jx_name   Jurisdiction display name.
 *     @type array  $flag_data Keys: url, source_url, attr_str, license.
 *     @type array  $gov_data  Keys: box_label, links[] (url, label).
 * }
 * @return string  HTML header block.
 */
function ws_render_jx_header($data) {
    ob_start(); ?>
    <header class="ws-jx-header-v2">
        <h1 class="ws-jx-title"><?php echo esc_html($data['jx_name']); ?></h1>
        <div class="ws-jx-header-split">
            <div class="ws-jx-flag-column">
                <?php echo ws_render_jx_flag($data['flag_data']); ?>
            </div>
            <div class="ws-jx-gov-column">
                <?php echo ws_render_jx_gov_offices($data['gov_data']); ?>
            </div>
        </div>
    </header>
    <?php
    return ob_get_clean();
}

/**
 * Renders the jurisdiction flag image with attribution and license.
 *
 * @param  array $flag_data {
 *     @type string $url            URL to the flag image.
 *     @type string $source_url     URL to the wikimedia source.
 *     @type string $attr_str       Attribution string (plain text).
 *     @type string $license        License identifier (e.g., "Public Domain").
 * }
 * @return string  HTML output, or empty string if no flag URL is set.
 */
function ws_render_jx_flag($flag_data) {
    if (empty($flag_data['url'])) return '';
    ob_start(); ?>
    <div class="ws-jx-flag-wrap">
        <img src="<?php echo esc_url($flag_data['url']); ?>" class="ws-jx-flag-img">
        <div class="ws-jx-attribution">
            <a href="<?php echo esc_url($flag_data['source_url']); ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="ws-term-highlight"
               data-tooltip="<?php echo esc_attr($flag_data['attr_str'] . ' — Click to open on Wikimedia Commons'); ?>">
               Attribution
            </a>
            <?php if (!empty($flag_data['license'])) : ?>
                <span> — <?php echo esc_html($flag_data['license']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders the jurisdiction leadership offices box.
 *
 * @param  array $gov_data {
 *     @type string $box_label  Heading label for the offices box.
 *     @type array  $links      Indexed array of office link arrays, each with 'url' and 'label'.
 * }
 * @return string  HTML output, or empty string if no links provided.
 */
function ws_render_jx_gov_offices($gov_data) {
    if (empty($gov_data['links'])) return '';
    ob_start(); ?>
    <div class="ws-jx-gov-offices-box">
        <h3><?php echo esc_html($gov_data['box_label']); ?></h3>
        <div class="ws-gov-links-list">
            <?php foreach ($gov_data['links'] as $link) :
                if (!empty($link['url'])) : ?>
                    <div class="ws-gov-link-item">
                        <a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($link['label']); ?>
                        </a>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


/**
 * Renders the summary content wrapper.
 *
 * Wraps the WYSIWYG content and optional footer HTML in the
 * .ws-jx-summary-container section. Called by the [ws_jx_summary]
 * shortcode after building $footer_html via ws_render_jx_summary_footer().
 *
 * @param  string $content      Summary body HTML (already through the_content filters).
 * @param  string $review_html  Optional footer block from ws_render_jx_summary_footer().
 * @return string               HTML summary section.
 */
function ws_render_jx_summary_section( $content, $review_html = '' ) {
    ob_start(); ?>
    <section class="ws-jx-summary-container">
        <div class="ws-jx-summary-content">
            <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped // Already passed through the_content ?>
        </div>
        <?php if ( $review_html ) : ?>
            <footer>
                <?php echo wp_kses_post( $review_html ); ?>
            </footer>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// Trust Badge (plain_reviewed)
//
// Renders the plain-language review status badge for a summary record.
// Legal review badge system was removed in Phase 9.0.
//
// @param  bool   $plain_reviewed  True if a human has reviewed the plain-language content.
// @param  string $reviewer_name   Display name of the reviewer, or empty.
// @param  string $reviewed_date   Date review was completed (Y-m-d), or empty.
// @return string                  HTML badge span.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_plain_english_reviewed_badge( $plain_reviewed, $reviewer_name = '', $reviewed_date = '' ) {
    if ( $plain_reviewed ) {
        $parts = [];
        if ( $reviewer_name ) {
            $parts[] = 'Reviewed by ' . esc_attr( $reviewer_name );
        }
        if ( $reviewed_date ) {
            $parts[] = 'on ' . esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $reviewed_date ) ) );
        }
        $tooltip = ! empty( $parts ) ? implode( ' ', $parts ) : 'Reviewed';
        return '<span class="ws-trust-badge ws-trust-badge--reviewed" title="' . esc_attr( $tooltip ) . '">'
             . 'Editor Reviewed'
             . '</span>';
    }
    return '<span class="ws-trust-badge ws-trust-badge--pending">Pending Review</span>';
}


/**
 * Renders the summary section footer.
 *
 * Displays author, creation date, last reviewed date, plain-language
 * review badge, and sources & citations. All fields are optional — sections
 * are omitted when their data is empty.
 *
 * Called by the [ws_jx_summary] shortcode; the return value is passed
 * as $review_html to ws_render_jx_summary_section().
 *
 * @param  array $data {
 *     @type string $created_by_name  Display name of the content author.
 *     @type string $created_date     Creation date (Y-m-d), or empty.
 *     @type bool   $is_reviewed      True if plain-language review is complete.
 *     @type string $reviewed_by_name Display name of the plain-language reviewer.
 *     @type string $reviewed_date    Date the plain-language review was completed (Y-m-d), or empty.
 *     @type string $sources          Sources & citations raw text, or empty.
 * }
 * @return string  HTML footer block.
 */
function ws_render_jx_summary_footer( $data ) {
    ob_start(); ?>
    <div class="ws-jx-summary-footer">

        <?php if ( $data['created_by_name'] ) : ?>
        <p class="ws-jx-summary-author">
            <strong>Author:</strong> <?php echo esc_html( $data['created_by_name'] ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $data['created_date'] ) : ?>
        <p class="ws-jx-summary-date-created">
            <strong>Date Created:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['created_date'] ) ) ); ?>
        </p>
        <?php endif; ?>
		
		<?php if ( $data['edited_by_name'] ) : ?>
			<p class="ws-jx-summary-date-edited">
				<strong>Last Edited By:</strong> <?php echo esc_html( $data['edited_by_name'] ); ?>
			</p>
			<?php if ( $data['edited_date'] ) : ?>
			<p class="ws-jx-summary-date-edited">
				<strong>Last Edited:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['edited_date'] ) ) ); ?>
			</p>
			<?php endif; ?>
		<?php endif; ?>

		 <?php echo ws_render_plain_english_reviewed_badge(
				! empty( $data['is_reviewed'] ),
				$data['reviewed_by_name'] ?? '',
				$data['reviewed_date'] ?? ''
			); ?>

        <?php if ( $data['sources'] ) : ?>
        <div class="ws-jx-summary-sources">
            <strong>Sources &amp; Citations:</strong>
            <pre class="ws-jx-sources-text"><?php echo esc_html( $data['sources'] ); ?></pre>
        </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}


// ws_render_jx_review_status() removed in Phase 9.0.
// The [ws_jx_review_status] shortcode was the only caller; it has been
// removed along with the legal review badge system. Plain-language review
// status is now rendered inline by ws_render_jx_summary_footer().


/**
 * Renders the case law / citations section.
 *
 * Outputs an HR separator and an ordered footnote list inside a
 * .ws-case-law section. Each $item is a pre-built HTML string
 * containing the return link, index number, and linked citation label.
 *
 * Called by ws_shortcode_jx_citation() after the footnote items are
 * assembled from jx-citation query results.
 *
 * @param  array  $items         Array of footnote item HTML strings.
 * @param  string $section_class Optional extra CSS class for the section element.
 * @return string                HTML section block, or empty string if $items is empty.
 */
function ws_render_jx_citations( $items, $section_class = '' ) {
    if ( empty( $items ) ) return '';
    $extra = $section_class ? ' ' . sanitize_html_class( $section_class ) : '';
    ob_start(); ?>
    <section class="ws-case-law<?php echo $extra; ?>">
        <hr class="ws-section-divider">
        <?php foreach ( $items as $item ) : ?>
            <?php echo wp_kses_post( $item ); ?><br>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}


/**
 * Renders the court interpretations section.
 *
 * Formats each interpretation as a card: case name (linked to opinion URL
 * when available), optional common name, court / year / citation meta line,
 * favorable-to-whistleblower indicator, plain-text summary, and a conditional
 * "→ External References" button.
 *
 * Calls ws_get_reference_page_url() to build the ref button URL — a lightweight
 * URL builder, not a database query. Wraps output in ws_render_section() which
 * applies wp_kses_post(). Called by ws_shortcode_jx_interpretation().
 *
 * Note: interpretations are federal court decisions. On state jurisdiction pages
 * they arrive with is_fed = true (US-term append). The --fed modifier class is
 * applied but the local/federal two-group split used for statutes and citations
 * is not appropriate here — all interpretations share a single section heading.
 *
 * @param  array  $interps  Interpretation data arrays from ws_get_jx_interpretation_data().
 * @return string           HTML section block, or empty string if $interps is empty.
 */
function ws_render_jx_interpretations( $interps ) {
    if ( empty( $interps ) ) return '';

    $content = '';
    foreach ( $interps as $interp ) {

        $name_html = $interp['opinion_url']
            ? '<a href="' . esc_url( $interp['opinion_url'] ) . '" target="_blank" rel="noopener noreferrer">'
              . esc_html( $interp['official_name'] ) . '</a>'
            : esc_html( $interp['official_name'] );

        $meta_parts = array_filter( [
            esc_html( $interp['court'] ),
            esc_html( $interp['year'] ),
            esc_html( $interp['citation'] ),
        ] );

        $favorable_class = $interp['favorable'] ? 'ws-interp-favorable' : 'ws-interp-unfavorable';
        $favorable_label = $interp['favorable'] ? 'Favorable' : 'Unfavorable';

        $ref_btn = '';
        if ( ! empty( $interp['ref_materials'] ) ) {
            $ref_url = ws_get_reference_page_url( $interp['id'], 'interpretations' );
            if ( $ref_url ) {
                $ref_btn = '<div class="ws-ref-materials-link">'
                         . '<a href="' . esc_url( $ref_url ) . '" class="ws-ref-materials-btn" target="_blank">'
                         . '&rarr; External References'
                         . '</a></div>';
            }
        }

        $card_class = 'ws-interpretation-card'
                    . ( $interp['is_fed'] ? ' ws-interpretation-card--fed' : '' );

        $card  = '<div class="' . esc_attr( $card_class ) . '">';
        $card .= '<p class="ws-interp-case-name">' . $name_html . '</p>';

        if ( $interp['common_name'] ) {
            $card .= '<p class="ws-interp-common-name">'
                   . esc_html( $interp['common_name'] ) . '</p>';
        }

        if ( $meta_parts ) {
            $favorable_span = '<span class="' . esc_attr( $favorable_class ) . '">'
                            . esc_html( $favorable_label ) . '</span>';
            $card .= '<p class="ws-interp-meta">'
                   . implode( ' &bull; ', $meta_parts )
                   . ' &bull; ' . $favorable_span . '</p>';
        }

        if ( $interp['summary'] ) {
            $card .= '<p class="ws-interp-summary">'
                   . esc_html( $interp['summary'] ) . '</p>';
        }

        $card    .= $ref_btn;
        $card    .= '</div>';
        $content .= $card;
    }

    return ws_render_section( 'Court Interpretations', $content );
}


/**
 * Renders the Limitations and Ramifications section from a repeater array.
 *
 * Each row produces one paragraph: the label bolded, followed by the
 * description as normal text. Both values are plain text — no HTML is
 * expected or allowed in the source data.
 *
 * Called by ws_shortcode_jx_limitations(). Returns empty string when
 * $limitations is empty so the section heading is never rendered without
 * content.
 *
 * @param  array  $limitations  Rows from ws_jx_limitations repeater.
 *                              Each row: ['ws_jx_limit_label' => '', 'ws_jx_limit_text' => '']
 * @return string               HTML section block, or '' when empty.
 */
function ws_render_jx_limitations( $limitations ) {
    if ( empty( $limitations ) || ! is_array( $limitations ) ) {
        return '';
    }

    $items = '';
    foreach ( $limitations as $row ) {
        $label = sanitize_text_field( $row['ws_jx_limit_label'] ?? '' );
        $text  = sanitize_text_field( $row['ws_jx_limit_text']  ?? '' );
        if ( ! $label && ! $text ) {
            continue;
        }
        $items .= '<p>';
        if ( $label ) {
            $items .= '<strong>' . esc_html( $label ) . ':</strong> ';
        }
        $items .= esc_html( $text );
        $items .= '</p>';
    }

    if ( ! $items ) {
        return '';
    }

    return '<section class="ws-limitations"><h2>Limitations and Ramifications</h2>' . $items . '</section>';
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_statute_procedures( $procedures )
//
// Renders a compact cross-reference panel beneath a statute block on the
// jurisdiction page: "Filing Procedures Under This Statute". Called from
// the $build_statute_chunk closure in shortcodes-jurisdiction.php.
//
// Deliberately compact — shows enough for the end-user to recognise the
// path forward and link to the full procedure card on the agency page.
// Not a full procedure card render (that lives in render-agency.php).
//
// Sections per item:
//   — Procedure title (linked to individual procedure permalink)
//   — Agency name (linked to agency page)
//   — Type badge (disclosure / retaliation / both)
//   — Filing deadline if set (e.g. "180-day deadline")
//   — "Intake Only" badge if intake_only is true
//
// @param  array  $procedures  Rows from ws_get_procedures_for_statute().
// @return string  HTML block, or '' when $procedures is empty.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_statute_procedures( $procedures ) {

    if ( empty( $procedures ) ) {
        return '';
    }

    /** @var array<string,string> Procedure type slug → short display label. */
    $type_labels = [
        'disclosure'  => 'Disclosure',
        'retaliation' => 'Retaliation',
        'both'        => 'Disclosure &amp; Retaliation',
    ];

    ob_start();
    ?>
    <div class="ws-statute-procedures">
        <h6 class="ws-statute-procedures__heading">Filing Procedures Under This Statute</h6>
        <ul class="ws-statute-procedures__list" role="list">
            <?php foreach ( $procedures as $proc ) : ?>
                <li class="ws-statute-procedures__item" role="listitem">

                    <a href="<?php echo esc_url( $proc['url'] ); ?>"
                       class="ws-statute-procedures__proc-link">
                        <?php echo esc_html( $proc['title'] ); ?>
                    </a>

                    <?php if ( ! empty( $proc['agency_name'] ) && ! empty( $proc['agency_url'] ) ) : ?>
                        <span class="ws-statute-procedures__agency">
                            — <a href="<?php echo esc_url( $proc['agency_url'] ); ?>">
                                <?php echo esc_html( $proc['agency_name'] ); ?>
                              </a>
                        </span>
                    <?php endif; ?>

                    <?php
                    $type_label = $type_labels[ $proc['type'] ?? '' ] ?? '';
                    if ( $type_label ) :
                    ?>
                        <span class="ws-statute-procedures__badge ws-statute-procedures__badge--type
                                     ws-statute-procedures__badge--<?php echo esc_attr( $proc['type'] ); ?>">
                            <?php echo wp_kses( $type_label, [] ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( ! empty( $proc['deadline_days'] ) ) : ?>
                        <span class="ws-statute-procedures__badge ws-statute-procedures__badge--deadline">
                            <?php echo absint( $proc['deadline_days'] ); ?>-day deadline
                        </span>
                    <?php endif; ?>

                    <?php if ( ! empty( $proc['intake_only'] ) ) : ?>
                        <span class="ws-statute-procedures__badge ws-statute-procedures__badge--intake-only"
                              title="This agency receives and refers reports only — it does not investigate.">
                            Intake Only
                        </span>
                    <?php endif; ?>

                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
