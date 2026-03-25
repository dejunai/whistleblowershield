<?php
/**
 * matrix-federal-courts.php
 *
 * Global registry of U.S. courts for ws-core citation fields.
 *
 * STRUCTURE
 * ---------
 * Each entry:
 *   'name'        Full official court name
 *   'short'       Legal citation abbreviation
 *   'type'        scotus | federal_appellate | federal_district
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
 *
 * State and territory courts live in matrix-state-courts.php ($ws_state_court_matrix).
 * ws_interp_load_court_choices() merges both matrices when the parent statute is
 * federal; for state statutes it uses $ws_state_court_matrix only.
 *
 * VERSION
 * -------
 * 2.3.1  Initial release.
 * 3.7.0  Removed sections 4–5 (state & territory courts) — extracted to
 *         matrix-state-courts.php. Enables context-aware court select: federal
 *         statute = all courts; state statute = state courts only.
 *         Added 'other' sentinel entry (ws_jx_codes = '__manual__', level = 99):
 *         signals the save hook to skip auto-population of ws_jx_interp_affected_jx
 *         and reveals the ws_jx_interp_court_name free-text field.
 *         Gate bumped to 1.1.0.
 */

defined( 'ABSPATH' ) || exit;

global $ws_court_matrix;

$ws_court_matrix = [

    // ── 0. Other (edge case — court not in matrix) ────────────────────────
    //
    // Selecting this entry reveals ws_jx_interp_court_name (free text field).
    // ws_jx_codes = '__manual__' signals the save hook to skip auto-population
    // of ws_jx_interp_affected_jx — the editor must set scope manually.
    // level = 99 ensures this entry sorts last in the select list.

    'other' => [
        'name'        => 'Other (specify below)',
        'short'       => 'Other',
        'type'        => 'other',
        'ws_jx_codes' => '__manual__',
        'circuit'     => null,
        'level'       => 99,
    ],

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
        'ws_jx_codes' => [ 'me', 'ma', 'nh', 'pr', 'ri' ],
        'circuit'     => '1',
        'level'       => 2,
    ],
    'ca2' => [
        'name'        => 'U.S. Court of Appeals for the Second Circuit',
        'short'       => '2d Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'ct', 'ny', 'vt' ],
        'circuit'     => '2',
        'level'       => 2,
    ],
    'ca3' => [
        'name'        => 'U.S. Court of Appeals for the Third Circuit',
        'short'       => '3d Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'de', 'nj', 'pa', 'vi' ],
        'circuit'     => '3',
        'level'       => 2,
    ],
    'ca4' => [
        'name'        => 'U.S. Court of Appeals for the Fourth Circuit',
        'short'       => '4th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'md', 'nc', 'sc', 'va', 'wv' ],
        'circuit'     => '4',
        'level'       => 2,
    ],
    'ca5' => [
        'name'        => 'U.S. Court of Appeals for the Fifth Circuit',
        'short'       => '5th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'la', 'ms', 'tx' ],
        'circuit'     => '5',
        'level'       => 2,
    ],
    'ca6' => [
        'name'        => 'U.S. Court of Appeals for the Sixth Circuit',
        'short'       => '6th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'ky', 'mi', 'oh', 'tn' ],
        'circuit'     => '6',
        'level'       => 2,
    ],
    'ca7' => [
        'name'        => 'U.S. Court of Appeals for the Seventh Circuit',
        'short'       => '7th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'il', 'in', 'wi' ],
        'circuit'     => '7',
        'level'       => 2,
    ],
    'ca8' => [
        'name'        => 'U.S. Court of Appeals for the Eighth Circuit',
        'short'       => '8th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'ar', 'ia', 'mn', 'mo', 'ne', 'nd', 'sd' ],
        'circuit'     => '8',
        'level'       => 2,
    ],
    'ca9' => [
        'name'        => 'U.S. Court of Appeals for the Ninth Circuit',
        'short'       => '9th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'ak', 'az', 'ca', 'gu', 'hi', 'id', 'mp', 'mt', 'nv', 'or', 'wa' ],
        'circuit'     => '9',
        'level'       => 2,
    ],
    'ca10' => [
        'name'        => 'U.S. Court of Appeals for the Tenth Circuit',
        'short'       => '10th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'co', 'ks', 'nm', 'ok', 'ut', 'wy' ],
        'circuit'     => '10',
        'level'       => 2,
    ],
    'ca11' => [
        'name'        => 'U.S. Court of Appeals for the Eleventh Circuit',
        'short'       => '11th Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'al', 'fl', 'ga' ],
        'circuit'     => '11',
        'level'       => 2,
    ],
    'cadc' => [
        'name'        => 'U.S. Court of Appeals for the District of Columbia Circuit',
        'short'       => 'D.C. Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'dc', 'us' ],
        'circuit'     => 'dc',
        'level'       => 2,
    ],
    'cafc' => [
        'name'        => 'U.S. Court of Appeals for the Federal Circuit',
        'short'       => 'Fed. Cir.',
        'type'        => 'federal_appellate',
        'ws_jx_codes' => [ 'us' ],
        'circuit'     => 'fc',
        'level'       => 2,
    ],

    // ── 3. Federal District Courts ─────────────────────────────────────────

    // Alabama
    'nd-al' => [ 'name' => 'U.S. District Court for the Northern District of Alabama', 'short' => 'N.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'al' ], 'circuit' => '11', 'level' => 3 ],
    'md-al' => [ 'name' => 'U.S. District Court for the Middle District of Alabama',   'short' => 'M.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'al' ], 'circuit' => '11', 'level' => 3 ],
    'sd-al' => [ 'name' => 'U.S. District Court for the Southern District of Alabama', 'short' => 'S.D. Ala.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'al' ], 'circuit' => '11', 'level' => 3 ],
    // Alaska
    'd-ak'  => [ 'name' => 'U.S. District Court for the District of Alaska',           'short' => 'D. Alaska',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ak' ], 'circuit' => '9',  'level' => 3 ],
    // Arizona
    'd-az'  => [ 'name' => 'U.S. District Court for the District of Arizona',          'short' => 'D. Ariz.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'az' ], 'circuit' => '9',  'level' => 3 ],
    // Arkansas
    'ed-ar' => [ 'name' => 'U.S. District Court for the Eastern District of Arkansas', 'short' => 'E.D. Ark.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ar' ], 'circuit' => '8',  'level' => 3 ],
    'wd-ar' => [ 'name' => 'U.S. District Court for the Western District of Arkansas', 'short' => 'W.D. Ark.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ar' ], 'circuit' => '8',  'level' => 3 ],
    // California
    'nd-ca' => [ 'name' => 'U.S. District Court for the Northern District of California', 'short' => 'N.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ca' ], 'circuit' => '9', 'level' => 3 ],
    'ed-ca' => [ 'name' => 'U.S. District Court for the Eastern District of California', 'short' => 'E.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ca' ], 'circuit' => '9', 'level' => 3 ],
    'cd-ca' => [ 'name' => 'U.S. District Court for the Central District of California',  'short' => 'C.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ca' ], 'circuit' => '9', 'level' => 3 ],
    'sd-ca' => [ 'name' => 'U.S. District Court for the Southern District of California', 'short' => 'S.D. Cal.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ca' ], 'circuit' => '9', 'level' => 3 ],
    // Colorado
    'd-co'  => [ 'name' => 'U.S. District Court for the District of Colorado',         'short' => 'D. Colo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'co' ], 'circuit' => '10', 'level' => 3 ],
    // Connecticut
    'd-ct'  => [ 'name' => 'U.S. District Court for the District of Connecticut',      'short' => 'D. Conn.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ct' ], 'circuit' => '2',  'level' => 3 ],
    // Delaware
    'd-de'  => [ 'name' => 'U.S. District Court for the District of Delaware',         'short' => 'D. Del.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'de' ], 'circuit' => '3',  'level' => 3 ],
    // District of Columbia
    'd-dc'  => [ 'name' => 'U.S. District Court for the District of Columbia',         'short' => 'D.D.C.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'dc' ], 'circuit' => 'dc', 'level' => 3 ],
    // Florida
    'nd-fl' => [ 'name' => 'U.S. District Court for the Northern District of Florida', 'short' => 'N.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'fl' ], 'circuit' => '11', 'level' => 3 ],
    'md-fl' => [ 'name' => 'U.S. District Court for the Middle District of Florida',   'short' => 'M.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'fl' ], 'circuit' => '11', 'level' => 3 ],
    'sd-fl' => [ 'name' => 'U.S. District Court for the Southern District of Florida', 'short' => 'S.D. Fla.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'fl' ], 'circuit' => '11', 'level' => 3 ],
    // Georgia
    'nd-ga' => [ 'name' => 'U.S. District Court for the Northern District of Georgia', 'short' => 'N.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ga' ], 'circuit' => '11', 'level' => 3 ],
    'md-ga' => [ 'name' => 'U.S. District Court for the Middle District of Georgia',   'short' => 'M.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ga' ], 'circuit' => '11', 'level' => 3 ],
    'sd-ga' => [ 'name' => 'U.S. District Court for the Southern District of Georgia', 'short' => 'S.D. Ga.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ga' ], 'circuit' => '11', 'level' => 3 ],
    // Guam
    'd-gu'  => [ 'name' => 'U.S. District Court for the District of Guam',             'short' => 'D. Guam',     'type' => 'federal_district', 'ws_jx_codes' => [ 'gu' ], 'circuit' => '9',  'level' => 3 ],
    // Hawaii — also covers AS (no standalone federal district court for American Samoa)
    'd-hi'  => [ 'name' => 'U.S. District Court for the District of Hawaii',           'short' => 'D. Haw.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'hi', 'as' ], 'circuit' => '9', 'level' => 3 ],
    // Idaho
    'd-id'  => [ 'name' => 'U.S. District Court for the District of Idaho',            'short' => 'D. Idaho',    'type' => 'federal_district', 'ws_jx_codes' => [ 'id' ], 'circuit' => '9',  'level' => 3 ],
    // Illinois
    'nd-il' => [ 'name' => 'U.S. District Court for the Northern District of Illinois', 'short' => 'N.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'il' ], 'circuit' => '7',  'level' => 3 ],
    'cd-il' => [ 'name' => 'U.S. District Court for the Central District of Illinois',  'short' => 'C.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'il' ], 'circuit' => '7',  'level' => 3 ],
    'sd-il' => [ 'name' => 'U.S. District Court for the Southern District of Illinois', 'short' => 'S.D. Ill.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'il' ], 'circuit' => '7',  'level' => 3 ],
    // Indiana
    'nd-in' => [ 'name' => 'U.S. District Court for the Northern District of Indiana', 'short' => 'N.D. Ind.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'in' ], 'circuit' => '7',  'level' => 3 ],
    'sd-in' => [ 'name' => 'U.S. District Court for the Southern District of Indiana', 'short' => 'S.D. Ind.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'in' ], 'circuit' => '7',  'level' => 3 ],
    // Iowa
    'nd-ia' => [ 'name' => 'U.S. District Court for the Northern District of Iowa',    'short' => 'N.D. Iowa',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ia' ], 'circuit' => '8',  'level' => 3 ],
    'sd-ia' => [ 'name' => 'U.S. District Court for the Southern District of Iowa',    'short' => 'S.D. Iowa',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ia' ], 'circuit' => '8',  'level' => 3 ],
    // Kansas
    'd-ks'  => [ 'name' => 'U.S. District Court for the District of Kansas',           'short' => 'D. Kan.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'ks' ], 'circuit' => '10', 'level' => 3 ],
    // Kentucky
    'ed-ky' => [ 'name' => 'U.S. District Court for the Eastern District of Kentucky', 'short' => 'E.D. Ky.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ky' ], 'circuit' => '6',  'level' => 3 ],
    'wd-ky' => [ 'name' => 'U.S. District Court for the Western District of Kentucky', 'short' => 'W.D. Ky.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ky' ], 'circuit' => '6',  'level' => 3 ],
    // Louisiana
    'ed-la' => [ 'name' => 'U.S. District Court for the Eastern District of Louisiana', 'short' => 'E.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'la' ], 'circuit' => '5',  'level' => 3 ],
    'md-la' => [ 'name' => 'U.S. District Court for the Middle District of Louisiana',  'short' => 'M.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'la' ], 'circuit' => '5',  'level' => 3 ],
    'wd-la' => [ 'name' => 'U.S. District Court for the Western District of Louisiana', 'short' => 'W.D. La.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'la' ], 'circuit' => '5',  'level' => 3 ],
    // Maine
    'd-me'  => [ 'name' => 'U.S. District Court for the District of Maine',            'short' => 'D. Me.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'me' ], 'circuit' => '1',  'level' => 3 ],
    // Maryland
    'd-md'  => [ 'name' => 'U.S. District Court for the District of Maryland',         'short' => 'D. Md.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'md' ], 'circuit' => '4',  'level' => 3 ],
    // Massachusetts
    'd-ma'  => [ 'name' => 'U.S. District Court for the District of Massachusetts',    'short' => 'D. Mass.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'ma' ], 'circuit' => '1',  'level' => 3 ],
    // Michigan
    'ed-mi' => [ 'name' => 'U.S. District Court for the Eastern District of Michigan', 'short' => 'E.D. Mich.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'mi' ], 'circuit' => '6',  'level' => 3 ],
    'wd-mi' => [ 'name' => 'U.S. District Court for the Western District of Michigan', 'short' => 'W.D. Mich.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'mi' ], 'circuit' => '6',  'level' => 3 ],
    // Minnesota
    'd-mn'  => [ 'name' => 'U.S. District Court for the District of Minnesota',        'short' => 'D. Minn.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'mn' ], 'circuit' => '8',  'level' => 3 ],
    // Mississippi
    'nd-ms' => [ 'name' => 'U.S. District Court for the Northern District of Mississippi', 'short' => 'N.D. Miss.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ms' ], 'circuit' => '5', 'level' => 3 ],
    'sd-ms' => [ 'name' => 'U.S. District Court for the Southern District of Mississippi', 'short' => 'S.D. Miss.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ms' ], 'circuit' => '5', 'level' => 3 ],
    // Missouri
    'ed-mo' => [ 'name' => 'U.S. District Court for the Eastern District of Missouri', 'short' => 'E.D. Mo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'mo' ], 'circuit' => '8',  'level' => 3 ],
    'wd-mo' => [ 'name' => 'U.S. District Court for the Western District of Missouri', 'short' => 'W.D. Mo.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'mo' ], 'circuit' => '8',  'level' => 3 ],
    // Montana
    'd-mt'  => [ 'name' => 'U.S. District Court for the District of Montana',          'short' => 'D. Mont.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'mt' ], 'circuit' => '9',  'level' => 3 ],
    // Nebraska
    'd-ne'  => [ 'name' => 'U.S. District Court for the District of Nebraska',         'short' => 'D. Neb.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'ne' ], 'circuit' => '8',  'level' => 3 ],
    // Nevada
    'd-nv'  => [ 'name' => 'U.S. District Court for the District of Nevada',           'short' => 'D. Nev.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'nv' ], 'circuit' => '9',  'level' => 3 ],
    // New Hampshire
    'd-nh'  => [ 'name' => 'U.S. District Court for the District of New Hampshire',    'short' => 'D.N.H.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'nh' ], 'circuit' => '1',  'level' => 3 ],
    // New Jersey
    'd-nj'  => [ 'name' => 'U.S. District Court for the District of New Jersey',       'short' => 'D.N.J.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'nj' ], 'circuit' => '3',  'level' => 3 ],
    // New Mexico
    'd-nm'  => [ 'name' => 'U.S. District Court for the District of New Mexico',       'short' => 'D.N.M.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'nm' ], 'circuit' => '10', 'level' => 3 ],
    // New York
    'nd-ny' => [ 'name' => 'U.S. District Court for the Northern District of New York', 'short' => 'N.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ny' ], 'circuit' => '2',  'level' => 3 ],
    'ed-ny' => [ 'name' => 'U.S. District Court for the Eastern District of New York',  'short' => 'E.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ny' ], 'circuit' => '2',  'level' => 3 ],
    'sd-ny' => [ 'name' => 'U.S. District Court for the Southern District of New York', 'short' => 'S.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ny' ], 'circuit' => '2',  'level' => 3 ],
    'wd-ny' => [ 'name' => 'U.S. District Court for the Western District of New York',  'short' => 'W.D.N.Y.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'ny' ], 'circuit' => '2',  'level' => 3 ],
    // North Carolina
    'ed-nc' => [ 'name' => 'U.S. District Court for the Eastern District of North Carolina', 'short' => 'E.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'nc' ], 'circuit' => '4', 'level' => 3 ],
    'md-nc' => [ 'name' => 'U.S. District Court for the Middle District of North Carolina',  'short' => 'M.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'nc' ], 'circuit' => '4', 'level' => 3 ],
    'wd-nc' => [ 'name' => 'U.S. District Court for the Western District of North Carolina', 'short' => 'W.D.N.C.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'nc' ], 'circuit' => '4', 'level' => 3 ],
    // North Dakota
    'd-nd'  => [ 'name' => 'U.S. District Court for the District of North Dakota',     'short' => 'D.N.D.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'nd' ], 'circuit' => '8',  'level' => 3 ],
    // Northern Mariana Islands
    'd-mp'  => [ 'name' => 'U.S. District Court for the Northern Mariana Islands',     'short' => 'D.N. Mar. I.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'mp' ], 'circuit' => '9', 'level' => 3 ],
    // Ohio
    'nd-oh' => [ 'name' => 'U.S. District Court for the Northern District of Ohio',    'short' => 'N.D. Ohio',   'type' => 'federal_district', 'ws_jx_codes' => [ 'oh' ], 'circuit' => '6',  'level' => 3 ],
    'sd-oh' => [ 'name' => 'U.S. District Court for the Southern District of Ohio',    'short' => 'S.D. Ohio',   'type' => 'federal_district', 'ws_jx_codes' => [ 'oh' ], 'circuit' => '6',  'level' => 3 ],
    // Oklahoma
    'nd-ok' => [ 'name' => 'U.S. District Court for the Northern District of Oklahoma', 'short' => 'N.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ok' ], 'circuit' => '10', 'level' => 3 ],
    'ed-ok' => [ 'name' => 'U.S. District Court for the Eastern District of Oklahoma',  'short' => 'E.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ok' ], 'circuit' => '10', 'level' => 3 ],
    'wd-ok' => [ 'name' => 'U.S. District Court for the Western District of Oklahoma',  'short' => 'W.D. Okla.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'ok' ], 'circuit' => '10', 'level' => 3 ],
    // Oregon
    'd-or'  => [ 'name' => 'U.S. District Court for the District of Oregon',           'short' => 'D. Or.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'or' ], 'circuit' => '9',  'level' => 3 ],
    // Pennsylvania
    'ed-pa' => [ 'name' => 'U.S. District Court for the Eastern District of Pennsylvania', 'short' => 'E.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'pa' ], 'circuit' => '3', 'level' => 3 ],
    'md-pa' => [ 'name' => 'U.S. District Court for the Middle District of Pennsylvania',  'short' => 'M.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'pa' ], 'circuit' => '3', 'level' => 3 ],
    'wd-pa' => [ 'name' => 'U.S. District Court for the Western District of Pennsylvania', 'short' => 'W.D. Pa.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'pa' ], 'circuit' => '3', 'level' => 3 ],
    // Puerto Rico
    'd-pr'  => [ 'name' => 'U.S. District Court for the District of Puerto Rico',      'short' => 'D.P.R.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'pr' ], 'circuit' => '1',  'level' => 3 ],
    // Rhode Island
    'd-ri'  => [ 'name' => 'U.S. District Court for the District of Rhode Island',     'short' => 'D.R.I.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'ri' ], 'circuit' => '1',  'level' => 3 ],
    // South Carolina
    'd-sc'  => [ 'name' => 'U.S. District Court for the District of South Carolina',   'short' => 'D.S.C.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'sc' ], 'circuit' => '4',  'level' => 3 ],
    // South Dakota
    'd-sd'  => [ 'name' => 'U.S. District Court for the District of South Dakota',     'short' => 'D.S.D.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'sd' ], 'circuit' => '8',  'level' => 3 ],
    // Tennessee
    'ed-tn' => [ 'name' => 'U.S. District Court for the Eastern District of Tennessee', 'short' => 'E.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'tn' ], 'circuit' => '6',  'level' => 3 ],
    'md-tn' => [ 'name' => 'U.S. District Court for the Middle District of Tennessee',  'short' => 'M.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'tn' ], 'circuit' => '6',  'level' => 3 ],
    'wd-tn' => [ 'name' => 'U.S. District Court for the Western District of Tennessee', 'short' => 'W.D. Tenn.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'tn' ], 'circuit' => '6',  'level' => 3 ],
    // Texas
    'nd-tx' => [ 'name' => 'U.S. District Court for the Northern District of Texas',   'short' => 'N.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'tx' ], 'circuit' => '5',  'level' => 3 ],
    'ed-tx' => [ 'name' => 'U.S. District Court for the Eastern District of Texas',    'short' => 'E.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'tx' ], 'circuit' => '5',  'level' => 3 ],
    'sd-tx' => [ 'name' => 'U.S. District Court for the Southern District of Texas',   'short' => 'S.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'tx' ], 'circuit' => '5',  'level' => 3 ],
    'wd-tx' => [ 'name' => 'U.S. District Court for the Western District of Texas',    'short' => 'W.D. Tex.',   'type' => 'federal_district', 'ws_jx_codes' => [ 'tx' ], 'circuit' => '5',  'level' => 3 ],
    // Utah
    'd-ut'  => [ 'name' => 'U.S. District Court for the District of Utah',             'short' => 'D. Utah',     'type' => 'federal_district', 'ws_jx_codes' => [ 'ut' ], 'circuit' => '10', 'level' => 3 ],
    // Vermont
    'd-vt'  => [ 'name' => 'U.S. District Court for the District of Vermont',          'short' => 'D. Vt.',      'type' => 'federal_district', 'ws_jx_codes' => [ 'vt' ], 'circuit' => '2',  'level' => 3 ],
    // Virgin Islands
    'd-vi'  => [ 'name' => 'U.S. District Court for the District of the Virgin Islands', 'short' => 'D.V.I.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'vi' ], 'circuit' => '3',  'level' => 3 ],
    // Virginia
    'ed-va' => [ 'name' => 'U.S. District Court for the Eastern District of Virginia', 'short' => 'E.D. Va.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'va' ], 'circuit' => '4',  'level' => 3 ],
    'wd-va' => [ 'name' => 'U.S. District Court for the Western District of Virginia', 'short' => 'W.D. Va.',    'type' => 'federal_district', 'ws_jx_codes' => [ 'va' ], 'circuit' => '4',  'level' => 3 ],
    // Washington
    'ed-wa' => [ 'name' => 'U.S. District Court for the Eastern District of Washington', 'short' => 'E.D. Wash.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'wa' ], 'circuit' => '9', 'level' => 3 ],
    'wd-wa' => [ 'name' => 'U.S. District Court for the Western District of Washington', 'short' => 'W.D. Wash.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'wa' ], 'circuit' => '9', 'level' => 3 ],
    // West Virginia
    'nd-wv' => [ 'name' => 'U.S. District Court for the Northern District of West Virginia', 'short' => 'N.D.W. Va.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'wv' ], 'circuit' => '4', 'level' => 3 ],
    'sd-wv' => [ 'name' => 'U.S. District Court for the Southern District of West Virginia', 'short' => 'S.D.W. Va.', 'type' => 'federal_district', 'ws_jx_codes' => [ 'wv' ], 'circuit' => '4', 'level' => 3 ],
    // Wisconsin
    'ed-wi' => [ 'name' => 'U.S. District Court for the Eastern District of Wisconsin', 'short' => 'E.D. Wis.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'wi' ], 'circuit' => '7',  'level' => 3 ],
    'wd-wi' => [ 'name' => 'U.S. District Court for the Western District of Wisconsin', 'short' => 'W.D. Wis.',  'type' => 'federal_district', 'ws_jx_codes' => [ 'wi' ], 'circuit' => '7',  'level' => 3 ],
    // Wyoming
    'd-wy'  => [ 'name' => 'U.S. District Court for the District of Wyoming',          'short' => 'D. Wyo.',     'type' => 'federal_district', 'ws_jx_codes' => [ 'wy' ], 'circuit' => '10', 'level' => 3 ],

];


// ════════════════════════════════════════════════════════════════════════════
// Gate: ws_seeded_court_matrix
//
// Courts are not a CPT. $ws_court_matrix is the single source of truth —
// loaded into memory at runtime and consumed directly by:
//   - acf-jx-interpretations.php  (ws_interp_court select field choices)
//   - admin-interpretation-metabox.php  (court label resolution)
//
// No posts are created. No database rows need to exist for courts to work.
//
// The gate option serves two purposes:
//   1. Version tracking — increment the string (e.g. '1.0.1') to signal that
//      the matrix data changed, allowing any future initialization logic to
//      detect a stale state and act accordingly.
//   2. Consistency — all matrix files write a ws_seeded_* option so tooling
//      can confirm every matrix loaded successfully on a given install.
//
// If courts ever need front-end pages or per-court admin UI, a ws-court CPT
// can be added at that time. The ws_interp_court meta key already stores the
// matrix array key as a string, so no migration of existing data would be
// required — the CPT slug would simply match the stored key.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_court_matrix' ) !== '1.1.0' ) {
        update_option( 'ws_seeded_court_matrix', '1.1.0' );
    }
} );
