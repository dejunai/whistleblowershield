<#
.SYNOPSIS
    Generates WhistleblowerShield Core documentation files.

.DESCRIPTION
    Creates two markdown documentation files in the ./documentation/ directory
    relative to the script location (run from GitHub repo root):

        documentation/ingest-tool-design.md
        documentation/build-json-directive.md

    Safe to re-run — existing files are backed up with a datestamp suffix
    before being overwritten.

.NOTES
    Run from the root of your GitHub repository:
        .\New-WsCoreDocs.ps1

.VERSION
    1.0.0  Initial implementation
    2.0.0  Added version-synced prompt generator design; per-record-type
           schemas; version-routed ingest dispatch; ALL record count
           first-pass query; disclosure_targets taxonomy; adverse_action_types
           rename; tool/ directory structure; proposed-terms-log schema;
           plain English queue audit; source_method/source_name/generated_by
           distinction clarified
#>

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ── Resolve output directory ──────────────────────────────────────────────────

$repoRoot  = $PSScriptRoot
$outputDir = Join-Path $repoRoot 'documentation'

if ( -not (Test-Path $outputDir) ) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
    Write-Host "Created directory: $outputDir" -ForegroundColor Green
}

# ── Helper: write file with backup if it already exists ──────────────────────

function Write-DocFile {
    param(
        [string] $FileName,
        [string] $Content
    )

    $filePath = Join-Path $outputDir $FileName

    if ( Test-Path $filePath ) {
        $stamp      = Get-Date -Format 'yyyyMMdd-HHmmss'
        $backupPath = $filePath -replace '\.md$', "-backup-$stamp.md"
        Copy-Item -Path $filePath -Destination $backupPath
        Write-Host "Backed up existing: $backupPath" -ForegroundColor Yellow
    }

    Set-Content -Path $filePath -Value $Content -Encoding UTF8
    Write-Host "Written: $filePath" -ForegroundColor Cyan
}


# ══════════════════════════════════════════════════════════════════════════════
# DOCUMENT 1 — ingest-tool-design.md
# ══════════════════════════════════════════════════════════════════════════════

$ingestToolDesign = @'
# Ingest Tool Design

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/ingest-tool-design.md`
**Status:** Design specification — implementation pending

---

## Overview

The ingest pipeline is a set of PHP-based admin tools for importing structured
legal data into `ws-core` CPTs from AI-assisted JSON research files. It is a
repeatable, auditable pipeline designed to run across multiple jurisdictions,
record types, and data batches over the life of the project.

The pipeline consists of two distinct tools with clearly separated concerns:

| Tool | File | Purpose |
|------|------|---------|
| Prompt Generator | `tools/tool-generate-prompt.php` | Produces a version-synced, taxonomy-accurate Gemini directive |
| Ingest Tool | `tools/tool-ingest.php` | Processes JSON files and writes records to WordPress |

---

## Tool Directory Structure

```
ws-core/
  tools/
    tool-generate-prompt.php       ← WP admin tool: generates Gemini directive
    tool-ingest.php                ← WP admin tool: processes JSON ingest files
    ingest/
      ingest-core.php              ← Shared utilities (field map, taxonomy helpers)
      ingest-v1.5.php              ← Version handler for json_format_version 1.5
      ingest-v1.6.php              ← Version handler for json_format_version 1.6
    prompt-templates/
      template-v1.5-statute.md     ← Static instruction prose for statute records
      template-v1.5-citation.md    ← Static instruction prose for citation records
      template-v1.6-statute.md
      (etc.)
    generated-prompts/
      prompt-statute-[datestamp].md  ← Runtime output — gitignore or version
