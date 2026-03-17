<?php
/**
 * court-matrix.php
 *
 * Global registry of U.S. courts for ws-core citation fields.
 *
 * STRUCTURE
 * ---------
 * Each entry:
 *   'name'        Full official court name
 *   'short'       Legal citation abbreviation
 *   'type'        scotus | federal_appellate | federal_district |
 *                 state_supreme | state_appellate
 *   'ws_jx_codes' Array of USPS codes this court serves, or null
 *                 (null = appears on every jurisdiction — SCOTUS only)
 *   'circuit'     Federal circuit number string, or null
 *   'level'       1=supreme/highest  2=appellate  3=district/trial
 *
 * SECTIONS
 * --------
 * 1. Supreme Court of the United States
 * 2. Federal Circuit Courts of Appeals (13)
 * 3. Federal District Courts (94)
 * 4. State & Territory Supreme Courts
 * 5. State Intermediate Appellate Courts
 *    Omitted (no intermediate tier): DE, ID, ME, MT, NH, ND, RI, SD, VT, WY
 *    PA has two entries: Commonwealth Court and Superior Court.
 *    TX/OK dual high courts each get two state_supreme entries.
 *
 * VERSION
 * -------
 * 2.3.1  Initial release.
 */

defined( 'ABSPATH' ) || exit;

global $ws_court_matrix;

