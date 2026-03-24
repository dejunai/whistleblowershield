Now I have a complete picture of the plugin end-to-end. Here is the full bug report.

***

## WhistleblowerShield Plugin — End-to-End Bug Report

Here are all the obvious bugs I found tracing the plugin from `ws-core.php` → `loader.php` → query layer → render layer → shortcodes.

***

### 🔴 Bug 1 — `WS_JURISDICTION_TERM_ID` constant is the wrong type

**File:** `ws-core.php`

```php
define( 'WS_JURISDICTION_TERM_ID', 'ws_jurisdiction' );
```

The name says `_TERM_ID` (implying an integer), but the value is the **taxonomy slug** string `'ws_jurisdiction'`. This constant appears to be used wherever a taxonomy slug argument is needed (e.g., `wp_get_post_terms()`, `tax_query`). The misleading name will cause any developer who reads it to assume it holds a term ID and pass it to a function expecting an integer — a silent wrong-type bug. It should be named `WS_JURISDICTION_TAXONOMY` (or similar).

***

### 🔴 Bug 2 — `[ws_jx_header]` shortcode is registered with an empty `jx` att that is never used by the assembler

**File:** `shortcodes-jurisdiction.php`, `render-jurisdiction.php`

The assembler always calls `do_shortcode( '[ws_jx_header]' )` with no `jx` attribute. Inside the shortcode:

```php
$jx_data = ws_get_jurisdiction_data( $atts['jx'] ?: null );
```

When `$atts['jx']` is empty (the normal case), it falls back to `null`, which means `ws_get_jurisdiction_data()` must resolve the current post from `$post` globally. **If `ws_get_jurisdiction_data()` does not handle a `null` input by falling back to `$post->ID` internally, this returns nothing and the header silently disappears.** This is a latent crash depending on the query layer's null handling — worth verifying.

***

### 🔴 Bug 3 — `[ws_jx_case_law]` footnote return links are always dead (anchor targets never emitted)

**File:** `shortcodes-jurisdiction.php`

There is a documented `@todo` but it is a functional bug:

```php
// @todo fn-ref-X anchors not yet emitted inline — return links are
// currently dead until in-text superscript anchors are implemented.
$return_link = '<a href="#' . esc_attr( $fn_ref_id ) . '" ...>&#x21a9;</a>';
```

The `fn-ref-X` anchor targets **are never written anywhere in the output**, so the `↩` return links in every footnote go to `#local-fn-ref-1`, `#fed-fn-ref-1`, etc. — none of which exist. Every footnote's return arrow is a broken link on every jurisdiction page.

***

### 🔴 Bug 4 — `ws_render_jx_summary_footer()` has a structural nesting bug

**File:** `render-section.php`

The outer `ws_render_jx_summary_section()` wraps its output in:

```html
<footer class="ws-jx-summary-footer"> ... </footer>
```

But `ws_render_jx_summary_footer()` itself (the thing passed in as `$review_html`) also outputs:

```html
<div class="ws-jx-summary-footer"> ... </div>
```

The result is **a `<div class="ws-jx-summary-footer">` nested directly inside a `<footer class="ws-jx-summary-footer">`** — same CSS class, two wrappers. CSS targeting `.ws-jx-summary-footer` will match both and the footer will likely render double-padded/double-bordered. One of these wrappers is redundant and should be removed.

***

### 🟡 Bug 5 — `render-jurisdiction.php` assembler calls `[ws_jx_case_law]` and `[ws_jx_limitations]` without the taxonomy gate that guards every other section

**File:** `render-jurisdiction.php`

Every other section uses a published-status gate:

```php
$related_statutes = ws_get_jx_statute_data( $jx_term_id );
if ( ws_is_published( $related_statutes ) ) { ... }
```

But `[ws_jx_case_law]` and `[ws_jx_limitations]` are called unconditionally:

```php
$case_law = do_shortcode( '[ws_jx_case_law]' );
if ( $case_law ) { ... }
```

This is actually *fine in practice* because both shortcodes return `''` on their own when there's no data — **but it means the `$has_content` flag is only set when these shortcodes return non-empty output**. If a jurisdiction has *only* case law and no summary/statutes, `$has_content` is set correctly. The asymmetry is not a crash bug, but it is an architecture inconsistency that is easy to misread as a bug and could cause confusion when debugging.

***

### 🟡 Bug 6 — `ws_render_directory_card()` uses un-escaped `$org_title_attr` inside `aria-label` attributes

**File:** `render-directory.php`

```php
$org_title_attr = esc_attr( $org['title'] );
```

`$org_title_attr` is properly escaped once. But it is then used **directly inside `aria-label` strings inside `<?php echo ... ?>` output contexts** without re-escaping:

```php
aria-label="Get Help Now from <?php echo $org_title_attr; ?> (opens in new tab)"
```

Since `$org_title_attr` was run through `esc_attr()` before the `ob_start()`, and is then echo'd again inside an HTML attribute, this is **double-processed** — HTML entities in org names (e.g., `&amp;`) will display literally as `&amp;` rather than `&` in the tooltip. Not an XSS risk, but a display bug for any org name containing `&`, `"`, or `<`.

The correct pattern is to echo the **raw** `$org['title']` directly through `esc_attr()` at point of use.

***

### 🟡 Bug 7 — `[ws_assist_org_directory]` shortcode passes a `$filters` array to `ws_get_nationwide_assist_org_data()` but that function is never defined in the files reviewed

**File:** `shortcodes-general.php`

```php
$items = ws_get_nationwide_assist_org_data( $filters );
```

The function `ws_get_nationwide_assist_org_data()` does not appear in `query-jurisdiction.php` based on its header/changelog, and was not found in the query layer files loaded. If it's absent or misnamed, **the entire Directory page throws a fatal call to undefined function on every page load**. This is the highest-risk item. Verify it exists in `query-helpers.php` or `query-shared.php`.

***

### 🟡 Bug 8 — `ws-core-front.js` and `ws-core-front.css` are enqueued globally on all frontend pages

**File:** `ws-core.php`

```php
// @todo - Revisit before launch. Narrow to specific page types ...
```

This is a self-documented pre-launch issue, but it is a real performance bug — the assets load on every page of the site (homepage, policy pages, contact, etc.) regardless of whether any ws-core shortcode is present. Both files should be conditional on `is_singular('jurisdiction')` or a more targeted check.

***

### Summary Table

| # | Severity | File | Issue |
|---|----------|------|-------|
| 1 | 🔴 | `ws-core.php` | `WS_JURISDICTION_TERM_ID` constant misnamed — holds taxonomy slug, not a term ID |
| 2 | 🔴 | `shortcodes-jurisdiction.php` | `[ws_jx_header]` null input to `ws_get_jurisdiction_data()` may silently blank the header |
| 3 | 🔴 | `shortcodes-jurisdiction.php` | Footnote `↩` return links point to anchor IDs that are never emitted — all broken |
| 4 | 🔴 | `render-section.php` | Double `ws-jx-summary-footer` wrapper — nested `<div>` inside `<footer>` with same class |
| 5 | 🟡 | `render-jurisdiction.php` | Case law / limitations bypass the taxonomy gate — architecture inconsistency |
| 6 | 🟡 | `render-directory.php` | `$org_title_attr` is pre-escaped then echo'd raw — double-encoding display bug |
| 7 | 🟡 | `shortcodes-general.php` | `ws_get_nationwide_assist_org_data()` may be undefined — potential fatal on Directory page |
| 8 | 🟡 | `ws-core.php` | CSS/JS loaded globally on all pages — known pre-launch performance issue |

Want me to file these as GitHub issues, or start fixing any of them now?