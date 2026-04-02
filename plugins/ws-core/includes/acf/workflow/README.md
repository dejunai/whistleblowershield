# includes/acf/workflow/

Shared ACF field groups that attach to multiple CPTs. These files
register system behavior fields, not content fields. They have a
different maintenance lifecycle from the CPT-specific ACF files and
are loaded separately in `loader.php`.

---

## Files

| File | Group Key | `menu_order` | Purpose |
|---|---|---|---|
| `acf-stamp-fields.php` | `group_stamp_metadata` | 90 | Authorship and edit timestamps |
| `acf-plain-english-fields.php` | `group_plain_english_metadata` | 85 | Plain language overlay and review workflow |
| `acf-source-verify.php` | `group_source_verify_metadata` | 95 | Source method, verification status, needs-review flag |
| `acf-major-edit.php` | `group_major_edit_metadata` | 99 | Major edit flag — triggers automatic legal update creation |

Load order in `loader.php`: stamp-fields first. Plain English stamp
writes depend on stamp field definitions being registered.

---

## Stamp Fields (`group_stamp_metadata`)

Attaches to all 9 content CPTs. Intentionally excludes `jurisdiction`
— jurisdiction records are seeder-generated, create authorship is
not meaningful there.

Fields are auto-filled by hook logic and locked read-only for
non-administrators. The hook that writes them is
`ws_acf_write_stamp_fields()` in `admin-hooks.php` at priority 20.

| Meta Key | Written |
|---|---|
| `ws_auto_date_created` | Once on first save |
| `ws_auto_create_author` | Once on first save |
| `ws_auto_last_edited` | Every save |
| `ws_auto_last_edited_author` | Every save (admin-overridable) |

---

## Plain English Fields (`group_plain_english_metadata`)

**Attaches to:** `jx-statute`, `jx-citation`, `jx-interpretation`,
`ws-agency`

**Intentionally excluded:**
- `jx-summary` — IS the plain language document; carries its own
  review fields in `acf-jx-summaries.php`
- `ws-assist-org` — content is plain language by nature
- `ws-legal-update` — changelog entries, no overlay use case
- `ws-reference` — outbound links, no prose to simplify
- `jurisdiction` — structured metadata container, not explanatory prose

Integrity guards enforced by `ws_acf_plain_english_guards()` in
`admin-hooks.php` at priority 5 (before ACF commits at priority 10):
- `ws_has_plain_english` requires non-empty `ws_plain_english_wysiwyg`
- `ws_plain_english_reviewed` requires editor rank or above
- Toggle-off clears all reviewed fields and stamps

---

## Source Verify Fields (`group_source_verify_metadata`)

**Attaches to:** `jx-statute`, `jx-citation`, `jx-interpretation`,
`ws-agency`, `ws-ag-procedure`, `ws-assist-org`, `jx-summary`,
`ws-reference`

Three write paths for `ws_auto_source_method`:
- Matrix seeders write `WS_SOURCE_MATRIX_SEED` directly
- Human-created records default to `WS_SOURCE_HUMAN_CREATED`
- Ingest tooling calls `ws_set_source_method()`

`ws_verification_status` cannot be set to `verified` unless
`ws_auto_source_name` is non-empty — enforced server-side by
`ws_enforce_source_verify_roles()` in `admin-hooks.php` at priority 20.

Non-admins cannot revert a `verified` record to `unverified`.
Only admins may set `ws_needs_review`.

---

## Major Edit (`group_major_edit_metadata`)

**Attaches to:** `jx-summary`, `jx-statute`, `jx-citation`,
`jx-interpretation`, `ws-ag-procedure`

When `ws_is_major_edit` is saved as true with a non-empty description,
`admin-major-edit-hook.php` creates a published `ws-legal-update` post
automatically. Both fields reset after the legal update is created.

An empty description with the toggle on resets the toggle and shows
an admin notice. An empty description is worse than no log entry.
