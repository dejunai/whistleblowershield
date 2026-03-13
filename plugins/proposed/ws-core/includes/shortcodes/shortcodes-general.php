<?php
/**
 * File: shortcodes-general.php
 *
 * General purpose shortcodes not tied to specific jurisdiction data.
 * These shortcodes are available site-wide and may be placed manually
 * on any page, or called by the auto-renderer in render-jurisdiction.php.
 *
 * Shortcodes registered here:
 *
 *   [ws_nla_disclaimer_notice]
 *       Renders the standard "not legal advice" notice box.
 *       Copy is managed centrally in this file — editing $notice_text
 *       propagates to all jurisdiction pages automatically.
 *
 *   [ws_footer]
 *       Renders the site-wide footer block: mission statement,
 *       policy page links, contact email, and copyright line.
 *
 *   [ws_legal_updates jurisdiction="california" count="5"]
 *       Renders recent legal updates. Scoped to a jurisdiction when
 *       the jurisdiction parameter is provided; site-wide when omitted.
 *       Queries the ws-legal-update CPT.
 *
 * VERSION
 * -------
 * 2.1.3  Full implementations restored from v2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── [ws_nla_disclaimer_notice] ────────────────────────────────────────────────
//
// To update the notice text site-wide: edit $notice_text below.
// The change propagates to all jurisdiction pages automatically.
// Styling is handled by .ws-nla-disclaimer-notice in ws-core-front.css.

add_shortcode( 'ws_nla_disclaimer_notice', function() {

    $notice_text = 'This page is provided for informational purposes only '
        . 'and does not constitute legal advice. The "Whistleblower Shield" '
        . 'is a database of legal information, not a law firm. Users should '
        . 'consult with a qualified legal professional regarding the specifics '
        . 'of their situation before initiating any formal disclosure or legal action.';

    return ws_render_nla_disclaimer( $notice_text );

} );


// ── [ws_footer] ───────────────────────────────────────────────────────────────

add_shortcode( 'ws_footer', function() {

    $current_year = date( 'Y' );

    $policy_links = [
        'Privacy Policy'     => '/privacy-policy/',
        'Disclaimer'         => '/disclaimer/',
        'Corrections Policy' => '/corrections-policy/',
        'Editorial Policy'   => '/editorial-policy/',
    ];

    ob_start();
    ?>
    <div class="ws-footer-block">

        <p class="ws-footer-mission">
            A nonpartisan educational reference of U.S. whistleblower protections &mdash; state by state and federal.
        </p>

        <nav class="ws-footer-policy-links" aria-label="Site policies">
            <?php foreach ( $policy_links as $label => $slug ) : ?>
                <a href="<?php echo esc_url( home_url( $slug ) ); ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="ws-footer-contact">
            Contact: <a href="mailto:admin@whistleblowershield.org">admin@whistleblowershield.org</a>
        </p>

        <p class="ws-footer-copyright">
            &copy; <?php echo esc_html( $current_year ); ?> WhistleblowerShield.org &mdash; All rights reserved.
        </p>

    </div>
    <?php
    return ob_get_clean();

} );


// ── [ws_legal_updates] ────────────────────────────────────────────────────────
//
// Renders recent legal updates for a specified jurisdiction, or site-wide
// if no jurisdiction parameter is given.
//
// Usage:
//   [ws_legal_updates jurisdiction="california" count="5"]
//   [ws_legal_updates count="10"]   ← site-wide

add_shortcode( 'ws_legal_updates', 'ws_shortcode_legal_updates' );
function ws_shortcode_legal_updates( $atts ) {

    $atts = shortcode_atts( [
        'jurisdiction' => '',
        'count'        => 5,
    ], $atts, 'ws_legal_updates' );

    $count = max( 1, (int) $atts['count'] );

    $meta_query = [];
    if ( ! empty( $atts['jurisdiction'] ) ) {
        // Resolve slug or ID to a jurisdiction post
        if ( is_numeric( $atts['jurisdiction'] ) ) {
            $jx_id = (int) $atts['jurisdiction'];
        } else {
            $jx_id = ws_get_id_by_code( strtoupper( $atts['jurisdiction'] ) );
            if ( ! $jx_id ) {
                // Fall back to slug lookup
                $posts = get_posts( [
                    'post_type'      => 'jurisdiction',
                    'name'           => sanitize_title( $atts['jurisdiction'] ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ] );
                $jx_id = ! empty( $posts ) ? $posts[0] : 0;
            }
        }

        if ( $jx_id ) {
            $meta_query = [ [
                'key'     => 'ws_legal_update_jurisdiction',
                'value'   => '"' . $jx_id . '"',
                'compare' => 'LIKE',
            ] ];
        }
    }

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="ws-legal-updates">
        <?php foreach ( $updates as $update ) :
            $law_name       = get_field( 'ws_legal_update_law_name',       $update->ID );
            $effective_date = get_field( 'ws_legal_update_effective_date', $update->ID );
            $source_url     = get_field( 'ws_legal_update_source_url',     $update->ID );
            $summary_html   = get_field( 'ws_legal_update_summary',        $update->ID );
            $fmt_effective  = $effective_date ? date( 'F j, Y', strtotime( $effective_date ) ) : '';
            $post_date      = get_the_date( 'F j, Y', $update->ID );
        ?>
        <div class="ws-legal-update-item">

            <h3 class="ws-legal-update-title">
                <?php if ( $source_url ) : ?>
                    <a href="<?php echo esc_url( $source_url ); ?>"
                       target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html( get_the_title( $update->ID ) ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( get_the_title( $update->ID ) ); ?>
                <?php endif; ?>
            </h3>

            <?php if ( $law_name ) : ?>
            <p class="ws-legal-update-law">
                <strong>Law / Statute:</strong> <?php echo esc_html( $law_name ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $fmt_effective ) : ?>
            <p class="ws-legal-update-effective">
                <strong>Effective:</strong> <?php echo esc_html( $fmt_effective ); ?>
            </p>
            <?php endif; ?>

            <p class="ws-legal-update-posted">
                <strong>Posted:</strong> <?php echo esc_html( $post_date ); ?>
            </p>

            <?php if ( $summary_html ) : ?>
            <div class="ws-legal-update-summary">
                <?php echo wp_kses_post( $summary_html ); ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
