<?php
/**
 * acf-stamp-fields.php — Centralized Authorship & Review field group.
 *
 * Group key: group_stamp_metadata  (menu_order 90)
 *
 * Attaches to: jx-summary, jx-statute, jx-citation, jx-interpretation,
 *              ws-agency, ws-ag-procedure, ws-assist-org, ws-legal-update,
 *              ws-reference
 *
 * Excluded: jurisdiction — seeder-generated; create authorship not meaningful.
 *           jurisdiction carries its own slim Record Management tab in
 *           acf-jurisdictions.php.
 *
 * Fields (all auto-filled, read-only for non-administrators):
 *   ws_auto_last_edited_author  — user who last saved; admin-overridable
 *   ws_auto_date_created        — local Y-m-d; written once
 *   ws_auto_last_edited         — local Y-m-d; every save
 *   ws_auto_create_author       — WP user ID; written once
 *
 * @package WhistleblowerShield
 * @since   3.4.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 3.4.0   Initial release. Centralizes stamp fields previously duplicated
 *         across individual CPT ACF files.
 * 3.5.0   Group key renamed: group_ws_stamp_fields → group_stamp_metadata.
 * 3.6.0   Stamp meta keys prefixed with ws_auto_.
 * 3.9.0   ws-ag-procedure added to location rules.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_stamp_fields' );

/**
 * Registers the shared Authorship & Review field group for all supported CPTs.
 */
function ws_register_acf_stamp_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_stamp_metadata',
        'title'                 => 'Authorship & Review',
        'menu_order'            => 90,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Attaches to all 9 supported CPTs.
        // jurisdiction is intentionally excluded — see file header.
        'location' => [
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-summary'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-statute'         ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-citation'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-interpretation'  ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-common-law'      ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-agency'          ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-ag-procedure'    ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-assist-org'      ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-legal-update'    ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-reference'       ] ],
        ],

        'fields' => [

            // ── Tab: Authorship & Review ──────────────────────────────────
            //
            // menu_order 90 positions this group after each CPT's own
            // content tabs but before acf-major-edit.php (menu_order 99).

            [
                'key'   => 'field_stamp_authorship_tab',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            // ── Last Edited By ────────────────────────────────────────────
            //
            // Stamped automatically on every save. Admins may override to
            // preserve attribution when a minor correction is made by someone
            // other than the credited contributor. Pre-filled for admins via
            // ws_acf_autofill_current_editor() in admin-hooks.php.

            [
                'key'           => 'field_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_auto_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            // ── Date Created ──────────────────────────────────────────────
            //
            // Written once on first save by ws_acf_write_stamp_fields().
            // Readonly and disabled for all users — never submitted via UI.

            [
                'key'          => 'field_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_auto_date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            // ── Last Edited ───────────────────────────────────────────────
            //
            // Refreshed on every save by ws_acf_write_stamp_fields().
            // Readonly and disabled for all users — never submitted via UI.

            [
                'key'          => 'field_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'ws_auto_last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            // ── Created By ────────────────────────────────────────────────
            //
            // Written once on first save by ws_acf_write_stamp_fields().
            // Readonly and disabled for all users — never submitted via UI.
            // return_format => id so the stamp function can read the stored
            // user ID directly without unwrapping an array.

            [
                'key'           => 'field_create_author',
                'label'         => 'Created By',
                'name'          => 'ws_auto_create_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on first save. Read only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
                'wrapper'       => [ 'width' => '33' ],
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_stamp_fields


// Field locking and stamp writes are handled centrally in admin-hooks.php.
// ws_acf_lock_for_non_admins() applies to: ws_auto_date_created, ws_auto_last_edited,
// ws_auto_last_edited_author, ws_auto_create_author (registered by field name, applies
// to all CPTs carrying these field names — no per-file registration needed).
