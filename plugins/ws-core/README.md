# ws-core

The core plugin for WhistleblowerShield.org. Implements the complete data
model, editorial workflow, and public-facing output for the platform.

**Stack:** WordPress + ACF Pro
**Requires:** PHP 8.0+, WordPress 6.0+, ACF Pro
**Version:** 3.14.0

Full project documentation is in `/documentation/`. This file covers the
rules a developer needs open while writing code.

---

## Architecture

Six layers, loaded in strict dependency order by `includes/loader.php`:

```
Universal Layer   CPTs, taxonomies, ACF field groups, query functions
                  Loaded on frontend and admin
Matrix Layer      Idempotent seeders — run once on install (admin only)
Admin Layer       ACF hooks, audit trail, monitoring, dashboard (admin only)
Tools Layer       Admin tools in includes/admin/tools/ — prompt generator,
                  ingest tool. Write to wp-content/logs/ws-ingest/.
Assembly Layer    Render functions + shortcodes → HTML (frontend only)
Assets            Conditionally loaded CSS + JS
```

**Assembly Layer = render functions + shortcodes only.** The query layer
is the Universal Layer — a prerequisite of the Assembly Layer, not part
of it. Never refer to the query layer as part of the "assembly layer."

**The query layer contract** is the most important rule in the codebase:
shortcodes, render functions, and admin surfaces never call `get_field()`,
`get_post_meta()`, or `WP_Query` directly. All data retrieval goes through
`includes/queries/`. Admin files that must bypass this (columns, hooks,
metaboxes) carry inline comments explaining why.

---

## CPT Registry

11 CPTs registered. One file per CPT in `includes/cpt/`.

| Slug | Purpose | Public |
|---|---|---|
| `jurisdiction` | One post per U.S. jurisdiction (57 total) | Yes |
| `jx-summary` | Plain-language summary for each jurisdiction | No |
| `jx-statute` | Codified statutory whistleblower protections | No |
| `jx-common-law` | Judicially-recognized common law protections | No |
| `jx-citation` | Case law citations supporting statute records | No |
| `jx-interpretation` | Court rulings interpreting specific statutes | No |
| `ws-agency` | Enforcement and oversight agencies | Yes |
| `ws-ag-procedure` | Agency-specific filing procedures | Yes |
| `ws-assist-org` | Legal aid and advocacy organizations | Yes |
| `ws-legal-update` | Legal development notices | No |
| `ws-reference` | External reference materials | Yes |

### jx-common-law

Added in v3.13.0. Stores judicially-recognized common law whistleblower
protection doctrines — public policy exceptions to at-will employment,
implied covenant claims, constitutional protections — for jurisdictions
that lack codified statutory protections or where common law supplements
statutes.

Key differences from `jx-statute`:
- Anchor is a judicial doctrine, not a statute section. `ws_cl_doctrine_id`
  (format: `[JX]-CL-[SHORT-SLUG]`) replaces `statute_id` as the stable
  pipeline identifier used in prompt exclusion lists.
- `ws_cl_doctrine_basis` and `ws_cl_recognition_status` are WYSIWYG fields
  that carry the primary explanatory content.
- `ws_cl_statutory_preclusion` boolean flags jurisdictions where the common
  law claim is unavailable when a statutory remedy exists (Wyoming pattern).
- `ws_cl_public_policy_sources` checkbox tracks what sources of law the
  jurisdiction accepts as establishing public policy (constitution, statute,
  administrative-rule, case-law, federal-law, other).
- `ws_cl_other_sources` freetext companion — visible when `other` is checked.
- `ws_cl_precedent_url` links to the leading case on an approved source.
- SOL is almost always `limit_ambiguous: true` — common law claims borrow
  limitations periods from analogous statutes.

Uses the same taxonomy palette as `jx-statute` and participates in the
same query layer pattern via `ws_get_jx_common_law_data()`.
Render stub: `render-common-law.php` — implement when Wyoming data build begins.

---

## Taxonomy Registry

17 taxonomies registered in `includes/taxonomies/register-taxonomies.php`.

### Shared doctrinal taxonomies

These attach to `jx-statute`, `jx-citation`, `jx-interpretation`, and
`jx-common-law`. All support `tax_query` filtering.

| Slug | Type | Notes |
|---|---|---|
| `ws_disclosure_type` | hierarchical | 6 parents, 26 children |
| `ws_protected_class` | hierarchical | 4 parents, 12 children + `has-details` |
| `ws_disclosure_targets` | hierarchical | 5 parents, 13 children + `has-details` |
| `ws_adverse_action_types` | flat | 14 terms + `has-details` |
| `ws_process_type` | flat | 9 terms |
| `ws_remedies` | flat | 20 terms + `has-details` |
| `ws_fee_shifting` | flat | 4 terms |
| `ws_employer_defense` | flat | 6 terms + `has-details` |
| `ws_employee_standard` | flat | 6 terms + `has-details` |

### has-details sentinel pattern

Five taxonomies support a `has-details` sentinel slug. When selected,
a companion ACF freetext `_details` field becomes visible on the edit
screen. The sentinel signals that the record contains nuance beyond what
the registered slugs capture. Applies to `jx-statute`, `jx-common-law`,
`jx-citation`, and `jx-interpretation`.

Companion field mapping:
```
protected_class     → *_protected_class_details
disclosure_targets  → *_disclosure_targets_details
adverse_action      → *_adverse_action_details
remedies            → *_remedies_details
employer_defense    → *_employer_defense_details
employee_standard   → *_employee_standard_details
```

