# ws-core ACF Audit — Pass 1: Structured Inventory
**Date:** 2026-03-19
**Scope:** All 10 ACF registration files in `includes/acf/`
**Pass goal:** Inventory only. No changes. Surface structural issues for Pass 2.

---

## 1. Field Group Registry

| Group Key | Title | File | Location Rule(s) | Position |
|---|---|---|---|---|
| `group_ws_jurisdiction_metadata` | Jurisdiction Metadata | `acf-jurisdictions.php` | `jurisdiction` | normal |
| `group_jx_summary` | Jurisdiction Summary Metadata | `acf-jx-summaries.php` | `jx-summary` | normal |
| `group_jx_statute_metadata` | Statute Details & Deadlines | `acf-jx-statutes.php` | `jx-statute` | normal |
| `group_jx_citation` | Jurisdiction Citation | `acf-jx-citations.php` | `jx-citation` | normal |
| `group_jx_interpretation_details` | Interpretation Details | `acf-jx-interpretations.php` | `jx-interpretation` | normal |
| `group_ws_agency` | Agency Details & Reporting Protocols | `acf-agencies.php` | `ws-agency` | normal |
| `group_ws_assist_org` | Assistance Organization Details | `acf-assist-orgs.php` | `ws-assist-org` | normal |
| `group_ws_reference_metadata` | Reference Details | `acf-references.php` | `ws-reference` | normal |
| `group_legal_update_metadata` | Legal Update Metadata | `acf-legal-updates.php` | `ws-legal-update` | normal |
| `group_ws_major_edit_metadata` | Major Edit Metadata | `acf-major-edit.php` | `jx-summary`, `jx-statute`, `jx-citation`, `jx-interpretation` | normal, menu_order 99 |
| `group_source_verify` | Source & Verification | `acf-source-verify.php` | `jx-statute`, `jx-citation`, `jx-interpretation`, `ws-agency`, `ws-assist-org`, `jx-summary`, `ws-reference` | side, order 100 |

---

## 2. Duplicate Field Keys

ACF requires globally unique field keys across all registered groups. The following keys are registered in **multiple groups simultaneously**. This is the most critical structural issue in the audit.

Because each CPT's group is only active on its own post type, ACF will not render duplicate fields on the same screen. However, when `admin-hooks.php` calls `acf_get_field( $field_key )` to resolve field names during `ws_acf_plain_english_guards()`, ACF returns whichever registration it finds first — which is non-deterministic. This creates a latent correctness risk that will become a real bug once the shared group refactor consolidates these.

| Duplicate Key | Files | Meta Name | Notes |
|---|---|---|---|
| `field_last_edited_author` | agencies, assist-orgs, jurisdictions, jx-citations, jx-interpretations, jx-statutes, jx-summaries, legal-updates | `last_edited_author` | 8 registrations. Intentionally shared meta name; key duplication is the issue. |
| `field_date_created` | same 8 files | `date_created` | 8 registrations. |
| `field_last_edited` | same 8 files | `last_edited` | 8 registrations. |
| `field_create_author` | same 8 files | `create_author` | 8 registrations. |
| `field_has_plain_english` | agencies, assist-orgs, jx-citations, jx-interpretations, jx-statutes | `has_plain_english` | 5 registrations. **Not in jx-summaries** (by design — see §4.1). |
| `field_plain_english_wysiwyg` | same 5 files | `plain_english_wysiwyg` | 5 registrations. |
| `field_plain_english_reviewed` | same 5 files | `plain_english_reviewed` | 5 registrations. **jx-summaries uses a different key** — see §4.2. |
| `field_plain_english_reviewed_by` | same 5 files | `plain_english_reviewed_by` | 5 registrations. |
| `field_plain_english_by` | same 5 files | `plain_english_by` | 5 registrations. |
| `field_plain_english_date` | same 5 files | `plain_english_date` | 5 registrations. |

**Note:** `ws-reference` intentionally uses unique keys `field_ws_ref_last_edited_author`, `field_ws_ref_date_created`, `field_ws_ref_last_edited`, `field_ws_ref_create_author` to avoid ACF lookup ambiguity. This is correct and documented.

---

## 3. Field Inventory by Group

