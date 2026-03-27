<?php
/**
 * matrix-jurisdictions.php — Canonical data and seeder for all 57 U.S. jurisdictions.
 *
 * @package WhistleblowerShield
 * @since   1.0.0
 * @version 3.10.0
 */

defined( 'ABSPATH' ) || exit;

$_ws_jx_matrix = [

    // ── Federal ───────────────────────────────────────────────────────────────

    'US' => [
        'title'                    => 'United States',
        'slug'                     => 'united-states',
        'ws_jurisdiction_class'    => 'federal',
        'ws_jx_code'               => 'US',
        'ws_jurisdiction_name'     => 'United States',
        'ws_jx_gov_portal_url'     => null,
        'ws_jx_gov_portal_label'   => null,
        'ws_jx_wb_authority_url'   => 'https://osc.gov',
        'ws_jx_wb_authority_label' => 'U.S. Office of Special Counsel',
        'ws_jx_legislature_url'    => 'https://www.congress.gov',
        'ws_jx_legislature_label'  => 'United States Congress',
        'ws_jx_executive_url'      => null,
        'ws_jx_executive_label'    => null,
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_the_United_States.svg',
    ],

    // ── District ──────────────────────────────────────────────────────────────

    'DC' => [
        'title'                    => 'District of Columbia',
        'slug'                     => 'district-of-columbia',
        'ws_jurisdiction_class'    => 'district',
        'ws_jx_code'               => 'DC',
        'ws_jurisdiction_name'     => 'District of Columbia',
        'ws_jx_gov_portal_url'     => 'https://dc.gov',
        'ws_jx_gov_portal_label'   => 'District of Columbia Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://oag.dc.gov',
        'ws_jx_wb_authority_label' => 'District of Columbia Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://dccouncil.gov',
        'ws_jx_legislature_label'  => 'Council of the District of Columbia',
        'ws_jx_executive_url'      => 'https://mayor.dc.gov',
        'ws_jx_executive_label'    => 'Office of the Mayor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_the_District_of_Columbia.svg',
    ],

    // ── 50 States ─────────────────────────────────────────────────────────────

    'AL' => [
        'title'                    => 'Alabama',
        'slug'                     => 'alabama',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'AL',
        'ws_jurisdiction_name'     => 'Alabama',
        'ws_jx_gov_portal_url'     => 'https://www.alabama.gov',
        'ws_jx_gov_portal_label'   => 'Alabama Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.alabamaag.gov',
        'ws_jx_wb_authority_label' => 'Alabama Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legislature.state.al.us',
        'ws_jx_legislature_label'  => 'Alabama Legislature',
        'ws_jx_executive_url'      => 'https://governor.alabama.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Alabama.svg',
    ],

    'AK' => [
        'title'                    => 'Alaska',
        'slug'                     => 'alaska',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'AK',
        'ws_jurisdiction_name'     => 'Alaska',
        'ws_jx_gov_portal_url'     => 'https://alaska.gov',
        'ws_jx_gov_portal_label'   => 'Alaska Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://law.alaska.gov',
        'ws_jx_wb_authority_label' => 'Alaska Department of Law',
        'ws_jx_legislature_url'    => 'https://akleg.gov',
        'ws_jx_legislature_label'  => 'Alaska State Legislature',
        'ws_jx_executive_url'      => 'https://gov.alaska.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Alaska.svg',
    ],

    'AZ' => [
        'title'                    => 'Arizona',
        'slug'                     => 'arizona',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'AZ',
        'ws_jurisdiction_name'     => 'Arizona',
        'ws_jx_gov_portal_url'     => 'https://az.gov',
        'ws_jx_gov_portal_label'   => 'Arizona Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.azag.gov',
        'ws_jx_wb_authority_label' => 'Arizona Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.azleg.gov',
        'ws_jx_legislature_label'  => 'Arizona State Legislature',
        'ws_jx_executive_url'      => 'https://azgovernor.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Arizona.svg',
    ],

    'AR' => [
        'title'                    => 'Arkansas',
        'slug'                     => 'arkansas',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'AR',
        'ws_jurisdiction_name'     => 'Arkansas',
        'ws_jx_gov_portal_url'     => 'https://portal.arkansas.gov',
        'ws_jx_gov_portal_label'   => 'Arkansas Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://arkansasag.gov',
        'ws_jx_wb_authority_label' => 'Arkansas Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.arkleg.state.ar.us',
        'ws_jx_legislature_label'  => 'Arkansas General Assembly',
        'ws_jx_executive_url'      => 'https://governor.arkansas.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Arkansas.svg',
    ],

    'CA' => [
        'title'                    => 'California',
        'slug'                     => 'california',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'CA',
        'ws_jurisdiction_name'     => 'California',
        'ws_jx_gov_portal_url'     => 'https://www.ca.gov',
        'ws_jx_gov_portal_label'   => 'California Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://oag.ca.gov',
        'ws_jx_wb_authority_label' => 'California Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legislature.ca.gov',
        'ws_jx_legislature_label'  => 'California State Legislature',
        'ws_jx_executive_url'      => 'https://www.gov.ca.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_California.svg',
    ],

    'CO' => [
        'title'                    => 'Colorado',
        'slug'                     => 'colorado',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'CO',
        'ws_jurisdiction_name'     => 'Colorado',
        'ws_jx_gov_portal_url'     => 'https://www.colorado.gov',
        'ws_jx_gov_portal_label'   => 'Colorado Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://coag.gov',
        'ws_jx_wb_authority_label' => 'Colorado Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://leg.colorado.gov',
        'ws_jx_legislature_label'  => 'Colorado General Assembly',
        'ws_jx_executive_url'      => 'https://www.colorado.gov/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Colorado.svg',
    ],

    'CT' => [
        'title'                    => 'Connecticut',
        'slug'                     => 'connecticut',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'CT',
        'ws_jurisdiction_name'     => 'Connecticut',
        'ws_jx_gov_portal_url'     => 'https://portal.ct.gov',
        'ws_jx_gov_portal_label'   => 'Connecticut Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://portal.ct.gov/AG',
        'ws_jx_wb_authority_label' => 'Connecticut Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.cga.ct.gov',
        'ws_jx_legislature_label'  => 'Connecticut General Assembly',
        'ws_jx_executive_url'      => 'https://portal.ct.gov/Office-of-the-Governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Connecticut.svg',
    ],

    'DE' => [
        'title'                    => 'Delaware',
        'slug'                     => 'delaware',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'DE',
        'ws_jurisdiction_name'     => 'Delaware',
        'ws_jx_gov_portal_url'     => 'https://delaware.gov',
        'ws_jx_gov_portal_label'   => 'Delaware Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://attorneygeneral.delaware.gov',
        'ws_jx_wb_authority_label' => 'Delaware Department of Justice',
        'ws_jx_legislature_url'    => 'https://legis.delaware.gov',
        'ws_jx_legislature_label'  => 'Delaware General Assembly',
        'ws_jx_executive_url'      => 'https://governor.delaware.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Delaware.svg',
    ],

    'FL' => [
        'title'                    => 'Florida',
        'slug'                     => 'florida',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'FL',
        'ws_jurisdiction_name'     => 'Florida',
        'ws_jx_gov_portal_url'     => 'https://www.myflorida.com',
        'ws_jx_gov_portal_label'   => 'Florida Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.myfloridalegal.com',
        'ws_jx_wb_authority_label' => 'Florida Office of the Chief Inspector General',
        'ws_jx_legislature_url'    => 'https://www.leg.state.fl.us',
        'ws_jx_legislature_label'  => 'Florida Legislature',
        'ws_jx_executive_url'      => 'https://www.flgov.com',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Florida.svg',
    ],

    'GA' => [
        'title'                    => 'Georgia',
        'slug'                     => 'georgia',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'GA',
        'ws_jurisdiction_name'     => 'Georgia',
        'ws_jx_gov_portal_url'     => 'https://www.georgia.gov',
        'ws_jx_gov_portal_label'   => 'Georgia Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://law.georgia.gov',
        'ws_jx_wb_authority_label' => 'Georgia Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legis.ga.gov',
        'ws_jx_legislature_label'  => 'Georgia General Assembly',
        'ws_jx_executive_url'      => 'https://gov.georgia.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Georgia_(U.S._state).svg',
    ],

    'HI' => [
        'title'                    => 'Hawaii',
        'slug'                     => 'hawaii',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'HI',
        'ws_jurisdiction_name'     => 'Hawaii',
        'ws_jx_gov_portal_url'     => 'https://portal.ehawaii.gov',
        'ws_jx_gov_portal_label'   => 'Hawaii Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ag.hawaii.gov',
        'ws_jx_wb_authority_label' => 'Hawaii Department of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.capitol.hawaii.gov',
        'ws_jx_legislature_label'  => 'Hawaii State Legislature',
        'ws_jx_executive_url'      => 'https://governor.hawaii.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Hawaii.svg',
    ],

    'ID' => [
        'title'                    => 'Idaho',
        'slug'                     => 'idaho',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'ID',
        'ws_jurisdiction_name'     => 'Idaho',
        'ws_jx_gov_portal_url'     => 'https://www.idaho.gov',
        'ws_jx_gov_portal_label'   => 'Idaho Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ag.idaho.gov',
        'ws_jx_wb_authority_label' => 'Idaho Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://legislature.idaho.gov',
        'ws_jx_legislature_label'  => 'Idaho State Legislature',
        'ws_jx_executive_url'      => 'https://gov.idaho.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Idaho.svg',
    ],

    'IL' => [
        'title'                    => 'Illinois',
        'slug'                     => 'illinois',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'IL',
        'ws_jurisdiction_name'     => 'Illinois',
        'ws_jx_gov_portal_url'     => 'https://www.illinois.gov',
        'ws_jx_gov_portal_label'   => 'Illinois Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.illinoisattorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'Office of the Executive Inspector General',
        'ws_jx_legislature_url'    => 'https://www.ilga.gov',
        'ws_jx_legislature_label'  => 'Illinois General Assembly',
        'ws_jx_executive_url'      => 'https://www.illinois.gov/government/executive-branch/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Illinois.svg',
    ],

    'IN' => [
        'title'                    => 'Indiana',
        'slug'                     => 'indiana',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'IN',
        'ws_jurisdiction_name'     => 'Indiana',
        'ws_jx_gov_portal_url'     => 'https://www.in.gov',
        'ws_jx_gov_portal_label'   => 'Indiana Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.in.gov/attorneygeneral',
        'ws_jx_wb_authority_label' => 'Indiana Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://iga.in.gov',
        'ws_jx_legislature_label'  => 'Indiana General Assembly',
        'ws_jx_executive_url'      => 'https://www.in.gov/gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Indiana.svg',
    ],

    'IA' => [
        'title'                    => 'Iowa',
        'slug'                     => 'iowa',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'IA',
        'ws_jurisdiction_name'     => 'Iowa',
        'ws_jx_gov_portal_url'     => 'https://www.iowa.gov',
        'ws_jx_gov_portal_label'   => 'Iowa Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.iowaattorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'Iowa Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legis.iowa.gov',
        'ws_jx_legislature_label'  => 'Iowa General Assembly',
        'ws_jx_executive_url'      => 'https://governor.iowa.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Iowa.svg',
    ],

    'KS' => [
        'title'                    => 'Kansas',
        'slug'                     => 'kansas',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'KS',
        'ws_jurisdiction_name'     => 'Kansas',
        'ws_jx_gov_portal_url'     => 'https://www.kansas.gov',
        'ws_jx_gov_portal_label'   => 'Kansas Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ag.ks.gov',
        'ws_jx_wb_authority_label' => 'Kansas Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.kslegislature.org',
        'ws_jx_legislature_label'  => 'Kansas State Legislature',
        'ws_jx_executive_url'      => 'https://governor.kansas.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Kansas.svg',
    ],

    'KY' => [
        'title'                    => 'Kentucky',
        'slug'                     => 'kentucky',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'KY',
        'ws_jurisdiction_name'     => 'Kentucky',
        'ws_jx_gov_portal_url'     => 'https://www.kentucky.gov',
        'ws_jx_gov_portal_label'   => 'Kentucky Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ag.ky.gov',
        'ws_jx_wb_authority_label' => 'Kentucky Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://legislature.ky.gov',
        'ws_jx_legislature_label'  => 'Kentucky General Assembly',
        'ws_jx_executive_url'      => 'https://governor.ky.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Kentucky.svg',
    ],

    'LA' => [
        'title'                    => 'Louisiana',
        'slug'                     => 'louisiana',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'LA',
        'ws_jurisdiction_name'     => 'Louisiana',
        'ws_jx_gov_portal_url'     => 'https://www.louisiana.gov',
        'ws_jx_gov_portal_label'   => 'Louisiana Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ag.state.la.us',
        'ws_jx_wb_authority_label' => 'Louisiana Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.legis.la.gov',
        'ws_jx_legislature_label'  => 'Louisiana State Legislature',
        'ws_jx_executive_url'      => 'https://gov.louisiana.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Louisiana.svg',
    ],

    'ME' => [
        'title'                    => 'Maine',
        'slug'                     => 'maine',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'ME',
        'ws_jurisdiction_name'     => 'Maine',
        'ws_jx_gov_portal_url'     => 'https://www.maine.gov',
        'ws_jx_gov_portal_label'   => 'Maine Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.maine.gov/ag',
        'ws_jx_wb_authority_label' => 'Maine Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://legislature.maine.gov',
        'ws_jx_legislature_label'  => 'Maine State Legislature',
        'ws_jx_executive_url'      => 'https://www.maine.gov/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Maine.svg',
    ],

    'MD' => [
        'title'                    => 'Maryland',
        'slug'                     => 'maryland',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MD',
        'ws_jurisdiction_name'     => 'Maryland',
        'ws_jx_gov_portal_url'     => 'https://www.maryland.gov',
        'ws_jx_gov_portal_label'   => 'Maryland Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.marylandattorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'Maryland Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://mgaleg.maryland.gov',
        'ws_jx_legislature_label'  => 'Maryland General Assembly',
        'ws_jx_executive_url'      => 'https://governor.maryland.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Maryland.svg',
    ],

    'MA' => [
        'title'                    => 'Massachusetts',
        'slug'                     => 'massachusetts',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MA',
        'ws_jurisdiction_name'     => 'Massachusetts',
        'ws_jx_gov_portal_url'     => 'https://www.mass.gov',
        'ws_jx_gov_portal_label'   => 'Massachusetts Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.mass.gov/orgs/office-of-attorney-general-andrea-joy-campbell',
        'ws_jx_wb_authority_label' => 'Massachusetts Office of the Inspector General',
        'ws_jx_legislature_url'    => 'https://malegislature.gov',
        'ws_jx_legislature_label'  => 'Massachusetts General Court',
        'ws_jx_executive_url'      => 'https://www.mass.gov/orgs/office-of-the-governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Massachusetts.svg',
    ],

    'MI' => [
        'title'                    => 'Michigan',
        'slug'                     => 'michigan',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MI',
        'ws_jurisdiction_name'     => 'Michigan',
        'ws_jx_gov_portal_url'     => 'https://www.michigan.gov',
        'ws_jx_gov_portal_label'   => 'Michigan Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.michigan.gov/ag',
        'ws_jx_wb_authority_label' => 'Michigan Department of Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legislature.mi.gov',
        'ws_jx_legislature_label'  => 'Michigan Legislature',
        'ws_jx_executive_url'      => 'https://www.michigan.gov/whitmer',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Michigan.svg',
    ],

    'MN' => [
        'title'                    => 'Minnesota',
        'slug'                     => 'minnesota',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MN',
        'ws_jurisdiction_name'     => 'Minnesota',
        'ws_jx_gov_portal_url'     => 'https://mn.gov',
        'ws_jx_gov_portal_label'   => 'Minnesota Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ag.state.mn.us',
        'ws_jx_wb_authority_label' => 'Minnesota Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.leg.mn.gov',
        'ws_jx_legislature_label'  => 'Minnesota State Legislature',
        'ws_jx_executive_url'      => 'https://mn.gov/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Minnesota.svg',
    ],

    'MS' => [
        'title'                    => 'Mississippi',
        'slug'                     => 'mississippi',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MS',
        'ws_jurisdiction_name'     => 'Mississippi',
        'ws_jx_gov_portal_url'     => 'https://www.ms.gov',
        'ws_jx_gov_portal_label'   => 'Mississippi Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ago.state.ms.us',
        'ws_jx_wb_authority_label' => 'Mississippi Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.legislature.ms.gov',
        'ws_jx_legislature_label'  => 'Mississippi Legislature',
        'ws_jx_executive_url'      => 'https://www.governorreeves.ms.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Mississippi.svg',
    ],

    'MO' => [
        'title'                    => 'Missouri',
        'slug'                     => 'missouri',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MO',
        'ws_jurisdiction_name'     => 'Missouri',
        'ws_jx_gov_portal_url'     => 'https://www.mo.gov',
        'ws_jx_gov_portal_label'   => 'Missouri Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ago.mo.gov',
        'ws_jx_wb_authority_label' => 'Missouri Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.house.mo.gov',
        'ws_jx_legislature_label'  => 'Missouri General Assembly',
        'ws_jx_executive_url'      => 'https://governor.mo.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Missouri.svg',
    ],

    'MT' => [
        'title'                    => 'Montana',
        'slug'                     => 'montana',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'MT',
        'ws_jurisdiction_name'     => 'Montana',
        'ws_jx_gov_portal_url'     => 'https://mt.gov',
        'ws_jx_gov_portal_label'   => 'Montana Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://dojmt.gov',
        'ws_jx_wb_authority_label' => 'Montana Department of Justice',
        'ws_jx_legislature_url'    => 'https://leg.mt.gov',
        'ws_jx_legislature_label'  => 'Montana State Legislature',
        'ws_jx_executive_url'      => 'https://governor.mt.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Montana.svg',
    ],

    'NE' => [
        'title'                    => 'Nebraska',
        'slug'                     => 'nebraska',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NE',
        'ws_jurisdiction_name'     => 'Nebraska',
        'ws_jx_gov_portal_url'     => 'https://www.nebraska.gov',
        'ws_jx_gov_portal_label'   => 'Nebraska Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ago.nebraska.gov',
        'ws_jx_wb_authority_label' => 'Nebraska Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://nebraskalegislature.gov',
        'ws_jx_legislature_label'  => 'Nebraska Legislature',
        'ws_jx_executive_url'      => 'https://governor.nebraska.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Nebraska.svg',
    ],

    'NV' => [
        'title'                    => 'Nevada',
        'slug'                     => 'nevada',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NV',
        'ws_jurisdiction_name'     => 'Nevada',
        'ws_jx_gov_portal_url'     => 'https://nv.gov',
        'ws_jx_gov_portal_label'   => 'Nevada Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ag.nv.gov',
        'ws_jx_wb_authority_label' => 'Nevada Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.leg.state.nv.us',
        'ws_jx_legislature_label'  => 'Nevada Legislature',
        'ws_jx_executive_url'      => 'https://gov.nv.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Nevada.svg',
    ],

    'NH' => [
        'title'                    => 'New Hampshire',
        'slug'                     => 'new-hampshire',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NH',
        'ws_jurisdiction_name'     => 'New Hampshire',
        'ws_jx_gov_portal_url'     => 'https://www.nh.gov',
        'ws_jx_gov_portal_label'   => 'New Hampshire Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.doj.nh.gov',
        'ws_jx_wb_authority_label' => 'New Hampshire Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.gencourt.state.nh.us',
        'ws_jx_legislature_label'  => 'New Hampshire General Court',
        'ws_jx_executive_url'      => 'https://www.governor.nh.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_New_Hampshire.svg',
    ],

    'NJ' => [
        'title'                    => 'New Jersey',
        'slug'                     => 'new-jersey',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NJ',
        'ws_jurisdiction_name'     => 'New Jersey',
        'ws_jx_gov_portal_url'     => 'https://www.nj.gov',
        'ws_jx_gov_portal_label'   => 'New Jersey Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.njoag.gov',
        'ws_jx_wb_authority_label' => 'New Jersey State Ethics Commission',
        'ws_jx_legislature_url'    => 'https://www.njleg.state.nj.us',
        'ws_jx_legislature_label'  => 'New Jersey Legislature',
        'ws_jx_executive_url'      => 'https://nj.gov/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_New_Jersey.svg',
    ],

    'NM' => [
        'title'                    => 'New Mexico',
        'slug'                     => 'new-mexico',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NM',
        'ws_jurisdiction_name'     => 'New Mexico',
        'ws_jx_gov_portal_url'     => 'https://www.nm.gov',
        'ws_jx_gov_portal_label'   => 'New Mexico Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.nmag.gov',
        'ws_jx_wb_authority_label' => 'New Mexico Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.nmlegis.gov',
        'ws_jx_legislature_label'  => 'New Mexico Legislature',
        'ws_jx_executive_url'      => 'https://www.governor.state.nm.us',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_New_Mexico.svg',
    ],

    'NY' => [
        'title'                    => 'New York',
        'slug'                     => 'new-york',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NY',
        'ws_jurisdiction_name'     => 'New York',
        'ws_jx_gov_portal_url'     => 'https://www.ny.gov',
        'ws_jx_gov_portal_label'   => 'New York Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ag.ny.gov',
        'ws_jx_wb_authority_label' => 'Office of the State Inspector General',
        'ws_jx_legislature_url'    => 'https://www.nysenate.gov',
        'ws_jx_legislature_label'  => 'New York State Legislature',
        'ws_jx_executive_url'      => 'https://www.governor.ny.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_New_York.svg',
    ],

    'NC' => [
        'title'                    => 'North Carolina',
        'slug'                     => 'north-carolina',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'NC',
        'ws_jurisdiction_name'     => 'North Carolina',
        'ws_jx_gov_portal_url'     => 'https://www.nc.gov',
        'ws_jx_gov_portal_label'   => 'North Carolina Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ncdoj.gov',
        'ws_jx_wb_authority_label' => 'North Carolina Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.ncleg.gov',
        'ws_jx_legislature_label'  => 'North Carolina General Assembly',
        'ws_jx_executive_url'      => 'https://governor.nc.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_North_Carolina.svg',
    ],

    'ND' => [
        'title'                    => 'North Dakota',
        'slug'                     => 'north-dakota',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'ND',
        'ws_jurisdiction_name'     => 'North Dakota',
        'ws_jx_gov_portal_url'     => 'https://www.nd.gov',
        'ws_jx_gov_portal_label'   => 'North Dakota Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://attorneygeneral.nd.gov',
        'ws_jx_wb_authority_label' => 'North Dakota Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://ndlegis.gov',
        'ws_jx_legislature_label'  => 'North Dakota Legislative Assembly',
        'ws_jx_executive_url'      => 'https://www.governor.nd.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_North_Dakota.svg',
    ],

    'OH' => [
        'title'                    => 'Ohio',
        'slug'                     => 'ohio',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'OH',
        'ws_jurisdiction_name'     => 'Ohio',
        'ws_jx_gov_portal_url'     => 'https://ohio.gov',
        'ws_jx_gov_portal_label'   => 'Ohio Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.ohioattorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'Ohio Inspector General',
        'ws_jx_legislature_url'    => 'https://www.legislature.ohio.gov',
        'ws_jx_legislature_label'  => 'Ohio General Assembly',
        'ws_jx_executive_url'      => 'https://governor.ohio.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Ohio.svg',
    ],

    'OK' => [
        'title'                    => 'Oklahoma',
        'slug'                     => 'oklahoma',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'OK',
        'ws_jurisdiction_name'     => 'Oklahoma',
        'ws_jx_gov_portal_url'     => 'https://www.ok.gov',
        'ws_jx_gov_portal_label'   => 'Oklahoma Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.oag.ok.gov',
        'ws_jx_wb_authority_label' => 'Oklahoma Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.oklegislature.gov',
        'ws_jx_legislature_label'  => 'Oklahoma Legislature',
        'ws_jx_executive_url'      => 'https://www.gov.ok.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Oklahoma.svg',
    ],

    'OR' => [
        'title'                    => 'Oregon',
        'slug'                     => 'oregon',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'OR',
        'ws_jurisdiction_name'     => 'Oregon',
        'ws_jx_gov_portal_url'     => 'https://www.oregon.gov',
        'ws_jx_gov_portal_label'   => 'Oregon Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.doj.state.or.us',
        'ws_jx_wb_authority_label' => 'Oregon Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.oregonlegislature.gov',
        'ws_jx_legislature_label'  => 'Oregon Legislative Assembly',
        'ws_jx_executive_url'      => 'https://www.oregon.gov/gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Oregon.svg',
    ],

    'PA' => [
        'title'                    => 'Pennsylvania',
        'slug'                     => 'pennsylvania',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'PA',
        'ws_jurisdiction_name'     => 'Pennsylvania',
        'ws_jx_gov_portal_url'     => 'https://www.pa.gov',
        'ws_jx_gov_portal_label'   => 'Pennsylvania Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.attorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'Office of State Inspector General',
        'ws_jx_legislature_url'    => 'https://www.legis.state.pa.us',
        'ws_jx_legislature_label'  => 'Pennsylvania General Assembly',
        'ws_jx_executive_url'      => 'https://www.governor.pa.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Pennsylvania.svg',
    ],

    'RI' => [
        'title'                    => 'Rhode Island',
        'slug'                     => 'rhode-island',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'RI',
        'ws_jurisdiction_name'     => 'Rhode Island',
        'ws_jx_gov_portal_url'     => 'https://www.ri.gov',
        'ws_jx_gov_portal_label'   => 'Rhode Island Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://riag.ri.gov',
        'ws_jx_wb_authority_label' => 'Rhode Island Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.rilegislature.gov',
        'ws_jx_legislature_label'  => 'Rhode Island General Assembly',
        'ws_jx_executive_url'      => 'https://governor.ri.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Rhode_Island.svg',
    ],

    'SC' => [
        'title'                    => 'South Carolina',
        'slug'                     => 'south-carolina',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'SC',
        'ws_jurisdiction_name'     => 'South Carolina',
        'ws_jx_gov_portal_url'     => 'https://sc.gov',
        'ws_jx_gov_portal_label'   => 'South Carolina Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.scag.gov',
        'ws_jx_wb_authority_label' => 'South Carolina Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.scstatehouse.gov',
        'ws_jx_legislature_label'  => 'South Carolina General Assembly',
        'ws_jx_executive_url'      => 'https://governor.sc.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_South_Carolina.svg',
    ],

    'SD' => [
        'title'                    => 'South Dakota',
        'slug'                     => 'south-dakota',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'SD',
        'ws_jurisdiction_name'     => 'South Dakota',
        'ws_jx_gov_portal_url'     => 'https://sd.gov',
        'ws_jx_gov_portal_label'   => 'South Dakota Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://atg.sd.gov',
        'ws_jx_wb_authority_label' => 'South Dakota Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://sdlegislature.gov',
        'ws_jx_legislature_label'  => 'South Dakota State Legislature',
        'ws_jx_executive_url'      => 'https://governor.sd.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_South_Dakota.svg',
    ],

    'TN' => [
        'title'                    => 'Tennessee',
        'slug'                     => 'tennessee',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'TN',
        'ws_jurisdiction_name'     => 'Tennessee',
        'ws_jx_gov_portal_url'     => 'https://www.tn.gov',
        'ws_jx_gov_portal_label'   => 'Tennessee Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.tn.gov/attorneygeneral',
        'ws_jx_wb_authority_label' => 'Tennessee Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.capitol.tn.gov',
        'ws_jx_legislature_label'  => 'Tennessee General Assembly',
        'ws_jx_executive_url'      => 'https://www.tn.gov/governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Tennessee.svg',
    ],

    'TX' => [
        'title'                    => 'Texas',
        'slug'                     => 'texas',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'TX',
        'ws_jurisdiction_name'     => 'Texas',
        'ws_jx_gov_portal_url'     => 'https://www.texas.gov',
        'ws_jx_gov_portal_label'   => 'Texas Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.texasattorneygeneral.gov',
        'ws_jx_wb_authority_label' => 'State Auditor\'s Office',
        'ws_jx_legislature_url'    => 'https://capitol.texas.gov',
        'ws_jx_legislature_label'  => 'Texas Legislature',
        'ws_jx_executive_url'      => 'https://gov.texas.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Texas.svg',
    ],

    'UT' => [
        'title'                    => 'Utah',
        'slug'                     => 'utah',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'UT',
        'ws_jurisdiction_name'     => 'Utah',
        'ws_jx_gov_portal_url'     => 'https://www.utah.gov',
        'ws_jx_gov_portal_label'   => 'Utah Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://attorneygeneral.utah.gov',
        'ws_jx_wb_authority_label' => 'Utah Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://le.utah.gov',
        'ws_jx_legislature_label'  => 'Utah State Legislature',
        'ws_jx_executive_url'      => 'https://governor.utah.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Utah.svg',
    ],

    'VT' => [
        'title'                    => 'Vermont',
        'slug'                     => 'vermont',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'VT',
        'ws_jurisdiction_name'     => 'Vermont',
        'ws_jx_gov_portal_url'     => 'https://www.vermont.gov',
        'ws_jx_gov_portal_label'   => 'Vermont Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ago.vermont.gov',
        'ws_jx_wb_authority_label' => 'Vermont Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://legislature.vermont.gov',
        'ws_jx_legislature_label'  => 'Vermont General Assembly',
        'ws_jx_executive_url'      => 'https://governor.vermont.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Vermont.svg',
    ],

    'VA' => [
        'title'                    => 'Virginia',
        'slug'                     => 'virginia',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'VA',
        'ws_jurisdiction_name'     => 'Virginia',
        'ws_jx_gov_portal_url'     => 'https://www.virginia.gov',
        'ws_jx_gov_portal_label'   => 'Virginia Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.oag.state.va.us',
        'ws_jx_wb_authority_label' => 'Office of the State Inspector General',
        'ws_jx_legislature_url'    => 'https://virginiageneralassembly.gov',
        'ws_jx_legislature_label'  => 'Virginia General Assembly',
        'ws_jx_executive_url'      => 'https://www.governor.virginia.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Virginia.svg',
    ],

    'WA' => [
        'title'                    => 'Washington',
        'slug'                     => 'washington',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'WA',
        'ws_jurisdiction_name'     => 'Washington',
        'ws_jx_gov_portal_url'     => 'https://wa.gov',
        'ws_jx_gov_portal_label'   => 'Washington Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.atg.wa.gov',
        'ws_jx_wb_authority_label' => 'Washington Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://leg.wa.gov',
        'ws_jx_legislature_label'  => 'Washington State Legislature',
        'ws_jx_executive_url'      => 'https://governor.wa.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Washington.svg',
    ],

    'WV' => [
        'title'                    => 'West Virginia',
        'slug'                     => 'west-virginia',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'WV',
        'ws_jurisdiction_name'     => 'West Virginia',
        'ws_jx_gov_portal_url'     => 'https://www.wv.gov',
        'ws_jx_gov_portal_label'   => 'West Virginia Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ago.wv.gov',
        'ws_jx_wb_authority_label' => 'West Virginia Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.wvlegislature.gov',
        'ws_jx_legislature_label'  => 'West Virginia Legislature',
        'ws_jx_executive_url'      => 'https://governor.wv.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_West_Virginia.svg',
    ],

    'WI' => [
        'title'                    => 'Wisconsin',
        'slug'                     => 'wisconsin',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'WI',
        'ws_jurisdiction_name'     => 'Wisconsin',
        'ws_jx_gov_portal_url'     => 'https://www.wisconsin.gov',
        'ws_jx_gov_portal_label'   => 'Wisconsin Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.doj.state.wi.us',
        'ws_jx_wb_authority_label' => 'Wisconsin Department of Justice',
        'ws_jx_legislature_url'    => 'https://legis.wisconsin.gov',
        'ws_jx_legislature_label'  => 'Wisconsin Legislature',
        'ws_jx_executive_url'      => 'https://evers.wi.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Wisconsin.svg',
    ],

    'WY' => [
        'title'                    => 'Wyoming',
        'slug'                     => 'wyoming',
        'ws_jurisdiction_class'    => 'state',
        'ws_jx_code'               => 'WY',
        'ws_jurisdiction_name'     => 'Wyoming',
        'ws_jx_gov_portal_url'     => 'https://www.wyo.gov',
        'ws_jx_gov_portal_label'   => 'Wyoming Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://ag.wyo.gov',
        'ws_jx_wb_authority_label' => 'Wyoming Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://wyoleg.gov',
        'ws_jx_legislature_label'  => 'Wyoming State Legislature',
        'ws_jx_executive_url'      => 'https://governor.wyo.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Wyoming.svg',
    ],

    // ── Territories ───────────────────────────────────────────────────────────

    'AS' => [
        'title'                    => 'American Samoa',
        'slug'                     => 'american-samoa',
        'ws_jurisdiction_class'    => 'territory',
        'ws_jx_code'               => 'AS',
        'ws_jurisdiction_name'     => 'American Samoa',
        'ws_jx_gov_portal_url'     => 'https://www.americansamoa.gov',
        'ws_jx_gov_portal_label'   => 'American Samoa Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.asag.as.gov',
        'ws_jx_wb_authority_label' => 'American Samoa Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.asbar.org/legislature',
        'ws_jx_legislature_label'  => 'Fono',
        'ws_jx_executive_url'      => 'https://www.americansamoa.gov/office-of-the-governor',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_American_Samoa.svg',
    ],

    'GU' => [
        'title'                    => 'Guam',
        'slug'                     => 'guam',
        'ws_jurisdiction_class'    => 'territory',
        'ws_jx_code'               => 'GU',
        'ws_jurisdiction_name'     => 'Guam',
        'ws_jx_gov_portal_url'     => 'https://www.guam.gov',
        'ws_jx_gov_portal_label'   => 'Guam Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://oagguam.org',
        'ws_jx_wb_authority_label' => 'Guam Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.guamlegislature.org',
        'ws_jx_legislature_label'  => 'Liheslaturan Guåhan',
        'ws_jx_executive_url'      => 'https://governor.guam.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Guam.svg',
    ],

    'MP' => [
        'title'                    => 'Northern Mariana Islands',
        'slug'                     => 'northern-mariana-islands',
        'ws_jurisdiction_class'    => 'territory',
        'ws_jx_code'               => 'MP',
        'ws_jurisdiction_name'     => 'Northern Mariana Islands',
        'ws_jx_gov_portal_url'     => 'https://www.gov.mp',
        'ws_jx_gov_portal_label'   => 'Northern Mariana Islands Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.cnmioag.gov.mp',
        'ws_jx_wb_authority_label' => 'NMI Office of the Attorney General',
        'ws_jx_legislature_url'    => 'https://www.cnmileg.net',
        'ws_jx_legislature_label'  => 'Northern Mariana Islands Commonwealth Legislature',
        'ws_jx_executive_url'      => 'https://www.governor.gov.mp',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_the_Northern_Mariana_Islands.svg',
    ],

    'PR' => [
        'title'                    => 'Puerto Rico',
        'slug'                     => 'puerto-rico',
        'ws_jurisdiction_class'    => 'territory',
        'ws_jx_code'               => 'PR',
        'ws_jurisdiction_name'     => 'Puerto Rico',
        'ws_jx_gov_portal_url'     => 'https://www.pr.gov',
        'ws_jx_gov_portal_label'   => 'Puerto Rico Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://www.justicia.pr.gov',
        'ws_jx_wb_authority_label' => 'Puerto Rico Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.oslpr.org',
        'ws_jx_legislature_label'  => 'Legislative Assembly of Puerto Rico',
        'ws_jx_executive_url'      => 'https://www.fortaleza.pr.gov',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_Puerto_Rico.svg',
    ],

    'VI' => [
        'title'                    => 'U.S. Virgin Islands',
        'slug'                     => 'virgin-islands',
        'ws_jurisdiction_class'    => 'territory',
        'ws_jx_code'               => 'VI',
        'ws_jurisdiction_name'     => 'U.S. Virgin Islands',
        'ws_jx_gov_portal_url'     => 'https://www.vi.gov',
        'ws_jx_gov_portal_label'   => 'U.S. Virgin Islands Official Government Portal',
        'ws_jx_wb_authority_url'   => 'https://doj.vi.gov',
        'ws_jx_wb_authority_label' => 'U.S. Virgin Islands Department of Justice',
        'ws_jx_legislature_url'    => 'https://www.legvi.org',
        'ws_jx_legislature_label'  => 'Legislature of the Virgin Islands',
        'ws_jx_executive_url'      => 'https://www.vi.gov/executive-branch',
        'ws_jx_executive_label'    => 'Office of the Governor',
        'ws_jx_flag_source_url'    => 'https://commons.wikimedia.org/wiki/File:Flag_of_the_United_States_Virgin_Islands.svg',
    ],

]; // end $_ws_jx_matrix


