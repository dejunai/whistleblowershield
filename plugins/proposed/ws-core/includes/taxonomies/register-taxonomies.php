<?php
/**
 * register-taxonomies.php — Registers all ws-core taxonomies and seeds initial terms.
 *
 * @package WhistleblowerShield
 * @since   2.1.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 2.1.0   Initial: ws_disclosure_type.
 * 2.3.1   ws_process_type added.
 * 2.4.0   ws_coverage_scope, ws_retaliation_forms, ws_languages, ws_case_stage added.
 * 3.0.0   ws_jurisdiction registered; all gates migrated to Unified Option-Gate Method.
 * 3.1.0   Taxonomy rename pass: ws_protected_class, ws_adverse_action_types, ws_remedies,
 *         ws_disclosure_targets, ws_fee_shifting. ws_bulk_insert_hierarchical() added.
 * 3.2.0   ws_employer_defense added (jx-statute).
 * 3.3.0   ws_aorg_type added (ws-assist-org).
 * 3.6.0   National Security parent + 3 children added to ws_disclosure_type.
 * 3.7.0   ws_employment_sector added. Deprecated taxonomies removed.
 * 3.8.1   ws_seed_disclosure_taxonomy() refactored to ws_bulk_insert_hierarchical().
 * 3.9.0   ws-ag-procedure added to ws_jurisdiction and ws_disclosure_type object_types.
 * 3.10.0  ws_procedure_type added (ws-ag-procedure). Replaces ws_proc_type ACF select.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// TAXONOMY REGISTRATION
// ════════════════════════════════════════════════════════════════════════════

/**
 * Register all taxonomies for the WhistleblowerShield Core.
 */
