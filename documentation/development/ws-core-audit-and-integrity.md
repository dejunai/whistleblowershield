# ws-core Audit and Integrity

## What This Document Is

The reference for all data integrity, audit, and monitoring systems in
the ws-core plugin. Covers the audit trail, the major edit logger, the
matrix divergence watch, the procedure statute validation system, the
URL health monitor, the feed monitor, the jurisdiction dashboard, and
the runtime health check.

These systems exist because the platform's editorial promise — accurate,
traceable, current legal information — requires more than human diligence.
It requires systems that surface problems automatically before they reach
end users.

---

## Audit Trail

**File:** `admin/admin-audit-trail.php`

A tamper-resistant, append-only edit history written on every save of
any audited CPT. Stored as private post meta (leading underscore) and
never exposed in the WordPress admin UI or ACF field groups.

**Two meta keys per post:**

| Key | Behavior | Shape |
|---|---|---|
| `_ws_last_edited_by` | Overwritten on each save | `{ user_id, display_name, timestamp (UTC ISO 8601) }` |
| `_ws_edit_history` | Append-only, never overwritten | Array of `{ user_id, display_name, timestamp }` entries |

**Audited CPTs:** `jurisdiction`, `jx-summary`, `jx-statute`,
`jx-citation`, `jx-interpretation`, `ws-legal-update`, `ws-agency`

**Hook priority:** `save_post` at priority 99 — after ACF finishes
writing its own fields — to avoid any race condition with ACF data.
This priority must not be changed.

**Retrieval functions** (defined in this file, not routed through the
query layer):

- `ws_get_last_editor( $post_id )` — returns last edit entry array or `null`
- `ws_get_edit_history( $post_id )` — returns full history array or `[]`

The audit trail is intentionally separate from the stamp fields
(`ws_auto_last_edited`, `ws_auto_create_author`) in the shared ACF
group. Stamp fields are visible to editors and are the editorial record.
The audit trail is private and is the tamper-resistant record.

---

## Major Edit Logger

**File:** `admin/admin-major-edit-hook.php`
**ACF group:** `group_major_edit_metadata` (`acf/acf-major-edit.php`)

When an editor flags a save as a major edit and provides a description,
a `ws-legal-update` post is automatically created and published. This
maintains the platform's changelog without requiring editors to manually
create legal update entries.

**Supported CPTs:** `jx-summary`, `jx-statute`, `jx-citation`,
`jx-interpretation`, `ws-ag-procedure`

**Behavior on save:**

- If `ws_is_major_edit = 1` and `ws_major_edit_description` is
  non-empty → creates a published `ws-legal-update` post, resets both
  fields, queues a success admin notice.
- If `ws_is_major_edit = 1` but description is empty → resets the
  toggle, queues a warning notice. An empty description is worse than
  no entry.

**Auto-stamped fields on the created legal update:**

| Field | Value |
|---|---|
| `post_title` | `"[Source Title] — [CPT Label] Update"` |
| `ws_legal_update_summary_wysiwyg` | The description text |
| `ws_legal_update_effective_date` | Today (local `Y-m-d`) |
| `ws_legal_update_date` | Today (local `Y-m-d`) |
| `ws_legal_update_source_post_id` | Source post ID |
| `ws_legal_update_source_post_type` | Source CPT slug |
| `ws_legal_update_type` | Derived from CPT (`jx-statute` → `statute`, etc.) |
| `ws_legal_update_law_name` | Official name from source post; falls back to post title |
| `ws_jurisdiction` (taxonomy) | Copied from source post via `wp_set_post_terms()` |

---

## Matrix Divergence Watch

**File:** `admin/matrix/admin-matrix-watch.php`

Detects when matrix-seeded records are manually edited after install.
Matrix-seeded records are the highest-staleness-risk category because
they have no natural editorial touchpoint — the seeder ran once and
nothing has touched them since.

