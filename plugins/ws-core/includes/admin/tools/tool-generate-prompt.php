<?php
/**
 * tool-generate-prompt.php
 *
 * WhistleblowerShield Core Plugin — Admin Tool
 *
 * PURPOSE
 * -------
 * Generates AI research prompt templates for the WhistleblowerShield
 * ingest pipeline. Reads taxonomy data directly from register-taxonomies.php
 * at runtime so taxonomy tables are always in sync with the PHP source of
 * truth. Outputs a ready-to-paste .txt file to the logs/ws-prompts/
 * directory for FTP retrieval.
 *
 * RECORD TYPES SUPPORTED
 * ----------------------
 * - statute       Full taxonomy palette, SOL/exhaustion/BOP rules
 * - common-law    Doctrine-anchored, ws_cl_* fields, statutory preclusion
 * - citation      Case law enrichment, court shorthand, sparse taxonomy
 * - interpretation Court ruling on statute, court matrix context
 *
 * OUTPUT
 * ------
 * Files written to: WP_CONTENT_DIR/logs/ws-prompts/
 * Filename format:  [JX_ID]-[record_type]-[YYYYMMDD-HHmm].txt
 *
 * ACCESS
 * ------
 * Admin only. Registered under the WhistleblowerShield tools menu.
 *
 * @package    WhistleblowerShield
 * @since      3.13.0
 * @version    3.13.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.13.0  Initial release. Generates statute, common-law, citation,
 *         and interpretation prompts from live taxonomy data.
 */

defined( 'ABSPATH' ) || exit;

// ── Admin menu registration ───────────────────────────────────────────────

add_action( 'admin_menu', 'ws_register_prompt_generator_page' );

function ws_register_prompt_generator_page() {
    add_submenu_page(
        'tools.php',
        'WS Prompt Generator',
        'WS Prompt Generator',
        'manage_options',
        'ws-prompt-generator',
        'ws_render_prompt_generator_page'
    );
}


// ── Output directory helper ───────────────────────────────────────────────

function ws_prompt_output_dir(): string {
    $dir = WP_CONTENT_DIR . '/logs/ws-prompts';
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
        file_put_contents( $dir . '/.htaccess', "Deny from all\n" );
    }
    return $dir;
}


// ── Taxonomy data — read from WordPress database ─────────────────────────
//
// All taxonomy helpers read live from WordPress via get_terms().
// This ensures approved proposed terms surface automatically without
// requiring a PHP sync pass. The database is the source of truth at
// runtime — register-taxonomies.php seeds it, but these functions
// read whatever is actually registered and approved.

/**
 * Reads a hierarchical taxonomy from WordPress and returns a nested array
 * suitable for ws_prompt_render_hierarchical_table().
 *
 * Structure: [ parent_slug => [ 'label' => 'Parent Label (parent)', 'children' => [ slug => label ] ] ]
 * The has-details sentinel is excluded from the hierarchy and returned
 * separately so the table renderer can append it at the end.
 *
 * @param string $taxonomy  The taxonomy slug to read.
 * @return array            [ 'hierarchy' => [...], 'has_sentinel' => bool ]
 */
function ws_prompt_read_hierarchical_taxonomy( string $taxonomy ): array {
    $result = [ 'hierarchy' => [], 'has_sentinel' => false ];

    // Get all top-level terms
    $parents = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'parent'     => 0,
        'orderby'    => 'term_id',
        'order'      => 'ASC',
    ] );

    if ( is_wp_error( $parents ) || empty( $parents ) ) {
        return $result;
    }

    foreach ( $parents as $parent ) {
        if ( $parent->slug === 'has-details' ) {
            $result['has_sentinel'] = true;
            continue;
        }

        $children_terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => $parent->term_id,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
        ] );

        $children = [];
        if ( ! is_wp_error( $children_terms ) ) {
            foreach ( $children_terms as $child ) {
                $children[ $child->slug ] = html_entity_decode( $child->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            }
        }

        $result['hierarchy'][ $parent->slug ] = [
            'label'    => html_entity_decode( $parent->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ' (parent)',
            'children' => $children,
        ];
    }

    return $result;
}

/**
 * Reads a flat taxonomy from WordPress and returns a slug => name array.
 * has-details sentinel is kept in place at the end of the list.
 *
 * @param string $taxonomy  The taxonomy slug to read.
 * @return array            [ slug => label ]
 */
function ws_prompt_read_flat_taxonomy( string $taxonomy ): array {
    $terms = get_terms( [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'orderby'    => 'term_id',
        'order'      => 'ASC',
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    $result = [];
    $sentinel = null;
    foreach ( $terms as $term ) {
        if ( $term->slug === 'has-details' ) {
            $sentinel = $term;
            continue;
        }
        $result[ $term->slug ] = html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }
    // Append sentinel last
    if ( $sentinel ) {
        $result['has-details'] = 'Has Details (sentinel — use with {taxonomy}_details)';
    }

    return $result;
}


// ── Taxonomy table renderers ──────────────────────────────────────────────

function ws_prompt_render_hierarchical_table( string $slug, string $label, string $applies_to, string $description, array $hierarchy, bool $has_sentinel = false ): string {
    $pad = 35;
    $out  = str_repeat( '─', 76 ) . "\n";
    $out .= "TAXONOMY: {$slug}\n";
    $out .= "Applies to: {$applies_to}\n";
    $out .= "Hierarchical: YES — use child slugs only\n";
    $out .= "Description: {$description}\n";
    $out .= str_repeat( '─', 76 ) . "\n\n";

    foreach ( $hierarchy as $parent_slug => $data ) {
        $out .= "--- {$parent_slug} ---\n";
        $out .= str_pad( $parent_slug, $pad ) . $data['label'] . "\n";
        foreach ( $data['children'] as $child_slug => $child_label ) {
            $out .= str_pad( $child_slug, $pad ) . $child_label . "\n";
        }
        $out .= "\n";
    }

    if ( $has_sentinel ) {
        $out .= str_pad( 'has-details', $pad ) . "Has Details (sentinel — use with {$slug}_details)\n";
        $out .= "\n";
    }

    return $out;
}

function ws_prompt_render_flat_table( string $slug, string $label, string $applies_to, string $description, array $terms ): string {
    $pad = 35;
    $out  = str_repeat( '─', 76 ) . "\n";
    $out .= "TAXONOMY: {$slug}\n";
    $out .= "Applies to: {$applies_to}\n";
    $out .= "Hierarchical: NO — flat list\n";
    $out .= "Description: {$description}\n";
    $out .= str_repeat( '─', 76 ) . "\n\n";

    foreach ( $terms as $term_slug => $term_label ) {
        $out .= str_pad( $term_slug, $pad ) . $term_label . "\n";
    }
    $out .= "\n";

    return $out;
}


// ── Parent slug self-check list (generated from live taxonomy data) ──────

function ws_prompt_get_parent_slugs(): array {
    $parents = [];
    foreach ( [ 'ws_disclosure_type', 'ws_protected_class', 'ws_disclosure_targets' ] as $taxonomy ) {
        $top_level = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0,
            'fields'     => 'slugs',
        ] );
        if ( ! is_wp_error( $top_level ) ) {
            foreach ( $top_level as $slug ) {
                if ( $slug !== 'has-details' ) {
                    $parents[] = $slug;
                }
            }
        }
    }
    return $parents;
}


// ── Court shorthand reference ─────────────────────────────────────────────

