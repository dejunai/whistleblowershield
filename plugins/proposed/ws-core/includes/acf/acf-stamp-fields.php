<?php
/**
 * acf-stamp-fields.php
 *
 * Registers the centralized Authorship & Review ACF field group
 * shared across all content CPTs that carry stamp metadata.
 *
 * PURPOSE
 * -------
 * Provides a single authoritative registration of the four stamp
 * fields used on every supported CPT. Prior to this file, these
 * fields were duplicated inline inside each CPT's ACF registration
 * file. Centralizing here eliminates that duplication and ensures
 * all CPTs share identical field definitions, keys, and behavior.
 *
 * ATTACHED CPTs
 * -------------
 * jx-summary, jx-statute, jx-citation, jx-interpretation,
 * ws-agency, ws-assist-org, ws-legal-update, ws-reference
 *
 * EXCLUDED CPTs
 * -------------
 * jurisdiction — Option D: jurisdiction records are seeder-generated,
 * not human-authored. The create_author concept does not map cleanly
 * to a WordPress user ID for matrix-seeded posts. jurisdiction retains
 * its own Record Management tab in acf-jurisdictions.php with a text
 * field for Created By that the seeder can populate with a human-readable
 * provenance string (e.g. "Matrix Seeder"). See acf-jurisdictions.php.
 *
 * FIELDS
 * ------
 * All four fields share canonical unprefixed meta key names. WordPress
 * post meta is scoped to post_id — no collision risk across CPTs.
 *
 *   last_edited_author  User who last saved this record. Stamped
 *                       automatically on every save. Admins may override
 *                       to preserve attribution for minor corrections.
 *                       return_format => array (display name + ID).
 *
 *   date_created        Local date (Y-m-d) this record was first saved.
 *                       Written once, never overwritten.
 *
 *   last_edited         Local date (Y-m-d) of the most recent save.
 *                       Refreshed on every save.
 *
 *   create_author       WordPress user ID of the user who created this
 *                       record. Written once, never overwritten.
 *                       return_format => id (stamp target).
 *
 * FIELD LOCKING
 * -------------
 * date_created, last_edited, create_author — locked for non-admins via
 * ws_acf_lock_for_non_admins() in admin-hooks.php (registered by field name).
 *
 * last_edited_author — also locked for non-admins. Admins see it
 * pre-filled with their own user ID via ws_acf_autofill_current_editor().
 *
 * STAMP WRITES
 * ------------
 * All four fields are written server-side by ws_acf_write_stamp_fields()
 * in admin-hooks.php at acf/save_post priority 20. The ACF UI fields are
 * display-only for non-admins; ACF's disabled attribute prevents submission
 * and preserves existing stored values.
 *
 * HOOKS
 * -----
 * Registered on acf/init, consistent with all other ACF files in ws-core.
 *
 * @package    WhistleblowerShield
 * @since      3.4.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.4.0  Initial release. Centralizes stamp fields previously duplicated
 *        in acf-jx-summaries.php, acf-jx-statutes.php, acf-jx-citations.php,
 *        acf-jx-interpretations.php, acf-agencies.php, acf-assist-orgs.php,
 *        acf-legal-updates.php, and acf-references.php.
 *        ws-reference joins with shared field keys — the previously unique
 *        field_ws_ref_last_edited_author key is retired; $ws_stamp_cpts in
 *        admin-hooks.php updated to field_last_edited_author for ws-reference.
 * 3.5.0  Sanity pass (ws-core v3.1.0): group key renamed
 *        group_ws_stamp_fields → group_stamp_metadata per new ACF key rules.
 * 3.6.0  ws_auto_ pass (ws-core v3.2.0): all four meta key names prefixed:
 *        date_created → ws_auto_date_created, last_edited → ws_auto_last_edited,
 *        last_edited_author → ws_auto_last_edited_author,
 *        create_author → ws_auto_create_author.
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

        // Attaches to all 8 supported CPTs.
        // jurisdiction is intentionally excluded — see file header.
        'location' => [
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-summary'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-statute'         ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-citation'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-interpretation'  ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-agency'          ] ],
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
