<?php
/**
 * File: cpt-jurisdiction.php
 *
 * Registers the Jurisdiction Core CPT.
 *
 * This CPT represents the canonical record for each U.S. jurisdiction
 * supported by the WhistleblowerShield legal archive.
 *
 * Supported jurisdictions:
 * - 50 U.S. states
 * - Federal government (US)
 * - District of Columbia (DC)
 * - U.S. Territories:
 *     Puerto Rico (PR)
 *     Guam (GU)
 *     U.S. Virgin Islands (VI)
 *     American Samoa (AS)
 *     Northern Mariana Islands (MP)
 *
 * Each jurisdiction record acts as the parent reference for:
 *
 *   jx_summary
 *   jx_statutes
 *   jx_procedures
 *   jx_resources
 *   legal_update
 *
 * Internal Identifier:
 *   jx_code (2-letter USPS style code)
 *
 * Examples:
 *   CA = California
 *   TX = Texas
 *   NY = New York
 *   US = Federal
 *
 * Version: 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function ws_register_cpt_jurisdiction()
{

    $labels = array(
        'name'               => 'Jurisdictions',
        'singular_name'      => 'Jurisdiction',
        'menu_name'          => 'Jurisdictions',
        'name_admin_bar'     => 'Jurisdiction',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Jurisdiction',
        'new_item'           => 'New Jurisdiction',
        'edit_item'          => 'Edit Jurisdiction',
        'view_item'          => 'View Jurisdiction',
        'all_items'          => 'All Jurisdictions',
        'search_items'       => 'Search Jurisdictions',
        'not_found'          => 'No jurisdictions found',
        'not_found_in_trash' => 'No jurisdictions found in trash'
    );

	$args = array(

		'labels' => $labels,

		'public' => true,

		'publicly_queryable' => true,

		'exclude_from_search' => false,

		'has_archive' => false,

		'show_in_rest' => true,

		'menu_icon' => 'dashicons-location-alt',

		'supports' => array(
			'title',
			'editor',
			'revisions'
		),

		'rewrite' => array(
			'slug' => 'jurisdiction'
		),

		'capability_type' => 'post',

		'show_in_menu' => true,

		'hierarchical' => false,

		'menu_position' => 25
	);

    register_post_type('jurisdiction', $args);

}

add_action('init', 'ws_register_cpt_jurisdiction');