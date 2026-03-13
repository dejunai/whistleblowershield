<?php
/**
 * File: acf-jx-summary.php
 *
 * Defines ACF fields for the Jurisdiction Summary dataset.
 *
 * The summary provides a plain-English overview of whistleblower
 * protections within a jurisdiction.
 *
 * The main content is written in the WordPress editor.
 * These fields provide structured metadata to support
 * future indexing and search capabilities.
 *
 * VERSION
 * 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'ws_register_acf_jx_summary');

function ws_register_acf_jx_summary() {

acf_add_local_field_group(array(

'key' => 'group_ws_jx_summary',

'title' => 'Jurisdiction Summary Metadata',

'fields' => array(

array(
'key' => 'field_ws_summary_last_review',
'label' => 'Last Legal Review',
'name' => 'ws_summary_last_review',
'type' => 'date_picker'
),

array(
'key' => 'field_ws_summary_notes',
'label' => 'Internal Notes',
'name' => 'ws_summary_notes',
'type' => 'textarea',
'instructions' => 'Internal editorial notes only.',
),

),

'location' => array(
array(
array(
'param' => 'post_type',
'operator' => '==',
'value' => 'jx_summary'
),
),
),

));

}