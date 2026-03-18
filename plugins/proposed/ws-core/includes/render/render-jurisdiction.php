<?php
/**
 * File: render-jurisdiction.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Automatically assembles the public-facing Jurisdiction page by
 * conditionally rendering available datasets associated with a
 * jurisdiction record.
 *
 * This file intercepts WordPress content rendering for the
 * "jurisdiction" Custom Post Type and dynamically builds the page
 * structure using shortcodes.
 *
 * The goal is to eliminate the need to manually insert shortcodes
 * into jurisdiction posts. Instead, sections appear automatically
 * when their associated datasets are published.
 *
 *
 * ARCHITECTURE
 * ------------
 *
 * jurisdiction (public CPT)
 *      ├── jx-summary
 *      ├── jx-statute
 *      ├── jx-citation
 *      └── 
 *
 * Each dataset is stored as a separate Custom Post Type and linked
 * to the jurisdiction using ACF relationship fields defined in:
 *
 *      /includes/acf/acf-jurisdiction.php
 *
 *
 * RENDERING MODEL
 * ---------------
 *
 * When a visitor loads a jurisdiction page:
 *
 *      WordPress loads post content
 *            ↓
 *      this file intercepts via "the_content" filter
 *            ↓
 *      plugin checks for published datasets via query layer
 *            ↓
 *      plugin renders sections using shortcodes
 *
 *
 * CONDITIONAL RENDERING
 * ---------------------
 *
 * Sections are only displayed when their corresponding dataset
 * exists and is published. Draft or unpublished datasets will
 * never appear on the public site.
 *
 * NOTE: Addendum CPTs (jx-summary, s, etc.) store
 * their content in ACF fields, not post_content. The published
 * status of the addendum post is the correct gate — the section
 * shortcode is responsible for reading and rendering field content.
 *
 *
 * WORKFLOW BENEFIT
 * ----------------
 *
 * Editors do NOT need to manually insert shortcodes.
 * Creating and publishing a dataset automatically adds that
 * section to the jurisdiction page.
 *
 *
 * FILE RESPONSIBILITIES
 * ---------------------
 *
 * This file ONLY:
 *      • detects jurisdiction pages
 *      • retrieves dataset relationships via query layer
 *      • verifies published status
 *      • triggers shortcode rendering
 *
 * It does NOT:
 *      • perform database queries (handled by query-jurisdiction.php)
 *      • contain HTML templates (handled by section-renderer.php)
 *      • register shortcodes (handled by shortcodes-jurisdiction.php)
 *
 *
 * VERSION
 * -------
 * 2.1.0  Auto-render architecture introduced
 * 2.1.2  Added is_main_query() and in_the_loop() safeguards
 * 2.1.3  Removed post_content gate — addendum content lives in ACF
 *         fields, not post_content. Published status is the correct gate.
 * 2.3.0  Added [ws_jx_case_law] and [ws_jx_limitations] to assembler.
 *         Case law renders after summary, limitations renders after
 *         case law. Both are conditional on content availability.
 * 2.3.1  ws_is_published() updated to handle query layer array format.
 *         All dataset functions return arrays with a 'status' key.
 *         ws_get_jx_statutes() returns an array-of-arrays (state + federal
 *         merge) — first entry's 'status' key is used for the gate check.
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

    // Array-of-arrays: ws_get_jx_statutes() returns multiple records
    // (state + federal merge). Check the first entry's status key.
    if ( isset( $data[0] ) && is_array( $data[0] ) ) {
        return ! empty( $data[0]['status'] ) && $data[0]['status'] === 'publish';
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
Main Jurisdiction Renderer
---------------------------------------------------------
*/

add_filter( 'the_content', 'ws_handle_jurisdiction_render' );

function ws_handle_jurisdiction_render( $content ) {
    global $post;

    // Guard against infinite loops from nested do_shortcode calls
    static $is_rendering = false;

    // Only run on the main query loop — not widgets, sidebars, or REST calls
    if ( ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( ! $post || $post->post_type !== 'jurisdiction' || $is_rendering ) {
        return $content;
    }

    $is_rendering = true;

    // Always render the header
    $output = do_shortcode( '[ws_jx_header]' );

    // Render disclaimer notice below header, before summary content
    $output .= do_shortcode( '[ws_nla_disclaimer_notice]' );

    // Each section renders only if its addendum post exists and is published.
    // Post_content is NOT checked — content lives in ACF fields on addendum CPTs.
    //
    // Resolve ws_jurisdiction term ID once; used for all dataset gate checks.
    $jx_term_id  = ws_get_jx_term_id( $post->ID );
    $has_content = false; // Phase 10: tracks whether any content section was added.

    // Render summary.
    if ( $jx_term_id ) {
        $related_summary = ws_get_jx_summary_data( $jx_term_id );
        if ( ws_is_published( $related_summary ) ) {
            $output .= do_shortcode( '[ws_jx_summary]' );
            $has_content = true;
        }
    }

    // Render statutes — shown before case law so users see what protects
    // them before seeing how courts have interpreted those protections.
    if ( $jx_term_id ) {
        $related_statutes = ws_get_jx_statute_data( $jx_term_id );
        if ( ws_is_published( $related_statutes ) ) {
            $output .= do_shortcode( '[ws_jx_statutes]' );
            $has_content = true;
        }
    }

    // Render case law citations — [ws_jx_case_law] returns empty if none attached.
    $case_law = do_shortcode( '[ws_jx_case_law]' );
    if ( $case_law ) {
        $output      .= $case_law;
        $has_content  = true;
    }

    // Render limitations — [ws_jx_limitations] returns empty if field is empty.
    $limitations = do_shortcode( '[ws_jx_limitations]' );
    if ( $limitations ) {
        $output      .= $limitations;
        $has_content  = true;
    }

    // Legal updates — always attempt; shortcode returns empty if none exist.
    $legal_updates = do_shortcode( '[ws_legal_updates jurisdiction="' . esc_attr( $post->post_name ) . '" count="5"]' );
    if ( $legal_updates ) {
        $output      .= $legal_updates;
        $has_content  = true;
    }

    // Phase 10 — Fallback: if no content sections were assembled, render a
    // placeholder notice. Only triggered when the entire page is empty — no
    // per-section placeholders.
    if ( ! $has_content ) {
        $output .= '<div class="ws-section--placeholder">Content for this jurisdiction is currently being prepared.</div>';
    }

    $is_rendering = false;
    return $output;
}
