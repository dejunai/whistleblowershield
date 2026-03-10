# Editorial Workflow

## Overview

The editorial workflow defines how legal information enters, is reviewed,
and evolves within the WhistleblowerShield platform.

Because the project focuses on legal accuracy AND public accessibility,
editorial discipline serves two purposes: ensuring the information is
correct, and ensuring it is useful to someone who is not a lawyer.

Both standards must be met before content is published.

---

## Who This Workflow Is For

This workflow applies to all editors and researchers contributing content
to WhistleblowerShield. The platform is built by an amateur legal researcher
and independent developer. Contributors are not assumed to be licensed
attorneys unless specifically noted.

This means:
- Caution and humility are the default editorial stance
- Primary sources are always preferred over interpretation
- When uncertain, the content should say so — or be left unpublished
- Legal review by a licensed attorney is the highest tier of validation,
  but is not required to publish a well-cited, clearly labeled draft

---

## Audience First

Before drafting any content, the editor should ask:

"Who is going to read this, and what do they need to know?"

The three primary user types are defined in user-personas.md.
The most important users to write for are:

- The person who has witnessed misconduct and doesn't know if they
  are protected (Persona 1 — Maya)
- The person who has already reported and is facing retaliation
  (Persona 3 — James)

Legal information that is accurate but inaccessible to these users
has failed its purpose on this platform.

---

## Phase 1 — Research

Researchers identify relevant whistleblower laws using:

- United States Code (uscode.house.gov, govinfo.gov)
- Code of Federal Regulations (ecfr.gov)
- Agency rulemaking and official publications
- State legislature official websites

Each law must be verified against primary sources before being
entered into the platform. Secondary sources may inform understanding
but must not replace primary citations.

See legal-research-methodology.md for source reliability standards.

---

## Phase 2 — Data Entry

Legal information is entered into structured fields defined by the
ws-core data schema.

Key fields per content type:

**jx-summary:** jurisdiction relationship, summary body (WYSIWYG),
sources & citations, date created, last reviewed, author.

**jx-statutes:** jurisdiction, statute name, citation, type,
official source URL, effective date.

**jx-procedures:** jurisdiction, procedure type, responsible agency,
agency URL, procedure description, official guidance URL, filing time limit.

**jx-resources:** jurisdiction, organization name, type, resource URL,
description, phone (optional).

**ws-legal-update:** affected jurisdiction(s), law/statute name,
update summary, effective date, source URL.

All entries must reference a jurisdiction. No orphan content.

---

## Phase 3 — Writing the Summary

The jx-summary post is the most reader-facing content type.
It must meet both the legal accuracy standard and the plain-language
standard defined in content-standards.md.

Required structure:
1. Opening statement — does this jurisdiction protect whistleblowers?
2. Who is covered
3. What disclosures are protected
4. Protections against retaliation
5. How to report / what to do
6. Notable statutes with citations

Reading level target: 9th to 10th grade.
See content-standards.md for full writing guidance.

---

## Phase 4 — Editorial Review

Before setting a post to Published, the editor confirms:

- Citation accuracy — every legal claim has a verifiable source
- Source links — all URLs resolve to official government sources
- Summary structure — follows the structure in content-standards.md
- Reading level — verified against the 9th–10th grade target
- Plain language — key legal terms are explained on first use
- Completeness — required fields are filled; no placeholder text remains
- Duplicate check — confirm no existing record covers the same content

When review is complete:
- Set ws_human_reviewed to Reviewed
- Update ws_last_reviewed to today's date

---

## Phase 5 — Publication

Once approved, the entry is set to Published and becomes publicly visible.

The ws_review_status badges (Human Reviewed, Pending Human Review,
Legally Reviewed, Pending Legal Review) are displayed publicly on
jurisdiction pages. These are real trust signals to users — they should
only be marked as reviewed when that review has genuinely occurred.

---

## Phase 6 — Legal Review (Optional, Highest Tier)

If a licensed attorney reviews a summary:

- Set ws_legal_review_completed to Completed
- Enter the attorney's name in ws_legal_reviewer
- The name and legal review badge are displayed publicly

Legal review is the highest validation tier and should be pursued
for high-priority jurisdictions, but is not a prerequisite for publication
of well-cited, clearly labeled content.

---

## Phase 7 — Revision

Whistleblower law changes. Entries must be updated when:

- Statutes are amended or repealed
- Regulations change
- Court interpretations affect meaning
- Agency guidance is updated
- A ws-legal-update entry documents a change in this jurisdiction

On revision:
- Update ws_last_reviewed to the revision date
- Update ws_summary_sources if sources have changed
- Reset ws_human_reviewed to Pending if substantive changes were made
  (the content should be re-reviewed before the badge is restored)
- Create a ws-legal-update entry to document what changed and why

---

## Keeping the Reader in Mind Throughout

At every phase, ask whether the content serves someone who is:
- Not a lawyer
- Under stress
- Trying to make a real decision about their situation

If the answer is no — if the content is complete but only comprehensible
to someone with legal training — it needs further revision before
it meets the platform's editorial standard.