### 3.1 `acf-jurisdictions.php` — `jurisdiction` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_jx_identity_tab` | — | tab | |
| `field_jurisdiction_tax` | `ws_jurisdiction_tax` | taxonomy | ws_jurisdiction, select, return_format=id, save_terms=1, load_terms=1. Used by `acf/fields/taxonomy/query/key=field_jurisdiction_tax` sort filter in admin-hooks. ✅ Key confirmed present. |
| `field_jx_code` | `ws_jx_code` | text | Legacy display field, readonly/disabled via `acf/prepare_field`. ✅ Key confirmed present. |
| `field_jurisdiction_class` | `ws_jurisdiction_class` | select | readonly/disabled via prepare_field. ✅ |
| `field_jurisdiction_name` | `ws_jurisdiction_name` | text | readonly/disabled via prepare_field. ✅ |
| `field_jx_gov_urls_tab` | — | tab | |
| `field_jx_gov_portal_url` | `ws_jx_gov_portal_url` | url | |
| `field_jx_gov_portal_label` | `ws_jx_gov_portal_label` | text | |
| `field_jx_executive_url` | `ws_jx_executive_url` | url | |
| `field_jx_executive_label` | `ws_jx_executive_label` | text | |
| `field_jx_wb_authority_url` | `ws_jx_wb_authority_url` | url | |
| `field_jx_wb_authority_label` | `ws_jx_wb_authority_label` | text | |
| `field_jx_legislature_url` | `ws_jx_legislature_url` | url | |
| `field_jx_legislature_label` | `ws_jx_legislature_label` | text | |
| `field_jx_flag_tab` | — | tab | |
| `field_jx_flag` | `ws_jx_flag` | image | return_format=array |
| `field_jx_flag_attribution` | `ws_jx_flag_attribution` | text | |
| `field_jx_flag_source_url` | `ws_jx_flag_source_url` | url | Conditionally appended with Commons link via `acf/prepare_field`. ✅ Key confirmed present. |
| `field_jx_flag_license` | `ws_jx_flag_license` | text | |
| `field_record_tab` | — | tab | |
| `field_create_author` | `create_author` | text | hidden wrapper. **DUPLICATE KEY** — see §2. |
| `field_date_created` | `date_created` | text | hidden wrapper. **DUPLICATE KEY** — see §2. |
| `field_date_created_gmt` | `date_created_gmt` | text | hidden wrapper. Unique to this group. |
| `field_last_edited_gmt` | `last_edited_gmt` | text | hidden wrapper. Unique to this group. |
| `field_last_edited_author` | `last_edited_author` | user | return_format=array. **DUPLICATE KEY** — see §2. |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** — see §2. |

**Issues:** `field_create_author` declared as `type=text` here; registered as `type=user` in all other groups. See §4.3.

---

