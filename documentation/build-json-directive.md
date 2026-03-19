# Build JSON Directive

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/build-json-directive.md`
**Purpose:** Reference document describing the prompt template system for
             generating structured legal data JSON via Gemini.

---

## Overview

Gemini directives are **not written by hand** â€” they are generated at runtime
by `tools/tool-generate-prompt.php`. This document describes the system
design, the static elements that live in prompt templates, and the dynamic
elements injected at generation time.

Do not paste this document to Gemini directly. Use the prompt generator tool
to produce a ready-to-paste directive that is guaranteed to be in sync with
the current taxonomy state.

---

## Why Generated Prompts

Hardcoded directives drift. As taxonomy terms are added, renamed, or
restructured, a static directive becomes stale â€” Gemini invents term IDs
that look plausible but are not registered, and the ingest tool has no
clean mapping target. The prompt generator eliminates this by reading
`register-taxonomies.php` directly every time a directive is produced.

---

## Prompt Template Structure

Each `prompt-templates/template-v{X}-{record_type}.md` file contains:

1. **Static instruction prose** â€” the rules, field definitions, and behavioral
   directives that rarely change. Versioned â€” a new version file is created
   when the schema changes, not edited in place.

2. **Taxonomy table placeholders** â€” markers replaced at generation time with
   the live term slug lists read from `register-taxonomies.php`. Format:

   ```
   {{TAXONOMY:ws_disclosure_type}}
   {{TAXONOMY:ws_protected_class}}
   {{TAXONOMY:ws_disclosure_targets}}
   {{TAXONOMY:ws_adverse_action_types}}
   {{TAXONOMY:ws_process_type}}
   {{TAXONOMY:ws_remedies}}
   {{TAXONOMY:ws_fee_shifting}}
   {{TAXONOMY:ws_retaliation_forms}}
   ```

3. **Schema block** â€” the exact JSON structure Gemini must produce for this
   record type. Record-type-specific â€” statute schema differs from citation
   schema, interpretation schema, etc.

---

## Static Instruction Rules (all record types)

These rules appear in every template and must not be altered between versions
unless a formal schema version increment is made:

### Output format rules

- Produce a single valid JSON object with two top-level keys: `meta` and `records`
- Maintain strict field order within `meta` and within each record as defined
  in the schema block â€” do not reorder fields
- Use slug-style term IDs (snake_case) for all taxonomy array values â€” never
  human-readable labels in array fields
- Do not add keys not present in the schema
- Do not infer data â€” if a field value cannot be found through research, omit
  the key entirely
- Do not use `null`, `"N/A"`, or any placeholder string to indicate missing data
- Use `""` for empty strings and `[]` for empty arrays
- Calculate `citation_count` and `proposed_count` last, after all arrays are
  finalised, to ensure accuracy
- `json_run_notes` is the only field where editorial latitude is permitted â€”
  all other fields are schema-constrained

### `new_terms_proposed` rules

- As you research each record, if a taxonomy value is needed that does not
  appear in the known taxonomy list for that field, do NOT include it in the
  record â€” add it to `new_terms_proposed` in the `meta` block instead
- This rule applies to values you invent as well as values you cannot classify â€”
  any slug not present in the provided taxonomy list must be proposed
- Each proposed term entry must follow the exact schema below
- If the same term appears in multiple records, list all statute identifiers
  in `seen_in` rather than creating duplicate entries
- `count` must equal the length of the `seen_in` array
- Do not propose new taxonomy tables â€” use `json_run_notes` to recommend one
- Do not propose new taxonomy parents â€” use `json_run_notes` to recommend one

### `record_count` = ALL behavior

When `record_count` is `ALL`:

- Do not produce records immediately
- Return only an integer count of records that would be produced for the
  declared jurisdiction and scope
- The ingest tool will issue a separate confirmation request before proceeding
  with the full directive

---

## `meta` Block Schema

```json
{
  "meta": {
    "json_format_version": "1.6",
    "source_method":       "ai_assisted",
    "source_name":         "[YOUR COMMON NAME e.g. Gemini]",
    "jurisdiction_id":     "[TWO-LETTER CODE]",
    "generated_date":      "[YYYY-MM-DD]",
    "generated_by":        "[YOUR FULL MODEL NAME AND VERSION e.g. Gemini 1.5 Pro]",
    "record_count":        [INTEGER â€” calculated after records array is complete],
    "proposed_count":      [INTEGER â€” calculated after new_terms_proposed is complete],
    "new_terms_proposed":  [],
    "json_run_notes":      "[YOUR BATCH SUMMARY AND ANY TAXONOMY RECOMMENDATIONS]"
  }
}
```

**`source_name` vs `generated_by`:** These serve different purposes.
`source_name` is the public-facing label stamped on every post meta record
(e.g. `"Gemini"`). `generated_by` is the audit trail entry capturing the
specific model version (e.g. `"Gemini 1.5 Pro"`). They may differ
intentionally â€” do not collapse them into the same value.

## `new_terms_proposed` Entry Schema

```json
{
  "taxonomy":   "[TAXONOMY TABLE SLUG FROM KNOWN LIST]",
  "parent":     "[EXISTING PARENT TERM SLUG â€” omit if top-level]",
  "term_id":    "[YOUR PROPOSED SLUG IN snake_case]",
  "term_label": "[YOUR PROPOSED HUMAN-READABLE LABEL]",
  "notes":      "[YOUR REASONING FOR WHY THIS TERM IS NEEDED]",
  "seen_in":    [ "[STATUTE_ID]", "[SECOND_STATUTE_ID]" ],
  "count":      [INTEGER â€” must equal length of seen_in array]
}
```

---

## Record Schema â€” `statute` (json_format_version 1.6)

Field order must be maintained exactly as shown.

```json
{
  "jurisdiction_id":  "[TWO-LETTER CODE]",
  "statute_id":       "[JURISDICTION_ID]-[SECTION e.g. CA-1102.5]",
  "official_name":    "[FULL OFFICIAL STATUTE NAME]",
  "common_name":      "[PLAIN LANGUAGE COMMON NAME IF ONE EXISTS]",

  "legal_basis": {
    "statute_citation":     "[FORMAL CITATION e.g. Cal. Lab. Code Â§ 1102.5]",
    "disclosure_types":     [ "[SLUG FROM ws_disclosure_type]" ],
    "protected_class":      [ "[SLUG FROM ws_protected_class]" ],
    "disclosure_targets":   [ "[SLUG FROM ws_disclosure_targets]" ],
    "adverse_action_scope": "[FREE TEXT â€” scope of actions considered adverse]"
  },

  "statute_of_limitations": {
    "limit_ambiguous":     [true | false],
    "limit_value":         [INTEGER â€” omit if limit_ambiguous is true],
    "limit_unit":          "[days | months | years â€” omit if limit_ambiguous is true]",
    "limit_details":       "[STRING â€” only include if limit_ambiguous is true]",
    "trigger":             "[WHAT EVENT STARTS THE CLOCK]",
    "exhaustion_required": [true | false],
    "exhaustion_details":  "[STRING â€” only include if exhaustion_required is true]",
    "tolling_notes":       "[ANY TOLLING OR EXTENSION PROVISIONS]"
  },

  "enforcement": {
    "primary_agency": "[AGENCY NAME]",
    "process_type":   [ "[SLUG FROM ws_process_type]" ],
    "adverse_action": [ "[SLUG FROM ws_adverse_action_types]" ],
    "remedies":       [ "[SLUG FROM ws_remedies]" ],
    "fee_shifting":   [ "[SLUG FROM ws_fee_shifting]" ]
  },

  "burden_of_proof": {
    "employee_standard":       "[LEGAL STANDARD EMPLOYEE MUST MEET]",
    "employer_defense":        "[LEGAL STANDARD FOR EMPLOYER DEFENSE]",
    "rebuttable_presumption":  "[IF APPLICABLE â€” describe the presumption]",
    "burden_of_proof_details": "[NARRATIVE DETAILS NOT COVERED ABOVE]",
    "burden_of_proof_flag":    [true | false â€” true only if burden_of_proof_details is non-empty]
  },

  "reward": {
    "available":      [true | false],
    "reward_details": "[STRING â€” only include if available is true]"
  },

  "links": {
    "statute_url": "[URL TO OFFICIAL OR RESPECTED ALTERNATIVE SOURCE]",
    "is_official": [true | false â€” true only if URL is a .gov domain],
    "url_source":  "[STRING â€” only include if is_official is false]",
    "is_pdf":      [true | false]
  },

  "citations": {
    "attached_citations": [ "[CASE NAME || CASE URL || SOURCE]" ],
    "citation_count":     [INTEGER â€” must equal length of attached_citations array]
  }
}
```

---

## Taxonomy Tables

> **Note to prompt generator:** Replace each section below with the live
> term slug list read from `register-taxonomies.php` at generation time.
> The lists below are illustrative placeholders only.

### `ws_disclosure_type`
{{TAXONOMY:ws_disclosure_type}}

### `ws_protected_class`
{{TAXONOMY:ws_protected_class}}

### `ws_disclosure_targets`
{{TAXONOMY:ws_disclosure_targets}}

### `ws_adverse_action_types`
{{TAXONOMY:ws_adverse_action_types}}

### `ws_process_type`
{{TAXONOMY:ws_process_type}}

### `ws_remedies`
{{TAXONOMY:ws_remedies}}

### `ws_fee_shifting`
{{TAXONOMY:ws_fee_shifting}}

---

## Run Scope Block

The following block appears at the end of every generated directive,
populated by the prompt generator from the admin UI inputs:

```
Jurisdiction:   [FULL NAME e.g. California]
Jurisdiction ID:[TWO-LETTER CODE e.g. CA]
Record type:    [statute | citation | interpretation | summary | agency | assist-org | reference]
Record count:   [INTEGER or ALL]
Scope:          [DESCRIBE WHAT TO COVER]
Exclude:        [STATUTES TO SKIP or omit field entirely]

