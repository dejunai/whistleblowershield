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

function ws_get_all_jurisdictions()
{

    $query = new WP_Query(array(
        'post_type' => 'jurisdiction',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    return $query->posts;

}