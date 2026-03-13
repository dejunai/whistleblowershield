<?php
/**
 * File: acf-jx-procedures.php
 *
 * Defines fields for Jurisdiction Procedures datasets.
 *
 * Procedures explain HOW a whistleblower reports misconduct
 * within a jurisdiction.
 *
 * VERSION
 * 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_jx_procedures');

function ws_register_acf_jx_procedures() {

acf_add_local_field_group(array(

'key' => 'group_ws_jx_procedures',

'title' => 'Procedures Metadata',

'fields' => array(

array(
'key' => 'field_ws_procedure_last_review',
'label' => 'Last Legal Review',
'name' => 'ws_procedure_last_review',
'type' => 'date_picker'
),

),

'location' => array(
array(
array(
'param' => 'post_type',
'operator' => '==',
'value' => 'jx_procedures'
),
),
),

));

}