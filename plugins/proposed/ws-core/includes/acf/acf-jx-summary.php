<?php
/**
 * acf-jx-summary.php
 *
 * Registers ACF Pro fields for the `jx-summary` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Jurisdiction Summary records.
 * The main editorial content is written in the WordPress block
 * editor. These fields supply supporting metadata for legal
 * review tracking and relationship management.
 *
 * BACK-REFERENCE FIELD
 * --------------------
 * ws_jurisdiction links this summary back to its parent
 * Jurisdiction record. This field is required by
 * ws_sync_jurisdiction_relationships() in admin-relationships.php
 * to maintain two-way relationship consistency.
 *
 * When this record is saved, the sync function reads ws_jurisdiction
 * and writes this post's ID into ws_related_summary on the parent
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
 *         to jx-summary (hyphenated). Added ws_jurisdiction
 *         back-reference field to support two-way relationship
 *         sync via admin-relationships.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jx_summary' );

function ws_register_acf_jx_summary() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_jx_summary',
        'title'                 => 'Jurisdiction Summary Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Location: jx-summary CPT only (hyphenated slug)
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-summary',
        ] ] ],

        'fields' => [

            // ── Back-Reference: Parent Jurisdiction ───────────────────────
            // Required for two-way relationship sync.
            // See ws_sync_jurisdiction_relationships() in
            // admin-relationships.php.

            [
                'key'          => 'field_ws_summary_jurisdiction',
                'label'        => 'Parent Jurisdiction',
                'name'         => 'ws_jurisdiction',
                'type'         => 'post_object',
                'instructions' => 'Select the Jurisdiction this summary belongs to. Required for relationship sync.',
                'required'     => 1,
                'post_type'    => [ 'jurisdiction' ],
                'allow_null'   => 0,
                'multiple'     => 0,
                'return_format' => 'id',
                'ui'           => 1,
            ],

            // ── Legal Review ──────────────────────────────────────────────

            [
                'key'            => 'field_ws_summary_last_review',
                'label'          => 'Last Legal Review',
                'name'           => 'ws_summary_last_review',
                'type'           => 'date_picker',
                'instructions'   => 'Date this summary was last reviewed for legal accuracy.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Internal Notes ────────────────────────────────────────────

            [
                'key'          => 'field_ws_summary_notes',
                'label'        => 'Internal Notes',
                'name'         => 'ws_summary_notes',
                'type'         => 'textarea',
                'instructions' => 'Internal editorial notes only. Not displayed publicly.',
                'rows'         => 4,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_summary
