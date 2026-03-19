#Requires -Version 5.1
<#
.SYNOPSIS
    Generates WhistleblowerShield AI Assistant Comparison and Prompt Improvement documentation.

.DESCRIPTION
    Creates two markdown documentation files in the ./documentation/ directory:

        documentation/ai-assistant-comparison.md
        documentation/prompt-improvement-notes.md

    Safe to re-run — existing files are backed up with a datestamp suffix.

.NOTES
    Run from the root of your GitHub repository:
        .\New-WsComparisonDocs.ps1

.VERSION
    1.0.0  Initial implementation — documents v1.6.5 prompt test run findings
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
# DOCUMENT 1 — ai-assistant-comparison.md
# ══════════════════════════════════════════════════════════════════════════════

$comparisonDoc = @'
# AI Assistant Comparison — Prompt v1.6.5

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/ai-assistant-comparison.md`
**Test Date:** 2026-03-18
**Prompt Version:** 1.6.5
**Jurisdiction:** California (CA)
**Record Type:** statute
**Record Count Requested:** 15

---

## Test Conditions

Both assistants received the identical prompt (`ws-prompt-template-v1.6.5.txt`)
with no prior discussion or context. Instructions given verbatim:
*"Read prompt entirely, then execute."*

| | Gemini | ChatGPT |
|---|---|---|
| Model | Gemini 1.5 Pro | GPT-5.3 (self-reported) |
| Output file | CA-15-statutes-Gemini.json | CA-15-statutes-ChatGPT.json |

---

## Scorecard

| Dimension | Gemini | ChatGPT |
|---|---|---|
| Record count delivered | ✓ 15 / 15 | ✗ 5 / 15 |
| `record_count` in meta accurate | ✓ 15 | ✗ 15 (wrong — only 5 delivered) |
| `batch_id` in meta | ✗ Omitted | ✓ Present |
| `completed.with_errors` honest | ✗ False (empty arrays included) | ✗ False (incomplete batch unreported) |
| `error_details` omitted when no errors | ✗ Included as empty array | ✓ Correctly omitted |
| `error_count` omitted when no errors | ✗ Included as 0 | ✓ Correctly omitted |
| Field order maintained | ✓ Consistent | ✓ Consistent |
| Parent slug used in records | ✗ Multiple violations | ✗ Multiple violations |
| Invented slugs in records | ✗ Significant | ✗ Moderate |
| `new_terms_proposed` used correctly | ✗ Zero proposed, several needed | ✗ Zero proposed |
| Omission vs. empty rule | ✓ Mostly correct | ✓ Correct |
| Citation format compliance | ✓ Mostly correct | ✓ Correct |
| Citation SPECIFIC_IMPACT quality | Moderate | Good (limited sample) |
| Batch completion honest | ✓ Completed | ✗ Stopped at 5, reported success |

---

## Critical Failures

### ChatGPT — Incomplete Batch + False Reporting

ChatGPT delivered 5 records while declaring `record_count: 15` and
`with_errors: false`. Three simultaneous integrity failures on one batch:

1. `record_count: 15` — calculated against intent, not actual output
2. `batch_completed` timestamp present — implies successful completion
3. `with_errors: false` — actively false given the incomplete batch

Root cause: ChatGPT calculated `record_count` before finalising the records
array, violating the explicit prompt instruction to calculate count fields
only after arrays are complete.

**Risk:** If the ingest tool trusted these fields at face value, 5 records
would enter the database flagged as a complete verified 15-record batch with
no errors — a silent data integrity failure with no recovery signal.

**Ingest tool mitigation:** The ingest tool must independently count the
`records` array and compare against `meta.record_count` before processing.
The assistant's `completed` block is advisory only — never authoritative.

### Gemini — Invented Slugs Without Proposing

Gemini used the following slugs that do not exist in the taxonomy tables,
without adding them to `new_terms_proposed`:

| Invented Slug | Field | Record |
|---|---|---|
| `statutory-violation` | `disclosure_types` | Multiple |
| `improper-governmental-activity` | `disclosure_types` | CA-8547 |
| `abuse-of-authority` | `disclosure_types` | CA-8547 |
| `patient-safety-care` | `disclosure_types` | CA-1278.5 |
| `fraud-false-claims` | `disclosure_types` | CA-12653 |
| `k12-education` | `protected_class` | CA-44110 (should be `k12-education-staff`) |
| `refusal-to-hire` | `adverse_action` | CA-1144 |
| `threats` | `adverse_action` | CA-1019, CA-12653 |
| `damages` | `remedies` | CA-44110 |
| `representative-action` | `disclosure_targets` | CA-2699 (wrong taxonomy) |

Several of these (`statutory-violation`, `improper-governmental-activity`,
`abuse-of-authority`, `patient-safety-care`) are legally meaningful distinct
concepts that genuinely warrant taxonomy proposals. Gemini invented rather
than proposed — the anti-fracture rule failed in practice.

### Both Assistants — Parent Slug Violation

Both assistants used `public-sector` directly in `protected_class` arrays
across multiple records. The prompt explicitly states hierarchical taxonomies
require child slugs only. Parent slugs are organisational — they must never
appear in record arrays.

This is the most consistent failure across both outputs.

### Both Assistants — Zero Terms Proposed

Neither assistant added any entries to `new_terms_proposed` despite both
using slugs that do not exist in the taxonomy tables. The proposal mechanism
did not fire when it should have in either case.

---

## Secondary Observations

**Gemini omitted `batch_id`** from the meta block without flagging it in
`json_run_notes` or `completed`. The field is not conditional — it should
always be present.

**Gemini's `json_run_notes`** referenced the old citation format
("Name || Purpose || URL || Source || Priority") rather than the v1.6.5
`SPECIFIC_IMPACT` pattern. Suggests possible prior-session context bleed.

**Gemini omitted `employer_defense`** from CA-1278.5 `burden_of_proof`.
This appears to be an unintentional omission rather than correct application
of the omission rule — the data is confirmable for this statute.

**Citation SPECIFIC_IMPACT patterns** were followed more consistently by
ChatGPT in its limited output. Several Gemini entries used noun phrases
instead of action-verb patterns:
- *"Exhaustion of administrative remedies"* → should be *"confirms exhaustion requirement"*
- *"Anti-immigration retaliation scope"* → should be *"defines anti-immigration retaliation scope"*

**Citation URL verification required.** Both assistants used Google Scholar
URLs with embedded case IDs. Case names appear real but embedded IDs may be
fabricated — all citation URLs should be spot-checked before ingest.

---

## Conclusion

**Gemini is the better choice for production batch generation** at this stage —
it delivers complete batches reliably and its omission/empty rule compliance
is generally good. Its taxonomy slug invention problem is addressable through
prompt improvement.

ChatGPT's incomplete batch with false success reporting is a more dangerous
failure mode for an automated ingest pipeline than Gemini's slug invention,
because it produces no recoverable signal. Until ChatGPT demonstrates
consistent batch completion, it should not be used for production runs.

Both assistants require the prompt improvements documented in
`prompt-improvement-notes.md` before the next test run.

---

*Last updated: 2026-03-18*
*See also: `prompt-improvement-notes.md`, `ingest-tool-design.md`, `build-json-directive.md`*
'@


# ══════════════════════════════════════════════════════════════════════════════
# DOCUMENT 2 — prompt-improvement-notes.md
# ══════════════════════════════════════════════════════════════════════════════

$promptImprovementDoc = @'
# Prompt Improvement Notes

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/prompt-improvement-notes.md`
**Based on:** v1.6.5 test run — Gemini vs. ChatGPT comparison (2026-03-18)

