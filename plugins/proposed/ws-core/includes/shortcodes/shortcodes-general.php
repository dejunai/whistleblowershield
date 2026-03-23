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
 *   [ws_legal_updates jx="california" count="5"]
 *       Renders recent legal updates. Scoped to a jurisdiction when jx
 *       is provided; site-wide when omitted. Jurisdiction calls restrict
 *       to WS_LEGAL_UPDATE_SUMMARY_TYPES automatically. Queries the
 *       ws-legal-update CPT.
 *
 *   [ws_reference_page post_id="123"]
 *       Renders the full reference materials page for a given jx-statute,
 *       jx-citation, or jx-interpretation post. Displays a back link,
 *       the parent post title, and a list of approved ws-reference items.
 *       If no approved references exist, renders a fallback message.
 *       All data reads delegated to ws_get_reference_page_data() in the
 *       query layer.
 *
 *   [ws_jurisdiction_index]
 *       Renders the full filterable jurisdiction index with type filter tabs
 *       and alphabetical grid. Placed on a standalone WP page — not called
 *       by the assembler. Moved here from shortcodes-jurisdiction.php (v3.6.0).
 *
 *   [ws_assist_org_directory type="" sector="" stage="" cost_model=""]
 *       Renders the nationwide assist-org directory. Shortcode attributes
 *       pre-filter results; all attributes are optional (omit = show all).
 *       URL query params aorg_type, aorg_sector, aorg_stage, aorg_cost
 *       take priority over shortcode attributes, enabling deep-linking from
 *       jurisdiction pages. Data from ws_get_nationwide_assist_org_data();
 *       render via ws_render_directory_page() in render-directory.php.
 *
 * VERSION
 * -------
 * 2.1.3  Full implementations restored from v2.0.0
 * 3.0.0  Phase 12.2: [ws_legal_updates] field reads moved to
 *         ws_get_legal_updates_data() in query-jurisdiction.php.
 *         Shortcode now delegates all data access to the query layer.
 * 3.1.0  Added [ws_reference_page] shortcode for ws-reference CPT system.
 * 3.3.3  DATA SOURCES docblock: corrected source_method value list (ai_assist →
 *         ai_assisted; added bulk_import). Evaluated ws_get_reference_page_url()
 *         relocation — no move needed, apply_filters() pattern is correct.
 * 3.6.0  [ws_jurisdiction_index] moved here from shortcodes-jurisdiction.php.
 *         Duplicate QUERY LAYER RETURN REFERENCE block removed — canonical copy
 *         lives in shortcodes-jurisdiction.php.
 * 3.7.0  Added [ws_assist_org_directory] shortcode. Delegates to
 *         ws_get_nationwide_assist_org_data() + ws_render_directory_page().
 *         Supports shortcode atts and URL param overrides for deep-linking.
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
// if no jurisdiction parameter is given. Has defaults per scope when no count parameter is given
//
// Usage:
//   [ws_legal_updates jx="california"]           <- scoped to jurisdiction, summary types only, default count 5
//   [ws_legal_updates]                           <- site-wide, all types, default count 100
//
// Summary types are defined by WS_LEGAL_UPDATE_SUMMARY_TYPES in ws-core.php.
//
// DEPLOYMENT
// Use 1 -- Jurisdiction page (assembled by render-jurisdiction.php):
//   [ws_legal_updates jx="CA" count="5"]
//   Shows the last 5 summary-type updates scoped to the current jurisdiction.
//
// Use 2 -- Site-wide changelog page (standalone WP page, manually placed):
//   [ws_legal_updates]
//   Shows the last 100 updates of all types across all jurisdictions.


