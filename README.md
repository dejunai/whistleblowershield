# WhistleblowerShield

U.S. whistleblower law is complicated, scattered across federal and state
statutes, and written in language that assumes the reader already knows
what they are looking at. Most people who need it most do not.

WhistleblowerShield is a public-interest legal reference platform built
to change that. It covers whistleblower protections across all 57 U.S.
jurisdictions — 50 states, the District of Columbia, five U.S.
territories, and the federal level — and presents that information in
plain language grounded in primary legal sources.

---

## Who It Is For

The platform is designed primarily for two kinds of people:

**Someone considering coming forward.** They have witnessed wrongdoing
and are trying to understand whether the law protects them before they
decide what to do. They may be searching from their phone. They need a
clear answer to "am I protected?" without reading a law review article
to get it.

**Someone already facing retaliation.** They reported misconduct and
something has happened to them — a demotion, a termination, a threat.
They need to know which agency to file with, how long they have, and
what happens next. Filing deadlines for retaliation complaints can be
as short as 30 days. The platform treats that as the highest-priority
content on every relevant page.

A third audience — journalists, policy researchers, law students, and
legal advocates — is also served. They need accurate, well-cited
information they can rely on and attribute. The platform is built to
hold up under that scrutiny.

The platform is designed to answer two questions, kept structurally
separate: **"Who can help me?"** (answered through the assist
organization directory) and **"What do I do next?"** (answered through
agency filing procedures). Combining them on the same page creates
noise at the moment when clarity matters most.

---

## What This Repository Is

This repository contains the `ws-core` WordPress plugin — the custom
plugin that implements the entire data model, editorial workflow, and
public-facing output for WhistleblowerShield.org. The plugin is a
complete ground-up build, not a theme or a collection of third-party
plugins stitched together.

The platform is in active development in a staging environment. It has
not yet been deployed to a live server with real user data. The
development-only notice in `ws-core.php` will be removed at launch.

---

## Architecture in Brief

The `ws-core` plugin is organized into six conceptual layers:

**Data layer** — 10 Custom Post Types, 16 taxonomies, 14 ACF Pro field
groups. Legal information is structured data: statutes, citations, court
interpretations, agencies, filing procedures, and assist organizations
are discrete entities with explicit relationships, not paragraphs of text.

**Matrix layer** — Idempotent seeders that populate the canonical
dataset on first install: 57 jurisdiction posts and taxonomy terms,
federal statutes, court matrices, agencies, filing procedures, and
assist organizations. All seeded records are versioned and monitored
for post-install drift.

**Query layer** — The only layer that reads from the database. Returns
normalized PHP arrays. Shortcodes and render functions never call
`get_field()`, `get_post_meta()`, or `WP_Query` directly. This is the
most important architectural rule in the codebase — it is documented,
enforced in code review, and the reason the output layer is maintainable.

**Admin layer** — ACF field registration, audit trail, editorial stamp
fields, source verification workflow, procedure statute link validation,
URL health monitoring, Inoreader feed monitor, and a jurisdiction
completion dashboard.

**Assembly layer** — Render functions and shortcodes that transform
query layer output into HTML. Jurisdiction pages and agency pages are
assembled automatically from available published datasets — no manual
shortcode placement required in posts.

**Frontend assets** — Two conditionally loaded CSS files and one
JavaScript file. Jurisdiction-specific styles load only on jurisdiction
pages.

The `ws_jurisdiction` taxonomy is the canonical join key across every
content type. A statute that belongs to California carries the `ca`
term. There is no post meta join, no relationship field join. The USPS
code slug is the whole relationship.

---

## Documentation

Full documentation is in [`/documentation/`](documentation/README.md).

The documentation covers the project overview, user personas, system
architecture, the legal data model, all ACF field groups, the query
layer API, the output layer, every audit and integrity system, editorial
standards, the guidance model, source handling and transparency
practices, and the current development roadmap including the pre-launch
checklist and deferred features.

Read [`documentation/README.md`](documentation/README.md) first. It
maps the six documentation areas and explains the four key architectural
concepts a contributor needs to understand before reading any code.

---

## Development Context

This is a solo-founder project built by an independent researcher and
self-taught developer. AI tools are used throughout — for code,
documentation, and design iteration. All system design and final
decisions are human-directed. The codebase is explicitly commented and
architectural decisions are documented with rationale so the reasoning
behind each choice is recoverable without asking.

The project is public for transparency and as a reference for anyone
building similar public-interest legal infrastructure.

---

## Contributing

The project is not currently running a formal contribution process.
If you find this repository and have something genuine to offer —
legal research expertise, editorial review, technical work — you are
welcome to reach out at admin@whistleblowershield.org. No promises,
but the door is open.

---

## License

Copyright © WhistleblowerShield. All rights reserved.

This repository is public for transparency and reference. No
open-source license is granted. If you want to discuss use of any
part of this work, contact admin@whistleblowershield.org.

---

## Disclaimer

WhistleblowerShield provides legal information, not legal advice.
Nothing in this repository or on the platform constitutes legal advice
or creates an attorney-client relationship. Laws vary by jurisdiction,
change over time, and apply differently depending on individual facts.
If you are facing a real legal situation, consult a qualified attorney.