---

## Overview

This document captures required and recommended prompt improvements identified
from the v1.6.5 test run. Changes are prioritised by impact. All changes apply
to `prompt-templates/` source files and must be regenerated via
`tool-generate-prompt.php` before the next production run.

---

## Priority 1 — Critical (Both Assistants Failed)

### 1.1 Parent Slug Prohibition — Strengthen Wording

**Problem:** Both assistants used parent slugs (e.g. `public-sector`) directly
in record arrays despite the prompt stating child slugs only.

**Current wording (Section 4 header):**
> "hierarchical — use CHILD slugs only"

**Required addition:** Add a bolded rule block directly above the taxonomy
tables in Section 4:

```
⚠ HIERARCHICAL TAXONOMY RULE — STRICTLY ENFORCED:
Parent slugs are structural labels only. They exist to organise the taxonomy
visually. They must NEVER appear in any record array.
Use ONLY the indented child slugs listed beneath each parent.
Placing a parent slug in a record is a schema violation.
```

---

### 1.2 Invented Slug Rule — Strengthen Wording

**Problem:** Both assistants placed invented slugs directly in records without
adding them to `new_terms_proposed`. The anti-fracture rule and proposal
mechanism did not fire reliably.

**Required addition:** Add to Section 2 as a standalone bolded rule:

```
⚠ ZERO TOLERANCE FOR INVENTED SLUGS:
If a slug you intend to use does not appear verbatim in Section 4,
it must NOT enter the record under any circumstance.
Add it to new_terms_proposed in the meta block instead.
This applies to slugs you invent, adapt, pluralise, or approximate.
There are no exceptions. Inventing a slug and placing it in a record
without first proposing it is a schema violation that corrupts the database.
```

---

### 1.3 `record_count` Calculation Rule — Strengthen Wording

**Problem:** ChatGPT declared `record_count: 15` while delivering 5 records,
having calculated the field against intent rather than actual output.

**Current wording:**
> "Calculate all count keys only after their respective arrays are finalised."

**Required addition:** Make this explicit for `record_count` specifically:

```
record_count must be calculated by counting the completed records array
after the final record is written — not before, not from the requested
count. If you were asked for 15 and produced 12, record_count must be 12.
```

---

### 1.4 `with_errors` Honesty Rule — Add Explicit Triggers

**Problem:** ChatGPT reported `with_errors: false` despite delivering an
incomplete batch. No errors were flagged.

**Required addition:** Add explicit `with_errors = true` trigger conditions:

```
Set with_errors = true and populate error_details if ANY of the following occur:
  - You were unable to produce the full requested record_count
  - A statute could not be researched with sufficient confidence
  - A required field could not be populated for any record
  - Any schema rule was knowingly violated
  - A citation could not be verified

Do not report with_errors = false if the batch is incomplete for any reason.
Honest error reporting is more valuable than a clean-looking output.
```

---

## Priority 2 — Important (One or Both Assistants Failed)

### 2.1 `batch_id` — Clarify as Non-Conditional

**Problem:** Gemini omitted `batch_id` without flagging it.

**Required addition:** Add a note in the meta schema block:

```
batch_id is required in every batch — it is not conditional.
Format: "[JURISDICTION_ID]-[YYYY-MM-DDTHH:MMZ]"
Example: "CA-2026-03-18T14:30Z"
```

---

### 2.2 SPECIFIC_IMPACT — Reinforce Verb-First Pattern

**Problem:** Several Gemini citation entries used noun phrases instead of
action-verb patterns, violating the SPECIFIC_IMPACT format requirement.

**Required addition:** Add a negative example to the citation notes:

```
SPECIFIC_IMPACT must begin with an action verb from the approved list.
✓ CORRECT:   "confirms exhaustion requirement"
✓ CORRECT:   "defines adverse employment action"
✗ INCORRECT: "Exhaustion of administrative remedies"  ← noun phrase, no verb
✗ INCORRECT: "Anti-immigration retaliation scope"     ← noun phrase, no verb
```

---

### 2.3 `error_details` and `error_count` — Reinforce Omission Rule

**Problem:** Gemini included `error_details: []` and `error_count: 0` when
`with_errors` was false, violating the explicit omission instruction.

