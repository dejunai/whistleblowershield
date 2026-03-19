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
| Prompt Generator | `tools/tool-generate-prompt.php` | Produces a version-synced, taxonomy-accurate AI assistant directive |
| Ingest Tool | `tools/tool-ingest.php` | Processes JSON files and writes records to WordPress |

---

## Tool Directory Structure

```
ws-core/
  tools/
    tool-generate-prompt.php       ← WP admin tool: generates AI assistant directive
    tool-ingest.php                ← WP admin tool: processes JSON ingest files
    ingest/
      ingest-core.php              ← Shared utilities (field map, taxonomy helpers)
      ingest-v1.5.php              ← Version handler for json_format_version 1.5
      ingest-v1.6.php              ← Version handler for json_format_version 1.6
      ingest-v1.6.5.php            ← Version handler for json_format_version 1.6.5
    prompt-templates/
      template-v1.6.5-statute.md   ← Static instruction prose for statute records
      template-v1.6.5-citation.md  ← Static instruction prose for citation records
      (etc.)
    generated-prompts/
      prompt-statute-[datestamp].md  ← Runtime output — gitignore or version
```

The directory structure is intentional — it immediately reveals the workflow.
`prompt-templates/` contains the stable human-authored prose. `generated-prompts/`
contains the runtime output ready to paste into the AI assistant. `ingest/`
contains the version-specific processing logic.

---

## Tool 1 — Prompt Generator (`tool-generate-prompt.php`)

### Purpose

Produces a complete, ready-to-paste AI assistant directive that is always in
sync with the current state of the plugin's registered taxonomies. Prevents
taxonomy drift between what the assistant is told to use and what is actually
registered in `register-taxonomies.php`.

The prompt generator is AI-assistant-agnostic — the generated directive is
designed to work with any capable AI assistant (Gemini, ChatGPT, Claude, etc.).
See `ai-assistant-comparison.md` for current production recommendation.

### Trigger

A button in the WP admin dashboard. Manual and intentional — run after any
taxonomy change. Future enhancement: git hook detecting changes to
`register-taxonomies.php`.

### Generation Logic

1. Read `register-taxonomies.php` directly — the single source of truth for
   all taxonomy slugs and labels.
2. Read the appropriate `prompt-templates/template-v{X}-{record_type}.md` for
   the requested record type and format version.
3. Inject the live taxonomy tables into the template at designated placeholders.
4. Write the complete directive to `generated-prompts/prompt-{record_type}-{datestamp}.md`.
5. Display the output in the admin UI for copy/paste into the AI assistant.

### Version + Record Type Matrix

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

### `record_count` = ALL Behavior

When `record_count` is set to `ALL` in the prompt generator UI:

1. **Do not generate the full directive immediately.**
2. Instead, issue a first-pass count query to the AI assistant:
   *"How many [record_type] records exist for jurisdiction [jurisdiction_id]
   that match the scope: [scope]? Return only an integer."*
3. Display a confirmation prompt to the admin:
   *"Estimated [N] records for [jurisdiction] [record_type]. This is an AI
   estimate and may not be exact. Proceed with full directive generation?"*
4. On confirmation, generate the directive with `record_count` set to the
   returned integer.
5. The confirmation UI must make clear the count is an estimate — the
   first-pass count is advisory, not authoritative.

---

## Tool 2 — Ingest Tool (`tool-ingest.php`)

### Purpose

Reads a JSON ingest file, validates it against the declared `json_format_version`,
and writes records to WordPress as the appropriate CPT with correct ACF field
values, taxonomy assignments, and source/verification stamps.

### Assistant Output is Advisory — Ingest Tool is Authoritative

The AI assistant's `completed` block (including `with_errors`, `record_count`,
and `batch_completed`) is treated as advisory metadata only. The ingest tool
runs its own independent validation pass regardless of what the assistant
reported. A clean-looking `completed` block does not bypass validation.

