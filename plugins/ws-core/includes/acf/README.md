# includes/acf/

ACF Pro field group registration for all ws-core CPTs.

Each file in this directory registers one field group for one CPT.
Shared workflow field groups (stamp, plain English, source verify,
major edit) live in `workflow/` — see `workflow/README.md`.

---

## Files

| File | CPT | Group Key |
|---|---|---|
| `acf-jurisdictions.php` | `jurisdiction` | `group_jurisdiction_metadata` |
| `acf-jx-summaries.php` | `jx-summary` | `group_jx_summary_metadata` |
| `acf-jx-statutes.php` | `jx-statute` | `group_jx_statute_metadata` |
| `acf-jx-common-law.php` | `jx-common-law` | `group_jx_common_law_metadata` |
| `acf-jx-citations.php` | `jx-citation` | `group_jx_citation_metadata` |
| `acf-jx-interpretations.php` | `jx-interpretation` | `group_jx_interpretation_metadata` |
| `acf-agencies.php` | `ws-agency` | `group_agency_metadata` |
| `acf-ag-procedures.php` | `ws-ag-procedure` | `group_ag_procedure_metadata` |
| `acf-assist-orgs.php` | `ws-assist-org` | `group_assist_org_metadata` |
| `acf-legal-updates.php` | `ws-legal-update` | `group_legal_update_metadata` |
| `acf-references.php` | `ws-reference` | `group_reference_metadata` |

---

## v3.14.0 Field Rename Pass

All field names in `acf-jx-statutes.php` and `acf-jx-common-law.php`
were renamed to match the JSON ingest schema keys exactly. All downstream
references in `query-jurisdiction.php` and `matrix-fed-statutes.php`
were updated in the same pass.

| Old name | New name | Applies to |
|---|---|---|
| `*_sol_has_details` | `*_limit_ambiguous` | statute, common-law |
| `*_sol_details` | `*_limit_details` | statute, common-law |
| `*_tolling_has_details` | `*_tolling_has_notes` | statute, common-law |
| `*_tolling_details` | `*_tolling_notes` | statute, common-law |
| `*_has_exhaustion` | `*_exhaustion_required` | statute, common-law |
| `*_rebuttable_has_details` | `*_rebuttable_has_presumption` | statute, common-law |
| `*_rebuttable_details` | `*_rebuttable_presumption` | statute, common-law |
| `*_bop_details` | `*_burden_of_proof_details` | statute, common-law |
| `*_has_reward` | `*_reward_available` | statute, common-law |
| `*_url_is_pdf` | `*_is_pdf` | statute only |

New field added: `ws_jx_statute_bop_flag` / `ws_cl_bop_flag` — short
signal phrase for non-standard burden shifts (text, 120 char max).

---

## acf-jx-statutes.php — Field Summary

**Legal Basis tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_official_name` | text | Required |
| `ws_jx_statute_citation` | text | |
| `ws_jx_statute_common_name` | text | |
| `ws_jx_statute_disclosure_type` | taxonomy | `ws_disclosure_type` |
| `ws_jx_statute_protected_class` | taxonomy | `ws_protected_class` — has-details |
| `ws_jx_statute_protected_class_details` | textarea | Conditional on has-details |
| `ws_jx_statute_disclosure_targets` | taxonomy | `ws_disclosure_targets` — has-details |
| `ws_jx_statute_disclosure_targets_details` | textarea | Conditional on has-details |
| `ws_jx_statute_adverse_action_scope` | textarea | |
| `ws_attach_flag` | true_false | Editorial curation flag |
| `ws_display_order` | number | Conditional on attach_flag |

**Statute of Limitations tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_sol_value` | number | |
| `ws_jx_statute_sol_unit` | select | days / months / years |
| `ws_jx_statute_sol_trigger` | select | |
| `ws_jx_statute_limit_ambiguous` | true_false | SOL derived, not explicit |
| `ws_jx_statute_limit_details` | textarea | Conditional on limit_ambiguous |
| `ws_jx_statute_tolling_has_notes` | true_false | Tolling provisions exist |
| `ws_jx_statute_tolling_notes` | textarea | Conditional on tolling_has_notes |
| `ws_jx_statute_exhaustion_required` | true_false | |
| `ws_jx_statute_exhaustion_details` | textarea | Conditional on exhaustion_required |

