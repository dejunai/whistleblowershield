<?php
/**
 * acf-plain-english-fields.php
 *
 * Registers the centralized Plain Language ACF field group shared
 * across content CPTs that support plain language companion documents.
 *
 * PURPOSE
 * -------
 * Provides a single authoritative registration of the plain language
 * fields used on supported CPTs. Prior to this file, these fields were
 * duplicated inline inside each CPT's ACF registration file.
 * Centralizing here eliminates that duplication and ensures consistent
 * field definitions, keys, and behavior across all supported CPTs.
 *
 * ATTACHED CPTs
 * -------------
 * jx-statute, jx-citation, jx-interpretation, ws-agency
 *
 * These are the CPTs whose primary content is legal source material
 * (statutes, case law, court interpretations) or complex institutional
 * descriptions (agencies) that may warrant a plain-language companion.
 *
 * EXCLUDED CPTs — and why
 * -----------------------
 * jx-summary     — IS the plain language document. It does not translate
 *                  legalese; it is the synthesis. It carries its own
 *                  reviewed fields in acf-jx-summaries.php with a badge
 *                  rendered independently by its shortcode path.
 *
 * ws-assist-org  — Assist org content is plain language by nature.
 *                  No translation layer is needed or appropriate.
 *
 * ws-legal-update — Time-stamped news/changelog entries. No plain
 *                   language companion use case exists.
 *
 * ws-reference   — Outbound links with metadata. No prose content
 *                  requiring simplification.
 *
 * jurisdiction   — A structured data container (metadata, URLs, flags).
 *                  Not explanatory prose. Plain language companions
 *                  belong on the child CPTs that carry the actual legal
 *                  content (statutes, citations, interpretations).
 *
 * FIELDS
 * ------
 *   has_plain_english         Toggle: plain language version exists.
 *                             Triggers conditional display of the wysiwyg.
 *
 *   plain_english_wysiwyg     The plain language content itself.
 *                             Conditional on has_plain_english = 1.
 *
 *   plain_english_reviewed    Toggle: a human has reviewed and approved
 *                             the plain language content. Requires editor
 *                             rank or above (enforced in admin-hooks.php).
 *
 *   ws_auto_plain_english_reviewed_by  WP user ID of the reviewer. Stamped
 *                             once when plain_english_reviewed is first
 *                             enabled. Cleared on toggle-off.
 *
 *   ws_auto_plain_english_reviewed_date  Local date (Y-m-d) the plain language
 *                             content was first reviewed. Stamped once when
 *                             plain_english_reviewed is first enabled.
 *                             Cleared on has_plain_english toggle-off.
 *
 *   ws_auto_plain_english_by  WP user ID of the summarizer. Stamped once
 *                             on first save after has_plain_english is
 *                             enabled and content exists.
 *
 *   ws_auto_plain_english_date  Local date (Y-m-d) the plain language content
 *                             was first saved. Stamped once. Cleared on
 *                             has_plain_english toggle-off.
 *
 * RENDER LAYER
 * ------------
 * The build_trust_badge() call in each CPT's render/shortcode path decides
 * whether to render a plain language reviewed badge. jx-summary's render
 * path calls its own badge variant independently — it does not use these
 * fields. No conditional logic is needed in this registration file.
 *
 * INTEGRITY GUARDS
 * ----------------
 * ws_acf_plain_english_guards() in admin-hooks.php (priority 5) enforces:
 *   Rule 1 — has_plain_english requires non-empty plain_english_wysiwyg.
 *   Rule 2 — plain_english_reviewed requires editor rank or above.
 *   Rule 3 — has_plain_english toggle-off clears reviewed fields and stamps.
 *
 * STAMP WRITES
 * ------------
 * ws_auto_plain_english_reviewed_by + ws_auto_plain_english_reviewed_date — ws_acf_stamp_plain_reviewed_by() priority 25.
 * ws_auto_plain_english_by + ws_auto_plain_english_date — ws_acf_stamp_summarized_fields() priority 25.
 * All functions in admin-hooks.php. None reference ACF field keys —
 * all read/write post meta directly by key name.
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
 * 3.4.0  Initial release. Centralizes plain language fields previously
 *        duplicated in acf-jx-statutes.php, acf-jx-citations.php,
 *        acf-jx-interpretations.php, and acf-agencies.php.
 *        ws-assist-org plain language tab retired — content is plain
 *        language by nature; feature does not apply.
 * 3.5.0  Sanity pass (ws-core v3.1.0): group key renamed
 *        group_ws_plain_english_fields → group_plain_english_metadata per new ACF key rules.
 * 3.6.0  ws_auto_ pass (ws-core v3.2.0): stamp field meta keys prefixed:
 *        plain_english_reviewed_by → ws_auto_plain_english_reviewed_by,
 *        plain_english_by → ws_auto_plain_english_by,
 *        plain_english_date → ws_auto_plain_english_date.
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
