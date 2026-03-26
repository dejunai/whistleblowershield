# Project Overview

## What This Is

WhistleblowerShield.org is a public-interest legal reference platform
covering U.S. whistleblower protections across 57 jurisdictions — 50
states, the District of Columbia, five U.S. territories, and the federal
level.

The platform is built on two foundational ideas:

1. **Legal information is structured data, not prose.** Statutes, citations,
   interpretations, agencies, procedures, and assist organizations are
   discrete entities with explicit relationships to each other and to
   jurisdictions. Storing and presenting them as structured records — rather
   than as articles — enables consistency, traceability, and filtering
   that prose-based sites cannot achieve.

2. **Accuracy without inaccessibility.** The people who most need
   whistleblower information are not legal professionals. They are employees
   who witnessed wrongdoing, people who already came forward and are now
   facing retaliation, and advocates trying to help them. The platform must
   be rigorous enough for a researcher to cite and simple enough for someone
   searching from their phone in a moment of fear.

---

## The Two Questions

The platform is ultimately designed to answer two questions for real people
in real situations:

**"Who can help me?"**
Answered through the assist organization directory — filtered by employment
sector, services offered, cost model, and jurisdiction.

**"What do I do next?"**
Answered through agency filing procedures — grouped by procedure type
(disclosure vs. retaliation), with deadline, identity policy, entry point,
prerequisites, and a plain-language walkthrough.

These two questions are answered in separate places by design. A person
considering coming forward needs to know what organizations exist to support
them. A person facing retaliation needs to know which agency to file with
and how long they have. Conflating the two paths creates noise at the
moment people can least afford it.

---

## Scope

**57 jurisdictions:** All 50 U.S. states, the District of Columbia, the five
U.S. territories (American Samoa, Guam, Northern Mariana Islands, Puerto
Rico, U.S. Virgin Islands), and the federal level.

**Content types per jurisdiction:**
- Plain-language summary of protections
- Statutes and regulations (with enforcement details, remedies, burden of
  proof, statute of limitations)
- Case law citations (with attach-flag curation for summary page highlights)
- Judicial interpretations (court-specific, statute-linked)
- Federal agencies with filing procedures
- Assist organizations (legal aid, advocacy, law firms, bar programs)

**Not in scope (initially):**
- County or municipal level protections
- International jurisdictions
- Employer compliance guidance
- Attorney directory or referral service

---

## Development Context

The platform is developed by a solo founder with assistance from AI tools.
AI is used to assist with structuring and refining documentation, support
analysis and iteration on system design, and help identify inconsistencies
and gaps. All outputs are reviewed and directed manually. System design
and final decisions are human-driven.

The codebase is built to support future contributors. Code is explicitly
commented, docblocks are comprehensive, and architectural decisions are
documented with rationale so a new contributor can understand not just
what the code does but why it was written that way.

The platform has never been deployed to a live server with real user data.
All development is currently in a staging environment.

---

## Project Principles

**Traceability over convenience.** Every piece of information on the
platform should be traceable to a primary legal source. Summaries are
helpful — but the underlying statute, citation, or regulation should always
be reachable from whatever surface the user is on.

**Structure over narrative.** Content is organized as structured data
objects with explicit relationships, not as articles that mention related
topics in passing. This makes the content queryable, filterable, and
maintainable in ways that narrative content is not.

**Plain language as a parallel layer.** Legal accuracy and plain language
are not opposites. The platform maintains both: structured legal records
with full technical detail, and plain-language overlays that translate
that detail for non-expert users. Both layers are editorially managed
and independently reviewable.

**Deadlines are life-or-death.** Filing deadlines for retaliation complaints
can be as short as 30 days. A missed deadline permanently forfeits legal
remedies. Every design and editorial decision that touches procedures must
treat deadline information as the highest-priority content on the page.
