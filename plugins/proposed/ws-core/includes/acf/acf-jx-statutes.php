<?php
/**
 * File: acf-jx-statutes.php
 *
 * Defines fields for Jurisdiction Statutes datasets.
 *
 * This dataset stores statutory references relevant to
 * whistleblower protections.
 *
 * VERSION
 * 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_jx_statutes');

function ws_register_acf_jx_statutes() {

acf_add_local_field_group(array(

'key' => 'group_ws_jx_statutes',

'title' => 'Statutes Metadata',

'fields' => array(

array(
'key' => 'field_ws_statutes_last_review',
'label' => 'Last Legal Review',
'name' => 'ws_statutes_last_review',
'type' => 'date_picker'
),

),

'location' => array(
array(
array(
'param' => 'post_type',
'operator' => '==',
'value' => 'jx_statutes'
),
),
),

));

}