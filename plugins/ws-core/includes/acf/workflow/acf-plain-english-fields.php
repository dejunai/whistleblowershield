<?php
/**
 * acf-plain-english-fields.php
 *
 * Centralized Plain Language ACF field group shared across content CPTs.
 * Group key: group_plain_english_metadata  (menu_order 85)
 *
 * ATTACHED CPTs
 * -------------
 * jx-statute, jx-citation, jx-interpretation, ws-agency
 *
 * EXCLUDED CPTs (and why)
 * -----------------------
 * jx-summary      — IS the plain language document; carries its own review fields.
 * ws-assist-org   — Content is plain language by nature; no overlay needed.
 * ws-legal-update — Changelog entries; no plain language companion use case.
 * ws-reference    — Outbound links with metadata; no prose to simplify.
 * jurisdiction    — Structured metadata container; not explanatory prose.
 *
 * FIELDS
 * ------
 * ws_has_plain_english              Toggle — enables plain language content field.
 * ws_plain_english_wysiwyg          The plain language content (conditional on toggle).
 * ws_plain_english_reviewed         Toggle — marks content as human-reviewed.
 * ws_auto_plain_english_reviewed_by User ID of reviewer. Stamped once on toggle-on; cleared on toggle-off.
 * ws_auto_plain_english_reviewed_date  Local Y-m-d of first review. Same lifecycle.
 * ws_auto_plain_english_by          User ID of summarizer. Stamped once on first plain language save.
 * ws_auto_plain_english_date        Local Y-m-d of first plain language save.
 *
 * INTEGRITY GUARDS (admin-hooks.php, priority 5)
 * -----------------------------------------------
 * Rule 1 — has_plain_english requires non-empty plain_english_wysiwyg.
 * Rule 2 — plain_english_reviewed requires editor rank or above.
 * Rule 3 — has_plain_english toggle-off clears all reviewed fields and stamps.
 *
 * @package    WhistleblowerShield
 * @since      3.4.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 3.4.0  Initial release. Centralizes plain language fields previously
 *        duplicated across four individual CPT ACF files.
 * 3.5.0  Group key renamed: group_ws_plain_english_fields → group_plain_english_metadata.
 * 3.6.0  Stamp field meta keys prefixed with ws_auto_.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_plain_english_fields' );

/**
 * Registers the shared Plain Language field group for all supported CPTs.
 */
function ws_register_acf_plain_english_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_plain_english_metadata',
        'title'                 => 'Plain Language',
        'menu_order'            => 85,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Attaches to the 4 CPTs whose content warrants plain language companions.
        // See file header for excluded CPTs and rationale.
        'location' => [
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-statute'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-citation'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-interpretation'  ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-common-law'      ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-agency'          ] ],
        ],

        'fields' => [

            // ── Tab: Plain Language ───────────────────────────────────────
            //
            // menu_order 85 positions this group after each CPT's content
            // tabs and before Authorship & Review (menu_order 90) and
            // Major Edit (menu_order 99).

            [
                'key'   => 'field_plain_english_tab',
                'label' => 'Plain Language',
                'type'  => 'tab',
            ],

            // ── Has Plain Language Version ────────────────────────────────
            //
            // Master toggle. Guards display of the wysiwyg editor below.
            // ws_acf_plain_english_guards() (priority 5) forces this to 0
            // if plain_english_wysiwyg is empty on save.

            [
                'key'           => 'field_has_plain_english',
                'label'         => 'Has Plain Language Version',
                'name'          => 'ws_has_plain_english',
                'type'          => 'true_false',
                'instructions'  => 'Enable when a plain-language version of this record has been written below.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            // ── Plain Language Content ────────────────────────────────────
            //
            // Conditional on has_plain_english = 1. Content written here
            // is stamped via ws_acf_stamp_summarized_fields() on first save.

            [
                'key'               => 'field_plain_english_wysiwyg',
                'label'             => 'Plain Language Content',
                'name'              => 'ws_plain_english_wysiwyg',
                'type'              => 'wysiwyg',
                'instructions'      => 'Plain-language explanation of this record for non-experts.',
                'tabs'              => 'all',
                'toolbar'           => 'full',
                'media_upload'      => 0,
                'conditional_logic' => [ [ [
                    'field'    => 'field_has_plain_english',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            // ── Plain Language Reviewed ───────────────────────────────────
            //
            // Requires editor rank or above — enforced server-side by
            // ws_acf_plain_english_guards() at priority 5. Cleared on
            // has_plain_english toggle-off by the same guard.

            [
                'key'           => 'field_plain_english_reviewed',
                'label'         => 'Plain Language Reviewed',
                'name'          => 'ws_plain_english_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human has reviewed and approved the plain-language content.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],

            // ── Reviewed By ───────────────────────────────────────────────
            //
            // Stamped once by ws_acf_stamp_plain_reviewed_by() when
            // plain_english_reviewed is first enabled. Cleared on toggle-off.
            // Locked for users below editor via ws_acf_lock_for_non_editors().

            [
                'key'           => 'field_plain_english_reviewed_by',
                'label'         => 'Reviewed By',
                'name'          => 'ws_auto_plain_english_reviewed_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped when Plain Language Reviewed is first enabled.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            // ── Reviewed Date ─────────────────────────────────────────────
            //
            // Stamped once by ws_acf_stamp_plain_reviewed_by() alongside
            // plain_english_reviewed_by. Cleared on has_plain_english toggle-off.
            // Locked for users below editor via ws_acf_lock_for_non_editors().

            [
                'key'          => 'field_plain_english_reviewed_date',
                'label'        => 'Reviewed Date',
                'name'         => 'ws_auto_plain_english_reviewed_date',
                'type'         => 'text',
                'instructions' => 'Auto-stamped when Plain Language Reviewed is first enabled. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            // ── Summarized By ─────────────────────────────────────────────
            //
            // Stamped once by ws_acf_stamp_summarized_fields() on first save
            // after has_plain_english is enabled and content exists.
            // Cleared on has_plain_english toggle-off.

            [
                'key'           => 'field_plain_english_by',
                'label'         => 'Summarized By',
                'name'          => 'ws_auto_plain_english_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped on first save after plain language content is created.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            // ── Summarized Date ───────────────────────────────────────────
            //
            // Stamped once by ws_acf_stamp_summarized_fields() alongside
            // plain_english_by. Cleared on has_plain_english toggle-off.

            [
                'key'          => 'field_plain_english_date',
                'label'        => 'Summarized Date',
                'name'         => 'ws_auto_plain_english_date',
                'type'         => 'text',
                'instructions' => 'Auto-stamped on first save after plain language content is created. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_plain_english_fields


// All integrity guards, stamp writes, and field locking for plain language
// fields are handled centrally in admin-hooks.php:
//   ws_acf_plain_english_guards()       — acf/save_post priority 5
//   ws_acf_stamp_plain_reviewed_by()    — acf/save_post priority 25
//     writes: ws_auto_plain_english_reviewed_by, ws_auto_plain_english_reviewed_date
//   ws_acf_stamp_summarized_fields()    — acf/save_post priority 25
//   ws_acf_lock_for_non_editors()       — acf/load_field by field name
