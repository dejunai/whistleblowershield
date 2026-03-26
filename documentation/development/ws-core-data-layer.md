# ws-core Data Layer

## What This Document Is

The complete field group reference for the `ws-core` plugin. Documents
every ACF field group, its tabs, and all fields — meta key name, ACF
field type, and purpose. This is the reference to open when you need to
know what a field is called, what type it is, or why it exists.

ACF field groups are registered in PHP, not stored in the database. The
source of truth is always the relevant `includes/acf/` file. This
document describes what is in those files as of v3.10.0.

---

## Field Group Architecture

Fourteen field groups are registered. Twelve are CPT-specific. Two are
shared groups that attach to multiple CPTs via their location rules:

| Shared Group | Group Key | Attaches To | Purpose |
|---|---|---|---|
| Stamp Fields | `group_stamp_metadata` | All 9 content CPTs | Created/edited timestamps and authorship |
| Plain English Fields | `group_plain_english_metadata` | `jx-statute`, `jx-citation`, `jx-interpretation`, `ws-agency` | Plain language overlay toggle, content, and review stamps |
| Source Verify Fields | `group_source_verify_metadata` | All content CPTs except `jurisdiction` | Source method, verification status, needs-review flag |
| Major Edit | `group_major_edit_metadata` | `jx-summary`, `jx-statute`, `jx-citation`, `jx-interpretation`, `ws-ag-procedure` | Flag + describe a major editorial change that triggers a legal update post |

The shared groups load at `menu_order` 85–90, appearing after CPT-specific
groups in the admin edit screen. They are never duplicated in individual
CPT field files.

---

## Shared Group: Stamp Fields

**Group key:** `group_stamp_metadata`
**File:** `acf/acf-stamp-fields.php`
**Attaches to:** `jx-summary`, `jx-statute`, `jx-citation`,
`jx-interpretation`, `ws-agency`, `ws-ag-procedure`, `ws-assist-org`,
`ws-legal-update`, `ws-reference`

All fields in this group are auto-filled by hook logic and locked
read-only for non-administrators. Editors see the values but cannot
change them.

| Meta Key | Type | Written By | Notes |
|---|---|---|---|
| `ws_auto_last_edited_author` | user | Every save | The user who last saved — admin-overridable for attribution |
| `ws_auto_date_created` | text | First save only | Local site date `Y-m-d`; never overwritten |
| `ws_auto_last_edited` | text | Every save | Local site date `Y-m-d` |
| `ws_auto_create_author` | user | First save only | The user who created the record |

Hidden audit keys (no ACF field, never shown in UI):
`_ws_auto_date_created_gmt`, `_ws_auto_last_edited_gmt`

---

## Shared Group: Plain English Fields

**Group key:** `group_plain_english_metadata`
**File:** `acf/acf-plain-english-fields.php`
**Attaches to:** `jx-statute`, `jx-citation`, `jx-interpretation`,
`ws-agency`

Note: `jx-summary` and `ws-ag-procedure` are intentionally excluded.
The summary IS the plain language document. The procedure walkthrough
IS the plain language content. Neither carries this overlay.

| Meta Key | Type | Notes |
|---|---|---|
| `ws_has_plain_english` | true_false | Toggle — enables the plain language content field |
| `ws_plain_english_wysiwyg` | wysiwyg | The plain language content (conditional on toggle) |
| `ws_plain_english_reviewed` | true_false | Marks the plain language version as reviewed |
| `ws_auto_plain_english_reviewed_by` | user | Auto-stamped once when review toggled on |
| `ws_auto_plain_english_reviewed_date` | text | Auto-stamped once when review toggled on |
| `ws_auto_plain_english_by` | user | Auto-stamped once on first plain language save |
| `ws_auto_plain_english_date` | text | Auto-stamped once on first plain language save |

---

## Shared Group: Source Verify Fields

**Group key:** `group_source_verify_metadata`
**File:** `acf/acf-source-verify.php`
**Attaches to:** `jx-statute`, `jx-citation`, `jx-interpretation`,
`ws-agency`, `ws-ag-procedure`, `ws-assist-org`, `jx-summary`,
`ws-reference`

