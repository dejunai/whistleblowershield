<?php
/**
 * render-general.php
 *
 * Render Layer — General Page Renderers
 *
 * PURPOSE
 * -------
 * Provides HTML rendering functions for general-purpose pages and site-wide
 * components. All functions here are called by shortcodes in
 * shortcodes-general.php. Jurisdiction-page-specific section renderers live
 * in render-section.php.
 *
 * This mirrors the shortcode layer split:
 *
 *   shortcodes-general.php    →  render-general.php   (this file)
 *   shortcodes-jurisdiction.php  →  render-section.php
 *
 *
 * FUNCTIONS
 * ---------
 *   ws_render_nla_disclaimer()      "Not legal advice" notice box.
 *   ws_render_footer()              Site-wide footer block.
 *   ws_render_legal_updates()       Legal updates list.
 *   ws_render_jurisdiction_index()  Filterable jurisdiction index grid.
 *
 *
 * @package    WhistleblowerShield
 * @since      3.6.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.6.0  Extracted from section-renderer.php as part of render-layer split.
 *        ws_render_nla_disclaimer(), ws_render_footer(), ws_render_legal_updates(),
 *        and ws_render_jurisdiction_index() previously defined in that file.
 */

defined( 'ABSPATH' ) || exit;


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
function ws_render_nla_disclaimer( $text ) {
    return '<div class="ws-summary-notice"><strong>NOTICE:</strong> ' . wp_kses_post( $text ) . '</div>';
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
 * ws_get_legal_updates_data(). Dates arrive as Y-m-d strings and are
 * formatted for display here in the render layer.
 *
 * Called by the [ws_legal_updates] shortcode after data assembly.
 *
 * @param  array $items {
 *     Array of update data arrays, each from ws_get_legal_updates_data():
 *     @type int    $id                ws-legal-update post ID.
 *     @type string $title            Update post title.
 *     @type string $update_date      Date update was logged (Y-m-d local).
 *     @type string $effective_date   Date the legal change takes effect (Y-m-d local).
 *     @type string $post_date        MySQL post_date from WP core.
 *     @type string $type             Update type slug (statute, citation, etc.).
 *     @type bool   $multi_jurisdiction True if update affects more than one jurisdiction.
 *     @type string $law_name         Official name of the affected law, or empty string.
 *     @type string $source_url       Primary source URL, or empty string.
 *     @type string $summary          Sanitized wysiwyg HTML summary (wp_kses_post applied).
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

            <?php if ( $item['type'] ) : ?>
            <p class="ws-legal-update-type">
                <strong>Update Type:</strong> <?php echo esc_html( $item['type'] ); ?>
            </p>
            <?php endif; ?>

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
                <strong>Posted:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['post_date'] ) ) ); ?>
            </p>

            <?php if ( $item['summary'] ) : ?>
            <div class="ws-legal-update-summary">
                <?php echo $item['summary']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped // Already passed through wp_kses_post ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}


/**
 * Renders the filterable jurisdiction index with type tabs and alphabetical grid.
 *
 * Called by the [ws_jurisdiction_index] shortcode in shortcodes-general.php.
 * Filter tab behavior is handled by ws-core-front.js (enqueued in ws-core.php).
 * Cards are hidden/shown via card.style.display in that script.
 *
 * @param  array $data {
 *     @type array $items   Indexed array of jurisdiction data arrays (from ws_get_jurisdiction_index_data()).
 *     @type array $counts  Associative array of type → count for rendering filter tabs.
 * }
 * @return string  HTML output, or a "No jurisdictions found" paragraph.
 */
function ws_render_jurisdiction_index( $data ) {
    $items  = $data['items'];
    $counts = $data['counts'];

    if ( empty( $items ) ) return '<p>No jurisdictions found.</p>';

    $type_labels = [
        'all'       => 'All',
        'state'     => 'States',
        'territory' => 'Territories',
        'district'  => 'Districts',
        'federal'   => 'Federal',
    ];

    ob_start(); ?>
    <div class="ws-jx-index-container">
        <nav class="ws-jx-filter-nav">
            <?php foreach ( $type_labels as $key => $label ) :
                if ( empty( $counts[ $key ] ) ) continue; ?>
                <button class="ws-jx-filter-btn <?php echo $key === 'all' ? 'ws-active' : ''; ?>"
                        data-filter="<?php echo esc_attr( $key ); ?>">
                    <?php echo esc_html( $label ); ?>
                    <span class="ws-jx-count">(<?php echo intval( $counts[ $key ] ); ?>)</span>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="ws-jx-grid">
            <?php foreach ( $items as $jx ) : ?>
                <a href="<?php echo esc_url( $jx['url'] ); ?>"
                   class="ws-jx-card"
                   data-type="<?php echo esc_attr( $jx['type'] ); ?>">
                    <span class="ws-jx-card-code"><?php echo esc_html( $jx['code'] ); ?></span>
                    <span class="ws-jx-card-name"><?php echo esc_html( $jx['name'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
