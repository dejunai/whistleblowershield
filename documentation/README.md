# Documentation

This directory contains the complete internal documentation for the
WhistleblowerShield platform — covering legal knowledge architecture,
technical development, editorial standards, operational policy,
product design, project governance, and active proposals.

---

## Sections

### Architecture

Defines the legal knowledge model that underpins the entire platform.

Key documents:
- `system-architecture.md` — the four-layer architecture overview
  (legal knowledge, data management, editorial, presentation)
- `legal-knowledge-model.md` — unified reference covering core legal
  entities (statutes, regulations, agencies, programs), how they
  relate to one another, and how each jurisdiction is structured
  as a knowledge node. Replaces legal-entity-model.md and
  jurisdiction-knowledge-model.md, both archived.
- `whistleblower-law-ontology.md` — conceptual model of how legal
  concepts relate to one another
- `whistleblower-law-taxonomy.md` — classification system for
  whistleblower law categories
- `jurisdiction-scope-model.md` — current scope of 57 U.S.
  jurisdictions (50 states, federal, D.C., 5 territories),
  jurisdiction hierarchy, and jurisdiction code system (jx_code)
- `cpt-relationship-map.md` — how Custom Post Types relate to
  one another within the data model
- `legal-citation-model.md` — citation formatting standards
  loosely aligned with Bluebook conventions
- `source-verification-policy.md` — four-tier source reliability
  framework (primary legal texts → government guidance →
  academic analysis → advocacy/commentary)
- `security-model.md` — role-based access, audit trail,
  and editorial integrity protections

---

### Development

Technical documentation for the ws-core plugin.

Located in `development/ws-core/`.

Key documents:
- `ws-core-overview.md` — plugin purpose and scope
- `ws-core-plugin-architecture.md` — structural design of the plugin
- `ws-core-cpts.md` — Custom Post Type definitions
- `ws-core-acf-schema.md` — ACF field group schema for each CPT
- `ws-core-data-model.md` — data model overview
- `ws-core-data-schema.md` — field-level schema reference
- `ws-core-module-structure.md` — module organization
- `ws-core-file-structure.md` — file and directory layout
- `ws-core-hook-system.md` — WordPress hooks used by the plugin
- `ws-core-shortcode-system.md` — shortcode architecture
- `ws-core-shortcodes.md` — individual shortcode reference
- `ws-core-audit-trail.md` — edit history and accountability logging
- `ws-core-design-principles.md` — guiding principles for plugin development
- `ws-core-future-block-architecture.md` — planned Gutenberg block layer
- `data-integrity-rules.md` — rules for maintaining clean,
  normalized jurisdiction datasets
- `query-layer.md` — structured query system design (moved from
  development/ root into development/ws-core/)

Note: Plugin development docs are substantially out of date as of
March 2026 and are being revised. Treat them as directional
references rather than current implementation guides until
the revision is complete.

---

### Editorial

Standards and processes governing how legal content is researched,
written, and maintained.

Key documents:
- `editorial-standards.md` — content writing standards including
  9th–10th grade reading level target, summary structure,
  plain language rules, inline definition guidance (tooltips
  and parentheticals), tone guidelines, and review checklist
- `workflow.md` — seven-phase editorial workflow from research
  through publication, revision, and legal review
- `user-personas.md` — three primary user personas (Maya: person
  in crisis; Daniel: informed researcher; James: person facing
  retaliation) with design implications for each

---

### Policy

Operational guidelines for transparency and research integrity.

Key documents:
- `transparency-policy.md` — defines what transparency means in
  practice: source transparency, editorial review status, public
  audit trail, corrections handling, and acknowledged limitations
- `legal-research-methodology.md` — step-by-step research process
  for lay contributors, covering AI-assisted drafting, primary
  source verification, plain-language clarification, case law
  research, and honest disclosure of methodology limitations

---

### Product

Defines how the legal archive is translated into the public-facing
guidance resource.

Key documents:
- `guidance-layer-model.md` — the three-layer presentation model
  (plain-language summary → practical guidance → legal citations),
  question-driven design philosophy, jurisdiction page structure,
  homepage design principles, tone and accessibility standards,
  and separation of concerns between internal docs and public site.
  Incorporates and replaces guidance_layer_design_principles.md,
  now archived.

---

### Project

Governance, vision, status, and roadmap for the platform.

Key documents:
- `project-vision.md` — platform purpose, the problem being solved,
  two-layer architecture rationale, the three user personas, editorial
  commitments, nonpartisan stance, and long-term ambition
- `project-status.md` — current development constraints
  (solo maintainer, live-site prototyping, no staging)
- `roadmap.md` — six-phase development roadmap from architecture
  through data population, accessibility, platform features,
  editorial expansion, and professional backend
- `governance.md` — decision-making principles and contributor
  expectations

---

### Proposals

Active and draft proposals for platform improvements.

These documents represent work in progress — some have been
implemented, some are pending, some remain exploratory.

Current proposals:
- `editorial-policy-revision.md` — revised Editorial Policy page
  content ready for WordPress implementation (v4, March 2026)
- `proposal-notes.md` — implementation notes for the editorial
  policy revision and California summary update
- `ws_core_refactor_plan.md` — plugin refactor for improved
  structure, query layer, and naming conventions
- `ws_core_plugin_review.md` — ten recommended structural
  improvements to ws-core (dependency protection, escaping,
  loading order, etc.)
- `ws_core_jurisdiction_data_integrity.md` — normalized CPT
  architecture with jurisdiction code system (jx_code)
  and query layer responsibility
- `ws_core_review_status_workflow.md` — three-state review
  workflow (draft → needs_review → verified) for dataset CPTs
- `proposal-jx-parent-and-dashboard-improvements.md` — ws_jx_parent
  relationship field and jurisdiction dashboard completeness view
- `public_guidance_design_standards.md` — writing and structure
  standards for public-facing content
- `missing_foundational_docs.md` — identifies documentation
  gaps for future contributors (scope policy, risk awareness,
  contributor onboarding, data update policy)

---

## Notes

The `archive/` directory holds deprecated or superseded documents
retained for historical reference. Archived as of March 2026:

- `legal-entity-model.md` — superseded by legal-knowledge-model.md
- `jurisdiction-knowledge-model.md` — superseded by legal-knowledge-model.md
- `guidance_layer_design_principles.md` — incorporated into guidance-layer-model.md
- `editorial_trust_and_verification_model.md` — superseded by
  editorial-standards.md and transparency-policy.md
- `project_vision_and_mission.md` — superseded by project-vision.md
  and roadmap.md

Documents in `proposals/` should be reviewed periodically.
Proposals that have been fully implemented should be moved to
the relevant stable section or archived.
