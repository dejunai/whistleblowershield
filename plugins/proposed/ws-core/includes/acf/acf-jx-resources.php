<?php
/**
 * File: acf-jx-resources.php
 *
 * Defines structured fields for jurisdiction resources.
 *
 * Resources include government reporting portals,
 * inspectors general, ethics commissions, etc.
 *
 * VERSION
 * 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_jx_resources');

function ws_register_acf_jx_resources() {

acf_add_local_field_group(array(

'key' => 'group_ws_jx_resources',

'title' => 'Resource Links',

'fields' => array(

array(
'key' => 'field_ws_resource_agency',
'label' => 'Agency Name',
'name' => 'ws_resource_agency',
'type' => 'text'
),

array(
'key' => 'field_ws_resource_url',
'label' => 'Agency URL',
'name' => 'ws_resource_url',
'type' => 'url'
),

),

'location' => array(
array(
array(
'param' => 'post_type',
'operator' => '==',
'value' => 'jx_resources'
),
),
),

));

}