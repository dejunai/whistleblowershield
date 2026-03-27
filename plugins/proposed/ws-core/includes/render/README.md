# includes/render/

Assembly Layer render functions. Loaded only on the frontend
(`! is_admin()`). Transform query layer output into HTML strings.
Never read from the database directly.

---

## Files

| File | Purpose |
|---|---|
| `render-jurisdiction.php` | Jurisdiction page assembler — curated and filtered paths |
| `render-section.php` | Section renderers for jurisdiction page components |
| `render-agency.php` | Agency page assembler + procedure card renderers |
| `render-general.php` | General renderers — footer, legal updates, jurisdiction index |
| `render-directory.php` | Assist org directory renderers |

---

## The Render Contract

1. **No direct data reads.** Render functions never call `get_field()`,
   `get_post_meta()`, or `WP_Query`. All data comes from the query layer.

2. **Accept arrays, return strings.** Every render function accepts
   a normalized data array and returns an HTML string. Nothing is
   echoed directly except within output buffer contexts.

3. **Conditional rendering.** If a dataset is empty, the section is
   omitted entirely. Empty section headings are never rendered.

---

## Content Sanitization

Two sanitization paths are used deliberately. Do not swap them.

**`wp_kses_post( $content )`** — for ACF WYSIWYG field content
(e.g. `ws_jurisdiction_summary_wysiwyg`, `ws_plain_english_wysiwyg`,
`ws_proc_walkthrough`). The HTML is already fully formed by the ACF
editor. Running `the_content` filters would double-wrap paragraphs,
expand embedded shortcodes, and trigger block rendering.

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

## Assembly Layer Definition

The Assembly Layer is render functions + shortcode files only.
The query layer is the Universal Layer — a prerequisite of the
Assembly Layer, not part of it. Never refer to the query layer
as part of the "assembly layer."