add_shortcode( 'ws_legal_updates', 'ws_shortcode_legal_updates' );
function ws_shortcode_legal_updates( $atts ) {

    $atts = shortcode_atts( [
        'jx'     => '',
        'count'  => 0,   // 0 = auto-resolve: 5 per-jurisdiction, 100 sitewide.
    ], $atts, 'ws_legal_updates' );

    // ── Resolve jurisdiction parameter to a post ID ───────────────────────
    //
    // Accepts: numeric post ID, USPS code ("CA"), or post slug ("california").
    // All data reads are delegated to ws_get_legal_updates_data().

    $jx_id = 0;
    if ( ! empty( $atts['jx'] ) ) {
        if ( is_numeric( $atts['jx'] ) ) {
            $jx_id = (int) $atts['jx'];
        } else {
            $jx_id = ws_get_id_by_code( strtoupper( $atts['jx'] ) );
            if ( ! $jx_id ) {
                $posts = get_posts( [
                    'post_type'      => 'jurisdiction',
                    'name'           => sanitize_title( $atts['jx'] ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ] );
                $jx_id = ! empty( $posts ) ? $posts[0] : 0;
            }
        }
    }

    $items = ws_get_legal_updates_data( $jx_id, (int) $atts['count']);

    if ( empty( $items ) ) {
        return '';
    }

    return ws_render_legal_updates( $items );
}


// ── [ws_reference_page] ───────────────────────────────────────────────────────
//
// Renders the reference materials page for a jx-statute, jx-citation, or
// jx-interpretation post. Intended to be placed on a dedicated WP page.
//
// Usage:
//   [ws_reference_page post_id="123"]
//
// post_id must resolve to a jx-statute, jx-citation, or jx-interpretation.
// The "More Info" button in statute/citation shortcodes links here only when
// references exist — so the fallback message below is defensive only.

// ── ws_get_reference_page_url() ───────────────────────────────────────────────
//
// Returns the URL of the dedicated reference materials page, appending
// ?post_id=N for the given parent post. Looks up a published WP page at
// the slug defined by the filter ws_reference_page_slug (default:
// 'reference-materials'). Returns '' if no such page exists.
//
// Relocation evaluated (v3.3.3): function uses apply_filters() for the page
// slug, making it overridable without a constant. No relocation needed --
// this is the correct pattern and the correct file.
//
// Usage: ws_get_reference_page_url( $post_id )

function ws_get_reference_page_url( $post_id ) {
    $slug = apply_filters( 'ws_reference_page_slug', 'reference-materials' );
    $page = get_page_by_path( $slug );
    if ( ! $page ) return '';
    return add_query_arg( 'post_id', (int) $post_id, get_permalink( $page->ID ) );
}


add_shortcode( 'ws_reference_page', 'ws_shortcode_reference_page' );
function ws_shortcode_reference_page( $atts ) {

    $atts    = shortcode_atts( [ 'post_id' => 0 ], $atts, 'ws_reference_page' );
    $post_id = (int) $atts['post_id'];

    // Accept post_id from URL query param when not passed as shortcode attribute.
    // This allows a single page with [ws_reference_page] to serve all records.
    if ( ! $post_id && isset( $_GET['post_id'] ) ) {
        $post_id = (int) $_GET['post_id'];
    }

    if ( ! $post_id ) {
        return '';
    }

    $data = ws_get_reference_page_data( $post_id );

    if ( null === $data ) {
        return '';
    }

    $refs = $data['references'];

    ob_start();
    ?>
    <div class="ws-reference-page">

        <div class="ws-reference-page__back">
            <a href="<?php echo esc_url( $data['parent_url'] ); ?>"
               class="ws-reference-page__back-link">
                &larr; <?php echo esc_html( $data['parent_title'] ); ?>
            </a>
        </div>

        <h2 class="ws-reference-page__heading">Reference Materials</h2>
        <p class="ws-reference-page__subheading">
            External resources related to:
            <strong><?php echo esc_html( $data['parent_title'] ); ?></strong>
        </p>

        <?php if ( empty( $refs ) ) : ?>
            <p class="ws-reference-page__empty">
                No reference materials are currently available for this record.
            </p>
        <?php else : ?>
            <ul class="ws-reference-page__list">
                <?php foreach ( $refs as $ref ) : ?>
                    <li class="ws-reference-page__item">
                        <div class="ws-reference-page__item-header">
                            <a href="<?php echo esc_url( $ref['url'] ); ?>"
                               class="ws-reference-page__item-title"
                               target="_blank"
                               rel="noopener noreferrer">
                                <?php echo esc_html( $ref['title'] ); ?>
                            </a>
                            <?php if ( ! empty( $ref['type'] ) ) : ?>
                                <span class="ws-reference-page__item-type">
                                    <?php echo esc_html( $ref['type'] ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $ref['source_name'] ) ) : ?>
                            <div class="ws-reference-page__item-source">
                                <?php echo esc_html( $ref['source_name'] ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $ref['description'] ) ) : ?>
                            <div class="ws-reference-page__item-description">
                                <?php echo esc_html( $ref['description'] ); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}