**Enforcement tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_process_type` | taxonomy | `ws_process_type` |
| `ws_jx_statute_adverse_action` | taxonomy | `ws_adverse_action_types` — has-details |
| `ws_jx_statute_adverse_action_details` | textarea | Conditional on has-details |
| `ws_jx_statute_fee_shifting` | taxonomy | `ws_fee_shifting` |
| `ws_jx_statute_remedies` | taxonomy | `ws_remedies` — has-details |
| `ws_jx_statute_remedies_details` | textarea | Conditional on has-details |
| `ws_jx_statute_related_agencies` | post_object | `ws-agency` |

**Burden of Proof tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_employee_standard` | taxonomy | `ws_employee_standard` — has-details |
| `ws_jx_statute_employee_standard_details` | textarea | Conditional on has-details |
| `ws_jx_statute_employer_defense` | taxonomy | `ws_employer_defense` — has-details |
| `ws_jx_statute_employer_defense_details` | textarea | Conditional on has-details |
| `ws_jx_statute_rebuttable_has_presumption` | true_false | |
| `ws_jx_statute_rebuttable_presumption` | textarea | Conditional on rebuttable_has_presumption |
| `ws_jx_statute_bop_has_details` | true_false | Derived at ingest from presence of burden_of_proof_details |
| `ws_jx_statute_burden_of_proof_details` | textarea | Conditional on bop_has_details |
| `ws_jx_statute_bop_flag` | text | Short burden-shift signal phrase, 120 char max |

**Reward tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_reward_available` | true_false | |
| `ws_jx_statute_reward_details` | textarea | Conditional on reward_available |

**Links tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_statute_url` | url | |
| `ws_jx_statute_is_pdf` | true_false | |
| `ws_jx_statute_last_reviewed` | text | Date string |

---

## acf-jx-common-law.php — Field Summary

Added v3.13.0. Mirrors `acf-jx-statutes.php` with `ws_cl_` prefix
and doctrine-specific adaptations in the Legal Basis tab.

**Legal Basis tab (unique to common-law):**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_cl_doctrine_id` | text | Required. Format: `[JX]-CL-[SHORT-SLUG]` |
| `ws_cl_doctrine_name` | text | |
| `ws_cl_common_name` | text | |
| `ws_cl_precedent_url` | url | Leading case on approved source |
| `ws_cl_public_policy_sources` | checkbox | constitution, statute, administrative-rule, case-law, federal-law, other |
| `ws_cl_other_sources` | text | Conditional on `other` in public_policy_sources |
| `ws_cl_doctrine_basis` | wysiwyg | Required. Primary explanatory content |
| `ws_cl_recognition_status` | wysiwyg | Required. Current judicial status |
| `ws_cl_statutory_preclusion` | true_false | Bars common law claim when statutory remedy exists |
| `ws_cl_statutory_preclusion_details` | textarea | Conditional on statutory_preclusion |
| `ws_cl_disclosure_type` | taxonomy | `ws_disclosure_type` |
| `ws_cl_protected_class` | taxonomy | `ws_protected_class` — has-details |
| `ws_cl_protected_class_details` | textarea | Conditional on has-details |
| `ws_cl_disclosure_targets` | taxonomy | `ws_disclosure_targets` — has-details |
| `ws_cl_disclosure_targets_details` | textarea | Conditional on has-details |
| `ws_cl_adverse_action_scope` | textarea | |
| `ws_attach_flag` | true_false | |
| `ws_display_order` | number | |

**SOL tab:** Same fields as statutes with `ws_cl_` prefix:
`ws_cl_sol_value`, `ws_cl_sol_unit`, `ws_cl_sol_trigger`,
`ws_cl_limit_ambiguous`, `ws_cl_limit_details`,
`ws_cl_tolling_has_notes`, `ws_cl_tolling_notes`,
`ws_cl_exhaustion_required`, `ws_cl_exhaustion_details`.
SOL is almost always `limit_ambiguous: true` for common law.

**Enforcement tab:** Same as statutes with `ws_cl_` prefix:
`ws_cl_process_type`, `ws_cl_adverse_action`, `ws_cl_adverse_action_details`,
`ws_cl_fee_shifting`, `ws_cl_remedies`, `ws_cl_remedies_details`,
`ws_cl_related_agencies`.

**Burden of Proof tab:** Same as statutes with `ws_cl_` prefix:
`ws_cl_employee_standard`, `ws_cl_employee_standard_details`,
`ws_cl_employer_defense`, `ws_cl_employer_defense_details`,
`ws_cl_rebuttable_has_presumption`, `ws_cl_rebuttable_presumption`,
`ws_cl_bop_has_details`, `ws_cl_burden_of_proof_details`,
`ws_cl_bop_flag`.

**Reward tab:** `ws_cl_reward_available`, `ws_cl_reward_details`.

---

## acf-jx-citations.php — Field Summary

**Content tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_citation_type` | select | |
| `ws_jx_citation_disclosure_type` | taxonomy | `ws_disclosure_type` |
| `ws_jx_citation_official_name` | text | |
| `ws_jx_citation_common_name` | text | |
| `ws_jx_citation_url` | url | |
| `ws_jx_citation_is_pdf` | true_false | |
| `ws_attach_flag` | true_false | |
| `ws_display_order` | number | |

