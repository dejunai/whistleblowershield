# ws-core Output Layer

## What This Document Is

The complete reference for the output layer — the render functions,
shortcodes, assemblers, and frontend assets that transform query layer
data into HTML. Documents every render function, every shortcode tag
with its attributes, and the CSS class architecture.

The output layer is the Assembly Layer in loader.php terms — loaded
only inside `! is_admin()`. It is never present in the WordPress admin.

---

## The Output Contract

Shortcodes and render functions operate under strict rules:

1. **No direct data reads.** Shortcodes and render functions never call
   `get_field()`, `get_post_meta()`, or `WP_Query` directly. All data
   comes through the query layer.

2. **Shortcodes are presentation wrappers.** A shortcode calls the
   appropriate query layer function, receives a normalized PHP array,
   and passes it to a render function. No business logic lives in
   shortcode handlers.

3. **Render functions accept data arrays, return HTML strings.** They
   never query the database. All HTML is returned as a string, not
   echoed directly (except within assembler output buffer contexts).

4. **WYSIWYG content uses `wp_kses_post()`**, not
   `apply_filters('the_content', ...)`, unless the field is
   `post_content`. See the note on content sanitization below.

---

## Content Sanitization Note

Two sanitization paths exist in the render layer — this distinction
is intentional and must be preserved:

- **`wp_kses_post( $data['content'] )`** — used for ACF WYSIWYG fields
  (e.g. `ws_jurisdiction_summary_wysiwyg`, `ws_plain_english_wysiwyg`,
  `ws_proc_walkthrough`). The HTML is already fully formed by the ACF
  editor. Running `the_content` filters would double-wrap paragraphs,
  expand shortcodes embedded in legal text, and trigger block rendering.

- **`apply_filters('the_content', $content)`** — used only for
  `post_content` fields, which require block rendering and `wpautop`.
  Currently used by statute blocks that read `post_content` directly.

---

## File Structure

```
render/
├── render-general.php      General-page renderers (all page types)
├── render-section.php      Jurisdiction-page section renderers
├── render-jurisdiction.php The jurisdiction assembler
├── render-directory.php    Assist org directory renderers
└── render-agency.php       Agency page assembler + procedure card renderers

shortcodes/
├── shortcodes-jurisdiction.php  Jurisdiction shortcodes
└── shortcodes-general.php       General-purpose shortcodes
```

---

## Assemblers

Two files intercept `the_content` filter to build pages automatically
without requiring manual shortcode placement.

### `render-jurisdiction.php`

**`ws_handle_jurisdiction_render( $content )`** — hooks on
`the_content`. Intercepts content for `jurisdiction` CPT singles.
Dispatches to one of two paths:

- **`ws_render_jx_curated( $post, $jx_term_id )`** — the standard path.
  Builds the full jurisdiction page from available published datasets:
  header, summary, statutes, citations, interpretations, agencies,
  assist organizations, legal updates. Each section is conditionally
  rendered — if a dataset has no published records, the section is
  omitted entirely. If no sections render, a single
  `.ws-section--placeholder` notice is shown.

- **`ws_render_jx_filtered( $post, $jx_term_id, $filter_context )`** —
  the Phase 2 filtered path (currently dormant). Entry point for
  situation-based filtered views (pre-disclosure, retaliation, etc.).
  Dispatch hook point is in place.

Section anchors are written into the wrapper divs:
`id="ws-statutes"`, `id="ws-citations"`, `id="ws-interpretations"`.

### `render-agency.php`

**`ws_handle_agency_render( $content )`** — hooks on `the_content`.
Handles two CPT types:

- **`ws-agency`** — appends a structured procedures section below the
  editorial post content. Procedures grouped by type: disclosure first,
  retaliation second, both last. Falls back silently when no published
  procedures exist.
- **`ws-ag-procedure`** — renders a standalone procedure page so that
  publicly queryable procedure permalinks don't display as blank pages.

---

## render-section.php

Section renderers for jurisdiction page components.

