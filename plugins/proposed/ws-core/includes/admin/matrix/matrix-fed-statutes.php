<?php
/**
 * fed-statutes-matrix.php
 *
 * Seeds major federal whistleblower protection statutes.
 *
 * PURPOSE
 * -------
 * Creates jx-statute CPT posts for major federal whistleblower protection
 * laws. All records are scoped to the US ws_jurisdiction taxonomy term and
 * are attached to the jurisdiction page (attach_flag = 1).
 *
 * SEEDER RULES
 * ------------
 * - All seeded records receive ws_matrix_source = 'fed-statutes-matrix'.
 * - Gate: ws_seeded_fed_statutes_matrix / 1.2.0 (Unified Option-Gate Method).
 * - The US ws_jurisdiction term must exist before this seeder runs.
 *   Load order in loader.php guarantees jurisdiction-matrix.php fires first.
 *
 * @package    WhistleblowerShield
 * @since      3.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.0.0  Initial release (Phase 6.3).
 * 3.1.0  Fixed ws_jx_statute_burden_of_proof meta key (was missing jx_ infix).
 *        Added per-statute taxonomy assignments: ws_process_type, ws_disclosure_type,
 *        ws_remedies, ws_protected_class, ws_adverse_action_types,
 *        ws_disclosure_targets, ws_fee_shifting.
 *        Gate bumped to 1.1.0 so existing records are updated on next admin_init.
 * 3.2.0  Meta key renames to match ACF 3.5.0 overhaul:
 *        limit_value → sol_value, limit_unit → sol_unit,
 *        ws_jx_statute_trigger → sol_trigger (also corrects pre-existing mismatch —
 *        matrix was writing ws_jx_statute_trigger; ACF and query layer expected
 *        ws_jx_statute_limit_trigger; both now aligned on ws_jx_statute_sol_trigger),
 *        exhaustion_required → has_exhaustion, burden_of_proof → bop_standard.
 *        Added ws_employer_defense taxonomy assignment.
 *        Gate bumped to 1.2.0.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Federal Statute Data
// ════════════════════════════════════════════════════════════════════════════

$_ws_fed_statutes_matrix = [

    [
        'title'                       => 'Sarbanes-Oxley Act — Section 806',
        'slug'                        => 'sarbanes-oxley-section-806',
        'ws_jx_statute_official_name' => 'Sarbanes-Oxley Act, 18 U.S.C. § 1514A',
        'ws_jx_statute_common_name'   => 'Sarbanes-Oxley (SOX)',
        'limit_value'                 => 180,
        'limit_unit'                  => 'days',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 1,
        'exhaustion_details'          => 'Must file with OSHA within 180 days of the adverse action. OSHA has 60 days to investigate. If no final order within 180 days, complainant may file in federal district court.',
        'burden_of_proof'             => 'contributing_factor',
        'ws_display_order'            => 10,
        'post_content'                => 'Protects employees of publicly traded companies who report securities fraud, mail fraud, wire fraud, bank fraud, or violations of SEC rules. Enforced by OSHA.',
        'process_types'               => [ 'administrative-complaint', 'civil-lawsuit' ],
        'disclosure_types'            => [ 'securities-commodities-fraud', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'compensatory-damages', 'attorney-fees', 'litigation-costs' ],
        'protected_classes'           => [ 'corporate-staff' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'harassment', 'transfer' ],
        'disclosure_targets'          => [ 'agency-federal', 'internal-compliance', 'legislative-federal' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'Dodd-Frank Wall Street Reform Act — Section 922',
        'slug'                        => 'dodd-frank-section-922',
        'ws_jx_statute_official_name' => 'Dodd-Frank Act, 15 U.S.C. § 78u-6',
        'ws_jx_statute_common_name'   => 'Dodd-Frank',
        'limit_value'                 => 6,
        'limit_unit'                  => 'years',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 0,
        'exhaustion_details'          => '',
        'burden_of_proof'             => 'preponderance',
        'ws_display_order'            => 20,
        'post_content'                => 'Provides anti-retaliation protections and monetary awards (10–30% of sanctions over $1M) for reporting violations of federal securities laws directly to the SEC.',
        'process_types'               => [ 'civil-lawsuit', 'regulatory-tip' ],
        'disclosure_types'            => [ 'securities-commodities-fraud', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'double-back-pay', 'compensatory-damages', 'attorney-fees', 'bounty-qui-tam-award' ],
        'protected_classes'           => [ 'corporate-staff' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'harassment', 'contract-non-renewal' ],
        'disclosure_targets'          => [ 'agency-federal' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'False Claims Act — Qui Tam Provisions',
        'slug'                        => 'false-claims-act-qui-tam',
        'ws_jx_statute_official_name' => 'False Claims Act, 31 U.S.C. §§ 3729–3733',
        'ws_jx_statute_common_name'   => 'False Claims Act (FCA)',
        'limit_value'                 => 3,
        'limit_unit'                  => 'years',
        'trigger'                     => 'discovery',
        'exhaustion_required'         => 0,
        'exhaustion_details'          => '',
        'burden_of_proof'             => 'preponderance',
        'ws_display_order'            => 30,
        'post_content'                => 'Allows private citizens (relators) to file qui tam lawsuits on behalf of the government against those who defraud federal programs. Relators receive 15–30% of recovered funds.',
        'process_types'               => [ 'qui-tam', 'civil-lawsuit' ],
        'disclosure_types'            => [ 'procurement-spending-fraud', 'healthcare-medicare-fraud', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'double-back-pay', 'compensatory-damages', 'attorney-fees', 'litigation-costs', 'bounty-qui-tam-award' ],
        'protected_classes'           => [ 'corporate-staff', 'contractor-gig', 'federal-employee' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'harassment', 'contract-non-renewal' ],
        'disclosure_targets'          => [ 'court-filing', 'agency-federal' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'Whistleblower Protection Act',
        'slug'                        => 'whistleblower-protection-act',
        'ws_jx_statute_official_name' => 'Whistleblower Protection Act, 5 U.S.C. § 2302(b)(8)',
        'ws_jx_statute_common_name'   => 'Whistleblower Protection Act (WPA)',
        'limit_value'                 => 12,
        'limit_unit'                  => 'months',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 1,
        'exhaustion_details'          => 'Federal employees must generally file with the Office of Special Counsel (OSC) first. OSC has 240 days to investigate. If OSC declines to pursue, the employee may file an Individual Right of Action (IRA) with the MSPB.',
        'burden_of_proof'             => 'contributing_factor',
        'ws_display_order'            => 40,
        'post_content'                => 'Protects federal employees and applicants who disclose government waste, fraud, abuse, or law violations. Enforced by the Office of Special Counsel (OSC) and the Merit Systems Protection Board (MSPB).',
        'process_types'               => [ 'administrative-complaint', 'civil-lawsuit' ],
        'disclosure_types'            => [ 'procurement-spending-fraud', 'public-corruption-ethics', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'compensatory-damages', 'attorney-fees', 'expungement-of-personnel-record' ],
        'protected_classes'           => [ 'federal-employee', 'job-applicant' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'transfer', 'security-clearance-action', 'disciplinary-action' ],
        'disclosure_targets'          => [ 'agency-federal', 'legislative-federal', 'internal-compliance' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'Whistleblower Protection Enhancement Act',
        'slug'                        => 'whistleblower-protection-enhancement-act',
        'ws_jx_statute_official_name' => 'Whistleblower Protection Enhancement Act of 2012, Pub. L. 112-199',
        'ws_jx_statute_common_name'   => 'Whistleblower Protection Enhancement Act (WPEA)',
        'limit_value'                 => 12,
        'limit_unit'                  => 'months',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 1,
        'exhaustion_details'          => 'Same as the Whistleblower Protection Act — must exhaust OSC remedies before filing with the MSPB.',
        'burden_of_proof'             => 'contributing_factor',
        'ws_display_order'            => 50,
        'post_content'                => 'Expands WPA protections to cover disclosures of classified information to Congress, disclosures made in the ordinary course of duties, and protections for employees of the TSA.',
        'process_types'               => [ 'administrative-complaint', 'civil-lawsuit', 'congressional-disclosure' ],
        'disclosure_types'            => [ 'public-corruption-ethics', 'military-defense-reporting', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'compensatory-damages', 'attorney-fees', 'expungement-of-personnel-record' ],
        'protected_classes'           => [ 'federal-employee' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'security-clearance-action', 'privilege-revocation' ],
        'disclosure_targets'          => [ 'agency-federal', 'legislative-federal' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'OSHA — Section 11(c) of the OSH Act',
        'slug'                        => 'osh-act-section-11c',
        'ws_jx_statute_official_name' => 'Occupational Safety and Health Act, 29 U.S.C. § 660(c)',
        'ws_jx_statute_common_name'   => 'OSH Act Section 11(c)',
        'limit_value'                 => 30,
        'limit_unit'                  => 'days',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 1,
        'exhaustion_details'          => 'Must file with OSHA within 30 days. OSHA investigates and may order reinstatement, back pay, and other remedies.',
        'burden_of_proof'             => 'contributing_factor',
        'ws_display_order'            => 60,
        'post_content'                => 'Protects private-sector employees who report workplace safety violations or participate in OSHA proceedings from retaliation.',
        'process_types'               => [ 'administrative-complaint' ],
        'disclosure_types'            => [ 'occupational-health-safety', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'compensatory-damages', 'attorney-fees' ],
        'protected_classes'           => [ 'corporate-staff' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'harassment', 'disciplinary-action' ],
        'disclosure_targets'          => [ 'agency-federal', 'internal-supervisor', 'internal-compliance' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

    [
        'title'                       => 'National Defense Authorization Act — Section 4701',
        'slug'                        => 'ndaa-section-4701',
        'ws_jx_statute_official_name' => 'National Defense Authorization Act, 10 U.S.C. § 4701 (formerly § 2409)',
        'ws_jx_statute_common_name'   => 'NDAA Section 4701',
        'limit_value'                 => 3,
        'limit_unit'                  => 'years',
        'trigger'                     => 'adverse_action',
        'exhaustion_required'         => 1,
        'exhaustion_details'          => 'Must file a complaint with the Inspector General of the relevant agency. If no action within 210 days, may file in federal district court.',
        'burden_of_proof'             => 'preponderance',
        'ws_display_order'            => 70,
        'post_content'                => 'Protects employees of defense contractors, subcontractors, grantees, and personal services contractors who disclose fraud, waste, abuse, or violations related to defense contracts.',
        'process_types'               => [ 'administrative-complaint', 'civil-lawsuit' ],
        'disclosure_types'            => [ 'procurement-spending-fraud', 'military-defense-reporting', 'retaliation-protection' ],
        'remedies'                    => [ 'reinstatement', 'back-pay', 'compensatory-damages', 'attorney-fees', 'litigation-costs' ],
        'protected_classes'           => [ 'contractor-gig', 'corporate-staff' ],
        'adverse_actions'             => [ 'termination', 'demotion', 'suspension', 'contract-non-renewal', 'security-clearance-action' ],
        'disclosure_targets'          => [ 'agency-federal', 'legislative-federal', 'law-enforcement' ],
        'fee_shifting'                => 'unilateral-pro-plaintiff',
    ],

];


// ════════════════════════════════════════════════════════════════════════════
// Seeder: ws_seed_fed_statutes_matrix
// ════════════════════════════════════════════════════════════════════════════

function ws_seed_fed_statutes_matrix() {
    global $_ws_fed_statutes_matrix;

    // Resolve the US jurisdiction term ID.
    $us_term = get_term_by( 'slug', 'us', WS_JURISDICTION_TERM_ID );
    if ( ! $us_term || is_wp_error( $us_term ) ) {
        return; // Taxonomy terms not yet seeded — bail.
    }
    $us_term_id = (int) $us_term->term_id;

    foreach ( $_ws_fed_statutes_matrix as $statute ) {

        $existing = get_page_by_path( $statute['slug'], OBJECT, 'jx-statute' );

        if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
            define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
        }

        if ( $existing ) {
            $post_id = $existing->ID;
            wp_update_post( [
                'ID'           => $post_id,
                'post_title'   => $statute['title'],
                'post_name'    => $statute['slug'],
                'post_content' => $statute['post_content'] ?? '',
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'   => $statute['title'],
                'post_name'    => $statute['slug'],
                'post_type'    => 'jx-statute',
                'post_status'  => 'publish',
                'post_content' => $statute['post_content'] ?? '',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // Write structured statute fields.
        $meta_fields = [
            'ws_jx_statute_official_name' => $statute['ws_jx_statute_official_name'] ?? '',
            'ws_jx_statute_common_name'   => $statute['ws_jx_statute_common_name']   ?? '',
            'ws_jx_statute_sol_value'     => $statute['limit_value']                 ?? '',
            'ws_jx_statute_sol_unit'      => $statute['limit_unit']                  ?? '',
            'ws_jx_statute_sol_trigger'   => $statute['trigger']                     ?? '',
            'ws_jx_statute_has_exhaustion'   => $statute['exhaustion_required']      ?? 0,
            'ws_jx_statute_exhaustion_details' => $statute['exhaustion_details']     ?? '',
            'ws_jx_statute_bop_standard'  => $statute['burden_of_proof']             ?? '',
            'ws_attach_flag'              => '1',
            'ws_display_order'            => $statute['ws_display_order']            ?? 999,
        ];

        foreach ( $meta_fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        // Assign US jurisdiction term.
        wp_set_object_terms( $post_id, $us_term_id, WS_JURISDICTION_TERM_ID );

        // ── Taxonomy Assignments ──────────────────────────────────────────

        // ws_process_type
        ws_matrix_assign_terms( $post_id, $statute['process_types'] ?? [], 'ws_process_type' );

        // ws_disclosure_type
        ws_matrix_assign_terms( $post_id, $statute['disclosure_types'] ?? [], 'ws_disclosure_type' );

        // ws_remedies
        ws_matrix_assign_terms( $post_id, $statute['remedies'] ?? [], 'ws_remedies' );

        // ws_protected_class
        ws_matrix_assign_terms( $post_id, $statute['protected_classes'] ?? [], 'ws_protected_class' );

        // ws_adverse_action_types
        ws_matrix_assign_terms( $post_id, $statute['adverse_actions'] ?? [], 'ws_adverse_action_types' );

        // ws_disclosure_targets
        ws_matrix_assign_terms( $post_id, $statute['disclosure_targets'] ?? [], 'ws_disclosure_targets' );

        // ws_fee_shifting (single value — wrap in array for shared helper)
        if ( ! empty( $statute['fee_shifting'] ) ) {
            ws_matrix_assign_terms( $post_id, [ $statute['fee_shifting'] ], 'ws_fee_shifting' );
        }

        // ws_employer_defense
        ws_matrix_assign_terms( $post_id, $statute['employer_defense'] ?? [], 'ws_employer_defense' );

        // Mark as seeded.
        update_post_meta( $post_id, 'ws_matrix_source', 'fed-statutes-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_fed_statutes_matrix' ) !== '1.2.0' ) {
        ws_seed_fed_statutes_matrix();
        update_option( 'ws_seeded_fed_statutes_matrix', '1.2.0' );
    }
} );
