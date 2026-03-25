<?php
/**
 * acf-jurisdiction.php
 *
 * Registers Advanced Custom Fields (ACF Pro) used by the `jurisdiction` CPT.
 *
 * PURPOSE
 * -------
 * This field group defines the metadata used to render the jurisdiction
 * header and maintain canonical identifiers for each supported U.S.
 * jurisdiction.
 *
 * Each Jurisdiction record represents one of the supported jurisdictions:
 *
 *   • 50 U.S. States
 *   • Federal Government (US)
 *   • District of Columbia (DC)
 *   • U.S. Territories
 *       - Puerto Rico              (PR)
 *       - Guam                     (GU)
 *       - U.S. Virgin Islands      (VI)
 *       - American Samoa           (AS)
 *       - Northern Mariana Islands (MP)
 *
 * DATA CATEGORIES
 * ---------------
 * 1. Jurisdiction Identity
 *      ws_jurisdiction_class
 *      ws_jurisdiction_name
 *      (Jurisdiction code is now the slug of the assigned ws_jurisdiction taxonomy term)
 *
 * 2. Government Links
 *      government portal (url + label)
 *      executive office (url + label)
 *      whistleblower authority (url + label)
 *      legislature (url + label)
 *
 * 3. Flag Metadata
 *      flag image
 *      attribution
 *      source URL
 *      license
 *
 * 4. Record Management
 *      author             (hidden; set once on first save)
 *      date created       (hidden; set once on first save, local + GMT)
 *      date updated       (refreshed on every save, local + GMT)
 *      last editor        (auto-filled, admin-overridable)
 *
 * 5. Dataset Relationships
 *      Links jurisdiction to its associated legal datasets:
 *      summary, statutes, citations, interpretations.
 *
 * JURISDICTION IDENTITY
 * ---------------------
 * The canonical two-letter code for each jurisdiction is stored as the slug
 * of the assigned ws_jurisdiction taxonomy term (e.g., 'ca', 'tx', 'us').
 * The ws_jx_code text field has been retired; code is now derived from
 * the taxonomy term slug.
 *
 * POST SLUGS (Territories)
 * ------------------------
 * The following slugs are used for territory auto-selection logic:
 *      guam
 *      puerto-rico
 *      us-virgin-islands
 *      american-samoa
 *      northern-mariana-islands
 *
 * NOTES
 * -----
 * Government Links allow flexible labeling across jurisdictions.
 * Examples:
 *      Governor / Mayor
 *      Attorney General / Secretary of Justice / U.S. Office of Special Counsel
 *
 * Whistleblower Authority Label and Legislature Name are auto-selected on
 * first save based on Jurisdiction Class and post slug. Both can be manually
 * overridden after the first save.
 *
 * Record Management fields are read-only in the UI. Author and date created
 * fields are hidden from the editor interface but retained for data integrity.
 * Last Editor is auto-filled on save but can be manually overridden by an
 * administrator to preserve attribution.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 1.0.0  Initial release.
 * 1.8.0  Relationship field post_type filters updated from
 *            jurisdiction-summary/resources/procedures/statutes
 *        to  jx-summary/s/s/jx-statutes
 * 2.1.0  Refactored for ws-core architecture. Added ws_jx_code, legislature,
 *        record management fields (author, date created, date updated,
 *        last editor), tabs, inline field instructions throughout,
 *        auto-selection of Legal Authority Label and Legislature Label
 *        on first save, and PHP 8.0 backstop for str_starts_with.
 * 2.1.1  Schema normalization pass:
 *        - Standardized meta names to ws_jx_* prefix (ws_jurisdiction_* for
 *          Identity tab class/name fields).
 *        - Standardized field keys to field_jx_* pattern (removed ws_ noise).
 *        - Renamed head_of_government → executive for brevity.
 *        - Renamed legal_authority → wb_authority for accuracy (AG is not
 *          always the whistleblower authority).
 *        - Renamed jurisdiction_type → jurisdiction_class for legal tone.
 *        - Renamed flag_attribution_url → flag_source_url for clarity.
 *        - Added ws_jx_related_citations and ws_jx_related_interpretations.
 *        - Removed deprecated jx-resources relationship field.
 *        - Removed redundant Gov URL instructions message block.
 *        - Hidden GMT date fields and author/date_created from editor UI.
 *        - Revised all instructions for lay-editor clarity.
 *        - Shortened select choice keys for cleaner code.
 *        - Updated auto-fill function to match new field names and values.
 * 3.0.0  Architecture refactor (Phase 3.2):
 *        - Removed ws_jx_code text field. Jurisdiction code is now the slug
 *          of the assigned ws_jurisdiction taxonomy term.
 *        Phase 3.6:
 *        - Removed Related Content tab and all ws_jx_related_* relationship
 *          fields (summary, statutes, citations, interpretations). Jurisdiction
 *          scoping is now fully taxonomy-based; admin-relationships.php removed.
 * 3.1.1  Pass 2 ACF audit fixes:
 *        - Corrected 'requied' typo to 'required' on field_jurisdiction_tax
 *          and field_jx_code (fields were silently not required).
 *        - Changed field_create_author from type=text to type=user with
 *          role and return_format=id, matching all other CPT groups.
 * 3.1.2  Pass 3 ACF audit — instructions fixes:
 *        - field_jurisdiction_tax: replaced visible instructions with internal
 *          note (field is hidden; instruction was never displayed).
 *        - field_jx_code: clarified as read-only seeder-populated display field.
 *        - field_create_author label: 'Create Author' → 'Created By' for
 *          consistency with all other CPT groups.
 *        - field_create_author instructions: standardised to 'Stamped
 *          automatically on first save. Read only.'
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jurisdiction_fields' );

function ws_register_acf_jurisdiction_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_jurisdiction_metadata',
        'title'                 => 'Jurisdiction Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jurisdiction',
        ] ] ],

        'fields' => [

			// ────────────────────────────────────────────────────────────────
			// Tab: Identity
			//
			// Core identifiers for each jurisdiction record. ws_jx_code is a
			// legacy display field retained for visual reference only — the
			// canonical jurisdiction identifier is the slug of the assigned
			// ws_jurisdiction taxonomy term. All fields are seeder-populated
			// and locked against manual editing.
			// ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_identity_tab',
                'label' => 'Identity',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jurisdiction_tax',
                'label'         => 'Jurisdiction USPS Code',
                'name'          => 'ws_jurisdiction_term_id',
                'type'          => 'taxonomy',
                'taxonomy'      => WS_JURISDICTION_TAXONOMY,
                'field_type'    => 'select',
                'instructions'  => 'Internal taxonomy field. Seeder-populated. Drives the ws_jurisdiction term assignment for this jurisdiction record.',
				'required'      => 1,
				'add_term'      => 0,
                'save_terms'    => 1,
				'load_terms'    => 1,
                'return_format' => 'id',
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'           => 'field_jx_code',
                'label'         => 'Jurisdiction USPS Code',
                'name'          => 'ws_jx_code',
                'type'          => 'text',
                'instructions'  => 'Read-only display of the canonical USPS code for this jurisdiction. Set by the seeder. Cannot be changed manually.',
                'required'      => 1,
				'wrapper'       => [ 'data-maxlength' => 2, 'width' => '20' ],
            ],

            [
                'key'           => 'field_jurisdiction_class',
                'label'         => 'Jurisdiction Class',
                'name'          => 'ws_jurisdiction_class',
                'type'          => 'select',
                'instructions'  => 'Determines how this jurisdiction is treated throughout the system. Affects default values for executive office, whistleblower authority, and legislature.',
                'required'      => 1,
                'choices'      => [
                    'federal'   => 'Federal',
                    'state'     => 'U.S. State',
                    'territory' => 'U.S. Territory',
                    'district'  => 'District (D.C.)',
                ],
				'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_jurisdiction_name',
                'label'        => 'Jurisdiction Name',
                'name'         => 'ws_jurisdiction_name',
                'type'         => 'text',
                'instructions' => 'Official name displayed to users (e.g., California, District of Columbia, Puerto Rico). Do not include "State of" prefix.',
                'required'     => 1,
                'wrapper'      => [ 'width' => '50' ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Government Leadership URLs
            //
            // External links rendered in the jurisdiction header. Labels are
            // selectable to accommodate naming differences across jurisdictions.
            // Whistleblower Authority and Legislature Name are auto-selected on
            // first save and can be manually corrected afterward.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_gov_urls_tab',
                'label' => 'Government Leadership URLs',
                'type'  => 'tab',
            ],

            // Government Portal

            [
                'key'          => 'field_jx_gov_portal_url',
                'label'        => 'Government Portal URL',
                'name'         => 'ws_jx_gov_portal_url',
                'type'         => 'url',
                'instructions' => 'Link to the jurisdiction\'s main government website (e.g., ca.gov, dc.gov).',
                'placeholder'  => 'https://',
				'wrapper'      => ['width' => '70']
            ],

            [
                'key'           => 'field_jx_gov_portal_label',
                'label'         => 'Government Portal Label',
                'name'          => 'ws_jx_gov_portal_label',
                'type'          => 'text',
                'instructions'  => 'Text shown to users for this link. Include jurisdiction name (e.g., "California State Portal").',
                'default_value' => 'Official Government Portal',
				'wrapper'      => ['width' => '30']
            ],

            // Executive Office

            [
                'key'          => 'field_jx_executive_url',
                'label'        => 'Executive Office URL',
                'name'         => 'ws_jx_executive_url',
                'type'         => 'url',
                'instructions' => 'Official website for this jurisdiction\'s executive office. Leave blank for Federal.',
                'placeholder'  => 'https://',
            	'wrapper'      => ['width' => '70']
            ],

            [
                'key'           => 'field_jx_executive_label',
                'label'         => 'Executive Office Title',
                'name'          => 'ws_jx_executive_label',
                'type'          => 'text',
                'instructions'  => 'Office title of the chief executive. Governor for states and most territories; Mayor for D.C.',
                'default_value' => 'Office of the Governor',
            	'wrapper'      => ['width' => '30']
            ],

            // Whistleblower Authority

            [
                'key'          => 'field_jx_wb_authority_url',
                'label'        => 'Whistleblower Authority URL',
                'name'         => 'ws_jx_wb_authority_url',
                'type'         => 'url',
                'instructions' => 'Website for the office handling whistleblower matters in this jurisdiction.',
                'placeholder'  => 'https://',
            	'wrapper'      => ['width' => '70']
            ],

            [
                'key'           => 'field_jx_wb_authority_label',
                'label'         => 'Whistleblower Authority Office',
                'name'          => 'ws_jx_wb_authority_label',
                'type'          => 'text',
                'instructions'  => 'Primary office for whistleblower protection and enforcement.',
                'default_value' => 'Office of the Attorney General',
            	'wrapper'      => ['width' => '30']
            ],

            // Legislature

            [
                'key'          => 'field_jx_legislature_url',
                'label'        => 'Legislature URL',
                'name'         => 'ws_jx_legislature_url',
                'type'         => 'url',
                'instructions' => 'Official website for the state legislature, territorial assembly, or Congress.',
                'placeholder'  => 'https://',
            	'wrapper'      => ['width' => '70']
            ],

            [
                'key'           => 'field_jx_legislature_label',
                'label'         => 'Legislature Name',
                'name'          => 'ws_jx_legislature_label',
                'type'          => 'text',
                'instructions'  => 'Name of the Jurisdiction\'s legislative body.',
                'default_value' => 'State Legislature',
            	'wrapper'      => ['width' => '30']
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Flag
            //
            // Flag images are sourced from Wikimedia Commons. Attribution,
            // source URL, and license are required for proper crediting.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_jx_flag_tab',
                'label' => 'Flag',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jx_flag',
                'label'         => 'Flag Image',
                'name'          => 'ws_jx_flag',
                'type'          => 'image',
                'instructions'  => 'Upload the official flag image. Source from Wikimedia Commons to ensure proper licensing.',
                'return_format' => 'array',
                'preview_size'  => 'thumbnail',
                'library'       => 'uploadedTo',
				'wrapper'       => ['width' => '30' ]
			],

            [
                'key'          => 'field_jx_flag_attribution',
                'label'        => 'Flag Attribution',
                'name'         => 'ws_jx_flag_attribution',
                'type'         => 'text',
                'instructions' => 'Credit line from Wikimedia Commons. Copy from the file\'s attribution section (e.g., "Devin Cook / Public domain").',
            	'wrapper'      => ['width' => '70' ]
			],

            [
                'key'          => 'field_jx_flag_source_url',
                'label'        => 'Flag Source URL',
                'name'         => 'ws_jx_flag_source_url',
                'type'         => 'url',
                'instructions' => 'Link to the Wikimedia Commons page for this flag image. Used for attribution and license verification.',
            	'wrapper'      => ['width' => '70' ]
			],

            [
                'key'           => 'field_jx_flag_license',
                'label'         => 'Flag License',
                'name'          => 'ws_jx_flag_license',
                'type'          => 'text',
                'instructions'  => 'License from Wikimedia Commons. Most U.S. flags are Public Domain. Check file page if unsure.',
                'default_value' => 'Public Domain',
            	'wrapper'       => ['width' => '30' ]
			],

            // ────────────────────────────────────────────────────────────────
            // Tab: Record Management
            //
            // Author and date created fields are hidden from the editor UI but
            // retained in the database for audit purposes. Date updated and
            // last editor are visible and managed automatically.
            //
            // Last Editor is editable by administrators to allow attribution
            // to be preserved when a minor correction is made by someone other
            // than the credited author.
            //
            // Date fields store values as Y-m-d strings. Local dates respect
            // the timezone configured in WordPress Settings → General.
            // GMT dates record the equivalent UTC value (hidden from UI).
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_record_tab',
                'label' => 'Record Management',
                'type'  => 'tab',
            ],

            // Hidden fields: create_author, date_created, date_created_gmt, last_edited_gmt

            [
                'key'           => 'field_jx_create_author',
                'label'         => 'Created By',
                'name'          => 'ws_auto_create_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on first save. Read only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
                'wrapper'       => [ 'class' => 'hidden' ],
            ],

            [
                'key'          => 'field_jx_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_auto_date_created',
                'type'         => 'text',
                'instructions' => 'Date this record was created. Set automatically.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'          => 'field_date_created_gmt',
                'label'        => 'Date Created (GMT)',
                'name'         => '_ws_auto_date_created_gmt',
                'type'         => 'text',
                'instructions' => 'UTC date this record was created. Set automatically.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'          => 'field_last_edited_gmt',
                'label'        => 'Date Last Edited (GMT)',
                'name'         => '_ws_auto_last_edited_gmt',
                'type'         => 'text',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            // Visible fields: last_edited, last_edited_author
			[
                'key'           => 'field_jx_last_edited_author',
                'label'         => 'Last Editor',
                'name'          => 'ws_auto_last_edited_author',
                'type'          => 'user',
                'instructions'  => 'User who last updated this record. Updated automatically. Admins can change to credit a different contributor.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
            ],
			
            [
                'key'          => 'field_jx_last_edited',
                'label'        => 'Date Last Edited',
                'name'         => 'ws_auto_last_edited',
                'type'         => 'text',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jurisdiction_fields


