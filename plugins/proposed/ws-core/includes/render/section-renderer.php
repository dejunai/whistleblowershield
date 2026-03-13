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

function ws_render_jx_header($post_id)
{

    $title = get_the_title($post_id);

    $type = get_field('ws_jx_type', $post_id);

    $flag = get_field('ws_jx_flag_image', $post_id);

    $flag_attribution = get_field('ws_jx_flag_attribution', $post_id);

    $head_label = get_field('ws_jx_head_label', $post_id);
    $head_url = get_field('ws_jx_head_url', $post_id);

    $legal_label = get_field('ws_jx_legal_label', $post_id);
    $legal_url = get_field('ws_jx_legal_url', $post_id);

    $leg_label = get_field('ws_jx_leg_label', $post_id);
    $leg_url = get_field('ws_jx_leg_url', $post_id);


    ob_start();
    ?>

    <header class="ws-jx-header">

        <div class="ws-jx-header-main">

            <?php if ($flag) : ?>

                <div class="ws-jx-flag">

                    <img src="<?php echo esc_url($flag['url']); ?>"
                         alt="<?php echo esc_attr($title); ?> flag">

                </div>

            <?php endif; ?>


            <div class="ws-jx-title-block">

                <h1 class="ws-jx-title">
                    <?php echo esc_html($title); ?>
                </h1>

                <?php if ($type) : ?>

                    <div class="ws-jx-type">
                        <?php echo esc_html($type); ?>
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div class="ws-jx-authorities">

            <?php if ($head_url) : ?>

                <div class="ws-jx-authority">

                    <strong><?php echo esc_html($head_label); ?>:</strong>

                    <a href="<?php echo esc_url($head_url); ?>" target="_blank" rel="noopener">
                        Official Website
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($legal_url) : ?>

                <div class="ws-jx-authority">

                    <strong><?php echo esc_html($legal_label); ?>:</strong>

                    <a href="<?php echo esc_url($legal_url); ?>" target="_blank" rel="noopener">
                        Official Website
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($leg_url) : ?>

                <div class="ws-jx-authority">

                    <strong><?php echo esc_html($leg_label); ?>:</strong>

                    <a href="<?php echo esc_url($leg_url); ?>" target="_blank" rel="noopener">
                        Legislature Website
                    </a>

                </div>

            <?php endif; ?>

        </div>


        <?php if ($flag_attribution) : ?>

            <div class="ws-jx-flag-attribution">

                <?php echo wp_kses_post($flag_attribution); ?>

            </div>

        <?php endif; ?>

    </header>

    <?php

    return ob_get_clean();

}