```

The directory structure is intentional — it immediately reveals the workflow.
`prompt-templates/` contains the stable human-authored prose. `generated-prompts/`
contains the runtime output ready to paste into Gemini. `ingest/` contains the
version-specific processing logic.

---

## Tool 1 — Prompt Generator (`tool-generate-prompt.php`)

### Purpose

Produces a complete, ready-to-paste Gemini directive that is always in sync
with the current state of the plugin's registered taxonomies. Prevents taxonomy
drift between what Gemini is told to use and what is actually registered in
`register-taxonomies.php`.

### Trigger

A button in the WP admin dashboard. Manual and intentional — run after any
taxonomy change. Future enhancement: git hook detecting changes to
`register-taxonomies.php`.

### Generation logic

1. Read `register-taxonomies.php` directly — the single source of truth for
   all taxonomy slugs and labels.
2. Read the appropriate `prompt-templates/template-v{X}-{record_type}.md` for
   the requested record type and format version.
3. Inject the live taxonomy tables into the template at designated placeholders.
4. Write the complete directive to `generated-prompts/prompt-{record_type}-{datestamp}.md`.
5. Display the output in the admin UI for copy/paste into Gemini.

### Version + record type matrix

Each combination of `json_format_version` and `record_type` has its own
template file. This keeps the instruction prose for each record type clean
and specific — a `jx-citation` record looks nothing like a `jx-statute` record
and should not share a schema block.

| Record Type | CPT Target | Template |
|-------------|-----------|---------|
| `statute` | `jx-statute` | `template-v{X}-statute.md` |
| `citation` | `jx-citation` | `template-v{X}-citation.md` |
| `interpretation` | `jx-interpretation` | `template-v{X}-interpretation.md` |
| `summary` | `jx-summary` | `template-v{X}-summary.md` |
| `agency` | `ws-agency` | `template-v{X}-agency.md` |
| `assist-org` | `ws-assist-org` | `template-v{X}-assist-org.md` |
| `reference` | `ws-reference` | `template-v{X}-reference.md` |

### `record_count` = ALL behavior

When `record_count` is set to `ALL` in the prompt generator UI:

1. **Do not generate the full directive immediately.**
2. Instead, issue a first-pass count query to Gemini:
   *"How many [record_type] records exist for jurisdiction [jurisdiction_id]
   that match the scope: [scope]? Return only an integer."*
3. Display a confirmation prompt to the admin:
   *"Estimated [N] records for [jurisdiction] [record_type]. This is a Gemini
   estimate and may not be exact. Proceed with full directive generation?"*
4. On confirmation, generate the directive with `record_count` set to the
   returned integer.
5. The confirmation UI must make clear the count is an estimate — Gemini's
   first-pass count is advisory, not authoritative.

This prevents accidental oversized batch runs while giving the admin a
realistic expectation of output volume before committing.

---

## Tool 2 — Ingest Tool (`tool-ingest.php`)

### Purpose

Reads a JSON ingest file, validates it against the declared `json_format_version`,
and writes records to WordPress as the appropriate CPT with correct ACF field
values, taxonomy assignments, and source/verification stamps.

### Version dispatch

The ingest tool reads `json_format_version` from the `meta` block first and
routes to the correct version handler:

```php
switch ( $meta['json_format_version'] ) {
    case '1.5': require_once 'ingest/ingest-v1.5.php'; break;
    case '1.6': require_once 'ingest/ingest-v1.6.php'; break;
    default:
        wp_die( 'Unknown json_format_version: ' . $meta['json_format_version'] );
}
```

Each version handler knows exactly which fields to expect, which field map
to apply, and which plain English fields to audit. Historical JSON files can
always be re-ingested cleanly against the version handler that was current
when they were generated.

### `ingest-core.php` — shared utilities

Contains utilities shared across all version handlers:

- `ws_ingest_set_source()` — calls `ws_set_source_method()` and `ws_set_source_name()`
- `ws_ingest_load_proposed_terms_log()` — loads and merges the proposed terms log
- `ws_ingest_save_proposed_terms_log()` — writes the updated log to disk
- `ws_ingest_build_blacklist()` — builds the proposed term blacklist from the log
- `ws_ingest_plain_english_audit()` — checks plain English fields and sets `has_plain_english`
- `ws_ingest_run_report()` — assembles and outputs the post-run report

---

## Processing Pipeline

Every ingest run executes in the following strict sequence regardless of
version or record type:

### Phase 1 — Header parse + pre-flight

1. Validate JSON structure and confirm `meta` block is present.
2. Read `json_format_version` and route to correct version handler.
3. Load `proposed-terms-log.json`.
4. Merge `new_terms_proposed` from header into the log — append to `seen_in`
   arrays for existing terms, create new entries for new proposals.
5. Build the proposed term blacklist from all `pending` entries in the log.
6. Save the updated log **before processing any records**.
7. Output pre-flight report:
   - `json_format_version`, `source_method`, `source_name`, `record_count`
   - Count and list of proposed terms found in this header
   - Count and list of proposed terms in the blacklist (including prior runs)
   - Require admin confirmation before Phase 2 (interactive mode)

### Phase 2 — Record processing

For each record:

1. Create WP post of the target CPT (determined by record type in header).
2. Call `ws_ingest_set_source()` with values from `meta` block.
3. Map JSON fields to ACF meta keys per the version handler's field map.
4. For taxonomy array fields: check each value against the blacklist.
   - If blacklisted: remove the value, log the removal (post, field, term).
   - If not blacklisted: assign via `wp_set_object_terms()`.
5. Set `verification_status` to `unverified` — never `verified` on ingest.
6. Set `needs_review` to `false`.

### Phase 3 — Plain English field audit

After each record is written:

1. Inspect every plain English eligible field for the record type.
2. If any field contains text: set `has_plain_english` to `true`.
3. Per-record output:
   - Count of plain English fields populated
   - List of each populated plain English field by name
4. Per-batch output:
   - Total records entering plain English review queue
   - Aggregated list of all affected fields across the batch

### Phase 4 — Post-run report

After all records processed:

- Records created successfully / skipped / failed (with reasons)
- Proposed terms removed: post title, field name, term value
- Plain English queue: record count and field list
- Path to updated `proposed-terms-log.json`
- Reminder: `verification_status` is `unverified` on all ingested records

---

## Proposed Terms Log

**File:** `documentation/proposed-terms-log.json`

Persistent JSON file maintained across all ingest runs. Read at Phase 1
start, written back after merge before any records are processed.

### Schema

```json
{
  "proposed_terms": [
    {
      "taxonomy":    "ws_disclosure_type",
      "term_id":     "proposed_slug",
      "term_label":  "Human Readable Label",
      "notes":       "Gemini's reasoning for inclusion.",
      "seen_in":     [ "CA-1102.5", "CA-8547" ],
      "count":       2,
      "status":      "pending",
      "resolved_on": null,
      "resolution":  null
    }
  ]
}
```

### `status` lifecycle

| Value | Meaning |
|-------|---------|
| `pending` | Awaiting human review |
| `approved` | Term added to taxonomy — run remediation pass on `seen_in` records |
| `rejected` | Term will not be added — affected records are clean as-is |
| `mapped` | Term mapped to an existing taxonomy term — remediation pass needed |

### Deduplication rule

If the same `term_id` appears in a subsequent ingest run, append new
`seen_in` values to the existing entry and increment `count`. Never create
duplicate entries for the same proposed term.

---

## Source Stamping

All ingested records receive these stamps from the `meta` block:

| Field | Source | Notes |
|-------|--------|-------|
| `source_method` | `meta.source_method` | e.g. `ai_assisted` |
| `source_name` | `meta.source_name` | e.g. `Gemini` — public-facing label |
| `verification_status` | Always `unverified` | Never overridden on ingest |
| `needs_review` | Always `false` | Set manually by admin if needed |

**`source_name` vs `generated_by`:** These serve different purposes.
`generated_by` is audit metadata (specific model version, e.g. `"Gemini 1.5 Pro"`).
`source_name` is the operational stamp written to post meta and surfaced in
the UI and methodology page (e.g. `"Gemini"`). They may differ intentionally —
`source_name` is the public-facing label, `generated_by` is the audit trail.

---

## Key Architectural Constraints

- **Shortcodes are presentation layer only.** The ingest tool writes directly
  to post meta via WP/ACF APIs — never through shortcodes.
- **`ws_set_source_method()` and `ws_set_source_name()` are immutable.**
  Call them before any subsequent save hooks fire.
- **Proposed terms are removed, not flagged in records.** Records are written
  clean. The log carries the full audit trail.
- **Plain English fields are written as-is.** No sanitization beyond standard
  WP escaping. Quality control is downstream in the plain English review process.
- **Version handlers are never modified after release.** If a field map
  needs changing, create a new version handler. Historical files must always
  be re-ingestable.
- **The prompt generator and ingest tool share `register-taxonomies.php`
  as their single source of truth.** Never hardcode taxonomy slugs in either
  tool — always read from the registered source.

---

*Last updated: 2026-03-18*
*See also: `build-json-directive.md`, `legal-research-methodology.md`, `project-status.md`*
'@


# ══════════════════════════════════════════════════════════════════════════════
# DOCUMENT 2 — build-json-directive.md
# ══════════════════════════════════════════════════════════════════════════════

$buildJsonDirective = @'
# Build JSON Directive

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/build-json-directive.md`
**Purpose:** Reference document describing the prompt template system for
             generating structured legal data JSON via Gemini.

