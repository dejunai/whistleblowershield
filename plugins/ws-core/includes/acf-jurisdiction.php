<?php
/**
 * acf-jurisdiction.php
 *
 * Registers the ACF Pro field group for the `jurisdiction` CPT.
 *
 * Fields registered:
 *   Identity:        ws_jurisdiction_name, ws_jurisdiction_type,
 *                    ws_jurisdiction_flag, ws_flag_attribution,
 *                    ws_flag_attribution_url, ws_flag_license
 *
 *   Government URLs: ws_gov_portal_url, ws_gov_portal_label,
 *                    ws_governor_url, ws_governor_label,
 *                    ws_mayor_url, ws_mayor_label,
 *                    ws_legal_authority_url, ws_legal_authority_label
 *
 *   Relationships:   ws_related_summary, ws_related_resources,
 *                    ws_related_procedures, ws_related_statutes
 *
 * v1.8.0 change: Relationship field post_type filters updated from
 *   jurisdiction-summary/resources/procedures/statutes
 *   to jx-summary/jx-resources/jx-procedures/jx-statutes
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jurisdiction_fields' );
function ws_register_acf_jurisdiction_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'      => 'group_ws_jurisdiction',
        'title'    => 'Jurisdiction Details',
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jurisdiction',
        ] ] ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'fields' => [

            // ── Tab: Identity ─────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_identity',
                'label' => 'Identity',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_jurisdiction_name',
                'label'        => 'Jurisdiction Name',
                'name'         => 'ws_jurisdiction_name',
                'type'         => 'text',
                'instructions' => 'Display name — e.g., California, Federal, Puerto Rico',
                'required'     => 1,
            ],
            [
                'key'          => 'field_ws_jurisdiction_type',
                'label'        => 'Jurisdiction Type',
                'name'         => 'ws_jurisdiction_type',
                'type'         => 'select',
                'instructions' => 'Select the category this jurisdiction belongs to.',
                'required'     => 1,
                'choices'      => [
                    'state'     => 'U.S. State',
                    'federal'   => 'Federal',
                    'territory' => 'U.S. Territory',
                    'district'  => 'District (D.C.)',
                ],
                'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
            ],
            [
                'key'          => 'field_ws_jurisdiction_flag',
                'label'        => 'Flag Image',
                'name'         => 'ws_jurisdiction_flag',
                'type'         => 'image',
                'instructions' => 'Upload the flag image sourced from Wikimedia Commons.',
                'return_format' => 'array',
                'preview_size'  => 'thumbnail',
                'library'       => 'all',
            ],
            [
                'key'          => 'field_ws_flag_attribution',
                'label'        => 'Flag Attribution',
                'name'         => 'ws_flag_attribution',
                'type'         => 'text',
                'instructions' => 'Full attribution string from Wikimedia Commons — e.g., "File:Flag of California.svg by Wikimedia Commons contributors"',
            ],
            [
                'key'          => 'field_ws_flag_attribution_url',
                'label'        => 'Flag Attribution URL',
                'name'         => 'ws_flag_attribution_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the Wikimedia Commons file page for this flag.',
            ],
            [
                'key'          => 'field_ws_flag_license',
                'label'        => 'Flag License',
                'name'         => 'ws_flag_license',
                'type'         => 'text',
                'instructions' => 'License type — e.g., CC BY-SA 4.0, Public Domain',
                'default_value' => 'Public Domain',
            ],

            // ── Tab: Government URLs ──────────────────────────────────────

            [
                'key'   => 'field_ws_tab_gov_urls',
                'label' => 'Government URLs',
                'type'  => 'tab',
            ],
            [
                'key'   => 'field_ws_gov_url_instructions',
                'label' => 'Instructions',
                'type'  => 'message',
                'message' => '<p>These links appear in the jurisdiction header above the summary. <strong>Government Portal</strong> applies to all jurisdictions. <strong>Governor</strong> applies to states and territories. <strong>Mayor</strong> applies to the District of Columbia only. <strong>Legal Authority</strong> applies to all except Federal (where the executive leader link is omitted). Leave inapplicable fields blank — the display template will suppress empty links automatically.</p>',
            ],
            [
                'key'          => 'field_ws_gov_portal_url',
                'label'        => 'Government Portal URL',
                'name'         => 'ws_gov_portal_url',
                'type'         => 'url',
                'instructions' => 'Official government portal — e.g., https://www.ca.gov',
            ],
            [
                'key'          => 'field_ws_gov_portal_label',
                'label'        => 'Government Portal Label',
                'name'         => 'ws_gov_portal_label',
                'type'         => 'text',
                'instructions' => 'Link label — e.g., State of California Official Portal',
                'default_value' => 'Official Government Portal',
            ],
            [
                'key'          => 'field_ws_governor_url',
                'label'        => "Governor's URL",
                'name'         => 'ws_governor_url',
                'type'         => 'url',
                'instructions' => 'Governor\'s official page. Use for states and territories only. Leave blank for Federal and D.C.',
            ],
            [
                'key'          => 'field_ws_governor_label',
                'label'        => "Governor's Label",
                'name'         => 'ws_governor_label',
                'type'         => 'text',
                'instructions' => 'e.g., Office of the Governor of California',
                'default_value' => 'Office of the Governor',
            ],
            [
                'key'          => 'field_ws_mayor_url',
                'label'        => "Mayor's URL",
                'name'         => 'ws_mayor_url',
                'type'         => 'url',
                'instructions' => 'Mayor\'s official page. Use for District of Columbia only. Leave blank for all other jurisdictions.',
            ],
            [
                'key'          => 'field_ws_mayor_label',
                'label'        => "Mayor's Label",
                'name'         => 'ws_mayor_label',
                'type'         => 'text',
                'instructions' => 'e.g., Office of the Mayor of the District of Columbia',
                'default_value' => 'Office of the Mayor',
            ],
            [
                'key'          => 'field_ws_legal_authority_url',
                'label'        => 'Legal Authority URL',
                'name'         => 'ws_legal_authority_url',
                'type'         => 'url',
                'instructions' => 'Attorney General or equivalent. Leave blank for Federal (D.O.J. is handled separately). Territory examples: Puerto Rico = Secretary of Justice. D.C. = Office of the Inspector General.',
            ],
            [
                'key'          => 'field_ws_legal_authority_label',
                'label'        => 'Legal Authority Label',
                'name'         => 'ws_legal_authority_label',
                'type'         => 'text',
                'instructions' => 'e.g., California Attorney General / D.C. Office of the Inspector General',
                'default_value' => 'Office of the Attorney General',
            ],

            // ── Tab: Related Content ──────────────────────────────────────

            [
                'key'   => 'field_ws_tab_related',
                'label' => 'Related Content',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_related_summary',
                'label'        => 'Related Summary',
                'name'         => 'ws_related_summary',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Summary entry for this jurisdiction.',
                'post_type'    => [ 'jx-summary' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],
            [
                'key'          => 'field_ws_related_resources',
                'label'        => 'Related Resources',
                'name'         => 'ws_related_resources',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Resources entry (future).',
                'post_type'    => [ 'jx-resources' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],
            [
                'key'          => 'field_ws_related_procedures',
                'label'        => 'Related Procedures',
                'name'         => 'ws_related_procedures',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Procedures entry (future).',
                'post_type'    => [ 'jx-procedures' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],
            [
                'key'          => 'field_ws_related_statutes',
                'label'        => 'Related Statutes',
                'name'         => 'ws_related_statutes',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Statutes entry (future).',
                'post_type'    => [ 'jx-statutes' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

        ], // end fields
    ] ); // end acf_add_local_field_group
}
