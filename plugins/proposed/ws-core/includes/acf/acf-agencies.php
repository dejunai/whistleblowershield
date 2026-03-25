<?php
/**
 * acf-agencies.php
 *
 * Registers ACF Pro fields for the `ws-agency` CPT.
 *
 * FIELD GROUPS
 * ------------
 * group_ws_agencies  →  Agency Details & Reporting Protocols
 *
 * JURISDICTION FIELD
 * ------------------
 * Scoped via the ws_jurisdiction taxonomy (field_ws_agency_jurisdiction).
 * ACF saves/loads terms natively — no dynamic choice filter needed.
 * Replaces the retired ws_jx_code meta select (Phase 3.2 / 12.1).
 *
 * HOOK
 * ----
 * Registered on acf/init, consistent with all other ACF files in ws-core.
 *
 * VERSION
 * -------
 * 2.3.1  Wrapped in add_action('acf/init',...) for hook consistency.
 *        Renamed field key field_ws_jx_code → field_ws_agencies_jx_codes
 *        to eliminate conflict with acf-jurisdiction.php.
 *        Jurisdiction choices now populated dynamically via acf/load_field
 *        instead of a hardcoded static list.
 *        Added ws_process_type taxonomy field (Process Types Handled).
 *        ws-agency added to ws_disclosure_cat object types in
 *        register-taxonomies.php so save_terms functions correctly.
 * 3.0.0  Phase 8: ws_jx_code multi-select replaced by ws_jurisdiction taxonomy
 *        field. Dynamic choice filter removed. Plain Language tab added (9.2).
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields — now registered
 *          centrally in acf-stamp-fields.php (group_stamp_metadata, menu_order 90).
 *        - Removed Plain Language tab and all plain English fields — now registered
 *          centrally in acf-plain-english-fields.php (menu_order 85).
 *        - ws_agency_last_reviewed retained as a content-owned field.
 */

defined( 'ABSPATH' ) || exit;


// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_agencies' );

