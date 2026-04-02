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
| `acf-jx-citations.php` | `jx-citation` | `group_jx_citation_metadata` |
| `acf-jx-interpretations.php` | `jx-interpretation` | `group_jx_interpretation_metadata` |
| `acf-agencies.php` | `ws-agency` | `group_agency_metadata` |
| `acf-ag-procedures.php` | `ws-ag-procedure` | `group_ag_procedure_metadata` |
| `acf-assist-orgs.php` | `ws-assist-org` | `group_assist_org_metadata` |
| `acf-legal-updates.php` | `ws-legal-update` | `group_legal_update_metadata` |
| `acf-references.php` | `ws-reference` | `group_reference_metadata` |

---

## Field Key Convention

Keys follow the pattern `field_` + meta name with `ws_` prefix stripped.

```
meta name:  ws_jx_statute_official_name
field key:  field_jx_statute_official_name
```

Fields whose meta name appears in multiple CPTs prepend CPT context:

```
field_statute_attach_flag
field_citation_attach_flag
field_interp_attach_flag
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
query pollution. `ws_jx_interp_affected_jx` is the primary example —
auto-populated from court matrix data, must not affect standard
`ws_jurisdiction` taxonomy queries.

---

## Toggle + Conditional Pattern

Several CPTs use a consistent pattern throughout their field groups:

```
[toggle field — true_false]
    └── [detail field — visible only when toggle is on]
```

This keeps edit screens clean while preserving all detail fields.
Examples across statutes: SOL details, tolling, exhaustion, BOP,
rebuttable presumption, reward.

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
