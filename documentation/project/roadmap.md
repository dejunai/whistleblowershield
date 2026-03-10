# Project Roadmap

## Platform Vision

WhistleblowerShield aims to become the most accessible and reliable
structured reference for United States whistleblower protection law.

"Accessible" and "reliable" are both essential. A resource that is accurate
but incomprehensible to ordinary people has failed half its purpose.
A resource that is readable but legally unreliable has failed the other half.

The roadmap is organized to build both dimensions in parallel.

---

## Phase 1 — Architecture (Substantially Complete)

Establishing the legal knowledge model and technical foundation.

Completed:
- Legal ontology and taxonomy
- Citation model and source verification policy
- Jurisdiction scope model
- ws-core plugin: all CPTs, ACF field groups, shortcodes, audit trail
- System architecture documentation
- Editorial workflow and content standards
- User persona definitions

---

## Phase 2 — Data Population (Current Phase)

Building out the legal knowledge base with real jurisdiction content.

Priorities:
- Jurisdiction summary content for all 50 states, D.C., federal,
  and U.S. territories — written to content-standards.md
- Federal whistleblower statutes documented in jx-statutes records
  (False Claims Act, Dodd-Frank, Sarbanes-Oxley, Whistleblower
  Protection Act, and others)
- Major federal reporting procedures in jx-procedures records
  (SEC, OSHA, IRS, DOJ programs)
- Key federal and national resources in jx-resources records
- Initial ws-legal-update entries for significant recent legal changes

Content standard:
All summaries must meet the reading level, structure, and citation
requirements defined in content-standards.md before being published.
The ws_human_reviewed badge is only set when those standards are met.

---

## Phase 3 — Accessibility and Navigation

Making the archive genuinely useful as a public-facing resource.

This phase is about the gap between having content and serving users.

Priorities:
- Situation-based entry points — navigation paths built around
  user intent, not just data structure:
    "I want to report something — am I protected?"
    "I've already reported — what are my rights?"
    "I need help right now"
- Procedures page rendering via shortcode or block
- Resources page rendering via shortcode or block
- Frontend design pass with GeneratePress Premium — typography,
  color, spacing, and visual hierarchy aligned with the platform's
  dual archive/resource identity
- Accessibility audit (keyboard navigation, screen reader
  compatibility, color contrast)
- Mobile usability review — Persona 1 (Maya) may be searching
  from a phone, possibly not from a work device

---

## Phase 4 — Platform Features

Structured capabilities that serve both casual users and researchers.

Priorities:
- Statute lookup — search by statute name or citation
- Jurisdiction comparison view — side-by-side protections across states
- Agency directory — which agencies run which programs
- Protected activity search — find laws relevant to specific
  types of misconduct (securities fraud, environmental violations,
  tax fraud, etc.)
- Legal updates feed — filterable by jurisdiction and topic

Technical considerations:
- Initial implementation via WP_Query and shortcodes
- REST API endpoints if external use cases emerge
- Search integration (native WordPress search or external engine)

---

## Phase 5 — Editorial Expansion

Scaling the content base and editorial process.

Priorities:
- Legal review program — structured engagement with licensed attorneys
  to review high-priority jurisdiction summaries
- Contribution guidelines — framework for external legal researchers
  to contribute content under editorial oversight
- Systematic revision cycle — scheduled re-review of all published
  summaries against current law
- Case law layer — significant court decisions affecting interpretation
  of whistleblower statutes

---

## Phase 6 — Professional Backend (Conditional)

A separate layer for legal professionals, if warranted.

This phase is not currently planned in detail. It would be added
if there is demonstrated need from attorneys, legal aid organizations,
or other professional users whose needs differ from the public resource.

A professional backend would be a separate interface — not a modification
of the public-facing platform — preserving the accessibility and tone
of the resource layer for its intended audience.

Possible features:
- Detailed citation export
- Case law cross-references
- Jurisdiction comparison exports
- Advanced filtering by statute type, enforcement agency, award type

---

## Guiding Principle Across All Phases

Every phase should make the platform more useful to someone
who is scared, overwhelmed, and trying to figure out if the law
is on their side.

That person is the reason this platform exists.
