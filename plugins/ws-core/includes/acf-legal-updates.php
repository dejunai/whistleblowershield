<?php
/**
 * acf-legal-updates.php
 *
 * Registers the ACF Pro field group for the `ws-legal-update` CPT.
 *
 * Fields registered:
 *   ws_legal_update_jurisdiction   (Relationship — multi-select, links to jurisdiction CPT)
 *   ws_legal_update_law_name       (Text)
 *   ws_legal_update_summary        (WYSIWYG)
 *   ws_legal_update_effective_date (Date Picker)
 *   ws_legal_update_source_url     (URL)
 *   ws_legal_update_author         (User — Author role and above, auto-fills current user)
 *
 * v1.9.0 — CPT renamed from `legal-update` to `ws-update`.
 * v1.9.1 — ACF field name prefixes corrected from `ws_update_*` to `ws_legal_update_*`.
 * v1.9.2 — CPT renamed from `ws-update` to `ws-legal-update`. Location rule updated.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_legal_update_fields' );
function ws_register_acf_legal_update_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'      => 'group_ws_legal_update',
        'title'    => 'Legal Update Details',
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-legal-update',
        ] ] ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'fields' => [

            [
                'key'          => 'field_ws_legal_update_jurisdiction',
                'label'        => 'Affected Jurisdiction(s)',
                'name'         => 'ws_legal_update_jurisdiction',
                'type'         => 'relationship',
                'instructions' => 'Select all jurisdictions this legal update affects. Multiple selections allowed.',
                'post_type'    => [ 'jurisdiction' ],
                'filters'      => [ 'search' ],
                'max'          => 0,
                'return_format' => 'object',
            ],
            [
                'key'          => 'field_ws_legal_update_law_name',
                'label'        => 'Law / Statute Name',
                'name'         => 'ws_legal_update_law_name',
                'type'         => 'text',
                'instructions' => 'Name of the law, statute, regulation, or court ruling that changed — e.g., California Labor Code Section 1102.5',
                'required'     => 1,
            ],
            [
                'key'          => 'field_ws_legal_update_summary',
                'label'        => 'Update Summary',
                'name'         => 'ws_legal_update_summary',
                'type'         => 'wysiwyg',
                'instructions' => 'Describe what changed, why it matters, and how it affects whistleblower protections in the affected jurisdiction(s).',
                'required'     => 1,
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload'  => 0,
            ],
            [
                'key'          => 'field_ws_legal_update_effective_date',
                'label'        => 'Effective Date',
                'name'         => 'ws_legal_update_effective_date',
                'type'         => 'date_picker',
                'instructions' => 'The date this legal change takes effect.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 0,
            ],
            [
                'key'          => 'field_ws_legal_update_source_url',
                'label'        => 'Source URL',
                'name'         => 'ws_legal_update_source_url',
                'type'         => 'url',
                'instructions' => 'Link to the official source — legislation text, court ruling, or agency notice.',
            ],
            [
                'key'          => 'field_ws_legal_update_author',
                'label'        => 'Author',
                'name'         => 'ws_legal_update_author',
                'type'         => 'user',
                'instructions' => 'Defaults to the current user. May be changed to any Author, Editor, or Administrator.',
                'role'         => [ 'author', 'editor', 'administrator' ],
                'allow_null'   => 0,
                'multiple'     => 0,
                'return_format' => 'array',
            ],

        ], // end fields
    ] ); // end acf_add_local_field_group
}

// ── Auto-fill: ws_legal_update_author (current user, new posts only) ──────────

add_filter( 'acf/load_value/name=ws_legal_update_author', 'ws_autofill_legal_update_author', 10, 3 );
function ws_autofill_legal_update_author( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = get_current_user_id();
    }
    return $value;
}
