<?php
/**
 * acf-jx-resources.php
 *
 * Registers ACF Pro fields for the `jx-resources` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured fields for Jurisdiction Resources records.
 * Resources include government reporting portals, inspector general
 * offices, ethics commissions, and similar external links.
 *
 * BACK-REFERENCE FIELD
 * --------------------
 * ws_jurisdiction links this resources record back to its parent
 * Jurisdiction record. This field is required by
 * ws_sync_jurisdiction_relationships() in admin-relationships.php
 * to maintain two-way relationship consistency.
 *
 * When this record is saved, the sync function reads ws_jurisdiction
 * and writes this post's ID into ws_related_resources on the parent
 * Jurisdiction record automatically.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. CPT slug corrected
 *         to jx-resources (hyphenated). Added ws_jurisdiction
 *         back-reference field to support two-way relationship
 *         sync via admin-relationships.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jx_resources' );

function ws_register_acf_jx_resources() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_jx_resources',
        'title'                 => 'Resource Links',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Location: jx-resources CPT only (hyphenated slug)
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-resources',
        ] ] ],

        'fields' => [

            // ── Back-Reference: Parent Jurisdiction ───────────────────────
            // Required for two-way relationship sync.
            // See ws_sync_jurisdiction_relationships() in
            // admin-relationships.php.

            [
                'key'           => 'field_ws_resources_jurisdiction',
                'label'         => 'Parent Jurisdiction',
                'name'          => 'ws_jurisdiction',
                'type'          => 'post_object',
                'instructions'  => 'Select the Jurisdiction these resources belong to. Required for relationship sync.',
                'required'      => 1,
                'post_type'     => [ 'jurisdiction' ],
                'allow_null'    => 0,
                'multiple'      => 0,
                'return_format' => 'id',
                'ui'            => 1,
            ],

            // ── Agency Details ────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_agency',
                'label'        => 'Agency Name',
                'name'         => 'ws_resource_agency',
                'type'         => 'text',
                'instructions' => 'Full name of the agency or organization — e.g., California State Auditor.',
            ],

            [
                'key'          => 'field_ws_resource_url',
                'label'        => 'Agency URL',
                'name'         => 'ws_resource_url',
                'type'         => 'url',
                'instructions' => 'Official website for this agency or resource.',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_resources