function ws_register_acf_agencies() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_agency_metadata',
        'title'                 => 'Agency Details & Reporting Protocols',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-agency',
        ] ] ],

        'fields' => [

            // ── Tab: Agency Identity ──────────────────────────────────────

            [
                'key'   => 'field_agency_identity_tab',
                'label' => 'Agency Identity',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_agency_code',
                'label'        => 'Agency Reference Code',
                'name'         => 'ws_agency_code',
                'type'         => 'text',
                'instructions' => 'Internal slug-safe unique ID — e.g., "osc", "sec-owb", "ny-ag-wb".',
                'required'     => 1,
            ],
            [
                'key'          => 'field_agency_name',
                'label'        => 'Full Agency Name',
                'name'         => 'ws_agency_name',
                'type'         => 'text',
                'required'     => 1,
                'instructions' => 'Example: U.S. Office of Special Counsel',
            ],
            [
                'key'           => 'field_agency_logo',
                'label'         => 'Agency Logo',
                'name'          => 'ws_agency_logo',
                'type'          => 'image',
                'instructions'  => 'Upload a high-resolution logo (PNG or SVG preferred).',
                'required'      => 0,
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'library'       => 'all',
                'max_size'      => '1',  // 1MB
                'mime_types'    => 'png,svg,jpg,jpeg',
            ],

            // ── Jurisdiction(s) ───────────────────────────────────────────
            //
            // Agencies may have authority over multiple jurisdictions.
            // Scoped via the ws_jurisdiction taxonomy — assign terms to
            // control which jurisdiction pages surface this agency.
            // save_terms=1 writes term assignments directly; load_terms=1
            // reflects current taxonomy state in the admin UI.

            [
                'key'           => 'field_agency_jurisdiction',
                'label'         => 'Jurisdiction(s)',
                'name'          => WS_JURISDICTION_TAXONOMY,
                'type'          => 'taxonomy',
                'taxonomy'      => WS_JURISDICTION_TAXONOMY,
                'field_type'    => 'multi_select',
                'instructions'  => 'Assign all jurisdictions this agency has authority over. Use US for federal/nationwide agencies.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 1,
            ],
            [
                'key'        => 'field_agency_disclosure_type',
                'label'      => 'Disclosure Categories',
                'name'       => 'ws_agency_disclosure_type',
                'type'       => 'taxonomy',
                'taxonomy'   => 'ws_disclosure_type',
                'field_type' => 'multi_select',
                'add_term'   => 0,
                'save_terms' => 1,
                'load_terms' => 1,
                'return_format' => 'id',
            ],

            // ── Process Types ─────────────────────────────────────────────
            //
            // What types of legal action does this agency handle?
            // This is descriptive — the statute is the authoritative
            // source for which process types a whistleblower can use.
            // Tag the agency here so editors and users can filter
            // agencies by how they handle reports (e.g., "show me
            // agencies that accept anonymous administrative complaints").

            [
                'key'           => 'field_agency_process_type',
                'label'         => 'Process Types Handled',
                'name'          => 'ws_process_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_process_type',
                'instructions'  => 'Select all process types this agency handles. Refer to the relevant statute(s) as the authoritative source.',
                'field_type'    => 'multi_select',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 1,
            ],

            // ── Tab: Contact & Reporting ──────────────────────────────────

            [
                'key'   => 'field_agency_contact_tab',
                'label' => 'Contact & Reporting',
                'type'  => 'tab',
            ],
            [
                'key'   => 'field_agency_url',
                'label' => 'Official Website URL',
                'name'  => 'ws_agency_url',
                'type'  => 'url',
            ],
            [
                'key'          => 'field_agency_reporting_url',
                'label'        => 'Secure Reporting Portal',
                'name'         => 'ws_agency_reporting_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the intake form or hotline page.',
            ],
            [
                'key'   => 'field_agency_phone',
                'label' => 'Whistleblower Hotline',
                'name'  => 'ws_agency_phone',
                'type'  => 'text',
            ],
            [
                'key'          => 'field_agency_confidentiality_notes',
                'label'        => 'Confidentiality & Privacy Notes',
                'name'         => 'ws_agency_confidentiality_notes',
                'type'         => 'textarea',
                'rows'         => 4,
                'instructions' => 'Briefly describe how this agency handles identity protection.',
            ],
            [
                'key'           => 'field_agency_anonymous_allowed',
                'label'         => 'Anonymous Reporting Allowed?',
                'name'          => 'ws_agency_accepts_anonymous',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this agency accepts reports without requiring the reporter to identify themselves.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_agency_reward_program',
                'label'         => 'Reward/Bounty Program Available?',
                'name'          => 'ws_agency_reward_program',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this agency offers financial rewards or bounties to whistleblowers.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            // ── Languages ─────────────────────────────────────────────

            [
                'key'           => 'field_agency_languages',
                'label'         => 'Languages Served',
                'name'          => 'ws_languages',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_languages',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select languages this agency can serve. Check "Additional" if other languages are available — then specify them below.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_agency_additional_languages',
                'label'        => 'Additional Languages',
                'name'         => 'ws_agency_additional_languages',
                'type'         => 'text',
                'instructions' => 'List additional languages not in the checkbox list above (comma-separated). Saving a non-empty value here automatically assigns the "Additional" language term.',
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            // Removed — registered centrally in acf-stamp-fields.php
            // (group_stamp_metadata, menu_order 90).

            // ── Last Verified Date ────────────────────────────────────────
            //
            // Content-owned field — not a stamp. Retained here in the
            // agency's own group.

            [
                'key'            => 'field_agency_last_reviewed',
                'label'          => 'Last Verified Date',
                'name'           => 'ws_agency_last_reviewed',
                'type'           => 'date_picker',
                'instructions'   => 'Update this date each time the agency record is meaningfully revised.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Tab: Plain Language ───────────────────────────────────────
            // Removed — registered centrally in acf-plain-english-fields.php
            // (group_plain_english_metadata, menu_order 85).

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_agencies


// Dynamic choice filter removed (Phase 3.2 / 12.1).
// ws_jurisdiction is now a taxonomy field — ACF loads terms natively.
