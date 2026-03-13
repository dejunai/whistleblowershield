<?php
/**
 * File: acf-jurisdiction.php
 *
 * Registers Advanced Custom Fields (ACF) used by the Jurisdiction
 * Core Custom Post Type.
 *
 * PURPOSE
 * -------
 * This field group defines the metadata used to render the jurisdiction
 * header and maintain canonical identifiers for each supported U.S.
 * jurisdiction.
 *
 * Each Jurisdiction record represents one of the supported jurisdictions:
 *
 *   • 50 U.S. States
 *   • Federal Government (US)
 *   • District of Columbia (DC)
 *   • U.S. Territories
 *       - Puerto Rico (PR)
 *       - Guam (GU)
 *       - U.S. Virgin Islands (VI)
 *       - American Samoa (AS)
 *       - Northern Mariana Islands (MP)
 *
 * DATA CATEGORIES
 * ---------------
 * 1. Jurisdiction Identity
 *      jx_code
 *      jurisdiction name/type
 *
 * 2. Flag Metadata
 *      flag image
 *      attribution
 *      source URL
 *      license
 *
 * 3. Government Links
 *      official government portal
 *      head of government
 *      legal authority
 *      legislature
 *
 * These links allow flexible labeling across jurisdictions.
 *
 * Examples:
 *      Governor / Mayor
 *      Attorney General / Secretary of Justice
 *
 * 4. Dataset Relationships
 *      Links jurisdiction to its associated legal datasets.
 *
 * INTERNAL IDENTIFIER
 * -------------------
 * jx_code is the canonical machine identifier used across the plugin.
 *
 * Examples:
 *      CA  = California
 *      TX  = Texas
 *      NY  = New York
 *      US  = Federal Government
 *
 * VERSION
 * -------
 * 2.1.0  Refactored for ws-core architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_jurisdiction_fields');

function ws_register_acf_jurisdiction_fields() {

    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(

        'key' => 'group_ws_jurisdiction',

        'title' => 'Jurisdiction Metadata',

        'fields' => array(

            /*
            ---------------------------------------------------------
            Jurisdiction Identity
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_jx_code',
                'label' => 'Jurisdiction Code',
                'name' => 'jx_code',
                'type' => 'text',
                'instructions' => 'Two-letter jurisdiction identifier (example: CA, TX, NY, US).',
                'required' => 1,
                'maxlength' => 2,
                'wrapper' => array(
                    'width' => '20'
                )
            ),

            array(
                'key' => 'field_ws_jx_type',
                'label' => 'Jurisdiction Type',
                'name' => 'ws_jx_type',
                'type' => 'select',
                'choices' => array(
                    'state' => 'U.S. State',
                    'federal' => 'Federal',
                    'district' => 'District',
                    'territory' => 'U.S. Territory'
                ),
                'required' => 1,
                'wrapper' => array(
                    'width' => '30'
                )
            ),

            /*
            ---------------------------------------------------------
            Flag Information
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_jx_flag',
                'label' => 'Jurisdiction Flag',
                'name' => 'ws_jx_flag',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all'
            ),

            array(
                'key' => 'field_ws_jx_flag_attribution',
                'label' => 'Flag Attribution',
                'name' => 'ws_jx_flag_attribution',
                'type' => 'text'
            ),

            array(
                'key' => 'field_ws_jx_flag_source',
                'label' => 'Flag Source URL',
                'name' => 'ws_jx_flag_source',
                'type' => 'url'
            ),

            array(
                'key' => 'field_ws_jx_flag_license',
                'label' => 'Flag License',
                'name' => 'ws_jx_flag_license',
                'type' => 'text'
            ),

            /*
            ---------------------------------------------------------
            Government Portal
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_gov_portal_url',
                'label' => 'Government Portal URL',
                'name' => 'ws_gov_portal_url',
                'type' => 'url'
            ),

            array(
                'key' => 'field_ws_gov_portal_label',
                'label' => 'Government Portal Label',
                'name' => 'ws_gov_portal_label',
                'type' => 'text',
                'placeholder' => 'Official Government Website'
            ),

            /*
            ---------------------------------------------------------
            Head of Government
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_head_url',
                'label' => 'Head of Government URL',
                'name' => 'ws_head_url',
                'type' => 'url'
            ),

            array(
                'key' => 'field_ws_head_label',
                'label' => 'Head of Government Label',
                'name' => 'ws_head_label',
                'type' => 'text',
                'placeholder' => 'Governor / Mayor'
            ),

            /*
            ---------------------------------------------------------
            Legal Authority
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_legal_authority_url',
                'label' => 'Legal Authority URL',
                'name' => 'ws_legal_authority_url',
                'type' => 'url'
            ),

            array(
                'key' => 'field_ws_legal_authority_label',
                'label' => 'Legal Authority Label',
                'name' => 'ws_legal_authority_label',
                'type' => 'text',
                'placeholder' => 'Attorney General / Secretary of Justice'
            ),

            /*
            ---------------------------------------------------------
            Legislature
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_legislature_url',
                'label' => 'Legislature Website',
                'name' => 'ws_legislature_url',
                'type' => 'url'
            ),

            array(
                'key' => 'field_ws_legislature_label',
                'label' => 'Legislature Label',
                'name' => 'ws_legislature_label',
                'type' => 'text',
                'placeholder' => 'State Legislature'
            ),

            /*
            ---------------------------------------------------------
            Dataset Relationships
            ---------------------------------------------------------
            */

            array(
                'key' => 'field_ws_related_summary',
                'label' => 'Jurisdiction Summary',
                'name' => 'ws_related_summary',
                'type' => 'relationship',
                'post_type' => array('jx_summary'),
                'max' => 1,
                'return_format' => 'object'
            ),

            array(
                'key' => 'field_ws_related_procedures',
                'label' => 'Jurisdiction Procedures',
                'name' => 'ws_related_procedures',
                'type' => 'relationship',
                'post_type' => array('jx_procedures'),
                'max' => 1,
                'return_format' => 'object'
            ),

            array(
                'key' => 'field_ws_related_statutes',
                'label' => 'Jurisdiction Statutes',
                'name' => 'ws_related_statutes',
                'type' => 'relationship',
                'post_type' => array('jx_statutes'),
                'max' => 1,
                'return_format' => 'object'
            ),

            array(
                'key' => 'field_ws_related_resources',
                'label' => 'Jurisdiction Resources',
                'name' => 'ws_related_resources',
                'type' => 'relationship',
                'post_type' => array('jx_resources'),
                'max' => 1,
                'return_format' => 'object'
            ),

        ),

        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'jurisdiction'
                )
            )
        ),

        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'active' => true

    ));
}