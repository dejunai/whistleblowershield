# ws-core System Reference

## What This Document Is

The complete reference for the `ws-core` WordPress plugin — the single
plugin that implements the entire WhistleblowerShield platform. Covers
file structure, all CPTs, all taxonomies, all constants, all naming
conventions, and the load order rules that govern how the plugin
initializes.

This document does not describe individual field groups (see
`ws-core-data-layer.md`), query layer functions (see
`ws-core-query-layer.md`), or render functions and shortcodes (see
`ws-core-output-layer.md`).

---

## Plugin Identity

| Property | Value |
|---|---|
| Plugin Name | WhistleblowerShield Core |
| Plugin File | `ws-core/ws-core.php` |
| Current Version | 3.10.0 |
| Requires | WordPress 6.0+, ACF Pro |
| Text Domain | `ws-core` |

The plugin bootstraps via `plugins_loaded` to ensure ACF Pro and all
other plugins are fully initialized before any ws-core module loads.
If ACF Pro is absent, an admin notice fires and the bootstrap aborts —
nothing else loads.

---

## Directory Structure

```
ws-core/
├── ws-core.php                     Main plugin file — bootstrap, constants, asset enqueue
├── ws-core-front-general.css       Frontend styles for general shortcodes (all pages)
├── ws-core-front-jx.css            Frontend styles for jurisdiction pages only
├── ws-core-front.js                Jurisdiction index filter tab logic
│
└── includes/
    ├── loader.php                  Centralized file loader — all layers
    │
    ├── cpt/                        Custom Post Type registrations (10 files)
    ├── taxonomies/                 Taxonomy registrations + seeders (2 files)
    ├── acf/                        ACF field group registrations (14 files)
    ├── queries/                    Query layer — data retrieval API (4 files)
    ├── render/                     Assembly layer — HTML output (5 files)
    ├── shortcodes/                 Shortcode registrations (2 files)
    └── admin/                      Admin layer (12 files + matrix/ subdirectory)
        └── matrix/                 Matrix seeders + divergence watch (9 files)
```

---

## Custom Post Types

Ten CPTs are registered. The `jx-` prefix denotes jurisdiction addendum
CPTs (non-public, scoped to a parent jurisdiction). The `ws-` prefix
denotes site-wide or directory CPTs.

| Slug | Label | Public | Archive URL | Purpose |
|---|---|---|---|---|
| `jurisdiction` | Jurisdiction | ✓ | `/jurisdiction/{slug}/` | Primary page for each of the 57 jurisdictions |
| `jx-summary` | Jurisdiction Summary | — | — | Plain-language overview of protections; one per jurisdiction |
| `jx-statute` | Statute | — | — | Individual statute or regulation record |
| `jx-citation` | Jurisdiction Citation | — | — | Case law citation; attach-flag for curation |
| `jx-interpretation` | Statute Interpretation | — | — | Court-specific statutory interpretation |
| `ws-agency` | Agency | ✓ | `/agencies/` | Government agency with whistleblower jurisdiction |
| `ws-ag-procedure` | Procedure | ✓ | — | Filing procedure attached to a parent agency |
| `ws-assist-org` | Assistance Organization | ✓ | `/assistance-organizations/` | Non-government help organization |
| `ws-legal-update` | Legal Update | — | — | Timestamped record of a legal development |
| `ws-reference` | Reference | ✓ | — | External source document linked to legal content |

**Notes:**
- `jurisdiction` has no archive — the jurisdictions index is a standard
  WordPress page using the `[ws_jurisdiction_index]` shortcode.
- `jx-summary`, `jx-statute`, `jx-citation`, `jx-interpretation`, and
  `ws-legal-update` are non-public. Their content is rendered exclusively
  through the query layer and assembly layer on parent pages.
- `ws-ag-procedure` is publicly queryable so individual procedure
  permalinks resolve — the agency assembler renders standalone procedure
  pages when a procedure URL is accessed directly.
- All addendum CPT content lives in ACF fields, not `post_content`.
  Published status is the editorial gate; `post_content` is always empty.

