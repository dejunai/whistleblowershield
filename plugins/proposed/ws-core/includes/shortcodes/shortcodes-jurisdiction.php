<?php
/**
 * File: shortcodes-jurisdiction.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Registers the shortcodes responsible for rendering Jurisdiction
 * page components.
 *
 * These shortcodes are not intended for manual insertion by editors.
 * Instead they are executed automatically by:
 *
 *      render-jurisdiction.php
 *
 * Each shortcode retrieves the relevant dataset using the query layer
 * and passes the result to the section renderer.
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
 * Query Layer:
 *
 *      includes/queries/query-jurisdiction.php
 *
 * Section Renderer:
 *
 *      includes/render/section-renderer.php
 *
 *
 * DESIGN PRINCIPLE
 * ----------------
 *
 * Shortcodes should remain minimal and readable.
 *
 * Responsibilities of this file:
 *
 *      • retrieve datasets
 *      • prepare content
 *      • call render helpers
 *
 * Responsibilities NOT included:
 *
 *      • HTML layout (handled by section renderer)
 *      • database queries (handled by query layer)
 *
 *
 * VERSION
 * -------
 * 2.1.0  Refactored shortcode layer
 */
/**
 * File: shortcodes-jurisdiction.php
 * Updated: 2.1.3
 */
if (!defined('ABSPATH')) {
    exit;
}


/*
---------------------------------------------------------
Jurisdiction Header
---------------------------------------------------------
/**
 * [ws_jx_header]
 * Main Jurisdiction Header Shortcode
 * [ws_jx_header jx="CA"]
 */
add_shortcode('ws_jx_header', function($atts) {
    $atts = shortcode_atts(['jx' => ''], $atts);
    $jx_data = ws_get_jurisdiction_data($atts['jx']);
    
    if (!$jx_data) return '';

    // Determine Box Label based on Type
    $labels = [
        'state'     => 'State Leadership Offices',
        'territory' => 'Territory Leadership Offices',
        'district'  => 'District Leadership Offices',
        'federal'   => 'Federal Offices'
    ];
    $box_label = $labels[$jx_data['type']] ?? 'Official Offices';

    // Build the render array
    $render_data = [
        'jx_name'   => $jx_data['name'],
        'flag_data' => [
            'jx_name'    => $jx_data['name'],
            'url'        => $jx_data['flag']['url'],
            'source_url' => $jx_data['flag']['source_url'],
            'attr_str'   => $jx_data['flag']['attr_str'],
            'license'    => $jx_data['flag']['license'],
        ],
        'gov_data' => [
            'box_label' => $box_label,
            'links'     => [
                ['url' => $jx_data['gov']['portal_url'],       'label' => $jx_data['gov']['portal_label'] ?: 'Government Portal'],
                ['url' => $jx_data['gov']['head_gov_url'],     'label' => $jx_data['gov']['head_gov_label'] ?: 'Head of Government'],
                ['url' => $jx_data['gov']['legal_auth_url'],   'label' => $jx_data['gov']['legal_auth_label'] ?: 'Attorney General'],
                ['url' => $jx_data['gov']['legislature_url'],  'label' => $jx_data['gov']['legislature_label'] ?: 'Legislature'],
            ]
        ]
    ];

    return ws_render_jx_header($render_data);
});
/*
---------------------------------------------------------
Summary
---------------------------------------------------------
*
* [ws_jx_summary]
* Renders the content of the linked Summary CPT.
*/
add_shortcode('ws_jx_summary', function() {
    global $post;
    $summary_post = ws_get_jx_summary($post->ID);

    if (!$summary_post) return '';

    // Process content (runs other shortcodes/filters inside the summary)
    $content = apply_filters('the_content', $summary_post->post_content);
    
    // Get the review status via the existing shortcode logic
    $review_html = do_shortcode('[ws_jx_review_status]');

    return ws_render_jx_summary_section($content, $review_html);
});



/*
---------------------------------------------------------
Procedures
---------------------------------------------------------
*/

add_shortcode('ws_jx_procedures', 'ws_shortcode_jx_procedures');

function ws_shortcode_jx_procedures()
{

    global $post;

    if (!$post) {
        return '';
    }

    $procedures = ws_get_jx_procedures($post->ID);

    if (!$procedures) {
        return '';
    }

    $content = apply_filters('the_content', $procedures->post_content);

    return ws_render_section(
        'Procedures',
        $content
    );

}



/*
---------------------------------------------------------
Statutes
---------------------------------------------------------
*/

add_shortcode('ws_jx_statutes', 'ws_shortcode_jx_statutes');

function ws_shortcode_jx_statutes()
{

    global $post;

    if (!$post) {
        return '';
    }

    $statutes = ws_get_jx_statutes($post->ID);

    if (!$statutes) {
        return '';
    }

    $content = apply_filters('the_content', $statutes->post_content);

    return ws_render_section(
        'Relevant Statutes',
        $content
    );

}



/*
---------------------------------------------------------
Resources
---------------------------------------------------------
*/

add_shortcode('ws_jx_resources', 'ws_shortcode_jx_resources');

function ws_shortcode_jx_resources()
{

    global $post;

    if (!$post) {
        return '';
    }

    $resources = ws_get_jx_resources($post->ID);

    if (!$resources) {
        return '';
    }

    $content = apply_filters('the_content', $resources->post_content);

    return ws_render_section(
        'Resources',
        $content
    );

}

/**
 * [ws_jurisdiction_index]
 * Renders the alphabetical grid with JS filtering tabs.
 */
add_shortcode('ws_jurisdiction_index', function() {
    $data = ws_get_jurisdiction_index_data();
    return ws_render_jurisdiction_index($data);
});

/**
 * [ws_jx_review_status]
 * Displays the "Last Reviewed" date from the Summary addendum.
 */
add_shortcode('ws_jx_review_status', function() {
    global $post;
    $summary = ws_get_jx_summary($post->ID);
    
    if (!$summary) return '';

    $last_review = get_field('ws_summary_last_review', $summary->ID);
    
    if (!$last_review) return '';

    return '<div class="ws-review-status">Last Legal Review: ' . esc_html($last_review) . '</div>';
});
/**
 * Standalone Flag Shortcode
 */
add_shortcode('ws_jx_flag', function($atts) {
    global $post;
    if (!$post) return '';

    $data = [
        'jx_name'    => get_the_title($post->ID),
        'url'        => get_field('ws_jx_flag_image', $post->ID),
        'source_url' => get_field('ws_jx_flag_source_url', $post->ID),
        'attr_str'   => get_field('ws_jx_flag_attribution', $post->ID),
        'license'    => get_field('ws_jx_flag_license', $post->ID),
    ];

    return ws_render_jx_flag($data);
});