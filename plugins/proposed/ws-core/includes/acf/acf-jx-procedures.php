<?php
/**
 * acf-jx-procedures.php
 *
 * Registers ACF Pro fields for the `jx-procedures` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Jurisdiction Procedures records.
 * The main editorial content is written in the WordPress block
 * editor. These fields supply supporting metadata for legal
 * review tracking and relationship management.
 *
 * BACK-REFERENCE FIELD
 * --------------------
 * ws_jurisdiction links this procedures record back to its parent
 * Jurisdiction record. This field is required by
 * ws_sync_jurisdiction_relationships() in admin-relationships.php
 * to maintain two-way relationship consistency.
 *
 * When this record is saved, the sync function reads ws_jurisdiction
 * and writes this post's ID into ws_related_procedures on the parent
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
 *         to jx-procedures (hyphenated). Added ws_jurisdiction
 *         back-reference field to support two-way relationship
 *         sync via admin-relationships.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jx_procedures' );

function ws_register_acf_jx_procedures() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_jx_procedure',
        'title'                 => 'Procedures Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Location: jx-procedures CPT only (hyphenated slug)
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-procedure',
        ] ] ],

        'fields' => [

            // ── Back-Reference: Parent Jurisdiction ───────────────────────
            // Required for two-way relationship sync.
            // See ws_sync_jurisdiction_relationships() in
            // admin-relationships.php.

            [
                'key'          => 'field_ws_procedures_jx_code',
                'label'        => 'Jurisdiction Code',
                'name'         => 'ws_jx_code',
                'type'         => 'text',
                'instructions' => 'USPS code for the parent jurisdiction (e.g., CA, TX, US). Required for relationship sync. Pre-populated automatically when created via the Jurisdiction editor.',
                'required'     => 1,
                'maxlength'    => 2,
                'placeholder'  => 'CA',
            ],

            // ── Legal Review ──────────────────────────────────────────────

            [
                'key'            => 'field_ws_procedure_last_review',
                'label'          => 'Last Legal Review',
                'name'           => 'ws_procedure_last_review',
                'type'           => 'date_picker',
                'instructions'   => 'Date these procedures were last reviewed for legal accuracy.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Authorship & Review ───────────────────────────────────────
            //
            // Stamp fields are written server-side via ws_acf_write_stamp_fields()
            // in admin-hooks.php. date_created and last_edited are readonly for
            // everyone. last_edited_author is locked for non-admins; administrators
            // may override it to preserve attribution.

            [
                'key'   => 'field_ws_jx_proc_tab_authorship',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_jx_proc_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_jx_proc_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            [
                'key'          => 'field_ws_jx_proc_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_proc_date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_ws_jx_proc_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'ws_jx_proc_last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jx_procedures