### 3.2 `acf-jx-summaries.php` — `jx-summary` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_ws_jx_sum_content_tab` | — | tab | |
| `field_ws_jurisdiction_summary` | `ws_jurisdiction_summary` | wysiwyg | required=1 |
| `field_ws_jx_summary_sources` | `ws_jx_summary_sources` | textarea | |
| `field_ws_jx_summary_notes` | `ws_jx_summary_notes` | textarea | |
| `field_ws_jx_limitations` | `ws_jx_limitations` | wysiwyg | |
| `field_ws_jx_sum_authorship_tab` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | return_format=array. **DUPLICATE KEY** — see §2. |
| `field_jx_sum_plain_english_reviewed` | `plain_english_reviewed` | true_false | **KEY DIVERGENCE from all other CPTs** — see §4.2. |
| `field_ws_jx_sum_plain_english_by_temp` | `ws_jx_sum_create_author` | user | **Non-canonical meta name** — see §4.4. `_temp` suffix in key name is a red flag. |
| `field_ws_jx_sum_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | return_format=id |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** — see §2. |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** — see §2. |
| `field_create_author` | `create_author` | user | return_format=id. **DUPLICATE KEY** — see §2. |

**Issues:** No `has_plain_english` field (correct by design — jx-summary is inherently plain language). `plain_english_reviewed` key diverges from all other CPTs. Non-canonical `ws_jx_sum_create_author` meta name. `_temp` suffix on field key. See §4.

---

### 3.3 `acf-jx-statutes.php` — `jx-statute` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_jx_statute_legal_tab` | — | tab | |
| `field_jx_statute_official_name` | `ws_jx_statute_official_name` | text | required=1 |
| `field_jx_statute_disclosure_type` | `ws_jx_statute_disclosure_type` | taxonomy | ws_disclosure_type, multi_select, save_terms=0, load_terms=1 |
| `field_jx_statute_attach_flag` | `attach_flag` | true_false | |
| `field_jx_statute_order` | `order` | number | conditional on attach_flag=1 |
| `field_jx_tab_statute_deadlines` | — | tab | |
| `field_jx_statute_limit_value` | `ws_jx_statute_limit_value` | number | |
| `field_jx_statute_limit_unit` | `ws_jx_statute_limit_unit` | select | days/months/years |
| `field_jx_statute_trigger` | `ws_jx_statute_trigger` | select | |
| `field_jx_statute_tolling_notes` | `ws_jx_statute_tolling_notes` | textarea | |
| `field_jx_statute_exhaustion_required` | `ws_jx_statute_exhaustion_required` | true_false | |
| `field_jx_statute_exhaustion_details` | `ws_jx_statute_exhaustion_details` | textarea | conditional on exhaustion_required=1 |
| `field_jx_statute_burden_of_proof` | `ws_statute_burden_of_proof` | select | **Note: meta name prefix is `ws_statute_` not `ws_jx_statute_`** — minor inconsistency, not a bug. |
| `field_jx_statute_remedies` | `ws_jx_statute_remedies` | taxonomy | **Uses `ws_remedy_type` (DEPRECATED taxonomy)** — see §4.5. |
| `field_jx_tab_statute_rel` | — | tab | |
| `field_jx_statute_related_agencies` | `ws_jx_statute_related_agencies` | post_object | post_type=ws-agency, multiple=1, return_format=id |
| `field_jx_tab_statute_review` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | return_format=array. **DUPLICATE KEY** — see §2. |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | return_format=id. **DUPLICATE KEY** |
| `field_jx_statute_last_reviewed` | `ws_jx_statute_last_reviewed` | text | @todo in file: duplicate purpose of `plain_reviewed` — see §4.6. |
| `tab_jx_statute_plain_language_tab` | — | tab | **Key uses `tab_` prefix, not `field_`** — see §4.7. |
| `field_has_plain_english` | `has_plain_english` | true_false | **DUPLICATE KEY** — see §2. |
| `field_plain_english_wysiwyg` | `plain_english_wysiwyg` | wysiwyg | **DUPLICATE KEY**. conditional on has_plain_english=1. |
| `field_plain_english_reviewed` | `plain_english_reviewed` | true_false | **DUPLICATE KEY** |
| `field_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | **DUPLICATE KEY** |
| `field_plain_english_by` | `plain_english_by` | user | **DUPLICATE KEY** |
| `field_plain_english_date` | `plain_english_date` | text | **DUPLICATE KEY** |
| `field_jx_statute_ref_materials_tab` | — | tab | |
| `field_statute_ref_materials` | `ws_ref_materials` | relationship | post_type=ws-reference, return_format=object |

---

### 3.4 `acf-jx-citations.php` — `jx-citation` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_ws_jx_cite_tab_content` | — | tab | |
| `field_ws_jx_cite_type` | `ws_jx_cite_type` | select | required=1 |
| `field_ws_jx_disclosure_cat` | `ws_disclosure_type` | taxonomy | **Meta name `ws_disclosure_type` conflicts with the taxonomy slug** — see §4.8. save_terms=1, load_terms=1. |
| `field_ws_jx_cite_label` | `ws_jx_cite_label` | text | required=1 |
| `field_ws_jx_cite_url` | `ws_jx_cite_url` | url | |
| `field_ws_jx_cite_is_pdf` | `ws_jx_cite_is_pdf` | true_false | |
| `field_ws_jx_cite_attach` | `attach_flag` | true_false | Note: key is `field_ws_jx_cite_attach`; meta name is canonical `attach_flag`. |
| `field_ws_jx_cite_position` | `order` | number | Note: key is `field_ws_jx_cite_position`; meta name is canonical `order`. Conditional on `field_ws_jx_cite_attach=1`. |
| `field_ws_jx_cite_tab_authorship` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | **DUPLICATE KEY** |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | **DUPLICATE KEY** |
| `field_ws_jx_cite_last_reviewed` | `ws_jx_cite_last_reviewed` | text | See §4.6. |
| `tab_ws_jx_cite_plain_language` | — | tab | **`tab_` prefix, not `field_`** — see §4.7. |
| `field_has_plain_english` | `has_plain_english` | true_false | **DUPLICATE KEY** |
| `field_plain_english_wysiwyg` | `plain_english_wysiwyg` | wysiwyg | **DUPLICATE KEY** |
| `field_plain_english_reviewed` | `plain_english_reviewed` | true_false | **DUPLICATE KEY** |
| `field_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | **DUPLICATE KEY** |
| `field_plain_english_by` | `plain_english_by` | user | **DUPLICATE KEY** |
| `field_plain_english_date` | `plain_english_date` | text | **DUPLICATE KEY** |
| `field_jx_citation_ref_materials_tab` | — | tab | |
| `field_citation_ref_materials` | `ws_ref_materials` | relationship | post_type=ws-reference, return_format=object |

---

### 3.5 `acf-jx-interpretations.php` — `jx-interpretation` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_interp_tab_case_identity` | — | tab | |
| `field_ws_interp_court` | `ws_interp_court` | select | choices=[] populated by `ws_interp_load_court_choices()` filter. |
| `field_ws_interp_year` | `ws_interp_year` | number | |
| `field_ws_interp_favorable` | `ws_interp_favorable` | true_false | |
| `field_ws_interp_case_name` | `ws_interp_case_name` | text | required=1 |
| `field_ws_interp_citation` | `ws_interp_citation` | text | required=1 |
| `field_ws_interp_url` | `ws_interp_url` | url | |
| `field_interp_tab_summary` | — | tab | |
| `field_ws_interp_summary` | `ws_interp_summary` | textarea | required=1 |
| `field_ws_interp_process_type` | `ws_process_type` | taxonomy | ws_process_type, multi_select, save_terms=1, load_terms=1 |
| `field_ws_interp_attach_flag` | `attach_flag` | true_false | |
| `field_ws_interp_order` | `order` | number | conditional on `field_ws_interp_attach_flag=1` |
| `field_interp_tab_relationships` | — | tab | |
| `field_ws_interp_statute_id` | `ws_statute_id` | post_object | post_type=jx-statute, required=1, return_format=id. Pre-populated via `acf/load_value/key=field_ws_interp_statute_id`. |
| `field_interp_tab_authorship` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | **DUPLICATE KEY** |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | **DUPLICATE KEY** |
| `field_ws_interp_last_reviewed` | `ws_interp_last_reviewed` | text | See §4.6. |
| `tab_ws_interp_plain_language` | — | tab | **`tab_` prefix** — see §4.7. |
| `field_has_plain_english` | `has_plain_english` | true_false | **DUPLICATE KEY** |
| `field_plain_english_wysiwyg` | `plain_english_wysiwyg` | wysiwyg | **DUPLICATE KEY** |
| `field_plain_english_reviewed` | `plain_english_reviewed` | true_false | **DUPLICATE KEY** |
| `field_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | **DUPLICATE KEY** |
| `field_plain_english_by` | `plain_english_by` | user | **DUPLICATE KEY** |
| `field_plain_english_date` | `plain_english_date` | text | **DUPLICATE KEY** |
| `field_jx_interp_ref_materials_tab` | — | tab | |
| `field_interp_ref_materials` | `ws_ref_materials` | relationship | post_type=ws-reference, return_format=object |

---

### 3.6 `acf-agencies.php` — `ws-agency` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `tab_agency_identity` | — | tab | |
| `field_ws_agency_code` | `ws_agency_code` | text | required=1 |
| `field_ws_agency_name` | `ws_agency_name` | text | required=1 |
| `field_ws_agency_logo` | `ws_agency_logo` | image | return_format=array |
| `field_ws_agency_jurisdiction` | `ws_jurisdiction` | taxonomy | ws_jurisdiction, multi_select, save_terms=1, load_terms=1. **Meta name `ws_jurisdiction` is the taxonomy slug** — see §4.8. |
| `field_ws_agency_disclosure_type` | `ws_agency_disclosure_type` | taxonomy | ws_disclosure_type, multi_select, save_terms=1, load_terms=1 |
| `field_ws_agency_process_type` | `ws_process_type` | taxonomy | ws_process_type, multi_select, save_terms=1, load_terms=1 |
| `tab_agency_contact` | — | tab | |
| `field_ws_agency_url` | `ws_agency_url` | url | |
| `field_ws_agency_reporting_url` | `ws_agency_reporting_url` | url | |
| `field_ws_agency_phone` | `ws_agency_phone` | text | |
| `field_ws_agency_confidentiality_notes` | `ws_agency_confidentiality_notes` | textarea | |
| `field_ws_agency_anonymous_allowed` | `ws_agency_anonymous_allowed` | true_false | |
| `field_ws_agency_reward_program` | `ws_agency_reward_program` | true_false | |
| `field_ws_agency_languages` | `ws_languages` | taxonomy | ws_languages, checkbox, save_terms=1, load_terms=1. **Meta name is taxonomy slug** — see §4.8. |
| `field_ws_agency_additional_languages` | `ws_agency_additional_languages` | text | Triggers `ws_languages` "additional" term auto-assign in admin-hooks. |
| `tab_agency_review` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | **DUPLICATE KEY** |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | **DUPLICATE KEY** |
| `field_ws_agency_last_reviewed` | `ws_agency_last_reviewed` | date_picker | return_format=Y-m-d. See §4.6. |
| `tab_ws_agency_plain_language` | — | tab | |
| `field_has_plain_english` | `has_plain_english` | true_false | **DUPLICATE KEY** |
| `field_plain_english_wysiwyg` | `plain_english_wysiwyg` | wysiwyg | **DUPLICATE KEY** |
| `field_plain_english_reviewed` | `plain_english_reviewed` | true_false | **DUPLICATE KEY** |
| `field_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | **DUPLICATE KEY** |
| `field_plain_english_by` | `plain_english_by` | user | **DUPLICATE KEY** |
| `field_plain_english_date` | `plain_english_date` | text | **DUPLICATE KEY** |

