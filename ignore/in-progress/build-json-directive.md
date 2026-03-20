# Build JSON Directive

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/build-json-directive.md`
**Purpose:** Reference document describing the prompt template system for
             generating structured legal data JSON via LLM research assistants.

---

## Overview

LLM directives are **not written by hand** — they are generated at runtime
by `tools/tool-generate-prompt.php`. This document describes the system
design, the static elements that live in prompt templates, and the dynamic
elements injected at generation time.

Do not paste this document to an LLM directly. Use the prompt generator tool
to produce a ready-to-paste directive that is guaranteed to be in sync with
the current taxonomy state.

---

## The Right Mental Model

You are directing a research assistant, not programming a data entry clerk.

A research assistant hands you their best notes — confidently where they can,
honestly where they cannot. They do not fill gaps with plausible-sounding
details to appear thorough. They say "I couldn't find this" and you trust them
more for it.

That is the relationship the prompt template establishes with the LLM. Every
behavioral instruction in the template exists to support that relationship, not
to constrain the model into compliance.

**Omission is not failure. Fabrication is.**

A record with five verified fields is more valuable than a record with fifteen
fields where three are invented. The prompt template communicates this directly.
The ingest tool enforces taxonomy shape. You enforce content accuracy. The model's
job is to draft and flag — nothing more.

---

## Why Generated Prompts

Hardcoded directives drift. As taxonomy terms are added, renamed, or
restructured, a static directive becomes stale — the model produces term IDs
that look plausible but are not registered, and the ingest tool has no clean
mapping target. The prompt generator eliminates this by reading
`register-taxonomies.php` directly every time a directive is produced.

This also means the model always sees the current taxonomy state. It has no
reason to invent terms it cannot find — the full known list is in front of it.

---

## Prompt Template Structure

Each `prompt-templates/template-v{X}-{record_type}.md` file contains:

1. **Context block** — a plain-language description of what this data is for
   and why accuracy matters more than completeness. This is the first thing
   the model reads. It does more work than any rule list.

2. **Behavioral framing** — the omission-first philosophy stated directly and
   without apology. Not a list of prohibitions. A statement of values.

3. **Taxonomy table placeholders** — markers replaced at generation time with
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

4. **Schema block** — the exact JSON structure the model must produce for this
   record type. Record-type-specific — statute schema differs from citation
   schema, interpretation schema, etc.

5. **Run scope block** — populated at generation time from the admin UI inputs.

---

## Behavioral Framing (all record types)

These principles appear in every template. They are not a ruleset — they are
the framing that shapes how the model approaches the task. Do not reduce them
to a bullet list of prohibitions in future template versions.

### Omission over fabrication

The model is expected to leave fields empty. This is not a fallback — it is
the correct behavior when a value cannot be sourced with reasonable confidence.
Empty strings and empty arrays are valid, complete output. The model should
understand this is a feature of the schema, not a gap in it.

Fields most likely to be empty in a typical run:

- `tolling_notes`
- `exhaustion_details`
- `rebuttable_presumption`
- `attached_citations`
- `reward_details`

This is normal. A run where these fields are consistently empty is not a
poor run — it is an honest one.

### Taxonomy discipline

The model receives the full known term list for every taxonomy field at
generation time. It should use those terms where they fit. Where none fit,
it should route the gap to `new_terms_proposed` in the meta block — not
invent a slug and insert it into a record array.

`new_terms_proposed` exists precisely so taxonomy gaps surface cleanly for
human review rather than silently polluting records.

### URLs and citations

These are the highest-risk fields for fabrication. The model should include
a URL only when it can identify a specific, verifiable source. It should
include a case citation only when it can name a real case with reasonable
confidence. When in doubt, the field stays empty. A blank URL field is
correct output. A fabricated one causes harm.

### `json_run_notes` is the model's voice

This is the one field with full editorial latitude. The model should use it
to flag uncertainty, note scope decisions, identify taxonomy gaps it could
not resolve, and surface anything a human reviewer should know about this
batch. It is the most useful field in the meta block. Treat it accordingly.

---

## `meta` Block Schema
```json
{
  "meta": {
    "json_format_version": "[VERSION STRING e.g. 1.9]",
    "source_method":       "ai_assisted",
    "source_name":         "[MODEL COMMON NAME e.g. Gemini]",
    "jurisdiction_id":     "[TWO-LETTER CODE]",
    "generated_date":      "[YYYY-MM-DD]",
    "generated_by":        "[FULL MODEL NAME AND VERSION]",
    "record_count":        [INTEGER — calculated after records array is complete],
    "proposed_count":      [INTEGER — calculated after new_terms_proposed is complete],
    "new_terms_proposed":  [],
    "json_run_notes":      "[BATCH SUMMARY, UNCERTAINTY FLAGS, TAXONOMY RECOMMENDATIONS]",
    "batch_completed":     "[YYYY-MM-DD HH:MM UTC]"
  }
}
```

**`source_name` vs `generated_by`:** `source_name` is the public-facing label
stamped on every post meta record (e.g. `"Gemini"`). `generated_by` is the
audit trail entry capturing the specific model version
(e.g. `"Gemini 1.5 Pro"`). They may differ intentionally — do not collapse
them into the same value.

**`batch_completed`:** This is the last key written to the meta block. The
ingest tool treats its presence as a completion sentinel — a missing or empty
`batch_completed` causes the ingest tool to abort. The model writes this value
last, after all records and proposed terms are finalized.

---

## `new_terms_proposed` Entry Schema
```json
{
  "taxonomy":   "[TAXONOMY TABLE SLUG FROM KNOWN LIST]",
  "parent":     "[EXISTING PARENT TERM SLUG — omit if top-level]",
  "term_id":    "[PROPOSED SLUG IN snake_case]",
  "term_label": "[PROPOSED HUMAN-READABLE LABEL]",
  "notes":      "[WHY THIS TERM IS NEEDED AND WHY EXISTING TERMS DO NOT COVER IT]",
  "seen_in":    ["[STATUTE_ID]", "[SECOND_STATUTE_ID]"],
  "count":      [INTEGER — must equal length of seen_in array]
}
```

Rules:
- Do not include a proposed `term_id` in any record's arrays — proposals live
  here only until a human resolves them
- If the same gap appears in multiple records, consolidate into one entry with
  all statute IDs in `seen_in`
- Do not propose new taxonomy tables or new parent terms — use `json_run_notes`
  to recommend them instead
- If no new terms are needed, `new_terms_proposed` must be an empty array `[]`

---

## Record Schema — `statute`

Field order must be maintained exactly as shown. Omit a key entirely when its
value would be empty and the field is conditional (see notes inline). Use `""`
for empty strings and `[]` for empty arrays on non-conditional fields.
```json
{
  "jurisdiction_id":  "[TWO-LETTER CODE]",
  "statute_id":       "[JURISDICTION_ID-SECTION e.g. CA-1102.5]",
  "official_name":    "[FULL OFFICIAL STATUTE NAME]",
  "common_name":      "[PLAIN LANGUAGE COMMON NAME — omit if none exists]",

  "legal_basis": {
    "statute_citation":     "[FORMAL CITATION e.g. Cal. Lab. Code § 1102.5]",
    "disclosure_types":     ["[SLUG FROM ws_disclosure_type]"],
    "protected_class":      ["[SLUG FROM ws_protected_class]"],
    "disclosure_targets":   ["[SLUG FROM ws_disclosure_targets]"],
    "adverse_action_scope": "[FREE TEXT — scope of actions considered adverse]"
  },

  "statute_of_limitations": {
    "limit_ambiguous":     [true | false],
    "limit_value":         [INTEGER — omit if limit_ambiguous is true],
    "limit_unit":          "[days | months | years — omit if limit_ambiguous is true]",
    "limit_details":       "[STRING — omit if limit_ambiguous is false]",
    "trigger":             "[WHAT EVENT STARTS THE CLOCK — omit if unknown]",
    "exhaustion_required": [true | false],
    "exhaustion_details":  "[STRING — omit if exhaustion_required is false]",
    "tolling_notes":       "[TOLLING OR EXTENSION PROVISIONS — omit if none identified]"
  },

  "enforcement": {
    "primary_agency": "[AGENCY NAME — omit if unknown]",
    "process_type":   ["[SLUG FROM ws_process_type]"],
    "adverse_action": ["[SLUG FROM ws_adverse_action_types]"],
    "remedies":       ["[SLUG FROM ws_remedies]"],
    "fee_shifting":   ["[SLUG FROM ws_fee_shifting]"]
  },

  "burden_of_proof": {
    "employee_standard":       "[LEGAL STANDARD EMPLOYEE MUST MEET — omit if unknown]",
    "employer_defense":        "[LEGAL STANDARD FOR EMPLOYER DEFENSE — omit if unknown]",
    "rebuttable_presumption":  "[DESCRIBE THE PRESUMPTION — omit if none identified]",
    "burden_of_proof_details": "[NARRATIVE DETAILS NOT COVERED ABOVE — omit if none]",
    "burden_of_proof_flag":    [true | false — true only if burden_of_proof_details is present]
  },

  "reward": {
    "available":      [true | false],
    "reward_details": "[STRING — omit if available is false]"
  },

  "links": {
    "statute_url": "[URL — omit if no trustworthy source identified]",
    "is_official": [true | false — true only if URL is a .gov domain],
    "url_source":  "[SOURCE NAME — omit if is_official is true or no URL present]",
    "is_pdf":      [true | false]
  },

  "citations": {
    "attached_citations": ["[CASE NAME || BRIEF IMPACT DESCRIPTION || URL IF VERIFIED || SOURCE]"],
    "citation_count":     [INTEGER — must equal length of attached_citations array]
  }
}
```

---

## Run Scope Block

The following block appears at the end of every generated directive,
populated by the prompt generator from the admin UI inputs:
```
Jurisdiction:    [FULL NAME e.g. California]
Jurisdiction ID: [TWO-LETTER CODE e.g. CA]
Record type:     [statute | citation | interpretation | summary | agency | assist-org | reference]
Record count:    [INTEGER or ALL]
Scope:           [DESCRIBE WHAT TO COVER]
Exclude:         [STATUTES TO SKIP — omit this line entirely if nothing excluded]

