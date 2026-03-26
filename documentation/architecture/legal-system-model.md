# Legal System Model

## Purpose

This document describes how legal information is conceptually structured
within the platform — what the entities are, how they relate to each
other, and the modeling decisions that shaped the data layer.

It is a reference for contributors who need to understand the domain
before working with the data. It is not a technical specification of the
database schema — that lives in `ws-core-data-layer.md`.

---

## The Core Problem

Whistleblower law in the United States is not a single body of law. It is
a patchwork of federal statutes, state statutes, agency-specific regulations,
and case law interpretations that vary by:

- **Jurisdiction** — federal law applies everywhere; state law varies
  dramatically; territorial law is its own category
- **Employment sector** — federal employees, private sector employees,
  military contractors, and healthcare workers are often covered by
  different laws
- **Disclosure type** — reporting securities fraud is governed by different
  law than reporting workplace safety violations
- **Timing** — a person considering coming forward has different legal
  needs than a person already facing retaliation

The platform's data model exists to make this complexity navigable without
flattening it. The answer to "am I protected?" is genuinely different
depending on who you are, where you work, what you reported, and when.

---

## Primary Entities

### Jurisdiction

The organizing unit of the entire system. Every other entity belongs to
one or more jurisdictions.

A jurisdiction is not just a geographic boundary — it is the legal
authority that governs a particular set of laws. The 57 jurisdictions in
the system are: the federal level (`us`), the District of Columbia (`dc`),
the 50 U.S. states, and the five U.S. territories (American Samoa, Guam,
Northern Mariana Islands, Puerto Rico, U.S. Virgin Islands).

Each jurisdiction has its own page on the platform, assembling all
content records scoped to it.

### Statute

A specific law or regulation that provides whistleblower protection.
Statutes are the foundational legal content of the platform — everything
else (citations, interpretations, procedures) connects back to statutes.

A statute record captures:
- The official name and citation
- The protected class of workers it covers
- The types of disclosure it protects
- The adverse actions it prohibits
- The process types available for enforcement
- The remedies available
- The burden of proof standard
- Whether a statute of limitations applies and how long it is
- Whether exhaustion of remedies is required before filing
- Whether the employer has a same-decision defense available
- Fee shifting rules
- Reward provisions where applicable (e.g. qui tam, SEC bounty)

Federal statutes are automatically appended to every state jurisdiction
page because federal law applies in every state. The `is_fed` flag in
the query layer return distinguishes federal records from state records
when both appear together.

### Citation

A published court decision or administrative ruling that interprets,
applies, or limits a statute's protection in a specific factual context.

Citations are the case law layer. A citation is always linked to one or
more statutes. The `attach_flag` marks citations that are significant
enough to surface on the jurisdiction summary page as curated highlights.
Unflagged citations are fully accessible via filtering.

### Interpretation

A structured record of how a specific court or tribunal has interpreted
a statutory protection, linked to both a statute and a specific court.

The court dimension is significant: the same statute may be interpreted
differently by the Ninth Circuit and the Fifth Circuit. Interpretations
carry the court key, which resolves to a court entry in one of two
in-memory court matrices (federal courts or state/territorial courts).
When no court applies or the court is not in the matrix, the `other`
sentinel allows free-text entry.

### Agency

A government body with jurisdiction to receive whistleblower disclosures
or investigate retaliation complaints. Agencies are the "who do I contact"
answer on the platform.

An agency record captures the agency's mission, contact information,
reporting URLs, hotlines, languages supported, and the disclosure types
it handles. Agencies carry the `ws_jurisdiction` taxonomy term indicating
where they have authority.

### Filing Procedure

A specific intake pathway offered by an agency for either disclosing
wrongdoing or filing a retaliation complaint — or both. Procedures are
the "what do I do next" answer on the platform.

A procedure record captures:
- Whether it covers disclosure, retaliation, or both
- The entry point (online, mail, phone, in-person, or multiple)
- The intake URL and direct phone number
- The identity policy (anonymous, confidential, or identified)
- Whether the agency only receives referrals without investigating
- The filing deadline in calendar days and the event that starts the clock
- Prerequisites before filing (e.g. exhaustion of internal remedies)
- A plain-language step-by-step walkthrough
- A mutual exclusivity note (remedies or procedures the filer may forfeit
  by using this pathway)
- Links to related statutes

Procedures are attached to their parent agency via the `ws_proc_agency_id`
field and cross-referenced to relevant statutes via `ws_proc_statute_ids`.
The statute cross-reference enables a compact "Filing Procedures Under
This Statute" panel on the jurisdiction page and powers the procedure
watch validation system.

