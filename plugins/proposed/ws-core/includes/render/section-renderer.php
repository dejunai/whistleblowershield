<?php
/**
 * File: section-renderer.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Provides standardized rendering functions for Jurisdiction page
 * sections.
 *
 * This file centralizes the HTML structure used by jurisdiction
 * datasets such as:
 *
 *      • Summary
 *      • Statutes
 *      • Case Law Citations
 *      
 *
 * By centralizing section layout here, the plugin avoids repeating
 * markup across multiple shortcode implementations.
 *
 * This improves:
 *
 *      • code readability
 *      • long-term maintainability
 *      • layout consistency
 *      • future theme redesign flexibility
 *
 *
 * ARCHITECTURE ROLE
 * -----------------
 *
 * Jurisdiction pages are assembled automatically using:
 *
 *      render-jurisdiction.php
 *
 * Each section is then rendered by a shortcode defined in:
 *
 *      shortcodes-jurisdiction.php
 *
 * The shortcode retrieves dataset content and passes it to the
 * helper functions defined in this file.
 *
 *
 * Example Flow
 * ------------
 *
 * Shortcode executes:
 *
 *      ws_jx_summary
 *
 * Shortcode retrieves dataset content:
 *
 *      ws_get_jx_summary()
 *
 * Shortcode passes content to renderer:
 *
 *      ws_render_section()
 *
 *
 * DESIGN GOALS
 * ------------
 *
 * The rendered HTML structure should remain simple, accessible,
 * and readable for users who may be experiencing stress or urgency.
 *
 * WhistleblowerShield prioritizes:
 *
 *      • plain language presentation
 *      • clear section separation
 *      • predictable layout
 *
 *
 * VERSION
 * -------
 * 2.1.0  Initial section renderer implementation.
 * 2.1.1  ws_render_legal_updates() @param docblock updated to match the
 *        expanded return array from ws_get_legal_updates_data() v3.2.0:
 *        added post_id, update_date, update_type, multi_jurisdiction,
 *        source_post_id, source_post_type, record; removed stale
 *        fmt_effective key. Return key renamed summary_html → summary_wysiwyg.
 * 3.3.2  ws_render_jx_summary_footer() updated to match simplified query
 *        layer return keys (query-jurisdiction.php v3.3.2). Expected keys
 *        updated: author_name → created_by_name, create_date → created_date,
 *        ws_plain_english_reviewed → is_reviewed,
 *        plain_english_reviewed_name → reviewed_by_name.
 *        Stale fmt_created and fmt_reviewed @param entries removed from docblock.
 */


if (!defined('ABSPATH')) {
    exit;
}


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
                        <a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="noopener">
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
 * Renders the filterable jurisdiction index with type tabs and alphabetical grid.
 *
 * @param  array $data {
 *     @type array $items   Indexed array of jurisdiction data arrays (from ws_get_all_jurisdictions()).
 *     @type array $counts  Associative array of type → count for rendering filter tabs.
 * }
 * @return string  HTML output, or a "No jurisdictions found" paragraph.
 */