---

### 3.7 `acf-assist-orgs.php` — `ws-assist-org` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_ws_ao_tab_identity` | — | tab | |
| `field_ws_ao_internal_id` | `ws_ao_internal_id` | text | required=1 |
| `field_ws_ao_type` | `ws_ao_type` | select | required=1, return_format=value |
| `field_ws_ao_logo` | `ws_ao_logo` | image | return_format=array |
| `field_ws_ao_tab_scope` | — | tab | |
| `field_ws_ao_serves_nationwide` | `ws_ao_serves_nationwide` | true_false | |
| `field_ws_ao_jurisdiction` | `ws_jurisdiction` | taxonomy | ws_jurisdiction, checkbox, save_terms=1, load_terms=1. **Meta name is taxonomy slug** — see §4.8. |
| `field_ws_ao_disclosure_type` | `ws_ao_disclosure_type` | taxonomy | ws_disclosure_type, multi_select, save_terms=1, load_terms=1 |
| `field_ws_ao_services` | `ws_ao_services` | checkbox | return_format=value |
| `field_ws_ao_employment_sectors` | `ws_ao_employment_sectors` | checkbox | return_format=value |
| `field_ws_ao_tab_contact` | — | tab | |
| `field_ws_ao_website_url` | `ws_ao_website_url` | url | required=1 |
| `field_ws_ao_intake_url` | `ws_ao_intake_url` | url | |
| `field_ws_ao_phone` | `ws_ao_phone` | text | |
| `field_ws_ao_email` | `ws_ao_email` | email | |
| `field_ws_ao_mailing_address` | `ws_ao_mailing_address` | textarea | |
| `field_ws_ao_languages` | `ws_languages` | taxonomy | ws_languages, checkbox, save_terms=1, load_terms=1. **Meta name is taxonomy slug** — see §4.8. |
| `field_ws_ao_additional_languages` | `ws_ao_additional_languages` | text | Triggers "additional" term auto-assign. |
| `field_ws_ao_tab_eligibility` | — | tab | |
| `field_ws_ao_cost_model` | `ws_ao_cost_model` | select | required=1, return_format=value |
| `field_ws_ao_income_limit` | `ws_ao_income_limit` | true_false | |
| `field_ws_ao_income_limit_notes` | `ws_ao_income_limit_notes` | textarea | conditional on income_limit=1 |
| `field_ws_ao_accepts_anonymous` | `ws_ao_accepts_anonymous` | true_false | |
| `field_ws_ao_eligibility_notes` | `ws_ao_eligibility_notes` | textarea | |
| `field_ws_ao_tab_credentials` | — | tab | |
| `field_ws_ao_licensed_attorneys` | `ws_ao_licensed_attorneys` | true_false | |
| `field_ws_ao_accreditation` | `ws_ao_accreditation` | text | |
| `field_ws_ao_bar_states` | `ws_ao_bar_states` | text | |
| `field_ws_ao_verify_url` | `ws_ao_verify_url` | url | |
| `field_ws_ao_tab_authorship` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | **DUPLICATE KEY** |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | **DUPLICATE KEY** |
| `field_ws_ao_last_reviewed` | `ws_ao_last_reviewed` | date_picker | return_format=Y-m-d. See §4.6. |
| `tab_ws_ao_plain_language` | — | tab | |
| `field_has_plain_english` | `has_plain_english` | true_false | **DUPLICATE KEY** |
| `field_plain_english_wysiwyg` | `plain_english_wysiwyg` | wysiwyg | **DUPLICATE KEY** |
| `field_plain_english_reviewed` | `plain_english_reviewed` | true_false | **DUPLICATE KEY** |
| `field_plain_english_reviewed_by` | `plain_english_reviewed_by` | user | **DUPLICATE KEY** |
| `field_plain_english_by` | `plain_english_by` | user | **DUPLICATE KEY** |
| `field_plain_english_date` | `plain_english_date` | text | **DUPLICATE KEY** |

