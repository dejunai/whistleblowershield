# JSON Schema Reference

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/json-schema-reference.md`
**Purpose:** Canonical reference for the JSON data structure used by the
             prompt generator, ingest tool, and version handlers.

---

## Current Version: 1.9

---

## `meta` Block
```json
{
  "meta": {
    "json_format_version": "",
    "source_method":       "",
    "source_name":         "",
    "jurisdiction_id":     "",
    "generated_date":      "",
    "generated_by":        "",
    "record_count":        0,
    "proposed_count":      0,
    "new_terms_proposed":  [],
    "json_run_notes":      "",
    "batch_completed":     ""
  }
}
```

### Field Notes

**`json_format_version`** — String. Increment when the record schema changes.
Prompt templates and the ingest tool version handler are both keyed to this value.

**`source_method`** — String. Currently always `"ai_assisted"`. Reserved for
future expansion if research methods diversify.

**`source_name`** — String. Public-facing label stamped on every post meta
record. Short common name only (e.g. `"Gemini"`).

**`generated_by`** — String. Audit trail entry. Full model name and version
(e.g. `"Gemini 1.5 Pro"`). May differ from `source_name` intentionally —
do not collapse.

**`record_count`** — Integer. Must equal the actual length of the `records`
array. Calculated last.

**`proposed_count`** — Integer. Must equal the actual length of
`new_terms_proposed`. Calculated last.

**`new_terms_proposed`** — Array. Empty array `[]` if no proposals. See
schema below.

**`json_run_notes`** — String. Free-form. The only field in the meta block
without a structural constraint on its content.

**`batch_completed`** — String. Format: `"YYYY-MM-DD HH:MM UTC"`. Written
last. The ingest tool treats its presence as a completion sentinel — absent
or empty aborts ingest.

---

## `new_terms_proposed` Entry Schema
```json
{
  "taxonomy":   "",
  "parent":     "",
  "term_id":    "",
  "term_label": "",
  "notes":      "",
  "seen_in":    [],
  "count":      0
}
```

### Field Notes

**`taxonomy`** — String. Must match a registered taxonomy table slug.

**`parent`** — String. Must match an existing registered parent term slug.
Omit entirely if the proposed term is top-level.

**`term_id`** — String. snake_case. Proposed slug only — not active until
a human resolves the proposal.

**`count`** — Integer. Must equal the length of `seen_in`. Calculated last.

---

## Record Schema — `statute`

Field order is significant — the ingest tool maps fields positionally.
Do not reorder.
```json
{
  "jurisdiction_id":  "",
  "statute_id":       "",
  "official_name":    "",
  "common_name":      "",

  "legal_basis": {
    "statute_citation":     "",
    "disclosure_types":     [],
    "protected_class":      [],
    "disclosure_targets":   [],
    "adverse_action_scope": ""
  },

  "statute_of_limitations": {
    "limit_ambiguous":     false,
    "limit_value":         0,
    "limit_unit":          "",
    "limit_details":       "",
    "trigger":             "",
    "exhaustion_required": false,
    "exhaustion_details":  "",
    "tolling_notes":       ""
  },

  "enforcement": {
    "primary_agency": "",
    "process_type":   [],
    "adverse_action": [],
    "remedies":       [],
    "fee_shifting":   []
  },

  "burden_of_proof": {
    "employee_standard":       "",
    "employer_defense":        "",
    "rebuttable_presumption":  "",
    "burden_of_proof_details": "",
    "burden_of_proof_flag":    false
  },

  "reward": {
    "available":      false,
    "reward_details": ""
  },

  "links": {
    "statute_url": "",
    "is_official": false,
    "url_source":  "",
    "is_pdf":      false
  },

  "citations": {
    "attached_citations": [],
    "citation_count":     0
  },
  
  "_review_notes": ""
  
} // close record
```

### Field Notes

**`statute_id`** — String. Format: `[JURISDICTION_ID]-[SECTION]`
e.g. `"CA-1102.5"`.

**`common_name`** — String. Omit entirely if no plain-language name exists.

**`limit_ambiguous`** — Boolean. When `true`: omit `limit_value` and
`limit_unit`, include `limit_details`. When `false`: include `limit_value`
and `limit_unit`, omit `limit_details`.

**`exhaustion_details`** — String. Omit entirely when `exhaustion_required`
is `false`.

**`tolling_notes`** — String. Omit entirely when no provisions are identified.

**`burden_of_proof_flag`** — Boolean. `true` only when
`burden_of_proof_details` is non-empty. These two fields move together.

**`reward_details`** — String. Omit entirely when `available` is `false`.

**`statute_url`** — String. Omit entirely when no trustworthy source is
identified. An empty URL field is correct output.

**`url_source`** — String. Omit entirely when `is_official` is `true` or
when no URL is present.

**`citation_count`** — Integer. Must equal the length of
`attached_citations`. Calculated last.

**`_review_notes`** — String. Plain-English orientation for the human reviewer.
Two to four sentences: what the statute covers, who it protects, and any flag
worth knowing before field-by-field review begins. Not ingested — the ingest
tool strips all keys prefixed with `_` without further logic.

---

## Taxonomy Fields

The following fields accept only registered term slugs. Any value not present
in the registered taxonomy for that field is invalid.

| Field | Taxonomy |
|---|---|
| `disclosure_types` | `ws_disclosure_type` |
| `protected_class` | `ws_protected_class` |
| `disclosure_targets` | `ws_disclosure_targets` |
| `process_type` | `ws_process_type` |
| `adverse_action` | `ws_adverse_action_types` |
| `remedies` | `ws_remedies` |
| `fee_shifting` | `ws_fee_shifting` |

---

## Versioning Policy

- Increment `json_format_version` when any field is added, removed, renamed,
  or has its conditionality changed
- Prompt templates are never edited in place after release — a new version
  file is created
- The ingest tool maintains a version handler for every released version
- Historical JSON files must remain re-ingestable against the version handler
  current at their generation date

---

*Last updated: 2026-03-19*
*See also: `build-json-directive.md`, `ingest-tool-design.md`*