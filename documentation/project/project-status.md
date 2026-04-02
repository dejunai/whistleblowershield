# Project Status

**Last updated:** 2026-04-02
**Plugin version:** 3.12.0
**Prompt template version:** v2.0.8
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

**Plugin architecture** — all 10 CPTs, 17 taxonomies, 14 ACF field
groups, 9 matrix seeders, 4-file query layer, 5-file render layer,
2-file shortcode layer, full admin layer including audit trail,
editorial workflow, monitoring systems, and jurisdiction dashboard.

**Taxonomy additions since v3.10.0:**
- `ws_employee_standard` (v3.12.0) — flat taxonomy replacing the
  freetext `employee_standard` field on `jx-statute`. Seven terms
  including `has-details` sentinel.
- `has-details` sentinel term added to `ws_adverse_action_types`,
  `ws_remedies`, `ws_disclosure_targets`, `ws_protected_class`,
  and `ws_employer_defense` (v3.11.0). Signals that a companion
  ACF freetext field holds detail beyond available slugs.

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

**Ingest pipeline** — fully operational multi-model AI research
pipeline with human review gate. See pipeline section below.

---

## Ingest Pipeline — Current State

The AI-assisted ingest pipeline is operational and has produced
validated statute records for California and Federal jurisdictions.

### Architecture (five layers)

1. **ChatGPT (GPT-5.4 Thinking)** — primary research model.
   Produces disciplined JSON with strong citation quality and
   honest integrity blocks. 3m 21s thinking time on 10-record
   batches. Trusted for SOL handling, exhaustion logic, and
   schema fidelity.

2. **Grok (xAI)** — enrichment pass. Strongest taxonomy depth
   and `_review_notes` quality. First model to produce a correctly
   formed taxonomy proposal. Known failure modes: empty strings
   instead of omitted keys, companion fields without `has-details`
   sentinel, parent slug self-check inconsistent.

3. **Gemini Pro** — third data point. Significantly stronger than
   Flash. Retired Gemini Flash from rotation after two consecutive
   runs with `limit_ambiguous: false` on derived SOLs and
   self-contradicting integrity blocks.

4. **NotebookLM** — fact-checker, merge layer, and contributor
   hub. Verifies factual fields against official statute text via
   web research. Resolves conflicts between model outputs with
   documented reasoning. Catches forbidden citation sources,
   parent slugs, and schema violations that survive model
   self-checks. Generates plain English `jx-summary` drafts
   from statute data — reduces summary writing time by an
   estimated 30x.

5. **Human review** — final approval gate. All records require
   human sign-off before ingest. `jx-summary` records are always
   `human_created` by enforced policy regardless of draft source.

### Prompt template — v2.0.8

Key features:
- Full taxonomy tables synced to `register-taxonomies.php` as
  source of truth
- `has-details` sentinel pattern with companion field mapping
- `ws_employee_standard` as registered taxonomy (converted from
  freetext in v2.0.7)
- Concrete proposal example (family-member-of-whistleblower)
  to unlock proposal behavior
- Forbidden citation sources: `scholar.google.com`, law firm
  websites, non-institutional sources
- SOL rule: derived = ambiguous, regardless of confidence
- Exhaustion rule: mandatory administrative step = true, even
  if de novo review available after waiting period
- `burden_of_proof_flag` clarified as signal phrase, not narrative
- `special damages` → `consequential-damages` mapping added
- Open selection RUN SCOPE with exclusion list support
- `is_pdf` omitted when false — present only when true

### NotebookLM ruleset

NotebookLM operates with a permanent ruleset in its personality
covering: forbidden citation sources, parent slug removal, SOL
verification, exhaustion resolution, companion field enforcement,
fee shifting discipline, and conflict resolution hierarchy
(statute text > binding case law > model inference).

### Taxonomy proposals — pending human review

Two proposals surfaced during California research runs, both
correctly formed and cross-jurisdictional:

- `family-member-of-whistleblower` → `ws_protected_class`
  Seen in: CA-6310, CA-6311. Employees retaliated against because
  a family member engaged in protected activity. Distinct from
  direct whistleblower protection.

- `domestic-work-employees` → `ws_protected_class`
  Seen in: CA-6310, CA-6311. Domestic workers explicitly covered
  with a stated exclusion for publicly funded household employees.

### Data produced to date

