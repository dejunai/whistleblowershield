# Ingest Tool Design

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/ingest-tool-design.md`
**Status:** Design specification â€” implementation pending

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
    tool-generate-prompt.php       â† WP admin tool: generates Gemini directive
    tool-ingest.php                â† WP admin tool: processes JSON ingest files
    ingest/
      ingest-core.php              â† Shared utilities (field map, taxonomy helpers)
      ingest-v1.5.php              â† Version handler for json_format_version 1.5
      ingest-v1.6.php              â† Version handler for json_format_version 1.6
    prompt-templates/
      template-v1.5-statute.md     â† Static instruction prose for statute records
      template-v1.5-citation.md    â† Static instruction prose for citation records
      template-v1.6-statute.md
      (etc.)
    generated-prompts/
      prompt-statute-[datestamp].md  â† Runtime output â€” gitignore or version
```

The directory structure is intentional â€” it immediately reveals the workflow.
`prompt-templates/` contains the stable human-authored prose. `generated-prompts/`
contains the runtime output ready to paste into Gemini. `ingest/` contains the
version-specific processing logic.

---

## Tool 1 â€” Prompt Generator (`tool-generate-prompt.php`)

### Purpose

Produces a complete, ready-to-paste Gemini directive that is always in sync
with the current state of the plugin's registered taxonomies. Prevents taxonomy
drift between what Gemini is told to use and what is actually registered in
`register-taxonomies.php`.

### Trigger

A button in the WP admin dashboard. Manual and intentional â€” run after any
taxonomy change. Future enhancement: git hook detecting changes to
`register-taxonomies.php`.

### Generation logic

1. Read `register-taxonomies.php` directly â€” the single source of truth for
   all taxonomy slugs and labels.
2. Read the appropriate `prompt-templates/template-v{X}-{record_type}.md` for
   the requested record type and format version.
3. Inject the live taxonomy tables into the template at designated placeholders.
4. Write the complete directive to `generated-prompts/prompt-{record_type}-{datestamp}.md`.
5. Display the output in the admin UI for copy/paste into Gemini.

### Version + record type matrix

Each combination of `json_format_version` and `record_type` has its own
template file. This keeps the instruction prose for each record type clean
and specific â€” a `jx-citation` record looks nothing like a `jx-statute` record
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
5. The confirmation UI must make clear the count is an estimate â€” Gemini's
   first-pass count is advisory, not authoritative.

This prevents accidental oversized batch runs while giving the admin a
realistic expectation of output volume before committing.

---

## Tool 2 â€” Ingest Tool (`tool-ingest.php`)

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

### `ingest-core.php` â€” shared utilities

Contains utilities shared across all version handlers:

- `ws_ingest_set_source()` â€” calls `ws_set_source_method()` and `ws_set_source_name()`
- `ws_ingest_load_proposed_terms_log()` â€” loads and merges the proposed terms log
- `ws_ingest_save_proposed_terms_log()` â€” writes the updated log to disk
- `ws_ingest_build_blacklist()` â€” builds the proposed term blacklist from the log
- `ws_ingest_plain_english_audit()` â€” checks plain English fields and sets `has_plain_english`
- `ws_ingest_run_report()` â€” assembles and outputs the post-run report

---

## Processing Pipeline

Every ingest run executes in the following strict sequence regardless of
version or record type:

### Phase 1 â€” Header parse + pre-flight

1. Validate JSON structure and confirm `meta` block is present.
2. Read `json_format_version` and route to correct version handler.
3. Load `proposed-terms-log.json`.
4. Merge `new_terms_proposed` from header into the log â€” append to `seen_in`
   arrays for existing terms, create new entries for new proposals.
5. Build the proposed term blacklist from all `pending` entries in the log.
6. Save the updated log **before processing any records**.
7. Output pre-flight report:
   - `json_format_version`, `source_method`, `source_name`, `record_count`
   - Count and list of proposed terms found in this header
   - Count and list of proposed terms in the blacklist (including prior runs)
   - Require admin confirmation before Phase 2 (interactive mode)

### Phase 2 â€” Record processing

For each record:

1. Create WP post of the target CPT (determined by record type in header).
2. Call `ws_ingest_set_source()` with values from `meta` block.
3. Map JSON fields to ACF meta keys per the version handler's field map.
4. For taxonomy array fields: check each value against the blacklist.
   - If blacklisted: remove the value, log the removal (post, field, term).
   - If not blacklisted: assign via `wp_set_object_terms()`.
5. Set `verification_status` to `unverified` â€” never `verified` on ingest.
6. Set `needs_review` to `false`.

### Phase 3 â€” Plain English field audit

After each record is written:

1. Inspect every plain English eligible field for the record type.
2. If any field contains text: set `has_plain_english` to `true`.
3. Per-record output:
   - Count of plain English fields populated
   - List of each populated plain English field by name
4. Per-batch output:
   - Total records entering plain English review queue
   - Aggregated list of all affected fields across the batch

### Phase 4 â€” Post-run report

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
| `approved` | Term added to taxonomy â€” run remediation pass on `seen_in` records |
| `rejected` | Term will not be added â€” affected records are clean as-is |
| `mapped` | Term mapped to an existing taxonomy term â€” remediation pass needed |

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
| `source_name` | `meta.source_name` | e.g. `Gemini` â€” public-facing label |
| `verification_status` | Always `unverified` | Never overridden on ingest |
| `needs_review` | Always `false` | Set manually by admin if needed |

**`source_name` vs `generated_by`:** These serve different purposes.
`generated_by` is audit metadata (specific model version, e.g. `"Gemini 1.5 Pro"`).
`source_name` is the operational stamp written to post meta and surfaced in
the UI and methodology page (e.g. `"Gemini"`). They may differ intentionally â€”
`source_name` is the public-facing label, `generated_by` is the audit trail.

---

## Key Architectural Constraints

- **Shortcodes are presentation layer only.** The ingest tool writes directly
  to post meta via WP/ACF APIs â€” never through shortcodes.
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
  tool â€” always read from the registered source.

---

*Last updated: 2026-03-18*
*See also: `build-json-directive.md`, `legal-research-methodology.md`, `project-status.md`*