### Assist Organization

A non-government entity that helps whistleblowers — legal aid clinics,
law firms, advocacy organizations, bar association programs, labor unions,
and government oversight offices. Assist organizations answer "who can
help me?" as distinct from "what do I do next?"

An assist organization record captures the organization's type, cost
model, services offered, employment sectors served, languages supported,
disclosure types covered, case stages served, contact information, and
jurisdiction scope. Nationwide organizations carry the `is_nationwide`
flag and are surfaced on all jurisdiction pages regardless of their
taxonomy term assignment.

### Summary

The plain-language overview of whistleblower protections for a specific
jurisdiction. The summary is the first thing a user sees on a jurisdiction
page and the primary answer to "am I protected?"

Unlike other content CPTs, the summary is itself the plain-language
document — it does not carry a plain English overlay. The summary is
written for Persona 1 (Maya): someone who may be frightened, searching
from a phone, and not familiar with legal terminology.

### Legal Update

A timestamped record of a significant legal development — a new statute,
an amended regulation, a landmark court decision, or a policy change.
Legal updates appear on jurisdiction pages and in a sitewide feed.

### Reference

An external source document linked to a statute, citation, or
interpretation. References appear in a dedicated reference page surfaced
from a small trigger link on the parent record. All reference links
carry `rel="noopener noreferrer nofollow"` and open in a new tab.

---

## Key Relationships

```
Jurisdiction (57)
    │
    ├── Summary (one per jurisdiction)
    │
    ├── Statute (many, including federal appended to all states)
    │       ├── Citation (many, attach_flag for curation)
    │       ├── Interpretation (many, court-scoped)
    │       └── Reference (many, external sources)
    │
    ├── Agency (many)
    │       └── Filing Procedure (many, type: disclosure | retaliation | both)
    │               └── Statute cross-reference (many-to-many)
    │
    ├── Assist Organization (many + nationwide overlay)
    │
    └── Legal Update (many, also sitewide)
```

All relationships in the diagram above are implemented via the
`ws_jurisdiction` taxonomy join — not via post meta or ACF relationship
fields. The only exception is the procedure-to-agency link
(`ws_proc_agency_id`, a direct post ID reference) and the
procedure-to-statute cross-reference (`ws_proc_statute_ids`).

---

## The Concept vs. Law Distinction

One modeling decision worth explicit documentation: the platform
distinguishes between a **legal concept** (a general protection that
exists in law) and a **jurisdiction-specific implementation** (how that
concept is expressed in a particular statute).

In practice: "retaliation protection" is a concept. The Sarbanes-Oxley
Act § 806 is a jurisdiction-specific implementation of that concept for
publicly traded companies. The False Claims Act § 3730(h) is a different
implementation of the same concept.

This distinction is expressed through the taxonomy system:
`ws_disclosure_type` classifies what a record covers in conceptual terms.
The statute record itself describes the specific law. A user can filter by
concept without needing to know which statute implements it — the taxonomy
handles that mapping.

---

## Plain Language as a Parallel Layer

Legal accuracy and plain language are not the same document. The platform
maintains both.

For statutes, citations, and interpretations: the primary record contains
the full technical detail — citation, burden of proof, statute of
limitations, employer defense standards. A plain English overlay
(controlled by `has_plain_english` and `ws_plain_english_wysiwyg`) can
be added per-record. The overlay is optional and independently reviewable.

For summaries: the entire document is the plain language layer. There is
no technical overlay — the summary is written for Maya, not for Daniel.

For filing procedures: the walkthrough field (`ws_proc_walkthrough`) is
the plain language layer. The procedure record does not carry the separate
plain English toggle because the walkthrough IS the plain language content
— there is no technical version of "here is how to file."

---

## Source Integrity

Every content record in the system carries source verification fields
(`ws_auto_source_method`, `ws_auto_source_name`, `ws_verification_status`,
`ws_needs_review`) managed by a dedicated source verify workflow.

The source method values are:
- `matrix_seed` — created by a matrix seeder at install
- `ai_assisted` — created or substantially drafted with AI assistance
- `human_created` — created directly by a human editor
- `bulk_import` — created via a structured import process
- `feed_import` — created via the Inoreader feed monitor

Matrix-seeded records are the highest-staleness-risk category because
they were never manually entered and have no natural editorial touchpoint.
The source verify workflow combined with the matrix divergence detection
system is designed specifically to surface these records when they need
review.
