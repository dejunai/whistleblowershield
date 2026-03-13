<?php
/**
 * acf-resources.php
 *
 * Registers the ACF Pro field group for the `jx-resources` CPT.
 *
 * The jx-resources CPT represents external organizations and resources
 * that are useful to whistleblowers within a given jurisdiction — e.g.,
 * government agencies, legal aid organizations, and recognized advocacy groups.
 *
 * Each resource record attaches to a parent jurisdiction via an ACF
 * relationship field, consistent with the jurisdiction-centric data model
 * used throughout ws-core.
 *
 * Per the data integrity rules documented in ws-core-data-integrity-rules.md:
 *   - Resources should only include credible organizations.
 *   - Resources should not include promotional or commercial content.
 *   - Accepted resource types: government agencies, legal aid organizations,
 *     recognized advocacy groups.
 *
 * Fields registered:
 *   ws_resource_jurisdiction     (Relationship → jurisdiction, required)
 *   ws_resource_organization     (Text, required)
 *   ws_resource_type             (Select: government | legal_aid | advocacy |
 *                                 bar_association | academic | other)
 *   ws_resource_url              (URL, required)
 *   ws_resource_description      (Textarea)
 *   ws_resource_phone            (Text — optional contact number)
 *   ws_resource_notes            (Textarea — internal notes only)
 *   ws_resourse_post_author      (User - selectable, defaults to current user)
 *
 * Added in v2.0.0. The jx-resources CPT was registered in v1.8.0 but had
 * no ACF field group until this release.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_resource_fields' );
function ws_register_acf_resource_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'      => 'group_ws_resource',
        'title'    => 'Resource Details',
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-resources',
        ] ] ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'fields' => [

            // ── Jurisdiction ──────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_jurisdiction',
                'label'        => 'Jurisdiction',
                'name'         => 'ws_resource_jurisdiction',
                'type'         => 'relationship',
                'instructions' => 'Select the jurisdiction this resource applies to. Every resource must reference a jurisdiction. For federal-level resources, select the Federal jurisdiction.',
                'required'     => 1,
                'post_type'    => [ 'jurisdiction' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

            // ── Organization Name ─────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_organization',
                'label'        => 'Organization Name',
                'name'         => 'ws_resource_organization',
                'type'         => 'text',
                'instructions' => 'Full official name of the organization providing this resource — e.g., Government Accountability Project, National Whistleblower Center, California Department of Justice. Use the organization\'s own preferred name.',
                'required'     => 1,
            ],

            // ── Resource Type ─────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_type',
                'label'        => 'Resource Type',
                'name'         => 'ws_resource_type',
                'type'         => 'select',
                'instructions' => 'Select the category that best describes this organization. Only credible, non-commercial organizations should be listed.',
                'required'     => 1,
                'choices'      => [
                    'government'      => 'Government Agency',
                    'legal_aid'       => 'Legal Aid / Pro Bono',
                    'advocacy'        => 'Advocacy Organization',
                    'bar_association' => 'Bar Association',
                    'academic'        => 'Academic / Research Institution',
                    'other'           => 'Other',
                ],
                'default_value' => 'advocacy',
                'allow_null'    => 0,
                'ui'            => 1,
            ],

            // ── Resource URL ──────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_url',
                'label'        => 'Resource URL',
                'name'         => 'ws_resource_url',
                'type'         => 'url',
                'instructions' => 'The primary official URL for this resource. Link to the organization\'s homepage or the specific page most relevant to whistleblowers. Verify the link is active before saving.',
                'required'     => 1,
                'placeholder'  => 'https://',
            ],

            // ── Description ───────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_description',
                'label'        => 'Description',
                'name'         => 'ws_resource_description',
                'type'         => 'textarea',
                'instructions' => 'A brief, factual description of what this organization does and how it helps whistleblowers. Write in plain language. 2–4 sentences recommended. This may be displayed publicly.',
                'rows'         => 4,
            ],

            // ── Phone ─────────────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_phone',
                'label'        => 'Phone Number',
                'name'         => 'ws_resource_phone',
                'type'         => 'text',
                'instructions' => 'Optional. Official contact number for this resource, if publicly available — e.g., a whistleblower hotline or intake line. Use format: (555) 555-5555 or 1-800-555-5555.',
                'placeholder'  => '(555) 555-5555',
            ],

            // ── Internal Notes ────────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_notes',
                'label'        => 'Internal Notes',
                'name'         => 'ws_resource_notes',
                'type'         => 'textarea',
                'instructions' => 'Internal editorial notes — not displayed publicly. Use to document verification status, last link check date, or other editorial observations.',
                'rows'         => 3,
            ],
			
			// ── Post Author ─────────────────────────────────────────

            [
                'key'          => 'field_ws_resource_post_author',
                'label'        => 'Post Author',
                'name'         => 'ws_resource_post_author',
                'type'         => 'user',
                'instructions' => 'Credited author displayed on the front end. Defaults to the current user. May be changed to any registered user with Author role or above.',
                'role'         => [ 'author', 'editor', 'administrator' ],
                'allow_null'   => 0,
                'multiple'     => 0,
                'return_format' => 'array',
            ],

            

        ], // end fields
    ] ); // end acf_add_local_field_group
}
// ── Auto-fill: ws_resource_post_author (current user, new posts only) ───────────────────────

add_filter( 'acf/load_value/name=ws_resource_post_author', 'ws_autofill_resource_post_author', 10, 3 );
function ws_autofill_resource_post_author( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = get_current_user_id();
    }
    return $value;
}
