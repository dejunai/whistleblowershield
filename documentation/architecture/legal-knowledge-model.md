# Legal Knowledge Model

## Purpose

This document defines both the conceptual legal entities represented
within the WhistleblowerShield knowledge system and how those entities
are organized around jurisdictions in practice.

It replaces two earlier documents — legal-entity-model.md and
jurisdiction-knowledge-model.md — which covered overlapping ground
from different angles. This document combines them into a single
coherent reference.

---

## Design Goals

The legal knowledge model is designed to:

- Represent whistleblower law in structured, queryable form
- Preserve traceable legal citations for every factual claim
- Maintain explicit relationships between statutes, agencies,
  jurisdictions, and programs
- Allow expansion as additional legal frameworks are documented
- Support future query, comparison, and export capabilities

The system prioritizes legal clarity and data integrity over complexity.

---

## The Root Entity: Jurisdiction

The primary organizing object in the system is the **jurisdiction**.

Every legal entity in the system attaches to a jurisdiction.
No legal content should exist without jurisdiction context.

A jurisdiction represents a legal authority within the
United States. See jurisdiction-scope-model.md for the full
list of 57 covered jurisdictions.

Examples:

- United States (Federal)
- California
- District of Columbia
- Puerto Rico

---

## Knowledge Structure

Each jurisdiction contains up to five types of associated content:

```
Jurisdiction
 ├ Summary        — plain-language overview of protections
 ├ Statutes       — formal legal authorities and citations
 ├ Procedures     — how to report violations and seek relief
 ├ Resources      — external organizations and assistance
 └ Legal Updates  — tracked changes in law or policy
```

Each component is a separate structured record linked to its
jurisdiction. No component should exist as an orphan record
without a jurisdiction relationship.

---

## Core Legal Entities

### Statute

A statute is a law enacted by a legislative authority and
codified in an official legal code.

At the federal level, statutes are codified in the United States
Code. At the state level, statutes are codified in official
state codes.

Statutes establish whistleblower protections, define enforcement
authority, and create reporting mechanisms.

Examples:

- False Claims Act (31 U.S.C. §§ 3729–3733)
- Sarbanes-Oxley Act (18 U.S.C. § 1514A)
- Dodd-Frank Act whistleblower provisions (15 U.S.C. § 78u-6)
- California Labor Code § 1102.5

Statutes often authorize regulatory agencies to implement
implementing rules and administer enforcement programs.

---

### Regulation

Regulations are rules issued by federal or state agencies
under statutory authority. They are typically codified in
the Code of Federal Regulations (federal) or equivalent
state administrative codes.

Regulations often clarify:

- reporting procedures and eligibility requirements
- retaliation protections and prohibited employer conduct
- enforcement mechanisms and penalties
- financial award criteria for whistleblower programs

Examples:

- 17 C.F.R. § 240.21F-2 (SEC whistleblower program rules)
- 29 C.F.R. § 24.102 (OSHA retaliation complaint procedures)

---

### Agency

Agencies administer whistleblower programs and enforce legal
protections. An agency may enforce multiple statutes and
administer multiple programs.

Examples:

- Securities and Exchange Commission (SEC)
- Commodity Futures Trading Commission (CFTC)
- Department of Labor / OSHA
- Internal Revenue Service (IRS)
- Office of Special Counsel (OSC)

---

### Whistleblower Program

Some agencies operate formal whistleblower programs that define
specific eligibility criteria, reporting channels, confidentiality
protections, and in some cases monetary award structures.

Programs operate under statutory authority and are typically
administered through a dedicated office within the agency.

Examples:

- SEC Office of the Whistleblower
- IRS Whistleblower Office
- CFTC Whistleblower Program

---

### Summary

A summary is a human-readable, plain-language explanation of
whistleblower protections within a jurisdiction, written for
non-lawyers.

Each summary includes:

- overview of protections and who is covered
- what disclosures are protected
- protections against retaliation
- how to report and seek relief
- key statutes with citations
- sources and citations
- authorship and review metadata

Summaries are the primary public-facing content type on the
platform. They must meet the reading level and structure
standards defined in editorial-standards.md.

---

### Legal Update

Legal updates track significant developments that affect the
accuracy of platform content.

Examples:

- statutory amendments or new legislation
- regulatory rule changes
- major court decisions affecting interpretation
- agency policy or program changes

Each legal update includes a law or statute name, a summary
of the change, a source citation, and an effective date.
Legal updates are linked to one or more affected jurisdictions.

---

## Entity Relationships

Legal entities interact through structured, explicit relationships:

```
Statute       → authorizes →    Agency
Agency        → administers →   Whistleblower Program
Agency        → issues →        Regulation
Regulation    → implements →    Statute
Legal Update  → modifies →      Statute or Regulation
Summary       → describes →     Jurisdiction
Statute       → applies in →    Jurisdiction
Procedure     → governs →       Jurisdiction
Resource      → serves →        Jurisdiction
```

These relationships allow the system to represent complex legal
frameworks and support future features such as cross-jurisdiction
comparison and statute-level search.

---

## Implementation in ws-core

The ws-core plugin implements this knowledge model using
WordPress Custom Post Types and Advanced Custom Fields.

Each entity type corresponds to a structured CPT with defined
fields. Relationships between entities are implemented through
ACF relationship fields, ensuring all connections are explicit
and queryable.

See ws-core-data-schema.md for field-level schema documentation.
See cpt-relationship-map.md for the full CPT relationship diagram.

---

## Future Expansion

The model is designed to accommodate additional entity types
without restructuring the core architecture. Possible future
additions include:

- Case law — significant court decisions as discrete records
- Regulatory agencies — agency profiles with program relationships
- Enforcement actions — tracked enforcement history

Any expansion should be documented in architecture/ before
implementation and must maintain the jurisdiction-centric
relationship model.