---

### 3.8 `acf-references.php` — `ws-reference` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_ws_ref_tab_content` | — | tab | |
| `field_ws_ref_title` | `ws_ref_title` | text | |
| `field_ws_ref_url` | `ws_ref_url` | url | required=1 |
| `field_ws_ref_description` | `ws_ref_description` | textarea | |
| `field_ws_ref_type` | `ws_ref_type` | select | return_format=**label** (not value) — see §4.9. |
| `field_ws_ref_source_name` | `ws_ref_source_name` | text | |
| `field_ws_ref_tab_approval` | — | tab | |
| `field_ws_ref_approved` | `ws_ref_approved` | true_false | Locked for non-admins via `ws_acf_lock_for_non_admins()`. ✅ Field name matches lock list. |
| `field_ws_ref_tab_authorship` | — | tab | |
| `field_ws_ref_last_edited_author` | `last_edited_author` | user | Unique key by design. ✅ Matches `$ws_stamp_cpts` entry in admin-hooks. |
| `field_ws_ref_date_created` | `date_created` | text | Unique key by design. ✅ |
| `field_ws_ref_last_edited` | `last_edited` | text | Unique key by design. ✅ |
| `field_ws_ref_create_author` | `create_author` | user | Unique key by design. ✅ |

---

### 3.9 `acf-legal-updates.php` — `ws-legal-update` CPT

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_update_jurisdictions` | `ws_update_jurisdictions` | taxonomy | ws_jurisdiction, multi_select, save_terms=0, load_terms=0 |
| `field_update_date` | `ws_update_date` | date_picker | return_format=Y-m-d |
| `field_ws_update_source` | `ws_update_source` | url | |
| `field_ws_update_type` | `ws_update_type` | select | return_format=value |
| `field_ws_legal_update_law_name` | `ws_legal_update_law_name` | text | |
| `field_ws_legal_update_summary` | `ws_legal_update_summary` | wysiwyg | toolbar=basic |
| `field_ws_legal_update_effective_date` | `ws_legal_update_effective_date` | date_picker | |
| `field_authorship_tab` | — | tab | |
| `field_last_edited_author` | `last_edited_author` | user | **DUPLICATE KEY** |
| `field_date_created` | `date_created` | text | **DUPLICATE KEY** |
| `field_last_edited` | `last_edited` | text | **DUPLICATE KEY** |
| `field_create_author` | `create_author` | user | **DUPLICATE KEY** |

**Note:** No plain language tab — correct, `ws-legal-update` is not in the plain English system.
**Note:** Fields have no tabs before `field_authorship_tab` — all content fields appear above the tab. This is valid ACF behavior (fields before the first tab render outside tabs) but worth noting for UX consistency.

---

### 3.10 `acf-major-edit.php` — Multi-CPT additive group

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_ws_is_major_edit` | `is_major_edit` | true_false | default=0 |
| `field_ws_major_edit_description` | `major_edit_description` | textarea | conditional on `field_ws_is_major_edit=1` |

