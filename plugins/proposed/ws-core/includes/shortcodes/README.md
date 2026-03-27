# includes/shortcodes/

Shortcode registration files. Assembly Layer — loaded only on the
frontend (`! is_admin()`).

This directory is the primary contributor entry point. Both files
are fully documented with `@param`, query return shapes, and
plain-language notes on behavior and constraints.

---

## Files

| File | Shortcodes |
|---|---|
| `shortcodes-jurisdiction.php` | `[ws_jx_header]`, `[ws_jx_summary]`, `[ws_jx_statutes]`, `[ws_jx_flag]`, `[ws_jx_citation]`, `[ws_jx_interpretation]`, `[ws_jx_limitations]` |
| `shortcodes-general.php` | `[ws_nla_disclaimer_notice]`, `[ws_footer]`, `[ws_legal_updates]`, `[ws_reference_page]`, `[ws_jurisdiction_index]`, `[ws_assist_org_directory]` |

See each file for complete `@param` documentation, attribute
descriptions, and query return shape glossary.

---

## The Shortcode Contract

Shortcodes are presentation wrappers only.

- Never call `get_field()`, `get_post_meta()`, or `WP_Query` directly
- Call the appropriate query layer function
- Pass the result to a render function
- Return the rendered HTML string

A shortcode that bypasses the query layer is a violation of the
plugin's core architectural rule. If the query layer doesn't return
the data you need, extend the query layer — don't add a direct read
to the shortcode.
