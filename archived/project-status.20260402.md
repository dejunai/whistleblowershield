# Project Status

**Last updated:** 2026-03-26
**Plugin version:** 3.10.0
**Environment:** Staging only — never deployed to production

---

## Current State

The `ws-core` plugin is complete and stable in a staging environment.
It has never been deployed to a live server with real user data. The
development-only notice in `ws-core.php` is intentionally active until
the pre-launch checklist below is complete.

No data migrations are required for any architectural change. The
staging environment has no user data and no production database.
Architectural changes remain free to be destructive until the
development-only notice is removed.

---

## What Is Complete

**Plugin architecture** — all 10 CPTs, 16 taxonomies, 14 ACF field
groups, 9 matrix seeders, 4-file query layer, 5-file render layer,
2-file shortcode layer, full admin layer including audit trail,
editorial workflow, monitoring systems, and jurisdiction dashboard.

**Documentation** — 13-document set in `/documentation/` covering
project overview, system architecture, legal system model, all data
layer fields, query layer API, output layer, audit and integrity
systems, editorial standards, guidance model, and research and
transparency practices. Plugin-level README and 10 directory-level
READMEs in `ws-core/includes/`.

**Phase 2 infrastructure** — all taxonomies, query functions, and
render stubs for the situation-based filter cascade are in place.
`ws_render_jx_filtered()` and `ws_render_directory_taxonomy_guide()`
are implemented as stubs pending Phase 2 build.

**Ingest pipeline design** — prompt template v2.0.4 (untested),
JSON schema v2.0, and full tool specifications documented in
`/in-progress/`. PHP tools not yet built.

---

## Pre-Launch Checklist

Complete these steps in order before removing the development-only
notice from `ws-core.php`.

### 1. Environment

- [ ] Confirm `ws-core-error.log` exists and is writable at
      `/wp-content/logs/ws-core-error.log` — PHP will not create
      it, only write to it
- [ ] Confirm PHP error logging is routed via the host's PHP Value
      Editor (not `.htaccess` — causes 500 errors on this host)
- [ ] Configure server-side crontab hitting
      `wp-cron.php?doing_wp_cron` every 5 minutes for reliable
      URL monitor and feed monitor scheduling

### 2. Install and Seed Loop

Repeat until the jurisdiction dashboard shows green across all
seeded content:

- [ ] Activate plugin — confirm no PHP errors on activation
- [ ] Confirm health check admin notice is silent (all 5 checks pass)
- [ ] Confirm all 7 seeder gates reached `1.0.0`:
      `ws_seeded_jurisdiction_matrix`,
      `ws_seeded_fed_statutes_matrix`,
      `ws_seeded_court_matrix`,
      `ws_seeded_state_court_matrix`,
      `ws_seeded_agency_matrix`,
      `ws_seeded_assist_org_matrix`,
      `ws_seeded_procedure_matrix`
- [ ] Confirm all 57 jurisdiction posts exist with correct
      `ws_jurisdiction` taxonomy term assignments
- [ ] Confirm jurisdiction dashboard shows correct counts for
      all seeded CPTs (statutes, agencies, procedures, assist orgs)
- [ ] Load one jurisdiction page on the frontend — confirm it
      renders without errors and all seeded sections appear

If anything fails, deactivate, wipe, fix, repeat.

### 3. Testing Pass

#### Query Layer
- [ ] `ws_get_jurisdiction_data( 'us' )` returns a non-null array
      with `id`, `name`, `code`, `flag`, `gov`, `record` keys
- [ ] `ws_get_jx_statute_data( $us_term_id )` returns an array
      of statute rows; each row has `is_fed = false` for US jurisdiction
- [ ] `ws_get_jx_statute_data( $ca_term_id )` (once CA data exists)
      returns local statutes with `is_fed = false` and appended
      federal statutes with `is_fed = true`
- [ ] `ws_get_legal_updates_data()` returns an array (may be empty)
      without errors
- [ ] `ws_get_jurisdiction_index_data()` returns jurisdictions
      grouped by class; only jurisdictions with a published
      `jx-summary` appear

#### Assembly Layer
- [ ] A jurisdiction page assembles and renders all available sections
      without PHP warnings
- [ ] The `[ws_jurisdiction_index]` shortcode renders the full index
      with working filter tabs
- [ ] The `[ws_legal_updates]` shortcode renders without errors
      on a page with no jurisdiction context

#### Admin Layer
- [ ] Creating a `jx-statute` post and saving stamps
      `ws_auto_date_created`, `ws_auto_create_author`,
      `ws_auto_last_edited`, `ws_auto_last_edited_author`
- [ ] Toggling `ws_has_plain_english` on a statute and saving
      stamps `ws_auto_plain_english_by` and `ws_auto_plain_english_date`
- [ ] Toggling `ws_plain_english_reviewed` stamps
      `ws_auto_plain_english_reviewed_by` and
      `ws_auto_plain_english_reviewed_date`
- [ ] Flagging a save as a major edit with a description creates
      a published `ws-legal-update` post and resets both fields
- [ ] Saving a `ws-ag-procedure` with a mismatched statute link
      demotes the post to draft and sets `ws_proc_stat_flagged`
- [ ] Audit trail writes `_ws_last_edited_by` and appends to
      `_ws_edit_history` on every content CPT save
- [ ] Matrix divergence: manually editing a seeded record sets
      `ws_matrix_divergence = 1` and surfaces in the dashboard widget

#### Monitoring Systems
- [ ] URL monitor cron schedules are registered:
      `ws_every_ten_days` and `ws_every_three_days`
- [ ] Feed monitor cron schedule is registered: `ws_feed_daily`
- [ ] Inoreader API credentials can be saved via the admin UI
      (does not require a live API call)

### 4. GeneratePress CSS Pass

- [ ] Verify jurisdiction page layout renders correctly with the
      active GeneratePress Premium theme
- [ ] Verify right sidebar is hidden globally and can be re-enabled
      per post type (required for Phase 2 filter panel)
- [ ] Trust badge color and border refinement (flagged in
      `ws-core-front-jx.css`)
- [ ] Confirm CSS conditional loading is correct:
      `ws-core-front-general.css` on all `is_singular()` pages,
      `ws-core-front-jx.css` on `jurisdiction` singles only

### 5. Launch

- [ ] Configure Inoreader API credentials in `wp_options`
- [ ] Remove the development-only notice block from `ws-core.php`
- [ ] Update the plugin description in `ws-core.php` to remove
      "proposed replacement" language

---

## Known Gaps at Launch

The following are known limitations that will not block launch but
should be addressed in subsequent iterations:

**No data beyond seeded matrix records** — California, Federal, and
one thin jurisdiction will be the initial data build. The remaining
54 jurisdictions will be populated post-launch via the ingest pipeline.

**Ingest tools not built** — `tool-generate-prompt.php` and
`tool-ingest.php` are fully specified in `/in-progress/` but not
implemented. Initial data population is manual.

**Phase 2 filter cascade not built** — `ws_render_jx_filtered()`,
`ws_render_directory_taxonomy_guide()`, and `ws_resolve_filter_context()`
are stubs. The platform launches with the curated jurisdiction view only.

**`project-status.md` should be updated** after each significant
milestone — install/seed loop complete, data build complete, Phase 2
complete. It is the document the development-only notice references
and should always reflect the current state.