$ws_court_matrix = [

    // ── 1. Supreme Court of the United States ─────────────────────────────

    'scotus' => [
        'name'        => 'Supreme Court of the United States',
        'short'       => 'SCOTUS',
        'type'        => 'scotus',
        'ws_jx_codes' => null,  // null = appears on all jurisdictions
        'circuit'     => null,
        'level'       => 1,
    ],

    // ── 2. Federal Circuit Courts of Appeals ──────────────────────────────

    'ca1' => [
        'name'        => 'U.S. Court of Appeals for the First Circuit',
        'short'       => '1st Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'ME', 'MA', 'NH', 'PR', 'RI' ],
        'circuit'     => '1',
        'level'       => 2,
    ],
    'ca2' => [
        'name'        => 'U.S. Court of Appeals for the Second Circuit',
        'short'       => '2d Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'CT', 'NY', 'VT' ],
        'circuit'     => '2',
        'level'       => 2,
    ],
    'ca3' => [
        'name'        => 'U.S. Court of Appeals for the Third Circuit',
        'short'       => '3d Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'DE', 'NJ', 'PA', 'VI' ],
        'circuit'     => '3',
        'level'       => 2,
    ],
    'ca4' => [
        'name'        => 'U.S. Court of Appeals for the Fourth Circuit',
        'short'       => '4th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'MD', 'NC', 'SC', 'VA', 'WV' ],
        'circuit'     => '4',
        'level'       => 2,
    ],
    'ca5' => [
        'name'        => 'U.S. Court of Appeals for the Fifth Circuit',
        'short'       => '5th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'LA', 'MS', 'TX' ],
        'circuit'     => '5',
        'level'       => 2,
    ],
    'ca6' => [
        'name'        => 'U.S. Court of Appeals for the Sixth Circuit',
        'short'       => '6th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'KY', 'MI', 'OH', 'TN' ],
        'circuit'     => '6',
        'level'       => 2,
    ],
    'ca7' => [
        'name'        => 'U.S. Court of Appeals for the Seventh Circuit',
        'short'       => '7th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'IL', 'IN', 'WI' ],
        'circuit'     => '7',
        'level'       => 2,
    ],
    'ca8' => [
        'name'        => 'U.S. Court of Appeals for the Eighth Circuit',
        'short'       => '8th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'AR', 'IA', 'MN', 'MO', 'NE', 'ND', 'SD' ],
        'circuit'     => '8',
        'level'       => 2,
    ],
    'ca9' => [
        'name'        => 'U.S. Court of Appeals for the Ninth Circuit',
        'short'       => '9th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'AK', 'AZ', 'CA', 'GU', 'HI', 'ID', 'MP', 'MT', 'NV', 'OR', 'WA' ],
        'circuit'     => '9',
        'level'       => 2,
    ],
    'ca10' => [
        'name'        => 'U.S. Court of Appeals for the Tenth Circuit',
        'short'       => '10th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'CO', 'KS', 'NM', 'OK', 'UT', 'WY' ],
        'circuit'     => '10',
        'level'       => 2,
    ],
    'ca11' => [
        'name'        => 'U.S. Court of Appeals for the Eleventh Circuit',
        'short'       => '11th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'AL', 'FL', 'GA' ],
        'circuit'     => '11',
        'level'       => 2,
    ],
    'cadc' => [
        'name'        => 'U.S. Court of Appeals for the District of Columbia Circuit',
        'short'       => 'D.C. Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'DC', 'US' ],
        'circuit'     => 'DC',
        'level'       => 2,
    ],
    'cafc' => [
        'name'        => 'U.S. Court of Appeals for the Federal Circuit',
        'short'       => 'Fed. Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'US' ],
        'circuit'     => 'FC',
        'level'       => 2,
    ],

    // ── 3. Federal District Courts ─────────────────────────────────────────

    // Alabama
    'nd-al' => [ 'name' => 'U.S. District Court for the Northern District of Alabama', 'short' => 'N.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AL' ], 'circuit' => '11', 'level' => 3 ],
    'md-al' => [ 'name' => 'U.S. District Court for the Middle District of Alabama',   'short' => 'M.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AL' ], 'circuit' => '11', 'level' => 3 ],
    'sd-al' => [ 'name' => 'U.S. District Court for the Southern District of Alabama', 'short' => 'S.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AL' ], 'circuit' => '11', 'level' => 3 ],
    // Alaska
    'd-ak'  => [ 'name' => 'U.S. District Court for the District of Alaska',           'short' => 'D. Alaska',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AK' ], 'circuit' => '9',  'level' => 3 ],
    // Arizona
    'd-az'  => [ 'name' => 'U.S. District Court for the District of Arizona',          'short' => 'D. Ariz.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'AZ' ], 'circuit' => '9',  'level' => 3 ],
    // Arkansas
    'ed-ar' => [ 'name' => 'U.S. District Court for the Eastern District of Arkansas', 'short' => 'E.D. Ark.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AR' ], 'circuit' => '8',  'level' => 3 ],
    'wd-ar' => [ 'name' => 'U.S. District Court for the Western District of Arkansas', 'short' => 'W.D. Ark.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'AR' ], 'circuit' => '8',  'level' => 3 ],
    // California
    'nd-ca' => [ 'name' => 'U.S. District Court for the Northern District of California', 'short' => 'N.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'CA' ], 'circuit' => '9', 'level' => 3 ],
    'ed-ca' => [ 'name' => 'U.S. District Court for the Eastern District of California', 'short' => 'E.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'CA' ], 'circuit' => '9', 'level' => 3 ],
    'cd-ca' => [ 'name' => 'U.S. District Court for the Central District of California',  'short' => 'C.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'CA' ], 'circuit' => '9', 'level' => 3 ],
    'sd-ca' => [ 'name' => 'U.S. District Court for the Southern District of California', 'short' => 'S.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'CA' ], 'circuit' => '9', 'level' => 3 ],
    // Colorado
    'd-co'  => [ 'name' => 'U.S. District Court for the District of Colorado',         'short' => 'D. Colo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'CO' ], 'circuit' => '10', 'level' => 3 ],
    // Connecticut
    'd-ct'  => [ 'name' => 'U.S. District Court for the District of Connecticut',      'short' => 'D. Conn.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'CT' ], 'circuit' => '2',  'level' => 3 ],
    // Delaware
    'd-de'  => [ 'name' => 'U.S. District Court for the District of Delaware',         'short' => 'D. Del.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'DE' ], 'circuit' => '3',  'level' => 3 ],
    // District of Columbia
    'd-dc'  => [ 'name' => 'U.S. District Court for the District of Columbia',         'short' => 'D.D.C.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'DC' ], 'circuit' => 'DC', 'level' => 3 ],
    // Florida
    'nd-fl' => [ 'name' => 'U.S. District Court for the Northern District of Florida', 'short' => 'N.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'FL' ], 'circuit' => '11', 'level' => 3 ],
    'md-fl' => [ 'name' => 'U.S. District Court for the Middle District of Florida',   'short' => 'M.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'FL' ], 'circuit' => '11', 'level' => 3 ],
    'sd-fl' => [ 'name' => 'U.S. District Court for the Southern District of Florida', 'short' => 'S.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'FL' ], 'circuit' => '11', 'level' => 3 ],
    // Georgia
    'nd-ga' => [ 'name' => 'U.S. District Court for the Northern District of Georgia', 'short' => 'N.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'GA' ], 'circuit' => '11', 'level' => 3 ],
    'md-ga' => [ 'name' => 'U.S. District Court for the Middle District of Georgia',   'short' => 'M.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'GA' ], 'circuit' => '11', 'level' => 3 ],
    'sd-ga' => [ 'name' => 'U.S. District Court for the Southern District of Georgia', 'short' => 'S.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'GA' ], 'circuit' => '11', 'level' => 3 ],
    // Guam
    'd-gu'  => [ 'name' => 'U.S. District Court for the District of Guam',             'short' => 'D. Guam',     'type' => 'federal_district', 'ws_jx_codes' => [ 'GU' ], 'circuit' => '9',  'level' => 3 ],
    // Hawaii — also covers AS (no standalone federal district court for American Samoa)
    'd-hi'  => [ 'name' => 'U.S. District Court for the District of Hawaii',           'short' => 'D. Haw.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'HI', 'AS' ], 'circuit' => '9', 'level' => 3 ],
    // Idaho
    'd-id'  => [ 'name' => 'U.S. District Court for the District of Idaho',            'short' => 'D. Idaho',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ID' ], 'circuit' => '9',  'level' => 3 ],
    // Illinois
    'nd-il' => [ 'name' => 'U.S. District Court for the Northern District of Illinois', 'short' => 'N.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'IL' ], 'circuit' => '7',  'level' => 3 ],
    'cd-il' => [ 'name' => 'U.S. District Court for the Central District of Illinois',  'short' => 'C.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'IL' ], 'circuit' => '7',  'level' => 3 ],
    'sd-il' => [ 'name' => 'U.S. District Court for the Southern District of Illinois', 'short' => 'S.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'IL' ], 'circuit' => '7',  'level' => 3 ],
    // Indiana
    'nd-in' => [ 'name' => 'U.S. District Court for the Northern District of Indiana', 'short' => 'N.D. Ind.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'IN' ], 'circuit' => '7',  'level' => 3 ],
    'sd-in' => [ 'name' => 'U.S. District Court for the Southern District of Indiana', 'short' => 'S.D. Ind.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'IN' ], 'circuit' => '7',  'level' => 3 ],
    // Iowa
    'nd-ia' => [ 'name' => 'U.S. District Court for the Northern District of Iowa',    'short' => 'N.D. Iowa',   'type' => 'federal_district', 'ws_jx_codes' => [ 'IA' ], 'circuit' => '8',  'level' => 3 ],
    'sd-ia' => [ 'name' => 'U.S. District Court for the Southern District of Iowa',    'short' => 'S.D. Iowa',   'type' => 'federal_district', 'ws_jx_codes' => [ 'IA' ], 'circuit' => '8',  'level' => 3 ],
    // Kansas
    'd-ks'  => [ 'name' => 'U.S. District Court for the District of Kansas',           'short' => 'D. Kan.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'KS' ], 'circuit' => '10', 'level' => 3 ],
    // Kentucky
    'ed-ky' => [ 'name' => 'U.S. District Court for the Eastern District of Kentucky', 'short' => 'E.D. Ky.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'KY' ], 'circuit' => '6',  'level' => 3 ],
    'wd-ky' => [ 'name' => 'U.S. District Court for the Western District of Kentucky', 'short' => 'W.D. Ky.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'KY' ], 'circuit' => '6',  'level' => 3 ],
    // Louisiana
    'ed-la' => [ 'name' => 'U.S. District Court for the Eastern District of Louisiana', 'short' => 'E.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'LA' ], 'circuit' => '5',  'level' => 3 ],
    'md-la' => [ 'name' => 'U.S. District Court for the Middle District of Louisiana',  'short' => 'M.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'LA' ], 'circuit' => '5',  'level' => 3 ],
    'wd-la' => [ 'name' => 'U.S. District Court for the Western District of Louisiana', 'short' => 'W.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'LA' ], 'circuit' => '5',  'level' => 3 ],
    // Maine
    'd-me'  => [ 'name' => 'U.S. District Court for the District of Maine',            'short' => 'D. Me.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'ME' ], 'circuit' => '1',  'level' => 3 ],
    // Maryland
    'd-md'  => [ 'name' => 'U.S. District Court for the District of Maryland',         'short' => 'D. Md.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'MD' ], 'circuit' => '4',  'level' => 3 ],
    // Massachusetts
    'd-ma'  => [ 'name' => 'U.S. District Court for the District of Massachusetts',    'short' => 'D. Mass.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'MA' ], 'circuit' => '1',  'level' => 3 ],
    // Michigan
    'ed-mi' => [ 'name' => 'U.S. District Court for the Eastern District of Michigan', 'short' => 'E.D. Mich.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'MI' ], 'circuit' => '6',  'level' => 3 ],
    'wd-mi' => [ 'name' => 'U.S. District Court for the Western District of Michigan', 'short' => 'W.D. Mich.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'MI' ], 'circuit' => '6',  'level' => 3 ],
    // Minnesota
    'd-mn'  => [ 'name' => 'U.S. District Court for the District of Minnesota',        'short' => 'D. Minn.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'MN' ], 'circuit' => '8',  'level' => 3 ],
    // Mississippi
    'nd-ms' => [ 'name' => 'U.S. District Court for the Northern District of Mississippi', 'short' => 'N.D. Miss.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'MS' ], 'circuit' => '5', 'level' => 3 ],
    'sd-ms' => [ 'name' => 'U.S. District Court for the Southern District of Mississippi', 'short' => 'S.D. Miss.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'MS' ], 'circuit' => '5', 'level' => 3 ],
    // Missouri
    'ed-mo' => [ 'name' => 'U.S. District Court for the Eastern District of Missouri', 'short' => 'E.D. Mo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'MO' ], 'circuit' => '8',  'level' => 3 ],
    'wd-mo' => [ 'name' => 'U.S. District Court for the Western District of Missouri', 'short' => 'W.D. Mo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'MO' ], 'circuit' => '8',  'level' => 3 ],
    // Montana
    'd-mt'  => [ 'name' => 'U.S. District Court for the District of Montana',          'short' => 'D. Mont.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'MT' ], 'circuit' => '9',  'level' => 3 ],
    // Nebraska
    'd-ne'  => [ 'name' => 'U.S. District Court for the District of Nebraska',         'short' => 'D. Neb.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'NE' ], 'circuit' => '8',  'level' => 3 ],
    // Nevada
    'd-nv'  => [ 'name' => 'U.S. District Court for the District of Nevada',           'short' => 'D. Nev.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'NV' ], 'circuit' => '9',  'level' => 3 ],
    // New Hampshire
    'd-nh'  => [ 'name' => 'U.S. District Court for the District of New Hampshire',    'short' => 'D.N.H.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'NH' ], 'circuit' => '1',  'level' => 3 ],
    // New Jersey
    'd-nj'  => [ 'name' => 'U.S. District Court for the District of New Jersey',       'short' => 'D.N.J.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'NJ' ], 'circuit' => '3',  'level' => 3 ],
    // New Mexico
    'd-nm'  => [ 'name' => 'U.S. District Court for the District of New Mexico',       'short' => 'D.N.M.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'NM' ], 'circuit' => '10', 'level' => 3 ],
    // New York
    'nd-ny' => [ 'name' => 'U.S. District Court for the Northern District of New York', 'short' => 'N.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'NY' ], 'circuit' => '2',  'level' => 3 ],
    'ed-ny' => [ 'name' => 'U.S. District Court for the Eastern District of New York',  'short' => 'E.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'NY' ], 'circuit' => '2',  'level' => 3 ],
    'sd-ny' => [ 'name' => 'U.S. District Court for the Southern District of New York', 'short' => 'S.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'NY' ], 'circuit' => '2',  'level' => 3 ],
    'wd-ny' => [ 'name' => 'U.S. District Court for the Western District of New York',  'short' => 'W.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'NY' ], 'circuit' => '2',  'level' => 3 ],
    // North Carolina
    'ed-nc' => [ 'name' => 'U.S. District Court for the Eastern District of North Carolina', 'short' => 'E.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'NC' ], 'circuit' => '4', 'level' => 3 ],
    'md-nc' => [ 'name' => 'U.S. District Court for the Middle District of North Carolina',  'short' => 'M.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'NC' ], 'circuit' => '4', 'level' => 3 ],
    'wd-nc' => [ 'name' => 'U.S. District Court for the Western District of North Carolina', 'short' => 'W.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'NC' ], 'circuit' => '4', 'level' => 3 ],
    // North Dakota
    'd-nd'  => [ 'name' => 'U.S. District Court for the District of North Dakota',     'short' => 'D.N.D.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'ND' ], 'circuit' => '8',  'level' => 3 ],
    // Northern Mariana Islands
    'd-mp'  => [ 'name' => 'U.S. District Court for the Northern Mariana Islands',     'short' => 'D.N. Mar. I.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'MP' ], 'circuit' => '9', 'level' => 3 ],
    // Ohio
    'nd-oh' => [ 'name' => 'U.S. District Court for the Northern District of Ohio',    'short' => 'N.D. Ohio',   'type' => 'federal_district', 'ws_jx_codes' => [ 'OH' ], 'circuit' => '6',  'level' => 3 ],
    'sd-oh' => [ 'name' => 'U.S. District Court for the Southern District of Ohio',    'short' => 'S.D. Ohio',   'type' => 'federal_district', 'ws_jx_codes' => [ 'OH' ], 'circuit' => '6',  'level' => 3 ],
    // Oklahoma
    'nd-ok' => [ 'name' => 'U.S. District Court for the Northern District of Oklahoma', 'short' => 'N.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'OK' ], 'circuit' => '10', 'level' => 3 ],
    'ed-ok' => [ 'name' => 'U.S. District Court for the Eastern District of Oklahoma',  'short' => 'E.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'OK' ], 'circuit' => '10', 'level' => 3 ],
    'wd-ok' => [ 'name' => 'U.S. District Court for the Western District of Oklahoma',  'short' => 'W.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'OK' ], 'circuit' => '10', 'level' => 3 ],
    // Oregon
    'd-or'  => [ 'name' => 'U.S. District Court for the District of Oregon',           'short' => 'D. Or.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'OR' ], 'circuit' => '9',  'level' => 3 ],
    // Pennsylvania
    'ed-pa' => [ 'name' => 'U.S. District Court for the Eastern District of Pennsylvania', 'short' => 'E.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'PA' ], 'circuit' => '3', 'level' => 3 ],
    'md-pa' => [ 'name' => 'U.S. District Court for the Middle District of Pennsylvania',  'short' => 'M.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'PA' ], 'circuit' => '3', 'level' => 3 ],
    'wd-pa' => [ 'name' => 'U.S. District Court for the Western District of Pennsylvania', 'short' => 'W.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'PA' ], 'circuit' => '3', 'level' => 3 ],
    // Puerto Rico
    'd-pr'  => [ 'name' => 'U.S. District Court for the District of Puerto Rico',      'short' => 'D.P.R.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'PR' ], 'circuit' => '1',  'level' => 3 ],
    // Rhode Island
    'd-ri'  => [ 'name' => 'U.S. District Court for the District of Rhode Island',     'short' => 'D.R.I.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'RI' ], 'circuit' => '1',  'level' => 3 ],
    // South Carolina
    'd-sc'  => [ 'name' => 'U.S. District Court for the District of South Carolina',   'short' => 'D.S.C.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'SC' ], 'circuit' => '4',  'level' => 3 ],
    // South Dakota
    'd-sd'  => [ 'name' => 'U.S. District Court for the District of South Dakota',     'short' => 'D.S.D.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'SD' ], 'circuit' => '8',  'level' => 3 ],
    // Tennessee
    'ed-tn' => [ 'name' => 'U.S. District Court for the Eastern District of Tennessee', 'short' => 'E.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'TN' ], 'circuit' => '6',  'level' => 3 ],
    'md-tn' => [ 'name' => 'U.S. District Court for the Middle District of Tennessee',  'short' => 'M.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'TN' ], 'circuit' => '6',  'level' => 3 ],
    'wd-tn' => [ 'name' => 'U.S. District Court for the Western District of Tennessee', 'short' => 'W.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'TN' ], 'circuit' => '6',  'level' => 3 ],
    // Texas
    'nd-tx' => [ 'name' => 'U.S. District Court for the Northern District of Texas',   'short' => 'N.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'TX' ], 'circuit' => '5',  'level' => 3 ],
    'ed-tx' => [ 'name' => 'U.S. District Court for the Eastern District of Texas',    'short' => 'E.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'TX' ], 'circuit' => '5',  'level' => 3 ],
    'sd-tx' => [ 'name' => 'U.S. District Court for the Southern District of Texas',   'short' => 'S.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'TX' ], 'circuit' => '5',  'level' => 3 ],
    'wd-tx' => [ 'name' => 'U.S. District Court for the Western District of Texas',    'short' => 'W.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'TX' ], 'circuit' => '5',  'level' => 3 ],
    // Utah
    'd-ut'  => [ 'name' => 'U.S. District Court for the District of Utah',             'short' => 'D. Utah',     'type' => 'federal_district', 'ws_jx_codes' => [ 'UT' ], 'circuit' => '10', 'level' => 3 ],
    // Vermont
    'd-vt'  => [ 'name' => 'U.S. District Court for the District of Vermont',          'short' => 'D. Vt.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'VT' ], 'circuit' => '2',  'level' => 3 ],
    // Virgin Islands
    'd-vi'  => [ 'name' => 'U.S. District Court for the District of the Virgin Islands', 'short' => 'D.V.I.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'VI' ], 'circuit' => '3',  'level' => 3 ],
    // Virginia
    'ed-va' => [ 'name' => 'U.S. District Court for the Eastern District of Virginia', 'short' => 'E.D. Va.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'VA' ], 'circuit' => '4',  'level' => 3 ],
    'wd-va' => [ 'name' => 'U.S. District Court for the Western District of Virginia', 'short' => 'W.D. Va.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'VA' ], 'circuit' => '4',  'level' => 3 ],
    // Washington
    'ed-wa' => [ 'name' => 'U.S. District Court for the Eastern District of Washington', 'short' => 'E.D. Wash.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'WA' ], 'circuit' => '9', 'level' => 3 ],
    'wd-wa' => [ 'name' => 'U.S. District Court for the Western District of Washington', 'short' => 'W.D. Wash.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'WA' ], 'circuit' => '9', 'level' => 3 ],
    // West Virginia
    'nd-wv' => [ 'name' => 'U.S. District Court for the Northern District of West Virginia', 'short' => 'N.D.W. Va.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'WV' ], 'circuit' => '4', 'level' => 3 ],
    'sd-wv' => [ 'name' => 'U.S. District Court for the Southern District of West Virginia', 'short' => 'S.D.W. Va.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'WV' ], 'circuit' => '4', 'level' => 3 ],
    // Wisconsin
    'ed-wi' => [ 'name' => 'U.S. District Court for the Eastern District of Wisconsin', 'short' => 'E.D. Wis.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'WI' ], 'circuit' => '7',  'level' => 3 ],
    'wd-wi' => [ 'name' => 'U.S. District Court for the Western District of Wisconsin', 'short' => 'W.D. Wis.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'WI' ], 'circuit' => '7',  'level' => 3 ],
    // Wyoming
    'd-wy'  => [ 'name' => 'U.S. District Court for the District of Wyoming',          'short' => 'D. Wyo.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'WY' ], 'circuit' => '10', 'level' => 3 ],

    // ── 4. State & Territory Supreme Courts ───────────────────────────────
    //
    // TX and OK each have dual high courts (civil + criminal) — two entries each.
    // NY: Court of Appeals is the highest state court.
    // MD: Supreme Court of Maryland (renamed from Court of Appeals in 2022).
    // ME/MA: Supreme Judicial Court.
    // WV: Supreme Court of Appeals.
    // DC: D.C. Court of Appeals is the highest local court.

    'al-sup'  => [ 'name' => 'Supreme Court of Alabama',                       'short' => 'Ala. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'AL' ], 'circuit' => null, 'level' => 1 ],
    'ak-sup'  => [ 'name' => 'Alaska Supreme Court',                           'short' => 'Alaska Sup. Ct.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'AK' ], 'circuit' => null, 'level' => 1 ],
    'az-sup'  => [ 'name' => 'Arizona Supreme Court',                          'short' => 'Ariz. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'AZ' ], 'circuit' => null, 'level' => 1 ],
    'ar-sup'  => [ 'name' => 'Arkansas Supreme Court',                         'short' => 'Ark. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'AR' ], 'circuit' => null, 'level' => 1 ],
    'ca-sup'  => [ 'name' => 'Supreme Court of California',                    'short' => 'Cal. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'CA' ], 'circuit' => null, 'level' => 1 ],
    'co-sup'  => [ 'name' => 'Colorado Supreme Court',                         'short' => 'Colo. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'CO' ], 'circuit' => null, 'level' => 1 ],
    'ct-sup'  => [ 'name' => 'Connecticut Supreme Court',                      'short' => 'Conn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'CT' ], 'circuit' => null, 'level' => 1 ],
    'de-sup'  => [ 'name' => 'Delaware Supreme Court',                         'short' => 'Del. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'DE' ], 'circuit' => null, 'level' => 1 ],
    'fl-sup'  => [ 'name' => 'Florida Supreme Court',                          'short' => 'Fla. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'FL' ], 'circuit' => null, 'level' => 1 ],
    'ga-sup'  => [ 'name' => 'Supreme Court of Georgia',                       'short' => 'Ga. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'GA' ], 'circuit' => null, 'level' => 1 ],
    'hi-sup'  => [ 'name' => 'Hawaii Supreme Court',                           'short' => 'Haw. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'HI' ], 'circuit' => null, 'level' => 1 ],
    'id-sup'  => [ 'name' => 'Idaho Supreme Court',                            'short' => 'Idaho Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'ID' ], 'circuit' => null, 'level' => 1 ],
    'il-sup'  => [ 'name' => 'Illinois Supreme Court',                         'short' => 'Ill. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'IL' ], 'circuit' => null, 'level' => 1 ],
    'in-sup'  => [ 'name' => 'Indiana Supreme Court',                          'short' => 'Ind. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'IN' ], 'circuit' => null, 'level' => 1 ],
    'ia-sup'  => [ 'name' => 'Iowa Supreme Court',                             'short' => 'Iowa Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'IA' ], 'circuit' => null, 'level' => 1 ],
    'ks-sup'  => [ 'name' => 'Kansas Supreme Court',                           'short' => 'Kan. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'KS' ], 'circuit' => null, 'level' => 1 ],
    'ky-sup'  => [ 'name' => 'Kentucky Supreme Court',                         'short' => 'Ky. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'KY' ], 'circuit' => null, 'level' => 1 ],
    'la-sup'  => [ 'name' => 'Louisiana Supreme Court',                        'short' => 'La. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'LA' ], 'circuit' => null, 'level' => 1 ],
    'me-sup'  => [ 'name' => 'Maine Supreme Judicial Court',                   'short' => 'Me. Sup. Jud. Ct.',      'type' => 'state_supreme', 'ws_jx_codes' => [ 'ME' ], 'circuit' => null, 'level' => 1 ],
    'md-sup'  => [ 'name' => 'Supreme Court of Maryland',                      'short' => 'Md. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'MD' ], 'circuit' => null, 'level' => 1 ],
    'ma-sup'  => [ 'name' => 'Massachusetts Supreme Judicial Court',           'short' => 'Mass. Sup. Jud. Ct.',    'type' => 'state_supreme', 'ws_jx_codes' => [ 'MA' ], 'circuit' => null, 'level' => 1 ],
    'mi-sup'  => [ 'name' => 'Michigan Supreme Court',                         'short' => 'Mich. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'MI' ], 'circuit' => null, 'level' => 1 ],
    'mn-sup'  => [ 'name' => 'Minnesota Supreme Court',                        'short' => 'Minn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'MN' ], 'circuit' => null, 'level' => 1 ],
    'ms-sup'  => [ 'name' => 'Mississippi Supreme Court',                      'short' => 'Miss. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'MS' ], 'circuit' => null, 'level' => 1 ],
    'mo-sup'  => [ 'name' => 'Missouri Supreme Court',                         'short' => 'Mo. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'MO' ], 'circuit' => null, 'level' => 1 ],
    'mt-sup'  => [ 'name' => 'Montana Supreme Court',                          'short' => 'Mont. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'MT' ], 'circuit' => null, 'level' => 1 ],
    'ne-sup'  => [ 'name' => 'Nebraska Supreme Court',                         'short' => 'Neb. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NE' ], 'circuit' => null, 'level' => 1 ],
    'nv-sup'  => [ 'name' => 'Nevada Supreme Court',                           'short' => 'Nev. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NV' ], 'circuit' => null, 'level' => 1 ],
    'nh-sup'  => [ 'name' => 'New Hampshire Supreme Court',                    'short' => 'N.H. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NH' ], 'circuit' => null, 'level' => 1 ],
    'nj-sup'  => [ 'name' => 'New Jersey Supreme Court',                       'short' => 'N.J. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NJ' ], 'circuit' => null, 'level' => 1 ],
    'nm-sup'  => [ 'name' => 'New Mexico Supreme Court',                       'short' => 'N.M. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NM' ], 'circuit' => null, 'level' => 1 ],
    'ny-app'  => [ 'name' => 'New York Court of Appeals',                      'short' => 'N.Y. Ct. App.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NY' ], 'circuit' => null, 'level' => 1 ],
    'nc-sup'  => [ 'name' => 'North Carolina Supreme Court',                   'short' => 'N.C. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'NC' ], 'circuit' => null, 'level' => 1 ],
    'nd-sup'  => [ 'name' => 'North Dakota Supreme Court',                     'short' => 'N.D. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ND' ], 'circuit' => null, 'level' => 1 ],
    'oh-sup'  => [ 'name' => 'Ohio Supreme Court',                             'short' => 'Ohio Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'OH' ], 'circuit' => null, 'level' => 1 ],
    'ok-sup'  => [ 'name' => 'Oklahoma Supreme Court',                         'short' => 'Okla. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'OK' ], 'circuit' => null, 'level' => 1 ],
    'ok-cca'  => [ 'name' => 'Oklahoma Court of Criminal Appeals',             'short' => 'Okla. Crim. App.',       'type' => 'state_supreme', 'ws_jx_codes' => [ 'OK' ], 'circuit' => null, 'level' => 1 ],
    'or-sup'  => [ 'name' => 'Oregon Supreme Court',                           'short' => 'Or. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'OR' ], 'circuit' => null, 'level' => 1 ],
    'pa-sup'  => [ 'name' => 'Supreme Court of Pennsylvania',                  'short' => 'Pa. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'PA' ], 'circuit' => null, 'level' => 1 ],
    'ri-sup'  => [ 'name' => 'Rhode Island Supreme Court',                     'short' => 'R.I. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'RI' ], 'circuit' => null, 'level' => 1 ],
    'sc-sup'  => [ 'name' => 'South Carolina Supreme Court',                   'short' => 'S.C. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'SC' ], 'circuit' => null, 'level' => 1 ],
    'sd-sup'  => [ 'name' => 'South Dakota Supreme Court',                     'short' => 'S.D. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'SD' ], 'circuit' => null, 'level' => 1 ],
    'tn-sup'  => [ 'name' => 'Tennessee Supreme Court',                        'short' => 'Tenn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'TN' ], 'circuit' => null, 'level' => 1 ],
    'tx-sup'  => [ 'name' => 'Supreme Court of Texas',                         'short' => 'Tex. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'TX' ], 'circuit' => null, 'level' => 1 ],
    'tx-cca'  => [ 'name' => 'Texas Court of Criminal Appeals',                'short' => 'Tex. Crim. App.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'TX' ], 'circuit' => null, 'level' => 1 ],
    'ut-sup'  => [ 'name' => 'Utah Supreme Court',                             'short' => 'Utah Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'UT' ], 'circuit' => null, 'level' => 1 ],
    'vt-sup'  => [ 'name' => 'Vermont Supreme Court',                          'short' => 'Vt. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'VT' ], 'circuit' => null, 'level' => 1 ],
    'va-sup'  => [ 'name' => 'Supreme Court of Virginia',                      'short' => 'Va. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'VA' ], 'circuit' => null, 'level' => 1 ],
    'wa-sup'  => [ 'name' => 'Washington Supreme Court',                       'short' => 'Wash. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'WA' ], 'circuit' => null, 'level' => 1 ],
    'wv-sup'  => [ 'name' => 'Supreme Court of Appeals of West Virginia',      'short' => 'W. Va. Sup. Ct.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'WV' ], 'circuit' => null, 'level' => 1 ],
    'wi-sup'  => [ 'name' => 'Wisconsin Supreme Court',                        'short' => 'Wis. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'WI' ], 'circuit' => null, 'level' => 1 ],
    'wy-sup'  => [ 'name' => 'Wyoming Supreme Court',                          'short' => 'Wyo. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'WY' ], 'circuit' => null, 'level' => 1 ],
    // D.C. and territories
    'dc-app'  => [ 'name' => 'District of Columbia Court of Appeals',          'short' => 'D.C. Ct. App.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'DC' ], 'circuit' => null, 'level' => 1 ],
    'pr-sup'  => [ 'name' => 'Supreme Court of Puerto Rico',                   'short' => 'P.R. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'PR' ], 'circuit' => null, 'level' => 1 ],
    'vi-sup'  => [ 'name' => 'Supreme Court of the Virgin Islands',            'short' => 'V.I. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'VI' ], 'circuit' => null, 'level' => 1 ],
    'gu-sup'  => [ 'name' => 'Supreme Court of Guam',                          'short' => 'Guam Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'GU' ], 'circuit' => null, 'level' => 1 ],
    'as-hct'  => [ 'name' => 'High Court of American Samoa',                   'short' => 'Am. Samoa H. Ct.',       'type' => 'state_supreme', 'ws_jx_codes' => [ 'AS' ], 'circuit' => null, 'level' => 1 ],
    'mp-sup'  => [ 'name' => 'Supreme Court of the Northern Mariana Islands',  'short' => 'N. Mar. I. Sup. Ct.',    'type' => 'state_supreme', 'ws_jx_codes' => [ 'MP' ], 'circuit' => null, 'level' => 1 ],

    // ── 5. State Intermediate Appellate Courts ─────────────────────────────
    //
    // Omitted (no intermediate appellate tier): DE, ID, ME, MT, NH, ND, RI, SD, VT, WY
    // PA: two entries — Commonwealth Court (public/admin law) and Superior Court (general).
    // One entry per jurisdiction; specific division noted in the citation label.

    'al-app'     => [ 'name' => 'Alabama Court of Civil Appeals',                      'short' => 'Ala. Civ. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'AL' ], 'circuit' => null, 'level' => 2 ],
    'ak-app'     => [ 'name' => 'Alaska Court of Appeals',                             'short' => 'Alaska Ct. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'AK' ], 'circuit' => null, 'level' => 2 ],
    'az-app'     => [ 'name' => 'Arizona Court of Appeals',                            'short' => 'Ariz. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'AZ' ], 'circuit' => null, 'level' => 2 ],
    'ar-app'     => [ 'name' => 'Arkansas Court of Appeals',                           'short' => 'Ark. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'AR' ], 'circuit' => null, 'level' => 2 ],
    'ca-app'     => [ 'name' => 'California Court of Appeal',                          'short' => 'Cal. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'CA' ], 'circuit' => null, 'level' => 2 ],
    'co-app'     => [ 'name' => 'Colorado Court of Appeals',                           'short' => 'Colo. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'CO' ], 'circuit' => null, 'level' => 2 ],
    'ct-app'     => [ 'name' => 'Connecticut Appellate Court',                         'short' => 'Conn. App. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'CT' ], 'circuit' => null, 'level' => 2 ],
    'fl-app'     => [ 'name' => 'Florida District Courts of Appeal',                   'short' => 'Fla. Dist. Ct. App.',    'type' => 'state_appellate', 'ws_jx_codes' => [ 'FL' ], 'circuit' => null, 'level' => 2 ],
    'ga-app'     => [ 'name' => 'Georgia Court of Appeals',                            'short' => 'Ga. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'GA' ], 'circuit' => null, 'level' => 2 ],
    'hi-app'     => [ 'name' => 'Intermediate Court of Appeals of Hawaii',             'short' => 'Haw. ICA',               'type' => 'state_appellate', 'ws_jx_codes' => [ 'HI' ], 'circuit' => null, 'level' => 2 ],
    'il-app'     => [ 'name' => 'Illinois Appellate Court',                            'short' => 'Ill. App. Ct.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'IL' ], 'circuit' => null, 'level' => 2 ],
    'in-app'     => [ 'name' => 'Indiana Court of Appeals',                            'short' => 'Ind. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'IN' ], 'circuit' => null, 'level' => 2 ],
    'ia-app'     => [ 'name' => 'Iowa Court of Appeals',                               'short' => 'Iowa Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'IA' ], 'circuit' => null, 'level' => 2 ],
    'ks-app'     => [ 'name' => 'Kansas Court of Appeals',                             'short' => 'Kan. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'KS' ], 'circuit' => null, 'level' => 2 ],
    'ky-app'     => [ 'name' => 'Kentucky Court of Appeals',                           'short' => 'Ky. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'KY' ], 'circuit' => null, 'level' => 2 ],
    'la-app'     => [ 'name' => 'Louisiana Courts of Appeal',                          'short' => 'La. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'LA' ], 'circuit' => null, 'level' => 2 ],
    'md-app'     => [ 'name' => 'Appellate Court of Maryland',                         'short' => 'Md. App. Ct.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'MD' ], 'circuit' => null, 'level' => 2 ],
    'ma-app'     => [ 'name' => 'Massachusetts Appeals Court',                         'short' => 'Mass. App. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'MA' ], 'circuit' => null, 'level' => 2 ],
    'mi-app'     => [ 'name' => 'Michigan Court of Appeals',                           'short' => 'Mich. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'MI' ], 'circuit' => null, 'level' => 2 ],
    'mn-app'     => [ 'name' => 'Minnesota Court of Appeals',                          'short' => 'Minn. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'MN' ], 'circuit' => null, 'level' => 2 ],
    'ms-app'     => [ 'name' => 'Mississippi Court of Appeals',                        'short' => 'Miss. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'MS' ], 'circuit' => null, 'level' => 2 ],
    'mo-app'     => [ 'name' => 'Missouri Court of Appeals',                           'short' => 'Mo. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'MO' ], 'circuit' => null, 'level' => 2 ],
    'ne-app'     => [ 'name' => 'Nebraska Court of Appeals',                           'short' => 'Neb. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'NE' ], 'circuit' => null, 'level' => 2 ],
    'nv-app'     => [ 'name' => 'Nevada Court of Appeals',                             'short' => 'Nev. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'NV' ], 'circuit' => null, 'level' => 2 ],
    'nj-app'     => [ 'name' => 'New Jersey Superior Court, Appellate Division',       'short' => 'N.J. Super. App. Div.',  'type' => 'state_appellate', 'ws_jx_codes' => [ 'NJ' ], 'circuit' => null, 'level' => 2 ],
    'nm-app'     => [ 'name' => 'New Mexico Court of Appeals',                         'short' => 'N.M. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'NM' ], 'circuit' => null, 'level' => 2 ],
    'ny-appdiv'  => [ 'name' => 'New York Supreme Court, Appellate Division',          'short' => 'N.Y. App. Div.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'NY' ], 'circuit' => null, 'level' => 2 ],
    'nc-app'     => [ 'name' => 'North Carolina Court of Appeals',                     'short' => 'N.C. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'NC' ], 'circuit' => null, 'level' => 2 ],
    'oh-app'     => [ 'name' => 'Ohio Courts of Appeals',                              'short' => 'Ohio Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'OH' ], 'circuit' => null, 'level' => 2 ],
    'ok-app'     => [ 'name' => 'Oklahoma Court of Civil Appeals',                     'short' => 'Okla. Civ. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'OK' ], 'circuit' => null, 'level' => 2 ],
    'or-app'     => [ 'name' => 'Oregon Court of Appeals',                             'short' => 'Or. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'OR' ], 'circuit' => null, 'level' => 2 ],
    'pa-cw'      => [ 'name' => 'Pennsylvania Commonwealth Court',                     'short' => 'Pa. Commw. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'PA' ], 'circuit' => null, 'level' => 2 ],
    'pa-sup-app' => [ 'name' => 'Pennsylvania Superior Court',                         'short' => 'Pa. Super. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'PA' ], 'circuit' => null, 'level' => 2 ],
    'sc-app'     => [ 'name' => 'South Carolina Court of Appeals',                     'short' => 'S.C. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'SC' ], 'circuit' => null, 'level' => 2 ],
    'tn-app'     => [ 'name' => 'Tennessee Court of Appeals',                          'short' => 'Tenn. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'TN' ], 'circuit' => null, 'level' => 2 ],
    'tx-app'     => [ 'name' => 'Texas Courts of Appeals',                             'short' => 'Tex. App.',              'type' => 'state_appellate', 'ws_jx_codes' => [ 'TX' ], 'circuit' => null, 'level' => 2 ],
    'ut-app'     => [ 'name' => 'Utah Court of Appeals',                               'short' => 'Utah Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'UT' ], 'circuit' => null, 'level' => 2 ],
    'va-app'     => [ 'name' => 'Virginia Court of Appeals',                           'short' => 'Va. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'VA' ], 'circuit' => null, 'level' => 2 ],
    'wa-app'     => [ 'name' => 'Washington Court of Appeals',                         'short' => 'Wash. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'WA' ], 'circuit' => null, 'level' => 2 ],
    'wv-app'     => [ 'name' => 'West Virginia Intermediate Court of Appeals',         'short' => 'W. Va. Ct. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'WV' ], 'circuit' => null, 'level' => 2 ],
    'wi-app'     => [ 'name' => 'Wisconsin Court of Appeals',                          'short' => 'Wis. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'WI' ], 'circuit' => null, 'level' => 2 ],

];
