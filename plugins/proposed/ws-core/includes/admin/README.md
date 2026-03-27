# includes/admin/

Admin layer files. Loaded only inside `is_admin()` — never on the
frontend. Covers ACF hooks, editorial workflow, audit trail, admin
columns, navigation metaboxes, and integrity systems.

Subdirectories:
- `matrix/` — matrix seeders and divergence watch
- `monitors/` — WP-Cron monitoring systems (URL health, feed ingest)

---

## Files

| File | Purpose |
|---|---|
| `admin-hooks.php` | All ACF save hooks — stamps, plain English, source verify, language/service term sync |
| `admin-columns.php` | Admin list table columns for all ws-core CPTs |
| `admin-navigation.php` | Navigation metaboxes on jurisdiction and agency edit screens |
| `admin-audit-trail.php` | Tamper-resistant append-only edit history |
| `admin-major-edit-hook.php` | Creates `ws-legal-update` posts on flagged saves |
| `admin-procedure-watch.php` | Statute link validation + publish gate for `ws-ag-procedure` |
| `admin-citation-metabox.php` | Citation context metabox on `jx-citation` edit screen |
| `admin-interpretation-metabox.php` | Court select pre-population on `jx-interpretation` edit screen |
| `jurisdiction-dashboard.php` | Jurisdiction completion tracker dashboard page |
| `admin-health-check.php` | Runtime dependency checks surfaced as admin notices |

---

## Hook Priority Table

The ordering of `acf/save_post` hooks is load-bearing. Do not change
priorities without understanding the full dependency chain.

| Priority | Hook | File | Purpose |
|---|---|---|---|
| 5 | `ws_acf_plain_english_guards` | `admin-hooks.php` | Integrity guards before ACF commits |
| 5 | `ws_stamp_source_method` | `admin-hooks.php` | Source method — first save only |
| 5 | `ws_default_verification_status` | `admin-hooks.php` | Default verify status — first save only |
| 5 | `ws_presave_needs_review` (stash) | `admin-hooks.php` | Stashes pre-save value before ACF writes |
| 6 | `ws_stamp_source_name` | `admin-hooks.php` | Source name — first save only, after method |
| 10 | *(ACF commits its own fields)* | ACF Pro | Field values written to post meta |
| 20 | `ws_acf_write_stamp_fields` | `admin-hooks.php` | Stamp fields written after ACF |
| 20 | `ws_stamp_verified_by_date` | `admin-hooks.php` | Verification stamp on status transition |
| 20 | `ws_enforce_source_verify_roles` | `admin-hooks.php` | Role gate enforcement |
| 20 | `ws_acf_log_major_edit` | `admin-major-edit-hook.php` | Creates legal update on major edit flag |
| 25 | `ws_acf_stamp_plain_reviewed_by` | `admin-hooks.php` | Plain English review stamp |
| 25 | `ws_acf_stamp_summarized_fields` | `admin-hooks.php` | Plain English authorship stamp |
| 25 | `ws_sync_additional_languages_term` | `admin-hooks.php` | Language term sync |
| 25 | `ws_sync_additional_services_term` | `admin-hooks.php` | Service term sync |
| 99 | `ws_record_audit_trail` | `admin-audit-trail.php` | Audit trail — always last |

The audit trail fires at priority 99 on `save_post` (not `acf/save_post`)
to ensure it runs after ACF has finished writing all its fields.

---

## The Admin Layer Boundary

Admin files may call `get_post_meta()`, `get_field()`, and `WP_Query`
directly. This is intentional and documented with inline comments at
each call site. The query layer contract (no direct data reads in
shortcodes and render functions) applies to the Assembly Layer, not
the Admin Layer.

Admin files read post meta directly for three reasons:
1. Hook context fires before or after the query layer's normalized
   return arrays are relevant
2. Admin columns and metaboxes need raw field values, not assembled
   datasets
3. The query layer is not loaded in `is_admin()` contexts for
   performance reasons

Any direct meta read in an admin file that is not self-evident
carries an inline comment explaining why the query layer is not used.
