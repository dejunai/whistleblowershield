# Guidance Layer Model

## Purpose

This document defines how the WhistleblowerShield legal archive is
translated into plain-language public guidance, and establishes the
design principles that govern the public-facing site.

---

## Core Principle

The database serves the guidance.
The guidance serves the user.

The legal archive exists to ensure public content is accurate,
verifiable, and maintainable. The public site exists to help
workers understand their rights — not to expose the complexity
of the archive underneath it.

The public site should never reflect the internal structure of
the legal database. It should reflect the questions users arrive
with.

---

## Primary Audiences

All guidance layer decisions — page structure, navigation, reading
level, what information appears first — should be tested against
the three user personas defined in user-personas.md.

### Person in Crisis (Maya)

A worker who has witnessed wrongdoing and is afraid of retaliation.

Typical questions:
- Am I protected if I report this?
- Can I be fired for speaking up?
- Who should I report to?

Needs: reassurance, plain-language explanations, immediate
practical guidance. This is the primary audience for every
public-facing page.

### Person Facing Retaliation (James)

A worker who has already reported misconduct and is now
experiencing retaliation.

Typical questions:
- What protections exist?
- What agency can help me?
- What deadlines apply?

Needs: step-by-step procedural guidance, agency contacts,
filing deadlines. Information in this category is often
time-sensitive and must be surfaced prominently.

### Informed Researcher (Daniel)

A journalist, policy advocate, or legal researcher seeking
accurate, well-cited legal information.

Needs: citations, statutes, source links, editorial
transparency. The structured archive supports this audience
without dominating the public interface.

---

## The Three-Layer Model

All public pages follow a layered information structure:

```
Plain-Language Summary
        ↓
  Practical Guidance
        ↓
   Legal Citations
```

Most visitors will only read the first layer. The layers
beneath exist for users who want to go deeper — and to
ensure the first layer is accurate and verifiable.

This structure applies to jurisdiction pages, the homepage,
and any future situation-based entry points. The archive
provides the data. The layers determine what users see first.

---

## Question-Driven Design

Pages should be organized around the questions users actually
arrive with, not around the internal structure of the database.

The database is organized by jurisdiction, then by content type
(summary, statutes, procedures, resources). This is correct for
the data model.

The public site should also support situation-based entry points:

- "I want to report something — am I protected?"
- "I've already reported — what are my rights?"
- "I need help right now"

These paths route users to the right content without requiring
them to understand how the archive is organized.

---

## Jurisdiction Page Structure

Jurisdiction pages present legal information in this order:

1. Plain-language overview — does this jurisdiction protect
   whistleblowers, and who does it cover?
2. Key protections — what disclosures are protected, what
   retaliation is prohibited
3. Reporting procedures — who to contact, how to file,
   what deadlines apply
4. Resources — organizations and assistance available
5. Statutes — key legal citations with official source links

This order reflects what users need most urgently, not what
is most comprehensive or technically complete.

The California page is the current reference implementation
of this structure. Future jurisdiction pages should follow
the same pattern, generated from structured data rather than
built manually.

---

## Homepage Design Principle

The homepage should orient a distressed visitor within seconds.

It should answer:
- What is this site?
- Is there a law that protects me?
- Where do I start?

The current homepage is functional but not yet fully
situation-driven. Future revisions should introduce
entry points organized around user intent:

- Am I protected?
- How do I report safely?
- What do I do if retaliation has already started?

These paths are a navigation layer built on top of the
existing data structure — they do not require changing the
underlying archive.

---

## Tone and Accessibility Standards

The public site must remain:

- Calm and non-intimidating
- Written in plain language throughout
- Easy to navigate under stress
- Accessible on mobile devices

Legal terminology should be explained on first use.
Where examples clarify better than definitions, use examples.
See editorial-standards.md for full writing guidance including
the tooltip and parenthetical system for inline definitions.

The disclaimer notice is present on all jurisdiction pages
and is intentionally written in an informational rather than
alarming tone. Users in difficult situations deserve to
understand the site's limitations without being made to feel
the information they find is worthless.

---

## Separation of Concerns

Internal documentation — data models, editorial workflows,
plugin architecture — supports the project internally.

It must never drive the complexity of the public interface.

The public site is for users. The documentation is for
contributors and maintainers. These are different audiences
with different needs, and the distinction should always
be preserved.

---

## Development Reality

The platform is currently maintained by a single developer
without staging infrastructure. During this phase:

- The live site functions as both prototype and production
- Pages may be manually constructed as reference models
- Automated rendering from structured data will come later

This is an acceptable constraint as long as the guidance
philosophy remains consistent across all published content.

---

## Long-Term Direction

As the platform grows, the archive may support:

- Situation-based navigation generated from structured data
- Jurisdiction comparison views
- Statute and agency lookup tools
- API access for researchers and downstream uses

These capabilities should remain secondary to the core mission:
providing clear, accurate guidance to workers who need to
understand their rights.
