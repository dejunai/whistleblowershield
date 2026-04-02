# includes/queries/

The query layer — the only layer in ws-core that reads from the
database. Returns normalized PHP arrays. Never produces HTML.

---

## The Query Layer Contract

**Shortcodes, render functions, and admin surfaces never call
`get_field()`, `get_post_meta()`, or `WP_Query` directly.**

All data retrieval goes through this directory. This is the most
important architectural rule in the codebase. Violations produce
fragile output code that breaks silently when field names change.

Admin files that must read meta directly (columns, hooks, metaboxes)
carry inline comments explaining why the query layer is not used.

---

## Files and Load Order

Load order is non-negotiable. Each file depends on the one before it.

```
query-helpers.php       Pure utilities — no DB reads, no side effects
query-shared.php        Sub-array builders — depend on helpers
query-jurisdiction.php  Primary dataset API — depends on shared
query-agencies.php      Agency/procedure API — depends on shared
```

All four files are in the Universal Layer — loaded on both frontend
and admin via `loader.php`.

---

## Return Key Convention

The query layer strips all `ws_` and `ws_auto_` prefixes from PHP
array return keys. The prefix prevents collisions in `wp_postmeta`;
inside a return array there is no collision risk and the prefix adds
noise.

Every dataset function returns these standard sub-arrays:

```
record  → created_by, created_by_name, created_date,
          edited_by, edited_by_name, edited_date

plain   → has_content, plain_content, written_by, written_by_name,
          written_date, is_reviewed, reviewed_by, reviewed_by_name,
          reviewed_date

verify  → source_method, source_name, verified_by, verified_by_name,
          verified_date, verify_status, needs_review
```

Top-level keys are unprefixed and context-scoped. See each function's
`@return` docblock for the complete key reference.

---

## Caching

| Transient | TTL | Invalidated By |
|---|---|---|
| `ws_id_for_term_{term_id}` | 24h | `save_post_jurisdiction` |
| `WS_CACHE_ALL_JURISDICTIONS` | 12h | `save_post_jurisdiction`, `delete_post` |
| `WS_CACHE_JX_INDEX` | 24h | `save_post_jurisdiction`, `delete_post` |
| `WS_CACHE_LEGAL_UPDATES_SITEWIDE` | 1h | `save_post_ws-legal-update` |
| `ws_agency_procs_{agency_id}` | 24h | procedure save/delete |
| `ws_statute_procs_{statute_id}` | 24h | `acf/save_post` stash+diff, procedure delete |

Sitewide legal updates cache stores up to 100 items. Requests ≤ 100
served via `array_slice()`. Requests > 100 bypass the cache.
Per-jurisdiction calls are never cached.

---

## Federal Append Pattern

`ws_get_jx_statute_data()`, `ws_get_jx_citation_data()`, and
`ws_get_jx_interpretation_data()` automatically append US-scoped
federal records when the requested jurisdiction is not Federal.
Each appended record carries `is_fed: true`. The render layer uses
this flag to split local and federal results into separate sections.

---

## The `attach_flag` Gate

The query layer functions for statutes, citations, and interpretations
only return records where `ws_attach_flag = true`. This is the
curated summary view — editorially selected records for the
jurisdiction page.

**This gate applies to the curated path only.** The Phase 2 filtered
path (`ws_render_jx_filtered()`) bypasses `attach_flag` entirely and
queries all published records. `attach_flag` is an editorial curation
tool, not a visibility gate.