function ws_register_taxonomies() {

    // ── 1. Disclosure Categories ──────────────────────────────────────────
    //
    // Bug #9 fix: 'ws-assist-org' added to object types so that
    // save_terms fires correctly on assist-org edit screens.

    if ( ! taxonomy_exists( 'ws_disclosure_type' ) ) {
        register_taxonomy(
            'ws_disclosure_type',
            [ 'jx-statute', 'jx-citation', 'ws-agency', 'ws-ag-procedure', 'ws-assist-org' ],
            [
                'label'             => 'Disclosure Categories',
                'labels'            => [
                    'name'              => 'Disclosure Categories',
                    'singular_name'     => 'Disclosure Category',
                    'search_items'      => 'Search Categories',
                    'all_items'         => 'All Categories',
                    'parent_item'       => 'Parent Category',
                    'parent_item_colon' => 'Parent Category:',
                    'edit_item'         => 'Edit Category',
                    'update_item'       => 'Update Category',
                    'add_new_item'      => 'Add New Category',
                    'new_item_name'     => 'New Disclosure Category Name',
                    'menu_name'         => 'Disclosure Categories',
                ],
                'public'            => true,
                'hierarchical'      => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'rewrite'           => [ 'slug' => 'disclosure' ],
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 2. Process Types ──────────────────────────────────────────────────

    if ( ! taxonomy_exists( 'ws_process_type' ) ) {
        register_taxonomy(
            'ws_process_type',
            [ 'jx-statute', 'ws-agency', 'ws-assist-org', 'jx-interpretation' ],
            [
                'label'             => 'Process Types',
                'labels'            => [
                    'name'              => 'Process Types',
                    'singular_name'     => 'Process Type',
                    'search_items'      => 'Search Process Types',
                    'all_items'         => 'All Process Types',
                    'edit_item'         => 'Edit Process Type',
                    'update_item'       => 'Update Process Type',
                    'add_new_item'      => 'Add New Process Type',
                    'new_item_name'     => 'New Process Type Name',
                    'menu_name'         => 'Process Types',
                ],
                'public'            => true,
                'hierarchical'      => false,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'rewrite'           => [ 'slug' => 'process-type' ],
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 3. Remedies ───────────────────────────────────────────────────────
    //
    // Renamed from ws_remedy_type → ws_remedies (3.1.0).

    if ( ! taxonomy_exists( 'ws_remedies' ) ) {
        register_taxonomy(
            'ws_remedies',
            [ 'jx-statute' ],
            [
                'label'             => 'Remedies',
                'labels'            => [
                    'name'              => 'Remedies',
                    'singular_name'     => 'Remedy',
                    'search_items'      => 'Search Remedies',
                    'all_items'         => 'All Remedies',
                    'edit_item'         => 'Edit Remedy',
                    'update_item'       => 'Update Remedy',
                    'add_new_item'      => 'Add New Remedy',
                    'new_item_name'     => 'New Remedy Name',
                    'menu_name'         => 'Remedies',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 4. Protected Class ────────────────────────────────────────────────
    //
    // Renamed from ws_coverage_scope → ws_protected_class (3.1.0).
    // Converted to hierarchical to support employee type groupings.

    if ( ! taxonomy_exists( 'ws_protected_class' ) ) {
        register_taxonomy(
            'ws_protected_class',
            [ 'jx-statute' ],
            [
                'label'             => 'Protected Class',
                'labels'            => [
                    'name'              => 'Protected Classes',
                    'singular_name'     => 'Protected Class',
                    'search_items'      => 'Search Protected Classes',
                    'all_items'         => 'All Protected Classes',
                    'parent_item'       => 'Parent Class',
                    'parent_item_colon' => 'Parent Class:',
                    'edit_item'         => 'Edit Protected Class',
                    'update_item'       => 'Update Protected Class',
                    'add_new_item'      => 'Add New Protected Class',
                    'new_item_name'     => 'New Protected Class Name',
                    'menu_name'         => 'Protected Classes',
                ],
                'public'            => false,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 5. Adverse Action Types ───────────────────────────────────────────
    //
    // Renamed from ws_retaliation_forms → ws_adverse_action_types (3.1.0).
    // Aligns with JSON field name adverse_action; cleaner legal terminology.

    if ( ! taxonomy_exists( 'ws_adverse_action_types' ) ) {
        register_taxonomy(
            'ws_adverse_action_types',
            [ 'jx-statute' ],
            [
                'label'             => 'Adverse Action Types',
                'labels'            => [
                    'name'              => 'Adverse Action Types',
                    'singular_name'     => 'Adverse Action Type',
                    'search_items'      => 'Search Adverse Action Types',
                    'all_items'         => 'All Adverse Action Types',
                    'edit_item'         => 'Edit Adverse Action Type',
                    'update_item'       => 'Update Adverse Action Type',
                    'add_new_item'      => 'Add New Adverse Action Type',
                    'new_item_name'     => 'New Adverse Action Type Name',
                    'menu_name'         => 'Adverse Action Types',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 6. Languages ──────────────────────────────────────────────────────
    //
    // Bug #2 fix: 'assist-org' corrected to 'ws-assist-org'.

    if ( ! taxonomy_exists( 'ws_languages' ) ) {
        register_taxonomy(
            'ws_languages',
            [ 'ws-agency', 'ws-assist-org' ],
            [
                'label'             => 'Languages',
                'labels'            => [
                    'name'              => 'Languages',
                    'singular_name'     => 'Language',
                    'search_items'      => 'Search Languages',
                    'all_items'         => 'All Languages',
                    'edit_item'         => 'Edit Language',
                    'update_item'       => 'Update Language',
                    'add_new_item'      => 'Add New Language',
                    'new_item_name'     => 'New Language Name',
                    'menu_name'         => 'Languages',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 7. Case Stage ─────────────────────────────────────────────────────
    //
    // Bug #2 fix: 'assist-org' corrected to 'ws-assist-org'.

    if ( ! taxonomy_exists( 'ws_case_stage' ) ) {
        register_taxonomy(
            'ws_case_stage',
            [ 'ws-assist-org' ],
            [
                'label'             => 'Case Stages',
                'labels'            => [
                    'name'              => 'Case Stages',
                    'singular_name'     => 'Case Stage',
                    'search_items'      => 'Search Case Stages',
                    'all_items'         => 'All Case Stages',
                    'edit_item'         => 'Edit Case Stage',
                    'update_item'       => 'Update Case Stage',
                    'add_new_item'      => 'Add New Case Stage',
                    'new_item_name'     => 'New Case Stage Name',
                    'menu_name'         => 'Case Stages',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 8. Jurisdiction ───────────────────────────────────────────────────
    //
    // Replaces ws_jx_code post meta as the jurisdiction join mechanism.
    // Private taxonomy — terms are canonical USPS-code slugs (e.g. 'us', 'ca', 'tx').
    // Terms are seeded in jurisdiction-matrix.php via ws_seeded_jurisdiction_taxonomy gate.

    if ( ! taxonomy_exists( WS_JURISDICTION_TAXONOMY ) ) {
        register_taxonomy(
            WS_JURISDICTION_TAXONOMY,
            [ 'jurisdiction', 'jx-statute', 'jx-summary', 'jx-citation', 'jx-interpretation', 'ws-agency', 'ws-ag-procedure', 'ws-assist-org' ],
            [
                'label'             => 'Jurisdictions',
                'labels'            => [
                    'name'              => 'Jurisdictions',
                    'singular_name'     => 'Jurisdiction',
                    'search_items'      => 'Search Jurisdictions',
                    'all_items'         => 'All Jurisdictions',
                    'edit_item'         => 'Edit Jurisdiction',
                    'update_item'       => 'Update Jurisdiction',
                    'add_new_item'      => 'Add New Jurisdiction',
                    'new_item_name'     => 'New Jurisdiction Name',
                    'menu_name'         => 'Jurisdictions',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 9. Disclosure Targets ─────────────────────────────────────────────
    //
    // New in 3.1.0. Describes who the disclosure was made to in order for
    // protection to apply. Hierarchical — grouped by reporting channel type.
    // Applied to jx-statute and ws-assist-org.

    if ( ! taxonomy_exists( 'ws_disclosure_targets' ) ) {
        register_taxonomy(
            'ws_disclosure_targets',
            [ 'jx-statute', 'ws-assist-org' ],
            [
                'label'             => 'Disclosure Targets',
                'labels'            => [
                    'name'              => 'Disclosure Targets',
                    'singular_name'     => 'Disclosure Target',
                    'search_items'      => 'Search Disclosure Targets',
                    'all_items'         => 'All Disclosure Targets',
                    'parent_item'       => 'Parent Target',
                    'parent_item_colon' => 'Parent Target:',
                    'edit_item'         => 'Edit Disclosure Target',
                    'update_item'       => 'Update Disclosure Target',
                    'add_new_item'      => 'Add New Disclosure Target',
                    'new_item_name'     => 'New Disclosure Target Name',
                    'menu_name'         => 'Disclosure Targets',
                ],
                'public'            => false,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 10. Fee Shifting ──────────────────────────────────────────────────
    //
    // New in 3.1.0. Flat taxonomy describing the fee shifting rule that
    // applies to enforcement of a statute. Applied to jx-statute only.

    if ( ! taxonomy_exists( 'ws_fee_shifting' ) ) {
        register_taxonomy(
            'ws_fee_shifting',
            [ 'jx-statute' ],
            [
                'label'             => 'Fee Shifting Rules',
                'labels'            => [
                    'name'              => 'Fee Shifting Rules',
                    'singular_name'     => 'Fee Shifting Rule',
                    'search_items'      => 'Search Fee Shifting Rules',
                    'all_items'         => 'All Fee Shifting Rules',
                    'edit_item'         => 'Edit Fee Shifting Rule',
                    'update_item'       => 'Update Fee Shifting Rule',
                    'add_new_item'      => 'Add New Fee Shifting Rule',
                    'new_item_name'     => 'New Fee Shifting Rule Name',
                    'menu_name'         => 'Fee Shifting',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 11. Employer Defense ──────────────────────────────────────────────
    //
    // New in 3.2.0. Flat taxonomy describing the defense standard(s) available
    // to the employer under a statute. Applied to jx-statute only.

    if ( ! taxonomy_exists( 'ws_employer_defense' ) ) {
        register_taxonomy(
            'ws_employer_defense',
            [ 'jx-statute' ],
            [
                'label'             => 'Employer Defense Standards',
                'labels'            => [
                    'name'          => 'Employer Defense Standards',
                    'singular_name' => 'Employer Defense Standard',
                    'search_items'  => 'Search Employer Defense Standards',
                    'all_items'     => 'All Employer Defense Standards',
                    'edit_item'     => 'Edit Employer Defense Standard',
                    'update_item'   => 'Update Employer Defense Standard',
                    'add_new_item'  => 'Add New Employer Defense Standard',
                    'new_item_name' => 'New Employer Defense Standard Name',
                    'menu_name'     => 'Employer Defense',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 12. Assist-Org Type ───────────────────────────────────────────────
    //
    // New in 3.3.0. Single-value classification for ws-assist-org records.
    // Drives the public "Get Help" directory filter. Replaces the ws_aorg_type
    // ACF select field. Terms are seeded via ws_seed_aorg_type_taxonomy().

    if ( ! taxonomy_exists( 'ws_aorg_type' ) ) {
        register_taxonomy(
            'ws_aorg_type',
            [ 'ws-assist-org' ],
            [
                'label'             => 'Organization Types',
                'labels'            => [
                    'name'              => 'Organization Types',
                    'singular_name'     => 'Organization Type',
                    'search_items'      => 'Search Organization Types',
                    'all_items'         => 'All Organization Types',
                    'edit_item'         => 'Edit Organization Type',
                    'update_item'       => 'Update Organization Type',
                    'add_new_item'      => 'Add New Organization Type',
                    'new_item_name'     => 'New Organization Type',
                    'menu_name'         => 'Org Types',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 13. Employment Sector ─────────────────────────────────────────────
    //
    // New in 3.7.0. Flat taxonomy classifying the employment sectors served
    // by a ws-assist-org record. Applied to ws-assist-org only.
    // Replaces ws_aorg_employment_sectors ACF checkbox field — enables
    // tax_query filtering for Phase 2 filter panel.

    if ( ! taxonomy_exists( 'ws_employment_sector' ) ) {
        register_taxonomy(
            'ws_employment_sector',
            [ 'ws-assist-org' ],
            [
                'label'             => 'Employment Sectors',
                'labels'            => [
                    'name'              => 'Employment Sectors',
                    'singular_name'     => 'Employment Sector',
                    'search_items'      => 'Search Employment Sectors',
                    'all_items'         => 'All Employment Sectors',
                    'edit_item'         => 'Edit Employment Sector',
                    'update_item'       => 'Update Employment Sector',
                    'add_new_item'      => 'Add New Employment Sector',
                    'new_item_name'     => 'New Employment Sector Name',
                    'menu_name'         => 'Employment Sectors',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 14. Assist-Org Cost Model ─────────────────────────────────────────
    //
    // New in 3.9.0. Flat taxonomy classifying the cost structure of a
    // ws-assist-org record. Applied to ws-assist-org only. Single-value
    // (equivalent to the former select field).
    // Replaces ws_aorg_cost_model ACF select field — enables tax_query
    // filtering for Phase 2 filter panel.

    if ( ! taxonomy_exists( 'ws_aorg_cost_model' ) ) {
        register_taxonomy(
            'ws_aorg_cost_model',
            [ 'ws-assist-org' ],
            [
                'label'             => 'Cost Structure',
                'labels'            => [
                    'name'              => 'Cost Structures',
                    'singular_name'     => 'Cost Structure',
                    'search_items'      => 'Search Cost Structures',
                    'all_items'         => 'All Cost Structures',
                    'edit_item'         => 'Edit Cost Structure',
                    'update_item'       => 'Update Cost Structure',
                    'add_new_item'      => 'Add New Cost Structure',
                    'new_item_name'     => 'New Cost Structure Name',
                    'menu_name'         => 'Cost Structure',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 15. Assist-Org Service ────────────────────────────────────────────
    //
    // New in 3.9.0. Flat taxonomy classifying the services offered by a
    // ws-assist-org record. Applied to ws-assist-org only.
    // Replaces ws_aorg_services ACF checkbox field — enables tax_query
    // filtering for Phase 2 filter panel.
    // 'additional' sentinel term auto-assigned when ws_aorg_additional_services
    // companion field is non-empty (mirrors ws_languages pattern).

    if ( ! taxonomy_exists( 'ws_aorg_service' ) ) {
        register_taxonomy(
            'ws_aorg_service',
            [ 'ws-assist-org' ],
            [
                'label'             => 'Services Offered',
                'labels'            => [
                    'name'              => 'Services Offered',
                    'singular_name'     => 'Service',
                    'search_items'      => 'Search Services',
                    'all_items'         => 'All Services',
                    'edit_item'         => 'Edit Service',
                    'update_item'       => 'Update Service',
                    'add_new_item'      => 'Add New Service',
                    'new_item_name'     => 'New Service Name',
                    'menu_name'         => 'Services Offered',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 16. Procedure Type ────────────────────────────────────────────────
    //
    // New in 3.10.0. Flat taxonomy classifying the purpose of a
    // ws-ag-procedure record. Applied to ws-ag-procedure only.
    // Three stable terms: disclosure, retaliation, both.
    // Replaces ws_proc_type ACF select field — enables tax_query filtering
    // in the Phase 2 filter cascade. Single-value per record (radio UI).
    // Terms are seeded via ws_seed_proc_type_taxonomy().

    if ( ! taxonomy_exists( 'ws_procedure_type' ) ) {
        register_taxonomy(
            'ws_procedure_type',
            [ 'ws-ag-procedure' ],
            [
                'label'             => 'Procedure Types',
                'labels'            => [
                    'name'              => 'Procedure Types',
                    'singular_name'     => 'Procedure Type',
                    'search_items'      => 'Search Procedure Types',
                    'all_items'         => 'All Procedure Types',
                    'edit_item'         => 'Edit Procedure Type',
                    'update_item'       => 'Update Procedure Type',
                    'add_new_item'      => 'Add New Procedure Type',
                    'new_item_name'     => 'New Procedure Type Name',
                    'menu_name'         => 'Procedure Types',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }
}
add_action( 'init', 'ws_register_taxonomies' );


// ════════════════════════════════════════════════════════════════════════════
// SHARED HELPERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Helper: Taxonomy Capability Mapping
 *
 * Restricts management to Administrators; allows assignment for other roles.
 */
function ws_get_taxonomy_caps() {
    return [
        'manage_terms' => 'manage_options',
        'edit_terms'   => 'manage_options',
        'delete_terms' => 'manage_options',
        'assign_terms' => 'edit_posts',
    ];
}

/**
 * Helper: Hierarchical Term Seeder
 *
 * Inserts a parent/child term structure into a taxonomy. Skips terms
 * that already exist. Used by seeding functions for hierarchical taxonomies.
 *
 * @param array  $hierarchy  Associative array: parent_slug => [ 'name' => '', 'children' => [] ]
 * @param string $taxonomy   Taxonomy slug.
 */
function ws_bulk_insert_hierarchical( array $hierarchy, string $taxonomy ) {
    foreach ( $hierarchy as $parent_slug => $data ) {
        $existing_parent = term_exists( $parent_slug, $taxonomy );
        if ( ! $existing_parent ) {
            $parent = wp_insert_term( $data['name'], $taxonomy, [ 'slug' => $parent_slug ] );
        } else {
            $parent = is_array( $existing_parent )
                ? $existing_parent
                : [ 'term_id' => $existing_parent ];
        }
        if ( is_wp_error( $parent ) || empty( $data['children'] ) ) {
            continue;
        }
        $parent_id = (int) $parent['term_id'];
        foreach ( $data['children'] as $child_slug => $child_name ) {
            if ( ! term_exists( $child_slug, $taxonomy ) ) {
                wp_insert_term( $child_name, $taxonomy, [
                    'slug'   => $child_slug,
                    'parent' => $parent_id,
                ] );
            }
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// SEEDING EXECUTION GATES
//
// Each seeder is individually gated using the Unified Option-Gate Method.
// Key format: ws_seeded_{seeder_slug} / version string: '1.0.0'
// No grouped gates — each taxonomy has its own independent gate.
//
// Gate version bump pattern: to re-seed a taxonomy after a term change,
// increment the version string (e.g. '1.0.0' → '1.1.0') in both the
// gate check and the update_option() call below.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', function() {

    if ( get_option( 'ws_seeded_disclosure_type' ) !== '1.1.0' ) {
        ws_seed_disclosure_taxonomy();
        update_option( 'ws_seeded_disclosure_type', '1.1.0' );
    }
    if ( get_option( 'ws_seeded_process_type' ) !== '1.0.0' ) {
        ws_seed_process_taxonomy();
        update_option( 'ws_seeded_process_type', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_remedies' ) !== '1.0.0' ) {
        ws_seed_remedies_taxonomy();
        update_option( 'ws_seeded_remedies', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_protected_class' ) !== '1.0.0' ) {
        ws_seed_protected_class_taxonomy();
        update_option( 'ws_seeded_protected_class', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_adverse_action_types' ) !== '1.0.0' ) {
        ws_seed_adverse_action_types_taxonomy();
        update_option( 'ws_seeded_adverse_action_types', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_languages_taxonomy' ) !== '1.0.0' ) {
        ws_seed_languages_taxonomy();
        update_option( 'ws_seeded_languages_taxonomy', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_case_stage' ) !== '1.0.0' ) {
        ws_seed_case_stage_taxonomy();
        update_option( 'ws_seeded_case_stage', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_jurisdiction' ) !== '1.0.0' ) {
        ws_seed_jurisdiction_taxonomy();
        update_option( 'ws_seeded_jurisdiction', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_disclosure_targets' ) !== '1.0.0' ) {
        ws_seed_disclosure_targets_taxonomy();
        update_option( 'ws_seeded_disclosure_targets', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_fee_shifting' ) !== '1.0.0' ) {
        ws_seed_fee_shifting_taxonomy();
        update_option( 'ws_seeded_fee_shifting', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_employer_defense' ) !== '1.0.0' ) {
        ws_seed_employer_defense_taxonomy();
        update_option( 'ws_seeded_employer_defense', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_aorg_type' ) !== '1.0.0' ) {
        ws_seed_aorg_type_taxonomy();
        update_option( 'ws_seeded_aorg_type', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_employment_sector' ) !== '1.0.0' ) {
        ws_seed_employment_sector_taxonomy();
        update_option( 'ws_seeded_employment_sector', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_aorg_service' ) !== '1.0.0' ) {
        ws_seed_aorg_service_taxonomy();
        update_option( 'ws_seeded_aorg_service', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_aorg_cost_model' ) !== '1.0.0' ) {
        ws_seed_aorg_cost_model_taxonomy();
        update_option( 'ws_seeded_aorg_cost_model', '1.0.0' );
    }
    if ( get_option( 'ws_seeded_procedure_type' ) !== '1.0.0' ) {
        ws_seed_proc_type_taxonomy();
        update_option( 'ws_seeded_procedure_type', '1.0.0' );
    }

} );


// ════════════════════════════════════════════════════════════════════════════
// SEEDING FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Seeds ws_disclosure_type with its hierarchical structure.
 */
function ws_seed_disclosure_taxonomy() {
    $hierarchy = [
        'workplace-employment' => [
            'name'     => 'Workplace & Employment',
            'children' => [
                'retaliation-protection'     => 'Retaliation Protection',
                'wrongful-termination'       => 'Wrongful Termination',
                'wage-hour-violations'       => 'Wage & Hour Violations',
                'occupational-health-safety' => 'Occupational Health & Safety',
                'collective-bargaining'      => 'Collective Bargaining Rights',
            ],
        ],
        'financial-corporate' => [
            'name'     => 'Financial & Corporate',
            'children' => [
                'securities-commodities-fraud'  => 'Securities & Commodities Fraud',
                'consumer-financial-protection' => 'Consumer Financial Protection',
                'banking-aml-compliance'        => 'Banking & AML Compliance',
                'shareholder-rights'            => 'Shareholder Rights',
                'tax-evasion-fraud'             => 'Tax Evasion & Fraud',
            ],
        ],
        'government-accountability' => [
            'name'     => 'Government Accountability',
            'children' => [
                'procurement-spending-fraud' => 'Procurement & Spending Fraud',
                'public-corruption-ethics'   => 'Public Corruption & Ethics',
                'election-integrity'         => 'Election Integrity',
                'military-defense-reporting' => 'Military & Defense Reporting',
            ],
        ],
        'public-health-safety' => [
            'name'     => 'Public Health & Safety',
            'children' => [
                'healthcare-medicare-fraud' => 'Healthcare & Medicare Fraud',
                'environmental-protection'  => 'Environmental Protection',
                'food-drug-safety'          => 'Food & Drug Safety',
                'nuclear-energy-safety'     => 'Nuclear & Energy Safety',
                'transportation-safety'     => 'Transportation & Aviation Safety',
            ],
        ],
        'privacy-data-integrity' => [
            'name'     => 'Privacy & Data Integrity',
            'children' => [
                'cybersecurity-disclosure'  => 'Cybersecurity Disclosure',
                'hipaa-patient-privacy'     => 'HIPAA & Patient Privacy',
                'consumer-data-protection'  => 'Consumer Data Protection',
                'education-privacy-ferpa'   => 'Education Privacy (FERPA)',
            ],
        ],
        'national-security' => [
            'name'     => 'National Security',
            'children' => [
                'intelligence-community'       => 'Intelligence Community Reporting',
                'classified-information'       => 'Classified Information Disclosures',
                'export-sanctions-compliance'  => 'Export Controls & Sanctions Compliance',
            ],
        ],
    ];
    ws_bulk_insert_hierarchical( $hierarchy, 'ws_disclosure_type' );
}

/**
 * Seeds ws_process_type with its flat term list.
 */
function ws_seed_process_taxonomy() {
    $taxonomy = 'ws_process_type';
    $terms    = [
        'administrative-complaint' => 'Administrative Complaint',
        'civil-lawsuit'            => 'Civil Lawsuit',
        'qui-tam'                  => 'Qui Tam (False Claims)',
        'internal-disclosure'      => 'Internal Disclosure',
        'regulatory-tip'           => 'Regulatory Tip',
        'criminal-referral'        => 'Criminal Referral',
        'state-agency-complaint'   => 'State Agency Complaint',
        'congressional-disclosure' => 'Congressional Disclosure',
        'representative-action'    => 'Representative Action',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_remedies with its flat term list.
 * Replaces ws_seed_remedy_taxonomy() for ws_remedy_type (deprecated).
 */
function ws_seed_remedies_taxonomy() {
    $taxonomy = 'ws_remedies';
    $terms    = [
        'reinstatement'                   => 'Reinstatement',
        'back-pay'                        => 'Back Pay',
        'front-pay'                       => 'Front Pay',
        'double-back-pay'                 => 'Double Back Pay',
        'lost-wages'                      => 'Lost Wages',
        'benefits-restoration'            => 'Benefits Restoration',
        'compensatory-damages'            => 'Compensatory Damages',
        'punitive-damages'                => 'Punitive Damages',
        'treble-damages'                  => 'Treble Damages',
        'civil-penalty'                   => 'Civil Penalty',
        'civil-penalties'                 => 'Civil Penalties (Aggregate)',
        'attorney-fees'                   => 'Attorney Fees',
        'litigation-costs'                => 'Litigation Costs',
        'injunctive-relief'               => 'Injunctive Relief',
        'cease-and-desist'                => 'Cease and Desist Order',
        'license-suspension'              => 'License Suspension',
        'expungement-of-personnel-record' => 'Expungement of Personnel Record',
        'bounty-qui-tam-award'            => 'Bounty / Qui Tam Award',
        'wage-differential'               => 'Wage Differential',
        'liquidated-damages'              => 'Liquidated Damages',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_protected_class with its hierarchical employee type structure.
 * Replaces ws_seed_coverage_scope_taxonomy() for ws_coverage_scope (deprecated).
 */
function ws_seed_protected_class_taxonomy() {
    $hierarchy = [
        'public-sector' => [
            'name'     => 'Public Sector',
            'children' => [
                'federal-employee'     => 'Federal Employee',
                'state-employee'       => 'State Agency Employee',
                'local-gov-staff'      => 'Local / Municipal Employee',
                'k12-education-staff'  => 'K-12 / Higher Ed Staff',
                'military-personnel'   => 'Military Personnel',
            ],
        ],
        'private-sector' => [
            'name'     => 'Private Sector',
            'children' => [
                'corporate-staff'      => 'Corporate / Private Employee',
                'contractor-gig'       => 'Independent Contractor / Gig',
                'non-profit-staff'     => 'Non-Profit Employee',
                'agricultural-worker'  => 'Agricultural Worker',
            ],
        ],
        'healthcare-staff' => [
            'name'     => 'Healthcare & Medical',
            'children' => [
                'clinical-staff'       => 'Clinical (Nurse / Physician)',
                'medical-student'      => 'Medical Student / Intern / Resident',
            ],
        ],
        'special-status' => [
            'name'     => 'Special Status',
            'children' => [
                'job-applicant'        => 'Job Applicant',
                'former-employee'      => 'Former Employee',
                'perceived-whistleblower' => 'Perceived Whistleblower',
            ],
        ],
    ];
    ws_bulk_insert_hierarchical( $hierarchy, 'ws_protected_class' );
}

/**
 * Seeds ws_adverse_action_types with its flat term list.
 * Replaces ws_seed_retaliation_forms_taxonomy() for ws_retaliation_forms (deprecated).
 */
function ws_seed_adverse_action_types_taxonomy() {
    $taxonomy = 'ws_adverse_action_types';
    $terms    = [
        'termination'               => 'Termination',
        'constructive-discharge'    => 'Constructive Discharge',
        'demotion'                  => 'Demotion',
        'suspension'                => 'Suspension',
        'disciplinary-action'       => 'Disciplinary Action',
        'transfer'                  => 'Transfer',
        'schedule-change'           => 'Schedule Change',
        'pay-reduction'             => 'Pay / Benefits Reduction',
        'harassment'                => 'Harassment',
        'blacklisting'              => 'Blacklisting',
        'security-clearance-action' => 'Security Clearance Action',
        'contract-non-renewal'      => 'Contract Non-Renewal',
        'privilege-revocation'      => 'Privilege / Access Revocation',
        'immigration-threat'        => 'Immigration-Related Threat',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_languages terms.
 * 'additional' is a functional flag — auto-assigned when ws_agency_additional_languages
 * or ws_ao_additional_languages text fields contain a value.
 */
function ws_seed_languages_taxonomy() {
    $taxonomy = 'ws_languages';
    $terms    = [
        'english'        => 'English',
        'spanish'        => 'Spanish',
        'mandarin'       => 'Mandarin',
        'cantonese'      => 'Cantonese',
        'french'         => 'French',
        'portuguese'     => 'Portuguese',
        'vietnamese'     => 'Vietnamese',
        'tagalog'        => 'Tagalog',
        'korean'         => 'Korean',
        'arabic'         => 'Arabic',
        'hindi'          => 'Hindi',
        'russian'        => 'Russian',
        'haitian-creole' => 'Haitian Creole',
        'polish'         => 'Polish',
        'japanese'       => 'Japanese',
        'additional'     => 'Additional',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_case_stage terms.
 */
function ws_seed_case_stage_taxonomy() {
    $taxonomy = 'ws_case_stage';
    $terms    = [
        'pre-report'         => 'Pre-Report',
        'post-report'        => 'Post-Report',
        'retaliation-active' => 'Retaliation Active',
        'litigation'         => 'Litigation',
        'other'              => 'Other',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_jurisdiction taxonomy with canonical USPS codes.
 * Special case: 'us' => 'Federal' (not 'Us' or 'United States').
 * Includes DC and the five U.S. territories.
 * Display order: Federal first, DC second, states alphabetical, territories alphabetical.
 */
function ws_seed_jurisdiction_taxonomy() {
    $taxonomy = WS_JURISDICTION_TAXONOMY;
    $terms    = [
        'us' => 'Federal',
        'dc' => 'District of Columbia',
        'al' => 'Alabama',
        'ak' => 'Alaska',
        'az' => 'Arizona',
        'ar' => 'Arkansas',
        'ca' => 'California',
        'co' => 'Colorado',
        'ct' => 'Connecticut',
        'de' => 'Delaware',
        'fl' => 'Florida',
        'ga' => 'Georgia',
        'hi' => 'Hawaii',
        'id' => 'Idaho',
        'il' => 'Illinois',
        'in' => 'Indiana',
        'ia' => 'Iowa',
        'ks' => 'Kansas',
        'ky' => 'Kentucky',
        'la' => 'Louisiana',
        'me' => 'Maine',
        'md' => 'Maryland',
        'ma' => 'Massachusetts',
        'mi' => 'Michigan',
        'mn' => 'Minnesota',
        'ms' => 'Mississippi',
        'mo' => 'Missouri',
        'mt' => 'Montana',
        'ne' => 'Nebraska',
        'nv' => 'Nevada',
        'nh' => 'New Hampshire',
        'nj' => 'New Jersey',
        'nm' => 'New Mexico',
        'ny' => 'New York',
        'nc' => 'North Carolina',
        'nd' => 'North Dakota',
        'oh' => 'Ohio',
        'ok' => 'Oklahoma',
        'or' => 'Oregon',
        'pa' => 'Pennsylvania',
        'ri' => 'Rhode Island',
        'sc' => 'South Carolina',
        'sd' => 'South Dakota',
        'tn' => 'Tennessee',
        'tx' => 'Texas',
        'ut' => 'Utah',
        'vt' => 'Vermont',
        'va' => 'Virginia',
        'wa' => 'Washington',
        'wv' => 'West Virginia',
        'wi' => 'Wisconsin',
        'wy' => 'Wyoming',
        'as' => 'American Samoa',
        'gu' => 'Guam',
        'mp' => 'Northern Mariana Islands',
        'pr' => 'Puerto Rico',
        'vi' => 'U.S. Virgin Islands',
    ];
    $order = 1;
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            $result = wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
            if ( ! is_wp_error( $result ) ) {
                update_term_meta( $result['term_id'], 'display_order', $order );
            }
        } else {
            $existing = get_term_by( 'slug', $slug, $taxonomy );
            if ( $existing ) {
                update_term_meta( $existing->term_id, 'display_order', $order );
            }
        }
        $order++;
    }
}

/**
 * Seeds ws_disclosure_targets with its hierarchical recipient structure.
 * New in 3.1.0. Describes who received the disclosure for protection to apply.
 */
function ws_seed_disclosure_targets_taxonomy() {
    $hierarchy = [
        'internal' => [
            'name'     => 'Internal',
            'children' => [
                'internal-supervisor'  => 'Supervisor / Manager',
                'internal-hr'          => 'Human Resources',
                'internal-compliance'  => 'Compliance / Ethics Hotline',
                'internal-legal'       => 'In-House Legal Counsel',
            ],
        ],
        'external-agency' => [
            'name'     => 'External: Government Agency',
            'children' => [
                'agency-federal'       => 'Federal Agency',
                'agency-state'         => 'State Agency',
                'agency-local'         => 'Local / Municipal Agency',
                'law-enforcement'      => 'Law Enforcement',
            ],
        ],
        'legislative' => [
            'name'     => 'Legislative Body',
            'children' => [
                'legislative-federal'  => 'U.S. Congress',
                'legislative-state'    => 'State Legislature',
            ],
        ],
        'judicial' => [
            'name'     => 'Judicial / Legal',
            'children' => [
                'court-filing'         => 'Court Filing',
                'attorney-counsel'     => 'Personal Attorney / Counsel',
            ],
        ],
        'public' => [
            'name'     => 'Public Disclosure',
            'children' => [
                'public-media'         => 'Media / Press',
                'public-nonprofit'     => 'Non-Profit / Advocacy Organization',
            ],
        ],
    ];
    ws_bulk_insert_hierarchical( $hierarchy, 'ws_disclosure_targets' );
}

/**
 * Seeds ws_fee_shifting with its flat term list.
 * New in 3.1.0.
 */
function ws_seed_fee_shifting_taxonomy() {
    $taxonomy = 'ws_fee_shifting';
    $terms    = [
        'unilateral-pro-plaintiff' => 'Unilateral (Pro-Plaintiff)',
        'bilateral-loser-pays'     => 'Bilateral (Loser Pays)',
        'discretionary'            => 'Discretionary',
        'none-american-rule'       => 'None (American Rule)',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}


/**
 * Seeds ws_aorg_type with organization type terms.
 *
 * New in 3.3.0. Replaces the ws_aorg_type ACF select field.
 * 'oversight-office' replaces the opaque 'ombudsman' label used in the
 * prior select — "Government Oversight Office" is legible to laypeople.
 */
function ws_seed_aorg_type_taxonomy() {
    $taxonomy = 'ws_aorg_type';
    $terms    = [
        'nonprofit'        => 'Nonprofit Organization',
        'legal-aid'        => 'Legal Aid Clinic',
        'law-firm'         => 'Law Firm',
        'bar-program'      => 'Bar Association Program',
        'advocacy'         => 'Advocacy Organization',
        'oversight-office' => 'Government Oversight Office',
        'union'            => 'Labor Union',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_employment_sector with flat sector terms.
 *
 * New in 3.7.0. Replaces ws_aorg_employment_sectors ACF checkbox.
 * 'all-sectors' is used for organizations that serve all worker types.
 */
function ws_seed_employment_sector_taxonomy() {
    $taxonomy = 'ws_employment_sector';
    $terms    = [
        'federal-employee'     => 'Federal Government Employee',
        'state-local-employee' => 'State & Local Government Employee',
        'private-sector'       => 'Private Sector Employee',
        'military-defense'     => 'Military & Defense Contractors',
        'nonprofit-ngo'        => 'Nonprofit & NGO Employee',
        'all-sectors'          => 'All Employment Sectors',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_aorg_cost_model with flat cost structure terms.
 *
 * New in 3.9.0. Replaces ws_aorg_cost_model ACF select field.
 */
function ws_seed_aorg_cost_model_taxonomy() {
    $taxonomy = 'ws_aorg_cost_model';
    $terms    = [
        'free'            => 'Free of Charge',
        'pro-bono'        => 'Pro Bono',
        'sliding-scale'   => 'Sliding Scale Fee',
        'contingency'     => 'Contingency Fee',
        'fee-for-service' => 'Fee for Service',
        'mixed'           => 'Mixed / Varies',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_aorg_service with flat service terms.
 *
 * New in 3.9.0. Replaces ws_aorg_services ACF checkbox.
 * 'additional' is the sentinel term for free-text overflow.
 */
function ws_seed_aorg_service_taxonomy() {
    $taxonomy = 'ws_aorg_service';
    $terms    = [
        'legal-rep'    => 'Full Legal Representation',
        'consultation' => 'Legal Consultation / Advice',
        'referral'     => 'Intake & Referral',
        'doc-review'   => 'Document Review',
        'hotline'      => 'Whistleblower Hotline',
        'retaliation'  => 'Retaliation Defense',
        'financial'    => 'Financial Assistance',
        'advocacy'     => 'Policy Advocacy',
        'media'        => 'Media & Communications Support',
        'additional'   => 'Additional Services',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_employer_defense with its flat term structure.
 *
 * New in 3.2.0.
 */
function ws_seed_employer_defense_taxonomy() {
    $taxonomy = 'ws_employer_defense';
    $terms    = [
        'same-decision-defense'             => 'Same-Decision Defense',
        'legitimate-non-retaliatory-reason' => 'Legitimate Non-Retaliatory Reason',
        'good-faith-compliance'             => 'Good-Faith Compliance',
        'statutory-exception-claim'         => 'Statutory Exception Claim',
        'mixed-motive-defense'              => 'Mixed Motive Defense',
        'no-protected-activity'             => 'Disclosure was not Protected',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}

/**
 * Seeds ws_procedure_type with its three flat terms.
 *
 * New in 3.10.0. Replaces the ws_proc_type ACF select field on ws-ag-procedure.
 * These three terms are stable — the set is not expected to grow.
 *
 *   disclosure  — procedure for reporting wrongdoing to the agency
 *   retaliation — procedure for filing a complaint after adverse action
 *   both        — single procedure that covers both disclosure and retaliation
 */
function ws_seed_proc_type_taxonomy() {
    $taxonomy = 'ws_procedure_type';
    $terms    = [
        'disclosure'  => 'Disclosure',
        'retaliation' => 'Retaliation',
        'both'        => 'Both',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }
}