Location: `jx-summary`, `jx-statute`, `jx-citation`, `jx-interpretation`. All keys are unique. ✅ Conditional logic references `field_ws_is_major_edit` — same group, no cross-group dependency. ✅

---

### 3.11 `acf-source-verify.php` — Multi-CPT additive group (side column)

| Field Key | Meta Name | Type | Notes |
|---|---|---|---|
| `field_sv_tab` | — | tab | |
| `field_source_method` | `source_method` | text | readonly, disabled |
| `field_source_name` | `source_name` | text | editable |
| `field_verified_by` | `verified_by` | text | readonly, disabled |
| `field_verified_date` | `verified_date` | text | readonly, disabled |
| `field_verification_status` | `verification_status` | select | conditional on `field_source_name != empty`. return_format=value. |
| `field_needs_review` | `needs_review` | true_false | |

All keys unique. ✅ Conditional logic references `field_source_name` — same group. ✅ Hook references `field_verification_status` by key in `ws_stamp_verified_by_date()` and `ws_enforce_source_verify_roles()` — key confirmed present. ✅

---

## 4. Structural Issues for Pass 2

### 4.1 `jx-summary` has no `has_plain_english` field
**Status:** Correct by design. `jx-summary` is inherently a plain-language document — there is no toggle because the entire post is the plain-language content. The plain-English guards hook (`ws_acf_plain_english_guards()`) correctly excludes `jx-summary` from its CPT list. No action needed.

### 4.2 `plain_english_reviewed` key diverges in `jx-summary`
**File:** `acf-jx-summaries.php`
**Issue:** All five CPTs with the plain-language tab register this field with key `field_plain_english_reviewed`. `jx-summary` uses `field_jx_sum_plain_english_reviewed`. The meta name (`plain_english_reviewed`) is the same across all six, so runtime data is unaffected. However, `ws_acf_plain_english_guards()` in admin-hooks resolves field names from keys by calling `acf_get_field( $field_key )` on submitted `$_POST['acf']` data — it does not look up by key directly for jx-summary fields since jx-summary is excluded from that hook's CPT list. No runtime bug today, but the divergence is a maintenance hazard.
**Pass 2 action:** Rename key to `field_plain_english_reviewed` to match all other CPTs.

### 4.3 `field_create_author` type mismatch in `acf-jurisdictions.php`
**File:** `acf-jurisdictions.php`
**Issue:** `field_create_author` is registered as `type=text` in this group; it is registered as `type=user` in all other groups. The meta value stored is a WP user ID integer in both cases (written by `ws_acf_write_stamp_fields()`), but the display widget in the admin will be a plain text box on jurisdiction records rather than a user-picker. This is a UI inconsistency.
**Pass 2 action:** Change `type` to `user` with `return_format=id` to match all other groups. Since this field is `readonly=1, disabled=1`, the change affects display only, not stored data.

### 4.4 Non-canonical meta name in `jx-summary` summarized-by field
**File:** `acf-jx-summaries.php`
**Field:** `field_ws_jx_sum_plain_english_by_temp`, meta name `ws_jx_sum_create_author`
**Issue:** The canonical meta name used by `ws_acf_stamp_summarized_fields()` is `plain_english_by`. This field uses `ws_jx_sum_create_author` — a completely different name. The stamp hook will write to `plain_english_by`, but the ACF field displaying it reads from `ws_jx_sum_create_author`. The two will never contain the same value. The `_temp` suffix on the key name confirms this was known to be unresolved.
**Pass 2 action:** Change meta name to `plain_english_by`. Rename key from `field_ws_jx_sum_plain_english_by_temp` to `field_plain_english_by`. Align return_format with other CPTs (change from `id` to `id` — already matches). Remove `_temp` from key.

### 4.5 `ws_jx_statute_remedies` references deprecated taxonomy
**File:** `acf-jx-statutes.php`
**Issue:** `field_jx_statute_remedies` points to `'taxonomy' => 'ws_remedy_type'`, which is the deprecated taxonomy retained only during the migration window. It should point to `'taxonomy' => 'ws_remedies'`.
**Pass 2 action:** Change taxonomy to `ws_remedies`.

