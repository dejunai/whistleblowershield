<?php
/**
 * register-taxonomies.php
 *
 * Registers all shared taxonomies for the ws-core plugin and seeds
 * their initial term structures on first admin load.
 *
 * TAXONOMIES
 * ----------
 * ws_disclosure_cat  — Hierarchical. Classifies legal content by the
 *                      nature of the misconduct (fraud, retaliation,
 *                      environmental, etc.). Applied to jx-statutes,
 *                      jx-procedures, jx-resources, jx-citations.
 *
 * ws_process_type    — Flat. Classifies legal content by the type of
 *                      action available to the whistleblower (civil
 *                      lawsuit, administrative complaint, qui tam, etc.).
 *                      Applied to jx-statutes and ws-agencies.
 *
 *                      On jx-statutes: the statute grants the process type
 *                      as a legal right (authoritative).
 *                      On ws-agencies: the agency handles this process type
 *                      (descriptive — the statute remains authoritative).
 *
 * SEEDING
 * -------
 * Both taxonomies are seeded on admin_init behind an option gate so
 * term_exists() checks run only when the seeded-version option is
 * missing or outdated. Bump the version string in the gate to force
 * a re-seed when the term structure changes.
 *
 * VERSION
 * -------
 * 2.1.0  Initial: ws_disclosure_cat
 * 2.3.1  Added ws_process_type taxonomy and seed.
 *        Added ws-agencies to ws_disclosure_cat object types.
 */

defined( 'ABSPATH' ) || exit;


function ws_register_taxonomies() {

    /*
    -----------------------------------------------------
    Taxonomy: Disclosure Categories
    Classifies statutes, procedures, citations, and
    resources by the specific nature of the misconduct.
    -----------------------------------------------------
    */

    register_taxonomy(
        'ws_disclosure_cat',
        [ 'jx-statutes', 'jx-procedures', 'jx-resources', 'jx-citations', 'ws-agencies' ],
        [
            'label'         => 'Disclosure Categories',
            'public'        => true,
            'hierarchical'  => true, 
            'show_in_rest'  => true, 
            'show_admin_column' => true,
            'rewrite'       => ['slug' => 'disclosure'],
            'labels'        => [
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
                'menu_name'         => 'Disclosure Categories'
            ]
        ]
    );

    /*
    -----------------------------------------------------
    Taxonomy: Process Types
    Classifies statutes and agencies by the type of legal
    action available to the whistleblower. Flat (not
    hierarchical) — process types are parallel categories,
    not parent-child relationships.

    Applied to: jx-statutes, ws-agencies

    On jx-statutes  — the statute grants this process type
                       as a legal right (authoritative source)
    On ws-agencies  — the agency handles this process type
                       (descriptive/secondary)
    -----------------------------------------------------
    */

    register_taxonomy(
        'ws_process_type',
        [ 'jx-statutes', 'ws-agencies' ],
        [
            'label'             => 'Process Types',
            'public'            => true,
            'hierarchical'      => false,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'process-type' ],
            'labels'            => [
                'name'          => 'Process Types',
                'singular_name' => 'Process Type',
                'search_items'  => 'Search Process Types',
                'all_items'     => 'All Process Types',
                'edit_item'     => 'Edit Process Type',
                'update_item'   => 'Update Process Type',
                'add_new_item'  => 'Add New Process Type',
                'new_item_name' => 'New Process Type Name',
                'menu_name'     => 'Process Types',
            ],
        ]
    );
/*
    -----------------------------------------------------
    Taxonomy: Remedies
    Statutory recoveries available to the whistleblower.
    -----------------------------------------------------
    */

    register_taxonomy(
        'ws_remedy',
        ['ws-statute'],
        [
            'label'         => 'Remedies',
            'public'        => false, // Admin-facing classification
            'hierarchical'  => false, // Flat list (tags)
            'show_in_rest'  => true,
            'show_admin_column' => true,
            'labels'        => [
                'name'          => 'Remedies',
                'singular_name' => 'Remedy',
                'add_new_item'  => 'Add New Remedy',
            ]
        ]
    );
}
add_action( 'init', 'ws_register_taxonomies' );

/**
 * Programmatically seed the initial tree for Disclosure Categories.
 */