function ws_render_jurisdiction_index($data) {
    $items  = $data['items'];
    $counts = $data['counts'];

    if (empty($items)) return '<p>No jurisdictions found.</p>';

    $type_labels = [
        'all'       => 'All',
        'state'     => 'States',
        'territory' => 'Territories',
        'district'  => 'Districts',
        'federal'   => 'Federal'
    ];

    ob_start(); ?>
    <div class="ws-jx-index-container">
        <nav class="ws-jx-filter-nav">
            <?php foreach ($type_labels as $key => $label) : 
                // Skip rendering the button if the count is zero
                if (empty($counts[$key])) continue; 
                ?>
                <button class="ws-jx-filter-btn <?php echo $key === 'all' ? 'ws-active' : ''; ?>" 
                        data-filter="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($label); ?> 
                    <span class="ws-jx-count">(<?php echo intval($counts[$key]); ?>)</span>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="ws-jx-grid">
            <?php foreach ($items as $jx) : ?>
                <a href="<?php echo esc_url($jx['url']); ?>"
                   class="ws-jx-card"
                   data-type="<?php echo esc_attr($jx['type']); ?>">
                    <span class="ws-jx-card-code"><?php echo esc_html($jx['code']); ?></span>
                    <span class="ws-jx-card-name"><?php echo esc_html($jx['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    // Filter tab behavior is handled by ws-core-front.js (enqueued in ws-core.php).
    // Cards are hidden/shown via card.style.display in that script.
    return ob_get_clean();
}
/**
 * Renders the standard "not legal advice" disclaimer notice.
 *
 * Called by the [ws_nla_disclaimer_notice] shortcode in shortcodes-general.php.
 * The notice text is managed centrally in that shortcode — editing it there
 * propagates to all jurisdiction pages. Styling is handled by
 * .ws-nla-disclaimer-notice in ws-core-front.css.
 *
 * @param  string $text  The disclaimer text to display.
 * @return string        HTML notice block.
 */
function ws_render_nla_disclaimer($text) {
    return '<div class="ws-summary-notice"><strong>NOTICE:</strong> ' . wp_kses_post($text) . '</div>';
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
            <?php echo $content; // Already passed through the_content ?>
        </div>
        <?php if ( $review_html ) : ?>
            <footer class="ws-jx-summary-footer">
                <?php echo $review_html; ?>
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
// @param  bool $plain_reviewed  True if a human has reviewed the plain-language content.
// @return string                HTML badge span.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_plain_english_reviewed_badge( $plain_reviewed, $reviewer_name = '', $reviewed_date = '' ) {
    if ( $plain_reviewed ) {
        $parts = [];
        if ( $reviewer_name ) {
            $parts[] = 'Reviewed by ' . esc_attr( $reviewer_name );
        }
        if ( $reviewed_date ) {
            $parts[] = 'on ' . esc_attr( $reviewed_date );
        }
        $tooltip = ! empty( $parts ) ? implode( ' ', $parts ) : 'Reviewed';
        return '<span class="ws-trust-badge ws-trust-badge--reviewed" title="' . $tooltip . '">'
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
            <strong>Date Created:</strong> <?php echo esc_html( $data['created_date'] ); ?>
        </p>
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
 * Called by ws_shortcode_jx_case_law() after the footnote items are
 * assembled from jx-citation query results.
 *
 * @param  array $items  Array of footnote item HTML strings.
 * @return string        HTML section block, or empty string if $items is empty.
 */
function ws_render_jx_case_law( $items, $section_class = '' ) {
    if ( empty( $items ) ) return '';
    $extra = $section_class ? ' ' . sanitize_html_class( $section_class ) : '';
    ob_start(); ?>
    <section class="ws-case-law<?php echo $extra; ?>">
        <hr class="ws-section-divider">
        <?php foreach ( $items as $item ) : ?>
            <?php echo $item; ?><br>
        <?php endforeach; ?>
    </section>
    <?php
    return ob_get_clean();
}


/**
 * Renders the limitations section wrapper.
 *
 * Wraps sanitized WYSIWYG content in the .ws-limitations section element.
 * Content must already be passed through wp_kses_post() by the caller.
 * Called by ws_shortcode_jx_limitations().
 *
 * @param  string $content  Sanitized HTML content.
 * @return string           HTML section block.
 */
function ws_render_jx_limitations( $content ) {
    return '<section class="ws-limitations">' . $content . '</section>';
}


/**
 * Renders the site-wide footer block.
 *
 * Outputs mission statement, policy navigation links, contact email,
 * and copyright line. Called by the [ws_footer] shortcode.
 * Styling is handled by .ws-footer-block in ws-core-front.css.
 *
 * @param  array $data {
 *     @type string $year         Current four-digit year string.
 *     @type array  $policy_links Associative array of label => site-relative URL slug.
 * }
 * @return string  HTML footer block.
 */
function ws_render_footer( $data ) {
    ob_start(); ?>
    <div class="ws-footer-block">

        <p class="ws-footer-mission">
            A nonpartisan educational reference of U.S. whistleblower protections &mdash; state by state and federal.
        </p>

        <nav class="ws-footer-policy-links" aria-label="Site policies">
            <?php foreach ( $data['policy_links'] as $label => $slug ) : ?>
                <a href="<?php echo esc_url( home_url( $slug ) ); ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="ws-footer-contact">
            Contact: <a href="mailto:admin@whistleblowershield.org">admin@whistleblowershield.org</a>
        </p>

        <p class="ws-footer-copyright">
            &copy; <?php echo esc_html( $data['year'] ); ?> WhistleblowerShield.org &mdash; All rights reserved.
        </p>

    </div>
    <?php
    return ob_get_clean();
}


/**
 * Renders the legal updates list.
 *
 * Outputs a .ws-legal-updates container with one .ws-legal-update-item
 * per update. All field data must be pre-fetched by the caller via
 * ws_get_legal_updates_data(). Dates arrive as Y-m-d strings; format
 * for display in the render layer before passing here.
 *
 * Called by the [ws_legal_updates] shortcode after data assembly.
 *
 * @param  array $items {
 *     Array of update data arrays, each from ws_get_legal_updates_data():
 *     @type int    $post_id          ws-legal-update post ID.
 *     @type string $title            Update post title.
 *     @type string $update_date      Date update was logged (Y-m-d local).
 *     @type string $effective_date   Date the legal change takes effect (Y-m-d local).
 *     @type string $post_date        MySQL post_date from WP core.
 *     @type string $update_type      Update type slug (statute, citation, etc.).
 *     @type bool   $multi_jurisdiction True if update affects more than one jurisdiction.
 *     @type string $law_name         Official name of the affected law, or empty string.
 *     @type string $source_url       Primary source URL, or empty string.
 *     @type string $summary_wysiwyg  Sanitized wysiwyg HTML summary (wp_kses_post applied).
 *     @type int    $source_post_id   Post ID of the source jx-* record, or 0.
 *     @type string $source_post_type Post type slug of the source record, or empty string.
 *     @type array  $record           Stamp fields — see ws_build_record_array().
 * }
 * @return string  HTML updates list block.
 */
function ws_render_legal_updates( $items ) {
    ob_start(); ?>
    <div class="ws-legal-updates">
        <?php foreach ( $items as $item ) : ?>
        <div class="ws-legal-update-item">

            <h3 class="ws-legal-update-title">
                <?php if ( $item['source_url'] ) : ?>
                    <a href="<?php echo esc_url( $item['source_url'] ); ?>"
                       target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html( $item['title'] ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( $item['title'] ); ?>
                <?php endif; ?>
            </h3>

            <?php if ( $item['law_name'] ) : ?>
            <p class="ws-legal-update-law">
                <strong>Law / Statute:</strong> <?php echo esc_html( $item['law_name'] ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $item['effective_date'] ) : ?>
            <p class="ws-legal-update-effective">
                <strong>Effective:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['effective_date'] ) ) ); ?>
            </p>
            <?php endif; ?>

            <p class="ws-legal-update-posted">
                <strong>Posted:</strong> <?php echo esc_html( $item['post_date'] ); ?>
            </p>

            <?php if ( $item['summary_wysiwyg'] ) : ?>
            <div class="ws-legal-update-summary">
                <?php echo $item['summary_wysiwyg']; // Already passed through wp_kses_post ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
