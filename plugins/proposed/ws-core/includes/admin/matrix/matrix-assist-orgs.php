<?php
/**
 * matrix-assist-orgs.php
 *
 * Seeds nationwide and federal-scope whistleblower support organizations.
 *
 * PURPOSE
 * -------
 * Creates ws-assist-org CPT posts for major national organizations that
 * provide legal support, advocacy, and resources to whistleblowers.
 * All records are tagged with the US ws_jurisdiction taxonomy term.
 *
 * NATIONWIDE vs FEDERAL-SCOPE
 * ---------------------------
 * is_nationwide = 1  Org operates across all 57 jurisdictions (e.g. ACLU,
 *                    GAP). Appears in ws_get_nationwide_assist_org_data().
 * is_nationwide = 0  Org serves federal workers / federal law only (e.g.
 *                    OSC, GAO FraudNet). US jurisdiction tag reflects scope
 *                    of law, not geographic reach. Not returned by the
 *                    nationwide query; accessible via jurisdiction pages.
 *
 * State or regional organizations are managed via the admin UI.
 *
 * SEEDER RULES
 * ------------
 * - All seeded records receive ws_matrix_source = 'assist-org-matrix'.
 * - Gate: ws_seeded_assist_org_matrix / 1.4.0 (Unified Option-Gate Method).
 * - The US ws_jurisdiction term must exist before this seeder runs.
 *   Load order in loader.php guarantees jurisdiction-matrix.php fires first.
 * - ws_disclosure_type slugs must align with ws_seed_disclosure_type_taxonomy()
 *   in register-taxonomies.php.
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
 * 3.5.0  Added description key to each record; seeder now writes ws_aorg_description
 *        meta. Gate bumped to 1.3.0. post_content retains plain-text copy for
 *        WP search indexing; ws_aorg_description is the ACF-managed canonical source.
 * 3.6.0  Full structural overhaul — matrix-assist-orgs-extended.php and
 *        matrix-assist-orgs-proposed.txt merged here (both scratch files retired).
 *        Added: internal_id, intake_url, email, cost_model, is_nationwide,
 *        accepts_anon, has_attorneys, services, sectors, disclosure_types.
 *        Seeder now writes all corresponding ACF meta fields and assigns
 *        ws_disclosure_type taxonomy terms by slug. 14 total organizations.
 *        Gate bumped to 1.4.0.
 * 3.7.0  Replaced ws_aorg_employment_sectors post_meta write with
 *        wp_set_object_terms on ws_employment_sector taxonomy.
 *        All sectors slugs remapped: federal → federal-employee,
 *        state → state-local-employee, private → private-sector,
 *        military → military-defense, nonprofit → nonprofit-ngo,
 *        any → all-sectors. Gate bumped to 1.5.0.
 * 3.7.1  is_nationwide corrected for federal-scope-only orgs (POGO,
 *        GAO FraudNet, IG Community, OSC, OPM OIG): 1 → 0. These orgs
 *        serve federal workers under federal law; the us jurisdiction tag
 *        reflects legal scope, not geographic reach. Gate bumped to 1.6.0.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Assist-Org Data
// ════════════════════════════════════════════════════════════════════════════

$_ws_assist_org_matrix = [

    // ── Dedicated whistleblower nonprofits / legal aid ────────────────────

    [
        'internal_id'          => 'gap-national',
        'title'                => 'Government Accountability Project',
        'slug'                 => 'government-accountability-project',
        'description'          => 'Promotes government and corporate accountability by advancing a culture of transparency, providing legal representation to whistleblowers, and advocating for strong whistleblower laws.',
        'post_content'         => 'Promotes government and corporate accountability by advancing a culture of transparency, providing legal representation to whistleblowers, and advocating for strong whistleblower laws.',
        'ws_aorg_website_url'  => 'https://whistleblower.org',
        'ws_aorg_intake_url'   => 'https://whistleblower.org/intake/',
        'ws_aorg_phone'        => '(202) 457-0034',
        'ws_aorg_email'        => 'info@whistleblower.org',
        'aorg_type'            => 'nonprofit',
        'cost_model'           => 'pro_bono',
        'is_nationwide'        => 1,
        'accepts_anon'         => 1,
        'has_attorneys'        => 1,
        'services'             => [ 'legal_rep', 'consultation', 'advocacy', 'media' ],
        'sectors'              => [ 'federal-employee', 'private-sector', 'nonprofit-ngo' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'procurement-spending-fraud', 'environmental-protection', 'occupational-health-safety', 'securities-commodities-fraud' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'retaliation-active', 'litigation' ],
    ],

    [
        'internal_id'          => 'nwc-dc',
        'title'                => 'National Whistleblower Center',
        'slug'                 => 'national-whistleblower-center',
        'description'          => 'Advocates for the rights of whistleblowers, promotes whistleblower protections, and educates the public and policymakers about the importance of whistleblowing.',
        'post_content'         => 'Advocates for the rights of whistleblowers, promotes whistleblower protections, and educates the public and policymakers about the importance of whistleblowing.',
        'ws_aorg_website_url'  => 'https://www.whistleblowers.org',
        'ws_aorg_intake_url'   => 'https://www.whistleblowers.org/report-fraud/',
        'ws_aorg_phone'        => '(202) 342-1903',
        'ws_aorg_email'        => 'contact@whistleblowers.org',
        'aorg_type'            => 'nonprofit',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'referral', 'advocacy', 'media' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'securities-commodities-fraud', 'tax-evasion-fraud', 'public-corruption-ethics', 'procurement-spending-fraud', 'healthcare-medicare-fraud', 'environmental-protection' ],
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'internal_id'          => 'whistleblower-aid-dc',
        'title'                => 'Whistleblower Aid',
        'slug'                 => 'whistleblower-aid',
        'description'          => 'Provides free legal assistance and support to clients who want to safely and legally disclose wrongdoing in the public interest.',
        'post_content'         => 'Provides free legal assistance and support to clients who want to safely and legally disclose wrongdoing in the public interest.',
        'ws_aorg_website_url'  => 'https://whistlebloweraid.org',
        'ws_aorg_intake_url'   => 'https://whistlebloweraid.org/contact/',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'legal-aid',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 1,
        'has_attorneys'        => 1,
        'services'             => [ 'legal_rep', 'consultation', 'doc_review', 'retaliation' ],
        'sectors'              => [ 'federal-employee', 'private-sector' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'classified-information', 'intelligence-community', 'environmental-protection', 'cybersecurity-disclosure' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'retaliation-active', 'litigation' ],
    ],

    [
        'internal_id'          => 'pogo-dc',
        'title'                => 'Project On Government Oversight',
        'slug'                 => 'project-on-government-oversight',
        'description'          => 'Investigates and exposes waste, corruption, abuse of power, and when the government fails to serve the public interest, including supporting federal whistleblowers.',
        'post_content'         => 'Investigates and exposes waste, corruption, abuse of power, and when the government fails to serve the public interest, including supporting federal whistleblowers.',
        'ws_aorg_website_url'  => 'https://www.pogo.org',
        'ws_aorg_intake_url'   => 'https://www.pogo.org/report-corruption',
        'ws_aorg_phone'        => '(202) 347-1122',
        'ws_aorg_email'        => 'info@pogo.org',
        'aorg_type'            => 'advocacy',
        'cost_model'           => 'free',
        'is_nationwide'        => 0,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'advocacy', 'media' ],
        'sectors'              => [ 'federal-employee', 'military-defense' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'procurement-spending-fraud', 'military-defense-reporting', 'environmental-protection' ],
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'internal_id'          => 'tafc-national',
        'title'                => 'Taxpayers Against Fraud Education Fund',
        'slug'                 => 'taxpayers-against-fraud-education-fund',
        'description'          => 'Supports whistleblowers and their counsel in False Claims Act and related anti-fraud cases, and maintains a network of qui tam attorneys.',
        'post_content'         => 'Supports whistleblowers and their counsel in False Claims Act and related anti-fraud cases, and maintains a network of qui tam attorneys.',
        'ws_aorg_website_url'  => 'https://taf.org',
        'ws_aorg_intake_url'   => 'https://taf.org/contact/',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'advocacy',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 0,
        'has_attorneys'        => 0,
        'services'             => [ 'referral', 'advocacy' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'securities-commodities-fraud', 'healthcare-medicare-fraud', 'procurement-spending-fraud', 'tax-evasion-fraud' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'litigation' ],
    ],

    [
        'internal_id'          => 'woa-national',
        'title'                => 'Whistleblowers of America',
        'slug'                 => 'whistleblowers-of-america',
        'description'          => 'Provides peer support, advocacy, and guidance to whistleblowers, with a focus on retaliation response and trauma-informed support.',
        'post_content'         => 'Provides peer support, advocacy, and guidance to whistleblowers, with a focus on retaliation response and trauma-informed support.',
        'ws_aorg_website_url'  => 'https://www.whistleblowersofamerica.org',
        'ws_aorg_intake_url'   => 'https://www.whistleblowersofamerica.org/contact',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'advocacy',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'advocacy', 'financial' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'retaliation-protection', 'wrongful-termination', 'occupational-health-safety', 'healthcare-medicare-fraud' ],
        'case_stages'          => [ 'post-report', 'retaliation-active' ],
    ],

    [
        'internal_id'          => 'win-global',
        'title'                => 'Whistleblowing International Network',
        'slug'                 => 'whistleblowing-international-network',
        'description'          => 'Global network of civil society organizations supporting whistleblowing, transparency, and accountability, including member groups operating in the United States.',
        'post_content'         => 'Global network of civil society organizations supporting whistleblowing, transparency, and accountability, including member groups operating in the United States.',
        'ws_aorg_website_url'  => 'https://whistleblowingnetwork.org',
        'ws_aorg_intake_url'   => 'https://whistleblowingnetwork.org/Contact',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'advocacy',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 0,
        'has_attorneys'        => 0,
        'services'             => [ 'referral', 'advocacy' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'election-integrity', 'environmental-protection', 'cybersecurity-disclosure', 'consumer-data-protection' ],
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    // ── National worker / employment focus ────────────────────────────────

    [
        'internal_id'          => 'nelp-national',
        'title'                => 'National Employment Law Project',
        'slug'                 => 'national-employment-law-project',
        'description'          => 'Champions the rights of low-wage and unemployed workers through research and advocacy, including for workers who face retaliation for reporting violations.',
        'post_content'         => 'Champions the rights of low-wage and unemployed workers through research and advocacy, including for workers who face retaliation for reporting violations.',
        'ws_aorg_website_url'  => 'https://www.nelp.org',
        'ws_aorg_intake_url'   => 'https://www.nelp.org/contact-us/',
        'ws_aorg_phone'        => '(212) 285-3025',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'advocacy',
        'cost_model'           => 'free',
        'is_nationwide'        => 1,
        'accepts_anon'         => 0,
        'has_attorneys'        => 0,
        'services'             => [ 'advocacy' ],
        'sectors'              => [ 'private-sector', 'nonprofit-ngo' ],
        'disclosure_types'     => [ 'retaliation-protection', 'wage-hour-violations', 'occupational-health-safety' ],
        'case_stages'          => [ 'post-report', 'retaliation-active' ],
    ],

    // ── Federal oversight / intake channels ───────────────────────────────

    [
        'internal_id'          => 'gao-fraudnet',
        'title'                => 'Government Accountability Office — FraudNet',
        'slug'                 => 'gao-fraudnet',
        'description'          => 'Receives allegations of fraud, waste, abuse, and mismanagement of federal funds. Referrals are made to the appropriate Inspector General or law enforcement agency.',
        'post_content'         => 'Receives allegations of fraud, waste, abuse, and mismanagement of federal funds. Referrals are made to the appropriate Inspector General or law enforcement agency.',
        'ws_aorg_website_url'  => 'https://www.gao.gov/about/what-gao-does/fraudnet',
        'ws_aorg_intake_url'   => 'https://www.gao.gov/about/what-gao-does/fraudnet',
        'ws_aorg_phone'        => '1-800-424-5454',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'oversight-office',
        'cost_model'           => 'free',
        'is_nationwide'        => 0,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'hotline', 'referral' ],
        'sectors'              => [ 'federal-employee' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'procurement-spending-fraud', 'banking-aml-compliance', 'tax-evasion-fraud' ],
        'case_stages'          => [ 'pre-report' ],
    ],

    [
        'internal_id'          => 'ig-community',
        'title'                => 'Inspector General Community',
        'slug'                 => 'ig-community',
        'description'          => 'The Council of the Inspectors General on Integrity and Efficiency coordinates federal oversight activities and provides a directory of agency IGs who receive whistleblower disclosures.',
        'post_content'         => 'The Council of the Inspectors General on Integrity and Efficiency coordinates federal oversight activities and provides a directory of agency IGs who receive whistleblower disclosures.',
        'ws_aorg_website_url'  => 'https://www.ignet.gov',
        'ws_aorg_intake_url'   => 'https://www.ignet.gov/content/inspectors-general-directory',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'oversight-office',
        'cost_model'           => 'free',
        'is_nationwide'        => 0,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'hotline', 'referral' ],
        'sectors'              => [ 'federal-employee' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'procurement-spending-fraud', 'healthcare-medicare-fraud', 'environmental-protection' ],
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    [
        'internal_id'          => 'osc-federal',
        'title'                => 'U.S. Office of Special Counsel',
        'slug'                 => 'us-office-of-special-counsel',
        'description'          => 'Independent federal agency that protects federal employees from prohibited personnel practices and provides a secure channel for whistleblower disclosures.',
        'post_content'         => 'Independent federal agency that protects federal employees from prohibited personnel practices and provides a secure channel for whistleblower disclosures.',
        'ws_aorg_website_url'  => 'https://osc.gov',
        'ws_aorg_intake_url'   => 'https://osc.gov/Services/Pages/Reporting-Wrongdoing.aspx',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'oversight-office',
        'cost_model'           => 'free',
        'is_nationwide'        => 0,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'hotline', 'referral' ],
        'sectors'              => [ 'federal-employee' ],
        'disclosure_types'     => [ 'retaliation-protection', 'wrongful-termination', 'public-corruption-ethics', 'procurement-spending-fraud' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'retaliation-active' ],
    ],

    [
        'internal_id'          => 'opm-oig',
        'title'                => 'OPM Office of Inspector General — Whistleblower Rights & Protections',
        'slug'                 => 'opm-oig-whistleblower-rights',
        'description'          => 'Provides information on whistleblower rights and protections for federal employees and channels for reporting wrongdoing affecting OPM programs and operations.',
        'post_content'         => 'Provides information on whistleblower rights and protections for federal employees and channels for reporting wrongdoing affecting OPM programs and operations.',
        'ws_aorg_website_url'  => 'https://oig.opm.gov/report-oig/whistleblower-rights-protections',
        'ws_aorg_intake_url'   => 'https://oig.opm.gov/report-oig',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'oversight-office',
        'cost_model'           => 'free',
        'is_nationwide'        => 0,
        'accepts_anon'         => 1,
        'has_attorneys'        => 0,
        'services'             => [ 'hotline', 'referral' ],
        'sectors'              => [ 'federal-employee' ],
        'disclosure_types'     => [ 'public-corruption-ethics', 'procurement-spending-fraud', 'retaliation-protection' ],
        'case_stages'          => [ 'pre-report', 'post-report' ],
    ],

    // ── Bar / attorney referral programs ──────────────────────────────────

    [
        'internal_id'          => 'nwc-attorney-referral',
        'title'                => 'National Whistleblower Center — Attorney Referral Program',
        'slug'                 => 'national-whistleblower-center-attorney-referral',
        'description'          => 'Referral program connecting whistleblowers with experienced attorneys in False Claims Act, SEC, IRS, and other whistleblower law areas.',
        'post_content'         => 'Referral program connecting whistleblowers with experienced attorneys in False Claims Act, SEC, IRS, and other whistleblower law areas.',
        'ws_aorg_website_url'  => 'https://www.whistleblowers.org',
        'ws_aorg_intake_url'   => 'https://www.whistleblowers.org/find-a-whisteblower-attorney/',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'bar-program',
        'cost_model'           => 'paid',
        'is_nationwide'        => 1,
        'accepts_anon'         => 0,
        'has_attorneys'        => 0,
        'services'             => [ 'referral' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'securities-commodities-fraud', 'tax-evasion-fraud', 'public-corruption-ethics', 'procurement-spending-fraud', 'healthcare-medicare-fraud' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'litigation' ],
    ],

    [
        'internal_id'          => 'aba-find-legal-help',
        'title'                => 'American Bar Association — Find Legal Help',
        'slug'                 => 'american-bar-association-find-legal-help',
        'description'          => 'ABA information portal that directs the public to state and local lawyer referral services and bar-sponsored legal aid programs across the United States.',
        'post_content'         => 'ABA information portal that directs the public to state and local lawyer referral services and bar-sponsored legal aid programs across the United States.',
        'ws_aorg_website_url'  => 'https://www.americanbar.org/groups/legal_services/flh-home/',
        'ws_aorg_intake_url'   => 'https://www.americanbar.org/groups/legal_services/flh-home/',
        'ws_aorg_phone'        => '',
        'ws_aorg_email'        => '',
        'aorg_type'            => 'bar-program',
        'cost_model'           => 'paid',
        'is_nationwide'        => 1,
        'accepts_anon'         => 0,
        'has_attorneys'        => 0,
        'services'             => [ 'referral' ],
        'sectors'              => [ 'all-sectors' ],
        'disclosure_types'     => [ 'retaliation-protection', 'wrongful-termination', 'securities-commodities-fraud', 'occupational-health-safety', 'healthcare-medicare-fraud' ],
        'case_stages'          => [ 'pre-report', 'post-report', 'litigation' ],
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
        return; // Jurisdiction terms not yet seeded — bail.
    }
    $us_term_id = (int) $us_term->term_id;

    if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
        define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
    }

    foreach ( $_ws_assist_org_matrix as $org ) {

        $existing  = get_page_by_path( $org['slug'], OBJECT, 'ws-assist-org' );
        $post_data = [
            'post_title'   => $org['title'],
            'post_name'    => $org['slug'],
            'post_type'    => 'ws-assist-org',
            'post_status'  => 'publish',
            'post_content' => $org['post_content'] ?? '',
        ];

        if ( $existing ) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // ── ACF meta fields ──────────────────────────────────────────────────
        //
        // String/URL fields: skipped if empty (no point storing blank strings).
        // Boolean fields (0/1) and array fields always write — 0 is meaningful.

        $meta = [
            'ws_aorg_internal_id'        => $org['internal_id']         ?? '',
            'ws_aorg_description'        => $org['description']         ?? '',
            'ws_aorg_website_url'        => $org['ws_aorg_website_url'] ?? '',
            'ws_aorg_intake_url'         => $org['ws_aorg_intake_url']  ?? '',
            'ws_aorg_phone'              => $org['ws_aorg_phone']       ?? '',
            'ws_aorg_email'              => $org['ws_aorg_email']       ?? '',
            'ws_aorg_cost_model'         => $org['cost_model']          ?? '',
            'ws_aorg_serves_nationwide'  => $org['is_nationwide']       ?? 0,
            'ws_aorg_accepts_anonymous'  => $org['accepts_anon']        ?? 0,
            'ws_aorg_licensed_attorneys' => $org['has_attorneys']       ?? 0,
            'ws_aorg_services'           => $org['services']            ?? [],
        ];

        foreach ( $meta as $key => $value ) {
            if ( $value !== '' ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // ── Taxonomies ───────────────────────────────────────────────────────

        // Organization type (single slug).
        if ( ! empty( $org['aorg_type'] ) ) {
            wp_set_object_terms( $post_id, $org['aorg_type'], 'ws_aorg_type' );
        }

        // Disclosure types (array of slugs — must match ws_disclosure_type seeder).
        if ( ! empty( $org['disclosure_types'] ) ) {
            wp_set_object_terms( $post_id, $org['disclosure_types'], 'ws_disclosure_type' );
        }

        // Case stages (array of slugs).
        if ( ! empty( $org['case_stages'] ) ) {
            wp_set_object_terms( $post_id, $org['case_stages'], 'ws_case_stage' );
        }

        // Employment sectors (array of ws_employment_sector slugs).
        if ( ! empty( $org['sectors'] ) ) {
            wp_set_object_terms( $post_id, $org['sectors'], 'ws_employment_sector' );
        }

        // Language: English (all seeded national orgs operate in English).
        wp_set_object_terms( $post_id, 'english', 'ws_languages' );

        // Jurisdiction: US.
        wp_set_object_terms( $post_id, $us_term_id, WS_JURISDICTION_TERM_ID );

        // ── Seeder stamp ─────────────────────────────────────────────────────
        update_post_meta( $post_id, 'ws_matrix_source', 'assist-org-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_assist_org_matrix' ) !== '1.6.0' ) {
        ws_seed_assist_org_matrix();
        update_option( 'ws_seeded_assist_org_matrix', '1.6.0' );
    }
} );
