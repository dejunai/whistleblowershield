<?php
/**
 * acf-ws-legal-update.php
 *
 * Registers ACF Pro fields for the `ws-legal-update` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Legal Update records, capturing
 * the nature, source, date, and affected jurisdictions of each
 * significant development in whistleblower law.
 *
 * Legal Updates are linked to one or more Jurisdiction records
 * through the ws_update_jurisdictions relationship field. This
 * relationship is jurisdiction → update (not managed by
 * admin-relationships.php, which handles jx-* addenda only).
 *
 * FUTURE USE
 * ----------
 * These records are intended for:
 *
 *      • internal legal tracking
 *      • journalist research
 *      • future public update feeds and timelines
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
 *         to ws-legal-update (hyphenated). File renamed from
 *         acf-legal-update.php to acf-ws-legal-update.php.
 *         Full header and inline comments added.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_legal_update' );

function ws_register_acf_legal_update() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_legal_update',
        'title'                 => 'Legal Update Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Location: ws-legal-update CPT only (hyphenated slug)
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-legal-update',
        ] ] ],

        'fields' => [

            // ── Affected Jurisdictions ────────────────────────────────────
            // Multi-select relationship — one update may affect many
            // jurisdictions. Returns an array of post IDs.

            [
                'key'           => 'field_ws_update_jurisdictions',
                'label'         => 'Affected Jurisdictions',
                'name'          => 'ws_update_jurisdictions',
                'type'          => 'relationship',
                'instructions'  => 'Select all jurisdictions affected by this legal update.',
                'post_type'     => [ 'jurisdiction' ],
                'filters'       => [ 'search' ],
                'return_format' => 'id',
            ],

            // ── Update Date ───────────────────────────────────────────────

            [
                'key'            => 'field_ws_update_date',
                'label'          => 'Update Date',
                'name'           => 'ws_update_date',
                'type'           => 'date_picker',
                'instructions'   => 'Date the legal change took effect or was officially published.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Primary Source ────────────────────────────────────────────

            [
                'key'          => 'field_ws_update_source',
                'label'        => 'Primary Source URL',
                'name'         => 'ws_update_source',
                'type'         => 'url',
                'instructions' => 'Official source for the legal change — e.g., court decision, statute, regulation, or agency policy document.',
            ],

            // ── Update Type ───────────────────────────────────────────────

            [
                'key'          => 'field_ws_update_type',
                'label'        => 'Update Type',
                'name'         => 'ws_update_type',
                'type'         => 'select',
                'instructions' => 'Select the category that best describes this legal development.',
                'choices'      => [
                    'statute'    => 'Statutory Change',
                    'court'      => 'Court Decision',
                    'regulation' => 'Regulatory Change',
                    'policy'     => 'Agency Policy',
                    'other'      => 'Other',
                ],
                'default_value' => 'statute',
                'allow_null'    => 0,
                'ui'            => 1,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_legal_update