Source method and name fields are admin-only and locked read-only.
Verification status and needs-review are editable by editors.

| Meta Key | Type | Notes |
|---|---|---|
| `ws_auto_source_method` | text | One of the five `WS_SOURCE_*` constants |
| `ws_auto_source_name` | text | External origin name; `'Direct'` for human/matrix created records |
| `ws_auto_verified_by` | text | User display name of the verifying editor |
| `ws_auto_verified_date` | text | Date of last verification `Y-m-d` |
| `ws_verification_status` | select | `verified` / `needs_review` / `unverified` / `outdated` |
| `ws_needs_review` | true_false | Flag surfaced in admin columns; cleared on next verification |

---

## Shared Group: Major Edit

**Group key:** `group_major_edit_metadata`
**File:** `acf/acf-major-edit.php`
**Attaches to:** `jx-summary`, `jx-statute`, `jx-citation`,
`jx-interpretation`, `ws-ag-procedure`

When `ws_is_major_edit` is toggled on save, a `ws-legal-update` post is
automatically created with the source post title, type, and description.
The toggle resets after the legal update is created.

| Meta Key | Type | Notes |
|---|---|---|
| `ws_is_major_edit` | true_false | Triggers legal update creation on save |
| `ws_major_edit_description` | textarea | Required description of the change (conditional on toggle) |

---

## CPT Group: Jurisdiction

**Group key:** `group_jurisdiction_metadata`
**File:** `acf/acf-jurisdictions.php`
**Attaches to:** `jurisdiction`

**Tab: Identity**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jurisdiction_term_id` | taxonomy | Links this post to its `ws_jurisdiction` term; `save_terms: 1` |
| `ws_jx_code` | text | USPS code (e.g. `ca`, `us`); retained for legacy display |
| `ws_jurisdiction_class` | select | `state` / `federal` / `territory` / `district` |
| `ws_jurisdiction_name` | text | Display name used in headings |

**Tab: Government Leadership URLs**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_gov_portal_url` | url | Main government portal |
| `ws_jx_gov_portal_label` | text | Custom label for portal link |
| `ws_jx_executive_url` | url | Governor / mayor / president URL |
| `ws_jx_executive_label` | text | Title of executive (e.g. "Governor") |
| `ws_jx_wb_authority_url` | url | Whistleblower authority office URL |
| `ws_jx_wb_authority_label` | text | Name of the whistleblower authority |
| `ws_jx_legislature_url` | url | State legislature URL |
| `ws_jx_legislature_label` | text | Name of legislature |

**Tab: Flag**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_flag` | image | Flag image (WordPress media library) |
| `ws_jx_flag_attribution` | text | Wikimedia Commons attribution string |
| `ws_jx_flag_source_url` | url | Canonical Wikimedia Commons SVG URL |
| `ws_jx_flag_license` | text | License identifier (e.g. `Public Domain`) |

**Tab: Record Management**

| Meta Key | Type | Notes |
|---|---|---|
| `_ws_auto_last_edited_gmt` | text | Hidden GMT audit timestamp |
| `ws_auto_last_edited_author` | user | Last editor (jurisdiction uses its own field key) |
| `ws_auto_last_edited` | text | Local date of last edit |

---

## CPT Group: Jurisdiction Summary

**Group key:** `group_jx_summary_metadata`
**File:** `acf/acf-jx-summaries.php`
**Attaches to:** `jx-summary`

Plain English fields and stamp fields attach separately via their
shared groups. The summary is excluded from plain English overlay
(it is inherently plain language) but carries its own review toggle.

**Tab: Content**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jurisdiction_summary_wysiwyg` | wysiwyg | The plain language summary content |
| `ws_jx_summary_sources` | textarea | Sources and citations for the summary |
| `ws_jx_summary_notes` | textarea | Internal editorial notes (not public) |
| `ws_jx_limitations` | repeater | Limitations and ramifications; each row: `ws_jx_limit_label` (text) + `ws_jx_limit_text` (textarea) |

