<?php
/**
 * render-jurisdiction.php — Jurisdiction page assembler.
 *
 * Intercepts the_content for jurisdiction CPT singles and builds the full
 * page from available published datasets.
 *
 * TWO RENDER PATHS:
 *   ws_render_jx_curated()   — Standard path. attach_flag gates datasets.
 *                               Editorial selection, 3-5 records per section.
 *   ws_render_jx_filtered()  — Phase 2 path. attach_flag IGNORED. All
 *                               published records are candidates; taxonomy
 *                               match ($filter_context) gates instead.
 *                               Currently a stub returning ''. Do not remove.
 *
 * The Phase 2 dispatch block in ws_handle_jurisdiction_render() is commented
 * out pending ws_resolve_filter_context() implementation.
 *
 * @package WhistleblowerShield
 * @since   2.1.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 2.1.0   Initial release.
 * 3.0.0   Taxonomy-based scoping replaces relationship fields.
 * 3.8.0   Dispatcher refactor: ws_render_jx_curated() extracted.
 *         ws_render_jx_filtered() stub + Phase 2 dispatch hook point added.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Checks whether a query layer dataset result represents a published record.
 *
 * Accepts the return value of any ws_get_jx_*() query layer function.
 * Query layer functions return arrays in two shapes:
 *
 *   Standard:  [ 'id' => int, 'status' => string, ... ]
 *   Statutes:  [ [ 'id' => int, 'status' => string, ... ], ... ]  (array-of-arrays)
 *
 * For the array-of-arrays format (ws_get_jx_statutes), the first entry is
 * checked — the state record always appears before the merged federal record.
 *
 * @param  array|WP_Post|false $data  Return value from a query layer function.
 * @return bool  True only when the record has post_status === 'publish'.
 */
function ws_is_published( $data ) {

    if ( ! $data ) {
        return false;
    }

    // Array-of-arrays: ws_get_jx_statute_data() returns state + federal merged.
    // Require at least one local (non-federal) published entry — federal-only
    // results mean the jurisdiction has no local statutes yet and the section
    // should not render.
    if ( isset( $data[0] ) && is_array( $data[0] ) ) {
        foreach ( $data as $entry ) {
            if ( empty( $entry['is_fed'] ) && ! empty( $entry['status'] ) && $entry['status'] === 'publish' ) {
                return true;
            }
        }
        return false;
    }

    // Standard dataset array returned by query layer functions.
    if ( is_array( $data ) ) {
        return ! empty( $data['status'] ) && $data['status'] === 'publish';
    }

    // Fallback: legacy WP_Post object (not expected in normal flow).
    return isset( $data->post_status ) && $data->post_status === 'publish';
}



/*
---------------------------------------------------------
Dispatcher
---------------------------------------------------------
*/

add_filter( 'the_content', 'ws_handle_jurisdiction_render' );

/**
 * Thin dispatcher — guards, resolves $jx_term_id, routes to the correct
 * render path. Contains no render logic of its own.
 */
