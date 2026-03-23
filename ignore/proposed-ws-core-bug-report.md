# Proposed `ws-core` Bug Report

_Date:_ 2026-03-22  
_Scope reviewed:_ `plugins/proposed/ws-core`  
_Method:_ static code review and PHP linting (`php -l`) only; no live WordPress runtime was available in this environment.

## Executive Summary

The proposed plugin currently has **two PHP parse errors**, one of which is in a universally loaded taxonomy file and would prevent the plugin from loading at all. Beyond that, I found **multiple query/render contract mismatches** that would hide content or emit PHP notices even after the syntax issues are fixed.

## Severity Legend

- **Critical** — blocks plugin load or admin access.
- **High** — core content fails to render or renders incorrectly.
- **Medium** — broken UX, incorrect metadata, or fragile markup.

---

## 1) Critical — `register-glossary.php` has an unclosed block / parse error

**File:** `plugins/proposed/ws-core/includes/taxonomies/register-glossary.php`  
**Evidence:** `php -l` reports `Parse error: Unclosed '{' on line 286 ... on line 628`.

### Why this matters

`register-glossary.php` is loaded from the **universal taxonomy layer** in `includes/loader.php`, so this is not an admin-only defect. A parse error here would prevent the proposed plugin from initializing on normal requests.

### Code evidence

- The glossary filter function starts at line 286 and never reaches a valid closing structure before EOF.
- The file ends at line 627, while PHP reports the parser is still waiting for a closing brace.

### Likely impact

- Plugin bootstrap failure.
- Any request that loads the proposed plugin can fatal before shortcodes, CPTs, taxonomies, or render helpers are available.

### Suggested fix

- Repair the control-flow structure in `ws_apply_glossary_tooltips()`.
- Re-run `php -l` against the file before testing any higher-level behavior.

---

## 2) Critical — `admin-url-monitor.php` docblock contains `*/5`, which terminates the comment and causes a parse error

**File:** `plugins/proposed/ws-core/includes/admin/admin-url-monitor.php`  
**Evidence:** `php -l` reports `Parse error: syntax error, unexpected token "*" on line 33`.

### Root cause

Inside the file header comment, the example crontab line contains:

```text
*/5 * * * * curl -s https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null
```

Because `*/` closes a block comment in PHP, the parser treats the remaining `5 * * * * ...` as code, which causes the syntax error.

### Why this matters

This file is loaded in the admin layer from `includes/loader.php`. Once an admin request tries to load the proposed plugin, the dashboard would fatal before admin workflow tools finish loading.

### Suggested fix

Escape or rewrite the cron example so it does not contain the literal `*/` inside a PHP block comment, for example by:

- changing it to `0,5,10,...` format in the comment, or
- inserting spaces / backticks in a way that avoids the literal comment terminator.

---

## 3) High — legal update summaries are fetched under one key and rendered under another, so summaries never display

**Files:**
- `plugins/proposed/ws-core/includes/queries/query-jurisdiction.php`
- `plugins/proposed/ws-core/includes/render/section-renderer.php`

### Evidence

The query layer returns the legal update body under the key:

- `summary`

But the renderer expects:

- `summary_wysiwyg`

### Why this matters

Even when `ws-legal-update` records contain a populated WYSIWYG summary, the render layer checks a different array key and therefore skips output.

### User-visible symptom

Legal updates appear with title/date metadata, but the summary body is missing.

### Suggested fix

Pick one canonical key and use it consistently in both places:

- either change the query layer to return `summary_wysiwyg`, or
- change the renderer to read `summary`.

---

## 4) High — case-law shortcode expects a `label` field that the citation query never returns

**Files:**
- `plugins/proposed/ws-core/includes/queries/query-jurisdiction.php`
- `plugins/proposed/ws-core/includes/shortcodes/shortcodes-jurisdiction.php`

### Evidence

The citation query returns keys such as:

- `official_name`
- `common_name`
- `cite_url`

But the case-law shortcode builds footnotes using:

- `$citation['label']`

No `label` key is created in the query layer.

### Why this matters

When citations are rendered, PHP will hit an undefined index / missing array key for `label`, and the visible citation text may be blank or incomplete.

### User-visible symptom

The case-law section may render links with no text, or log PHP notices depending on error settings.

### Suggested fix

Standardize the contract. For example:

- define `label` in the query layer as `official_name ?: common_name`, or
- update the shortcode to build the label from the existing query keys.

---

## 5) Medium — summary review badge shows the writer date, not the review date, and the query layer does not expose a review date at all

**Files:**
- `plugins/proposed/ws-core/includes/queries/query-jurisdiction.php`
- `plugins/proposed/ws-core/includes/render/section-renderer.php`
- `plugins/proposed/ws-core/includes/shortcodes/shortcodes-jurisdiction.php`

### Evidence

The plain-English helper returns:

- `written_date`
- `is_reviewed`
- `reviewed_by_name`

But not a dedicated `reviewed_date`.

The summary footer then calls the review badge renderer with:

- reviewer name = `reviewed_by_name`
- reviewed date = `written_date`

### Why this matters

The tooltip text says `Reviewed by X on Y`, but `Y` is actually the date the plain-English content was written/stamped, not the date review happened.

### User-visible symptom

The trust badge can display misleading review metadata, which is especially problematic on a legal-information site emphasizing editorial integrity.

### Suggested fix

- Add a real `reviewed_date` field to the plain sub-array if one exists in the data model, or
- stop printing a date in the review badge until a true review timestamp is available.

---

## 6) Medium — local and federal case-law groups reuse the same footnote IDs, creating duplicate anchors

**File:** `plugins/proposed/ws-core/includes/shortcodes/shortcodes-jurisdiction.php`

### Evidence

When both local and federal citations exist, the shortcode renders two groups and starts both numbering sequences at `1`:

- local group uses `$build_items( $local, 1 )`
- federal group uses `$build_items( $fed, 1 )`

Inside the builder, IDs are generated from that counter:

- `fn-1`, `fn-2`, ...
- `fn-ref-1`, `fn-ref-2`, ...

### Why this matters

This produces duplicate DOM IDs on the same page whenever both groups exist.

### User-visible symptom

- Anchors are invalid / ambiguous.
- “Return to text” links can target the wrong element.
- Browser behavior becomes unpredictable for in-page navigation.

### Suggested fix

Use a shared page-level counter across both groups, or namespace IDs per section (for example `local-fn-1` and `fed-fn-1`).

---

## Recommended Fix Order

1. **Fix both parse errors first** (`register-glossary.php`, `admin-url-monitor.php`).
2. **Repair query/render contract mismatches** for legal updates and citations.
3. **Correct review metadata semantics** in the summary footer.
4. **De-duplicate case-law anchor IDs** and revisit the footnote backlink flow.

## Commands Used

```bash
php -l plugins/proposed/ws-core/ws-core.php
find plugins/proposed/ws-core -name '*.php' -print0 | xargs -0 -n1 php -l
find plugins/proposed/ws-core -name '*.php' ! -path '*/admin-url-monitor.php' -print0 | xargs -0 -n1 php -l
find plugins/proposed/ws-core -name '*.php' ! -path '*/admin-url-monitor.php' ! -path '*/register-glossary.php' -print0 | xargs -0 -n1 php -l
rg -n "function ws_is_published|ws_is_published\(" plugins/proposed/ws-core/includes -g '!*.txt'
rg -n "summary_wysiwyg|\\['[A-Za-z0-9_]+'\\]" plugins/proposed/ws-core/includes/render/section-renderer.php plugins/proposed/ws-core/includes/shortcodes/shortcodes-general.php plugins/proposed/ws-core/includes/shortcodes/shortcodes-jurisdiction.php
```