**Tab: Summary Review**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_plain_english_reviewed` | true_false | Summary-specific review toggle (not from shared group) |

---

## CPT Group: Statute

**Group key:** `group_jx_statute_metadata`
**File:** `acf/acf-jx-statutes.php`
**Attaches to:** `jx-statute`

The most field-dense CPT in the system. Six content tabs plus
stamp, plain English, source verify, and major edit from shared groups.

**Tab: Legal Basis**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_official_name` | text | Official statute name |
| `ws_jx_statute_citation` | text | Official citation (e.g. `29 U.S.C. § 660(c)`) |
| `ws_jx_statute_common_name` | text | Common or short name |
| `ws_jx_statute_disclosure_type` | taxonomy | `ws_disclosure_type` terms; `save_terms: 1` |
| `ws_jx_statute_protected_class` | taxonomy | `ws_protected_class` terms; `save_terms: 1` |
| `ws_jx_statute_disclosure_targets` | taxonomy | `ws_disclosure_targets` terms; `save_terms: 1` |
| `ws_jx_statute_adverse_action_scope` | textarea | Free-text description of covered adverse actions |
| `ws_attach_flag` | true_false | Surface this statute on the jurisdiction summary page |
| `ws_display_order` | number | Sort order among flagged records (conditional on flag) |

**Tab: Statute of Limitations**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_sol_value` | number | Filing window value (e.g. `180`) |
| `ws_jx_statute_sol_unit` | select | `days` / `months` / `years` |
| `ws_jx_statute_sol_trigger` | select | What event starts the clock |
| `ws_jx_statute_sol_has_details` | true_false | Toggle — enables SOL detail field |
| `ws_jx_statute_sol_details` | textarea | Supplementary SOL detail (conditional) |
| `ws_jx_statute_tolling_has_details` | true_false | Toggle — tolling provisions exist |
| `ws_jx_statute_tolling_details` | textarea | Tolling and extension details (conditional) |
| `ws_jx_statute_has_exhaustion` | true_false | Toggle — exhaustion required before filing |
| `ws_jx_statute_exhaustion_details` | textarea | Exhaustion procedure and deadline (conditional) |

**Tab: Enforcement**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_process_type` | taxonomy | `ws_process_type` terms; `save_terms: 1` |
| `ws_jx_statute_adverse_action` | taxonomy | `ws_adverse_action_types` terms; `save_terms: 1` |
| `ws_jx_statute_fee_shifting` | taxonomy | `ws_fee_shifting` terms; `save_terms: 1` |
| `ws_jx_statute_remedies` | taxonomy | `ws_remedies` terms; `save_terms: 1` |
| `ws_jx_statute_related_agencies` | post_object | Links to `ws-agency` posts that enforce this statute |

**Tab: Burden of Proof**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_bop_standard` | select | Employee burden standard |
| `ws_jx_statute_employer_defense` | taxonomy | `ws_employer_defense` terms; `save_terms: 1` |
| `ws_jx_statute_employer_defense_details` | textarea | Details on employer defense |
| `ws_jx_statute_rebuttable_has_details` | true_false | Toggle — rebuttable presumption exists |
| `ws_jx_statute_rebuttable_details` | textarea | Rebuttable presumption details (conditional) |
| `ws_jx_statute_bop_has_details` | true_false | Toggle — supplementary BOP detail |
| `ws_jx_statute_bop_details` | textarea | BOP supplementary detail (conditional) |

**Tab: Reward**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_has_reward` | true_false | Toggle — reward or bounty available |
| `ws_jx_statute_reward_details` | textarea | Reward details (conditional) |

**Tab: Links**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_url` | url | Primary statute URL (govinfo.gov or state legislature) |
| `ws_jx_statute_url_is_pdf` | true_false | True if URL points to a PDF |
| `ws_jx_statute_last_reviewed` | text | Last verified date `Y-m-d` |

**Tab: Reference Materials**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_ref_materials` | relationship | Links to `ws-reference` posts |

