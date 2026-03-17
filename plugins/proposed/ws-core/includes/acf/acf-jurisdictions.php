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
                'key'   => 'field_tab_jx_identity',
                'label' => 'Identity',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_jurisdiction_class',
                'label'         => 'Jurisdiction Class',
                'name'          => 'ws_jurisdiction_class',
                'type'          => 'select',
                'instructions'  => 'Determines how this jurisdiction is treated throughout the system. Affects default values for executive office, whistleblower authority, and legislature.',
                'required'      => 1,
                'choices'       => [
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
                'key'          => 'field_jurisdiction_name',
                'label'        => 'Jurisdiction Name',
                'name'         => 'ws_jurisdiction_name',
                'type'         => 'text',
                'instructions' => 'Official name displayed to users (e.g., California, District of Columbia, Puerto Rico). Do not include "State of" prefix.',
                'required'     => 1,
                'wrapper'      => [ 'width' => '50' ],
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Government URLs
            //
            // External links rendered in the jurisdiction header. Labels are
            // selectable to accommodate naming differences across jurisdictions.
            // Whistleblower Authority and Legislature Name are auto-selected on
            // first save and can be manually corrected afterward.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_tab_jx_gov_urls',
                'label' => 'Government URLs',
                'type'  => 'tab',
            ],

            // Government Portal

            [
                'key'          => 'field_jx_gov_portal_url',
                'label'        => 'Government Portal URL',
                'name'         => 'ws_jx_gov_portal_url',
                'type'         => 'url',
                'instructions' => 'Link to the jurisdiction\'s main government website (e.g., ca.gov, dc.gov).',
            ],

            [
                'key'           => 'field_jx_gov_portal_label',
                'label'         => 'Government Portal Label',
                'name'          => 'ws_jx_gov_portal_label',
                'type'          => 'text',
                'instructions'  => 'Text shown to users for this link. Include jurisdiction name (e.g., "California State Portal").',
                'default_value' => 'Official Government Portal',
            ],

            // Executive Office

            [
                'key'          => 'field_jx_executive_url',
                'label'        => 'Executive Office URL',
                'name'         => 'ws_jx_executive_url',
                'type'         => 'url',
                'instructions' => 'Official website for this jurisdiction\'s executive office. Leave blank for Federal.',
                'placeholder'  => 'https://',
            ],

            [
                'key'           => 'field_jx_executive_label',
                'label'         => 'Executive Office Title',
                'name'          => 'ws_jx_executive_label',
                'type'          => 'select',
                'instructions'  => 'Title of the chief executive. Governor for states and most territories; Mayor for D.C.',
                'choices'       => [
                    'governor' => 'Governor',
                    'mayor'    => 'Mayor',
                ],
                'default_value' => 'governor',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // Whistleblower Authority

            [
                'key'          => 'field_jx_wb_authority_url',
                'label'        => 'Whistleblower Authority URL',
                'name'         => 'ws_jx_wb_authority_url',
                'type'         => 'url',
                'instructions' => 'Website for the office handling whistleblower matters in this jurisdiction. See label field for office type.',
            ],

            [
                'key'           => 'field_jx_wb_authority_label',
                'label'         => 'Whistleblower Authority Office',
                'name'          => 'ws_jx_wb_authority_label',
                'type'          => 'select',
                'instructions'  => 'Primary office for whistleblower protection and enforcement. Auto-selected on first save; can be corrected if needed.',
                'choices'       => [
                    'ag'  => 'Attorney General',
                    'ig'  => 'Inspector General (D.C.)',
                    'soj' => 'Secretary of Justice (PR)',
                    'osc' => 'Office of Special Counsel (Federal)',
                ],
                'default_value' => 'ag',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // Legislature

            [
                'key'          => 'field_jx_legislature_url',
                'label'        => 'Legislature URL',
                'name'         => 'ws_jx_legislature_url',
                'type'         => 'url',
                'instructions' => 'Official website for the state legislature, territorial assembly, or Congress.',
            ],

            [
                'key'           => 'field_jx_legislature_label',
                'label'         => 'Legislature Name',
                'name'          => 'ws_jx_legislature_label',
                'type'          => 'select',
                'instructions'  => 'Name of the legislative body. Auto-selected on first save; can be corrected if needed.',
                'choices'       => [
                    'state'   => 'State Legislature',
                    'congress'=> 'U.S. Congress',
                    'council' => 'D.C. Council',
                    'guam'    => 'Guam Legislature',
                    'pr'      => 'Puerto Rico Legislature',
                    'usvi'    => 'Virgin Islands Legislature',
                    'fono'    => 'American Samoa Fono',
                    'cnmi'    => 'CNMI Legislature',
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
                'key'   => 'field_tab_jx_flag',
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
                'library'       => 'all',
            ],

            [
                'key'          => 'field_jx_flag_attribution',
                'label'        => 'Flag Attribution',
                'name'         => 'ws_jx_flag_attribution',
                'type'         => 'text',
                'instructions' => 'Credit line from Wikimedia Commons. Copy from the file\'s attribution section (e.g., "Devin Cook / Public domain").',
            ],

            [
                'key'          => 'field_jx_flag_source_url',
                'label'        => 'Flag Source URL',
                'name'         => 'ws_jx_flag_source_url',
                'type'         => 'url',
                'instructions' => 'Link to the Wikimedia Commons page for this flag image. Used for attribution and license verification.',
            ],

            [
                'key'           => 'field_jx_flag_license',
                'label'         => 'Flag License',
                'name'          => 'ws_jx_flag_license',
                'type'          => 'text',
                'instructions'  => 'License from Wikimedia Commons. Most U.S. flags are Public Domain. Check file page if unsure.',
                'default_value' => 'Public Domain',
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
                'key'   => 'field_tab_jx_record',
                'label' => 'Record Management',
                'type'  => 'tab',
            ],

            // Hidden fields: author, date_created, date_created_gmt, date_updated_gmt

            [
                'key'          => 'field_jx_author',
                'label'        => 'Author',
                'name'         => 'ws_jx_author',
                'type'         => 'text',
                'instructions' => 'Editor who created this record. Set automatically.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'          => 'field_jx_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_date_created',
                'type'         => 'text',
                'instructions' => 'Date this record was created. Set automatically.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'          => 'field_jx_date_created_gmt',
                'label'        => 'Date Created (GMT)',
                'name'         => 'ws_jx_date_created_gmt',
                'type'         => 'text',
                'instructions' => 'UTC date this record was created. Set automatically.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            // Visible fields: date_updated, last_editor

            [
                'key'          => 'field_jx_date_updated',
                'label'        => 'Date Updated',
                'name'         => 'ws_jx_date_updated',
                'type'         => 'text',
                'instructions' => 'Date of most recent update. Refreshed automatically on save.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

            [
                'key'          => 'field_jx_date_updated_gmt',
                'label'        => 'Date Updated (GMT)',
                'name'         => 'ws_jx_date_updated_gmt',
                'type'         => 'text',
                'instructions' => 'UTC date of most recent update. Refreshed automatically on save.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'class' => 'hidden' ],
            ],

            [
                'key'           => 'field_jx_last_editor',
                'label'         => 'Last Editor',
                'name'          => 'ws_jx_last_editor',
                'type'          => 'user',
                'instructions'  => 'Editor who last updated this record. Updated automatically. Admins can change to credit a different contributor.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
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
//   - Author                set once on first save
//   - Date Created          set once on first save (local + GMT)
//   - Date Updated          refreshed on every save (local + GMT)
//   - Last Editor           auto-filled on every save; admin-overridable
//   - WB Authority Label    set once on first save based on class/slug
//   - Legislature Label     set once on first save based on class/slug
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

    $posted_editor    = isset( $_POST['acf']['field_jx_last_editor'] )
                            ? (int) $_POST['acf']['field_jx_last_editor']
                            : 0;
    $current_is_admin = current_user_can( 'administrator' );

    if ( $current_is_admin && $posted_editor && $posted_editor !== $current_user_id ) {
        // Admin explicitly selected a different user — honor the override
        update_field( 'ws_jx_last_editor', $posted_editor, $post_id );
    } else {
        // All other cases — auto-fill with the current user
        update_field( 'ws_jx_last_editor', $current_user_id, $post_id );
    }

    // ── Whistleblower Authority Label ────────────────────────────────────────
    // Set once on first save only. Can be manually corrected afterward.
    //
    // Mapping:
    //   district          → ig   (Inspector General - D.C.)
    //   federal           → osc  (Office of Special Counsel)
    //   slug: puerto-rico → soj  (Secretary of Justice)
    //   all others        → ag   (Attorney General)

    $existing_wb_authority = get_field( 'ws_jx_wb_authority_label', $post_id );
    if ( empty( $existing_wb_authority ) ) {

        $class = get_field( 'ws_jurisdiction_class', $post_id );
        $slug  = get_post_field( 'post_name', $post_id );

        if ( $class === 'district' ) {
            update_field( 'ws_jx_wb_authority_label', 'ig', $post_id );
        } elseif ( $class === 'federal' ) {
            update_field( 'ws_jx_wb_authority_label', 'osc', $post_id );
        } elseif ( ws_slug_starts_with( $slug, 'puerto-rico' ) ) {
            update_field( 'ws_jx_wb_authority_label', 'soj', $post_id );
        } else {
            // Default for all 50 states and remaining territories
            update_field( 'ws_jx_wb_authority_label', 'ag', $post_id );
        }
    }

    // ── Legislature Label ────────────────────────────────────────────────────
    // Set once on first save only. Can be manually corrected afterward.
    // States all share 'state'. Federal and D.C. map by class.
    // Territories are differentiated by post slug.
    // Unrecognized territory slugs fall back to 'state' as a safe default.
    //
    // Mapping:
    //   federal                             → congress (U.S. Congress)
    //   district                            → council  (D.C. Council)
    //   territory + slug: guam              → guam     (Guam Legislature)
    //   territory + slug: puerto-rico       → pr       (Puerto Rico Legislature)
    //   territory + slug: us-virgin-islands → usvi     (Virgin Islands Legislature)
    //   territory + slug: american-samoa    → fono     (American Samoa Fono)
    //   territory + slug: northern-mariana-islands → cnmi (CNMI Legislature)
    //   all others / unmatched              → state    (State Legislature)

    $existing_legislature = get_field( 'ws_jx_legislature_label', $post_id );
    if ( empty( $existing_legislature ) ) {

        // Re-use $class and $slug if already retrieved above
        if ( ! isset( $class ) ) {
            $class = get_field( 'ws_jurisdiction_class', $post_id );
        }
        if ( ! isset( $slug ) ) {
            $slug = get_post_field( 'post_name', $post_id );
        }

        if ( $class === 'federal' ) {
            update_field( 'ws_jx_legislature_label', 'congress', $post_id );
        } elseif ( $class === 'district' ) {
            update_field( 'ws_jx_legislature_label', 'council', $post_id );
        } elseif ( $class === 'territory' ) {

            if ( ws_slug_starts_with( $slug, 'guam' ) ) {
                update_field( 'ws_jx_legislature_label', 'guam', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'puerto-rico' ) ) {
                update_field( 'ws_jx_legislature_label', 'pr', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'us-virgin-islands' ) ) {
                update_field( 'ws_jx_legislature_label', 'usvi', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'american-samoa' ) ) {
                update_field( 'ws_jx_legislature_label', 'fono', $post_id );
            } elseif ( ws_slug_starts_with( $slug, 'northern-mariana-islands' ) ) {
                update_field( 'ws_jx_legislature_label', 'cnmi', $post_id );
            } else {
                // Unrecognized territory slug — fall back to state legislature
                update_field( 'ws_jx_legislature_label', 'state', $post_id );
            }

        } else {
            // Default for all 50 states
            update_field( 'ws_jx_legislature_label', 'state', $post_id );
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