### Other taxonomies

| Slug | Attaches To | Notes |
|---|---|---|
| `ws_jurisdiction` | All content CPTs | Canonical join key. USPS slug. |
| `ws_languages` | `ws-agency`, `ws-assist-org` | `additional` is a system sentinel |
| `ws_case_stage` | `ws-assist-org` | Phase 2 filter axis |
| `ws_aorg_type` | `ws-assist-org` | Single-value |
| `ws_employment_sector` | `ws-assist-org` | Phase 2 filter axis |
| `ws_aorg_cost_model` | `ws-assist-org` | Single-value |
| `ws_aorg_service` | `ws-assist-org` | `additional` is a system sentinel |
| `ws_procedure_type` | `ws-ag-procedure` | 3 stable terms |

---

## Naming Conventions

### ACF Field Keys

These rules govern ACF `key` values only — not `name` (meta key), `label`,
or any other property.

1. No `ws_` prefix on field keys. `field_` is sufficient namespacing.
2. Group keys end with `_metadata`. Example: `group_jx_statute_metadata`.
3. Tab field keys end with `_tab`. Example: `field_legal_basis_tab`.
4. Field key = `field_` + meta name with `ws_` prefix stripped.
   Example: `ws_jx_statute_official_name` → `field_jx_statute_official_name`.
5. Fields whose meta name appears in multiple groups (e.g. `ws_attach_flag`,
   `ws_display_order`, `ws_ref_materials`) prepend CPT context to disambiguate.

### Post Meta Keys

1. All custom meta keys carry a `ws_` prefix. No bare unprefixed keys.
2. Auto-stamp keys carry the `ws_auto_` prefix (written by hook logic only).
3. Private audit-only keys additionally carry a leading underscore:
   `_ws_auto_date_created_gmt`, `_ws_auto_last_edited_gmt`.
4. Content CPT meta keys carry a CPT infix:
   `ws_jx_*` (statute), `ws_cl_*` (common-law), `ws_agency_*`, `ws_aorg_*`,
   `ws_legal_update_*`, `ws_jx_interp_*`, `ws_jx_citation_*`, `ws_proc_*`.
5. Meta key infixes and CPT slugs are always singular. When in doubt, singular wins.

### Render Function Names

Render functions are named after their **data type**, not the page section
they produce.

```php
ws_render_jx_common_law()   // correct — data type
ws_render_jx_case_law()     // wrong — section name
```

---

## Date Conventions

All date values written to post meta by plugin code use:

```php
current_time( 'Y-m-d' )   // local site date, date-only
```

GMT audit timestamps use `gmdate( 'Y-m-d' )`.
`current_time( 'mysql' )` is reserved for `post_date` arguments only.

---

## Query Layer Return Keys

The query layer strips all `ws_` and `ws_auto_` prefixes from return keys.

```
record  → created_by, created_by_name, created_date,
          edited_by, edited_by_name, edited_date

plain   → has_content, plain_content, written_by, written_by_name,
          written_date, is_reviewed, reviewed_by, reviewed_by_name,
          reviewed_date

verify  → source_method, source_name, verified_by, verified_by_name,
          verified_date, verify_status, needs_review
```

---

## Seeder Gates

All matrix seeders use a `ws_seeded_{slug}` option key with a semver string
value. To re-run a seeder, bump its version string — never delete the option.

---

## Version History

| Version | Summary |
|---|---|
| 1.0.0 | Initial release |
| 2.1.0 | Refactored for ws-core architecture |
| 2.3.1 | Citations, agencies, legal updates added |
| 3.0.0 | Taxonomy join replaces post meta join; attach-flag pattern; federal append; matrix seeders |
| 3.1.0 | ACF key naming rules; meta key naming rules; query layer return key standardization |
| 3.2.0 | `ws_auto_` prefix pass; legal update system overhaul |
| 3.3.0 | Dataset completeness pass; source verify system; query layer split |
| 3.4.0 | Admin layer audit; plain English fields centralized; source verify role gates |
| 3.5.0 | jx-statute ingest alignment; `ws_employer_defense` taxonomy; ACF overhaul |
| 3.6.0 | Query layer split (helpers/shared/jurisdiction/agencies); render naming rules |
| 3.7.0 | `ws_employment_sector` taxonomy; deprecated taxonomy cleanup |
| 3.8.0 | Court matrix split; interpretation system; reference page implementation |
| 3.8.1 | Post-audit pass — PHP 8 fatal fix, output escaping, race conditions |
| 3.9.0 | `ws-ag-procedure` CPT; agency render pipeline; procedure seeder |
| 3.10.0 | `ws_procedure_type` taxonomy; source verify for procedures |
| 3.11.0 | `has-details` sentinel added to 5 taxonomies |
| 3.12.0 | `ws_employee_standard` taxonomy; ACF companion field pattern for has-details |
| 3.13.0 | `jx-common-law` CPT + ACF + query function + render stub; all shared taxonomies updated to include jx-common-law; `ws_cl_doctrine_id`, `ws_cl_statutory_preclusion`, `ws_cl_public_policy_sources`, `ws_cl_precedent_url` fields |
| 3.13.1 | `tool-generate-prompt.php` added to `includes/admin/tools/`; reads live taxonomy data via `get_terms()` — no hardcoded arrays |
| 3.14.0 | `tool-ingest.php` added; ACF field names renamed throughout `acf-jx-statutes.php` and `acf-jx-common-law.php` to match JSON schema keys; four ingest log files added to `wp-content/logs/ws-ingest/` |