**Classification tab:**

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_citation_protected_class` | taxonomy | `ws_protected_class` — has-details |
| `ws_jx_citation_protected_class_details` | textarea | |
| `ws_jx_citation_disclosure_targets` | taxonomy | `ws_disclosure_targets` — has-details |
| `ws_jx_citation_disclosure_targets_details` | textarea | |
| `ws_jx_citation_adverse_action` | taxonomy | `ws_adverse_action_types` — has-details |
| `ws_jx_citation_adverse_action_details` | textarea | |
| `ws_jx_citation_process_type` | taxonomy | `ws_process_type` |
| `ws_jx_citation_remedies` | taxonomy | `ws_remedies` — has-details |
| `ws_jx_citation_remedies_details` | textarea | |
| `ws_jx_citation_fee_shifting` | taxonomy | `ws_fee_shifting` |
| `ws_jx_citation_employer_defense` | taxonomy | `ws_employer_defense` — has-details |
| `ws_jx_citation_employer_defense_details` | textarea | |
| `ws_jx_citation_employee_standard` | taxonomy | `ws_employee_standard` — has-details |
| `ws_jx_citation_employee_standard_details` | textarea | |
| `ws_jx_citation_statute_ids` | textarea | Pipe-delimited statute IDs this citation supports |
| `ws_ref_materials` | relationship | `ws-reference` |
| `ws_jx_citation_last_reviewed` | text | |

---

## acf-jx-interpretations.php — Field Summary

| Meta Key | Type | Notes |
|---|---|---|
| `ws_jx_interp_official_name` | text | |
| `ws_jx_interp_common_name` | text | |
| `ws_jx_interp_case_citation` | text | |
| `ws_jx_interp_court` | select | Court matrix shorthand |
| `ws_jx_interp_court_name` | text | |
| `ws_jx_interp_year` | text | |
| `ws_jx_interp_favorable` | true_false | Only when ruling materially expands protection |
| `ws_jx_interp_summary` | textarea | |
| `ws_jx_interp_url` | url | |
| `ws_jx_interp_statute_id` | text | Anchor statute |
| `ws_jx_interp_affected_jx` | taxonomy | `ws_jurisdiction` — `save_terms: 0` |
| `ws_jx_interp_disclosure_type` | taxonomy | `ws_disclosure_type` |
| `ws_jx_interp_protected_class` | taxonomy | `ws_protected_class` — has-details |
| `ws_jx_interp_protected_class_details` | textarea | |
| `ws_jx_interp_disclosure_targets` | taxonomy | `ws_disclosure_targets` — has-details |
| `ws_jx_interp_disclosure_targets_details` | textarea | |
| `ws_jx_interp_adverse_action` | taxonomy | `ws_adverse_action_types` — has-details |
| `ws_jx_interp_adverse_action_details` | textarea | |
| `ws_jx_interp_process_type` | taxonomy | `ws_process_type` |
| `ws_jx_interp_remedies` | taxonomy | `ws_remedies` — has-details |
| `ws_jx_interp_remedies_details` | textarea | |
| `ws_jx_interp_fee_shifting` | taxonomy | `ws_fee_shifting` |
| `ws_jx_interp_employer_defense` | taxonomy | `ws_employer_defense` — has-details |
| `ws_jx_interp_employer_defense_details` | textarea | |
| `ws_jx_interp_employee_standard` | taxonomy | `ws_employee_standard` — has-details |
| `ws_jx_interp_employee_standard_details` | textarea | |
| `ws_attach_flag` | true_false | |
| `ws_display_order` | number | |
| `ws_ref_materials` | relationship | `ws-reference` |
| `ws_jx_interp_last_reviewed` | text | |

Note: `ws_jx_interp_affected_jx` uses `save_terms: 0` to prevent
taxonomy query pollution — interpretations are scoped to the
jurisdiction of the statute they interpret, not to the jurisdictions
they may affect as precedent.

---

## Field Key Convention

Keys follow the pattern `field_` + meta name with `ws_` prefix stripped.

```
meta name:  ws_jx_statute_official_name
field key:  field_jx_statute_official_name