### 4.6 `last_reviewed` field inconsistency across CPTs
**Files:** `acf-jx-statutes.php` (`ws_jx_statute_last_reviewed`, type=text), `acf-jx-citations.php` (`ws_jx_cite_last_reviewed`, type=text), `acf-jx-interpretations.php` (`ws_interp_last_reviewed`, type=text), `acf-agencies.php` (`ws_agency_last_reviewed`, type=date_picker), `acf-assist-orgs.php` (`ws_ao_last_reviewed`, type=date_picker)
**Issue:** The three jx-* CPTs use a plain text field for last reviewed; the two org CPTs use a date_picker. All five have different meta names. This field is not consumed by the query layer or render layer — it is purely editorial. However inconsistent types and names impede bulk admin tooling and future query work.
**Pass 2 action:** Decide canonical type (date_picker is correct; text was likely used for free-form entry). Standardize meta name to `last_reviewed` across all five. Standardize field keys to `field_last_reviewed`. Coordinate with the planned shared group refactor since these fields will move.

### 4.7 Tab field keys use `tab_` prefix instead of `field_` in three files
**Files:** `acf-jx-statutes.php` (`tab_jx_statute_plain_language_tab`), `acf-jx-citations.php` (`tab_ws_jx_cite_plain_language`), `acf-jx-interpretations.php` (`tab_ws_interp_plain_language`)
**Issue:** Tab fields in all other groups follow the `field_` prefix convention. These three use `tab_`. This is not an ACF bug — ACF accepts any unique string as a key — but it is a convention inconsistency that will become confusing when scanning field keys.
**Pass 2 action:** Rename to `field_` prefix for consistency. Since no hook references these tab keys directly, the change is safe.

### 4.8 Taxonomy ACF fields using taxonomy slug as meta name
**Files:** `acf-agencies.php`, `acf-assist-orgs.php`, `acf-jx-citations.php`
**Issue:** Several taxonomy fields use the taxonomy slug as the ACF meta name:
- `field_ws_agency_jurisdiction` → meta name `ws_jurisdiction` (the taxonomy slug)
- `field_ws_ao_jurisdiction` → meta name `ws_jurisdiction`
- `field_ws_agency_languages` → meta name `ws_languages`
- `field_ws_ao_languages` → meta name `ws_languages`
- `field_ws_jx_disclosure_cat` in citations → meta name `ws_disclosure_type`

When `save_terms=1`, ACF writes the taxonomy term assignments to the taxonomy table (correct). It also writes a post meta key of the same name as the meta name. This creates a post meta entry `ws_jurisdiction` containing the selected term ID(s) **in addition to** the taxonomy term assignment. This is redundant but not broken. The query layer uses `tax_query` not `meta_query` for jurisdiction scoping, so this has no practical effect today. However it is confusing and worth cleaning up to meta names that don't shadow taxonomy slugs.
**Pass 2 action:** Evaluate whether any code reads these post meta keys directly. If not, rename meta names to `ws_agency_jurisdictions`, `ws_ao_jurisdictions`, `ws_agency_languages`, `ws_ao_languages`, `ws_citation_disclosure_type`. Coordinate with query layer review.

### 4.9 `ws_ref_type` returns label instead of value
**File:** `acf-references.php`
**Issue:** `field_ws_ref_type` uses `return_format=label`. Every other select field in the plugin uses `return_format=value`. If the query layer ever reads `ws_ref_type`, it will receive a human-readable string (e.g. `"Academic Paper"`) rather than the stored key (`"academic_paper"`). Conditional logic or filtering by type would need to match against the label string, which is fragile.
**Pass 2 action:** Change to `return_format=value` unless the render layer has an explicit dependency on receiving the label. Check `ws_get_ref_materials()` and `ws_get_reference_page_data()` in `query-jurisdiction.php`.

### 4.10 Typo: `requied` instead of `required` in `acf-jurisdictions.php`
**File:** `acf-jurisdictions.php`
**Lines:** 181, 195 (fields `field_jurisdiction_tax` and `field_jx_code`)
**Issue:** `'requied' => 1` — misspelled key. ACF will not treat these fields as required.
**Pass 2 action:** Correct to `'required' => 1`.

### 4.11 `ws-legal-update` content fields appear outside tabs
**File:** `acf-legal-updates.php`
**Issue:** The `field_authorship_tab` is the first and only tab defined. All content fields (`ws_update_jurisdictions`, `ws_update_date`, `ws_update_source`, etc.) appear before it, which means they render outside any tab in the ACF UI. This is valid ACF behavior but produces an inconsistent editorial experience compared to all other CPTs which use tabs throughout.
**Pass 2 action:** Add a "Content" tab at the top of the field list, before `field_update_jurisdictions`, to bring the group in line with all other CPT field groups.

---

## 5. admin-hooks.php Cross-Reference

