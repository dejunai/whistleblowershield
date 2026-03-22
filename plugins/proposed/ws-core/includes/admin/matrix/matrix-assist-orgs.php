<?php
/**
 * assist-org-matrix.php
 *
 * Seeds nationwide whistleblower support organizations.
 *
 * PURPOSE
 * -------
 * Creates ws-assist-org CPT posts for major national organizations that
 * provide legal support, advocacy, and resources to whistleblowers.
 * All records are scoped to the US ws_jurisdiction taxonomy term.
 *
 * Only nationwide organizations are seeded here. State or regional
 * organizations are managed via the admin UI.
 *
 * SEEDER RULES
 * ------------
 * - All seeded records receive ws_matrix_source = 'assist-org-matrix'.
 * - Gate: ws_seeded_assist_org_matrix / 1.0.0 (Unified Option-Gate Method).
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
 * 3.0.0  Initial release (Phase 6.4).
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Assist-Org Data
// ════════════════════════════════════════════════════════════════════════════

$_ws_assist_org_matrix = [

    [
        'title'            => 'Government Accountability Project',
        'slug'             => 'government-accountability-project',
        'ws_ao_name'       => 'Government Accountability Project',
        'ws_ao_acronym'    => 'GAP',
        'ws_ao_url'        => 'https://whistleblower.org',
        'ws_ao_mission'    => 'Promotes government and corporate accountability by advancing a culture of transparency, providing legal representation to whistleblowers, and advocating for strong whistleblower laws.',
        'ws_ao_phone'      => '(202) 457-0034',
        'ws_ao_provides'   => 'Legal representation, policy advocacy, case consultation.',
    ],

    [
        'title'            => 'National Whistleblower Center',
        'slug'             => 'national-whistleblower-center',
        'ws_ao_name'       => 'National Whistleblower Center',
        'ws_ao_acronym'    => 'NWC',
        'ws_ao_url'        => 'https://www.whistleblowers.org',
        'ws_ao_mission'    => 'Advocates for the rights of whistleblowers, promotes whistleblower protections, and educates the public and policymakers about the importance of whistleblowing.',
        'ws_ao_phone'      => '(202) 457-0034',
        'ws_ao_provides'   => 'Legal referrals, policy advocacy, public education.',
    ],

    [
        'title'            => 'Project On Government Oversight',
        'slug'             => 'project-on-government-oversight',
        'ws_ao_name'       => 'Project On Government Oversight',
        'ws_ao_acronym'    => 'POGO',
        'ws_ao_url'        => 'https://www.pogo.org',
        'ws_ao_mission'    => 'Investigates and exposes waste, corruption, abuse of power, and when the government fails to serve the public interest. Supports federal whistleblowers.',
        'ws_ao_phone'      => '(202) 347-1122',
        'ws_ao_provides'   => 'Investigative support, congressional referrals, public advocacy.',
    ],

    [
        'title'            => 'Whistleblower Aid',
        'slug'             => 'whistleblower-aid',
        'ws_ao_name'       => 'Whistleblower Aid',
        'ws_ao_acronym'    => '',
        'ws_ao_url'        => 'https://whistlebloweraid.org',
        'ws_ao_mission'    => 'Provides free legal assistance and support to clients who want to safely and legally disclose wrongdoing in the public interest.',
        'ws_ao_phone'      => '',
        'ws_ao_provides'   => 'Free legal representation, secure communications, intake screening.',
    ],

    [
        'title'            => 'National Employment Law Project',
        'slug'             => 'national-employment-law-project',
        'ws_ao_name'       => 'National Employment Law Project',
        'ws_ao_acronym'    => 'NELP',
        'ws_ao_url'        => 'https://www.nelp.org',
        'ws_ao_mission'    => 'Champions the rights of low-wage and unemployed workers through research and advocacy, including for workers who face retaliation for reporting violations.',
        'ws_ao_phone'      => '(212) 285-3025',
        'ws_ao_provides'   => 'Policy advocacy, research, worker rights education.',
    ],

    [
        'title'            => 'Government Accountability Office — FraudNet',
        'slug'             => 'gao-fraudnet',
        'ws_ao_name'       => 'U.S. Government Accountability Office — FraudNet',
        'ws_ao_acronym'    => 'GAO FraudNet',
        'ws_ao_url'        => 'https://www.gao.gov/about/what-gao-does/fraudnet',
        'ws_ao_mission'    => 'Receives allegations of fraud, waste, abuse, and mismanagement of federal funds. Referrals are made to the appropriate Inspector General or law enforcement agency.',
        'ws_ao_phone'      => '1-800-424-5454',
        'ws_ao_provides'   => 'Anonymous reporting intake, federal fraud referrals.',
    ],

    [
        'title'            => 'Inspector General Community',
        'slug'             => 'ig-community',
        'ws_ao_name'       => 'Inspector General Community (CIGIE)',
        'ws_ao_acronym'    => 'CIGIE',
        'ws_ao_url'        => 'https://www.ignet.gov',
        'ws_ao_mission'    => 'The Council of the Inspectors General on Integrity and Efficiency coordinates federal oversight activities and provides a directory of agency IGs who receive whistleblower disclosures.',
        'ws_ao_phone'      => '',
        'ws_ao_provides'   => 'Disclosure intake through agency Inspectors General, oversight coordination.',
    ],

];


// ════════════════════════════════════════════════════════════════════════════
// Seeder: ws_seed_assist_org_matrix
// ════════════════════════════════════════════════════════════════════════════

function ws_seed_assist_org_matrix() {
    global $_ws_assist_org_matrix;

    // Resolve the US jurisdiction term ID.
    $us_term = get_term_by( 'slug', 'us', WS_JURISDICTION_TERM_ID );
    if ( ! $us_term || is_wp_error( $us_term ) ) {
        return; // Taxonomy terms not yet seeded — bail.
    }
    $us_term_id = (int) $us_term->term_id;

    foreach ( $_ws_assist_org_matrix as $org ) {

        $existing = get_page_by_path( $org['slug'], OBJECT, 'ws-assist-org' );

        if ( $existing ) {
            $post_id = $existing->ID;
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => $org['title'],
                'post_name'  => $org['slug'],
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'  => $org['title'],
                'post_name'   => $org['slug'],
                'post_type'   => 'ws-assist-org',
                'post_status' => 'publish',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // Write org meta fields.
        $meta_fields = [
            'ws_ao_name'     => $org['ws_ao_name']     ?? '',
            'ws_ao_acronym'  => $org['ws_ao_acronym']  ?? '',
            'ws_ao_url'      => $org['ws_ao_url']       ?? '',
            'ws_ao_mission'  => $org['ws_ao_mission']   ?? '',
            'ws_ao_phone'    => $org['ws_ao_phone']     ?? '',
            'ws_ao_provides' => $org['ws_ao_provides']  ?? '',
        ];

        foreach ( $meta_fields as $key => $value ) {
			
			if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
				define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
			}
			
            if ( $value !== '' ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // Assign US jurisdiction term.
        wp_set_object_terms( $post_id, $us_term_id, WS_JURISDICTION_TERM_ID );

        // Mark as seeded.
        update_post_meta( $post_id, 'ws_matrix_source', 'assist-org-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_assist_org_matrix' ) !== '1.0.0' ) {
        ws_seed_assist_org_matrix();
        update_option( 'ws_seeded_assist_org_matrix', '1.0.0' );
    }
} );
