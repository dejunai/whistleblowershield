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
 *      ws_jurisdiction_type
 *      ws_jx_code
 *      ws_jurisdiction_name
 *
 * 2. Government Links
 *      official government portal
 *      head of government
 *      legal authority
 *      legislature
 *
 * 3. Flag Metadata
 *      flag image
 *      attribution
 *      source URL
 *      license
 *
 * 4. Record Management
 *      author          (set once on first save)
 *      date created    (set once on first save, local + GMT)
 *      date updated    (refreshed on every save, local + GMT)
 *      last editor     (auto-filled, admin-overridable)
 *
 * 5. Dataset Relationships
 *      Links jurisdiction to its associated legal datasets.
 *
 * INTERNAL IDENTIFIER
 * -------------------
 * ws_jx_code is the canonical two-letter machine identifier used across the plugin.
 *
 * Examples:
 *      CA  = California
 *      TX  = Texas
 *      NY  = New York
 *      US  = Federal Government
 *      DC  = District of Columbia
 *      PR  = Puerto Rico
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
 * Legal Authority Label and Legislature Label are auto-selected on first save
 * based on Jurisdiction Type and post slug. Both can be manually overridden
 * after the first save.
 *
 * Record Management fields are read-only in the UI. Last Editor is the
 * exception — it is auto-filled on save but can be manually overridden
 * by an administrator to preserve attribution.
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 1.8.0  Relationship field post_type filters updated from
 *             jurisdiction-summary/resources/procedures/statutes
 *         to  jx-summary/jx-resources/jx-procedures/jx-statutes
 * 2.1.0  Refactored for ws-core architecture. Added ws_jx_code, legislature,
 *         record management fields (author, date created, date updated,
 *         last editor), tabs, inline field instructions throughout,
 *         auto-selection of Legal Authority Label and Legislature Label
 *         on first save, and PHP 8.0 backstop for str_starts_with.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_jurisdiction_fields' );

