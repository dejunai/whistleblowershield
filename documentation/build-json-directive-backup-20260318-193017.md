# Build JSON Directive

**Project:** WhistleblowerShield Core (`ws-core`)
**File:** `documentation/build-json-directive.md`
**Purpose:** Verbatim prompt template to issue to an AI Assitant when generating
             structured legal data JSON for the ingest tool at WhistleblowerShield.org.

---

## How to Use This Document

Copy the prompt block below verbatim and paste it to an AI Assitant before providing
the jurisdiction and statute scope. Substitute the bracketed placeholders
before sending. Do not paraphrase or summarize the instructions an AI Assitant
performs best with the full directive intact.

---

## Prompt Template

---

I am an amateur legal data researcher generating structured JSON for import into a
whistleblower protection legal reference database. Your output must conform
exactly to the schema and rules below. Do not summarize, paraphrase, or
abbreviate these instructions.

### Output format

Produce a single valid JSON object with two top-level keys: `meta` and `records`.

### `meta` block

The `meta` block must appear first and must contain the following keys:

```json
{
  "meta": {
    "source_method":      "ai_assisted",
    "source_name":        "Gemini",
    "jurisdiction_id":    "[TWO-LETTER JURISDICTION CODE, e.g. CA]",
    "generated_date":     "[TODAY'S DATE IN YYYY-MM-DD FORMAT]",
    "generated_by":       "[YOUR MODEL NAME AND VERSION]",
    "record_count":       [INTEGER COUNT OF RECORDS IN THIS BATCH],
    "new_terms_proposed": []
  }
}
```

### `new_terms_proposed` â€” taxonomy proposals

As you research each statute, you will encounter classification values that
do not fit cleanly into the known taxonomy lists provided below. When this
occurs:

1. Do NOT include the unrecognized term in the record itself.
2. Add an entry to `new_terms_proposed` in the `meta` block.
3. Each entry must follow this exact structure:

```json
{
  "taxonomy":  "[TAXONOMY NAME FROM THE LIST BELOW]",
  "parent":    "[EXISTING PARENT TERM IF ANY]"
  "term":      "[YOUR PROPOSED TERM LABEL]",
  "notes":     "[YOUR REASONING FOR WHY THIS TERM IS NEEDED]",
  "seen_in":   [ "[STATUTE IDENTIFIER, e.g. CA-1102.5]" ]
}
```

4. If the same proposed term appears in multiple statutes, list all statute
   identifiers in the `seen_in` array rather than creating duplicate entries.
5. If no new terms are needed, `new_terms_proposed` must be an empty array `[]`.

### Known taxonomy lists

Use only values from these lists when populating taxonomy fields in records.
Any value not present in a list must be proposed via `new_terms_proposed`.

**`disclosure_types`**
- Violation of Law
- Refusal of Illegal Activity
- Internal Reporting
- External Reporting
- Improper Governmental Activity
- Waste
- Fraud
- Abuse of Authority
- Health or Safety Threats
- Unsafe Patient Care
- Facility Conditions
- Quality of Care Grievances
- Environmental Violation
- Financial Fraud
- Securities Violation

**`sol_unit`**
- days
- months
- years

**`protected_class`** *(free text â€” no fixed taxonomy, populate as found)*

### `records` array

Each record in the `records` array must follow this exact schema. Omit a key
entirely if no data is available â€” do not use null, empty strings, or
placeholder text.

