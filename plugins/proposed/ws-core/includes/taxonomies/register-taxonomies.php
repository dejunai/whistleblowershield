<?php
/**
 * register-taxonomies.php
 *
 * Registers all shared taxonomies for the ws-core plugin and seeds
 * their initial term structures on first admin load.
 *
 * VERSION
 * -------
 * 2.1.0  Initial: ws_disclosure_type
 * 2.3.1  Added ws_process_type taxonomy and seed.
 * 2.4.0  STABILIZATION PASS:
 *        - Hard Delete: Removed  from all taxonomy associations.
 *        - New Taxonomies: Added ws_coverage_scope, ws_retaliation_forms,
 *          ws_languages, ws_case_stage.
 *        - Security: Implemented capability mapping to lock vocabulary to
 *          Administrators.
 *        - UI: Added comprehensive labels for all taxonomies.
 * 2.4.1  Bug fixes:
 *        - ws-assist-org added to ws_disclosure_type object types so that
 *          save_terms works correctly on assist-org edit screens (Bug #9).
 *        - ws_languages and ws_case_stage object type corrected from
 *          'assist-org' to 'ws-assist-org' (Bug #2).
 *        - Missing comma after ws_languages entry in ws_seed_v240_taxonomies()
 *          fixed (Bug #1 — was a fatal PHP parse error).
 *        - ws_seed_remedy_taxonomy() now calls update_option() so the gate
 *          check does not re-run on every admin_init (Bug #3).
 */

