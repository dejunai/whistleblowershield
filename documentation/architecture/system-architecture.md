# System Architecture

## Overview

WhistleblowerShield is built on WordPress with a custom plugin (`ws-core`)
that implements the entire data model, editorial workflow, and public-facing
output. The platform has no dependency on WordPress themes for content
structure — GeneratePress Premium provides the site shell, but all
meaningful content is produced by the plugin.

The system is divided into six conceptual layers that execute in dependency
order. Understanding the layers and their boundaries is the most important
thing a new contributor can learn before touching any code.

---

## The Six Layers

### 1. Data Layer (CPT + Taxonomy + ACF)

Defines what exists and how it is shaped.

Custom Post Types (CPTs) represent the primary content entities —
jurisdictions, statutes, citations, interpretations, agencies, procedures,
assist organizations, legal updates, references, and summaries. Each CPT
is a distinct data type with its own ACF field group defining its fields.

Taxonomies provide classification and — critically — the join mechanism
that scopes every content record to its jurisdiction. The `ws_jurisdiction`
taxonomy is the canonical foreign key throughout the system. There is no
post meta join; there is no relationship field join. If a record belongs
to California, it carries the `ca` term. That's the whole relationship.

ACF Pro defines the field-level schema for every CPT. Field groups are
registered in PHP (not stored in the database) so they are version-controlled
and consistent across environments.

### 2. Matrix Layer (Seeders)

Populates the database with canonical reference data on first install.

Nine matrix files seed the initial dataset: 57 jurisdiction posts and
taxonomy terms, federal statutes, federal and state court definitions
(in-memory only — no posts), assist organizations, agencies, and filing
procedures. All seeders are idempotent and gated by versioned option keys
so they run exactly once and can be re-run by bumping the gate version.

Matrix-seeded records carry `ws_matrix_source` post meta. The divergence
detection system flags any seeded record that is manually edited after
install, surfacing it in the admin dashboard for editorial review.

### 3. Query Layer (Data API)

Retrieves structured data and assembles it into normalized PHP arrays.

Four files loaded in strict dependency order:

- `query-helpers.php` — pure stateless utilities (no WordPress data reads)
- `query-shared.php` — shared sub-array builders (record, plain English,
  source verify) used by all dataset functions
- `query-jurisdiction.php` — the primary dataset API; one function per
  content type, all returning normalized arrays
- `query-agencies.php` — agency and procedure dataset functions

The query layer is loaded in the Universal Layer — it is available on
both the frontend and in the admin. This is the only layer that calls
`get_post_meta()`, `get_field()`, or `WP_Query`. Everything above this
layer works with the PHP arrays the query layer returns.

**The query layer contract is the most important architectural rule in
the codebase:** shortcodes, render functions, and admin surfaces never
call `get_post_meta()`, `get_field()`, or `WP_Query` directly. Violations
make the output layer fragile and unmaintainable.

### 4. Admin Layer

Manages the editorial workflow and data integrity.

Covers ACF field registration, admin columns, navigation metaboxes,
audit trail, stamp fields, plain English workflow, source verification,
major edit logging, procedure watch (statute link validation), URL health
monitoring, feed monitoring, and the jurisdiction dashboard. All admin
layer files load inside `is_admin()` — they are never present on the
frontend.

The admin layer is the most complex layer in the codebase by file count.
It does not produce public-facing output. Its job is to make data entry
accurate, auditable, and as self-correcting as possible.

### 5. Assembly Layer (Render + Shortcodes)

Transforms query layer output into HTML.

Loaded inside `! is_admin()` — never present in the admin. Three types
of files:

- **Render files** — functions that build HTML from data arrays. Named
  after their ingest data type, not the page section they produce (e.g.
  `ws_render_jx_citations()`, not `ws_render_case_law()`).
- **Shortcode files** — register WordPress shortcodes that call render
  functions. Shortcodes are presentation layer only; they call the query
  layer and pass results to render functions. They never read data directly.
- **Assemblers** — intercept `the_content` filter for CPT singles and
  stitch sections together automatically. The jurisdiction assembler
  (`render-jurisdiction.php`) builds the entire jurisdiction page from
  available datasets without requiring manual shortcode placement in posts.
  The agency assembler (`render-agency.php`) appends procedure sections
  to agency pages.

### 6. Frontend Assets

Two CSS files and one JavaScript file, conditionally enqueued:

- `ws-core-front-general.css` — styles for shortcodes usable on any page
  (disclaimer, footer, legal updates, jurisdiction index, directory,
  reference materials). Loaded on all singular posts and pages.
- `ws-core-front-jx.css` — styles for jurisdiction CPT pages only (header,
  flag, summary, trust badge, statute blocks, citations). Loaded only on
  `is_singular('jurisdiction')`.
- `ws-core-front.js` — jurisdiction index filter tab logic. Self-exits
  when `.ws-jx-filter-nav` is absent, so safe to load on all pages
  alongside the general CSS.

---

## Data Flow

A visitor loading a jurisdiction page illustrates the full flow:

```
WordPress resolves the jurisdiction post URL
        ↓
the_content filter fires
        ↓
ws_handle_jurisdiction_render() intercepts (render-jurisdiction.php)
        ↓
Query layer functions called:
  ws_get_jurisdiction_data()
  ws_get_jx_summary_data()
  ws_get_jx_statute_data()     ← appends US-scoped federal records
  ws_get_jx_citation_data()    ← appends US-scoped federal records
  ws_get_jx_interpretation_data()
  ws_get_agency_data()
  ws_get_assist_org_data()
  ws_get_legal_updates_data()
        ↓
Assembled data passed to render functions (render-section.php)
        ↓
HTML output returned to WordPress for display
```

The federal append (`is_fed` flag) deserves specific mention: statute,
citation, and interpretation queries automatically include US-scoped
records alongside state-scoped records on every state page. Federal law
applies everywhere. The `is_fed` flag in the return array lets the render
layer visually distinguish federal records from state records when both
appear in the same section.

---

## The Jurisdiction Join

Every content CPT is scoped to one or more jurisdictions via the
`ws_jurisdiction` taxonomy. The term slug is the canonical USPS code in
lowercase (`ca`, `us`, `tx`, `dc`, `pr`, etc.).

This replaced an earlier post meta join key (`ws_jx_code`) in v3.0.0.
The taxonomy approach enables `tax_query` filtering throughout the stack,
correct admin UI display, REST API compatibility, and seeder reliability
that post meta could not provide.

The constant `WS_JURISDICTION_TAXONOMY` holds the taxonomy slug and is
used wherever WordPress expects a taxonomy identifier. Never hardcode the
string `'ws_jurisdiction'` — always use the constant.

---

## The Attach-Flag Pattern

Statutes, citations, and interpretations each carry two fields:

- `ws_attach_flag` (boolean) — marks a record as a curated highlight
- `ws_display_order` (integer) — controls sort order among flagged records

Flagged records appear on the jurisdiction summary page as curated
highlights — typically three to five items per section. Unflagged records
are not hidden; they are fully accessible via taxonomy-driven queries
on the content type's own archive or filter page.

The flag is an editorial curation tool, not a visibility gate.

---

## Separation of Concerns

The boundaries between layers are enforced, not just suggested:

| Layer | Reads data? | Writes HTML? | Runs on admin? | Runs on frontend? |
|---|---|---|---|---|
| CPT / Taxonomy | — | — | ✓ | ✓ |
| Query | ✓ | — | ✓ | ✓ |
| Admin | direct meta | — | ✓ | — |
| Assembly / Render | query layer only | ✓ | — | ✓ |
| Shortcodes | query layer only | delegates | — | ✓ |

The "direct meta" note in the Admin row is intentional and documented.
Admin-only files (columns, hooks, metaboxes) read post meta directly
because the query layer is designed for frontend dataset assembly, not
for individual field reads in admin UI context. Every direct meta read
in admin files carries an inline comment explaining why the query layer
is not used.

---

## Caching

Three transient-based caches reduce database load on high-traffic pages:

- `WS_CACHE_ALL_JURISDICTIONS` — the full jurisdiction list used by the
  index page. Invalidated on jurisdiction save or delete.
- `WS_CACHE_JX_INDEX` — the jurisdiction index page data. Same invalidation.
- `WS_CACHE_LEGAL_UPDATES_SITEWIDE` — up to 100 recent legal updates.
  Served via `array_slice()` for count requests ≤ 100. Requests above 100
  or per-jurisdiction calls bypass the cache entirely. Invalidated on every
  legal update save.

Per-agency procedure transients (`ws_agency_procs_{id}`) and per-statute
procedure transients (`ws_statute_procs_{id}`) are managed by the query
agencies layer with 24-hour TTL and cache invalidation on procedure save
or delete.