```json
{
  "jurisdiction_id":  "[TWO-LETTER CODE]",
  "statute_id":       "[JURISDICTION_ID]-[SECTION, e.g. CA-1102.5]",
  "official_name":    "[FULL OFFICIAL STATUTE NAME]",
  "common_name":      "[PLAIN LANGUAGE COMMON NAME IF ONE EXISTS]",

  "legal_basis": {
    "statute_citation":  "[FORMAL CITATION, e.g. Cal. Lab. Code Â§ 1102.5]",
    "disclosure_types":  [ "[FROM KNOWN LIST ONLY]" ],
    "protected_class":   "[WHO IS PROTECTED â€” free text]"
  },

  "statute_of_limitations": {
    "limit_value":         [INTEGER],
    "limit_unit":          "[days | months | years]",
    "trigger":             "[WHAT EVENT STARTS THE CLOCK]",
    "exhaustion_required": [true | false],
    "exhaustion_details":  "[IF EXHAUSTION REQUIRED, DESCRIBE THE PROCESS]",
    "tolling_notes":       "[ANY TOLLING OR EXTENSION PROVISIONS]"
  },

  "enforcement": {
    "primary_agency": "[AGENCY NAME]",
    "remedies":       [ "[REMEDY DESCRIPTION]" ]
  },

  "burden_of_proof": {
    "employee_standard":      "[LEGAL STANDARD EMPLOYEE MUST MEET]",
    "employer_defense":       "[LEGAL STANDARD FOR EMPLOYER DEFENSE]",
    "rebuttable_presumption": "[IF APPLICABLE â€” describe the presumption]",
    "burden_of_proof_details_needed": [true | false]
  },

  "reward": {
    "available":       [true | false],
    "reward_details_needed": [true | false]
  },

  "links": {
    "official_url": "[URL TO OFFICIAL GOVERNMENT OR LEGISLATIVE SOURCE]",
    "is_official":  [true | false]
  }
}
```

### Field rules

- **`statute_id`** must be unique within the batch and follow the pattern
  `[JURISDICTION_ID]-[SECTION]`, e.g. `CA-1102.5`.
- **`disclosure_types`** must contain only values from the known taxonomy
  list. Unrecognized values go to `new_terms_proposed`, not here.
- **`exhaustion_details`** must only be present if `exhaustion_required`
  is `true`.
- **`burden_of_proof_details_needed`** set to `true` signals that the
  burden of proof for this statute is complex enough to require a narrative
  explanation field. Set to `false` or omit if the standard fields are
  sufficient.
- **`reward_details_needed`** set to `true` signals that reward provisions
  are present and complex enough to require a narrative explanation field.
- **`is_official`** must be `true` only if the URL points directly to an
  official government, legislative, or regulatory source. Set to `false`
  for secondary sources, legal databases, or aggregators.
- **Do not fabricate data.** If a field value cannot be found through
  research, omit the key entirely.
- **Do not add keys not present in this schema.** Extra keys will be
  ignored by the ingest tool but create noise.

### Scope for this run

Research and generate records for the following:

**Jurisdiction:** [JURISDICTION FULL NAME, e.g. California]
**Jurisdiction ID:** [TWO-LETTER CODE, e.g. CA]
**Scope:** [DESCRIBE WHAT TO COVER, e.g. "All state-level whistleblower
            protection statutes applicable to private sector employees"]
**Exclude:** [ANY STATUTES TO SKIP, or "None"]

Produce the complete JSON object now. Do not include any commentary,
explanation, or markdown outside the JSON block itself.

---

## Notes for the Researcher (not sent to an AI Assitant)

- Verify `record_count` in the header matches the actual number of records
  in the `records` array before running the ingest tool.
- Check `new_terms_proposed` in the header against `proposed-terms-log.json`
  before running ingest â€” new proposals require a manual review decision
  before affected records can be fully classified.
- The `burden_of_proof_details_needed` and `reward_details_needed` flags
  are signals to you as the researcher â€” when `true`, plan to author the
  corresponding detail field manually after ingest.
- All non-legalese text fields will enter the plain English review queue
  automatically on ingest. No action needed at import time.
- `source_name` in the meta block should reflect the actual an AI Assitant model
  version used (e.g. "Gemini 1.5 Pro", "Gemini 2.0 Flash") for accurate
  provenance tracking.

---

*Last updated: 2026-03-18*
*See also: `ingest-tool-design.md`, `legal-research-methodology.md`*