meta name:  ws_cl_doctrine_id
field key:  field_jx_cl_doctrine_id
```

Fields whose meta name appears in multiple CPTs prepend CPT context:

```
field_jx_statute_attach_flag
field_jx_cl_attach_flag
field_jx_citation_attach_flag
```

Group keys end with `_metadata`. Tab keys end with `_tab`.
No `ws_` prefix on any ACF key — `field_` is sufficient namespacing.

---

## `save_terms` Convention

Every taxonomy ACF field that should write term assignments to the
WordPress taxonomy table carries `save_terms: 1` and `load_terms: 1`.
This is what makes `tax_query` filtering work in the query layer and
allows matrix seeders to use `wp_set_object_terms()` without requiring
an ACF save cycle.

`ws_jx_interp_affected_jx` explicitly uses `save_terms: 0` to prevent
taxonomy query pollution. See note in interpretations field summary above.

---

## Toggle + Conditional Pattern

Several CPTs use a consistent pattern:

```
[toggle field — true_false]
    └── [detail field — visible only when toggle is on]
```

Keeps edit screens clean while preserving all detail fields.

---

## has-details Sentinel Pattern

Six taxonomies support a `has-details` sentinel term. When selected,
a companion `_details` textarea becomes visible via dynamic conditional
logic injected at field load time (not at registration — term IDs are
runtime values).

Valid taxonomies for has-details:
- `ws_protected_class`
- `ws_disclosure_targets`
- `ws_adverse_action_types`
- `ws_remedies`
- `ws_employer_defense`
- `ws_employee_standard`

`ws_disclosure_type` and `ws_process_type` do NOT support has-details.
If has-details appears in either taxonomy on an ingest record it is an
invalid slug and will be stripped by the ingest tool.

This pattern is active on `jx-statute`, `jx-common-law`, `jx-citation`,
and `jx-interpretation`.

---

## `menu_order` Stacking

```
0–80   CPT-specific group
85     Plain English (group_plain_english_metadata)
90     Stamp fields (group_stamp_metadata)
95     Source verify (group_source_verify_metadata)
99     Major edit (group_major_edit_metadata)
```

---

## Shared Workflow Groups

See `workflow/README.md` for the four shared groups.

`acf-stamp-fields.php` attaches to: `jx-summary`, `jx-statute`,
`jx-common-law`, `jx-citation`, `jx-interpretation`, `ws-agency`,
`ws-ag-procedure`, `ws-assist-org`, `ws-legal-update`, `ws-reference`.

`acf-plain-english-fields.php` attaches to: `jx-statute`,
`jx-common-law`, `jx-citation`, `jx-interpretation`, `ws-agency`.
