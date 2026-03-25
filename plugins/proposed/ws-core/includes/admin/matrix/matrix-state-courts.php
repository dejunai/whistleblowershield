<?php
/**
 * matrix-state-courts.php
 *
 * State and territory court registry for ws-core interpretation fields.
 *
 * STRUCTURE
 * ---------
 * Each entry:
 *   'name'        Full official court name
 *   'short'       Legal citation abbreviation
 *   'type'        state_supreme | state_appellate
 *   'ws_jx_codes' Array containing the single USPS jurisdiction code this
 *                 court serves. Used by ws_interp_auto_populate_affected_jx()
 *                 to resolve the affected ws_jurisdiction term on save.
 *   'circuit'     Always null for state courts
 *   'level'       1=supreme/highest  2=appellate
 *
 * SECTIONS
 * --------
 * 1. State & Territory Supreme Courts
 * 2. State Intermediate Appellate Courts
 *    Omitted (no intermediate tier): DE, ID, ME, MT, NH, ND, RI, SD, VT, WY
 *    PA has two entries: Commonwealth Court and Superior Court.
 *    TX/OK dual high courts each get two state_supreme entries.
 *
 * USAGE
 * -----
 * $ws_state_court_matrix is consumed by ws_interp_load_court_choices() in
 * acf-jx-interpretations.php:
 *   - Federal statute parent: $ws_court_matrix + $ws_state_court_matrix merged
 *     into a single court select. All federal + all state courts are candidates.
 *   - State statute parent: $ws_state_court_matrix only. Federal courts are
 *     excluded — they do not interpret state statutes.
 *
 * Keys must not collide with $ws_court_matrix keys. Naming convention:
 *   state supreme     — {state-abbr}-sup   (e.g. ca-sup, tx-sup)
 *   state appellate   — {state-abbr}-app   (e.g. ca-app, tx-app)
 *   exceptions noted inline (ny-app, ok-cca, tx-cca, dc-app, pa-cw, pa-sup-app, ny-appdiv)
 *
 * VERSION
 * -------
 * 3.7.0  Extracted from matrix-federal-courts.php sections 4–5. Separated to enable
 *         context-aware court select filtering: state statute parent = state courts only.
 *         All 50 state supreme courts and available intermediate appellate courts
 *         are predefined; the 'other' sentinel in $ws_court_matrix handles any
 *         remaining edge cases.
 */

defined( 'ABSPATH' ) || exit;

global $ws_state_court_matrix;