function ws_handle_jurisdiction_render( $content ) {
    global $post;

    // Guard against infinite loops from nested do_shortcode calls.
    static $is_rendering = false;

    // Only run on the main query loop — not widgets, sidebars, or REST calls.
    if ( ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( ! $post || $post->post_type !== 'jurisdiction' || $is_rendering ) {
        return $content;
    }

    $is_rendering = true;

    // Resolve ws_jurisdiction term ID once — both render paths need it.
    $jx_term_id = ws_get_jx_term_id( $post->ID );

    // ── Phase 2 dispatch ──────────────────────────────────────────────────
    //
    // When filter GET params are present, route to ws_render_jx_filtered().
    // Dormant until ws_resolve_filter_context() is implemented in Phase 2.
    // Centralized param names live in ws-filter-config.php (also Phase 2).
    //
    // $filter_context = ws_resolve_filter_context();
    // if ( $filter_context ) {
    //     $is_rendering = false;
    //     return ws_render_jx_filtered( $post, $jx_term_id, $filter_context );
    // }

    $output       = ws_render_jx_curated( $post, $jx_term_id );
    $is_rendering = false;
    return $output;
}


/*
---------------------------------------------------------
Curated Render Path  (default)
---------------------------------------------------------
*/

/**
 * Assembles the curated jurisdiction page.
 *
 * Renders sections gated by attach_flag — only records an editor has
 * explicitly flagged appear here. Called by ws_handle_jurisdiction_render()
 * when no filter context is active.
 *
 * Render order: header → disclaimer → summary → statutes → citations →
 *               interpretations → limitations → legal updates → fallback
 *
 * @param  WP_Post  $post        The jurisdiction post object.
 * @param  int|null $jx_term_id  The ws_jurisdiction term ID for this post.
 * @return string                Assembled HTML for the jurisdiction page.
 */
function ws_render_jx_curated( $post, $jx_term_id ) {

    $output      = do_shortcode( '[ws_jx_header]' );
    $output     .= do_shortcode( '[ws_not_legal_advice_disclaimer_notice]' );
    $has_content = false;

    // Summary.
    if ( $jx_term_id ) {
        if ( ws_is_published( ws_get_jx_summary_data( $jx_term_id ) ) ) {
            $output      .= do_shortcode( '[ws_jx_summary]' );
            $has_content  = true;
        }
    }

    // Statutes — users see what protects them before seeing how courts
    // have interpreted those protections.
    if ( $jx_term_id ) {
        if ( ws_is_published( ws_get_jx_statute_data( $jx_term_id ) ) ) {
            $output      .= '<div id="ws-statutes">' . do_shortcode( '[ws_jx_statutes]' ) . '</div>';
            $has_content  = true;
        }
    }

    // Citations — id="ws-citations" is the anchor target for the reference page back link.
    $citations = do_shortcode( '[ws_jx_citation]' );
    if ( $citations ) {
        $output      .= '<div id="ws-citations">' . $citations . '</div>';
        $has_content  = true;
    }

    // Interpretations — after citations, before limitations.
    // id="ws-interpretations" is the anchor target for the reference page back link.
    $interpretations = do_shortcode( '[ws_jx_interpretation]' );
    if ( $interpretations ) {
        $output      .= '<div id="ws-interpretations">' . $interpretations . '</div>';
        $has_content  = true;
    }

    // Limitations.
    $limitations = do_shortcode( '[ws_jx_limitations]' );
    if ( $limitations ) {
        $output      .= $limitations;
        $has_content  = true;
    }

    // Legal updates — shortcode returns empty if none exist.
    // Pass the USPS code (e.g. 'ca'), not the WP post slug (e.g. 'california').
    // The [ws_legal_updates] shortcode resolves jx via taxonomy slug, not post slug.
    $jx_info = ws_get_jurisdiction_data( $post->ID );
    $jx_code = $jx_info ? strtolower( $jx_info['code'] ) : '';
    $legal_updates = $jx_code
        ? do_shortcode( '[ws_legal_updates jx="' . esc_attr( $jx_code ) . '" count="5"]' )
        : '';
    if ( $legal_updates ) {
        $output      .= $legal_updates;
        $has_content  = true;
    }

    // Fallback — only triggers when no content sections were assembled.
    if ( ! $has_content ) {
        $output .= '<div class="ws-section--placeholder">Content for this jurisdiction is currently being prepared.</div>';
    }

    return $output;
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_jx_filtered()
//
// !! PHASE 2 PRIORITY — DO NOT REMOVE !!
//
// Filtered render path — parallel sibling to ws_render_jx_curated().
// Invoked by ws_handle_jurisdiction_render() when $_GET contains taxonomy
// filter params resolved by ws_resolve_filter_context() (Phase 2).
//
// Contrast with ws_render_jx_curated():
//   Curated:  attach_flag = true gates all datasets — editorial curation.
//   Filtered: attach_flag ignored — all published records are candidates;
//             $filter_context constrains results via taxonomy cascade instead.
//
// Implementation notes (Phase 2):
//   - $filter_context is an array of resolved taxonomy term IDs built from
//     the plain-English question panel ($_GET params on page load).
//     Example: [ 'ws_industry' => [12, 47], 'ws_disclosure_type' => [8] ]
//   - Output includes statutes, citations, interpretations, limitations, and
//     ws-assist-org records matched by the filter context. ws-assist-org and
//     ws-agency records are not on the curated page — they appear here only.
//   - Render order mirrors the curated path; assist-orgs append last.
//   - PHP-only: standard GET form. No AJAX required for core functionality;
//     JS may be layered on for UX polish.
//   - Filtered URLs are bookmarkable and shareable
//     (e.g. /california/?industry=12&disclosure=8).
//
// @param  WP_Post  $post           The jurisdiction post object.
// @param  int|null $jx_term_id     The ws_jurisdiction term ID for this post.
// @param  array    $filter_context Taxonomy term IDs resolved from $_GET params.
// @return string                   HTML for the filtered jurisdiction page.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_jx_filtered( $post, $jx_term_id, $filter_context ) {
    // Phase 2: Taxonomy cascade filter render — see block comment above.
    return '';
}
