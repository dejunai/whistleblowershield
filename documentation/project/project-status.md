# Project Status

**Last Updated:** March 2026

---

## Development Model

WhistleblowerShield is maintained by a single developer — an amateur
legal researcher and self-taught developer operating without a team,
staging environment, or dedicated budget.

The live site at whistleblowershield.org serves simultaneously as
prototype and production environment. All development, testing, and
content work happens directly on the live site. This is an accepted
constraint during the current phase, not a permanent approach.

---

## Current Phase: Phase 2 — Data Population

The platform architecture is substantially complete. Active work is
focused on populating the legal knowledge base with jurisdiction content.

Phase 2 priorities:

- Jurisdiction summary content for all 57 jurisdictions (50 states,
  Federal, D.C., 5 territories) written to editorial-standards.md
- Federal whistleblower statutes entered as jx-statutes records
- Major federal reporting procedures entered as jx-procedures records
- Key federal and national resources entered as jx-resources records
- ws-legal-update entries for significant recent legal changes

California is the current reference implementation. Its page
demonstrates the intended structure and presentation for all
jurisdiction pages.

---

## Plugin Status

### ws-core (current: v2.0.0)

The live plugin is stable at v2.0.0. It registers all CPTs, ACF field
groups, shortcodes, audit trail, and one-time cleanup and migration
routines.

Known state:
- All six CPTs registered and functional
- ACF field groups complete for all CPTs including the three addendum
  CPTs added in v2.0.0 (jx-statutes, jx-resources, jx-procedures)
- All shortcodes functional and in use on the California page
- Audit trail recording on all CPT saves
- Three one-time cleanup/migration routines completed
- `cpt-summaries.php` is a dead file (not loaded) — to be removed

### ws-core v2.1 (proposed — in development)

A refactored version of the plugin is in development at
plugins/proposed/ws-core/. The refactor introduces three improvements:

1. **Modular directory structure** — CPTs, ACF, shortcodes, queries,
   render, and admin layers in separate subdirectories
2. **Query layer** — centralized data retrieval with transient caching
   in query-jurisdiction.php
3. **Admin tools** — jurisdiction dashboard, list column status
   indicators, sidebar navigation box with smart create links,
   auto-population of parent jurisdiction on new addendum screens
4. **Auto-assembly** — the_content filter builds jurisdiction pages
   automatically from published datasets; no manual shortcode placement

The proposed plugin is partially complete. It is not yet ready for
deployment. Remaining work includes:

- Version history comment block
- `jx_code` field exists in acf-jurisdiction.php but needs confirming
  in all query layer references
- `ws_legislature_url` / `ws_legislature_label` fields referenced in
  query layer but not yet registered in ACF
- Procedures, statutes, and resources shortcodes render from
  post_content — ACF rendering strategy to be confirmed
- Conditional CSS loading strategy to be finalized
- Full testing pass before deployment

---

## Site Content Status

| Section | Status |
|---|---|
| California | Published — reference implementation |
| Federal | Stub — pending content |
| All other jurisdictions | Registered — no published content |
| Legal Updates | None published yet |
| Get Help page | Honest placeholder — pending Phase 3 |

---

## Documentation Status

Internal documentation is substantially complete and current as of
March 2026. Recent updates include:

- jurisdiction-scope-model.md — updated to reflect 57-jurisdiction scope
- legal-knowledge-model.md — new unified file replacing two earlier docs
- guidance-layer-model.md — merged and expanded
- project-vision.md — substantially expanded
- legal-research-methodology.md — fully written
- editorial-standards.md — updated (strong/weak guidance, tooltip policy)
- editorial-policy-revision.md — v4 ready for WordPress implementation
- documentation/README.md — fully current

Plugin documentation (development/ws-core/) is acknowledged as out of
date. It will be revised once the v2.1 refactor is finalized.

---

## Known Constraints

- No staging environment — all work is live
- No automated testing
- No legal review completed on any published content
- Solo maintainer — no contributors or reviewers yet
- Budget constraints limit external legal review engagement