// ── [ws_jurisdiction_index] ───────────────────────────────────────────────────
//
// Renders the full filterable jurisdiction index. Intended for placement on a
// standalone WP page (e.g. the site home or a "Find a Jurisdiction" page).
// Not called by the assembler — editors place this manually.

add_shortcode( 'ws_jurisdiction_index', function() {
    $data = ws_get_jurisdiction_index_data();
    return ws_render_jurisdiction_index( $data );
} );


// ── [ws_assist_org_directory] ─────────────────────────────────────────────────
//
// Renders the nationwide assist-org directory. All shortcode attributes are
// optional — omitting them shows all organizations.
//
// Usage:
//   [ws_assist_org_directory]                              — all organizations
//   [ws_assist_org_directory type="nonprofit"]             — filtered by type
//   [ws_assist_org_directory sector="federal" stage="retaliation"]
//   [ws_assist_org_directory cost_model="pro_bono"]
//
// URL query params override shortcode attributes, enabling deep-links from
// jurisdiction pages to arrive with pre-applied filter state:
//
//   ?aorg_type=legal-aid&aorg_stage=retaliation
//
// URL param names:
//   aorg_type     — ws_aorg_type slug
//   aorg_sector   — employment sector slug
//   aorg_stage    — ws_case_stage slug
//   aorg_cost     — cost model value

add_shortcode( 'ws_assist_org_directory', 'ws_shortcode_assist_org_directory' );
function ws_shortcode_assist_org_directory( $atts ) {

    $atts = shortcode_atts( [
        'type'       => '',
        'sector'     => '',
        'stage'      => '',
        'cost_model' => '',
    ], $atts, 'ws_assist_org_directory' );

    // Build filters — URL params take priority over shortcode atts.
    // All values are sanitized before use.
    $filters = [];

    $type = ! empty( $_GET['aorg_type'] )
        ? sanitize_key( wp_unslash( $_GET['aorg_type'] ) )
        : sanitize_key( $atts['type'] );
    if ( $type ) {
        $filters['type'] = $type;
    }

    $sector = ! empty( $_GET['aorg_sector'] )
        ? sanitize_text_field( wp_unslash( $_GET['aorg_sector'] ) )
        : sanitize_text_field( $atts['sector'] );
    if ( $sector ) {
        $filters['sector'] = $sector;
    }

    $stage = ! empty( $_GET['aorg_stage'] )
        ? sanitize_key( wp_unslash( $_GET['aorg_stage'] ) )
        : sanitize_key( $atts['stage'] );
    if ( $stage ) {
        $filters['stage'] = $stage;
    }

    $cost_model = ! empty( $_GET['aorg_cost'] )
        ? sanitize_key( wp_unslash( $_GET['aorg_cost'] ) )
        : sanitize_key( $atts['cost_model'] );
    if ( $cost_model ) {
        $filters['cost_model'] = $cost_model;
    }

    $items = ws_get_nationwide_assist_org_data( $filters );

    return ws_render_directory_page( $items );
}


// ============================================================================
// QUERY LAYER RETURN REFERENCE
//
// Canonical copy lives in shortcodes-jurisdiction.php.
// Refer there for the full return-key reference for all dataset functions.
// ============================================================================