---

## Overview

Gemini directives are **not written by hand** — they are generated at runtime
by `tools/tool-generate-prompt.php`. This document describes the system
design, the static elements that live in prompt templates, and the dynamic
elements injected at generation time.

Do not paste this document to Gemini directly. Use the prompt generator tool
to produce a ready-to-paste directive that is guaranteed to be in sync with
the current taxonomy state.

---

## Why Generated Prompts

Hardcoded directives drift. As taxonomy terms are added, renamed, or
restructured, a static directive becomes stale — Gemini invents term IDs
that look plausible but are not registered, and the ingest tool has no
clean mapping target. The prompt generator eliminates this by reading
`register-taxonomies.php` directly every time a directive is produced.

---

## Prompt Template Structure

Each `prompt-templates/template-v{X}-{record_type}.md` file contains:

1. **Static instruction prose** — the rules, field definitions, and behavioral
   directives that rarely change. Versioned — a new version file is created
   when the schema changes, not edited in place.

2. **Taxonomy table placeholders** — markers replaced at generation time with
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

3. **Schema block** — the exact JSON structure Gemini must produce for this
   record type. Record-type-specific — statute schema differs from citation
   schema, interpretation schema, etc.

---

## Static Instruction Rules (all record types)

These rules appear in every template and must not be altered between versions
unless a formal schema version increment is made:

### Output format rules

- Produce a single valid JSON object with two top-level keys: `meta` and `records`
- Maintain strict field order within `meta` and within each record as defined
  in the schema block — do not reorder fields
- Use slug-style term IDs (snake_case) for all taxonomy array values — never
  human-readable labels in array fields
- Do not add keys not present in the schema
- Do not infer data — if a field value cannot be found through research, omit
  the key entirely
- Do not use `null`, `"N/A"`, or any placeholder string to indicate missing data
- Use `""` for empty strings and `[]` for empty arrays
- Calculate `citation_count` and `proposed_count` last, after all arrays are
  finalised, to ensure accuracy
- `json_run_notes` is the only field where editorial latitude is permitted —
  all other fields are schema-constrained

### `new_terms_proposed` rules

- As you research each record, if a taxonomy value is needed that does not
  appear in the known taxonomy list for that field, do NOT include it in the
  record — add it to `new_terms_proposed` in the `meta` block instead
- This rule applies to values you invent as well as values you cannot classify —
  any slug not present in the provided taxonomy list must be proposed
- Each proposed term entry must follow the exact schema below
- If the same term appears in multiple records, list all statute identifiers
  in `seen_in` rather than creating duplicate entries
- `count` must equal the length of the `seen_in` array
- Do not propose new taxonomy tables — use `json_run_notes` to recommend one
- Do not propose new taxonomy parents — use `json_run_notes` to recommend one

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
    "record_count":        [INTEGER — calculated after records array is complete],
    "proposed_count":      [INTEGER — calculated after new_terms_proposed is complete],
    "new_terms_proposed":  [],
    "json_run_notes":      "[YOUR BATCH SUMMARY AND ANY TAXONOMY RECOMMENDATIONS]"
  }
}
```

**`source_name` vs `generated_by`:** These serve different purposes.
`source_name` is the public-facing label stamped on every post meta record
(e.g. `"Gemini"`). `generated_by` is the audit trail entry capturing the
specific model version (e.g. `"Gemini 1.5 Pro"`). They may differ
intentionally — do not collapse them into the same value.

## `new_terms_proposed` Entry Schema

```json
{
  "taxonomy":   "[TAXONOMY TABLE SLUG FROM KNOWN LIST]",
  "parent":     "[EXISTING PARENT TERM SLUG — omit if top-level]",
  "term_id":    "[YOUR PROPOSED SLUG IN snake_case]",
  "term_label": "[YOUR PROPOSED HUMAN-READABLE LABEL]",
  "notes":      "[YOUR REASONING FOR WHY THIS TERM IS NEEDED]",
  "seen_in":    [ "[STATUTE_ID]", "[SECOND_STATUTE_ID]" ],
  "count":      [INTEGER — must equal length of seen_in array]
}
```

---

## Record Schema — `statute` (json_format_version 1.6)

Field order must be maintained exactly as shown.

```json
{
  "jurisdiction_id":  "[TWO-LETTER CODE]",
  "statute_id":       "[JURISDICTION_ID]-[SECTION e.g. CA-1102.5]",
  "official_name":    "[FULL OFFICIAL STATUTE NAME]",
  "common_name":      "[PLAIN LANGUAGE COMMON NAME IF ONE EXISTS]",

  "legal_basis": {
    "statute_citation":     "[FORMAL CITATION e.g. Cal. Lab. Code § 1102.5]",
    "disclosure_types":     [ "[SLUG FROM ws_disclosure_type]" ],
    "protected_class":      [ "[SLUG FROM ws_protected_class]" ],
    "disclosure_targets":   [ "[SLUG FROM ws_disclosure_targets]" ],
    "adverse_action_scope": "[FREE TEXT — scope of actions considered adverse]"
  },

  "statute_of_limitations": {
    "limit_ambiguous":     [true | false],
    "limit_value":         [INTEGER — omit if limit_ambiguous is true],
    "limit_unit":          "[days | months | years — omit if limit_ambiguous is true]",
    "limit_details":       "[STRING — only include if limit_ambiguous is true]",
    "trigger":             "[WHAT EVENT STARTS THE CLOCK]",
    "exhaustion_required": [true | false],
    "exhaustion_details":  "[STRING — only include if exhaustion_required is true]",
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
    "rebuttable_presumption":  "[IF APPLICABLE — describe the presumption]",
    "burden_of_proof_details": "[NARRATIVE DETAILS NOT COVERED ABOVE]",
    "burden_of_proof_flag":    [true | false — true only if burden_of_proof_details is non-empty]
  },

  "reward": {
    "available":      [true | false],
    "reward_details": "[STRING — only include if available is true]"
  },

  "links": {
    "statute_url": "[URL TO OFFICIAL OR RESPECTED ALTERNATIVE SOURCE]",
    "is_official": [true | false — true only if URL is a .gov domain],
    "url_source":  "[STRING — only include if is_official is false]",
    "is_pdf":      [true | false]
  },

  "citations": {
    "attached_citations": [ "[CASE NAME || CASE URL || SOURCE]" ],
    "citation_count":     [INTEGER — must equal length of attached_citations array]
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
- Prompt templates are never edited in place after release — a new version
  file is created
- The ingest tool maintains a version handler for every released version
- Historical JSON files must always be re-ingestable against the version
  handler current at their generation date
- `json_run_notes` in the meta block is the appropriate place for Gemini
  to flag taxonomy recommendations, scope observations, or data quality notes

---

## Notes for the Researcher

- Always use the prompt generator tool — never write directives by hand
- Verify `record_count` in the returned JSON matches the actual records array
  length before running the ingest tool
- Review `new_terms_proposed` and `proposed-terms-log.json` before ingest —
  pending proposals require a resolution decision before affected taxonomy
  fields can be fully classified
- `burden_of_proof_flag` and `reward_details` being non-empty are signals to
  plan manual review of those fields post-ingest
- All non-legalese text fields enter the plain English review queue
  automatically on ingest — no action needed at import time
- `source_name` in the meta block is the public-facing label; `generated_by`
  is the audit trail — they may intentionally differ

---

*Last updated: 2026-03-18*
*See also: `ingest-tool-design.md`, `legal-research-methodology.md`*
'@


# ── Write both files ──────────────────────────────────────────────────────────

Write-DocFile -FileName 'ingest-tool-design.md'   -Content $ingestToolDesign
Write-DocFile -FileName 'build-json-directive.md' -Content $buildJsonDirective

Write-Host ''
Write-Host 'Done. Two documentation files written to ./documentation/' -ForegroundColor Green
Write-Host ''
Write-Host 'Next steps:' -ForegroundColor White
Write-Host '  1. Create documentation/proposed-terms-log.json as an empty log:'
Write-Host '     { "proposed_terms": [] }'
Write-Host '  2. Verify taxonomy table placeholder names match actual slugs'
Write-Host '     in register-taxonomies.php before building tool-generate-prompt.php'
Write-Host '  3. Add tools/ directory to ws-core plugin structure'
Write-Host '  4. Rename retaliation_forms taxonomy to adverse_action_types'
Write-Host '     in register-taxonomies.php and update all references'
Write-Host '  5. Add disclosure_targets as a new taxonomy in register-taxonomies.php'