---

## CPT Group: Citation

**Group key:** `group_jx_citation_metadata`
**File:** `acf/acf-jx-citations.php`
**Attaches to:** `jx-citation`

**Tab: Content**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_citation_type` | select | Case law type (`federal_circuit` / `federal_district` / `state` / `administrative` / `supreme_court`) |
| `ws_jx_citation_disclosure_type` | taxonomy | `ws_disclosure_type` terms; `save_terms: 1` |
| `ws_jx_citation_official_name` | text | Official case name |
| `ws_jx_citation_common_name` | text | Short / common name |
| `ws_jx_citation_url` | url | Source URL (court opinion or database) |
| `ws_jx_citation_is_pdf` | true_false | True if URL points to a PDF |
| `ws_attach_flag` | true_false | Surface this citation on the jurisdiction summary page |
| `ws_display_order` | number | Sort order among flagged records (conditional on flag) |
| `ws_jx_citation_last_reviewed` | text | Last verified date `Y-m-d` |

**Tab: Relationships**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_citation_statute_ids` | post_object | Related `jx-statute` posts |

**Tab: Reference Materials**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_ref_materials` | relationship | Links to `ws-reference` posts |

---

## CPT Group: Interpretation

**Group key:** `group_jx_interpretation_metadata`
**File:** `acf/acf-jx-interpretations.php`
**Attaches to:** `jx-interpretation`

**Tab: Case Identity**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_interp_court` | select | Court key from the federal or state court matrix; `other` triggers free-text field |
| `ws_jx_interp_court_name` | text | Free-text court name (conditional on `other` selection) |
| `ws_jx_interp_year` | number | Year of decision |
| `ws_jx_interp_favorable` | true_false | Whether outcome favored the whistleblower |
| `ws_jx_interp_official_name` | text | Official case name |
| `ws_jx_interp_common_name` | text | Short / common name |
| `ws_jx_interp_case_citation` | text | Reporter citation |
| `ws_jx_interp_url` | url | URL to the court opinion |

**Tab: Summary**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_interp_summary` | textarea | Plain-language summary of the holding |
| `ws_process_type` | taxonomy | `ws_process_type` terms; `save_terms: 1` |
| `ws_attach_flag` | true_false | Surface this interpretation on the jurisdiction summary page |
| `ws_display_order` | number | Sort order among flagged records (conditional on flag) |
| `ws_jx_interp_last_reviewed` | text | Last verified date `Y-m-d` |

**Tab: Relationships**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_interp_statute_id` | post_object | Parent `jx-statute` post (single, required) |
| `ws_jx_interp_affected_jx` | taxonomy | `ws_jurisdiction` terms auto-populated from court's `ws_jx_codes`; `save_terms: 0` to avoid taxonomy pollution |

**Tab: Reference Materials**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_ref_materials` | relationship | Links to `ws-reference` posts |

---

## CPT Group: Agency

**Group key:** `group_agency_metadata`
**File:** `acf/acf-agencies.php`
**Attaches to:** `ws-agency`

**Tab: Agency Identity**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_agency_code` | text | Short reference code (e.g. `SEC`, `OSHA`) |
| `ws_agency_name` | text | Full official agency name |
| `ws_agency_logo` | image | Agency logo (WordPress media library) |
| `ws_jurisdiction` *(taxonomy field)* | taxonomy | Jurisdiction scope; `save_terms: 1` |
| `ws_agency_disclosure_type` | taxonomy | `ws_disclosure_type` terms; `save_terms: 1` |
| `ws_process_type` | taxonomy | `ws_process_type` terms; `save_terms: 1` |

