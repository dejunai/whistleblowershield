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
 *      ├── jx-procedures
 *      ├── jx-statutes
 *      └── jx-resources
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
 * NOTE: Addendum CPTs (jx-summary, jx-procedures, etc.) store
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
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/*
---------------------------------------------------------
Helper: Verify Published State
---------------------------------------------------------
*/

function ws_is_published( $post ) {
    return ( $post && $post->post_status === 'publish' );
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
    // Post_content is NOT checked — content lives in ACF fields.
    $sections = [
        'summary'    => '[ws_jx_summary]',
        'procedures' => '[ws_jx_procedures]',
        'statutes'   => '[ws_jx_statutes]',
        'resources'  => '[ws_jx_resources]',
    ];

    foreach ( $sections as $key => $shortcode ) {
        $get_func = 'ws_get_jx_' . $key;
        $related  = $get_func( $post->ID );

        if ( ws_is_published( $related ) ) {
            $output .= do_shortcode( $shortcode );
        }
    }

    // Legal updates — always attempt; shortcode returns empty if none exist
    $output .= do_shortcode( '[ws_legal_updates jurisdiction="' . esc_attr( $post->post_name ) . '" count="5"]' );

    $is_rendering = false;
    return $output;
}