function ws_register_acf_jurisdiction_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_jurisdiction',
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
            // Core identifiers for each jurisdiction record. ws_jx_code is the
            // canonical machine identifier used across the plugin. All three
            // fields are required.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_identity',
                'label' => 'Identity',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_jx_type',
                'label'        => 'Jurisdiction Type',
                'name'         => 'ws_jurisdiction_type',
                'type'         => 'select',
                'instructions' => 'Select the category this jurisdiction belongs to.',
                'required'     => 1,
                'choices'      => [
                    'state'     => 'U.S. State',
                    'federal'   => 'Federal',
                    'district'  => 'District (D.C.)',
                    'territory' => 'U.S. Territory',
                ],
                'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_ws_jx_code',
                'label'        => 'Jurisdiction Code',
                'name'         => 'ws_jx_code',
                'type'         => 'text',
                'instructions' => 'Two-letter postal code used as the canonical machine identifier across the plugin. Examples: CA, TX, NY, US, DC, PR.',
                'required'     => 1,
                'maxlength'    => 2,
                'wrapper'      => [ 'width' => '20' ],
            ],

            [
                'key'          => 'field_ws_jurisdiction_name',
                'label'        => 'Jurisdiction Name',
                'name'         => 'ws_jurisdiction_name',
                'type'         => 'text',
                'instructions' => 'Display name used in headings and labels — e.g., California, Federal, Puerto Rico.',
                'required'     => 1,
                'wrapper'      => [ 'width' => '50' ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Government URLs
            //
            // External links rendered in the jurisdiction header. Labels are
            // selectable to accommodate naming differences across jurisdictions.
            // Legal Authority Label and Legislature Label are auto-selected on
            // first save and can be manually corrected afterward.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_gov_urls',
                'label' => 'Government URLs',
                'type'  => 'tab',
            ],

            [
                'key'     => 'field_ws_gov_url_instructions',
                'label'   => 'Instructions',
                'type'    => 'message',
                'message' => '<p>These links appear in the jurisdiction header above the summary. <strong>Government Portal</strong> applies to all jurisdictions. <strong>Head of Government</strong> applies to all except Federal (where the executive leader link is omitted). <strong>Legal Authority</strong> and <strong>Legislature</strong> apply to all jurisdictions. Leave inapplicable fields blank — the display template will suppress empty links automatically.</p>',
            ],

            // Government Portal

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
                'instructions' => 'Link label displayed on the front end — e.g., State of California Official Portal.',
                'default_value' => 'Official Government Portal',
            ],

            // Head of Government
            // Note: leave blank for Federal — suppressed by display template.

            [
                'key'          => 'field_ws_head_url',
                'label'        => 'Head of Government URL',
                'name'         => 'ws_head_of_government_url',
                'type'         => 'url',
                'instructions' => 'Link to the Governor\'s Office (or Mayor\'s Office for D.C.). Leave blank for Federal.',
                'placeholder'  => 'https://',
            ],

            [
                'key'          => 'field_ws_head_label',
                'label'        => 'Head of Government Label',
                'name'         => 'ws_head_of_government_label',
                'type'         => 'select',
                'instructions' => 'Select the official title for the head of this jurisdiction.',
                'choices'      => [
                    'governor' => 'Office of the Governor',
                    'mayor'    => 'Office of the Mayor',
                ],
                'default_value' => 'governor',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // Legal Authority
            // Auto-selected on first save via ws_autofill_jx_record_fields().
            // Can be manually corrected after first save.

            [
                'key'          => 'field_ws_legal_authority_url',
                'label'        => 'Legal Authority URL',
                'name'         => 'ws_legal_authority_url',
                'type'         => 'url',
                'instructions' => 'Attorney General or equivalent. Use U.S. Office of Special Counsel for Federal. Use Secretary of Justice for Puerto Rico. Use Inspector General for D.C.',
            ],

            [
                'key'          => 'field_ws_legal_authority_label',
                'label'        => 'Legal Authority Label',
                'name'         => 'ws_legal_authority_label',
                'type'         => 'select',
                'instructions' => 'Office holding the highest legal authority for whistleblower matters in this jurisdiction. Auto-selected on first save based on Jurisdiction Type.',
                'choices'      => [
                    'attorney'  => 'Office of the Attorney General',
                    'inspector' => 'D.C. Office of the Inspector General',
                    'secretary' => 'Office of the Secretary of Justice',
                    'special'   => 'U.S. Office of Special Counsel',
                ],
                'default_value' => 'attorney',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // Legislature
            // Auto-selected on first save via ws_autofill_jx_record_fields().
            // Can be manually corrected after first save.

            [
                'key'          => 'field_ws_legislature_url',
                'label'        => 'Legislature Website',
                'name'         => 'ws_legislature_url',
                'type'         => 'url',
                'instructions' => 'Official website for this jurisdiction\'s legislative body.',
            ],

            [
                'key'          => 'field_ws_legislature_label',
                'label'        => 'Legislature Label',
                'name'         => 'ws_legislature_label',
                'type'         => 'select',
                'instructions' => 'Name of the legislative body for this jurisdiction. Auto-selected on first save based on Jurisdiction Type and post slug.',
                'choices'      => [
                    'state'    => 'State Legislature',
                    'federal'  => 'United States Congress',
                    'district' => 'Council of the District of Columbia',
                    'guam'     => 'Guam Legislature',
                    'pr'       => 'Legislative Assembly of Puerto Rico',
                    'usvi'     => 'Legislature of the Virgin Islands',
                    'as'       => 'American Samoa Fono',
                    'nmic'     => 'Northern Mariana Islands Commonwealth Legislature',
                ],
                'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Flag
            //
            // Flag images are sourced from Wikimedia Commons. Attribution,
            // source URL, and license are required for proper crediting.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_flag',
                'label' => 'Flag',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_jx_flag',
                'label'         => 'Flag Image',
                'name'          => 'ws_jx_flag',
                'type'          => 'image',
                'instructions'  => 'Upload the flag image sourced from Wikimedia Commons.',
                'return_format' => 'array',
                'preview_size'  => 'thumbnail',
                'library'       => 'all',
            ],

            [
                'key'          => 'field_ws_jx_flag_attribution',
                'label'        => 'Flag Attribution',
                'name'         => 'ws_jx_flag_attribution',
                'type'         => 'text',
                'instructions' => 'Full attribution string from Wikimedia Commons — e.g., "File:Flag of California.svg by Wikimedia Commons contributors".',
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
                'instructions' => 'License type — e.g., CC BY-SA 4.0, Public Domain.',
                'default_value' => 'Public Domain',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Record Management
            //
            // All fields in this tab are read-only — they are managed entirely
            // by ws_autofill_jx_record_fields() on save_post.
            //
            // Exception: ws_jx_last_editor is editable by administrators to
            // allow attribution to be preserved when a minor correction is made
            // by someone other than the credited author.
            //
            // Date fields store values as Y-m-d strings. Local dates respect
            // the timezone configured in WordPress Settings → General.
            // GMT dates record the equivalent UTC value.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_record',
                'label' => 'Record Management',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_jx_author',
                'label'        => 'Author',
                'name'         => 'ws_jx_author',
                'type'         => 'text',
                'instructions' => 'User ID of the WordPress user who created this record. Auto-populated on first save. Read-only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'          => 'field_ws_jx_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_date_created',
                'type'         => 'text',
                'instructions' => 'Local date this record was first created (Y-m-d). Auto-populated on first save. Read-only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'          => 'field_ws_jx_date_created_gmt',
                'label'        => 'Date Created (GMT)',
                'name'         => 'ws_jx_date_created_gmt',
                'type'         => 'text',
                'instructions' => 'UTC date this record was first created (Y-m-d). Auto-populated on first save. Read-only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'          => 'field_ws_jx_date_updated',
                'label'        => 'Date Updated',
                'name'         => 'ws_jx_date_updated',
                'type'         => 'text',
                'instructions' => 'Local date this record was last updated (Y-m-d). Refreshed on every save. Read-only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'          => 'field_ws_jx_date_updated_gmt',
                'label'        => 'Date Updated (GMT)',
                'name'         => 'ws_jx_date_updated_gmt',
                'type'         => 'text',
                'instructions' => 'UTC date this record was last updated (Y-m-d). Refreshed on every save. Read-only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'           => 'field_ws_jx_last_editor',
                'label'         => 'Last Editor',
                'name'          => 'ws_jx_last_editor',
                'type'          => 'user',
                'instructions'  => 'WordPress user who last updated this record. Auto-populated on every save. Administrators may override to preserve attribution.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Related Content
            //
            // Relationship fields linking this jurisdiction to its associated
            // legal dataset CPT records. Each accepts a single post object.
            // Post type slugs use the hyphenated jx- prefix convention.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_related',
                'label' => 'Related Content',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_related_summary',
                'label'        => 'Jurisdiction Summary',
                'name'         => 'ws_related_summary',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Summary entry for this jurisdiction.',
                'post_type'    => [ 'jx-summary' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

            [
                'key'          => 'field_ws_related_procedures',
                'label'        => 'Jurisdiction Procedures',
                'name'         => 'ws_related_procedures',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Procedures entry for this jurisdiction.',
                'post_type'    => [ 'jx-procedure' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

            [
                'key'          => 'field_ws_related_statutes',
                'label'        => 'Jurisdiction Statutes',
                'name'         => 'ws_related_statutes',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Statutes entry for this jurisdiction.',
                'post_type'    => [ 'jx-statute' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

            [
                'key'          => 'field_ws_related_resources',
                'label'        => 'Jurisdiction Resources',
                'name'         => 'ws_related_resources',
                'type'         => 'relationship',
                'instructions' => 'Link the Jurisdiction Resources entry for this jurisdiction.',
                'post_type'    => [ 'jx-resource' ],
                'filters'      => [ 'search' ],
                'max'          => 1,
                'return_format' => 'object',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_jurisdiction_fields


// ════════════════════════════════════════════════════════════════════════════
// Save Post: Auto-populate Record Management and Label Fields
//
// Fires on acf/save_post with priority 5 (before ACF's default priority 10)
// to ensure values are written before ACF finalizes the save.
//
// Handles:
//   - Author            set once on first save
//   - Date Created      set once on first save (local + GMT)
//   - Date Updated      refreshed on every save (local + GMT)
//   - Last Editor       auto-filled on every save; admin-overridable
//   - Legal Auth Label  set once on first save based on type/slug
//   - Legislature Label set once on first save based on type/slug
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/save_post', 'ws_autofill_jx_record_fields', 5 );

function ws_autofill_jx_record_fields( $post_id ) {

    // Only act on jurisdiction CPT records
    if ( get_post_type( $post_id ) !== 'jurisdiction' ) {
        return;
    }

    $current_user_id = get_current_user_id();
    $now_local       = current_time( 'Y-m-d' );       // Respects WP timezone setting
    $now_gmt         = current_time( 'Y-m-d', true ); // UTC equivalent

    // ── Author ───────────────────────────────────────────────────────────────
    // Written once on first save. Never overwritten after initial creation.

    $existing_author = get_field( 'ws_jx_author', $post_id );
    if ( empty( $existing_author ) ) {
        update_field( 'ws_jx_author', $current_user_id, $post_id );
    }

    // ── Date Created (local) ─────────────────────────────────────────────────
    // Written once on first save. Never overwritten after initial creation.

    $existing_created = get_field( 'ws_jx_date_created', $post_id );
    if ( empty( $existing_created ) ) {
        update_field( 'ws_jx_date_created', $now_local, $post_id );
    }

    // ── Date Created (GMT) ───────────────────────────────────────────────────
    // Written once on first save. Never overwritten after initial creation.

    $existing_created_gmt = get_field( 'ws_jx_date_created_gmt', $post_id );
    if ( empty( $existing_created_gmt ) ) {
        update_field( 'ws_jx_date_created_gmt', $now_gmt, $post_id );
    }

    // ── Date Updated (local) ─────────────────────────────────────────────────
    // Refreshed on every save.

    update_field( 'ws_jx_date_updated', $now_local, $post_id );

    // ── Date Updated (GMT) ───────────────────────────────────────────────────
    // Refreshed on every save.

    update_field( 'ws_jx_date_updated_gmt', $now_gmt, $post_id );

    // ── Last Editor ──────────────────────────────────────────────────────────
    // Auto-filled with the current user on every save.
    // Exception: if the current user is an administrator and has explicitly
    // selected a different user in the field UI, that selection is preserved.
    // This allows admins to credit a specific author when making minor corrections.

    $posted_editor    = isset( $_POST['acf']['field_ws_jx_last_editor'] )
                            ? (int) $_POST['acf']['field_ws_jx_last_editor']
                            : 0;
    $current_is_admin = current_user_can( 'administrator' );

    if ( $current_is_admin && $posted_editor && $posted_editor !== $current_user_id ) {
        // Admin explicitly selected a different user — honor the override
        update_field( 'ws_jx_last_editor', $posted_editor, $post_id );
    } else {
        // All other cases — auto-fill with the current user
        update_field( 'ws_jx_last_editor', $current_user_id, $post_id );
    }

    // ── Legal Authority Label ────────────────────────────────────────────────
    // Set once on first save only. Can be manually corrected afterward.
    //
    // Mapping:
    //   district          → inspector  (D.C. Office of the Inspector General)
    //   federal           → special    (U.S. Office of Special Counsel)
    //   slug: puerto-rico → secretary  (Office of the Secretary of Justice)
    //   all others        → attorney   (Office of the Attorney General)

    $existing_legal = get_field( 'ws_legal_authority_label', $post_id );
    if ( empty( $existing_legal ) ) {

        $type = get_field( 'ws_jurisdiction_type', $post_id );
        $slug = get_post_field( 'post_name', $post_id );

        if ( $type === 'district' ) {
            update_field( 'ws_legal_authority_label', 'inspector', $post_id );
        } elseif ( $type === 'federal' ) {
            update_field( 'ws_legal_authority_label', 'special', $post_id );
        } elseif ( ws_slug_starts_with( $slug, 'puerto-rico' ) ) {
            update_field( 'ws_legal_authority_label', 'secretary', $post_id );
        } else {
            // Default for all 50 states and remaining territories
            update_field( 'ws_legal_authority_label', 'attorney', $post_id );
        }
    }

    // ── Legislature Label ────────────────────────────────────────────────────
    // Set once on first save only. Can be manually corrected afterward.
    // States all share 'state'. Federal and D.C. map by type.
    // Territories are differentiated by post slug.
    // Unrecognized territory slugs fall back to 'state' as a safe default.
    //
    // Mapping:
    //   federal                            → federal   (United States Congress)
    //   district                           → district  (Council of the D.C.)
    //   territory + slug: guam             → guam      (Guam Legislature)
    //   territory + slug: puerto-rico      → pr        (Legislative Assembly)
    //   territory + slug: us-virgin-islands → usvi     (Legislature of the V.I.)
    //   territory + slug: american-samoa   → as        (American Samoa Fono)
    //   territory + slug: northern-mariana-islands → nmic
    //   all others / unmatched             → state     (State Legislature)

    $existing_legislature = get_field( 'ws_legislature_label', $post_id );
    if ( empty( $existing_legislature ) ) {

        // Re-use $type and $slug if already retrieved above
        if ( ! isset( $type ) ) {
            $type = get_field( 'ws_jurisdiction_type', $post_id );
        }
        if ( ! isset( $slug ) ) {
            $slug = get_post_field( 'post_name', $post_id );
        }

        if ( $type === 'federal' ) {
            update_field( 'ws_legislature_label', 'federal', $post_id );
        } elseif ( $type === 'district' ) {
            update_field( 'ws_legislature_label', 'district', $post_id );
        } elseif ( $type === 'territory' ) {

            if ( ws_slug_starts_with( $slug, 'guam' ) ) {
                update_field( 'ws_legislature_label', 'guam', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'puerto-rico' ) ) {
                update_field( 'ws_legislature_label', 'pr', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'us-virgin-islands' ) ) {
                update_field( 'ws_legislature_label', 'usvi', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'american-samoa' ) ) {
                update_field( 'ws_legislature_label', 'as', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'northern-mariana-islands' ) ) {
                update_field( 'ws_legislature_label', 'nmic', $post_id );
            } else {
                // Unrecognized territory slug — fall back to state legislature
                update_field( 'ws_legislature_label', 'state', $post_id );
            }

        } else {
            // Default for all 50 states
            update_field( 'ws_legislature_label', 'state', $post_id );
        }
    }

} // end ws_autofill_jx_record_fields


// ════════════════════════════════════════════════════════════════════════════
// Helper: PHP 8.0 Backstop for str_starts_with
//
// str_starts_with() was introduced in PHP 8.0. This helper wraps the native
// function with a function_exists() check and falls back to a substr()
// comparison for environments running PHP < 8.0. Both paths produce
// identical behavior.
//
// @param  string $slug    The post slug to test.
// @param  string $prefix  The prefix to check for.
// @return bool
// ════════════════════════════════════════════════════════════════════════════

function ws_slug_starts_with( $slug, $prefix ) {
    if ( function_exists( 'str_starts_with' ) ) {
        return str_starts_with( $slug, $prefix );
    }
    return substr( $slug, 0, strlen( $prefix ) ) === $prefix;
}
