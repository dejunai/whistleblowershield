# WhistleblowerShield — Documentation

This directory contains the working documentation for WhistleblowerShield.org
— a public-interest legal reference platform covering U.S. whistleblower
protections across 57 jurisdictions.

Documentation is organized into six areas. Read them in order if you are
new to the project. Jump directly to the relevant area if you are not.

---

## Areas

### `/project/`
Start here. Covers what the platform is, who it is for, and why it is
built the way it is.

- `project-overview.md` — mission, scope, the two core user questions,
  development context, and project principles
- `user-personas.md` — the three primary audiences: Maya (considering
  coming forward), James (facing retaliation), Daniel (researcher).
  Defines design priorities and the intentional ordering of those priorities.

### `/architecture/`
Covers the conceptual structure of the system — how legal information is
modeled, how the layers relate to each other, and the key design decisions
that shaped the current implementation.

- `system-architecture.md` — layer overview, data flow, separation of
  concerns, the query-layer contract
- `legal-system-model.md` — how legal concepts, jurisdictions, statutes,
  citations, and interpretations are modeled as structured data

### `/development/`
Technical reference for the ws-core plugin — the WordPress plugin that
implements the entire platform. Required reading before touching any code.

- `ws-core-system.md` — plugin overview, file structure, CPT inventory,
  taxonomy inventory, constants, naming conventions, load order
- `ws-core-data-layer.md` — ACF field group documentation for all 14
  field groups; field names, meta keys, types, conditional logic
- `ws-core-query-layer.md` — query layer function reference; return
  shapes, caching, invalidation patterns
- `ws-core-output-layer.md` — render functions, shortcodes, assembly
  pattern, CSS architecture
- `ws-core-audit-and-integrity.md` — audit trail, major edit logger,
  matrix divergence watch, procedure statute link validation, URL health
  monitor, Inoreader feed monitor, jurisdiction dashboard, runtime health check

### `/editorial/`
Standards and guidelines for content creation and maintenance.

- `editorial-system.md` — writing standards, content structure, the
  plain-language layer, the layered content model
- `research-and-transparency.md` — source handling, primary vs. secondary
  sources, interpretation standards, transparency practices

### `/product/`
User-facing design and guidance principles.

- `guidance-system.md` — how legal information is translated into
  practical guidance; the two-question model; jurisdiction and agency
  page structure; the curated vs. filtered render paths; Phase 2
  situation-based entry and what remains to activate it

### `/proposals/`
The living roadmap. Documents current development status, what is
built and working, what is structurally in place but not yet active,
deferred items with rationale, the pre-launch checklist, and known
technical debt.

- `current-proposals.md` — Phase 2 filter cascade spec, pre-launch
  checklist, deferred features, known technical debt

---

## Key Concepts

**The query layer contract:** All data retrieval goes through the query
layer (`includes/queries/`). Shortcodes, render functions, and admin
surfaces never call `get_field()`, `get_post_meta()`, or `WP_Query`
directly. This is the most important architectural rule in the codebase.
Violations produce fragile, unmaintainable output code.

**The taxonomy join:** All content CPTs are scoped to jurisdictions via
the `ws_jurisdiction` taxonomy, not post meta. The USPS code slug (e.g.
`ca`, `us`, `tx`) is the canonical join key across every content type.

**The attach-flag pattern:** Statutes, citations, and interpretations each
carry an `attach_flag` boolean. Flagged records are editorially curated
highlights surfaced on the jurisdiction summary page. The flag does not
control visibility — unflagged records are fully accessible via filtering.
It controls what appears in the curated summary view.

**The two-question split:** "Who can help me?" and "What do I do next?"
are answered on separate pages by design. Assist organizations answer the
first question. Agency filing procedures answer the second. This separation
is structural, not editorial.

---

## What This Documentation Is Not

This documentation describes the current state of the system as built.
It is not a tutorial or a style guide for the public-facing site. Future
feature specs live in `/proposals/current-proposals.md`, not scattered
through the technical documents. Version history lives in the individual
plugin files — docblocks carry a full changelog. This documentation
describes what is true now, not the path that got here.