Produce the complete JSON object now. Do not include any commentary,
explanation, or markdown outside the JSON block itself.
```

---

## Versioning Policy

- A new `json_format_version` is issued when the record schema changes
- Prompt templates are never edited in place after release â€” a new version
  file is created
- The ingest tool maintains a version handler for every released version
- Historical JSON files must always be re-ingestable against the version
  handler current at their generation date
- `json_run_notes` in the meta block is the appropriate place for Gemini
  to flag taxonomy recommendations, scope observations, or data quality notes

---

## Notes for the Researcher

- Always use the prompt generator tool â€” never write directives by hand
- Verify `record_count` in the returned JSON matches the actual records array
  length before running the ingest tool
- Review `new_terms_proposed` and `proposed-terms-log.json` before ingest â€”
  pending proposals require a resolution decision before affected taxonomy
  fields can be fully classified
- `burden_of_proof_flag` and `reward_details` being non-empty are signals to
  plan manual review of those fields post-ingest
- All non-legalese text fields enter the plain English review queue
  automatically on ingest â€” no action needed at import time
- `source_name` in the meta block is the public-facing label; `generated_by`
  is the audit trail â€” they may intentionally differ

---

*Last updated: 2026-03-18*
*See also: `ingest-tool-design.md`, `legal-research-methodology.md`*
