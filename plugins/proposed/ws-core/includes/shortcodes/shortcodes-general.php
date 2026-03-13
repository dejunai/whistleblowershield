<?php
/**
 * File: shortcodes-general.php
 * General purpose shortcodes not tied to specific jurisdiction data.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [ws_nla_disclaimer_notice]
 * Renders the standard "Not Legal Advice" notice box.
 */
add_shortcode('ws_nla_disclaimer_notice', function() {
    $text = "This summary is provided for informational purposes only and does not constitute legal advice. Whistleblower laws are complex; consult an attorney.";
    return ws_render_nla_disclaimer($text);
});

/**
 * [ws_footer]
 * Renders the standardized site footer.
 */
add_shortcode('ws_footer', function() {
    ob_start(); ?>
    <div class="ws-footer-block">
        <p>&copy; <?php echo date('Y'); ?> WhistleblowerShield.org. All Rights Reserved.</p>
        <p>Providing plain-language legal resources for whistleblowers nationwide.</p>
    </div>
    <?php
    return ob_get_clean();
});