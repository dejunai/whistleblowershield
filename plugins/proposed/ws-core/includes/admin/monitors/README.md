# includes/admin/monitors/

Two WP-Cron-driven monitoring systems. Both write to `wp_options`,
surface results in admin UI, and send email digests to administrators.

---

## Files

| File | Purpose |
|---|---|
| `admin-url-monitor.php` | Checks URL fields across CPTs for broken or redirected links |
| `admin-feed-monitor.php` | Polls Inoreader API for new enacted legislation |

---

## URL Monitor (`admin-url-monitor.php`)

**Schedules:**
- Standard URLs: `ws_every_ten_days` (864,000 seconds)
- Procedure intake URLs: `ws_every_three_days` (259,200 seconds)

**Response classification:**
- `2xx` — pass; clears from log if previously flagged; sends recovery email
- `3xx` — warning; logged separately
- `4xx` / `5xx` — failure; logged; email sent to all administrators
- `WP_Error` — unreachable; skipped this run, retried next run, not logged

**Option keys:**
- `ws_url_monitor_log` — structured failure/warning log (array)
- `ws_url_monitor_last_run` — Unix timestamp of last completed run

The `detected` timestamp is preserved across reruns — the log shows
first-seen date, not last-confirmed date.

**Adding a URL field:** add an entry to `$ws_url_monitor_map` at the
top of the file. No other changes required.

**Production reliability:** WP-Cron fires on page load. On low-traffic
sites the schedule is approximate. Add a server-side crontab hitting
`wp-cron.php?doing_wp_cron` every five minutes for guaranteed timing.

---

## Feed Monitor (`admin-feed-monitor.php`)

**Schedule:** `ws_feed_daily` (once per day via WP-Cron)

**Pipeline:**
1. Fetch `LegalResearch` folder from Inoreader API
2. Keyword-filter for enacted/signed legislation
3. Deduplicate against staged and already-ingested items
4. Append new items to staged JSON file
5. Prune pending items older than `ws_feed_staged_max_age_days`
   (default 90 days, filterable via `ws_feed_staged_max_age_days`)

**Admin review UI:** Tools → Feed Monitor
- Accept → creates `jx-statute` draft post, removes from staged JSON
- Reject → removes from staged JSON immediately
- Edit → updates staged JSON before ingest

**Configuration (stored in `wp_options`):**
- Inoreader Bearer token — required
- App ID + App Key — optional (tier-dependent)

**Option keys:**
- `ws_feed_monitor_credentials` — API credentials array
- `ws_feed_staged_path` — path to staged JSON file

**File writes:** all `file_put_contents()` calls use `LOCK_EX` to
prevent race conditions on concurrent cron runs.