$ws_state_court_matrix = [

    // ── 1. State & Territory Supreme Courts ───────────────────────────────
    //
    // TX and OK each have dual high courts (civil + criminal) — two entries each.
    // NY: Court of Appeals is the highest state court.
    // MD: Supreme Court of Maryland (renamed from Court of Appeals in 2022).
    // ME/MA: Supreme Judicial Court.
    // WV: Supreme Court of Appeals.
    // DC: D.C. Court of Appeals is the highest local court.

    'al-sup'  => [ 'name' => 'Supreme Court of Alabama',                       'short' => 'Ala. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'al' ], 'circuit' => null, 'level' => 1 ],
    'ak-sup'  => [ 'name' => 'Alaska Supreme Court',                           'short' => 'Alaska Sup. Ct.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'ak' ], 'circuit' => null, 'level' => 1 ],
    'az-sup'  => [ 'name' => 'Arizona Supreme Court',                          'short' => 'Ariz. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'az' ], 'circuit' => null, 'level' => 1 ],
    'ar-sup'  => [ 'name' => 'Arkansas Supreme Court',                         'short' => 'Ark. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ar' ], 'circuit' => null, 'level' => 1 ],
    'ca-sup'  => [ 'name' => 'Supreme Court of California',                    'short' => 'Cal. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ca' ], 'circuit' => null, 'level' => 1 ],
    'co-sup'  => [ 'name' => 'Colorado Supreme Court',                         'short' => 'Colo. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'co' ], 'circuit' => null, 'level' => 1 ],
    'ct-sup'  => [ 'name' => 'Connecticut Supreme Court',                      'short' => 'Conn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'ct' ], 'circuit' => null, 'level' => 1 ],
    'de-sup'  => [ 'name' => 'Delaware Supreme Court',                         'short' => 'Del. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'de' ], 'circuit' => null, 'level' => 1 ],
    'fl-sup'  => [ 'name' => 'Florida Supreme Court',                          'short' => 'Fla. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'fl' ], 'circuit' => null, 'level' => 1 ],
    'ga-sup'  => [ 'name' => 'Supreme Court of Georgia',                       'short' => 'Ga. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'ga' ], 'circuit' => null, 'level' => 1 ],
    'hi-sup'  => [ 'name' => 'Hawaii Supreme Court',                           'short' => 'Haw. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'hi' ], 'circuit' => null, 'level' => 1 ],
    'id-sup'  => [ 'name' => 'Idaho Supreme Court',                            'short' => 'Idaho Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'id' ], 'circuit' => null, 'level' => 1 ],
    'il-sup'  => [ 'name' => 'Illinois Supreme Court',                         'short' => 'Ill. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'il' ], 'circuit' => null, 'level' => 1 ],
    'in-sup'  => [ 'name' => 'Indiana Supreme Court',                          'short' => 'Ind. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'in' ], 'circuit' => null, 'level' => 1 ],
    'ia-sup'  => [ 'name' => 'Iowa Supreme Court',                             'short' => 'Iowa Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ia' ], 'circuit' => null, 'level' => 1 ],
    'ks-sup'  => [ 'name' => 'Kansas Supreme Court',                           'short' => 'Kan. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ks' ], 'circuit' => null, 'level' => 1 ],
    'ky-sup'  => [ 'name' => 'Kentucky Supreme Court',                         'short' => 'Ky. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'ky' ], 'circuit' => null, 'level' => 1 ],
    'la-sup'  => [ 'name' => 'Louisiana Supreme Court',                        'short' => 'La. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'la' ], 'circuit' => null, 'level' => 1 ],
    'me-sup'  => [ 'name' => 'Maine Supreme Judicial Court',                   'short' => 'Me. Sup. Jud. Ct.',      'type' => 'state_supreme', 'ws_jx_codes' => [ 'me' ], 'circuit' => null, 'level' => 1 ],
    'md-sup'  => [ 'name' => 'Supreme Court of Maryland',                      'short' => 'Md. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'md' ], 'circuit' => null, 'level' => 1 ],
    'ma-sup'  => [ 'name' => 'Massachusetts Supreme Judicial Court',           'short' => 'Mass. Sup. Jud. Ct.',    'type' => 'state_supreme', 'ws_jx_codes' => [ 'ma' ], 'circuit' => null, 'level' => 1 ],
    'mi-sup'  => [ 'name' => 'Michigan Supreme Court',                         'short' => 'Mich. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'mi' ], 'circuit' => null, 'level' => 1 ],
    'mn-sup'  => [ 'name' => 'Minnesota Supreme Court',                        'short' => 'Minn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'mn' ], 'circuit' => null, 'level' => 1 ],
    'ms-sup'  => [ 'name' => 'Mississippi Supreme Court',                      'short' => 'Miss. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'ms' ], 'circuit' => null, 'level' => 1 ],
    'mo-sup'  => [ 'name' => 'Missouri Supreme Court',                         'short' => 'Mo. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'mo' ], 'circuit' => null, 'level' => 1 ],
    'mt-sup'  => [ 'name' => 'Montana Supreme Court',                          'short' => 'Mont. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'mt' ], 'circuit' => null, 'level' => 1 ],
    'ne-sup'  => [ 'name' => 'Nebraska Supreme Court',                         'short' => 'Neb. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ne' ], 'circuit' => null, 'level' => 1 ],
    'nv-sup'  => [ 'name' => 'Nevada Supreme Court',                           'short' => 'Nev. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nv' ], 'circuit' => null, 'level' => 1 ],
    'nh-sup'  => [ 'name' => 'New Hampshire Supreme Court',                    'short' => 'N.H. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nh' ], 'circuit' => null, 'level' => 1 ],
    'nj-sup'  => [ 'name' => 'New Jersey Supreme Court',                       'short' => 'N.J. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nj' ], 'circuit' => null, 'level' => 1 ],
    'nm-sup'  => [ 'name' => 'New Mexico Supreme Court',                       'short' => 'N.M. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nm' ], 'circuit' => null, 'level' => 1 ],
    'ny-app'  => [ 'name' => 'New York Court of Appeals',                      'short' => 'N.Y. Ct. App.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ny' ], 'circuit' => null, 'level' => 1 ],
    'nc-sup'  => [ 'name' => 'North Carolina Supreme Court',                   'short' => 'N.C. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nc' ], 'circuit' => null, 'level' => 1 ],
    'nd-sup'  => [ 'name' => 'North Dakota Supreme Court',                     'short' => 'N.D. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'nd' ], 'circuit' => null, 'level' => 1 ],
    'oh-sup'  => [ 'name' => 'Ohio Supreme Court',                             'short' => 'Ohio Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'oh' ], 'circuit' => null, 'level' => 1 ],
    'ok-sup'  => [ 'name' => 'Oklahoma Supreme Court',                         'short' => 'Okla. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'ok' ], 'circuit' => null, 'level' => 1 ],
    'ok-cca'  => [ 'name' => 'Oklahoma Court of Criminal Appeals',             'short' => 'Okla. Crim. App.',       'type' => 'state_supreme', 'ws_jx_codes' => [ 'ok' ], 'circuit' => null, 'level' => 1 ],
    'or-sup'  => [ 'name' => 'Oregon Supreme Court',                           'short' => 'Or. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'or' ], 'circuit' => null, 'level' => 1 ],
    'pa-sup'  => [ 'name' => 'Supreme Court of Pennsylvania',                  'short' => 'Pa. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'pa' ], 'circuit' => null, 'level' => 1 ],
    'ri-sup'  => [ 'name' => 'Rhode Island Supreme Court',                     'short' => 'R.I. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ri' ], 'circuit' => null, 'level' => 1 ],
    'sc-sup'  => [ 'name' => 'South Carolina Supreme Court',                   'short' => 'S.C. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'sc' ], 'circuit' => null, 'level' => 1 ],
    'sd-sup'  => [ 'name' => 'South Dakota Supreme Court',                     'short' => 'S.D. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'sd' ], 'circuit' => null, 'level' => 1 ],
    'tn-sup'  => [ 'name' => 'Tennessee Supreme Court',                        'short' => 'Tenn. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'tn' ], 'circuit' => null, 'level' => 1 ],
    'tx-sup'  => [ 'name' => 'Supreme Court of Texas',                         'short' => 'Tex. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'tx' ], 'circuit' => null, 'level' => 1 ],
    'tx-cca'  => [ 'name' => 'Texas Court of Criminal Appeals',                'short' => 'Tex. Crim. App.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'tx' ], 'circuit' => null, 'level' => 1 ],
    'ut-sup'  => [ 'name' => 'Utah Supreme Court',                             'short' => 'Utah Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'ut' ], 'circuit' => null, 'level' => 1 ],
    'vt-sup'  => [ 'name' => 'Vermont Supreme Court',                          'short' => 'Vt. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'vt' ], 'circuit' => null, 'level' => 1 ],
    'va-sup'  => [ 'name' => 'Supreme Court of Virginia',                      'short' => 'Va. Sup. Ct.',           'type' => 'state_supreme', 'ws_jx_codes' => [ 'va' ], 'circuit' => null, 'level' => 1 ],
    'wa-sup'  => [ 'name' => 'Washington Supreme Court',                       'short' => 'Wash. Sup. Ct.',         'type' => 'state_supreme', 'ws_jx_codes' => [ 'wa' ], 'circuit' => null, 'level' => 1 ],
    'wv-sup'  => [ 'name' => 'Supreme Court of Appeals of West Virginia',      'short' => 'W. Va. Sup. Ct.',        'type' => 'state_supreme', 'ws_jx_codes' => [ 'wv' ], 'circuit' => null, 'level' => 1 ],
    'wi-sup'  => [ 'name' => 'Wisconsin Supreme Court',                        'short' => 'Wis. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'wi' ], 'circuit' => null, 'level' => 1 ],
    'wy-sup'  => [ 'name' => 'Wyoming Supreme Court',                          'short' => 'Wyo. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'wy' ], 'circuit' => null, 'level' => 1 ],
    // D.C. and territories
    'dc-app'  => [ 'name' => 'District of Columbia Court of Appeals',          'short' => 'D.C. Ct. App.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'dc' ], 'circuit' => null, 'level' => 1 ],
    'pr-sup'  => [ 'name' => 'Supreme Court of Puerto Rico',                   'short' => 'P.R. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'pr' ], 'circuit' => null, 'level' => 1 ],
    'vi-sup'  => [ 'name' => 'Supreme Court of the Virgin Islands',            'short' => 'V.I. Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'vi' ], 'circuit' => null, 'level' => 1 ],
    'gu-sup'  => [ 'name' => 'Supreme Court of Guam',                          'short' => 'Guam Sup. Ct.',          'type' => 'state_supreme', 'ws_jx_codes' => [ 'gu' ], 'circuit' => null, 'level' => 1 ],
    'as-hct'  => [ 'name' => 'High Court of American Samoa',                   'short' => 'Am. Samoa H. Ct.',       'type' => 'state_supreme', 'ws_jx_codes' => [ 'as' ], 'circuit' => null, 'level' => 1 ],
    'mp-sup'  => [ 'name' => 'Supreme Court of the Northern Mariana Islands',  'short' => 'N. Mar. I. Sup. Ct.',    'type' => 'state_supreme', 'ws_jx_codes' => [ 'mp' ], 'circuit' => null, 'level' => 1 ],

    // ── 2. State Intermediate Appellate Courts ─────────────────────────────
    //
    // Omitted (no intermediate appellate tier): DE, ID, ME, MT, NH, ND, RI, SD, VT, WY
    // PA: two entries — Commonwealth Court (public/admin law) and Superior Court (general).
    // One entry per jurisdiction; specific division noted in the citation label.

    'al-app'     => [ 'name' => 'Alabama Court of Civil Appeals',                      'short' => 'Ala. Civ. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'al' ], 'circuit' => null, 'level' => 2 ],
    'ak-app'     => [ 'name' => 'Alaska Court of Appeals',                             'short' => 'Alaska Ct. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'ak' ], 'circuit' => null, 'level' => 2 ],
    'az-app'     => [ 'name' => 'Arizona Court of Appeals',                            'short' => 'Ariz. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'az' ], 'circuit' => null, 'level' => 2 ],
    'ar-app'     => [ 'name' => 'Arkansas Court of Appeals',                           'short' => 'Ark. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ar' ], 'circuit' => null, 'level' => 2 ],
    'ca-app'     => [ 'name' => 'California Court of Appeal',                          'short' => 'Cal. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ca' ], 'circuit' => null, 'level' => 2 ],
    'co-app'     => [ 'name' => 'Colorado Court of Appeals',                           'short' => 'Colo. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'co' ], 'circuit' => null, 'level' => 2 ],
    'ct-app'     => [ 'name' => 'Connecticut Appellate Court',                         'short' => 'Conn. App. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'ct' ], 'circuit' => null, 'level' => 2 ],
    'fl-app'     => [ 'name' => 'Florida District Courts of Appeal',                   'short' => 'Fla. Dist. Ct. App.',    'type' => 'state_appellate', 'ws_jx_codes' => [ 'fl' ], 'circuit' => null, 'level' => 2 ],
    'ga-app'     => [ 'name' => 'Georgia Court of Appeals',                            'short' => 'Ga. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'ga' ], 'circuit' => null, 'level' => 2 ],
    'hi-app'     => [ 'name' => 'Intermediate Court of Appeals of Hawaii',             'short' => 'Haw. ICA',               'type' => 'state_appellate', 'ws_jx_codes' => [ 'hi' ], 'circuit' => null, 'level' => 2 ],
    'il-app'     => [ 'name' => 'Illinois Appellate Court',                            'short' => 'Ill. App. Ct.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'il' ], 'circuit' => null, 'level' => 2 ],
    'in-app'     => [ 'name' => 'Indiana Court of Appeals',                            'short' => 'Ind. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'in' ], 'circuit' => null, 'level' => 2 ],
    'ia-app'     => [ 'name' => 'Iowa Court of Appeals',                               'short' => 'Iowa Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ia' ], 'circuit' => null, 'level' => 2 ],
    'ks-app'     => [ 'name' => 'Kansas Court of Appeals',                             'short' => 'Kan. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ks' ], 'circuit' => null, 'level' => 2 ],
    'ky-app'     => [ 'name' => 'Kentucky Court of Appeals',                           'short' => 'Ky. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'ky' ], 'circuit' => null, 'level' => 2 ],
    'la-app'     => [ 'name' => 'Louisiana Courts of Appeal',                          'short' => 'La. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'la' ], 'circuit' => null, 'level' => 2 ],
    'md-app'     => [ 'name' => 'Appellate Court of Maryland',                         'short' => 'Md. App. Ct.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'md' ], 'circuit' => null, 'level' => 2 ],
    'ma-app'     => [ 'name' => 'Massachusetts Appeals Court',                         'short' => 'Mass. App. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'ma' ], 'circuit' => null, 'level' => 2 ],
    'mi-app'     => [ 'name' => 'Michigan Court of Appeals',                           'short' => 'Mich. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'mi' ], 'circuit' => null, 'level' => 2 ],
    'mn-app'     => [ 'name' => 'Minnesota Court of Appeals',                          'short' => 'Minn. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'mn' ], 'circuit' => null, 'level' => 2 ],
    'ms-app'     => [ 'name' => 'Mississippi Court of Appeals',                        'short' => 'Miss. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'ms' ], 'circuit' => null, 'level' => 2 ],
    'mo-app'     => [ 'name' => 'Missouri Court of Appeals',                           'short' => 'Mo. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'mo' ], 'circuit' => null, 'level' => 2 ],
    'ne-app'     => [ 'name' => 'Nebraska Court of Appeals',                           'short' => 'Neb. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ne' ], 'circuit' => null, 'level' => 2 ],
    'nv-app'     => [ 'name' => 'Nevada Court of Appeals',                             'short' => 'Nev. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'nv' ], 'circuit' => null, 'level' => 2 ],
    'nj-app'     => [ 'name' => 'New Jersey Superior Court, Appellate Division',       'short' => 'N.J. Super. App. Div.',  'type' => 'state_appellate', 'ws_jx_codes' => [ 'nj' ], 'circuit' => null, 'level' => 2 ],
    'nm-app'     => [ 'name' => 'New Mexico Court of Appeals',                         'short' => 'N.M. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'nm' ], 'circuit' => null, 'level' => 2 ],
    'ny-appdiv'  => [ 'name' => 'New York Supreme Court, Appellate Division',          'short' => 'N.Y. App. Div.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'ny' ], 'circuit' => null, 'level' => 2 ],
    'nc-app'     => [ 'name' => 'North Carolina Court of Appeals',                     'short' => 'N.C. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'nc' ], 'circuit' => null, 'level' => 2 ],
    'oh-app'     => [ 'name' => 'Ohio Courts of Appeals',                              'short' => 'Ohio Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'oh' ], 'circuit' => null, 'level' => 2 ],
    'ok-app'     => [ 'name' => 'Oklahoma Court of Civil Appeals',                     'short' => 'Okla. Civ. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'ok' ], 'circuit' => null, 'level' => 2 ],
    'or-app'     => [ 'name' => 'Oregon Court of Appeals',                             'short' => 'Or. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'or' ], 'circuit' => null, 'level' => 2 ],
    'pa-cw'      => [ 'name' => 'Pennsylvania Commonwealth Court',                     'short' => 'Pa. Commw. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'pa' ], 'circuit' => null, 'level' => 2 ],
    'pa-sup-app' => [ 'name' => 'Pennsylvania Superior Court',                         'short' => 'Pa. Super. Ct.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'pa' ], 'circuit' => null, 'level' => 2 ],
    'sc-app'     => [ 'name' => 'South Carolina Court of Appeals',                     'short' => 'S.C. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'sc' ], 'circuit' => null, 'level' => 2 ],
    'tn-app'     => [ 'name' => 'Tennessee Court of Appeals',                          'short' => 'Tenn. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'tn' ], 'circuit' => null, 'level' => 2 ],
    'tx-app'     => [ 'name' => 'Texas Courts of Appeals',                             'short' => 'Tex. App.',              'type' => 'state_appellate', 'ws_jx_codes' => [ 'tx' ], 'circuit' => null, 'level' => 2 ],
    'ut-app'     => [ 'name' => 'Utah Court of Appeals',                               'short' => 'Utah Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'ut' ], 'circuit' => null, 'level' => 2 ],
    'va-app'     => [ 'name' => 'Virginia Court of Appeals',                           'short' => 'Va. Ct. App.',           'type' => 'state_appellate', 'ws_jx_codes' => [ 'va' ], 'circuit' => null, 'level' => 2 ],
    'wa-app'     => [ 'name' => 'Washington Court of Appeals',                         'short' => 'Wash. Ct. App.',         'type' => 'state_appellate', 'ws_jx_codes' => [ 'wa' ], 'circuit' => null, 'level' => 2 ],
    'wv-app'     => [ 'name' => 'West Virginia Intermediate Court of Appeals',         'short' => 'W. Va. Ct. App.',        'type' => 'state_appellate', 'ws_jx_codes' => [ 'wv' ], 'circuit' => null, 'level' => 2 ],
    'wi-app'     => [ 'name' => 'Wisconsin Court of Appeals',                          'short' => 'Wis. Ct. App.',          'type' => 'state_appellate', 'ws_jx_codes' => [ 'wi' ], 'circuit' => null, 'level' => 2 ],

    // ── Other (edge case — court not in matrix) ───────────────────────────
    //
    // Selecting this entry reveals ws_jx_interp_court_name (free text field).
    // ws_jx_codes = '__manual__' signals the save hook to skip auto-population
    // of ws_jx_interp_affected_jx — the editor must set scope manually.
    // level = 99 ensures this entry sorts last in the select list.
    //
    // Defined in both matrices so 'other' is available regardless of whether
    // the parent statute is federal or state. In the merged federal select,
    // the state matrix entry overwrites the federal one — both are identical.

    'other' => [
        'name'        => 'Other (specify below)',
        'short'       => 'Other',
        'type'        => 'other',
        'ws_jx_codes' => '__manual__',
        'circuit'     => null,
        'level'       => 99,
    ],

];


// ════════════════════════════════════════════════════════════════════════════
// Gate: ws_seeded_state_court_matrix
//
// $ws_state_court_matrix is loaded at runtime — no posts or terms are created.
// The gate option tracks the matrix version for tooling consistency.
// Increment the string to signal a data change (e.g., new court added).
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_state_court_matrix' ) !== '1.0.0' ) {
        update_option( 'ws_seeded_state_court_matrix', '1.0.0' );
    }
} );