Produce the complete JSON object now. Do not include any commentary,
explanation, or markdown outside the JSON block itself.
```

### `record_count: ALL` behavior

When `record_count` is `ALL`:

- Do not produce records immediately
- Return only an integer count of records that would be produced for the
  declared jurisdiction and scope
- The ingest tool will issue a separate confirmation request before proceeding
  with the full directive

---

## Versioning Policy

- A new `json_format_version` is issued when the record schema changes
- Prompt templates are never edited in place after release — a new version
  file is created
- The ingest tool maintains a version handler for every released version
- Historical JSON files must always be re-ingestable against the version
  handler current at their generation date
- `json_run_notes` is the appropriate place for the model to flag taxonomy
  recommendations, scope observations, or data quality notes

---

## Notes for the Researcher

- Always use the prompt generator tool — never write directives by hand
- Verify `record_count` in the returned JSON matches the actual records array
  length before running the ingest tool
- Review `new_terms_proposed` before ingest — pending proposals require a
  resolution decision before affected taxonomy fields can be fully classified
- `burden_of_proof_flag: true` and a non-empty `reward_details` are signals
  to plan manual review of those fields post-ingest
- `batch_completed` being absent or empty means the run did not finish —
  do not ingest an incomplete batch
- `source_name` in the meta block is the public-facing label; `generated_by`
  is the audit trail — they may intentionally differ

---

*Last updated: 2026-03-19*
*See also: `how-to-use-the-LLM-guide.md`, `ingest-tool-design.md`, `legal-research-methodology.md`*