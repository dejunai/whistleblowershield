# ws-core Query Layer

## What This Document Is

The complete reference for the query layer — the four PHP files that form
the data retrieval API of the ws-core plugin. Documents every public
function, its parameters, return shape, caching behaviour, and
invalidation pattern.

**The query layer contract:** All data retrieval in the plugin goes
through this layer. Shortcodes, render functions, and admin surfaces
never call `get_field()`, `get_post_meta()`, or `WP_Query` directly.
Admin files that must read post meta directly (columns, hooks, metaboxes)
carry inline comments explaining why the query layer is not used.

---

## File Structure

Four files loaded in strict dependency order — this order is
non-negotiable:

```
query-helpers.php      Pure utility functions — no WP data reads
query-shared.php       Shared sub-array builders — depend on helpers
query-jurisdiction.php Primary dataset API — depends on shared
query-agencies.php     Agency/procedure dataset API — depends on shared
```

All four files are in the Universal Layer — loaded on both frontend and
admin. The query layer is the only layer that reads from WordPress
(post meta, taxonomy terms, transients).

---

## query-helpers.php

Pure stateless utilities. No `WP_Query`, no `get_post_meta()`,
no `get_field()`. Functions here must remain side-effect free.

### `ws_resolve_display_name( $user_id )`
Resolves a WordPress user ID to a display name string. Returns empty
string if the user ID is falsy or the user does not exist. Used by all
dataset functions so the render layer never calls `get_userdata()`.

### `ws_jx_term_by_code( $code )`
Resolves a USPS jurisdiction code (e.g. `'ca'`, `'us'`) to a `WP_Term`
object via `get_term_by()`. Normalizes to lowercase internally. Fires
an `E_USER_WARNING` in `WP_DEBUG` mode when an uppercase code is passed
so typos are caught in development rather than silently corrected.
Returns `WP_Term` or `false`.

### `ws_court_lookup( $court_key )`
Looks up a court entry from the federal or state court matrix. Checks
`$ws_court_matrix` (federal) first, then `$ws_state_court_matrix` (state).
Returns the court entry array or `null` if not found. On the frontend both
globals are empty (court matrices are admin-only) — callers must handle
`null` gracefully. The query layer falls back to the raw court key when
`null` is returned.

---

## query-shared.php

Shared sub-array builders used by every dataset function. These produce
the three standard sub-arrays present in all dataset returns.

### `ws_build_record_array( $post_id )`
Returns the `record` sub-array:

```php
[
    'created_by'      => int,    // ws_auto_create_author
    'created_by_name' => string, // display name resolved from created_by
    'created_date'    => string, // Y-m-d local (ws_auto_date_created)
    'edited_by'       => int,    // ws_auto_last_edited_author
    'edited_by_name'  => string, // display name resolved from edited_by
    'edited_date'     => string, // Y-m-d local (ws_auto_last_edited)
]
```

### `ws_build_plain_english_array( $post_id )`
Returns the `plain` sub-array for CPTs that carry the plain English
workflow. CPTs without the workflow still call this — it returns
appropriate empty/false values:

```php
[
    'has_content'      => bool,
    'plain_content'    => string, // wysiwyg HTML
    'written_by'       => int,
    'written_by_name'  => string,
    'written_date'     => string, // Y-m-d
    'is_reviewed'      => bool,
    'reviewed_by'      => int,
    'reviewed_by_name' => string,
    'reviewed_date'    => string, // Y-m-d
]
```

### `ws_build_source_verify_array( $post_id )`
Returns the `verify` sub-array present in all dataset returns:

```php
[
    'source_method'   => string, // WS_SOURCE_* constant value
    'source_name'     => string,
    'verified_by'     => string, // display name (stored as text, not user ID)
    'verified_by_name'=> string,
    'verified_date'   => string, // Y-m-d
    'verify_status'   => string, // 'verified' | 'needs_review' | 'unverified' | 'outdated'
    'needs_review'    => bool,
]
```

---

## query-jurisdiction.php

The primary dataset API. Contains all functions needed to assemble a
jurisdiction page.

### Lookup Helpers

#### `ws_get_term_id_by_code( $jx_code )`
Resolves a USPS code to a `ws_jurisdiction` taxonomy term ID. Returns
`int` term ID or `0`. Caches result in transient
`ws_id_for_term_{term_id}` for `DAY_IN_SECONDS`.

#### `ws_get_id_by_code( $jx_code )`
Resolves a USPS code to a jurisdiction post ID. Composes
`ws_get_term_id_by_code()` internally. Returns `int` post ID or `0`.

#### `ws_resolve_jx_id( $input )`
Accepts a post ID, post slug, or USPS code and returns a post ID.
Used by shortcodes to normalize their `jx` attribute before calling
dataset functions.

#### `ws_get_jx_term_id( $post_id )`
Returns the `ws_jurisdiction` taxonomy term ID for a jurisdiction post.
Returns `0` if no term is assigned.

#### `ws_get_us_term_id()`
Returns the term ID for the `'us'` (Federal) jurisdiction term.
Returns `0` if not found.