// ════════════════════════════════════════════════════════════════════════════
// Seeder: ws_seed_jurisdiction_matrix
//
// Creates or updates all 57 jurisdiction CPT posts from $_ws_jx_matrix.
//
// Seeder order:
//   1. Seed ws_jurisdiction taxonomy terms (slugs = lowercase USPS codes).
//   2. Write ws_us_term_id option for the US term (used by query layer).
//   3. Create/update jurisdiction CPT posts.
//   4. Assign taxonomy term to each post via wp_set_object_terms().
//   5. Write ws_jx_term_id post meta on each post.
//   6. Write ws_matrix_source = 'jurisdiction-matrix' on each post.
//
// Gate: ws_seeded_jurisdiction_matrix / 1.0.0 (Unified Option-Gate Method).
// ════════════════════════════════════════════════════════════════════════════

error_log( 'load-time matrix type=' . gettype( $_ws_jx_matrix ) );

function ws_seed_jurisdiction_matrix() {
	global $_ws_jx_matrix;

    error_log( 'seed: isset=' . ( isset( $_ws_jx_matrix ) ? 'yes' : 'no' ) );
	error_log( 'seed: type=' . gettype( $_ws_jx_matrix ) );
    // ── Step 1: Seed taxonomy terms ───────────────────────────────────────

    $term_map = []; // slug → term_id

    foreach ( $_ws_jx_matrix as $code => $jx ) {

        $slug = strtolower( $code );
        $name = $jx['title'];

        $existing = term_exists( $slug, WS_JURISDICTION_TAXONOMY );

        if ( $existing ) {
            $term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
        } else {
            $result = wp_insert_term( $name, WS_JURISDICTION_TAXONOMY, [ 'slug' => $slug ] );
            if ( is_wp_error( $result ) ) {
                continue;
            }
            $term_id = (int) $result['term_id'];
        }

        $term_map[ $slug ] = $term_id;

        // Write the US term ID option used by ws_get_us_term_id() in the query layer.
        // Direct get_post_meta() call is intentional here. ws_us_term_id is a site
        // option storing a taxonomy term ID — not jurisdiction content — and is not
        // routed through the query layer.
        if ( $slug === 'us' ) {
            update_option( 'ws_us_term_id', $term_id );
        }
    }

    // ── Step 2: Create/update jurisdiction posts ──────────────────────────

    foreach ( $_ws_jx_matrix as $code => $jx ) {

        $slug    = strtolower( $code );
        $term_id = $term_map[ $slug ] ?? 0;

        // Find existing post by slug.
        $existing_post = get_page_by_path( $jx['slug'], OBJECT, 'jurisdiction' );
		
		if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
			define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
		}
		

        if ( $existing_post ) {
            $post_id = $existing_post->ID;
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => $jx['title'],
                'post_name'  => $jx['slug'],
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'  => $jx['title'],
                'post_name'   => $jx['slug'],
                'post_type'   => 'jurisdiction',
                'post_status' => 'publish',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // Write ACF / meta fields from the matrix entry.
        $meta_fields = [
            'ws_jurisdiction_class'    => $jx['ws_jurisdiction_class']    ?? '',
            'ws_jurisdiction_name'     => $jx['ws_jurisdiction_name']     ?? $jx['title'],
            'ws_jx_gov_portal_url'     => $jx['ws_jx_gov_portal_url']     ?? '',
            'ws_jx_gov_portal_label'   => $jx['ws_jx_gov_portal_label']   ?? '',
            'ws_jx_wb_authority_url'   => $jx['ws_jx_wb_authority_url']   ?? '',
            'ws_jx_wb_authority_label' => $jx['ws_jx_wb_authority_label'] ?? '',
            'ws_jx_legislature_url'    => $jx['ws_jx_legislature_url']    ?? '',
            'ws_jx_legislature_label'  => $jx['ws_jx_legislature_label']  ?? '',
            'ws_jx_executive_url'      => $jx['ws_jx_executive_url']      ?? '',
            'ws_jx_executive_label'    => $jx['ws_jx_executive_label']    ?? '',
            'ws_jx_flag_source_url'    => $jx['ws_jx_flag_source_url']    ?? '',
        ];

        foreach ( $meta_fields as $key => $value ) {
            if ( $value !== '' && $value !== null ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        // Assign ws_jurisdiction taxonomy term and write ws_jx_term_id meta.
        //
        // wp_set_object_terms() writes the actual taxonomy relationship to
        // wp_term_relationships. This is what makes tax_query, wp_get_post_terms(),
        // and the ACF load_terms behavior work. It is not redundant with ws_jx_code
        // (a display string) or ws_jx_term_id (below).
        //
        // ws_jx_term_id is a deliberate convenience cache — it stores the term_id
        // directly on the post so seeders and admin tooling can retrieve it via
        // get_post_meta() without an additional get_term_by() or wp_get_post_terms()
        // call at runtime. The data is derivable from the taxonomy relationship but
        // the direct lookup is faster and simpler in contexts where only the ID is needed.
        if ( $term_id ) {
            wp_set_object_terms( $post_id, $term_id, WS_JURISDICTION_TAXONOMY );
            update_post_meta( $post_id, 'ws_jx_term_id', $term_id );
        }

        // Mark as seeded.
        // Direct get_post_meta() call is intentional here. ws_matrix_source is an
        // administrative flag written by the seeder and consumed exclusively by admin
        // tooling. It is not jurisdiction content and does not belong in the query layer.
        update_post_meta( $post_id, 'ws_matrix_source', 'jurisdiction-matrix' );
    }
}


// ── Gate: Unified Option-Gate Method ─────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_jurisdiction_matrix' ) !== '1.0.0' ) {
        ws_seed_jurisdiction_matrix();
        update_option( 'ws_seeded_jurisdiction_matrix', '1.0.0' );
    }
} );
