# ws-core

The core plugin for WhistleblowerShield.org. Implements the complete data
model, editorial workflow, and public-facing output for the platform.

**Stack:** WordPress + ACF Pro
**Requires:** PHP 8.0+, WordPress 6.0+, ACF Pro
**Version:** 3.10.0

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
   Example: `field_statute_attach_flag`, `field_citation_attach_flag`.

### Post Meta Keys

These rules govern all custom meta key `name` values written to `wp_postmeta`.

1. All custom meta keys carry a `ws_` prefix. No bare unprefixed keys.
2. Auto-stamp keys — written exclusively by hook logic, never by human
   input — carry the `ws_auto_` prefix:
   `ws_auto_date_created`, `ws_auto_last_edited`,
   `ws_auto_create_author`, `ws_auto_last_edited_author`,
   `ws_auto_source_method`, `ws_auto_source_name`,
   `ws_auto_verified_by`, `ws_auto_verified_date`,
   `ws_auto_plain_english_by`, `ws_auto_plain_english_date`,
   `ws_auto_plain_english_reviewed_by`, `ws_auto_plain_english_reviewed_date`.
3. Private audit-only keys additionally carry a leading underscore per the
   WordPress hidden-meta convention:
   `_ws_auto_date_created_gmt`, `_ws_auto_last_edited_gmt`.
4. Content CPT meta keys carry a CPT infix:
   `ws_jx_*`, `ws_agency_*`, `ws_aorg_*`,
   `ws_legal_update_*`, `ws_jx_interp_*`, `ws_jx_citation_*`, `ws_proc_*`.
5. Data-type suffixes: `_url` (URL string), `_wysiwyg` (rich-text),
   `_id` (integer foreign key or term ID).
6. Meta key infixes and CPT slugs are always singular:
   `ws_aorg_*` not `ws_aorgs_*`, `ws_agency_*` not `ws_agencies_*`.
   PHP source filenames may be plural. When in doubt, singular wins.

### Render Function Names

Render functions are named after their **data type**, not the page section
they produce. The data type is unambiguous; the section name requires context.

```php
ws_render_jx_citations()   // correct — data type
ws_render_jx_case_law()    // wrong — section name
```

Exception: wrapper functions that compose multiple data types into a named
page region may use a section name, provided the docblock lists every data
type consumed.

---

## Date Conventions

All date values written to post meta by plugin code use:

```php
current_time( 'Y-m-d' )   // local site date, date-only
```

GMT audit timestamps (hidden `_ws_auto_*_gmt` keys) use `gmdate( 'Y-m-d' )`.
`current_time( 'mysql' )` is reserved for `wp_insert_post` / `wp_update_post`
`post_date` arguments only — never for custom meta keys.

---

## Query Layer Return Keys

The query layer strips all `ws_` and `ws_auto_` prefixes from PHP array
return keys. Meta key naming rules govern what is stored in the database;
they do not govern what is exposed through the query layer API.

Within each sub-array, keys are scoped to their context with no prefix:

```
record  → created_by, created_by_name, created_date,
          edited_by, edited_by_name, edited_date

plain   → has_content, plain_content, written_by, written_by_name,
          written_date, is_reviewed, reviewed_by, reviewed_by_name,
          reviewed_date

verify  → source_method, source_name, verified_by, verified_by_name,
          verified_date, verify_status, needs_review
```

The `ws_` prefix prevents collisions in `wp_postmeta`. Inside a PHP return
array there is no collision risk and the prefix adds noise. See
`includes/queries/query-jurisdiction.php` for the full per-function key
reference.

---

## Seeder Gates

All matrix seeders use a `ws_seeded_{slug}` option key with a semver string
value. To re-run a seeder, bump its version string — never delete the option.
The `WS_MATRIX_SEEDING_IN_PROGRESS` constant prevents seeders from triggering
false divergence flags in `admin-matrix-watch.php`.

---

## Version History

| Version | Summary |
|---|---|
| 1.0.0 | Initial release |
| 2.1.0 | Refactored for ws-core architecture |
| 2.3.1 | Citations, agencies, legal updates added |
| 3.0.0 | Taxonomy join replaces post meta join; attach-flag pattern; federal append; relationship fields removed; matrix seeders |
| 3.1.0 | ACF key naming rules; meta key naming rules; stamp field audit; query layer return key standardization |
| 3.2.0 | `ws_auto_` prefix pass; legal update system overhaul; taxonomy join for legal updates |
| 3.3.0 | Dataset completeness pass; source verify system; query layer split into four files |
| 3.4.0 | Admin layer audit; plain English fields centralized; source verify role gates |
| 3.5.0 | jx-statute ingest alignment; `ws_employer_defense` taxonomy; ACF overhaul |
| 3.6.0 | Query layer split (helpers/shared/jurisdiction/agencies); render function naming rules |
| 3.7.0 | `ws_employment_sector` taxonomy; deprecated taxonomy cleanup; nationwide assist org query |
| 3.8.0 | Court matrix split; interpretation system; section anchors; reference page implementation |
| 3.8.1 | Post-audit pass — PHP 8 fatal fix, output escaping, race conditions, memory, caching |
| 3.9.0 | `ws-ag-procedure` CPT; agency render pipeline; statute cross-reference validation; procedure seeder |
| 3.10.0 | `ws_procedure_type` taxonomy; source verify coverage for procedures |
