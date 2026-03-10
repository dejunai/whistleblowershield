# System Architecture

## Overview

WhistleblowerShield is a legal knowledge platform designed to organize,
verify, and publish United States whistleblower protection laws.

The platform serves two distinct but complementary purposes:

**As a legal archive:** Structured, sourced, and verifiable documentation
of whistleblower protection statutes, procedures, agencies, and legal
updates — organized by jurisdiction and maintained with full editorial
accountability.

**As a public resource:** An accessible, plain-language reference for
ordinary people — including potential whistleblowers seeking to understand
their protections, and people already facing retaliation who need
to know what to do next.

These two purposes are not in conflict. The archive is the foundation.
The resource is what the archive is for.

The system architecture is intentionally modular, allowing legal data,
editorial workflow, and presentation layers to evolve independently.

---

## The Four Layers

### 1. Legal Knowledge Layer

Defines the conceptual model for how whistleblower law is represented.

Key documents:
- Whistleblower Law Ontology
- Whistleblower Law Taxonomy
- Jurisdiction Scope Model
- Legal Citation Model
- Source Verification Policy

These documents define what the platform knows and how it knows it.
They are independent of any implementation detail.

### 2. Data Management Layer

Implements the legal knowledge model inside WordPress.

The ws-core plugin handles:
- Custom Post Types (jurisdiction, jx-summary, jx-statutes,
  jx-procedures, jx-resources, ws-legal-update)
- ACF field groups defining structured data for each CPT
- Jurisdiction-centric relationships between all content types
- Audit trail recording all editorial changes

All data structures follow the CPT Relationship Map and the
ws-core Data Schema documents.

### 3. Editorial Layer

Governs how legal information enters the system and maintains quality.

The editorial layer enforces two standards simultaneously:
- **Legal accuracy:** primary source citations, verified links,
  factual correctness, review status tracking
- **Public accessibility:** plain-language writing, defined reading level
  target, user-centered summary structure

These standards are documented in:
- editorial-workflow.md (process)
- content-standards.md (writing and structure)
- user-personas.md (who the content is written for)
- legal-research-methodology.md (source standards)
- source-verification-policy.md (source reliability tiers)

### 4. Presentation Layer

Exposes structured legal data to end users in a form that serves
the three user personas defined in user-personas.md.

Current presentation mechanisms:
- Shortcodes rendering jurisdiction pages (ws_jurisdiction_header,
  ws_summary, ws_legal_updates, ws_disclaimer_notice, etc.)
- Jurisdiction index page with type-based filtering
- Legal updates archive

Future presentation priorities, ordered by user impact:
- Situation-based entry points ("I want to report something,"
  "I'm being retaliated against") that route to relevant content
- Procedures and resources pages rendered via shortcodes
- Jurisdiction comparison view
- Structured search by statute, agency, or protected activity type
- Block-based layouts replacing or supplementing shortcodes
- API access for researchers and downstream uses

---

## Design Goals

The architecture prioritizes:

**Legal accuracy** — all content is sourced, cited, and tracked.

**Public accessibility** — the frontend serves non-lawyers in real situations,
not just researchers. This shapes page structure, reading level standards,
navigation design, and what information appears first.

**Structured data** — legal information exists as structured fields,
not free-form text, enabling future search, comparison, and export.

**Extensibility** — the jurisdiction-centric model can expand to add
new content types, new jurisdictions, and new presentation mechanisms
without restructuring the core data model.

**Long-term maintainability** — editorial accountability (review status,
audit trail, last-reviewed dates) and documentation-first development
ensure the platform remains accurate as law evolves.

---

## What This Platform Is Not (Initially)

- Not a legal services provider or referral service
- Not a platform for reporting whistleblower disclosures
- Not a backend system for legal professionals
  (this may be added as a separate layer in a future phase)
- Not a general-purpose legal information site —
  scope is limited to U.S. whistleblower protection law

---

## Technology Stack

- WordPress (application framework)
- ws-core (custom plugin — legal data model, CPTs, ACF schema,
  shortcodes, audit trail)
- Advanced Custom Fields Pro (structured metadata)
- GeneratePress Premium (theme — layout and design)
- Cloudflare (security and caching)
- Managed hosting

---

## Development Status

The platform is currently in the transition from Phase 1 to Phase 2
of the project roadmap.

Phase 1 (substantially complete):
- Legal knowledge documentation
- System architecture documentation
- ws-core plugin foundation with all CPTs, ACF field groups,
  and core shortcodes implemented

Phase 2 (in progress):
- Population of legal knowledge base with jurisdiction content
- Procedures and resources page rendering
- Situation-based navigation and entry points
- Plain-language summary content meeting content-standards.md
