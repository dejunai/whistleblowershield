<?php
/**
 * acf-agencies.php
 *
 * Registers ACF Pro fields for the `ws-agencies` CPT.
 *
 * FIELD GROUPS
 * ------------
 * group_ws_agencies  →  Agency Details & Reporting Protocols
 *
 * JURISDICTION FIELD
 * ------------------
 * ws_jx_code is stored as a multi-select of USPS codes (e.g. ['CA','TX','US']).
 * Choices are populated dynamically via the acf/load_field filter so the list
 * always reflects published jurisdiction records without manual maintenance.
 * Field key: field_ws_agencies_jx_codes (unique — avoids conflict with
 * field_ws_jx_code defined on the jurisdiction CPT in acf-jurisdiction.php).
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
 *        ws-agencies added to ws_disclosure_cat object types in
 *        register-taxonomies.php so save_terms functions correctly.
 */

defined( 'ABSPATH' ) || exit;


// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_agencies' );

function ws_register_acf_agencies() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_agencies',
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
            'value'    => 'ws-agencies',
        ] ] ],

        'fields' => [

            // ── Tab: Agency Identity ──────────────────────────────────────

            [
                'key'   => 'tab_agency_identity',
                'label' => 'Agency Identity',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_agency_code',
                'label'        => 'Agency Reference Code',
                'name'         => 'ws_agency_code',
                'type'         => 'text',
                'instructions' => 'Internal slug-safe unique ID — e.g., "osc", "sec-owb", "ny-ag-wb".',
                'required'     => 1,
            ],
            [
                'key'          => 'field_ws_agency_name',
                'label'        => 'Full Agency Name',
                'name'         => 'ws_agency_name',
                'type'         => 'text',
                'required'     => 1,
                'instructions' => 'Example: U.S. Office of Special Counsel',
            ],
            [
                'key'           => 'field_ws_agency_logo',
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
            // Choices are populated dynamically from published jurisdiction
            // records via the acf/load_field filter below — no manual
            // maintenance of this list is required.
            //
            // Field key is field_ws_agencies_jx_codes (not field_ws_jx_code)
            // to avoid collision with the jurisdiction CPT's own code field.

            [
                'key'           => 'field_ws_agencies_jx_codes',
                'label'         => 'Jurisdiction(s)',
                'name'          => 'ws_jx_code',
                'type'          => 'select',
                'instructions'  => 'Select all USPS codes this agency has authority over.',
                'choices'       => [],  // Populated dynamically — see acf/load_field filter below
                'multiple'      => 1,
                'ui'            => 1,
                'ajax'          => 0,  // Not needed — choices loaded server-side on field load
                'return_format' => 'value',
                'allow_null'    => 1,
            ],
            [
                'key'        => 'field_ws_agency_disclosure_type',
                'label'      => 'Disclosure Categories',
                'name'       => 'ws_agency_disclosure_type',
                'type'       => 'taxonomy',
                'taxonomy'   => 'ws_disclosure_cat',
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
                'key'           => 'field_ws_agency_process_type',
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
                'key'   => 'tab_agency_contact',
                'label' => 'Contact & Reporting',
                'type'  => 'tab',
            ],
            [
                'key'   => 'field_ws_agency_url',
                'label' => 'Official Website URL',
                'name'  => 'ws_agency_url',
                'type'  => 'url',
            ],
            [
                'key'          => 'field_ws_agency_reporting_url',
                'label'        => 'Secure Reporting Portal',
                'name'         => 'ws_agency_reporting_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the intake form or hotline page.',
            ],
            [
                'key'   => 'field_ws_agency_phone',
                'label' => 'Whistleblower Hotline',
                'name'  => 'ws_agency_phone',
                'type'  => 'text',
            ],
            [
                'key'          => 'field_ws_agency_confidentiality_notes',
                'label'        => 'Confidentiality & Privacy Notes',
                'name'         => 'ws_agency_confidentiality_notes',
                'type'         => 'textarea',
                'rows'         => 4,
                'instructions' => 'Briefly describe how this agency handles identity protection.',
            ],
            [
                'key'           => 'field_ws_agency_anonymous_allowed',
                'label'         => 'Anonymous Reporting Allowed?',
                'name'          => 'ws_agency_anonymous_allowed',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
            ],
            [
                'key'           => 'field_ws_agency_reward_program',
                'label'         => 'Reward/Bounty Program Available?',
                'name'          => 'ws_agency_reward_program',
                'type'          => 'true_false',
                'ui'            => 1,
                'default_value' => 0,
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────

            [
                'key'   => 'tab_agency_review',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_ws_agency_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_agency_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save.',
                'allow_null'    => 0,
                'multiple'      => 0,
                'return_format' => 'array',
            ],
            [
                'key'            => 'field_ws_agency_last_reviewed',
                'label'          => 'Last Verified Date',
                'name'           => 'ws_agency_last_reviewed',
                'type'           => 'date_picker',
                'display_format' => 'm/d/Y',
                'return_format'  => 'Ymd',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_agencies


// ── Dynamic jurisdiction choices ──────────────────────────────────────────────
//
// Populates the ws_jx_code select with all published jurisdiction records
// each time an agency edit screen loads. Sorted alphabetically by label.
// Keyed by USPS code ("CA"), labeled as "California (CA)".

add_filter( 'acf/load_field/key=field_ws_agencies_jx_codes', 'ws_agencies_load_jx_choices' );

function ws_agencies_load_jx_choices( $field ) {

    $jurisdictions = ws_get_all_jurisdictions();

    if ( empty( $jurisdictions ) ) {
        return $field;
    }

    $choices = [];

    foreach ( $jurisdictions as $jx ) {
        $code = get_post_meta( $jx->ID, 'ws_jx_code', true );
        if ( $code ) {
            $choices[ $code ] = get_the_title( $jx->ID ) . ' (' . $code . ')';
        }
    }

    // Alphabetical by label so the list is easy to scan in the admin UI.
    asort( $choices );

    $field['choices'] = $choices;

    return $field;
}
