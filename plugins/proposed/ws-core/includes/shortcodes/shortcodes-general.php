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

    return ws_render_footer( [
        'year'         => date( 'Y' ),
        'policy_links' => [
            'Privacy Policy'     => '/privacy-policy/',
            'Disclaimer'         => '/disclaimer/',
            'Corrections Policy' => '/corrections-policy/',
            'Editorial Policy'   => '/editorial-policy/',
        ],
    ] );

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

    // Assemble data for each update item before passing to the render layer.
    $items = [];
    foreach ( $updates as $update ) {
        $effective_date = get_field( 'ws_legal_update_effective_date', $update->ID );
        $items[] = [
            'title'         => get_the_title( $update->ID ),
            'source_url'    => get_field( 'ws_legal_update_source_url', $update->ID ) ?: '',
            'law_name'      => get_field( 'ws_legal_update_law_name',   $update->ID ) ?: '',
            'fmt_effective' => $effective_date ? date( 'F j, Y', strtotime( $effective_date ) ) : '',
            'post_date'     => get_the_date( 'F j, Y', $update->ID ),
            'summary_html'  => wp_kses_post( get_field( 'ws_legal_update_summary', $update->ID ) ?: '' ),
        ];
    }

    return ws_render_legal_updates( $items );
}
