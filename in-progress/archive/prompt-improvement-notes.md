# Prompt Improvement Notes

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/prompt-improvement-notes.md`
**Based on:** v1.6.5 test run â€” Gemini vs. ChatGPT comparison (2026-03-18)

---

## Overview

This document captures required and recommended prompt improvements identified
from the v1.6.5 test run. Changes are prioritised by impact. All changes apply
to `prompt-templates/` source files and must be regenerated via
`tool-generate-prompt.php` before the next production run.

---

## Priority 1 â€” Critical (Both Assistants Failed)

### 1.1 Parent Slug Prohibition â€” Strengthen Wording

**Problem:** Both assistants used parent slugs (e.g. `public-sector`) directly
in record arrays despite the prompt stating child slugs only.

**Current wording (Section 4 header):**
> "hierarchical â€” use CHILD slugs only"

**Required addition:** Add a bolded rule block directly above the taxonomy
tables in Section 4:

```
âš  HIERARCHICAL TAXONOMY RULE â€” STRICTLY ENFORCED:
Parent slugs are structural labels only. They exist to organise the taxonomy
visually. They must NEVER appear in any record array.
Use ONLY the indented child slugs listed beneath each parent.
Placing a parent slug in a record is a schema violation.
```

---

### 1.2 Invented Slug Rule â€” Strengthen Wording

**Problem:** Both assistants placed invented slugs directly in records without
adding them to `new_terms_proposed`. The anti-fracture rule and proposal
mechanism did not fire reliably.

**Required addition:** Add to Section 2 as a standalone bolded rule:

```
âš  ZERO TOLERANCE FOR INVENTED SLUGS:
If a slug you intend to use does not appear verbatim in Section 4,
it must NOT enter the record under any circumstance.
Add it to new_terms_proposed in the meta block instead.
This applies to slugs you invent, adapt, pluralise, or approximate.
There are no exceptions. Inventing a slug and placing it in a record
without first proposing it is a schema violation that corrupts the database.
```

---

### 1.3 `record_count` Calculation Rule â€” Strengthen Wording

**Problem:** ChatGPT declared `record_count: 15` while delivering 5 records,
having calculated the field against intent rather than actual output.

**Current wording:**
> "Calculate all count keys only after their respective arrays are finalised."

**Required addition:** Make this explicit for `record_count` specifically:

```
record_count must be calculated by counting the completed records array
after the final record is written â€” not before, not from the requested
count. If you were asked for 15 and produced 12, record_count must be 12.
```

---

### 1.4 `with_errors` Honesty Rule â€” Add Explicit Triggers

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

## Priority 2 â€” Important (One or Both Assistants Failed)

### 2.1 `batch_id` â€” Clarify as Non-Conditional

**Problem:** Gemini omitted `batch_id` without flagging it.

**Required addition:** Add a note in the meta schema block:

```
batch_id is required in every batch â€” it is not conditional.
Format: "[JURISDICTION_ID]-[YYYY-MM-DDTHH:MMZ]"
Example: "CA-2026-03-18T14:30Z"
```

---

### 2.2 SPECIFIC_IMPACT â€” Reinforce Verb-First Pattern

**Problem:** Several Gemini citation entries used noun phrases instead of
action-verb patterns, violating the SPECIFIC_IMPACT format requirement.

**Required addition:** Add a negative example to the citation notes:

```
SPECIFIC_IMPACT must begin with an action verb from the approved list.
âœ“ CORRECT:   "confirms exhaustion requirement"
âœ“ CORRECT:   "defines adverse employment action"
âœ— INCORRECT: "Exhaustion of administrative remedies"  â† noun phrase, no verb
âœ— INCORRECT: "Anti-immigration retaliation scope"     â† noun phrase, no verb
```

---

### 2.3 `error_details` and `error_count` â€” Reinforce Omission Rule

**Problem:** Gemini included `error_details: []` and `error_count: 0` when
`with_errors` was false, violating the explicit omission instruction.

**Required addition:** Repeat the omission rule immediately after the
completed block schema:

```
REMINDER â€” OMISSION RULE FOR COMPLETED BLOCK:
If with_errors is false: omit error_details and error_count entirely.
Do not include them as empty values. A missing key is not the same as
an empty key â€” the ingest tool treats them differently.
```

---

## Priority 3 â€” Recommended (Quality Improvements)

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
whistleblower statutes. Omit only if genuinely unconfirmable â€”
not as a default shortcut.
```

---

## Ingest Tool Changes Required

Independent of prompt improvements, the following ingest tool validations
are required based on this test run:

### IT-1 â€” Record Count Mismatch Check

```php
if ( count( $data['records'] ) !== (int) $data['meta']['record_count'] ) {
    wp_die(
        'record_count mismatch â€” declared ' . $data['meta']['record_count'] .
        ', found ' . count( $data['records'] ) . '. Ingest aborted.'
    );
}
```

### IT-2 â€” `batch_completed` Sentinel Check

```php
if ( empty( $meta['batch_completed'] ) ) {
    wp_die( 'batch_completed sentinel missing or empty â€” ingest aborted.' );
}
```

### IT-3 â€” `completed.with_errors` Advisory Only

The ingest tool must treat `completed.with_errors` as advisory â€” never as
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
