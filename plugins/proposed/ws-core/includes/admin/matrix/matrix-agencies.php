<?php
/**
 * matrix-agencies.php — Seeds nationwide federal agencies relevant to whistleblower protection.
 *
 * @package WhistleblowerShield
 * @since   3.0.0
 * @version 3.10.0
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Agency Data
// ════════════════════════════════════════════════════════════════════════════

global $_ws_agency_matrix;
$_ws_agency_matrix = [

    [
        'title'              => 'U.S. Securities and Exchange Commission',
        'slug'               => 'sec-whistleblower-program',
        'ws_agency_name'     => 'U.S. Securities and Exchange Commission',
        'ws_agency_acronym'  => 'SEC',
        'ws_agency_url'      => 'https://www.sec.gov/whistleblower',
        'ws_agency_mission'  => 'Administers the SEC Whistleblower Program under Dodd-Frank, awarding 10–30% of sanctions over $1 million to eligible whistleblowers.',
    ],

    [
        'title'              => 'Occupational Safety and Health Administration',
        'slug'               => 'osha-whistleblower-protection-program',
        'ws_agency_name'     => 'Occupational Safety and Health Administration',
        'ws_agency_acronym'  => 'OSHA',
        'ws_agency_url'      => 'https://www.whistleblowers.gov',
        'ws_agency_mission'  => 'Investigates retaliation complaints under 25+ federal statutes including Sarbanes-Oxley, Clean Air Act, and STAA.',
    ],

    [
        'title'              => 'U.S. Office of Special Counsel',
        'slug'               => 'office-of-special-counsel',
        'ws_agency_name'     => 'U.S. Office of Special Counsel',
        'ws_agency_acronym'  => 'OSC',
        'ws_agency_url'      => 'https://osc.gov',
        'ws_agency_mission'  => 'Receives disclosures from federal employees, investigates prohibited personnel practices, and enforces the Whistleblower Protection Act.',
    ],

    [
        'title'              => 'Merit Systems Protection Board',
        'slug'               => 'merit-systems-protection-board',
        'ws_agency_name'     => 'Merit Systems Protection Board',
        'ws_agency_acronym'  => 'MSPB',
        'ws_agency_url'      => 'https://www.mspb.gov',
        'ws_agency_mission'  => 'Adjudicates federal employee appeals including individual right of action (IRA) cases under the Whistleblower Protection Act.',
    ],

    [
        'title'              => 'National Labor Relations Board',
        'slug'               => 'national-labor-relations-board',
        'ws_agency_name'     => 'National Labor Relations Board',
        'ws_agency_acronym'  => 'NLRB',
        'ws_agency_url'      => 'https://www.nlrb.gov',
        'ws_agency_mission'  => 'Protects the right of private-sector employees to act collectively, which may include whistleblowing in concerted protected activity.',
    ],

    [
        'title'              => 'Commodity Futures Trading Commission',
        'slug'               => 'cftc-whistleblower-program',
        'ws_agency_name'     => 'Commodity Futures Trading Commission',
        'ws_agency_acronym'  => 'CFTC',
        'ws_agency_url'      => 'https://www.whistleblower.gov',
        'ws_agency_mission'  => 'Administers the CFTC Whistleblower Program, providing awards to eligible whistleblowers reporting violations of the Commodity Exchange Act.',
    ],

    [
        'title'              => 'Internal Revenue Service Whistleblower Office',
        'slug'               => 'irs-whistleblower-office',
        'ws_agency_name'     => 'Internal Revenue Service — Whistleblower Office',
        'ws_agency_acronym'  => 'IRS WO',
        'ws_agency_url'      => 'https://www.irs.gov/compliance/whistleblower-informant-award',
        'ws_agency_mission'  => 'Awards 15–30% of collected proceeds to informants who report federal tax underpayments above $2 million (corporate) or $200,000 income threshold (individual).',
    ],

    [
        'title'              => 'U.S. Environmental Protection Agency',
        'slug'               => 'epa-whistleblower-protection',
        'ws_agency_name'     => 'U.S. Environmental Protection Agency',
        'ws_agency_acronym'  => 'EPA',
        'ws_agency_url'      => 'https://www.epa.gov/ocr/whistleblower-protection',
        'ws_agency_mission'  => 'Receives retaliation complaints under environmental whistleblower statutes including Clean Air Act, Clean Water Act, and Safe Drinking Water Act.',
    ],

    [
        'title'              => 'Department of Justice — False Claims Act Unit',
        'slug'               => 'doj-false-claims-act',
        'ws_agency_name'     => 'U.S. Department of Justice — Civil Division',
        'ws_agency_acronym'  => 'DOJ',
        'ws_agency_url'      => 'https://www.justice.gov/civil/false-claims-act',
        'ws_agency_mission'  => 'Pursues False Claims Act qui tam actions. Relators (whistleblowers) may receive 15–30% of government recoveries under 31 U.S.C. § 3730.',
    ],

];


// ════════════════════════════════════════════════════════════════════════════
// Seeder: ws_seed_agency_matrix
// ════════════════════════════════════════════════════════════════════════════

function ws_seed_agency_matrix() {

    // Resolve the US jurisdiction term ID.
    $us_term = ws_jx_term_by_code( 'us' );
    if ( ! $us_term || is_wp_error( $us_term ) ) {
        return; // Taxonomy terms not yet seeded — bail.
    }
    $us_term_id = (int) $us_term->term_id;

    if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
        define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
    }

    foreach ( $_ws_agency_matrix as $agency ) {

        $existing = get_page_by_path( $agency['slug'], OBJECT, 'ws-agency' );

        if ( $existing ) {
            $post_id = $existing->ID;
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => $agency['title'],
                'post_name'  => $agency['slug'],
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'  => $agency['title'],
                'post_name'   => $agency['slug'],
                'post_type'   => 'ws-agency',
                'post_status' => 'publish',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // Write agency meta fields.
        $meta_fields = [
            'ws_agency_name'    => $agency['ws_agency_name']    ?? '',
            'ws_agency_acronym' => $agency['ws_agency_acronym'] ?? '',
            'ws_agency_url'     => $agency['ws_agency_url']     ?? '',
            'ws_agency_mission' => $agency['ws_agency_mission'] ?? '',
        ];

        foreach ( $meta_fields as $key => $value ) {
            if ( $value !== '' ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // Assign US jurisdiction term.
        wp_set_object_terms( $post_id, $us_term_id, WS_JURISDICTION_TAXONOMY );

        // Assign ws_languages: English (all seeded federal agencies operate in English).
        $english_term = get_term_by( 'slug', 'english', 'ws_languages' );
        if ( $english_term && ! is_wp_error( $english_term ) ) {
            wp_set_object_terms( $post_id, (int) $english_term->term_id, 'ws_languages' );
        }

        // Mark as seeded.
        update_post_meta( $post_id, 'ws_matrix_source', 'agency-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_agency_matrix' ) !== '1.0.0' ) {
        ws_seed_agency_matrix();
        update_option( 'ws_seeded_agency_matrix', '1.0.0' );
    }
} );