function ws_prompt_get_court_shorthand(): string {
    return <<<TEXT
COURT SHORTHAND — use these identifiers exactly. Do not invent abbreviations.

  SCOTUS              U.S. Supreme Court
  CA-[#]              Federal Circuit Court of Appeals (e.g. CA-3, CA-9)
  USDC-[DIST]         Federal District Court (e.g. USDC-DNJ, USDC-CDCA)
  [STATE]-SUP         State Supreme Court (e.g. NJ-SUP, CA-SUP)
  [STATE]-APP         State Appellate Court — general (e.g. NJ-APP, CA-APP)
  [STATE]-APP-[DIV]   State Appellate Division (e.g. NJ-APP-2, CA-APP-4)
  [STATE]-TAX         State Tax Court
  OTHER               Court not in this list — describe in _review_notes

TEXT;
}


// ── Static rule blocks ────────────────────────────────────────────────────

function ws_prompt_statute_rules(): string {
    return <<<'RULES'
---

STATUTE OF LIMITATIONS

Many whistleblower statutes do not specify a filing deadline in their own
text. The applicable SOL is often derived from a general civil procedure
statute rather than the whistleblower law itself. This distinction matters.

Before setting limit_ambiguous to false, verify the SOL value directly
against the statute text at the provided legislature URL. If the value is
stated explicitly in the statute, set limit_ambiguous to false. If you are
deriving it from a general civil procedure statute or secondary source,
set limit_ambiguous to true regardless of your confidence in the derived
value.

Any record with limit_ambiguous: true requires a corresponding entry in
error_details in the integrity block. This is mandatory.

---

EXHAUSTION RULE

exhaustion_required must be true whenever a mandatory administrative filing
step is required before civil court access, even if de novo review becomes
available after a waiting period. Populate exhaustion_details with the
specific procedural requirement and deadline.

Written notice requirements that do not block court access are NOT
exhaustion — do not set exhaustion_required true for these. Note them
in _review_notes instead.

---

BURDEN OF PROOF

burden_of_proof_flag is a short signal phrase identifying the non-standard
burden shift — not a full sentence, not a boolean. Examples:
  "AIR21 burden-shifting framework"
  "90-day rebuttable presumption"
  "contributing-factor-shift"

Use burden_of_proof_details for narrative explanation. Omit entirely
unless a meaningful or non-standard burden shift is identified.

employee_standard (reasonable-belief): describes the threshold for what
qualifies as a protected disclosure — it is not a causation standard. Use
it only when the statute explicitly names reasonable belief as a separate
element. Do not use as a substitute for contributing-factor.

---
RULES;
}

function ws_prompt_citation_rules(): string {
    return <<<'RULES'
---

APPROVED SOURCES

Always attempt sources in this order. Use the first source that yields a
trustworthy URL and stop.

STATUTE SOURCES:
  1. The official legislature URL for this jurisdiction (provided in RUN SCOPE)
  2. uscode.house.gov — federal statutes
  3. congress.gov — federal statutes
  4. legiscan.com — acceptable secondary
  5. law.justia.com — acceptable secondary

CASE LAW SOURCES:
  1. Official court websites (supremecourt.gov, ca9.uscourts.gov, etc.)
  2. courtlistener.com — PACER-sourced, highly reliable
  3. casetext.com — strong coverage, stable URLs
  4. law.justia.com — broad coverage

FORBIDDEN: scholar.google.com (unstable URLs), law firm websites, any
non-institutional aggregator not listed above.

If no verifiable URL exists from the approved list, omit the citation
entirely. Do not substitute a different URL.

---

CITATIONS

Citation format:
  "CASE NAME v. CASE NAME || SPECIFIC_IMPACT || URL || SOURCE || QUALITY"

SPECIFIC_IMPACT: 3-8 words, action-verb first, describing the functional
legal impact of this ruling. Use one of these patterns:
  "defines [legal concept]"       "clarifies [legal standard]"
  "establishes [rule/test]"       "applies [statute/standard]"
  "limits [scope/protection]"     "expands [scope/protection]"
  "interprets [term/phrase]"      "confirms [legal principle]"
  "rejects [legal argument]"      "resolves [conflict/ambiguity]"

QUALITY values:
  high     — appellate or supreme court; frequently cited
  moderate — appellate but narrower scope or less cited
  low      — trial-level or limited precedential value

Prioritize appellate and supreme court decisions.

---

RULES;
}

function ws_prompt_omission_rules(): string {
    return <<<'RULES'
---

OMISSION

If you cannot find a value with reasonable confidence, omit the key entirely
or leave it as "" or [] depending on the field type. Do not use null, "N/A",
"unknown", or any placeholder string to signal a missing value.

The following fields are routinely empty in a well-produced run and their
absence is never penalized:
  - tolling_notes
  - exhaustion_details
  - rebuttable_presumption
  - burden_of_proof_details
  - reward_details
  - statute_url
  - attached_citations
  - _review_notes
  - _reconciled_notes   → must exist, must be empty, used by second pass legal fact checker

The following fields must be omitted entirely when empty — do not include
them as empty arrays [] or empty strings "":
  - enforcement.fee_shifting
  - enforcement.process_type
  - enforcement.adverse_action
  - enforcement.remedies
  - legal_basis.disclosure_types
  - legal_basis.protected_class
  - legal_basis.disclosure_targets
  - burden_of_proof.employee_standard
  - burden_of_proof.employer_defense
  - citations.attached_citations
  - common_name
  - enforcement.primary_agency
  - burden_of_proof.burden_of_proof_flag
  - statute_of_limitations.trigger
  - statute_of_limitations.limit_details
  - statute_of_limitations.exhaustion_details
  - statute_of_limitations.tolling_notes
  - reward.reward_details
  - links.url_source

An honest incomplete record can be enriched over time.
A confidently wrong record causes harm and cannot be trusted.

---

RULES;
}


// ── Taxonomy tables block (shared across record types) ────────────────────

function ws_prompt_taxonomy_tables( string $applies_to ): string {
    $out  = str_repeat( '=', 80 ) . "\n";
    $out .= "TAXONOMY TABLES\n";
    $out .= "Notes: Full doctrinal palette. Use child slugs only for hierarchical\n";
    $out .= "       taxonomies. Tag only what is genuinely supported by the source\n";
    $out .= "       material. Many axes will be sparsely used on any single record.\n";
    $out .= str_repeat( '=', 80 ) . "\n\n\n";

    // Hierarchical taxonomies
    $hierarchical = [
        'ws_disclosure_type' => [
            'label'       => 'Disclosure Categories',
            'description' => "Subject matter of the protected disclosure. Use all that apply.",
            'sentinel'    => false,
        ],
        'ws_protected_class' => [
            'label'       => 'Protected Class',
            'description' => "Employment or worker classification protected. Tag all explicitly covered.\n             Do not infer coverage.",
            'sentinel'    => true,
        ],
        'ws_disclosure_targets' => [
            'label'       => 'Disclosure Targets',
            'description' => "Who the protected disclosure may be made to. Tag all valid targets\n             explicitly named or clearly implied.",
            'sentinel'    => true,
        ],
    ];

    foreach ( $hierarchical as $slug => $config ) {
        $data = ws_prompt_read_hierarchical_taxonomy( $slug );
        $out .= ws_prompt_render_hierarchical_table(
            $slug, $config['label'], $applies_to,
            $config['description'],
            $data['hierarchy'],
            $data['has_sentinel']
        );
    }

    // Flat taxonomies
    $flat = [
        'ws_adverse_action_types' => [
            'label'       => 'Adverse Action Types',
            'description' => "Retaliatory actions explicitly or broadly prohibited. Tag all covered;\n             do not tag actions merely implied.",
        ],
        'ws_process_type' => [
            'label'       => 'Process Types',
            'description' => "Procedural route available. Tag all that apply.",
        ],
        'ws_remedies' => [
            'label'       => 'Available Remedies',
            'description' => "Remedies available to a prevailing claimant. Tag all explicitly\n             available; do not infer from general principles.",
        ],
        'ws_fee_shifting' => [
            'label'       => 'Fee Shifting',
            'description' => "Fee-shifting posture. Single-value taxonomy; use the most accurate term.\n             prevailing-party = either side recovers.\n             unilateral-pro-plaintiff = only a successful plaintiff recovers.",
        ],
        'ws_employer_defense' => [
            'label'       => 'Employer Defense',
            'description' => "Affirmative defenses available to an employer. Tag all explicitly\n             recognized under the statute.",
        ],
        'ws_employee_standard' => [
            'label'       => 'Employee Standard',
            'description' => "Burden-of-proof standard the employee must meet. Tag all that explicitly\n             apply. Omit entirely if no standard is named — do not infer.",
        ],
    ];

    foreach ( $flat as $slug => $config ) {
        $terms = ws_prompt_read_flat_taxonomy( $slug );
        // Fix sentinel label to reference correct companion field
        if ( isset( $terms['has-details'] ) ) {
            $field_map = [
                'ws_adverse_action_types' => 'adverse_action_details',
                'ws_remedies'             => 'remedies_details',
                'ws_employer_defense'     => 'employer_defense_details',
                'ws_employee_standard'    => 'employee_standard_details',
            ];
            $companion = $field_map[ $slug ] ?? "{$slug}_details";
            $terms['has-details'] = "Has Details (sentinel — use with {$companion})";
        }
        $out .= ws_prompt_render_flat_table(
            $slug, $config['label'], $applies_to,
            $config['description'],
            $terms
        );
    }

    return $out;
}


// ── Parent slug self-check block ──────────────────────────────────────────

function ws_prompt_parent_slug_block(): string {
    $parents = ws_prompt_get_parent_slugs();
    $list    = implode( ', ', array_map( fn($s) => "\"{$s}\"", $parents ) );

    return <<<BLOCK
---

PARENT SLUGS — CRITICAL RULE

Parent slugs are structural labels only. They are never valid record values
and must never appear in any record array.

SELF-CHECK REQUIRED: Before writing your final JSON, scan every taxonomy
array in every record. If you find any parent slug — including but not
limited to {$list} — you must:

  1. Delete it from the array immediately
  2. Leave the array empty if no valid child slug applies
  3. Note the removed slug in json_run_notes
  4. Set with_errors: true in the integrity block
  5. Add to error_details: "[STATUTE_ID]: Removed parent slug [SLUG] from
     [FIELD] — no matching child slug found"

A parent slug in a record array corrupts the database. This self-check
is mandatory on every batch.

---

BLOCK;
}


// ── Taxonomy proposal block ───────────────────────────────────────────────

function ws_prompt_proposal_block(): string {
    return <<<'BLOCK'
---
PROPOSING NEW TAXONOMY TERMS

When you encounter a concept that does not fit any slug in the known taxonomy,
propose it. Proposals are expected and valued at every stage of this pipeline.

A proposal that does not become a registered taxonomy term is not discarded.
It enters a human review queue where it may serve as an edge-case signal —
a last-resort reference for a user whose situation does not fit any existing
term. The person using this site may have nowhere else to turn. A concept
you surface here, even once, even in a single statute, could be the most
useful thing in this entire batch for that person.

Propose it.

Before proposing, consider two things — not as gates, but as guidance:

  1. Is this concept likely to appear in other statutes across other
     jurisdictions? If yes, it is a strong candidate. If it feels entirely
     specific to one statute in one jurisdiction, note it in json_run_notes
     as well so the human reviewer has full context.

  2. Can this concept be accurately represented by combining three or fewer
     existing child slugs across the relevant taxonomy fields? If yes,
     use that combination in the record — and also propose the term.
     A workaround combination and a clean proposal are not mutually exclusive.

Do not propose new taxonomy tables or new parent terms. Use json_run_notes
to recommend them

  {
    "taxonomy":   "[REGISTERED TAXONOMY TABLE SLUG]",
    "term_id":    "[YOUR PROPOSED SLUG IN kebab-case]",
    "term_label": "[HUMAN-READABLE LABEL]",
    "notes":      "[WHY THIS TERM IS NEEDED AND WHY EXISTING TERMS DO NOT COVER IT]",
    "seen_in":    ["[RECORD_ID]"],
    "count":      [INTEGER — must equal length of seen_in]
  }

Do not insert a proposed term_id into any record array. Proposals live in
new_terms_proposed only. If no new terms are needed, new_terms_proposed
must be an empty array [].

---

BLOCK;
}


// ── Integrity block ───────────────────────────────────────────────────────

function ws_prompt_integrity_block(): string {
    return <<<'BLOCK'

INTEGRITY BLOCK

The integrity block is your honest self-report on the state of this batch.
Reporting errors here is not a failure — it is the most valuable contribution
you can make to the reliability of this platform.

{
  "integrity": {
    "with_errors":   [true | false],
    "error_details": ["[SPECIFIC ERROR WITH DETAILS]"],
    "error_count":   [INTEGER — must equal length of error_details]
  }
}

with_errors must be true if ANY of the following occurred:
  - record_count is less than records_requested
  - A statute could not be researched with sufficient confidence
  - Any schema rule was knowingly violated
  - A citation was omitted because a URL could not be verified
  - A SOL value was derived rather than explicit (limit_ambiguous: true)
  - A parent slug was detected and removed during self-check
  - Anything a human reviewer should know about this batch

OMISSION RULE: If with_errors is false, omit error_details and error_count
entirely. The ingest tool treats a missing key differently from an empty array.

---

BLOCK;
}


// ── Record schemas ────────────────────────────────────────────────────────

function ws_prompt_statute_schema(): string {
    return <<<'ENDSCHEMA'

RECORD SCHEMA

{
  "jurisdiction_id":  "[TWO-LETTER CODE]",
  "statute_id":       "[JURISDICTION_ID-SECTION e.g. CA-1102.5]",
  "official_name":    "[FULL OFFICIAL STATUTE NAME]",
  "common_name":      "[PLAIN LANGUAGE COMMON NAME — omit if none exists]",

  "legal_basis": {
    "statute_citation":           "[FORMAL CITATION e.g. Cal. Lab. Code § 1102.5]",
    "disclosure_types":           [],
    "protected_class":            [],
    "protected_class_details":    "[FREE TEXT — omit unless protected_class uses has-details]",
    "disclosure_targets":         [],
    "disclosure_targets_details": "[FREE TEXT — omit unless disclosure_targets uses has-details]",
    "adverse_action_scope":       "[FREE TEXT — scope of covered adverse actions]"
  },

  "statute_of_limitations": {
    "limit_ambiguous":     false,
    "limit_value":         0,
    "limit_unit":          "[days | months | years]",
    "limit_details":       "[omit if limit_ambiguous is false]",
    "trigger":             "[omit if unknown]",
    "exhaustion_required": false,
    "exhaustion_details":  "[omit if exhaustion_required is false]",
    "tolling_notes":       "[omit if none identified]"
  },

  "enforcement": {
    "primary_agency": "[omit if unknown]",
    "process_type":   [],
    "adverse_action": [],
    "adverse_action_details": "[FREE TEXT — omit unless adverse_action uses has-details]",
    "remedies":       [],
    "remedies_details": "[FREE TEXT — omit unless remedies uses has-details]",
    "fee_shifting":   "[omit if empty]"
  },

  "burden_of_proof": {
    "employee_standard":         [],
    "employee_standard_details": "[FREE TEXT — omit unless employee_standard uses has-details]",
    "employer_defense":          [],
    "employer_defense_details":  "[FREE TEXT — omit unless employer_defense uses has-details]",
    "rebuttable_presumption":    "[omit if none identified]",
    "burden_of_proof_details":   "[omit if none]",
    "burden_of_proof_flag":      "[omit unless a meaningful burden shift is identified]"
  },

  "reward": {
    "available":      false,
    "reward_details": "[omit if available is false]"
  },

  "links": {
    "statute_url": "[omit if no approved source identified]",
    "is_official": false,
    "url_source":  "[domain name — omit if is_official is true or no URL]",
    "is_pdf":      "[omit if false]"
  },

  "citations": {
    "attached_citations": [],
    "citation_count":     0
  },

  "_review_notes":      "",
  "_reconciled_notes":  "[Field must be added, it must exist — leave this key empty. Research models must not populate this field.]"
}

---

SCHEMA NOTES

statute_id: Use [JURISDICTION_ID-SECTION] only. Use the chapter entry-point
section, not mid-chapter provisions. Do not include code prefixes (LAB, GOV).
Do not invent cluster IDs.

limit_ambiguous: Set to true whenever the SOL is derived from a general civil
procedure statute, secondary source, or case law — regardless of confidence.
A zero limit_value with limit_ambiguous false implies the deadline is
verifiably zero; use this only when literally correct.

remedies: If a statute refers to "actual damages" map to compensatory-damages
and note in _review_notes. If "special damages" map to consequential-damages
and note in _review_notes.

_reconciled_notes key must exist, and must be empty "", it is used by
the second pass legal fact checker who reconciles the records objects.

CALCULATED FIELDS — compute last, after all records are finalized:
  meta.record_count         — must equal length of records array
  meta.proposed_count       — must equal length of new_terms_proposed
  citations.citation_count  — must equal length of attached_citations
  integrity.error_count     — must equal length of error_details

ENDSCHEMA;
}

function ws_prompt_common_law_schema(): string {
    return <<<'ENDSCHEMA'

RECORD SCHEMA

{
  "jurisdiction_id": "[TWO-LETTER CODE]",
  "doctrine_id":     "[JX]-CL-[SHORT-SLUG e.g. WY-CL-PUBLIC-POLICY]",
  "doctrine_name":   "[FORMAL DOCTRINE NAME]",
  "common_name":     "[SHORTHAND NAME — omit if none widely used]",

  "legal_basis": {
    "doctrine_basis":             "[WYSIWYG — legal principle, leading cases, how protection works]",
    "recognition_status":         "[WYSIWYG — current judicial status, well-established vs contested]",
    "public_policy_sources":      ["[constitution | statute | administrative-rule | case-law | federal-law | other]"],
    "other_sources":              "[omit unless 'other' is in public_policy_sources]",
    "precedent_url":              "[URL to leading case on approved source — omit if unverifiable]",
    "disclosure_types":           [],
    "protected_class":            [],
    "protected_class_details":    "[FREE TEXT — omit unless protected_class uses has-details]",
    "disclosure_targets":         [],
    "disclosure_targets_details": "[FREE TEXT — omit unless disclosure_targets uses has-details]",
    "adverse_action_scope":       "[FREE TEXT — scope of covered adverse actions]"
  },

  "statute_of_limitations": {
    "limit_ambiguous":     true,
    "limit_value":         0,
    "limit_unit":          "[days | months | years]",
    "limit_details":       "[required — identify the analogous statute the period is borrowed from]",
    "trigger":             "[omit if unknown]",
    "exhaustion_required": false,
    "exhaustion_details":  "[omit if exhaustion_required is false]",
    "tolling_notes":       "[omit if none identified]"
  },

  "enforcement": {
    "primary_agency": "[omit if unknown]",
    "process_type":   [],
    "adverse_action": [],
    "adverse_action_details": "[FREE TEXT — omit unless adverse_action uses has-details]",
    "remedies":       [],
    "remedies_details": "[FREE TEXT — omit unless remedies uses has-details]",
    "fee_shifting":   "[omit if empty]"
  },

  "burden_of_proof": {
    "statutory_preclusion":         false,
    "statutory_preclusion_details": "[omit if statutory_preclusion is false]",
    "employee_standard":            [],
    "employee_standard_details":    "[FREE TEXT — omit unless employee_standard uses has-details]",
    "employer_defense":             [],
    "employer_defense_details":     "[FREE TEXT — omit unless employer_defense uses has-details]",
    "rebuttable_presumption":       "[omit if none identified]",
    "burden_of_proof_details":      "[omit if none]",
    "burden_of_proof_flag":         "[omit unless a meaningful burden shift is identified]"
  },

  "reward": {
    "available":      false,
    "reward_details": "[omit if available is false]"
  },

  "links": {
    "precedent_url": "[omit if no approved source identified]",
    "is_official":   false,
    "url_source":    "[domain name — omit if is_official is true or no URL]"
  },

  "citations": {
    "attached_citations": [],
    "citation_count":     0
  },

  "_review_notes":     "",
  "_reconciled_notes": "[NOTEBOOKLM ONLY — leave this key empty. Research models must not populate this field.]"
}

---

SCHEMA NOTES

doctrine_id: Format [JX]-CL-[SHORT-SLUG] in kebab-case, max 4-5 words after
CL. Used in prompt exclusion lists to prevent duplicate records.

limit_ambiguous: Almost always true for common law — SOL is borrowed from the
nearest analogous statute. Document the source statute in limit_details.

statutory_preclusion: Set to true when this jurisdiction's courts hold that
the common law claim is unavailable when a statutory remedy for the same
conduct exists. Document the controlling cases in statutory_preclusion_details.

CALCULATED FIELDS — compute last:
  meta.record_count        — must equal length of records array
  meta.proposed_count      — must equal length of new_terms_proposed
  citations.citation_count — must equal length of attached_citations
  integrity.error_count    — must equal length of error_details

ENDSCHEMA;
}

function ws_prompt_citation_schema(): string {
    return <<<'ENDSCHEMA'

RECORD SCHEMA

{
  "jurisdiction_id":   "[TWO-LETTER CODE]",
  "citation_id":       "[JX]-CIT-[YEAR]-[SHORT-SLUG e.g. NJ-CIT-2003-DZWONAR]",
  "parent_statute_id": "[STATUTE_ID this citation directly supports e.g. NJ-34:19-1]",
  "case_name":         "[FULL CASE NAME e.g. Dzwonar v. McDevitt]",
  "court":             "[COURT SHORTHAND from list above e.g. NJ-SUP]",
  "effective_date":    "[YYYY-MM-DD — operative date of ruling]",
  "ruling_date":       "[YYYY-MM-DD — decision date, omit if same as effective_date]",
  "specific_impact":   "[10-20 words, action-verb first — describes the legal holding]",
  "favorable":         true,

  "disclosure_types":    [],
  "protected_class":     [],
  "disclosure_targets":  [],
  "adverse_action":      [],
  "remedies":            [],
  "process_type":        [],
  "employer_defense":    [],
  "employee_standard":   [],

  "_multi_taxonomy_notes": "[Omit unless multiple taxonomy arrays are tagged — explain the intersection]",

  "links": {
    "case_url":    "[URL to case on approved source — omit if unverifiable]",
    "is_official": false,
    "url_source":  "[domain name — omit if is_official is true]"
  },

  "quality":          "[high | moderate | low]",

  "_review_notes":     "",
  "_reconciled_notes": "[NOTEBOOKLM ONLY — leave this key empty. Research models must not populate this field.]"
}

---

SCHEMA NOTES

citation_id: Format [JX]-CIT-[YEAR]-[SHORT-SLUG]. Year is ruling year.
Short slug is first meaningful word of the case name.

taxonomy arrays: Tag sparingly. In almost all cases a citation addresses
exactly one taxonomy axis — tag one term with confidence rather than
several terms with uncertainty. If and only if the ruling explicitly and
materially addresses multiple taxonomy axes in a single holding, tag all
that apply and populate _multi_taxonomy_notes. Multiple tags without
_multi_taxonomy_notes is an error. _multi_taxonomy_notes without
multiple tags is an error.

favorable: Set to true only when the ruling materially expands, strengthens,
or clarifies protection in a way that changes what the statute covers or
how it applies. Procedural wins that do not move the legal line — omit.
Editorial judgment is not sufficient — it must be undeniably and
overwhelmingly pro-whistleblower in scope.

effective_date: The date the ruling became operative law. Omit ruling_date
if the same as effective_date.

CALCULATED FIELDS — compute last:
  meta.record_count     — must equal length of records array
  meta.proposed_count   — must equal length of new_terms_proposed
  integrity.error_count — must equal length of error_details

ENDSCHEMA;
}

function ws_prompt_interpretation_schema( string $statute_type ): string {
    $court_note = $statute_type === 'federal'
        ? 'Federal statutes may be interpreted by federal or state courts.'
        : 'State statutes: include only state court interpretations unless a federal court ruling directly interprets this state statute.';

    return <<<ENDSCHEMA

RECORD SCHEMA

{$court_note}

{
  "jurisdiction_id":   "[TWO-LETTER CODE]",
  "interpretation_id": "[JX]-INTERP-[YEAR]-[SHORT-SLUG]",
  "parent_statute_id": "[STATUTE_ID this interpretation directly addresses]",
  "case_name":         "[FULL CASE NAME]",
  "court":             "[COURT SHORTHAND from list above]",
  "effective_date":    "[YYYY-MM-DD — operative date of ruling]",
  "ruling_date":       "[YYYY-MM-DD — omit if same as effective_date]",
  "specific_impact":   "[10-20 words, action-verb first — describes the legal holding]",
  "favorable":         true,

  "disclosure_types":    [],
  "protected_class":     [],
  "disclosure_targets":  [],
  "adverse_action":      [],
  "remedies":            [],
  "process_type":        [],
  "employer_defense":    [],
  "employee_standard":   [],

  "_multi_taxonomy_notes": "[Omit unless multiple taxonomy arrays are tagged]",

  "links": {
    "case_url":    "[URL on approved source — omit if unverifiable]",
    "is_official": false,
    "url_source":  "[domain name — omit if is_official is true]"
  },

  "quality": "[high | moderate | low]",

  "_review_notes":     "",
  "_reconciled_notes": "[NOTEBOOKLM ONLY — leave this key empty. Research models must not populate this field.]"
}

---

SCHEMA NOTES

taxonomy arrays: Tag sparingly — same rules as citations. One taxonomy axis
almost always. Multiple tags require _multi_taxonomy_notes.

favorable: Only when the ruling materially expands or clarifies protection.
Must be undeniably pro-whistleblower in scope. Omit when in doubt.

CALCULATED FIELDS — compute last:
  meta.record_count     — must equal length of records array
  meta.proposed_count   — must equal length of new_terms_proposed
  integrity.error_count — must equal length of error_details

ENDSCHEMA;
}


// ── Meta block schema (shared) ────────────────────────────────────────────

function ws_prompt_meta_schema( string $record_type ): string {
    return <<<ENDSCHEMA

META BLOCK SCHEMA

{
  "meta": {
    "json_format_version": "2.0",
    "source_method":       "ai_assisted",
    "source_name":         "[YOUR COMMON NAME e.g. Gemini]",
    "jurisdiction_id":     "[TWO-LETTER JURISDICTION CODE]",
    "generated_date":      "[YYYY-MM-DD]",
    "generated_by":        "[YOUR FULL MODEL NAME AND VERSION]",
    "record_count":        0,
    "proposed_count":      0,
    "new_terms_proposed":  [],
    "json_run_notes":      "",
    "batch_completed":     "[YYYY-MM-DD HH:MM UTC — written last]"
  }
}

batch_completed: Always use UTC. Format: YYYY-MM-DD HH:MM UTC.
Written last, after all records, proposals, and calculated fields are final.

ENDSCHEMA;
}


// ── Full prompt assemblers ────────────────────────────────────────────────

function ws_generate_statute_prompt( array $scope ): string {
    $jx       = strtoupper( sanitize_text_field( $scope['jx_id'] ) );
    $jx_name  = sanitize_text_field( $scope['jx_name'] );
    $leg_url  = esc_url_raw( $scope['legislature_url'] );
    $records  = (int) $scope['records_requested'];
    $notes    = sanitize_textarea_field( $scope['scope_notes'] );
    $excludes = sanitize_textarea_field( $scope['exclusion_list'] );

    $out  = "You are a legal research assistant generating structured JSON data for\n";
    $out .= "WhistleblowerShield.org, a public-interest reference site covering U.S.\n";
    $out .= "whistleblower protections across all 57 U.S. jurisdictions. Please read\n";
    $out .= "the entire prompt before execution.\n\n";
    $out .= "This data will enter a human review queue before anything is published.\n";
    $out .= "You are not the final authority — you are the first pass. Your job is to\n";
    $out .= "produce the most accurate draft you can, and to be honest about what you\n";
    $out .= "could not find. A wrong statute of limitations or a fabricated citation\n";
    $out .= "could cause real harm to a worker relying on this information.\n";
    $out .= "Honest gaps do not. When in doubt, always choose omission.\n\n";
    $out .= "---\n\n";
    $out .= "WHAT YOU ARE PRODUCING\n\n";
    $out .= "A single JSON object with three top-level keys:\n\n";
    $out .= "  - \"meta\"      — one block describing this batch\n";
    $out .= "  - \"records\"   — an array of statute records\n";
    $out .= "  - \"integrity\" — your honest self-report on the state of this batch\n\n";
    $out .= "The ingest tool maps your JSON directly to internal data fields. Do not\n";
    $out .= "add keys that are not in the schema. Do not reorder fields within a record.\n\n";
    $out .= ws_prompt_omission_rules();
    $out .= "\n";
    $out .= "TAXONOMY FIELDS\n\n";
    $out .= "The following fields accept only the registered term slugs listed in the\n";
    $out .= "taxonomy tables below. Use the slug that best fits. If no slug fits, leave\n";
    $out .= "the array empty — do not invent a slug and insert it into the record.\n\n";
    $out .= "  legal_basis.disclosure_types     → ws_disclosure_type\n";
    $out .= "  legal_basis.protected_class      → ws_protected_class      → can use has-details\n";
    $out .= "  legal_basis.disclosure_targets   → ws_disclosure_targets   → can use has-details\n";
    $out .= "  enforcement.process_type         → ws_process_type\n";
    $out .= "  enforcement.adverse_action       → ws_adverse_action_types → can use has-details\n";
    $out .= "  enforcement.remedies             → ws_remedies             → can use has-details\n";
    $out .= "  enforcement.fee_shifting         → ws_fee_shifting\n";
    $out .= "  burden_of_proof.employee_standard → ws_employee_standard   → can use has-details\n";
    $out .= "  burden_of_proof.employer_defense  → ws_employer_defense    → can use has-details\n\n";
    $out .= "Any taxonomy field set to has-details requires the details in an associated\n";
    $out .= "freetext field *_details following the taxonomy field. Omit the *_details\n";
    $out .= "key entirely when the taxonomy field has a proper slug.\n\n";
    $out .= ws_prompt_parent_slug_block();
    $out .= ws_prompt_taxonomy_tables( 'jx-statute' );
    $out .= ws_prompt_proposal_block();
    $out .= ws_prompt_statute_rules();
    $out .= ws_prompt_citation_rules();
    $out .= ws_prompt_meta_schema( 'statute' );
    $out .= ws_prompt_statute_schema();
    $out .= ws_prompt_integrity_block();

    // RUN SCOPE
    $out .= "RUN SCOPE\n\n";
    $out .= "Jurisdiction:       {$jx_name}\n";
    $out .= "Jurisdiction ID:    {$jx}\n";
    $out .= "Legislature URL:    {$leg_url}\n";
    $out .= "Record type:        statute\n";
    if ( $records > 0 ) {
        $out .= "Records Requested:  {$records}\n";
    }
    if ( $notes ) {
        $out .= "Scope:              {$notes}\n";
    }
    if ( $excludes ) {
        $out .= "Exclusion list:     Do not produce records for any statute in this list:\n";
        foreach ( explode( "\n", $excludes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "                    {$line}\n";
        }
    }
    $out .= "\n\nThis template covers `statute` records only.\n";
    $out .= "Other record types use separate templates.\n\n";
    $out .= "If any field in the RUN SCOPE is missing, vague, or still holding a\n";
    $out .= "placeholder value, the directive is malformed. Abort immediately.\n\n";
    $out .= "If you cannot confirm a statute exists or locate it at any approved source,\n";
    $out .= "do not fabricate a record. A partial honest batch is always preferred over\n";
    $out .= "a complete unreliable one.\n\n";
    $out .= "---\n\n";
    $out .= "Produce the complete JSON object now, inside a single code block.\n";
    $out .= "Do not include any commentary, explanation, or markdown outside the code block.\n";

    return $out;
}

function ws_generate_common_law_prompt( array $scope ): string {
    $jx       = strtoupper( sanitize_text_field( $scope['jx_id'] ) );
    $jx_name  = sanitize_text_field( $scope['jx_name'] );
    $leg_url  = esc_url_raw( $scope['legislature_url'] );
    $notes    = sanitize_textarea_field( $scope['scope_notes'] );
    $excludes = sanitize_textarea_field( $scope['exclusion_list'] );

    $out  = "You are a legal research assistant generating structured JSON data for\n";
    $out .= "WhistleblowerShield.org, a public-interest reference site covering U.S.\n";
    $out .= "whistleblower protections across all 57 U.S. jurisdictions. Please read\n";
    $out .= "the entire prompt before execution.\n\n";
    $out .= "This template covers `common-law` records ONLY — judicially-recognized\n";
    $out .= "whistleblower protections that exist outside codified statute. Do not\n";
    $out .= "produce records for statutory protections — those use a separate template.\n\n";
    $out .= "A wrong SOL or fabricated case citation could cause real harm.\n";
    $out .= "Honest gaps do not. When in doubt, always choose omission.\n\n";
    $out .= "---\n\n";
    $out .= "WHAT YOU ARE PRODUCING\n\n";
    $out .= "A single JSON object with three top-level keys:\n\n";
    $out .= "  - \"meta\"      — one block describing this batch\n";
    $out .= "  - \"records\"   — an array of common law doctrine records\n";
    $out .= "  - \"integrity\" — your honest self-report on the state of this batch\n\n";
    $out .= "The ingest tool maps your JSON directly to internal data fields. Do not\n";
    $out .= "add keys that are not in the schema. Do not reorder fields within a record.\n\n";
    $out .= ws_prompt_omission_rules();
    $out .= ws_prompt_parent_slug_block();
    $out .= ws_prompt_taxonomy_tables( 'jx-common-law' );
    $out .= ws_prompt_proposal_block();
    $out .= ws_prompt_citation_rules();
    $out .= ws_prompt_meta_schema( 'common-law' );
    $out .= ws_prompt_common_law_schema();
    $out .= ws_prompt_integrity_block();

    $out .= "RUN SCOPE\n\n";
    $out .= "Jurisdiction:       {$jx_name}\n";
    $out .= "Jurisdiction ID:    {$jx}\n";
    $out .= "Legislature URL:    {$leg_url}\n";
    $out .= "Record type:        common-law\n";
    if ( $notes ) {
        $out .= "Scope:              {$notes}\n";
    }
    if ( $excludes ) {
        $out .= "Exclusion list:     Do not produce records for any doctrine in this list:\n";
        foreach ( explode( "\n", $excludes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "                    {$line}\n";
        }
    }
    $out .= "\n\nIf you cannot confirm a common law doctrine exists with reasonable\n";
    $out .= "confidence, do not fabricate a record. A partial honest batch is always\n";
    $out .= "preferred over a complete unreliable one.\n\n";
    $out .= "---\n\n";
    $out .= "Produce the complete JSON object now, inside a single code block.\n";
    $out .= "Do not include any commentary, explanation, or markdown outside the code block.\n";

    return $out;
}

function ws_generate_citation_prompt( array $scope ): string {
    $jx         = strtoupper( sanitize_text_field( $scope['jx_id'] ) );
    $jx_name    = sanitize_text_field( $scope['jx_name'] );
    $leg_url    = esc_url_raw( $scope['legislature_url'] );
    $statutes   = sanitize_textarea_field( $scope['scope_notes'] );
    $min_q      = sanitize_text_field( $scope['min_quality'] ?? 'moderate' );
    $excludes   = sanitize_textarea_field( $scope['exclusion_list'] );

    $out  = "You are a legal research assistant generating structured JSON citation data\n";
    $out .= "for WhistleblowerShield.org. Please read the entire prompt before execution.\n\n";
    $out .= "Your task is to find case law citations that directly support, interpret,\n";
    $out .= "or materially expand the protections of the statutes listed in the RUN SCOPE.\n";
    $out .= "You are not researching new statutes — you are finding case law that anchors\n";
    $out .= "to existing statute records.\n\n";
    $out .= "A fabricated citation could cause real harm. If you cannot supply both a\n";
    $out .= "real case with reasonable confidence AND a verifiable URL from an approved\n";
    $out .= "source, omit the citation entirely.\n\n";
    $out .= "---\n\n";
    $out .= ws_prompt_citation_rules();
    $out .= ws_prompt_get_court_shorthand();
    $out .= "\n---\n\n";
    $out .= "TAXONOMY TAGGING — CITATION DISCIPLINE\n\n";
    $out .= "Tag only what this specific ruling directly addresses. In almost all cases\n";
    $out .= "a citation addresses exactly one taxonomy axis — tag one term with confidence\n";
    $out .= "rather than several terms with uncertainty.\n\n";
    $out .= "If and only if the ruling explicitly and materially addresses multiple\n";
    $out .= "taxonomy axes in a single holding, tag all that apply AND populate\n";
    $out .= "_multi_taxonomy_notes with a prose explanation of how the ruling touched\n";
    $out .= "each axis. Multiple tags without _multi_taxonomy_notes is an error.\n\n";
    $out .= ws_prompt_taxonomy_tables( 'jx-citation' );
    $out .= ws_prompt_meta_schema( 'citation' );
    $out .= ws_prompt_citation_schema();
    $out .= ws_prompt_integrity_block();

    $out .= "RUN SCOPE\n\n";
    $out .= "Jurisdiction:       {$jx_name}\n";
    $out .= "Jurisdiction ID:    {$jx}\n";
    $out .= "Legislature URL:    {$leg_url}\n";
    $out .= "Record type:        citation\n";
    $out .= "Minimum quality:    {$min_q}\n";
    if ( $statutes ) {
        $out .= "Find citations for these statutes:\n";
        foreach ( explode( "\n", $statutes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "  {$line}\n";
        }
    }
    if ( $excludes ) {
        $out .= "Exclusion list — do not produce records for these cases:\n";
        foreach ( explode( "\n", $excludes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "  {$line}\n";
        }
    }
    $out .= "\n\n---\n\n";
    $out .= "Produce the complete JSON object now, inside a single code block.\n";
    $out .= "Do not include any commentary, explanation, or markdown outside the code block.\n";

    return $out;
}

function ws_generate_interpretation_prompt( array $scope ): string {
    $jx           = strtoupper( sanitize_text_field( $scope['jx_id'] ) );
    $jx_name      = sanitize_text_field( $scope['jx_name'] );
    $leg_url      = esc_url_raw( $scope['legislature_url'] );
    $statutes     = sanitize_textarea_field( $scope['scope_notes'] );
    $statute_type = sanitize_text_field( $scope['statute_type'] ?? 'state' );
    $min_q        = sanitize_text_field( $scope['min_quality'] ?? 'moderate' );
    $excludes     = sanitize_textarea_field( $scope['exclusion_list'] );

    $out  = "You are a legal research assistant generating structured JSON interpretation\n";
    $out .= "data for WhistleblowerShield.org. Please read the entire prompt before execution.\n\n";
    $out .= "Your task is to find court rulings that directly interpret the statutes listed\n";
    $out .= "in the RUN SCOPE — rulings that clarify what the statute means, who it covers,\n";
    $out .= "how it is applied, or where its limits lie.\n\n";
    $out .= "A fabricated citation could cause real harm. If you cannot supply both a real\n";
    $out .= "case with reasonable confidence AND a verifiable URL, omit it entirely.\n\n";
    $out .= "---\n\n";
    $out .= ws_prompt_citation_rules();
    $out .= ws_prompt_get_court_shorthand();
    $out .= "\n---\n\n";
    $out .= "TAXONOMY TAGGING — INTERPRETATION DISCIPLINE\n\n";
    $out .= "Tag only what this specific ruling directly interprets or clarifies.\n";
    $out .= "In almost all cases a court ruling addresses exactly one taxonomy axis.\n";
    $out .= "Tag one term with confidence. If the ruling genuinely addresses multiple\n";
    $out .= "axes, tag all and populate _multi_taxonomy_notes.\n\n";
    $out .= ws_prompt_taxonomy_tables( 'jx-interpretation' );
    $out .= ws_prompt_meta_schema( 'interpretation' );
    $out .= ws_prompt_interpretation_schema( $statute_type );
    $out .= ws_prompt_integrity_block();

    $out .= "RUN SCOPE\n\n";
    $out .= "Jurisdiction:       {$jx_name}\n";
    $out .= "Jurisdiction ID:    {$jx}\n";
    $out .= "Legislature URL:    {$leg_url}\n";
    $out .= "Record type:        interpretation\n";
    $out .= "Statute type:       {$statute_type}\n";
    $out .= "Minimum quality:    {$min_q}\n";
    if ( $statutes ) {
        $out .= "Find interpretations for these statutes:\n";
        foreach ( explode( "\n", $statutes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "  {$line}\n";
        }
    }
    if ( $excludes ) {
        $out .= "Exclusion list — do not produce records for these cases:\n";
        foreach ( explode( "\n", $excludes ) as $line ) {
            $line = trim( $line );
            if ( $line ) $out .= "  {$line}\n";
        }
    }
    $out .= "\n\n---\n\n";
    $out .= "Produce the complete JSON object now, inside a single code block.\n";
    $out .= "Do not include any commentary, explanation, or markdown outside the code block.\n";

    return $out;
}


// ── Form handler ──────────────────────────────────────────────────────────

function ws_handle_prompt_generation(): array {
    $result = [ 'success' => false, 'message' => '', 'filename' => '', 'path' => '' ];

    if ( empty( $_POST['ws_prompt_nonce'] ) || ! wp_verify_nonce( $_POST['ws_prompt_nonce'], 'ws_generate_prompt' ) ) {
        $result['message'] = 'Security check failed.';
        return $result;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        $result['message'] = 'Insufficient permissions.';
        return $result;
    }

    $record_type = sanitize_text_field( $_POST['record_type'] ?? '' );
    $jx_id       = strtoupper( sanitize_text_field( $_POST['jx_id'] ?? '' ) );
    $jx_name     = sanitize_text_field( $_POST['jx_name'] ?? '' );
    $leg_url     = esc_url_raw( $_POST['legislature_url'] ?? '' );

    if ( ! $record_type || ! $jx_id || ! $jx_name ) {
        $result['message'] = 'Record type, Jurisdiction ID, and Jurisdiction Name are required.';
        return $result;
    }

    $scope = [
        'jx_id'           => $jx_id,
        'jx_name'         => $jx_name,
        'legislature_url' => $leg_url,
        'records_requested' => (int) ( $_POST['records_requested'] ?? 0 ),
        'scope_notes'     => sanitize_textarea_field( $_POST['scope_notes'] ?? 'state-level whistleblower laws and protections' ),
        'exclusion_list'  => sanitize_textarea_field( $_POST['exclusion_list'] ?? '' ),
        'min_quality'     => sanitize_text_field( $_POST['min_quality'] ?? 'moderate' ),
        'statute_type'    => sanitize_text_field( $_POST['statute_type'] ?? 'state' ),
    ];

    switch ( $record_type ) {
        case 'statute':
            $prompt = ws_generate_statute_prompt( $scope );
            break;
        case 'common-law':
            $prompt = ws_generate_common_law_prompt( $scope );
            break;
        case 'citation':
            $prompt = ws_generate_citation_prompt( $scope );
            break;
        case 'interpretation':
            $prompt = ws_generate_interpretation_prompt( $scope );
            break;
        default:
            $result['message'] = 'Unknown record type.';
            return $result;
    }

    $dir      = ws_prompt_output_dir();
    $filename = strtoupper( $jx_id ) . '-' . $_POST['records_requested'] . '-' . ucfirst($record_type) . '-' . date( 'Ymd-Hi' ) . '.txt';
    $filepath = $dir . '/' . $filename;

    if ( file_put_contents( $filepath, $prompt ) === false ) {
        $result['message'] = 'Failed to write prompt file. Check directory permissions on ' . $dir;
        return $result;
    }

    $result['success']  = true;
    $result['message']  = 'Prompt generated successfully.';
    $result['filename'] = $filename;
    $result['path']     = str_replace( ABSPATH, '/', $filepath );

    return $result;
}


// ── Admin page renderer ───────────────────────────────────────────────────

function ws_render_prompt_generator_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access denied.' );
    }

    $result = null;
    if ( isset( $_POST['ws_prompt_nonce'] ) ) {
        $result = ws_handle_prompt_generation();
    }

    $record_type = sanitize_text_field( $_POST['record_type'] ?? 'statute' );

    ?>
    <div class="wrap">
        <h1>WS Prompt Generator</h1>
        <p>Generates AI research prompt templates from live taxonomy data. Output files are written to
           <code><?php echo esc_html( str_replace( ABSPATH, '/', WP_CONTENT_DIR . '/logs/ws-prompts/' ) ); ?></code>
           for FTP retrieval.</p>

        <?php if ( $result ): ?>
            <div class="notice notice-<?php echo $result['success'] ? 'success' : 'error'; ?> is-dismissible">
                <p><?php echo esc_html( $result['message'] ); ?></p>
                <?php if ( $result['success'] ): ?>
                    <p><strong>File:</strong> <code><?php echo esc_html( $result['filename'] ); ?></code></p>
                    <p><strong>Path:</strong> <code><?php echo esc_html( $result['path'] ); ?></code></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'ws_generate_prompt', 'ws_prompt_nonce' ); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row"><label for="record_type">Record Type</label></th>
                    <td>
                        <select name="record_type" id="record_type" onchange="wsPromptToggleFields()">
                            <option value="statute"        <?php selected( $record_type, 'statute' ); ?>>Statute</option>
                            <option value="common-law"     <?php selected( $record_type, 'common-law' ); ?>>Common Law</option>
                            <option value="citation"       <?php selected( $record_type, 'citation' ); ?>>Citation</option>
                            <option value="interpretation" <?php selected( $record_type, 'interpretation' ); ?>>Interpretation</option>
                        </select>
                        <p class="description">Statute and Common Law produce full research prompts.
                           Citation and Interpretation produce enrichment prompts anchored to existing records.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="jx_name">Jurisdiction Name</label></th>
                    <td>
                        <input type="text" name="jx_name" id="jx_name"
                               value="<?php echo esc_attr( $_POST['jx_name'] ?? '' ); ?>"
                               class="regular-text" placeholder="e.g. New Jersey" required>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="jx_id">Jurisdiction ID</label></th>
                    <td>
                        <input type="text" name="jx_id" id="jx_id"
                               value="<?php echo esc_attr( $_POST['jx_id'] ?? '' ); ?>"
                               class="small-text" placeholder="e.g. NJ" maxlength="4" required
                               style="text-transform:uppercase;">
                        <p class="description">USPS two-letter code or US for federal.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="legislature_url">Legislature URL</label></th>
                    <td>
                        <input type="url" name="legislature_url" id="legislature_url"
                               value="<?php echo esc_attr( $_POST['legislature_url'] ?? '' ); ?>"
                               class="regular-text" placeholder="https://www.njleg.state.nj.us/">
                        <p class="description">Official legislature homepage for this jurisdiction.</p>
                    </td>
                </tr>

                <tr class="ws-field-statute ws-field-common-law">
                    <th scope="row"><label for="records_requested">Records Requested</label></th>
                    <td>
                        <input type="number" name="records_requested" id="records_requested"
                               value="<?php echo esc_attr( $_POST['records_requested'] ?? '' ); ?>"
                               class="small-text" min="0" max="20" placeholder="0 = no limit">
                        <p class="description">Leave blank or 0 for no limit.</p>
                    </td>
                </tr>

                <tr class="ws-field-statute ws-field-common-law">
                    <th scope="row"><label for="scope_notes">Scope Notes</label></th>
                    <td>
                        <textarea name="scope_notes" id="scope_notes" rows="3" class="large-text"
                                  placeholder="e.g. Please include CEPA, with citations"><?php echo esc_textarea( $_POST['scope_notes'] ?? '' ); ?></textarea>
                        <p class="description">Optional guidance for the model — specific statutes to include, priorities, etc. — Defaults to: state-level whistleblower laws and protections (if empty)</p>
                    </td>
                </tr>

                <tr class="ws-field-citation ws-field-interpretation" style="display:none;">
                    <th scope="row"><label for="scope_notes_citations">Statutes to Research</label></th>
                    <td>
                        <textarea name="scope_notes" id="scope_notes_citations" rows="5" class="large-text"
                                  placeholder="NJ-34:19-1&#10;NJ-2A:32C-10&#10;NJ-34:11-4.10"><?php echo esc_textarea( $_POST['scope_notes'] ?? '' ); ?></textarea>
                        <p class="description">One statute ID per line. Find citations/interpretations for these records.</p>
                    </td>
                </tr>

                <tr class="ws-field-citation ws-field-interpretation" style="display:none;">
                    <th scope="row"><label for="min_quality">Minimum Quality</label></th>
                    <td>
                        <select name="min_quality" id="min_quality">
                            <option value="low"      <?php selected( $_POST['min_quality'] ?? 'moderate', 'low' ); ?>>Low (include all)</option>
                            <option value="moderate" <?php selected( $_POST['min_quality'] ?? 'moderate', 'moderate' ); ?>>Moderate (appellate+)</option>
                            <option value="high"     <?php selected( $_POST['min_quality'] ?? 'moderate', 'high' ); ?>>High (supreme courts only)</option>
                        </select>
                    </td>
                </tr>

                <tr class="ws-field-interpretation" style="display:none;">
                    <th scope="row"><label for="statute_type">Statute Type</label></th>
                    <td>
                        <select name="statute_type" id="statute_type">
                            <option value="state"   <?php selected( $_POST['statute_type'] ?? 'state', 'state' ); ?>>State statute</option>
                            <option value="federal" <?php selected( $_POST['statute_type'] ?? 'state', 'federal' ); ?>>Federal statute</option>
                        </select>
                        <p class="description">State statutes: state courts only. Federal statutes: federal and state courts.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="exclusion_list">Exclusion List</label></th>
                    <td>
                        <textarea name="exclusion_list" id="exclusion_list" rows="4" class="large-text"
                                  placeholder="NJ-34:19-1&#10;NJ-2A:32C-10"><?php echo esc_textarea( $_POST['exclusion_list'] ?? '' ); ?></textarea>
                        <p class="description">One ID per line. Records already ingested — model will not produce duplicates.</p>
                    </td>
                </tr>

            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary"
                       value="Generate Prompt">
            </p>

        </form>
    </div>

    <script>
    function wsPromptToggleFields() {
        var type = document.getElementById('record_type').value;
        var groups = {
            'statute':        ['ws-field-statute'],
            'common-law':     ['ws-field-statute', 'ws-field-common-law'],
            'citation':       ['ws-field-citation'],
            'interpretation': ['ws-field-citation', 'ws-field-interpretation'],
        };
        var allClasses = ['ws-field-statute', 'ws-field-common-law', 'ws-field-citation', 'ws-field-interpretation'];
        allClasses.forEach(function(cls) {
            document.querySelectorAll('.' + cls).forEach(function(el) {
                el.style.display = 'none';
            });
        });
        (groups[type] || []).forEach(function(cls) {
            document.querySelectorAll('.' + cls).forEach(function(el) {
                el.style.display = '';
            });
        });
    }
    document.addEventListener('DOMContentLoaded', wsPromptToggleFields);
    </script>
    <?php
}
