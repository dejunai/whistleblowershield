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

## acf-jx-common-law.php

Added v3.13.0. Mirrors `acf-jx-statutes.php` structure with
doctrine-specific adaptations in the Legal Basis tab:

**Unique fields (no statute equivalent):**
- `ws_cl_doctrine_id` — required. Format: `[JX]-CL-[SHORT-SLUG]`.
  Used in AI pipeline exclusion lists to prevent duplicate records.
- `ws_cl_precedent_url` — URL to the leading case on an approved source.
- `ws_cl_public_policy_sources` — checkbox: constitution, statute,
  administrative-rule, case-law, federal-law, other.
- `ws_cl_other_sources` — freetext companion, visible when `other` checked.
- `ws_cl_doctrine_basis` — WYSIWYG, required. Primary explanatory content.
- `ws_cl_recognition_status` — WYSIWYG, required. Current judicial status.
- `ws_cl_statutory_preclusion` — true/false. Flags jurisdictions where
  the common law claim is barred when a statutory remedy exists.
- `ws_cl_statutory_preclusion_details` — textarea, conditional.

All other tabs (SOL, Enforcement, Burden of Proof, Reward, Reference
Materials) mirror `jx-statute` with `ws_cl_` prefix. The `has-details`
sentinel pattern is active on all supporting taxonomies.

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
what allows matrix seeders to use `wp_set_object_terms()` without
requiring an ACF save cycle to recognize the terms.

Fields that explicitly use `save_terms: 0` do so to prevent taxonomy
query pollution. `ws_jx_interp_affected_jx` is the primary example.

---

## Toggle + Conditional Pattern

Several CPTs use a consistent pattern throughout their field groups:

```
[toggle field — true_false]
    └── [detail field — visible only when toggle is on]
```

This keeps edit screens clean while preserving all detail fields.

---

## has-details Sentinel Pattern

Five taxonomies support a `has-details` sentinel term. When selected
in a taxonomy multi-select field, a companion `_details` textarea
becomes visible via dynamic conditional logic injected at field load
time (not at registration time — term IDs are runtime values).

This pattern is active on `jx-statute`, `jx-common-law`, `jx-citation`,
and `jx-interpretation`. Each CPT's ACF file carries the
`ws_jx_{cpt}_details_conditional()` filter function that injects
the conditional logic at load time.

---

## `menu_order` Stacking

Field groups stack on the CPT edit screen in `menu_order` sequence:

```
0–80   CPT-specific group (e.g. group_jx_statute_metadata)
85     Plain English (group_plain_english_metadata)
90     Stamp fields (group_stamp_metadata)
95     Source verify (group_source_verify_metadata)
99     Major edit (group_major_edit_metadata)
```

Shared workflow groups always appear after the CPT content group.

---

## Shared Workflow Groups

See `workflow/README.md` for documentation of the four shared groups
that attach to multiple CPTs: stamp fields, plain English, source
verify, and major edit.

`acf-stamp-fields.php` attaches to: `jx-summary`, `jx-statute`,
`jx-common-law`, `jx-citation`, `jx-interpretation`, `ws-agency`,
`ws-ag-procedure`, `ws-assist-org`, `ws-legal-update`, `ws-reference`.

`acf-plain-english-fields.php` attaches to: `jx-statute`,
`jx-common-law`, `jx-citation`, `jx-interpretation`, `ws-agency`.