**Tab: Contact & Reporting**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_agency_url` | url | Official website |
| `ws_agency_reporting_url` | url | Secure reporting portal |
| `ws_agency_phone` | text | Whistleblower hotline |
| `ws_agency_confidentiality_notes` | textarea | Notes on identity and confidentiality policies |
| `ws_agency_accepts_anonymous` | true_false | Whether anonymous reporting is permitted |
| `ws_agency_reward_program` | true_false | Whether a reward or bounty program exists |
| `ws_languages` | taxonomy | `ws_languages` terms; `save_terms: 1` |
| `ws_agency_additional_languages` | text | Free-text overflow; auto-assigns `additional` term |
| `ws_agency_last_reviewed` | date_picker | Last verified date |

---

## CPT Group: Filing Procedure

**Group key:** `group_ag_procedure_metadata`
**File:** `acf/acf-ag-procedures.php`
**Attaches to:** `ws-ag-procedure`

Stamp fields attach via the shared group. Plain English fields do NOT
attach — the walkthrough is the plain English content. Source verify
fields DO attach.

**Tab: Procedure Identity**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_proc_agency_id` | post_object | Parent `ws-agency` post; pre-filled from `?agency_id=` URL param on new posts |
| `ws_procedure_type` | taxonomy | `ws_procedure_type` terms; radio UI; `save_terms: 1` |
| `ws_jurisdiction` *(taxonomy field)* | taxonomy | Jurisdiction scope; `save_terms: 1` |
| `ws_proc_disclosure_types` | taxonomy | `ws_disclosure_type` terms; `save_terms: 1` |
| `ws_proc_statute_ids` | relationship | Related `jx-statute` posts; auto-scoped to matching jurisdiction and disclosure types |

**Tab: Filing Details**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_proc_entry_point` | select | `online` / `mail` / `phone` / `in_person` / `multi` |
| `ws_proc_intake_url` | url | Direct intake form URL |
| `ws_proc_phone` | text | Direct hotline for this procedure |
| `ws_proc_identity_policy` | select | `anonymous` / `confidential` / `identified` / `varies` |
| `ws_proc_intake_only` | true_false | Agency receives and refers only — does not investigate |
| `ws_proc_deadline_days` | number | Filing deadline in calendar days; `0` = none or unknown |
| `ws_proc_deadline_clock_start` | select | `adverse_action` / `knowledge` / `last_act` / `varies` (conditional on deadline > 0) |
| `ws_proc_prerequisites` | true_false | Prerequisites required before filing |
| `ws_proc_prerequisites_note` | textarea | What prerequisites must be satisfied (conditional on toggle) |

**Tab: Plain English**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_proc_walkthrough` | wysiwyg | Step-by-step plain language filing guide |
| `ws_proc_exclusivity_note` | textarea | Remedies or procedures the filer may forfeit by using this pathway |

**Tab: Last Verified**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_proc_last_reviewed` | date_picker | Last verified date |

**Tab: Admin Review** *(admin-only)*

| Meta Key | Type | Notes |
|---|---|---|
| `ws_proc_stat_override` | true_false | Admin override for statute link mismatch flag; resets to 0 after save |

---

## CPT Group: Assist Organization

**Group key:** `group_assist_org_metadata`
**File:** `acf/acf-assist-orgs.php`
**Attaches to:** `ws-assist-org`

**Tab: Identity**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_aorg_internal_id` | text | Internal reference code |
| `ws_aorg_type` | taxonomy | `ws_aorg_type` terms; radio UI; `save_terms: 1` |
| `ws_aorg_description` | textarea | Organization description |
| `ws_aorg_logo` | image | Organization logo |

**Tab: Scope of Service**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_aorg_serves_nationwide` | true_false | Serves all 57 jurisdictions — enables nationwide overlay |
| `ws_jurisdiction` *(taxonomy field)* | taxonomy | Specific jurisdictions served; `save_terms: 1` |
| `ws_aorg_disclosure_types` | taxonomy | `ws_disclosure_type` terms; `save_terms: 1` |
| `ws_aorg_services` | taxonomy | `ws_aorg_service` terms; `save_terms: 1` |
| `ws_aorg_additional_services` | textarea | Free-text overflow; auto-assigns `additional` service term |
| `ws_employment_sector` | taxonomy | `ws_employment_sector` terms; `save_terms: 1` |
| `ws_case_stage` | taxonomy | `ws_case_stage` terms; `save_terms: 1` |

**Tab: Contact**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_aorg_website_url` | url | Website |
| `ws_aorg_phone` | text | Phone |
| `ws_aorg_email` | email | Contact email |
| `ws_aorg_mailing_address` | textarea | Mailing address |
| `ws_languages` | taxonomy | `ws_languages` terms; `save_terms: 1` |
| `ws_aorg_additional_languages` | text | Free-text overflow; auto-assigns `additional` language term |