**Detection:** `save_post` hook checks for `ws_matrix_source` post meta
on every save. If present, sets `ws_matrix_divergence = 1` and writes
the editor's user ID to `ws_matrix_divergence_editor`. The
`WS_MATRIX_SEEDING_IN_PROGRESS` constant prevents seeders from
triggering false divergence flags during their own execution.

**Resolution:** Set `ws_matrix_divergence_resolved = 1` on the post
to dismiss the divergence. This is a manual process — edit the post
meta directly or via WP-CLI.

**Dashboard widget:** Registered via `wp_dashboard_setup`. Lists all
posts with unresolved divergences (matrix divergence set, resolved not
set or not 1). Shows post title (edit link), CPT label, matrix source
name, and the editor who made the change. Visible to administrator-role
users only.

---

## Procedure Statute Link Validation

**File:** `admin/admin-procedure-watch.php`

Guards against inaccurate statute cross-references on `ws-ag-procedure`
posts. A procedure linked to a statute with no disclosure-type
intersection is misleading guidance — this system catches it before
the procedure can be published.

**Two severity levels:**

**Hard mismatch** — a linked statute has zero `ws_disclosure_type`
term intersection with the procedure's own disclosure types:
- Sets `ws_proc_stat_flagged = 1`
- Writes mismatch detail JSON to `ws_proc_stat_flag_detail`
- Demotes post status to `draft`
- Publish gate (`wp_insert_post_data`) blocks all subsequent publish
  attempts (quick edit, bulk edit, REST, programmatic) until resolved

**Broad-scope advisory** (soft, no demotion) — procedure has no
disclosure types set AND has statute links:
- Sets `ws_proc_stat_broad_scope = 1`
- Admin notice surfaces the advisory on the edit screen
- No publish block

**Admin override:** An admin can check `ws_proc_stat_override` on the
Admin Review tab and save. The override is read by `wp_insert_post_data`
before ACF saves (reading `$_POST['acf']` directly), allowing the
publish to proceed. After save, the hook writes an append-only override
audit log to `ws_proc_stat_override_log`, clears the mismatch flag,
and resets the override field to 0.

**Note on skipped statutes:** Statutes with no `ws_disclosure_type`
terms assigned are skipped by the hard-mismatch check. The data problem
is on the statute side, not the procedure side — flagging here would
point the editor in the wrong direction. Use the jurisdiction dashboard
or health check to surface incomplete statute taxonomy data.

---

## URL Health Monitor

**File:** `admin/admin-url-monitor.php`

Monitors URL fields across jurisdiction, agency, and assist-org CPTs
for broken or redirected links. High-priority procedure intake URLs run
on a separate, more frequent schedule because a dead intake URL is an
immediate user-facing failure.

**Cron schedules:**
- Standard URLs: `ws_every_ten_days` (every 10 days)
- Procedure intake URLs: `ws_every_three_days` (every 3 days)

**On each run:**
1. Loop all published posts in each monitored CPT
2. For each URL meta key, fire `wp_remote_head()` with 10-second timeout
3. Classify response: 2xx = pass, 3xx = warning, 4xx/5xx = failure,
   `WP_Error` = unreachable (skipped, retried next run, not logged)
4. Persist results to `ws_url_monitor_log` option
5. Send email digest to all administrator-role users on failures
   and recoveries

**Log entry shape:**
```php
[
    'post_id'    => int,
    'post_title' => string,
    'post_type'  => string,
    'meta_key'   => string,
    'url'        => string,
    'status'     => int,      // HTTP status code
    'type'       => string,   // 'failure' | 'warning'
    'detected'   => string,   // Y-m-d H:i:s local (first-seen date — preserved across reruns)
]
```

**Adding a new URL field:** Add an entry to `$ws_url_monitor_map` in
the file header. No other changes required.

**Production reliability note:** WP-Cron fires on page load. On
low-traffic sites the schedule interval is approximate. For guaranteed
timing, add a server-side crontab hitting `wp-cron.php?doing_wp_cron`
every five minutes.

