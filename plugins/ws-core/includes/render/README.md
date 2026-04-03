# includes/render/

Assembly Layer render functions. Loaded only on the frontend
(`! is_admin()`). Transform query layer output into HTML strings.
Never read from the database directly.

---

## Files

| File | Purpose | Status |
|---|---|---|
| `render-jurisdiction.php` | Jurisdiction page assembler — curated and filtered paths | Live |
| `render-section.php` | Section renderers for jurisdiction page components (statutes, citations, etc.) | Live |
| `render-agency.php` | Agency page assembler + procedure card renderers | Live |
| `render-general.php` | General renderers — footer, legal updates, jurisdiction index | Live |
| `render-directory.php` | Assist org directory renderers | Live |
| `render-common-law.php` | Common law doctrine section renderer | **Stub** |
| `ws-statute-bold.php` | Bold statute name formatter utility | Live |

---

## render-common-law.php

Added v3.13.0. Stub only — `ws_render_jx_common_law()` returns an
empty string and logs a debug notice. Full implementation is deferred
until the Wyoming data build and jurisdiction page layout work begins.

**Implementation notes (for when the stub is filled):**
- Group local and federal doctrine records via `is_fed` flag, same
  pattern as the statute renderer.
- Render `ws_cl_doctrine_basis` and `ws_cl_recognition_status` as
  WYSIWYG output — use `wp_kses_post()`, not `esc_html()`.
- Surface `ws_cl_statutory_preclusion` as a prominent notice when
  true — critical user-facing signal that a statutory remedy may
  block the common law claim.
- SOL is almost always ambiguous for common law — render `sol_details`
  prominently when `sol_has_details` is true.
- The `render-assist-org.php` refactor (extracting assist-org rendering
  from `render-section.php`) must happen before Phase 2 — do not
  implement `render-common-law.php` in isolation if that work is imminent.

---

## The Render Contract

1. **No direct data reads.** Render functions never call `get_field()`,
   `get_post_meta()`, or `WP_Query`. All data comes from the query layer.

2. **Accept arrays, return strings.** Every render function accepts
   a normalized data array and returns an HTML string. Nothing is
   echoed directly except within output buffer contexts.

3. **Named after data type, not page section.** `ws_render_jx_common_law()`
   not `ws_render_common_law_section()`.

4. **Conditional rendering.** If a dataset is empty, the section is
   omitted entirely. Empty section headings are never rendered.

---

## Content Sanitization

Two sanitization paths are used deliberately. Do not swap them.

**`wp_kses_post( $content )`** — for ACF WYSIWYG field content.
The HTML is already fully formed by the ACF editor. Running
`the_content` filters would double-wrap paragraphs, expand embedded
shortcodes, and trigger block rendering.

**`apply_filters( 'the_content', $content )`** — for `post_content`
fields only, which require block rendering and `wpautop`.

---

## Assembler Pattern

`render-jurisdiction.php` and `render-agency.php` hook onto
`the_content` filter and intercept output for their respective CPTs.

`ws_handle_jurisdiction_render()` dispatches to:
- `ws_render_jx_curated()` — the standard curated path (built)
- `ws_render_jx_filtered()` — the Phase 2 filtered path (stub, dormant)

The Phase 2 dispatch block is commented out in the assembler pending
`ws_resolve_filter_context()` implementation. Do not remove it.

---

## Pre-Phase 2 Prerequisite

`render-assist-org.php` must be extracted from `render-section.php`
before Phase 2 is implemented. Assist org cards render differently
in the directory context than in the jurisdiction page context, and
the Phase 2 filter cascade will need to call them independently.