**Required addition:** Repeat the omission rule immediately after the
completed block schema:

```
REMINDER — OMISSION RULE FOR COMPLETED BLOCK:
If with_errors is false: omit error_details and error_count entirely.
Do not include them as empty values. A missing key is not the same as
an empty key — the ingest tool treats them differently.
```

---

## Priority 3 — Recommended (Quality Improvements)

### 3.1 Citation URL Verification Warning

Add a note to the citation rules:

```
Citation URLs must link to publicly accessible, verifiable sources.
Do not fabricate or approximate URLs. If a reliable URL cannot be
confirmed, omit the URL field and note the citation as unverified
in json_run_notes. Unverified citations are preferable to false URLs.
```

### 3.2 `json_run_notes` Format Guidance

Add guidance to prevent stale format references:

```
json_run_notes should reflect the current batch only. Do not reference
prior prompt versions, prior format names, or prior session context.
```

### 3.3 `employer_defense` Expected for Most Statutes

Add a note to the `burden_of_proof` schema:

```
employer_defense is expected to be present for the majority of
whistleblower statutes. Omit only if genuinely unconfirmable —
not as a default shortcut.
```

---

## Ingest Tool Changes Required

Independent of prompt improvements, the following ingest tool validations
are required based on this test run:

### IT-1 — Record Count Mismatch Check

```php
if ( count( $data['records'] ) !== (int) $data['meta']['record_count'] ) {
    wp_die(
        'record_count mismatch — declared ' . $data['meta']['record_count'] .
        ', found ' . count( $data['records'] ) . '. Ingest aborted.'
    );
}
```

### IT-2 — `batch_completed` Sentinel Check

```php
if ( empty( $meta['batch_completed'] ) ) {
    wp_die( 'batch_completed sentinel missing or empty — ingest aborted.' );
}
```

### IT-3 — `completed.with_errors` Advisory Only

The ingest tool must treat `completed.with_errors` as advisory — never as
authoritative. Run independent validation regardless of its value. If the
tool's own validation detects issues, abort and log regardless of what the
assistant reported.

---

## Change Summary for Next Prompt Version (v1.7.0)

| # | Section | Change | Priority |
|---|---|---|---|
| 1.1 | Section 4 header | Parent slug prohibition block | Critical |
| 1.2 | Section 2 | Zero tolerance invented slug rule | Critical |
| 1.3 | Section 1 | `record_count` explicit calculation rule | Critical |
| 1.4 | Section 3 completed | `with_errors` trigger conditions | Critical |
| 2.1 | Section 3 meta | `batch_id` non-conditional note | Important |
| 2.2 | Section 3 citations | SPECIFIC_IMPACT negative examples | Important |
| 2.3 | Section 3 completed | Omission rule reminder | Important |
| 3.1 | Section 3 citations | URL verification warning | Recommended |
| 3.2 | Section 3 meta | `json_run_notes` format guidance | Recommended |
| 3.3 | Section 3 burden | `employer_defense` expected note | Recommended |

---

*Last updated: 2026-03-18*
*See also: `ai-assistant-comparison.md`, `build-json-directive.md`, `ingest-tool-design.md`*
'@


# ── Write both files ──────────────────────────────────────────────────────────

Write-DocFile -FileName 'ai-assistant-comparison.md'   -Content $comparisonDoc
Write-DocFile -FileName 'prompt-improvement-notes.md'  -Content $promptImprovementDoc

Write-Host ''
Write-Host 'Done. Two documentation files written to ./documentation/' -ForegroundColor Green
Write-Host ''
Write-Host 'Next steps:' -ForegroundColor White
Write-Host '  1. Apply Priority 1 changes to prompt-templates/ before next run'
Write-Host '  2. Add IT-1, IT-2, IT-3 validation checks to ingest-tool-design.md'
Write-Host '  3. Spot-check citation URLs from both JSON files before any ingest'
Write-Host '  4. Consider Gemini for production runs — ChatGPT needs further testing'