**California (JX-CA notebook):** 22 statute records in unified
master JSON. Covers General Protections, Safety and Health,
Public Sector Employees (State, UC, CSU, Community College,
Judicial Branch, Legislative, Local Government), Specific Fraud
and Conditions, and Legal Frameworks (burden of proof, enforcement
procedures). Records are in human review queue — not yet ingested.

**Federal (JX-US notebook):** 1 statute record (US-1514A,
Sarbanes-Oxley § 806). Clean record with three Supreme Court
citations. In human review queue — not yet ingested.

### Known pipeline issues — not yet addressed

- `tool-ingest.php` validation not yet updated to recognize
  `has-details` as a valid term or `ws_employee_standard` as
  a registered taxonomy.
- ACF companion fields for `has-details` pattern not yet created
  on `jx-statute` CPT (`ws_adverse_action_details`,
  `ws_remedies_details`, `ws_disclosure_target_details`,
  `ws_protected_class_details`, `ws_employer_defense_details`,
  `ws_employee_standard_details`).
- Auto-stamp hooks in `/acf/workflow/` not firing correctly.
  `admin-matrix-watch.php` also not firing. Root cause not yet
  identified — parked for dedicated debugging session.
- Prompt template taxonomy tables and `register-taxonomies.php`
  were out of sync prior to v2.0.7. PHP is now the declared
  source of truth. Old prompt table text files should be discarded.

---

## NotebookLM — Project Hub

NotebookLM serves as both the merge/fact-check layer and the
planned contributor entry point. Notebook structure:

- **WS-Core** — rules, prompt templates, architecture docs,
  project README, documentation README, `project-status.md`
- **JX-CA** — California unified statute JSON (22 records)
- **JX-US** — Federal statute JSON (US-1514A)

Naming convention established: `WS-` prefix for project-wide
notebooks, `JX-` prefix for jurisdiction data notebooks.
Flat structure, alphabetically self-organizing. All 57
jurisdictions will follow `JX-[CODE]` pattern.

The Mind Map feature in NotebookLM produces navigable statute
taxonomy from JSON data — independently derived organizational
structure (General, Safety and Health, Public Sector, Specific
Conditions, Legal Frameworks) is a candidate for public-facing
navigation taxonomy.

Clicking any Mind Map leaf node triggers a plain English summary
with web research grounding — demonstrated on CA-8547.10 with
unprompted comparative analysis of UC/CSU/Community College
frameworks including the 18-month safety valve not present in
the JSON. This is the confirmed `jx-summary` draft generation
workflow.

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
- [ ] Confirm all seeder gates reached correct versions:
      `ws_seeded_jurisdiction_matrix`,
      `ws_seeded_fed_statutes_matrix`,
      `ws_seeded_court_matrix`,
      `ws_seeded_state_court_matrix`,
      `ws_seeded_agency_matrix`,
      `ws_seeded_assist_org_matrix`,
      `ws_seeded_procedure_matrix`
- [ ] Confirm gate versions for updated seeders:
      `ws_seeded_remedies` = 1.1.0,
      `ws_seeded_protected_class` = 1.1.0,
      `ws_seeded_adverse_action_types` = 1.1.0,
      `ws_seeded_disclosure_targets` = 1.1.0,
      `ws_seeded_employer_defense` = 1.1.0,
      `ws_seeded_employee_standard` = 1.0.0
- [ ] Confirm all 57 jurisdiction posts exist with correct
      `ws_jurisdiction` taxonomy term assignments
- [ ] Confirm jurisdiction dashboard shows correct counts for
      all seeded CPTs (statutes, agencies, procedures, assist orgs)
- [ ] Load one jurisdiction page on the frontend — confirm it
      renders without errors and all seeded sections appear

If anything fails, deactivate, wipe, fix, repeat.

### 3. Pipeline Prerequisites

Before beginning data ingest:

- [ ] Update `tool-ingest.php` validation to recognize `has-details`
      as a valid term in all five supporting taxonomies
- [ ] Update `tool-ingest.php` to recognize `ws_employee_standard`
      as a registered taxonomy
- [ ] Create ACF companion fields for `has-details` pattern on
      `jx-statute` CPT (six fields, each conditionally visible
      when `has-details` assigned to its taxonomy)
- [ ] Resolve auto-stamp hook issue in `/acf/workflow/`
- [ ] Resolve `admin-matrix-watch.php` not firing
- [ ] Review and action two pending taxonomy proposals:
      `family-member-of-whistleblower` and `domestic-work-employees`

