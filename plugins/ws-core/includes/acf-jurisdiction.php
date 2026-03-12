<?php
/**
 * acf-jurisdiction.php
 *
 * Registers the ACF Pro field group for the `jurisdiction` CPT.
 *
 * Fields registered:
 *   Identity:        ws_jurisdiction_name, ws_jurisdiction_type,
 *                    ws_jurisdiction_flag, ws_jx_flag_attribution,
 *                    ws_jx_flag_attribution_url, ws_jx_flag_license
 *
 *   Government URLs: ws_gov_portal_url, ws_gov_portal_label,
 *                    ws_head_url, ws_head_label,
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
                'key'          => 'field_ws_jx_flag_attribution',
                'label'        => 'Flag Attribution',
                'name'         => 'ws_jx_flag_attribution',
                'type'         => 'text',
                'instructions' => 'Full attribution string from Wikimedia Commons — e.g., "File:Flag of California.svg by Wikimedia Commons contributors"',
            ],
            [
                'key'          => 'field_ws_jx_flag_attribution_url',
                'label'        => 'Flag Attribution URL',
                'name'         => 'ws_jx_flag_attribution_url',
                'type'         => 'url',
                'instructions' => 'Direct link to the Wikimedia Commons file page for this flag.',
            ],
            [
                'key'          => 'field_ws_jx_flag_license',
                'label'        => 'Flag License',
                'name'         => 'ws_jx_flag_license',
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
                'message' => '<p>These links appear in the jurisdiction header above the summary. <strong>Government Portal</strong> applies to all jurisdictions. <strong>Head of Government</strong> applies to all except Federal (where the executive leader link is omitted). <strong>Legal Authority</strong> applies to all jurisdictions. Leave inapplicable fields blank — the display template will suppress empty links automatically.</p>',
            ],
            [
                'key'          => 'field_ws_gov_portal_url',
                'label'        => 'Government Portal URL',
                'name'         => 'ws_gov_portal_url',
                'type'         => 'url',
                'instructions' => 'Official Government Portal — e.g., https://www.ca.gov',
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
				'key'          => 'field_ws_head_of_government_url',
				'label'        => 'Head of Government URL',
				'name'         => 'ws_head_of_government_url',
				'type'         => 'url',
				'instructions' => 'Link to the Governor’s Office (or Mayor’s Office for D.C.). Do not fill for Federal',
				'placeholder'  => 'https://',
			],
			[
				'key'          => 'field_ws_head_of_government_label',
				'label'        => 'Head of Government Label',
				'name'         => 'ws_head_of_government_label',
				'type'         => 'select',
				'instructions' => 'Select the Official Title for the Head of this Jurisdiction.',
				'choices'      => [
					'governor' => 'Office of the Governor',
					'mayor'    => 'Office of the Mayor',
				],
				'default_value' => 'governor',
				'allow_null'    => 0,
                'ui'            => 1,
			],
            [
                'key'          => 'field_ws_legal_authority_url',
                'label'        => 'Legal Authority URL',
                'name'         => 'ws_legal_authority_url',
                'type'         => 'url',
                'instructions' => 'Attorney General or equivalent. Use U.S. Special Counsel for Federal. Use Secretary of Justice for Puerto Rico. Use Inspector General for D.C.',
            ],
            [
                'key'          => 'field_ws_legal_authority_label',
                'label'        => 'Legal Authority Label',
                'name'         => 'ws_legal_authority_label',
                'type'         => 'select',
                'instructions' => 'Office of... the selected the official for the highest legal office in this jurisdiction (with respect to whistleblowers, if any).',
				'choices'      => [
					'attorney'   => 'Office of the Attorney General',
					'inspector'  => 'D.C. Office of the Inspector General',
					'secretary'  => 'Office of the Secretary of Justice',
					'special'    => 'U.S. Office of Special Counsel',
				],
				'default_value' => 'attorney',
				'allow_null'    => 0,
                'ui'            => 1,               
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
/**
 * Auto-select Legal Authority Label based on Jurisdiction Type & Slug.
 */
add_filter('acf/load_value/name=ws_legal_authority_label', 'ws_sync_legal_authority_label', 10, 3);
function ws_sync_legal_authority_label($value, $post_id, $field) {
    $type = get_field('ws_jurisdiction_type', $post_id);
    $slug = get_post_field('post_name', $post_id);

    // DC: Primary whistleblower authority is the OIG
    if ( $type === 'district' ) {
        return 'inspector';
    }

    // Federal: OSC is the primary protection agency
    if ( $type === 'federal' ) {
        return 'special';
    }
	
    // Puerto Rico: OSC is the primary protection agency
	if (str_starts_with($slug, 'puerto-rico')) {
        return 'secretary';
    }

    // Default for the 50 States & 4 Territories
    return 'attorney';
}
