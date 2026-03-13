<?php
/**
 * File: query-jurisdiction.php
 *
 * Jurisdiction Query Layer
 *
 * PURPOSE
 * -------
 * Provides centralized functions for retrieving jurisdiction
 * records and their associated datasets.
 *
 * This file acts as the primary data access layer for the
 * WhistleblowerShield plugin.
 *
 * By consolidating queries here we avoid repeating WP_Query
 * logic throughout the plugin and maintain consistent behavior
 * across shortcodes and templates.
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
 * Each dataset is connected to the jurisdiction record using
 * ACF relationship fields defined in acf-jurisdiction.php.
 *
 * INTERNAL IDENTIFIER
 * -------------------
 * jx_code
 *
 * Example:
 *      CA
 *      NY
 *      TX
 *      US
 *
 * VERSION
 * -------
 * 2.1.0  Refactored for ws-core architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
---------------------------------------------------------
Get Jurisdiction by Code
---------------------------------------------------------
*/

function ws_get_jurisdiction_by_code($jx_code)
{

    $query = new WP_Query(array(
        'post_type' => 'jurisdiction',
        'meta_query' => array(
            array(
                'key' => 'jx_code',
                'value' => strtoupper($jx_code),
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    if ($query->have_posts()) {
        return $query->posts[0];
    }

    return null;
}

/**
 * Find a Jurisdiction ID by its Postal Code (e.g., 'CA', 'TX')
 */
function ws_get_id_by_code($jx_code) {
    if (empty($jx_code)) return false;
    
    $jx_code = strtoupper(sanitize_text_field($jx_code));
    $cache_key = 'ws_id_for_' . $jx_code;
    $post_id = get_transient($cache_key);

    if (false === $post_id) {
        $query = new WP_Query([
            'post_type'      => 'jurisdiction',
            'meta_key'       => 'jx_code',
            'meta_value'     => $jx_code,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        
        $post_id = !empty($query->posts) ? $query->posts[0] : 0;
        set_transient($cache_key, $post_id, DAY_IN_SECONDS);
    }

    return $post_id ?: false;
}

/**
 * Master Data Fetcher
 */
function ws_get_jurisdiction_data($input = null) {
    if (!$input) {
        global $post;
        $post_id = $post->ID ?? 0;
    } else {
        $post_id = is_numeric($input) ? $input : ws_get_id_by_code($input);
    }

    if (!$post_id || get_post_type($post_id) !== 'jurisdiction') return false;

    return [
        'id'   => $post_id,
        'name' => get_the_title($post_id),
        'type' => get_field('ws_jurisdiction_type', $post_id),
        'code' => get_field('jx_code', $post_id),
        'flag' => [
            'url'        => get_field('ws_jx_flag_image', $post_id),
            'source_url' => get_field('ws_jx_flag_source_url', $post_id),
            'attr_str'   => get_field('ws_jx_flag_attribution', $post_id),
            'license'    => get_field('ws_jx_flag_license', $post_id),
        ],
        'gov' => [
            'portal_url'       => get_field('ws_gov_portal_url', $post_id),
            'portal_label'     => get_field('ws_gov_portal_label', $post_id),
            'head_gov_url'     => get_field('ws_head_of_government_url', $post_id),
            'head_gov_label'   => get_field('ws_head_of_government_label', $post_id),
            'legal_auth_url'   => get_field('ws_legal_authority_url', $post_id),
            'legal_auth_label' => get_field('ws_legal_authority_label', $post_id),
            'legislature_url'  => get_field('ws_legislature_url', $post_id),
            'legislature_label'=> get_field('ws_legislature_label', $post_id), // UPDATED KEY
        ]
    ];
}
/*
---------------------------------------------------------
Get Summary Dataset
---------------------------------------------------------
*/

function ws_get_jx_summary($jurisdiction_id)
{

    return get_field('ws_related_summary', $jurisdiction_id);

}


/*
---------------------------------------------------------
Get Procedures Dataset
---------------------------------------------------------
*/

function ws_get_jx_procedures($jurisdiction_id)
{

    return get_field('ws_related_procedures', $jurisdiction_id);

}


/*
---------------------------------------------------------
Get Statutes Dataset
---------------------------------------------------------
*/

function ws_get_jx_statutes($jurisdiction_id)
{

    return get_field('ws_related_statutes', $jurisdiction_id);

}


/*
---------------------------------------------------------
Get Resources Dataset
---------------------------------------------------------
*/

function ws_get_jx_resources($jurisdiction_id)
{

    return get_field('ws_related_resources', $jurisdiction_id);

}


/*
---------------------------------------------------------
Get All Jurisdictions
---------------------------------------------------------
*/

function ws_get_all_jurisdictions() {
    $cache_key = 'ws_all_jurisdictions_cache';
    $jurisdictions = get_transient($cache_key);

    if (false === $jurisdictions) {
        $query = new WP_Query([
            'post_type'      => 'jurisdiction',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true, // Performance boost: skip counting total rows
        ]);
        $jurisdictions = $query->posts;
        
        // Cache for 12 hours
        set_transient($cache_key, $jurisdictions, 12 * HOUR_IN_SECONDS);
    }

    return $jurisdictions;
}

// Add this to clear cache when a Jurisdiction is updated
add_action('save_post_jurisdiction', function() {
    delete_transient('ws_all_jurisdictions_cache');
});
/**
 * Retrieve a list of all jurisdictions for the index.
 * Returns an array of data objects.
 */
/**
 * Retrieve a list of all jurisdictions and their counts by type.
 */
function ws_get_jurisdiction_index_data() {
    $cache_key = 'ws_jx_index_cache';
    $cached = get_transient($cache_key);

    if (false === $cached) {
        $query = new WP_Query([
            'post_type'      => 'jurisdiction',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ]);

        $index_items = [];
        $counts = [
            'all'       => 0,
            'state'     => 0,
            'territory' => 0,
            'district'  => 0,
            'federal'   => 0
        ];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $type = get_field('ws_jurisdiction_type', $post->ID) ?: 'state';
                $code = get_field('jx_code', $post->ID);
                
                $index_items[] = [
                    'name' => get_the_title($post->ID),
                    'code' => $code,
                    'type' => $type,
                    'url'  => get_permalink($post->ID)
                ];

                // Increment Counts
                $counts['all']++;
                if (isset($counts[$type])) {
                    $counts[$type]++;
                }
            }
        }

        $cached = [
            'items'  => $index_items,
            'counts' => $counts
        ];

        set_transient($cache_key, $cached, DAY_IN_SECONDS);
    }

    return $cached;
}