---

## Taxonomies

Sixteen taxonomies are registered. `ws_jurisdiction` is the canonical
join key used across every content CPT. All others are classification
or filtering taxonomies.

| Slug | Label | Hierarchical | Public | Applied To |
|---|---|---|---|---|
| `ws_jurisdiction` | Jurisdictions | — | — | All content CPTs |
| `ws_disclosure_type` | Disclosure Categories | ✓ | ✓ | `jx-statute`, `jx-citation`, `ws-agency`, `ws-ag-procedure`, `ws-assist-org` |
| `ws_process_type` | Process Types | — | ✓ | `jx-statute`, `ws-agency`, `ws-assist-org`, `jx-interpretation` |
| `ws_remedies` | Remedies | — | — | `jx-statute` |
| `ws_protected_class` | Protected Classes | ✓ | — | `jx-statute` |
| `ws_adverse_action_types` | Adverse Action Types | — | — | `jx-statute` |
| `ws_languages` | Languages | — | — | `ws-agency`, `ws-assist-org` |
| `ws_case_stage` | Case Stages | — | — | `ws-assist-org` |
| `ws_disclosure_targets` | Disclosure Targets | ✓ | — | `jx-statute`, `ws-assist-org` |
| `ws_fee_shifting` | Fee Shifting Rules | — | — | `jx-statute` |
| `ws_employer_defense` | Employer Defense Standards | — | — | `jx-statute` |
| `ws_aorg_type` | Organization Types | — | — | `ws-assist-org` |
| `ws_employment_sector` | Employment Sectors | — | — | `ws-assist-org` |
| `ws_aorg_cost_model` | Cost Structure | — | — | `ws-assist-org` |
| `ws_aorg_service` | Services Offered | — | — | `ws-assist-org` |
| `ws_procedure_type` | Procedure Types | — | — | `ws-ag-procedure` |

**`ws_jurisdiction` details:** Private taxonomy. Slugs are lowercase
USPS codes (`us`, `ca`, `tx`, `dc`, `pr`, etc.). Terms are seeded by
`matrix-jurisdictions.php`. Always reference via the
`WS_JURISDICTION_TAXONOMY` constant — never hardcode the string
`'ws_jurisdiction'`.

**Hierarchical taxonomies:** `ws_disclosure_type` has six parent
categories with ~30 child terms covering the main areas of whistleblower
law (workplace, financial, government accountability, public health,
privacy, national security). `ws_protected_class` and
`ws_disclosure_targets` are also hierarchical. All other taxonomies
are flat.

**`ws_languages` sentinel term:** The `additional` term is
auto-assigned when the companion free-text field
(`ws_agency_additional_languages` or `ws_aorg_additional_languages`)
is non-empty. This enables `tax_query` filtering on "supports additional
languages" without enumerating every possible language.

**`ws_aorg_service` sentinel term:** Same pattern — the `additional`
term is auto-assigned when `ws_aorg_additional_services` is non-empty.

---

## Constants

All constants are defined in `ws-core.php` before the bootstrap runs.

