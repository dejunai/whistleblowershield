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
 *      • Procedures
 *      • Statutes
 *      • Resources
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
 * 2.1.0  Initial section renderer implementation
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

function ws_render_section($title, $content)
{

    if (!$content) {
        return '';
    }

    ob_start();
    ?>

    <section class="ws-jx-section">

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
 * Render individual Flag component
 * With Attribution and License
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
 * Render the Leadership Offices Box
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
 * Render the Filterable Jurisdiction Index with Conditional Tabs
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
// Review Badges
//
// Private helper shared by ws_render_jx_summary_footer() and
// ws_render_jx_review_status(). Renders the .ws-review-badges block only.
//
// @param  bool   $human_reviewed  True if human review is complete.
// @param  bool   $legal_reviewed  True if legal review is complete.
// @param  string $legal_reviewer  Name of legal reviewer, or empty string.
// @return string                  HTML badges block.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_review_badges( $human_reviewed, $legal_reviewed, $legal_reviewer ) {
    ob_start(); ?>
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
    <?php
    return ob_get_clean();
}


/**
 * Renders the summary section footer.
 *
 * Displays author, creation date, last reviewed date, review status
 * badges, and sources & citations. All fields are optional — sections
 * are omitted when their data is empty.
 *
 * Called by the [ws_jx_summary] shortcode; the return value is passed
 * as $review_html to ws_render_jx_summary_section().
 *
 * @param  array $data {
 *     @type string $author_name    Display name of the content author.
 *     @type string $fmt_created    Formatted creation date string, or empty.
 *     @type string $fmt_reviewed   Formatted last-reviewed date string, or empty.
 *     @type bool   $human_reviewed True if human review is complete.
 *     @type bool   $legal_reviewed True if legal review is complete.
 *     @type string $legal_reviewer Name of legal reviewer, or empty string.
 *     @type string $sources        Sources & citations raw text, or empty.
 * }
 * @return string  HTML footer block.
 */
function ws_render_jx_summary_footer( $data ) {
    ob_start(); ?>
    <div class="ws-jx-summary-footer">

        <?php if ( $data['author_name'] ) : ?>
        <p class="ws-jx-summary-author">
            <strong>Author:</strong> <?php echo esc_html( $data['author_name'] ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $data['fmt_created'] ) : ?>
        <p class="ws-jx-summary-date-created">
            <strong>Date Created:</strong> <?php echo esc_html( $data['fmt_created'] ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $data['fmt_reviewed'] ) : ?>
        <p class="ws-jx-summary-last-reviewed">
            <strong>Last Reviewed:</strong> <?php echo esc_html( $data['fmt_reviewed'] ); ?>
        </p>
        <?php endif; ?>

        <?php echo ws_render_review_badges(
            $data['human_reviewed'],
            $data['legal_reviewed'],
            $data['legal_reviewer']
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


/**
 * Renders the standalone review status block.
 *
 * Displays last reviewed date and review badge indicators. Used by
 * the [ws_jx_review_status] shortcode for embedding review status
 * independently from the full summary section.
 *
 * @param  array $data {
 *     @type string $fmt_reviewed   Formatted last-reviewed date string, or empty.
 *     @type bool   $human_reviewed True if human review is complete.
 *     @type bool   $legal_reviewed True if legal review is complete.
 *     @type string $legal_reviewer Name of legal reviewer, or empty string.
 * }
 * @return string  HTML review status block.
 */
function ws_render_jx_review_status( $data ) {
    ob_start(); ?>
    <div class="ws-review-status">

        <?php if ( $data['fmt_reviewed'] ) : ?>
        <p class="ws-jx-summary-last-reviewed">
            <strong>Last Reviewed:</strong> <?php echo esc_html( $data['fmt_reviewed'] ); ?>
        </p>
        <?php endif; ?>

        <?php echo ws_render_review_badges(
            $data['human_reviewed'],
            $data['legal_reviewed'],
            $data['legal_reviewer']
        ); ?>

    </div>
    <?php
    return ob_get_clean();
}


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
function ws_render_jx_case_law( $items ) {
    if ( empty( $items ) ) return '';
    ob_start(); ?>
    <section class="ws-case-law">
        <hr style="margin: 10px 0;">
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
 * per update. All field data must be pre-fetched and pre-formatted by
 * the caller (ws_shortcode_legal_updates()).
 *
 * Called by the [ws_legal_updates] shortcode after data assembly.
 *
 * @param  array $items {
 *     Array of update data arrays, each containing:
 *     @type string $title         Update post title.
 *     @type string $source_url    Source URL, or empty string.
 *     @type string $law_name      Law / statute name, or empty string.
 *     @type string $fmt_effective Formatted effective date string, or empty.
 *     @type string $post_date     Formatted post date string.
 *     @type string $summary_html  Sanitized summary HTML, or empty string.
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

            <?php if ( $item['fmt_effective'] ) : ?>
            <p class="ws-legal-update-effective">
                <strong>Effective:</strong> <?php echo esc_html( $item['fmt_effective'] ); ?>
            </p>
            <?php endif; ?>

            <p class="ws-legal-update-posted">
                <strong>Posted:</strong> <?php echo esc_html( $item['post_date'] ); ?>
            </p>

            <?php if ( $item['summary_html'] ) : ?>
            <div class="ws-legal-update-summary">
                <?php echo $item['summary_html']; // Already passed through wp_kses_post ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