| Function | Purpose |
|---|---|
| `ws_render_section( $title, $content, $class )` | Generic section wrapper with heading and class |
| `ws_render_section_two_group( $title_local, $content_local, $title_fed, $content_fed )` | Two-column local/federal section — used for statutes, citations |
| `ws_render_jx_header( $data )` | Flag, jurisdiction name, government offices panel |
| `ws_render_jx_flag( $flag_data )` | Flag image with Wikimedia attribution tooltip |
| `ws_render_jx_gov_offices( $gov_data )` | Government links panel (portal, executive, authority, legislature) |
| `ws_render_jx_summary_section( $content, $review_html )` | Summary block with optional review badge HTML |
| `ws_render_plain_english_reviewed_badge( $reviewed, $reviewer_name, $date )` | "Editor Reviewed" trust badge with hover tooltip showing reviewer name and date |
| `ws_render_jx_summary_footer( $data )` | Summary authorship footer (created/edited dates, sources) |
| `ws_render_jx_citations( $items, $section_class )` | Citation list with footnote reference pattern |
| `ws_render_jx_interpretations( $interps )` | Court interpretation blocks with court label, year, favorable flag |
| `ws_render_jx_limitations( $limitations )` | Limitations and ramifications list from summary repeater |
| `ws_render_statute_procedures( $procedures )` | Compact "Filing Procedures Under This Statute" cross-reference panel |

### `ws_render_agency_procedures( $procedures )`
Groups procedures by type and renders each group with a heading,
subtext, and procedure cards. Display order: disclosure → retaliation
→ both. Skips empty groups.

### `ws_render_agency_procedure_card( $proc )`
Renders one procedure card. Includes: intake-only warning (if set),
identity policy label, filing deadline with clock-start label,
entry point, prerequisites notice, walkthrough (wysiwyg), exclusivity
note, CTA buttons (intake URL + phone), last-verified date.

---

## render-general.php

General renderers for non-jurisdiction pages and sitewide components.

| Function | Purpose |
|---|---|
| `ws_render_nla_disclaimer( $text )` | "Not legal advice" notice box |
| `ws_render_footer( $data )` | Site footer block (mission, policy links, contact, copyright) |
| `ws_render_legal_updates( $items )` | Legal updates list (title, type, date, jurisdiction, source link) |
| `ws_render_jurisdiction_index( $data )` | Full jurisdictions index with type filter tabs and alphabetical grid |

---

## render-directory.php

Assist organization directory renderers for the `[ws_assist_org_directory]`
shortcode.

| Function | Purpose |
|---|---|
| `ws_render_directory_page( $items )` | Full directory page with filter controls and listing |
| `ws_render_directory_listing( $items )` | Card grid of organization results |
| `ws_render_directory_card( $org )` | Individual organization card with type, services, cost model, contact |
| `ws_render_directory_empty()` | Empty state when no results match filters |
| `ws_render_directory_taxonomy_guide()` | Taxonomy guide stub (placeholder for future filter explanation) |

---

## Shortcodes

### Jurisdiction Shortcodes (`shortcodes-jurisdiction.php`)

All jurisdiction shortcodes resolve their jurisdiction context
automatically from the current `$post` when placed on a jurisdiction
CPT page. The `jx` attribute is required only when the shortcode is
placed on a non-jurisdiction page.

| Tag | Attribute | Purpose |
|---|---|---|
| `[ws_jx_header]` | `jx=""` | Full jurisdiction header: flag, name, government offices panel |
| `[ws_jx_summary]` | *(none)* | Summary block with authorship footer and trust badge |
| `[ws_jx_statutes]` | *(none)* | Curated statute blocks with two-group local/federal split |
| `[ws_jx_flag]` | `jx=""` | Flag image with attribution only (no header) |
| `[ws_jx_citation]` | *(none)* | Curated citation list |
| `[ws_jx_interpretation]` | *(none)* | Curated interpretation blocks |
| `[ws_jx_limitations]` | *(none)* | Limitations and ramifications list |

**Important:** On jurisdiction CPT pages these shortcodes are placed
automatically by the assembler (`render-jurisdiction.php`). Manual
shortcode placement in jurisdiction posts is not required and not
recommended. These shortcodes exist for edge cases where a component
needs to appear on a non-jurisdiction page.

### General Shortcodes (`shortcodes-general.php`)