This design decision is based on observed assistant behavior — see
`ai-assistant-comparison.md` for documented failure modes.

### Version Dispatch

The ingest tool reads `json_format_version` from the `meta` block first and
routes to the correct version handler:

```php
switch ( $meta['json_format_version'] ) {
    case '1.5':   require_once 'ingest/ingest-v1.5.php';   break;
    case '1.6':   require_once 'ingest/ingest-v1.6.php';   break;
    case '1.6.5': require_once 'ingest/ingest-v1.6.5.php'; break;
    default:
        wp_die( 'Unknown json_format_version: ' . $meta['json_format_version'] );
}
```

Each version handler knows exactly which fields to expect, which field map
to apply, and which plain English fields to audit. Historical JSON files can
always be re-ingested cleanly against the version handler that was current
when they were generated.

### `ingest-core.php` — Shared Utilities

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

### Phase 1 — Pre-Flight Validation

Before any records are processed, the ingest tool runs independent validation.
These checks are non-negotiable and cannot be bypassed by assistant output.

**IT-1 — `batch_completed` Sentinel Check**

```php
if ( empty( $meta['batch_completed'] ) ) {
    wp_die( 'batch_completed sentinel missing or empty — ingest aborted.' );
}
```

The `batch_completed` key must be present and non-empty in the `completed`
block. Its absence means the assistant did not reach the end of its output —
the batch is truncated or incomplete regardless of any other fields.

**IT-2 — Record Count Integrity Check**

```php
if ( count( $data['records'] ) !== (int) $data['meta']['record_count'] ) {
    wp_die(
        'record_count mismatch — declared ' . $data['meta']['record_count'] .
        ', found ' . count( $data['records'] ) . '. Ingest aborted.'
    );
}
```

The ingest tool independently counts the `records` array and compares it
against `meta.record_count`. A mismatch aborts the run. The assistant's
declared count is never trusted without verification.

**IT-3 — `completed.with_errors` Advisory Check**

```php
// Log the assistant's self-report but do not gate on it.
// The ingest tool's own validation is the authoritative error source.
$assistant_reported_errors = ! empty( $completed['with_errors'] );
if ( $assistant_reported_errors ) {
    ws_ingest_log_warning(
        'Assistant reported with_errors = true. Details: ' .
        implode( '; ', $completed['error_details'] ?? [] )
    );
    // Optionally: prompt admin to confirm before proceeding.
}
```

If the assistant reported `with_errors: true`, surface the `error_details`
to the admin and optionally require confirmation before proceeding. The ingest
tool continues its own validation regardless.

**Additional Pre-Flight Checks**

After the three primary checks, Phase 1 continues:

1. Validate JSON structure and confirm `meta` block is present.
2. Route to correct version handler via `json_format_version`.
3. Load `proposed-terms-log.json`.
4. Merge `new_terms_proposed` from header into the log — append to `seen_in`
   arrays for existing terms, create new entries for new proposals.
5. Build the proposed term blacklist from all `pending` entries in the log.
6. Save the updated log **before processing any records**.
7. Output pre-flight report:
   - `json_format_version`, `source_method`, `source_name`
   - Declared `record_count` vs. actual array count
   - `batch_completed` timestamp
   - Assistant `with_errors` self-report and any `error_details`
   - Count and list of proposed terms found in this header
   - Count and list of proposed terms in the blacklist (including prior runs)
   - Require admin confirmation before Phase 2 (interactive mode)

### Phase 2 — Record Processing

For each record:

1. Create WP post of the target CPT (determined by record type in header).
2. Call `ws_ingest_set_source()` with values from `meta` block.
3. Map JSON fields to ACF meta keys per the version handler's field map.
4. For taxonomy array fields: check each value against the blacklist.
   - If blacklisted: remove the value, log the removal (post, field, term).
   - If not blacklisted: assign via `wp_set_object_terms()`.