| Constant | Value | Purpose |
|---|---|---|
| `WS_CORE_VERSION` | `'3.10.0'` | Plugin version — used as asset enqueue version string |
| `WS_CORE_PATH` | `plugin_dir_path()` | Absolute filesystem path to plugin root |
| `WS_CORE_URL` | `plugin_dir_url()` | URL to plugin root for asset enqueues |
| `WS_JURISDICTION_TAXONOMY` | `'ws_jurisdiction'` | Canonical taxonomy slug — use everywhere WordPress expects a taxonomy identifier |
| `WS_CACHE_ALL_JURISDICTIONS` | `'ws_all_jurisdictions_cache'` | Transient key for full jurisdiction list cache |
| `WS_CACHE_JX_INDEX` | `'ws_jx_index_cache'` | Transient key for jurisdiction index page cache |
| `WS_CACHE_LEGAL_UPDATES_SITEWIDE` | `'ws_legal_updates_sitewide'` | Transient key for sitewide legal updates cache (up to 100 items) |
| `WS_REF_PARENT_TYPES` | `['jx-statute', 'jx-citation', 'jx-interpretation']` | CPT slugs that support reference parent relationships |
| `WS_SOURCE_MATRIX_SEED` | `'matrix_seed'` | Source method: created by matrix seeder |
| `WS_SOURCE_AI_ASSISTED` | `'ai_assisted'` | Source method: created with AI assistance |
| `WS_SOURCE_BULK_IMPORT` | `'bulk_import'` | Source method: created via bulk import |
| `WS_SOURCE_FEED_IMPORT` | `'feed_import'` | Source method: created via feed monitor |
| `WS_SOURCE_HUMAN_CREATED` | `'human_created'` | Source method: created directly by a human editor |
| `WS_SOURCE_NAME_DIRECT` | `'Direct'` | Source name value for matrix_seed and human_created posts where source and method are the same |
| `WS_LEGAL_UPDATE_SUMMARY_TYPES` | `['statute','citation','summary','interpretation','regulation','policy']` | Legal update types that appear on public-facing pages; `internal` and `other` are excluded |

---

## Naming Conventions

These conventions govern all current and future code in the plugin.
They are not suggestions — deviating from them creates maintenance
problems and silently breaks downstream consumers.

### PHP Function Names

All plugin functions carry the `ws_` prefix. No exceptions.

### Post Meta Key Names

1. All custom meta keys carry the `ws_` prefix. No bare unprefixed keys.
2. Auto-stamp keys — values written exclusively by hook logic, never
   by human input — carry the `ws_auto_` prefix:
   `ws_auto_date_created`, `ws_auto_last_edited`,
   `ws_auto_create_author`, `ws_auto_last_edited_author`,
   `ws_auto_source_method`, `ws_auto_source_name`,
   `ws_auto_verified_by`, `ws_auto_verified_date`,
   `ws_auto_plain_english_by`, `ws_auto_plain_english_date`,
   `ws_auto_plain_english_reviewed_by`.
3. Private audit-only keys additionally carry a leading underscore per
   the WordPress hidden-meta convention:
   `_ws_auto_date_created_gmt`, `_ws_auto_last_edited_gmt`.
4. Content CPT meta keys carry a CPT infix:
   `ws_jx_*`, `ws_agency_*`, `ws_aorg_*`, `ws_legal_update_*`,
   `ws_jx_interp_*`, `ws_jx_citation_*`, `ws_proc_*`.
5. Data-type suffixes: `_url` (URL string), `_wysiwyg` (rich-text
   content), `_id` (integer foreign key or term ID).
6. Plural vs. singular: filenames and directories may be plural.
   Meta key infixes, CPT slugs, and taxonomy slugs are always singular:
   `ws_aorg_*` not `ws_aorgs_*`, `ws_agency_*` not `ws_agencies_*`.

### ACF Field Key Names

1. No `ws_` prefix on field keys. The `field_` prefix is sufficient.
   `field_ws_foo` → `field_foo`.
2. Group keys end with `_metadata`:
   `group_foo_metadata` not `group_ws_foo` or `group_foo_fields`.
3. Tab field keys: `_tab` appears only at the end:
   `field_foo_tab` not `field_tab_foo`.
4. Field key = `field_` + meta name with `ws_` prefix stripped.
   e.g., meta name `ws_jx_statute_official_name` →
   key `field_jx_statute_official_name`.
   For fields whose meta name appears in multiple groups, prepend CPT
   context to disambiguate:
   `field_{cpt_context}_{name_without_ws_prefix}`.

These rules apply to ACF `key` values only — not `name`, `label`,
or any other property.

### Query Layer Return Keys

The query layer strips all `ws_` and `ws_auto_` prefixes from PHP
array return keys. Meta key naming governs database storage; it does
not govern what the query layer exposes.

Standard sub-arrays returned by all dataset functions:

- `record` — `created_by`, `created_by_name`, `created_date`,
  `edited_by`, `edited_by_name`, `edited_date`