| Tag | Attributes | Purpose |
|---|---|---|
| `[ws_nla_disclaimer_notice]` | *(none)* | Standard "not legal advice" notice box |
| `[ws_footer]` | *(none)* | Site footer block |
| `[ws_legal_updates]` | `jx=""`, `count="0"` | Legal updates list. `jx` = USPS code or post ID (omit for sitewide). `count` defaults to 5 per-jurisdiction and 100 sitewide. |
| `[ws_reference_page]` | `post_id="0"` | Reference materials page for a parent post. Also reads `post_id` and `section` from URL params — a single page serves all records. |
| `[ws_jurisdiction_index]` | *(none)* | Full jurisdictions index page with type filter tabs |
| `[ws_assist_org_directory]` | `type=""`, `sector=""`, `stage=""`, `cost_model=""` | Assist organization directory. All attributes accept taxonomy term slugs. URL params take priority over shortcode attributes. |

---

## Frontend Assets

### `ws-core-front-general.css`

Loaded on all `is_singular()` pages. Covers shortcodes that may appear
on any page type.

Key class namespaces:
- `.ws-nla-disclaimer-notice` — not legal advice notice
- `.ws-legal-updates`, `.ws-legal-update-item`, `.ws-legal-update-*` — legal updates list
- `.ws-footer-block`, `.ws-footer-*` — site footer
- `.ws-flag-block` — flag image block (standalone)
- `.ws-term-highlight`, `.ws-tooltip-content` — glossary term tooltips
- `.ws-jx-filter-nav`, `.ws-jx-grid` — jurisdiction index filter tabs and grid
- `.ws-directory-*` — assist org directory
- `.ws-agency-procedures`, `.ws-agency-procedures__*` — agency procedure sections
- `.ws-proc-card`, `.ws-proc-card__*` — individual procedure cards
- `.sr-only` — screen-reader only utility class (accessibility)

### `ws-core-front-jx.css`

Loaded only on `is_singular('jurisdiction')`. Loaded with `wp_enqueue_style`
dependency on `ws-core-front-general` so general styles always load first.

Key class namespaces:
- `.ws-jurisdiction-header`, `.ws-jurisdiction-*` — jurisdiction header
- `.ws-jx-flag-img`, `.ws-jx-flag-attribution` — flag and attribution
- `.ws-jx-gov-offices-box`, `.ws-jx-gov-*` — government offices panel
- `.ws-jx-summary-block`, `.ws-jx-summary-*` — summary section
- `.ws-trust-badge`, `.ws-trust-badge--reviewed`, `.ws-trust-badge--draft` — review status badges
- `.ws-jx-summary-sources`, `.ws-jx-sources-text` — sources display
- `.ws-statute-procedures__*` — cross-reference procedure panel under statute blocks

### `ws-core-front.js`

Loaded on all `is_singular()` pages (footer). Self-exits immediately when
`.ws-jx-filter-nav` is absent — safe to load on all pages without
performance cost on non-index pages.

Responsibilities:
- Jurisdiction index filter tab logic (client-side, no jQuery)
- Tab keyboard navigation: arrow keys, Home, End (WCAG SC 2.1.1)
- `aria-controls` wiring between tabs and grid
- `.hidden` property toggling on the no-results status region
  (`role="status"`, `aria-live="polite"`)

HTML contract: filter tab buttons must carry `data-type` attributes
matching the jurisdiction class values (`state`, `federal`, `territory`,
`district`). The grid must carry `id="ws-jx-grid"`.

---

## The Reference Page Pattern

The `[ws_reference_page]` shortcode implements a two-part disclaimer
pattern for external references:

1. A content disclaimer — standard not-legal-advice language
2. An external link disclaimer — links open in new tabs with
   `target="_blank"`, `rel="noopener noreferrer nofollow"`, and a
   `window.opener = null` JavaScript safety assignment

A single WordPress page with `[ws_reference_page]` placed on it serves
all reference pages. The specific record is resolved from the `?post_id=`
URL query parameter. The `?section=` parameter appends a `#ws-{section}`
hash to the back link so users return to the correct position on the
parent jurisdiction page.

The small "→ External References" trigger on statute, citation, and
interpretation blocks links to this page with the appropriate post ID.
