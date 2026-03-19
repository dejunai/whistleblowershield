# AI Assistant Comparison â€” Prompt v1.6.5

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
| Record count delivered | âœ“ 15 / 15 | âœ— 5 / 15 |
| `record_count` in meta accurate | âœ“ 15 | âœ— 15 (wrong â€” only 5 delivered) |
| `batch_id` in meta | âœ— Omitted | âœ“ Present |
| `completed.with_errors` honest | âœ— False (empty arrays included) | âœ— False (incomplete batch unreported) |
| `error_details` omitted when no errors | âœ— Included as empty array | âœ“ Correctly omitted |
| `error_count` omitted when no errors | âœ— Included as 0 | âœ“ Correctly omitted |
| Field order maintained | âœ“ Consistent | âœ“ Consistent |
| Parent slug used in records | âœ— Multiple violations | âœ— Multiple violations |
| Invented slugs in records | âœ— Significant | âœ— Moderate |
| `new_terms_proposed` used correctly | âœ— Zero proposed, several needed | âœ— Zero proposed |
| Omission vs. empty rule | âœ“ Mostly correct | âœ“ Correct |
| Citation format compliance | âœ“ Mostly correct | âœ“ Correct |
| Citation SPECIFIC_IMPACT quality | Moderate | Good (limited sample) |
| Batch completion honest | âœ“ Completed | âœ— Stopped at 5, reported success |

---

## Critical Failures

### ChatGPT â€” Incomplete Batch + False Reporting

ChatGPT delivered 5 records while declaring `record_count: 15` and
`with_errors: false`. Three simultaneous integrity failures on one batch:

1. `record_count: 15` â€” calculated against intent, not actual output
2. `batch_completed` timestamp present â€” implies successful completion
3. `with_errors: false` â€” actively false given the incomplete batch

Root cause: ChatGPT calculated `record_count` before finalising the records
array, violating the explicit prompt instruction to calculate count fields
only after arrays are complete.

**Risk:** If the ingest tool trusted these fields at face value, 5 records
would enter the database flagged as a complete verified 15-record batch with
no errors â€” a silent data integrity failure with no recovery signal.

**Ingest tool mitigation:** The ingest tool must independently count the
`records` array and compare against `meta.record_count` before processing.
The assistant's `completed` block is advisory only â€” never authoritative.

### Gemini â€” Invented Slugs Without Proposing

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
than proposed â€” the anti-fracture rule failed in practice.

### Both Assistants â€” Parent Slug Violation

Both assistants used `public-sector` directly in `protected_class` arrays
across multiple records. The prompt explicitly states hierarchical taxonomies
require child slugs only. Parent slugs are organisational â€” they must never
appear in record arrays.

This is the most consistent failure across both outputs.

### Both Assistants â€” Zero Terms Proposed

Neither assistant added any entries to `new_terms_proposed` despite both
using slugs that do not exist in the taxonomy tables. The proposal mechanism
did not fire when it should have in either case.

---

## Secondary Observations

**Gemini omitted `batch_id`** from the meta block without flagging it in
`json_run_notes` or `completed`. The field is not conditional â€” it should
always be present.

**Gemini's `json_run_notes`** referenced the old citation format
("Name || Purpose || URL || Source || Priority") rather than the v1.6.5
`SPECIFIC_IMPACT` pattern. Suggests possible prior-session context bleed.

**Gemini omitted `employer_defense`** from CA-1278.5 `burden_of_proof`.
This appears to be an unintentional omission rather than correct application
of the omission rule â€” the data is confirmable for this statute.

**Citation SPECIFIC_IMPACT patterns** were followed more consistently by
ChatGPT in its limited output. Several Gemini entries used noun phrases
instead of action-verb patterns:
- *"Exhaustion of administrative remedies"* â†’ should be *"confirms exhaustion requirement"*
- *"Anti-immigration retaliation scope"* â†’ should be *"defines anti-immigration retaliation scope"*

**Citation URL verification required.** Both assistants used Google Scholar
URLs with embedded case IDs. Case names appear real but embedded IDs may be
fabricated â€” all citation URLs should be spot-checked before ingest.

---

## Conclusion

**Gemini is the better choice for production batch generation** at this stage â€”
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
