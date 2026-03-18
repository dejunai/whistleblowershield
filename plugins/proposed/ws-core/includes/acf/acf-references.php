<?php
/**
 * acf-ws-reference.php
 *
 * Registers ACF Pro fields for the `ws-reference` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for external reference materials attached
 * to individual statute, citation, or interpretation records.
 *
 * FIELD SUMMARY
 * -------------
 * Content tab:
 *   ws_ref_title       Title of the referenced resource (text)
 *   ws_ref_url         External link to the resource (url, required)
 *   ws_ref_description Brief description of the resource and relevance (textarea)
 *   ws_ref_type        Resource type (select)
 *   ws_ref_source_name Publishing organization or author name (text)
 *
 * Approval tab:
 *   ws_ref_approved    Editor approval toggle — admin-rank required (true_false)
 *                      Only approved references display publicly via [ws_reference_page].
 *
 * Authorship & Review tab:
 *   last_edited_author  Last edited by (user, readonly non-admins)
 *                       ACF key: field_ws_ref_last_edited_author (unique to this group)
 *   date_created        Date created (text, readonly, stamped once)
 *   last_edited         Last edited (text, readonly, stamped every save)
 *   create_author       Created by (user, readonly)
 *
 * STAMP FIELDS
 * ------------
 * Written server-side by ws_acf_write_stamp_fields() in admin-hooks.php via
 * the $ws_stamp_cpts config map (entry: 'ws-reference').
 * Field names match the shared unprefixed stamp keys used across all ws-core CPTs.
 *
 * PLAIN ENGLISH
 * -------------
 * ws-reference does not participate in the has_plain_english / plain_reviewed
 * workflow. ws_ref_approved is its own independent approval mechanism.
 *
 * @package    WhistleblowerShield
 * @since      3.3.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.3.0  Initial release.
 */

add_action( 'acf/init', 'ws_register_acf_ws_reference' );

function ws_register_acf_ws_reference() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'                   => 'group_ws_reference_metadata',
        'title'                 => 'Reference Details',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-reference',
        ] ] ],

        'fields' => [

            // ────────────────────────────────────────────────────────────────
            // Tab: Content
            //
            // The substance of the reference: what it is, where it lives,
            // and why it's relevant to the parent record.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ref_tab_content',
                'label' => 'Content',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_ref_title',
                'label'        => 'Resource Title',
                'name'         => 'ws_ref_title',
                'type'         => 'text',
                'instructions' => 'The title of the referenced resource as it should appear publicly.',
                'required'     => 0,
            ],

            [
                'key'          => 'field_ws_ref_url',
                'label'        => 'Resource URL',
                'name'         => 'ws_ref_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the external resource. Required. All outbound links open in a new tab.',
                'required'     => 1,
            ],

            [
                'key'          => 'field_ws_ref_description',
                'label'        => 'Description',
                'name'         => 'ws_ref_description',
                'type'         => 'textarea',
                'instructions' => 'Brief description of the resource and its relevance to the parent record. One to three sentences.',
                'rows'         => 4,
            ],

            [
                'key'           => 'field_ws_ref_type',
                'label'         => 'Resource Type',
                'name'          => 'ws_ref_type',
                'type'          => 'select',
                'instructions'  => 'Classify the type of resource.',
                'choices'       => [
                    'case_law'        => 'Case Law',
                    'academic_paper'  => 'Academic Paper',
                    'gov_report'      => 'Government Report',
                    'journalism'      => 'Journalism',
                    'policy_analysis' => 'Policy Analysis',
                    'other'           => 'Other',
                ],
                'allow_null'    => 1,
                'ui'            => 1,
                'return_format' => 'label',
            ],

            [
                'key'          => 'field_ws_ref_source_name',
                'label'        => 'Source / Author',
                'name'         => 'ws_ref_source_name',
                'type'         => 'text',
                'instructions' => 'Name of the publishing organization or primary author.',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Approval
            //
            // Controls public visibility via the [ws_reference_page] shortcode.
            // Only approved references are returned by ws_get_ref_materials().
            // Locked for users below administrator via admin-hooks.php.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ref_tab_approval',
                'label' => 'Approval',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_ref_approved',
                'label'         => 'Approved for Public Display',
                'name'          => 'ws_ref_approved',
                'type'          => 'true_false',
                'instructions'  => 'Enable to allow this reference to appear on the public reference page. Requires administrator access. Only approved references are returned by the query layer.',
                'ui'            => 1,
                'ui_on_text'    => 'Approved',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Authorship & Review
            //
            // Standard stamp fields shared across all ws-core CPTs.
            // Written server-side by ws_acf_write_stamp_fields() in admin-hooks.php.
            // Field key for last_edited_author is unique to this group
            // (field_ws_ref_last_edited_author) to avoid ACF key lookup ambiguity
            // with the shared field_last_edited_author key used in other groups.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ref_tab_authorship',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_ref_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            [
                'key'          => 'field_ws_ref_date_created',
                'label'        => 'Date Created',
                'name'         => 'date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_ws_ref_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'           => 'field_ws_ref_create_author',
                'label'         => 'Created By',
                'name'          => 'create_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on first save. Read only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
                'wrapper'       => [ 'width' => '33' ],
            ],

        ],
    ] );

} // end ws_register_acf_ws_reference


// Field locking and stamp fields are handled centrally in admin-hooks.php.
// ws_ref_approved is locked for non-admins via ws_acf_lock_for_non_admins().
// ws-reference is NOT enrolled in the plain_english guards or stamp functions.
