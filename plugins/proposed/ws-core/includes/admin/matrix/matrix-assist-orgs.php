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
 * 3.1.0  Fixed meta key prefix: ws_ao_* → ws_aorg_* to match ACF registrations.
 *        ws_ao_url → ws_aorg_website_url; ws_ao_phone → ws_aorg_phone.
 *        Mission/provides text moved to post_content (no dedicated ACF meta field).
 *        ws_ao_name and ws_ao_acronym removed (post title is canonical; no acronym field).
 *        Gate bumped to 1.1.0 so existing records are corrected on next admin_init.
 * 3.3.0  Added aorg_type slug to each record; seeder now assigns ws_aorg_type
 *        taxonomy term. Gate bumped to 1.2.0.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Assist-Org Data
// ════════════════════════════════════════════════════════════════════════════

$_ws_assist_org_matrix = [

    [
        'title'                => 'Government Accountability Project',
        'slug'                 => 'government-accountability-project',
        'post_content'         => 'Promotes government and corporate accountability by advancing a culture of transparency, providing legal representation to whistleblowers, and advocating for strong whistleblower laws. Services: Legal representation, policy advocacy, case consultation.',
        'ws_aorg_website_url'  => 'https://whistleblower.org',
        'ws_aorg_phone'        => '(202) 457-0034',
        'aorg_type'            => 'nonprofit',
        'case_stages'          => [ 'pre-report', 'post-report', 'retaliation-active', 'litigation' ],
    ],

    [
        'title'                => 'National Whistleblower Center',
        'slug'                 => 'national-whistleblower-center',
        'post_content'         => 'Advocates for the rights of whistleblowers, promotes whistleblower protections, and educates the public and policymakers about the importance of whistleblowing. Services: Legal referrals, policy advocacy, public education.',
        'ws_aorg_website_url'  => 'https://www.whistleblowers.org',
        'ws_aorg_phone'        => '(202) 457-0034',
        'aorg_type'            => 'nonprofit',
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'title'                => 'Project On Government Oversight',
        'slug'                 => 'project-on-government-oversight',
        'post_content'         => 'Investigates and exposes waste, corruption, abuse of power, and when the government fails to serve the public interest. Supports federal whistleblowers. Services: Investigative support, congressional referrals, public advocacy.',
        'ws_aorg_website_url'  => 'https://www.pogo.org',
        'ws_aorg_phone'        => '(202) 347-1122',
        'aorg_type'            => 'advocacy',
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'title'                => 'Whistleblower Aid',
        'slug'                 => 'whistleblower-aid',
        'post_content'         => 'Provides free legal assistance and support to clients who want to safely and legally disclose wrongdoing in the public interest. Services: Free legal representation, secure communications, intake screening.',
        'ws_aorg_website_url'  => 'https://whistlebloweraid.org',
        'ws_aorg_phone'        => '',
        'aorg_type'            => 'legal-aid',
        'case_stages'          => [ 'pre-report', 'post-report', 'retaliation-active', 'litigation' ],
    ],

    [
        'title'                => 'National Employment Law Project',
        'slug'                 => 'national-employment-law-project',
        'post_content'         => 'Champions the rights of low-wage and unemployed workers through research and advocacy, including for workers who face retaliation for reporting violations. Services: Policy advocacy, research, worker rights education.',
        'ws_aorg_website_url'  => 'https://www.nelp.org',
        'ws_aorg_phone'        => '(212) 285-3025',
        'aorg_type'            => 'advocacy',
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'title'                => 'Government Accountability Office — FraudNet',
        'slug'                 => 'gao-fraudnet',
        'post_content'         => 'Receives allegations of fraud, waste, abuse, and mismanagement of federal funds. Referrals are made to the appropriate Inspector General or law enforcement agency. Services: Anonymous reporting intake, federal fraud referrals.',
        'ws_aorg_website_url'  => 'https://www.gao.gov/about/what-gao-does/fraudnet',
        'ws_aorg_phone'        => '1-800-424-5454',
        'aorg_type'            => 'oversight-office',
        'case_stages'          => [ 'pre-report' ],
    ],

    [
        'title'                => 'Inspector General Community',
        'slug'                 => 'ig-community',
        'post_content'         => 'The Council of the Inspectors General on Integrity and Efficiency coordinates federal oversight activities and provides a directory of agency IGs who receive whistleblower disclosures. Services: Disclosure intake through agency Inspectors General, oversight coordination.',
        'ws_aorg_website_url'  => 'https://www.ignet.gov',
        'ws_aorg_phone'        => '',
        'aorg_type'            => 'oversight-office',
        'case_stages'          => [ 'pre-report', 'post-report' ],
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
                'ID'           => $post_id,
                'post_title'   => $org['title'],
                'post_name'    => $org['slug'],
                'post_content' => $org['post_content'] ?? '',
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'   => $org['title'],
                'post_name'    => $org['slug'],
                'post_type'    => 'ws-assist-org',
                'post_status'  => 'publish',
                'post_content' => $org['post_content'] ?? '',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
            define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
        }

        // Write org meta fields.
        $meta_fields = [
            'ws_aorg_website_url' => $org['ws_aorg_website_url'] ?? '',
            'ws_aorg_phone'       => $org['ws_aorg_phone']       ?? '',
        ];

        foreach ( $meta_fields as $key => $value ) {
            if ( $value !== '' ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // Assign organization type taxonomy term.
        if ( ! empty( $org['aorg_type'] ) ) {
            wp_set_object_terms( $post_id, $org['aorg_type'], 'ws_aorg_type' );
        }

        // Assign US jurisdiction term.
        wp_set_object_terms( $post_id, $us_term_id, WS_JURISDICTION_TERM_ID );

        // Assign ws_languages: English (all seeded national orgs operate in English).
        $english_term = get_term_by( 'slug', 'english', 'ws_languages' );
        if ( $english_term && ! is_wp_error( $english_term ) ) {
            wp_set_object_terms( $post_id, (int) $english_term->term_id, 'ws_languages' );
        }

        // Assign ws_case_stage terms from per-record slugs array.
        if ( ! empty( $org['case_stages'] ) ) {
            $stage_ids = [];
            foreach ( $org['case_stages'] as $stage_slug ) {
                $stage_term = get_term_by( 'slug', $stage_slug, 'ws_case_stage' );
                if ( $stage_term && ! is_wp_error( $stage_term ) ) {
                    $stage_ids[] = (int) $stage_term->term_id;
                }
            }
            if ( ! empty( $stage_ids ) ) {
                wp_set_object_terms( $post_id, $stage_ids, 'ws_case_stage' );
            }
        }

        // Mark as seeded.
        update_post_meta( $post_id, 'ws_matrix_source', 'assist-org-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_assist_org_matrix' ) !== '1.2.0' ) {
        ws_seed_assist_org_matrix();
        update_option( 'ws_seeded_assist_org_matrix', '1.2.0' );
    }
} );