| Hook / Filter | Key Referenced | Verified in ACF | Status |
|---|---|---|---|
| `acf/prepare_field/key=field_jx_code` | `field_jx_code` | `acf-jurisdictions.php` | ✅ |
| `acf/prepare_field/key=field_jurisdiction_name` | `field_jurisdiction_name` | `acf-jurisdictions.php` | ✅ |
| `acf/prepare_field/key=field_jurisdiction_class` | `field_jurisdiction_class` | `acf-jurisdictions.php` | ✅ |
| `acf/prepare_field/key=field_jx_flag_source_url` | `field_jx_flag_source_url` | `acf-jurisdictions.php` | ✅ |
| `acf/fields/taxonomy/query/key=field_jurisdiction_tax` | `field_jurisdiction_tax` | `acf-jurisdictions.php` | ✅ |
| `ws_acf_lock_for_non_admins` (name=`ws_ref_approved`) | name-based filter | `acf-references.php` → `ws_ref_approved` | ✅ |
| `ws_acf_plain_english_guards` — resolves field names from `$_POST['acf']` keys | All plain_english_* keys | Duplicate keys present — see §2 | ⚠️ Latent risk, not a current bug |
| `ws_stamp_verified_by_date` reads `$_POST['acf']['field_verification_status']` | `field_verification_status` | `acf-source-verify.php` | ✅ |
| `ws_enforce_source_verify_roles` reads `$_POST['acf']['field_verification_status']` | `field_verification_status` | `acf-source-verify.php` | ✅ |
| `$ws_stamp_cpts['ws-reference']['author_acf_key']` = `field_ws_ref_last_edited_author` | `field_ws_ref_last_edited_author` | `acf-references.php` | ✅ |
| All other stamp CPTs use `field_last_edited_author` | `field_last_edited_author` | Duplicate key across 8 groups | ⚠️ See §2 |

---

## 6. Pass 2 Priority Order

Listed in dependency order — fix lower numbers before higher numbers where noted.

| # | Issue | File(s) | Risk if deferred |
|---|---|---|---|
| 1 | §4.10 Typo `requied` | `acf-jurisdictions.php` | Fields silently not required |
| 2 | §4.5 Deprecated taxonomy `ws_remedy_type` | `acf-jx-statutes.php` | Remedies field reads from deprecated taxonomy; data won't survive taxonomy migration |
| 3 | §4.4 Non-canonical meta name `ws_jx_sum_create_author` | `acf-jx-summaries.php` | Stamp hook writes `plain_english_by`; ACF field reads `ws_jx_sum_create_author` — display always blank |
| 4 | §4.9 `ws_ref_type` returns label | `acf-references.php` | Any future code reading this field gets a label string, not a key |
| 5 | §4.3 `field_create_author` type mismatch | `acf-jurisdictions.php` | UI only; no data impact |
| 6 | §4.2 `plain_english_reviewed` key divergence in jx-summary | `acf-jx-summaries.php` | Maintenance hazard; no current runtime bug |
| 7 | §4.7 `tab_` prefix on plain language tab keys | statutes, citations, interpretations | Convention only; no runtime impact |
| 8 | §4.11 Content fields outside tabs in legal-updates | `acf-legal-updates.php` | UX only |
| 9 | §4.8 Taxonomy slug as meta name | agencies, assist-orgs, citations | Redundant meta; no current query impact |
| 10 | §4.6 `last_reviewed` inconsistency | statutes, citations, interpretations, agencies, assist-orgs | Deferred — resolve during shared group refactor |
| 11 | §2 All duplicate field keys | 8 files | Deferred — resolved by shared group refactor |

---

## 7. Notes for Pass 3 (Instructions)

The following instructions issues were observed and should be addressed in Pass 3. They have no runtime effect.

- `acf-jx-summaries.php` `field_jx_sum_plain_english_reviewed`: instructions contain inline `@todo` comments about readonly behavior and admin panel tracking. These should be removed and resolved or filed as separate tasks.
- `acf-jx-statutes.php` `field_jx_statute_remedies`: instructions still reference `@todo` about taxonomy capability handling — already resolved in `register-taxonomies.php` via `ws_get_taxonomy_caps()`.
- `acf-jx-statutes.php` `field_jx_statute_burden_of_proof`: `@todo` about `varies` selection revealing a hidden textarea. File as a separate enhancement task rather than leaving as inline comment.
- `acf-jx-statutes.php` `field_jx_statute_last_reviewed`: instructions say "Update this date each time the statute record is meaningfully revised" — once standardized to `last_reviewed` across all CPTs, instructions should be consistent.
- `acf-jx-citations.php` field group has `/* --- NEW TAXONOMY FIELD ADDED HERE --- */` comment blocks around `field_ws_jx_disclosure_cat`. These are scaffolding comments that should be removed.
- `acf-legal-updates.php` docblock still references `admin-relationships.php` in context: "This relationship is jurisdiction → update (not managed by admin-relationships.php)". The parenthetical is accurate but can be simplified now that `admin-relationships.php` is fully removed.