### 4. Data Build — Demo Jurisdictions

Build in this order:

- [ ] Federal (US) — US-1514A complete, additional federal
      statutes needed
- [ ] California (CA) — 22 records in review queue, ingest
      pending pipeline prerequisites
- [ ] Wyoming (WY) — thin jurisdiction stress test

For each jurisdiction:
- [ ] Human review of all records in NotebookLM unified JSON
- [ ] Ingest via `tool-ingest.php`
- [ ] Verify jurisdiction dashboard shows correct counts
- [ ] Load jurisdiction page — confirm all sections render
- [ ] Review `jx-summary` draft (NotebookLM generated) and
      approve or edit to `human_created` standard

### 5. Testing Pass

#### Query Layer
- [ ] `ws_get_jurisdiction_data( 'us' )` returns a non-null array
      with `id`, `name`, `code`, `flag`, `gov`, `record` keys
- [ ] `ws_get_jx_statute_data( $us_term_id )` returns an array
      of statute rows; each row has `is_fed = false` for US jurisdiction
- [ ] `ws_get_jx_statute_data( $ca_term_id )` returns local
      statutes with `is_fed = false` and appended federal statutes
      with `is_fed = true`
- [ ] `ws_get_legal_updates_data()` returns an array (may be empty)
      without errors
- [ ] `ws_get_jurisdiction_index_data()` returns jurisdictions
      grouped by class; only jurisdictions with a published
      `jx-summary` appear

#### Assembly Layer
- [ ] A jurisdiction page assembles and renders all available
      sections without PHP warnings
- [ ] The `[ws_jurisdiction_index]` shortcode renders the full
      index with working filter tabs
- [ ] The `[ws_legal_updates]` shortcode renders without errors
      on a page with no jurisdiction context

#### Admin Layer
- [ ] Creating a `jx-statute` post and saving stamps
      `ws_auto_date_created`, `ws_auto_create_author`,
      `ws_auto_last_edited`, `ws_auto_last_edited_author`
- [ ] Toggling `ws_has_plain_english` on a statute and saving
      stamps `ws_auto_plain_english_by` and
      `ws_auto_plain_english_date`
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
- [ ] `has-details` companion fields appear conditionally on
      `jx-statute` edit screen when sentinel assigned

#### Monitoring Systems
- [ ] URL monitor cron schedules are registered:
      `ws_every_ten_days` and `ws_every_three_days`
- [ ] Feed monitor cron schedule is registered: `ws_feed_daily`
- [ ] Inoreader API credentials can be saved via the admin UI
      (does not require a live API call)

### 6. GeneratePress CSS Pass

- [ ] Verify jurisdiction page layout renders correctly with the
      active GeneratePress Premium theme
- [ ] Verify right sidebar is hidden globally and can be re-enabled
      per post type (required for Phase 2 filter panel)
- [ ] Trust badge color and border refinement (flagged in
      `ws-core-front-jx.css`)
- [ ] Confirm CSS conditional loading is correct:
      `ws-core-front-general.css` on all `is_singular()` pages,
      `ws-core-front-jx.css` on `jurisdiction` singles only

### 7. Launch

- [ ] Configure Inoreader API credentials in `wp_options`
- [ ] Remove the development-only notice block from `ws-core.php`
- [ ] Update the plugin description in `ws-core.php` to remove
      "proposed replacement" language

---

## Known Gaps at Launch

The following are known limitations that will not block launch but
should be addressed in subsequent iterations:

**Demo data only at launch** — California, Federal, and Wyoming
will be the initial data build. The remaining 54 jurisdictions
will be populated post-launch via the ingest pipeline.

**Phase 2 filter cascade not built** — `ws_render_jx_filtered()`,
`ws_render_directory_taxonomy_guide()`, and
`ws_resolve_filter_context()` are stubs. The platform launches
with the curated jurisdiction view only.

**Citation gaps across most records** — 18 of 22 California
records have zero citations. Citation enrichment is a post-launch
priority, not a launch blocker.

**Tool-generate-prompt.php** — does not yet support named statute
IDs in RUN SCOPE (planned: 3-5 ID maximum for targeted gap fills).
Currently supports count-based open selection only.

**`project-status.md` should be updated** after each significant
milestone. It is the document the development-only notice
references and should always reflect the current state.
