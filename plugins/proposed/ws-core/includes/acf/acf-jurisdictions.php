<?php
/**
 * acf-jurisdictions.php
 *
 * ACF Pro field group for the `jurisdiction` CPT.
 * Group key: group_jurisdiction_metadata
 *
 * FIELDS
 * ------
 * Identity tab:
 *   ws_jurisdiction_term_id   Taxonomy field — links post to ws_jurisdiction term (save_terms: 1).
 *   ws_jx_code                Read-only display of the USPS code. Canonical code is the term slug.
 *   ws_jurisdiction_class     Select: state / federal / territory / district.
 *   ws_jurisdiction_name      Display name used in page headings.
 *
 * Government Leadership URLs tab:
 *   ws_jx_gov_portal_url / _label      Main government portal.
 *   ws_jx_executive_url / _label       Governor / mayor / president URL and title.
 *   ws_jx_wb_authority_url / _label    Whistleblower authority office.
 *   ws_jx_legislature_url / _label     Legislature URL and name.
 *   Authority and legislature labels are auto-selected on first save from
 *   jurisdiction class and post slug; both are manually overridable.
 *
 * Flag tab:
 *   ws_jx_flag                Image field (WordPress media library).
 *   ws_jx_flag_attribution    Wikimedia Commons attribution string.
 *   ws_jx_flag_source_url     Canonical Wikimedia SVG URL.
 *   ws_jx_flag_license        License identifier (e.g. "Public Domain").
 *
 * Record Management tab:
 *   _ws_auto_last_edited_gmt  Hidden GMT audit timestamp.
 *   ws_auto_last_edited_author  Last editor (admin-overridable for attribution).
 *   ws_auto_last_edited       Local date of last edit (Y-m-d).
 *   Create authorship fields are omitted — jurisdiction records are seeder-generated.
 *
 * Territory post slugs used by auto-selection logic:
 *   guam, puerto-rico, us-virgin-islands, american-samoa, northern-mariana-islands
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 1.0.0   Initial release.
 * 2.1.0   ws-core refactor: tabs, record management fields, auto-selection logic.
 * 3.0.0   ws_jx_code retired as join key; taxonomy term slug is now canonical.
 *         Related Content tab and ws_jx_related_* fields removed.
 * 3.9.0   Record Management tab trimmed; create authorship fields removed.
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
            // Jurisdiction records are seeder-generated — create_author and
            // date_created are not meaningful and are not tracked here.
            // Matrix-watch handles the audit trail for post-install edits.
            //
            // last_edited and last_edited_author are stamped automatically
            // on every ACF save by admin-hooks.php. Last Editor is editable
            // by administrators to credit a different contributor when needed.
            //
            // GMT date is hidden from the UI but written to the database.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_record_tab',
                'label' => 'Record Management',
                'type'  => 'tab',
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


