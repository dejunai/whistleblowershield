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
 *      ├── jx_summary
 *      ├── jx_procedures
 *      ├── jx_statutes
 *      └── jx_resources
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
 *      plugin checks for published datasets
 *            ↓
 *      plugin renders sections using shortcodes
 *
 *
 * CONDITIONAL RENDERING
 * ---------------------
 *
 * Sections are only displayed when their corresponding dataset:
 *
 *      1) exists
 *      2) is published
 *
 * Draft or unpublished datasets will never appear on the public site.
 *
 *
 * WORKFLOW BENEFIT
 * ----------------
 *
 * Editors do NOT need to manually insert shortcodes.
 *
 * Creating and publishing a dataset automatically updates the
 * jurisdiction page.
 *
 *
 * FILE RESPONSIBILITIES
 * ---------------------
 *
 * This file ONLY:
 *
 *      • detects jurisdiction pages
 *      • retrieves dataset relationships
 *      • verifies publish status
 *      • triggers shortcode rendering
 *
 * It does NOT:
 *
 *      • perform database queries
 *      • contain HTML templates
 *
 * Queries are handled in:
 *
 *      /includes/queries/query-jurisdiction.php
 *
 * Rendering is handled in:
 *
 *      /includes/shortcodes/shortcodes-jurisdiction.php
 *
 *
 * VERSION
 * -------
 * 2.1.0  Jurisdiction auto-render architecture introduced
 */
/**
 * File: render-jurisdiction.php
 * Updated: 2.1.2
 * Added: is_main_query() and in_the_loop() safeguards.
 */
 
if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
Helper: Verify Published State
---------------------------------------------------------
*/

function ws_is_published($post)
{
    return ($post && $post->post_status === 'publish');
}


/*
---------------------------------------------------------
Main Jurisdiction Renderer
---------------------------------------------------------
*/

add_filter( 'the_content', 'ws_handle_jurisdiction_render' );

function ws_handle_jurisdiction_render( $content ) {
    global $post;
    
    // Guard against infinite loops
    static $is_rendering = false;

    /**
     * Safeguard: Only assemble for the main page content.
     * This prevents the plugin from running inside widgets, sidebars, or footers.
     */
    if ( ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( $post->post_type !== 'jurisdiction' || $is_rendering ) {
        return $content;
    }

    $is_rendering = true; 

    // Build the page structure
    $output = do_shortcode('[ws_jx_header]');

    $sections = [
        'summary'    => '[ws_jx_summary]',
        'procedures' => '[ws_jx_procedures]',
        'statutes'   => '[ws_jx_statutes]',
        'resources'  => '[ws_jx_resources]'
    ];

    foreach ( $sections as $key => $shortcode ) {
        $get_func = "ws_get_jx_{$key}";
        $related  = $get_func($post->ID);

        // check if addendum exists, is published, and has content
        if ( $related && $related->post_status === 'publish' ) {
            if ( ! empty( trim( $related->post_content ) ) ) {
                $output .= do_shortcode($shortcode);
            }
        }
    }

    $is_rendering = false; 
    return $output;
}
/**
 * [ws_jurisdiction_index] 
 * Cleaned up version of your original alphabetical grid.
 */
add_shortcode('ws_jurisdiction_index', function() {
    $jurisdictions = ws_get_all_jurisdictions();
    if (empty($jurisdictions)) return '';

    $output = '<div class="ws-jx-index-grid">';
    foreach ($jurisdictions as $jx) {
        $code = get_field('jx_code', $jx->ID);
        $type = get_field('ws_jurisdiction_type', $jx->ID); // State, Territory, etc.
        $url  = get_permalink($jx->ID);
        
        // Call the Renderer instead of writing HTML here
        $output .= ws_render_jx_index_card($jx->post_title, $code, $url, $type);
    }
    $output .= '</div>';
    
    return $output;
});

/**
 * [ws_jx_review_status]
 * Simplified to pull from the Summary addendum.
 */
add_shortcode('ws_jx_review_status', function() {
    global $post;
    $summary = ws_get_jx_summary($post->ID);
    
    if (!$summary) return '';

    $date = get_field('ws_summary_last_review', $summary->ID);
    return ws_render_jx_review_status($date);
});