#### `ws_parse_jx_limitations_meta( $sid )`
Frontend fallback for the `ws_jx_limitations` ACF repeater. ACF field
definitions are admin-only so `get_field()` returns false on the
frontend for repeater fields. This function reads the raw post meta keys
ACF writes for repeaters and returns the same shape `get_field()` would.

### Dataset Functions

All dataset functions accept a `$jx_term_id` integer (the
`ws_jurisdiction` taxonomy term ID) as their primary scope parameter,
except `ws_get_jurisdiction_data()` which accepts post ID, slug, or
USPS code.

#### `ws_get_jurisdiction_data( $input = null )`
Returns identity, flag, and government links for a jurisdiction.
`$input` accepts post ID, slug, or USPS code; `null` uses the current
global `$post`. Returns array or `null`.

```php
[
    'id'         => int,
    'name'       => string,
    'class'      => string,   // 'state' | 'federal' | 'territory' | 'district'
    'code'       => string,   // lowercase USPS code
    'jx_term_id' => int,
    'flag'       => [ 'url', 'attribution', 'source_url', 'license' ],
    'gov'        => [ 'portal_url', 'portal_label', 'executive_url', 'executive_label',
                      'authority_url', 'authority_label', 'legislature_url', 'legislature_label' ],
    'record'     => [ ...ws_build_record_array() ],
]
```

#### `ws_get_jx_summary_data( $jx_term_id )`
Returns the jx-summary record for the jurisdiction. Returns array
or `null`.

```php
[
    'id', 'title', 'url', 'status',
    'content'     => string,  // WYSIWYG HTML — use wp_kses_post(), not the_content
    'sources'     => string,
    'limitations' => array,   // repeater rows: each has 'ws_jx_limit_label', 'ws_jx_limit_text'
    'notes'       => string,  // internal only — do not expose publicly
    'plain'       => [ ...ws_build_plain_english_array() ],
    'verify'      => [ ...ws_build_source_verify_array() ],
    'record'      => [ ...ws_build_record_array() ],
]
```

#### `ws_get_jx_statute_data( $jx_term_id )`
Returns an array of statute data arrays. Automatically appends
US-scoped federal statutes when the jurisdiction is not Federal.
Each item has `is_fed: true` for appended federal records.

Returns all fields from the statute ACF group: `official_name`,
`citation`, `common_name`, `disclosure_type`, `protected_class`,
`disclosure_targets`, `adverse_action_scope`, `attach_flag`, `order`,
`sol_value`, `sol_unit`, `sol_trigger`, `sol_has_details`,
`sol_details`, `tolling_has_details`, `tolling_details`,
`has_exhaustion`, `exhaustion_details`, `process_type`,
`adverse_action`, `fee_shifting`, `remedies`, `related_agencies`,
`bop_standard`, `employer_defense`, `employer_defense_details`,
`rebuttable_has_details`, `rebuttable_details`, `bop_has_details`,
`bop_details`, `has_reward`, `reward_details`, `statute_url`,
`url_is_pdf`, `last_reviewed`, `ref_materials`, plus `plain`,
`verify`, and `record` sub-arrays.

**Note:** Only returns published records with `attach_flag = true`,
sorted by `ws_display_order ASC`. Unflagged statutes are not returned
by this function — they are accessible via their own archive.

#### `ws_get_jx_citation_data( $jx_term_id )`
Returns an array of citation data arrays. Same federal append logic as
statutes. Fields: `citation_type`, `disclosure_type`, `official_name`,
`common_name`, `url`, `url_is_pdf`, `attach_flag`, `order`,
`last_reviewed`, `statute_ids`, `ref_materials`, plus `plain`,
`verify`, `record`.

#### `ws_get_jx_interpretation_data( $jx_term_id )`
Returns an array of interpretation data arrays. Same federal append
logic. Fields: `court` (resolved to short label via `ws_court_lookup()`),
`court_name` (free-text when court is `other`), `year`, `favorable`,
`official_name`, `common_name`, `case_citation`, `url`, `summary`,
`process_type`, `attach_flag`, `order`, `last_reviewed`, `statute_id`,
`affected_jx`, `ref_materials`, plus `plain`, `verify`, `record`.

#### `ws_get_agency_data( $jx_term_id )`
Returns an array of agency data arrays for the jurisdiction. Fields:
`code`, `name`, `logo`, `mission`, `url`, `reporting_url`, `phone`,
`confidentiality_notes`, `accepts_anonymous`, `reward_program`,
`languages`, `additional_languages`, `last_reviewed`, plus `plain`,
`verify`, `record`.

#### `ws_get_assist_org_data( $jx_term_id )`
Returns an array of assist organization data arrays scoped to the
jurisdiction. Fields: `internal_id`, `org_type`, `description`,
`logo`, `serves_nationwide`, `disclosure_types`, `services`,
`additional_services`, `employment_sectors`, `case_stages`, `website`,
`phone`, `email`, `address`, `languages`, `additional_languages`,
`cost_model`, `income_limit`, `income_limit_notes`, `accepts_anonymous`,
`eligibility_notes`, `licensed_attorneys`, `accreditation`,
`bar_states`, `verify_url`, `last_reviewed`, plus `verify`, `record`.

