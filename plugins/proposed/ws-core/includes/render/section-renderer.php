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


/*
---------------------------------------------------------
Jurisdiction Header Renderer
---------------------------------------------------------

Outputs the jurisdiction header block including:

• flag
• jurisdiction name
• jurisdiction type
• leadership links

Data comes from ACF fields attached to the jurisdiction
post itself.
*/
/**
 * Render the Primary Jurisdiction Header
 * Replaces the logic found in legacy [ws_jurisdiction_header]
 *
 * Render the Primary Jurisdiction Header
 * Layout: H1 Title -> [Flag Section] [Leadership Box]
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
 * [NEW] Render a single Jurisdiction Card for the Index
 */
function ws_render_jx_index_card($name, $code, $url, $type) {
    ob_start(); ?>
    <a href="<?php echo esc_url($url); ?>" class="ws-jx-card" data-type="<?php echo esc_attr($type); ?>">
        <div class="ws-jx-card-inner">
            <span class="ws-jx-card-code"><?php echo esc_html($code); ?></span>
            <span class="ws-jx-card-name"><?php echo esc_html($name); ?></span>
        </div>
    </a>
    <?php
    return ob_get_clean();
}

/**
 * [NEW] Render the Legal Review Status Badge
 */
function ws_render_jx_review_status($date) {
    if (empty($date)) return '';
    ob_start(); ?>
    <div class="ws-review-status">
        <span class="ws-review-label">Last Legal Review:</span>
        <span class="ws-review-date"><?php echo esc_html($date); ?></span>
    </div>
    <?php
    return ob_get_clean();
}
function ws_render_nla_disclaimer($text) {
    return '<div class="ws-summary-notice"><strong>NOTICE:</strong> ' . wp_kses_post($text) . '</div>';
}


/**
 * Render the Summary Content Wrapper
 */
function ws_render_jx_summary_section($content, $review_html = '') {
    ob_start(); ?>
    <section class="ws-jx-summary-container">
        <div class="ws-jx-summary-content">
            <?php echo $content; // Already passed through the_content ?>
        </div>
        <?php if ($review_html) : ?>
            <footer class="ws-jx-summary-footer">
                <?php echo $review_html; ?>
            </footer>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}