function ws_seed_disclosure_taxonomy() {
    $taxonomy = 'ws_disclosure_cat';

    // Define the full hierarchical structure: 'Parent Name' => [ 'slug' => 'Child Name' ]
    $structure = [
        'Workplace & Employment' => [
            'slug' => 'workplace-employment',
            'children' => [
                'retaliation-protection'     => 'Retaliation Protection',
                'wrongful-termination'       => 'Wrongful Termination',
                'wage-hour-violations'       => 'Wage & Hour Violations',
                'occupational-health-safety' => 'Occupational Health & Safety',
                'collective-bargaining'      => 'Collective Bargaining Rights'
            ]
        ],
        'Financial & Corporate' => [
            'slug' => 'financial-corporate',
            'children' => [
                'securities-commodities-fraud' => 'Securities & Commodities Fraud',
                'consumer-financial-protection' => 'Consumer Financial Protection',
                'banking-aml-compliance'       => 'Banking & AML Compliance',
                'shareholder-rights'           => 'Shareholder Rights',
                'tax-evasion-fraud'            => 'Tax Evasion & Fraud'
            ]
        ],
        'Government Accountability' => [
            'slug' => 'government-accountability',
            'children' => [
                'procurement-spending-fraud'   => 'Procurement & Spending Fraud', // Replaced false-claims-act
                'public-corruption-ethics'     => 'Public Corruption & Ethics',
                'election-integrity'           => 'Election Integrity',
                'military-defense-reporting'   => 'Military & Defense Reporting'
            ]
        ],
        'Public Health & Safety' => [
            'slug' => 'public-health-safety',
            'children' => [
                'healthcare-medicare-fraud'    => 'Healthcare & Medicare Fraud',
                'environmental-protection'     => 'Environmental Protection',
                'food-drug-safety'             => 'Food & Drug Safety',
                'nuclear-energy-safety'        => 'Nuclear & Energy Safety',
                'transportation-safety'        => 'Transportation & Aviation Safety'
            ]
        ],
        'Privacy & Data Integrity' => [
            'slug' => 'privacy-data-integrity',
            'children' => [
                'cybersecurity-disclosure'     => 'Cybersecurity Disclosure',
                'hipaa-patient-privacy'        => 'HIPAA & Patient Privacy',
                'consumer-data-protection'     => 'Consumer Data Protection',
                'education-privacy-ferpa'      => 'Education Privacy (FERPA)'
            ]
        ]
    ];

    foreach ( $structure as $parent_name => $data ) {
        // 1. Ensure Parent exists
        $parent_term = term_exists( $data['slug'], $taxonomy );
        if ( ! $parent_term ) {
            $parent = wp_insert_term( $parent_name, $taxonomy, [ 'slug' => $data['slug'] ] );
        } else {
            $parent = is_array( $parent_term ) ? $parent_term : [ 'term_id' => $parent_term ];
        }

        if ( ! is_wp_error( $parent ) ) {
            $parent_id = (int) $parent['term_id'];

            // 2. Ensure Children exist under this Parent
            foreach ( $data['children'] as $child_slug => $child_name ) {
                if ( ! term_exists( $child_slug, $taxonomy ) ) {
                    wp_insert_term( $child_name, $taxonomy, [
                        'slug'   => $child_slug,
                        'parent' => $parent_id
                    ] );
                }
            }
        }
    }

    // Mark this version as seeded so the function does not run on every
    // admin_init. Bump the version string in the gate (below) when the
    // taxonomy structure changes to trigger a re-seed.
    update_option( 'ws_disclosure_cat_seeded', '1.0' );
}
// Gate: only run when the seeded-version option is missing or outdated.
// Bump the version string below whenever the taxonomy structure changes
// to force a re-seed on the next admin load.
add_action( 'admin_init', function() {
    if ( get_option( 'ws_disclosure_cat_seeded' ) !== '1.0' ) {
        ws_seed_disclosure_taxonomy();
    }
} );


// ══════════════════════════════════════════════════════════════════════════════
// Process Type Taxonomy — Seed
//
// Seeds the initial flat term list for ws_process_type. These terms
// cover the primary legal actions available to a whistleblower.
//
// Term structure is intentionally flat — process types are parallel
// categories (a statute may grant multiple) and do not nest.
//
// Bump the version string in the gate below to force a re-seed when
// terms are added, renamed, or reorganized.
// ══════════════════════════════════════════════════════════════════════════════

function ws_seed_process_type_taxonomy() {
    $taxonomy = 'ws_process_type';

    // Keyed by slug => Label
    $terms = [
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

// Gate: only run when the seeded-version option is missing or outdated.
add_action( 'admin_init', function() {
    if ( get_option( 'ws_process_type_seeded' ) !== '1.0' ) {
        ws_seed_process_type_taxonomy();
    }
} );



/*
    -----------------------------------------------------
    Taxonomy: Remedies
    Statutory recoveries available to the whistleblower.
    Gate: only run when the seeded-version option is missing or outdated.
    ────────────────────────────────────────────────────────────────
*/

add_action( 'admin_init', function() {
    if ( get_option( 'ws_remedy_seeded' ) !== '1.0' ) {
        ws_seed_remedy_taxonomy();
        update_option( 'ws_remedy_seeded', '1.0' );
    }
} );

function ws_seed_remedy_taxonomy() {
    $taxonomy = 'ws_remedy';
    $terms = [
        'Back Pay',
        'Front Pay',
        'Reinstatement',
        'Compensatory Damages',
        'Punitive Damages',
        'Treble Damages',
        'Attorney Fees',
        'Litigation Costs',
        'Expungement of Personnel Record'
    ];

    foreach ( $terms as $term ) {
        if ( ! term_exists( $term, $taxonomy ) ) {
            wp_insert_term( $term, $taxonomy );
        }
    }
}