#### `ws_get_nationwide_assist_org_data( $filters = [] )`
Returns assist organizations scoped to the `'us'` jurisdiction term
(nationwide organizations). Accepts optional `$filters` array with
keys `type`, `sector`, `stage`, `cost_model` — each accepts a taxonomy
term slug. Used by the `[ws_assist_org_directory]` shortcode. Return
shape identical to `ws_get_assist_org_data()`.

#### `ws_get_all_jurisdictions()`
Returns a flat array of all published jurisdiction posts as lightweight
objects (`id`, `name`, `class`, `code`, `url`). Cached at
`WS_CACHE_ALL_JURISDICTIONS` for 12 hours. Invalidated on jurisdiction
save or delete.

#### `ws_get_jurisdiction_index_data()`
Returns structured data for the jurisdiction index page: jurisdictions
grouped by class, with counts. Only jurisdictions with a linked
published `jx-summary` are included. Cached at `WS_CACHE_JX_INDEX`
for 24 hours.

#### `ws_get_legal_updates_data( $jx_id = 0, $count = 0 )`
Returns legal update records. When `$jx_id` is `0`, returns sitewide
updates (all public types); when set, returns updates for that
jurisdiction. `$count = 0` auto-resolves to `5` for per-jurisdiction
and `100` for sitewide. Sitewide results cached at
`WS_CACHE_LEGAL_UPDATES_SITEWIDE` for 1 hour; requests ≤ 100 served
via `array_slice()`; requests > 100 bypass the cache. Invalidated on
every legal update save.

#### `ws_get_ref_materials( $post_id )`
Returns reference material rows for a statute, citation, or
interpretation post. Returns array or `[]`. Used internally by
statute/citation/interpretation dataset functions.

#### `ws_get_reference_page_data( $parent_post_id )`
Returns data for the reference page display. Accepts the parent
post ID (statute, citation, or interpretation). Returns structured
data including two-part disclaimer and the trimmed reference list.

---

## query-agencies.php

Dataset functions for agencies and filing procedures.

### `ws_get_agency_procedures( $agency_id )`
Returns all published `ws-ag-procedure` records for a given agency
post ID. Ordered alphabetically by title. Cached per-agency at
`ws_agency_procs_{$agency_id}` for 24 hours. Invalidated on
procedure save or delete.

Each row: `id`, `title`, `url`, `agency_id`, `agency_name`,
`agency_url`, `type` (procedure type slug), `jurisdiction`,
`disclosure_types`, `entry_point`, `intake_url`, `phone`,
`identity_policy`, `intake_only`, `deadline_days`, `clock_start`,
`has_prereqs`, `prereq_note`, `walkthrough`, `exclusivity_note`,
`last_reviewed`, `record`.

### `ws_get_agency_procedure( $procedure_id )`
Returns a single procedure row. Delegates to
`ws_build_agency_procedure_row()`.

### `ws_build_agency_procedure_row( $pid )`
Builds one normalized procedure row. Returns `[]` when the post is
invalid, wrong CPT, or not published.

### `ws_get_procedures_for_statute( $statute_id )`
Returns all published procedures that link a given `jx-statute` post
via `ws_proc_statute_ids`. Used by the statute section renderer to
surface "Filing Procedures Under This Statute" on jurisdiction pages.
Cached per-statute at `ws_statute_procs_{$statute_id}` for 24 hours.
Uses two LIKE queries to handle both serialized string and integer
shapes ACF may write for relationship fields.

Each row: `id`, `title`, `url`, `type`, `agency_id`, `agency_name`,
`agency_url`, `deadline_days`, `intake_only`.

### Cache Invalidation Hooks

All agency/procedure transients are maintained by hooks in
`query-agencies.php`:

- **`save_post_ws-ag-procedure`** — clears `ws_agency_procs_{id}` for
  the procedure's current and previous parent agency (stash pattern
  handles agency changes).
- **`acf/save_post` (priority 5 stash + priority 25 diff)** — stashes
  old statute IDs before ACF saves; after save, diffs old vs. new and
  clears `ws_statute_procs_{id}` for all affected statute IDs.
- **`before_delete_post` / `deleted_post`** — clears both agency and
  all linked statute transients when a procedure is deleted.

---

## Transient Cache Summary

| Cache Key | TTL | Invalidated By |
|---|---|---|
| `ws_id_for_term_{term_id}` | 24h | `save_post_jurisdiction` |
| `WS_CACHE_ALL_JURISDICTIONS` | 12h | `save_post_jurisdiction`, `delete_post` |
| `WS_CACHE_JX_INDEX` | 24h | `save_post_jurisdiction`, `delete_post` |
| `WS_CACHE_LEGAL_UPDATES_SITEWIDE` | 1h | `save_post_ws-legal-update` |
| `ws_agency_procs_{agency_id}` | 24h | `save_post_ws-ag-procedure`, procedure delete |
| `ws_statute_procs_{statute_id}` | 24h | `acf/save_post` stash+diff, procedure delete |