defined( 'ABSPATH' ) || exit;

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
            [ 'jx-statute', '', 'jx-citation', 'ws-agency', 'ws-assist-org' ],
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
            [ 'jx-statute', 'ws-agency', 'jx-interpretation' ],
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

    if ( ! taxonomy_exists( 'ws_remedy_type' ) ) {
        register_taxonomy(
            'ws_remedy_type',
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

    // ── 4. Coverage Scope ─────────────────────────────────────────────────

    if ( ! taxonomy_exists( 'ws_coverage_scope' ) ) {
        register_taxonomy(
            'ws_coverage_scope',
            [ 'jx-statute' ],
            [
                'label'             => 'Coverage Scope',
                'labels'            => [
                    'name'              => 'Coverage Scopes',
                    'singular_name'     => 'Coverage Scope',
                    'search_items'      => 'Search Scopes',
                    'all_items'         => 'All Scopes',
                    'edit_item'         => 'Edit Scope',
                    'update_item'       => 'Update Scope',
                    'add_new_item'      => 'Add New Scope',
                    'new_item_name'     => 'New Coverage Scope Name',
                    'menu_name'         => 'Coverage Scope',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
                'capabilities'      => ws_get_taxonomy_caps(),
            ]
        );
    }

    // ── 5. Retaliation Forms ──────────────────────────────────────────────

    if ( ! taxonomy_exists( 'ws_retaliation_forms' ) ) {
        register_taxonomy(
            'ws_retaliation_forms',
            [ 'jx-statute' ],
            [
                'label'             => 'Retaliation Forms',
                'labels'            => [
                    'name'              => 'Retaliation Forms',
                    'singular_name'     => 'Retaliation Form',
                    'search_items'      => 'Search Retaliation Forms',
                    'all_items'         => 'All Retaliation Forms',
                    'edit_item'         => 'Edit Retaliation Form',
                    'update_item'       => 'Update Retaliation Form',
                    'add_new_item'      => 'Add New Retaliation Form',
                    'new_item_name'     => 'New Retaliation Form Name',
                    'menu_name'         => 'Retaliation Forms',
                ],
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_rest'      => true,
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
}
add_action( 'init', 'ws_register_taxonomies' );

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


// ── Seeding execution gates ───────────────────────────────────────────────────
//
// Each seeding function runs once — gated by a versioned option flag.
// The v240 gate covers all four new taxonomies introduced in 2.4.0.

add_action( 'admin_init', function() {
    if ( get_option( 'ws_disclosure_type_seeded' ) !== '1.0' ) { ws_seed_disclosure_taxonomy(); }
    if ( get_option( 'ws_process_type_seeded' ) !== '1.0' )    { ws_seed_process_type_taxonomy(); }
    if ( get_option( 'ws_remedy_type_seeded' ) !== '1.0' )     { ws_seed_remedy_taxonomy(); }
    if ( get_option( 'ws_v240_taxonomies_seeded' ) !== '1.0' ) {
        ws_seed_v240_taxonomies();
        update_option( 'ws_v240_taxonomies_seeded', '1.0' );
    }
} );


// ── Seeding functions ─────────────────────────────────────────────────────────

/**
 * Seeds the ws_disclosure_type taxonomy with its initial hierarchical structure.
 */
function ws_seed_disclosure_taxonomy() {
    $taxonomy  = 'ws_disclosure_type';
    $structure = [
        'Workplace & Employment' => [
            'slug'     => 'workplace-employment',
            'children' => [
                'retaliation-protection'     => 'Retaliation Protection',
                'wrongful-termination'       => 'Wrongful Termination',
                'wage-hour-violations'       => 'Wage & Hour Violations',
                'occupational-health-safety' => 'Occupational Health & Safety',
                'collective-bargaining'      => 'Collective Bargaining Rights',
            ],
        ],
        'Financial & Corporate' => [
            'slug'     => 'financial-corporate',
            'children' => [
                'securities-commodities-fraud'  => 'Securities & Commodities Fraud',
                'consumer-financial-protection' => 'Consumer Financial Protection',
                'banking-aml-compliance'        => 'Banking & AML Compliance',
                'shareholder-rights'            => 'Shareholder Rights',
                'tax-evasion-fraud'             => 'Tax Evasion & Fraud',
            ],
        ],
        'Government Accountability' => [
            'slug'     => 'government-accountability',
            'children' => [
                'procurement-spending-fraud' => 'Procurement & Spending Fraud',
                'public-corruption-ethics'   => 'Public Corruption & Ethics',
                'election-integrity'         => 'Election Integrity',
                'military-defense-reporting' => 'Military & Defense Reporting',
            ],
        ],
        'Public Health & Safety' => [
            'slug'     => 'public-health-safety',
            'children' => [
                'healthcare-medicare-fraud' => 'Healthcare & Medicare Fraud',
                'environmental-protection'  => 'Environmental Protection',
                'food-drug-safety'          => 'Food & Drug Safety',
                'nuclear-energy-safety'     => 'Nuclear & Energy Safety',
                'transportation-safety'     => 'Transportation & Aviation Safety',
            ],
        ],
        'Privacy & Data Integrity' => [
            'slug'     => 'privacy-data-integrity',
            'children' => [
                'cybersecurity-disclosure'  => 'Cybersecurity Disclosure',
                'hipaa-patient-privacy'     => 'HIPAA & Patient Privacy',
                'consumer-data-protection'  => 'Consumer Data Protection',
                'education-privacy-ferpa'   => 'Education Privacy (FERPA)',
            ],
        ],
    ];

    foreach ( $structure as $parent_name => $data ) {
        $parent_term = term_exists( $data['slug'], $taxonomy );
        if ( ! $parent_term ) {
            $parent = wp_insert_term( $parent_name, $taxonomy, [ 'slug' => $data['slug'] ] );
        } else {
            $parent = is_array( $parent_term ) ? $parent_term : [ 'term_id' => $parent_term ];
        }

        if ( ! is_wp_error( $parent ) ) {
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

    update_option( 'ws_disclosure_type_seeded', '1.0' );
}

/**
 * Seeds the ws_process_type taxonomy with its initial flat term list.
 */
function ws_seed_process_type_taxonomy() {
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
    ];

    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
        }
    }

    update_option( 'ws_process_type_seeded', '1.0' );
}

/**
 * Seeds the ws_remedy_type taxonomy with its initial flat term list.
 *
 * Bug #3 fix: update_option() call added so the gate check does not
 * re-run on every admin_init after first execution.
 */
function ws_seed_remedy_taxonomy() {
    $taxonomy = 'ws_remedy_type';
    $terms    = [
        'Back Pay',
        'Front Pay',
        'Reinstatement',
        'Compensatory Damages',
        'Punitive Damages',
        'Treble Damages',
        'Attorney Fees',
        'Litigation Costs',
        'Expungement of Personnel Record',
    ];

    foreach ( $terms as $term ) {
        if ( ! term_exists( $term, $taxonomy ) ) {
            wp_insert_term( $term, $taxonomy );
        }
    }

    update_option( 'ws_remedy_type_seeded', '1.0' ); // Bug #3 fix
}

/**
 * Seeds terms for all 2.4.0 taxonomies: ws_coverage_scope, ws_retaliation_forms,
 * ws_languages, and ws_case_stage.
 *
 * Bug #1 fix: missing comma after ws_languages entry in $seeds array corrected
 * (was a fatal PHP parse error on activation).
 */
function ws_seed_v240_taxonomies() {
    $seeds = [
        'ws_coverage_scope' => [
            'federal-employees',
            'private-sector-employees',
            'contractors',
            'state-employees',
            'local-government-employees',
            'nonprofit-employees',
            'other',
        ],
        'ws_retaliation_forms' => [
            'termination',
            'demotion',
            'disciplinary-action',
            'transfer',
            'schedule-change',
            'harassment',
            'blacklisting',
            'security-clearance-action',
            'other',
        ],
        'ws_languages' => [
            'english',
            'spanish',
            'mandarin',
            'cantonese',
            'french',
            'portuguese',
            'vietnamese',
            'tagalog',
            'korean',
            'arabic',
            'hindi',
            'russian',
            'haitian-creole',
            'polish',
            'japanese',
            'additional',   // Functional flag — triggers meta field query
        ],                  // Bug #1 fix: comma was missing here
        'ws_case_stage' => [
            'pre-report',
            'post-report',
            'retaliation-active',
            'litigation',
            'other',
        ],
    ];

    foreach ( $seeds as $tax => $terms ) {
        foreach ( $terms as $slug ) {
            if ( ! term_exists( $slug, $tax ) ) {
                $label = ucwords( str_replace( '-', ' ', $slug ) );
                wp_insert_term( $label, $tax, [ 'slug' => $slug ] );
            }
        }
    }
}