5. Set `verification_status` to `unverified` — never `verified` on ingest.
6. Set `needs_review` to `false`.

### Phase 3 — Plain English Field Audit

After each record is written:

1. Inspect every plain English eligible field for the record type.
2. If any field contains text: set `has_plain_english` to `true`.
3. Per-record output:
   - Count of plain English fields populated
   - List of each populated plain English field by name
4. Per-batch output:
   - Total records entering plain English review queue
   - Aggregated list of all affected fields across the batch

### Phase 4 — Post-Run Report

After all records are processed:

- Records created successfully / skipped / failed (with reasons)
- Proposed terms removed: post title, field name, term value
- Plain English queue: record count and field list
- Path to updated `proposed-terms-log.json`
- Reminder: `verification_status` is `unverified` on all ingested records
- Summary of any discrepancies between assistant `completed` block and
  ingest tool's own validation findings

---

## Proposed Terms Log

**File:** `documentation/proposed-terms-log.json`

Persistent JSON file maintained across all ingest runs. Read at Phase 1
start, written back after merge before any records are processed.
Initialise as `{ "proposed_terms": [] }` before first run.

### Schema

```json
{
  "proposed_terms": [
    {
      "taxonomy":    "ws_disclosure_type",
      "term_id":     "proposed-slug",
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

### `status` Lifecycle

| Value | Meaning |
|-------|---------|
| `pending` | Awaiting human review |
| `approved` | Term added to taxonomy — run remediation pass on `seen_in` records |
| `rejected` | Term will not be added — affected records are clean as-is |
| `mapped` | Term mapped to an existing taxonomy term — remediation pass needed |

### Deduplication Rule

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
- **The assistant's `completed` block is advisory only.** The ingest tool's
  own validation is authoritative. IT-1, IT-2, and IT-3 checks run on every
  ingest regardless of what the assistant reported.

---

## AI Assistant Selection

See `ai-assistant-comparison.md` for the current production recommendation
based on comparative testing. The prompt generator produces an
assistant-agnostic directive — switching assistants requires no changes to
the ingest tool or prompt templates.

---

*Last updated: 2026-03-18*
*See also: `build-json-directive.md`, `ai-assistant-comparison.md`,*
*`prompt-improvement-notes.md`, `legal-research-methodology.md`, `project-status.md`*
'@



# ── Write file ────────────────────────────────────────────────────────────────

$repoRoot  = $PSScriptRoot
$outputDir = Join-Path $repoRoot 'documentation'

if ( -not (Test-Path $outputDir) ) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
    Write-Host "Created directory: $outputDir" -ForegroundColor Green
}

$filePath = Join-Path $outputDir 'ingest-tool-design.md'
if ( Test-Path $filePath ) {
    $stamp      = Get-Date -Format 'yyyyMMdd-HHmmss'
    $backupPath = $filePath -replace '\.md$', "-backup-$stamp.md"
    Copy-Item -Path $filePath -Destination $backupPath
    Write-Host "Backed up existing: $backupPath" -ForegroundColor Yellow
}
Set-Content -Path $filePath -Value $ingestToolDesign -Encoding UTF8
Write-Host "Written: $filePath" -ForegroundColor Cyan

Write-Host ''
Write-Host 'Done. ingest-tool-design.md updated in ./documentation/' -ForegroundColor Green
Write-Host ''
Write-Host 'Changes from prior version:' -ForegroundColor White
Write-Host '  - Added IT-1: batch_completed sentinel check'
Write-Host '  - Added IT-2: record_count integrity check'
Write-Host '  - Added IT-3: with_errors advisory handling'
Write-Host '  - Added assistant advisory note to Phase 1'
Write-Host '  - Added AI assistant selection section'
Write-Host '  - Added ingest-v1.6.5.php to tool directory structure'
Write-Host '  - Updated prompt generator wording to AI-assistant-agnostic'