- `plain` — `has_content`, `plain_content`, `written_by`,
  `written_by_name`, `written_date`, `is_reviewed`,
  `reviewed_by`, `reviewed_by_name`
- `verify` — `source_method`, `source_name`, `verified_by`,
  `verified_by_name`, `verified_date`, `verify_status`,
  `needs_review`

### Render Function Names

Render functions are named after their ingest data type, not the
page section they produce:

- `ws_render_jx_citations()` not `ws_render_jx_case_law()`
- `ws_render_jx_statutes()` not `ws_render_jx_relevant_law()`

Exception: wrapper functions that compose multiple data types into a
named page region may use a section name, provided the docblock
explicitly lists every data type the function consumes.

### Date Stamps

All date values written to post meta by plugin code use
`current_time( 'Y-m-d' )` — local site date, date-only, no time
component. GMT audit timestamps (hidden `_ws_auto_*_gmt` keys) use
`gmdate( 'Y-m-d' )`. The full MySQL datetime
`current_time( 'mysql' )` is reserved for `wp_insert_post` /
`wp_update_post` `post_date` arguments only.

### Seeder Option Gates

All matrix seeders use the Unified Option-Gate Method:

```php
if ( get_option( 'ws_seeded_{seeder_slug}' ) !== '1.0.0' ) {
    ws_seed_{seeder_slug}_function();
    update_option( 'ws_seeded_{seeder_slug}', '1.0.0' );
}
```

To re-run a seeder, bump the version string in both the check and
the `update_option()` call. Never delete the option to re-run —
bump the version.

---

## Load Order

The plugin loads all modules through `includes/loader.php`, organized
into three phases. The order within each phase is non-negotiable.

### Phase 1 — Universal Layer (frontend + admin)

Loads on every request. Required for WordPress to understand CPT URLs,
taxonomy associations, and the query API.

1. **CPT Layer** — all ten CPT registration files
2. **Query Layer** — `query-helpers` → `query-shared` →
   `query-jurisdiction` → `query-agencies`
   *(strict dependency order — do not reorder)*
3. **Taxonomy Layer** — `register-taxonomies` → `register-glossary`
   *(registers taxonomy functions on `init`; terms are seeded on
   `admin_init` — terms are not available immediately after require_once)*

### Phase 2 — Admin Layer (`is_admin()` only)

Loads only in the WordPress admin. ACF field definitions, seeders,
and admin tooling are never present on the frontend.

**Matrix sub-layer** (strict dependency order):

| File | Type | Depends On |
|---|---|---|
| `matrix-helpers` | Utilities | Nothing |
| `matrix-jurisdictions` | Post seeder | Nothing (runs first) |
| `matrix-fed-statutes` | Post seeder | `us` jurisdiction term |
| `matrix-federal-courts` | In-memory only | Nothing — defines `$ws_court_matrix` array, no DB writes |
| `matrix-state-courts` | In-memory only | Nothing — defines `$ws_state_court_matrix` array, no DB writes |
| `matrix-assist-orgs` | Post seeder | `us` jurisdiction term |
| `matrix-agencies` | Post seeder | `us` jurisdiction term |
| `matrix-ag-procedures` | Post seeder | Agency posts + statute posts must exist |
| `admin-matrix-watch` | Hook registration | Must be last — watches for post edits after seeding |

**ACF Layer** — 14 field group files, all loaded after matrix so
taxonomy terms exist when ACF fields render.

**Admin tools** — 12 files. `admin-navigation.php` must load first
as it defines `ws_get_attached_citation_count()`, which
`admin-columns.php` and `jurisdiction-dashboard.php` both call.

### Phase 3 — Assembly Layer (`! is_admin()` only)

Loads only on the frontend. Never present in the admin.

- **Render files** — `render-general` → `render-section` →
  `render-jurisdiction` → `render-directory` → `render-agency`
- **Shortcode files** — `shortcodes-jurisdiction` →
  `shortcodes-general`

Missing files in this layer are logged to the error log but do not
trigger `admin_notices` — the block runs only on `! is_admin()`.
Check the server error log if assembly layer output is silently broken.