---

## Feed Monitor

**File:** `admin/admin-feed-monitor.php`

A legislative ingest pipeline that polls the Inoreader API daily for
new enacted legislation in the `LegalResearch` folder. Provides a
human-reviewable staging layer before any content is created.

**Pipeline:**

1. **WP-Cron daily — `ws_feed_monitor_poll()`**
   - Fetches `LegalResearch` folder from Inoreader API
   - Keyword-filters for enacted/signed legislation:
     - Include: *signed into law, enacted, became law, chaptered,
       effective law, public law*
     - Exclude: *introduced, referred to committee, passed house,
       passed senate, failed, vetoed, tabled, withdrawn*
   - Deduplicates against already-staged and already-ingested items
   - Appends new items to staged JSON file (`feed-staged.json`)
   - Prunes pending items older than `ws_feed_staged_max_age_days`
     (default 90 days, filterable) on each write

2. **Admin review UI — Tools → Feed Monitor**
   - Lists staged items with accept / reject / edit controls
   - Accepted items trigger `ws_feed_ingest_item()`
   - Rejected items are removed from staged JSON immediately

3. **`ws_feed_ingest_item()`**
   - Creates a `jx-statute` post (draft)
   - Writes `ws_ingest_source`, `ws_ingest_date`, `ws_ingest_file`
     post meta
   - Sets `ws_plain_reviewed = 0` (pending legal review)
   - Removes item from staged JSON

**Configuration:** Bearer token required; App ID and App Key optional
(tier-dependent). Stored in `wp_options`. Default jurisdiction fallback
for unscoped items: `apply_filters('ws_feed_monitor_default_jx_code', 'US')`.

**File writes:** `LOCK_EX` flag on all `file_put_contents()` calls to
prevent race conditions on concurrent cron runs.

---

## Jurisdiction Dashboard

**File:** `admin/jurisdiction-dashboard.php`

A completion tracker for all 57 jurisdictions. Accessible via the admin
sidebar menu. Shows each jurisdiction's status across all content CPTs:
summary, statutes, citations, interpretations, legal updates, agencies,
and assist organizations.

**Caching:** Full rendered HTML table cached as `ws_jx_dashboard_html`
transient for 10 minutes. Auto-invalidated on `save_post` and
`delete_post` for any tracked CPT. Manual "Refresh" button clears the
cache on demand.

This is the primary editorial tool for identifying which jurisdictions
are incomplete or have stale content. The color-coded threshold
badges (red/orange/green at 0 / 1–2 / 3+) used for citation counts
follow the same pattern used in admin columns throughout the plugin.

---

## Runtime Health Check

**File:** `admin/admin-health-check.php`

Verifies that five critical dependencies are actually in place after
all plugin files have loaded. Runs on `admin_notices` — after `init`,
after all plugins loaded. Shown only to administrator-role users. Silent
when everything is healthy.

**Five checks:**

1. **ACF active** — `function_exists('acf_add_local_field_group')`.
   If ACF deactivates, all field groups silently vanish.

2. **Core CPTs registered** — `post_type_exists()` for each content
   CPT. Catches loader ordering regressions during development.

3. **Core taxonomy registered** — `taxonomy_exists('ws_jurisdiction')`.
   Catches taxonomy load failures that would break term queries,
   the metabox jurisdiction guard, and `tax_input` parameters.

4. **Query layer callable** — `function_exists()` for the two top-level
   query sentinels. Confirms `query-jurisdiction.php` loaded correctly
   (and that `query-helpers` and `query-shared` loaded before it).

5. **Procedure seeder gate** — verifies `ws_seeded_procedure_matrix`
   reached `1.0.0` and that at least one published procedure exists
   once the gate has been marked complete.

All failures are collected and shown in a single consolidated admin
notice rather than individual notices per check.