**Tab: Eligibility & Cost**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_aorg_cost_model` | taxonomy | `ws_aorg_cost_model` terms; radio UI; `save_terms: 1` |
| `ws_aorg_income_limit` | true_false | Income eligibility required |
| `ws_aorg_income_limit_notes` | textarea | Eligibility details (conditional) |
| `ws_aorg_accepts_anonymous` | true_false | Can assist anonymous clients |
| `ws_aorg_eligibility_notes` | textarea | Additional eligibility requirements |

**Tab: Credentials**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_aorg_licensed_attorneys` | true_false | Licensed attorneys on staff |
| `ws_aorg_accreditation` | text | Accreditations and certifications |
| `ws_aorg_bar_states` | text | State bar memberships |
| `ws_aorg_verify_url` | url | Verification or transparency URL |
| `ws_aorg_last_reviewed` | date_picker | Last verified date |

---

## CPT Group: Legal Update

**Group key:** `group_legal_update_metadata`
**File:** `acf/acf-legal-updates.php`
**Attaches to:** `ws-legal-update`

**Tab: Content**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_legal_update_jurisdiction` | taxonomy | Affected jurisdiction; `ws_jurisdiction` term; `save_terms: 1` |
| `ws_legal_update_multi_jurisdiction` | true_false | Affects multiple jurisdictions |
| `ws_legal_update_date` | date_picker | Date of the legal development |
| `ws_legal_update_source_url` | url | Primary source URL |
| `ws_legal_update_type` | select | `statute` / `citation` / `summary` / `interpretation` / `regulation` / `policy` / `internal` / `other` |
| `ws_legal_update_law_name` | text | Name of the law or case (auto-filled by major edit hook) |
| `ws_legal_update_summary_wysiwyg` | wysiwyg | Summary of the legal development |
| `ws_legal_update_effective_date` | date_picker | Effective date of the change |

Auto-written meta keys (set by major edit hook, never via ACF UI):
`ws_legal_update_source_post_id`, `ws_legal_update_source_post_type`

---

## CPT Group: Reference

**Group key:** `group_reference_metadata`
**File:** `acf/acf-references.php`
**Attaches to:** `ws-reference`

**Tab: Content**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_ref_title` | text | Resource title |
| `ws_ref_url` | url | Resource URL |
| `ws_ref_description` | textarea | Brief description |
| `ws_ref_type` | select | Resource type (`statute_text` / `regulation` / `agency_guidance` / `academic` / `news` / `advocacy` / `other`) |
| `ws_ref_source_name` | text | Source or author name |

Stamp fields attach via shared group.

---

## Toggle + Conditional Pattern

Several CPTs use a consistent toggle + conditional field pattern:

```
[toggle field — true_false]
    └── [detail field — visible only when toggle is on]
```

Examples: SOL details, tolling details, exhaustion details, BOP details,
rebuttable presumption, reward, prerequisites, deadline clock start,
major edit description. This pattern keeps the admin edit screen clean
while preserving all detail fields for the cases that need them.

---

## The `save_terms` Convention

Every taxonomy ACF field that should write term assignments to the
WordPress taxonomy table carries `save_terms: 1` and `load_terms: 1`.
This is what makes `tax_query` filtering work throughout the query layer
and what allows matrix seeders to use `wp_set_object_terms()` directly
without an ACF save cycle.

Fields that explicitly use `save_terms: 0` do so to prevent taxonomy
query pollution — `ws_jx_interp_affected_jx` is the notable example,
where terms are auto-populated from court matrix data and should not
affect standard `ws_jurisdiction` taxonomy queries.
