<?php
/**
 * File: acf-legal-update.php
 *
 * Defines structured metadata for Legal Update records.
 *
 * Legal Updates track developments in whistleblower law
 * across U.S. jurisdictions. Updates may apply to one or
 * multiple jurisdictions.
 *
 * These records are intended primarily for:
 *
 * • internal legal tracking
 * • journalist research
 * • future public update feeds
 *
 * VERSION
 * 2.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_legal_update');

function ws_register_acf_legal_update() {

acf_add_local_field_group(array(

'key' => 'group_ws_legal_update',

'title' => 'Legal Update Metadata',

'fields' => array(

array(
'key' => 'field_ws_update_jurisdictions',
'label' => 'Affected Jurisdictions',
'name' => 'ws_update_jurisdictions',
'type' => 'relationship',
'post_type' => array('jurisdiction'),
'filters' => array(
'search',
'post_type'
),
'return_format' => 'id',
'instructions' => 'Select all jurisdictions affected by this update.'
),

array(
'key' => 'field_ws_update_date',
'label' => 'Update Date',
'name' => 'ws_update_date',
'type' => 'date_picker',
'display_format' => 'Y-m-d',
'return_format' => 'Y-m-d'
),

array(
'key' => 'field_ws_update_source',
'label' => 'Primary Source URL',
'name' => 'ws_update_source',
'type' => 'url',
'instructions' => 'Official source for the legal change (court decision, statute, regulation, etc).'
),

array(
'key' => 'field_ws_update_type',
'label' => 'Update Type',
'name' => 'ws_update_type',
'type' => 'select',
'choices' => array(
'statute' => 'Statutory Change',
'court' => 'Court Decision',
'regulation' => 'Regulatory Change',
'policy' => 'Agency Policy',
'other' => 'Other'
),
'default_value' => 'statute'
),

),

'location' => array(
array(
array(
'param' => 'post_type',
'operator' => '==',
'value' => 'legal_update'
),
),
),

));

}