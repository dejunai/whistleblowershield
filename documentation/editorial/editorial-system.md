# Editorial System

## Purpose

This document describes how content is written, structured, reviewed,
and maintained on WhistleblowerShield. It is the guide for anyone
writing or editing content — from a first-time contributor to an
experienced editor returning to a record after months away.

The editorial system exists to enforce the platform's core promise:
accurate, traceable, current legal information presented clearly enough
for a frightened person searching from their phone to understand.

---

## The Three Audiences

Every content decision should be tested against the three personas
(see `user-personas.md`). The ordering is intentional:

1. **Maya** (pre-disclosure) — does this help her understand whether
   she is protected and what her options are?
2. **James** (post-disclosure, retaliation) — does this help him file
   a complaint, find an agency, and understand his deadline?
3. **Daniel** (researcher) — is this accurate, well-cited, and
   attributable?

If content serves Daniel but fails Maya, it needs more plain-language
work. If it serves Maya but fails Daniel, it needs more citation work.
The goal is both.

---

## The Layered Content Model

Most content on the platform follows a four-layer structure. Not every
layer is present on every record — use the layers that apply.

**Layer 1 — Plain Language Summary**
The first thing the user reads. Answers the user's immediate question
in plain language. Written for Maya, not Daniel. No legal citations
visible at this layer — they are reachable but not leading.

**Layer 2 — Practical Guidance**
What the user should understand about their situation. What their
options are. What the consequences of each path might be. Not advice —
never prescribe a specific action — but clear enough to support
decision-making.

**Layer 3 — Legal Detail**
The full technical record: statute citation, burden of proof, statute
of limitations, employer defenses, remedies, enforcement process. For
the user who wants to go deeper or verify what the summary says.

**Layer 4 — Primary Sources**
Links to the actual statute, regulation, court opinion, or agency
guidance document. The foundation of traceability. Every legal claim
on the platform should be reachable at this layer.

---

## Writing Standards

### Clarity

Use plain, direct language. Avoid legal jargon where possible. When
technical terms are unavoidable, provide enough context for a
non-lawyer to understand what they mean in practice.

Do not write for a general reader — write for someone who is stressed,
in a hurry, and not familiar with legal terminology. If a sentence
requires the reader to already know what a term means to understand
the sentence, it is not plain language.

### Accuracy

Do not overstate legal conclusions. Do not imply certainty where none
exists. The law varies, statutes are interpreted differently by
different courts, and outcomes depend on facts that the platform
cannot assess.

Every statement about what the law provides must be supportable by
a primary source. If a claim cannot be traced to a statute, regulation,
or published court decision, it should not be in the content.

### The Not Legal Advice Line

The platform provides legal information, not legal advice. This is not
just a disclaimer — it is an editorial constraint. Content must not:

- Tell a specific user what to do in their specific situation
- Predict outcomes
- Recommend a particular attorney, organization, or course of action
  without disclosure

The `[ws_nla_disclaimer_notice]` shortcode appears on all jurisdiction
pages and is rendered by the assembly layer automatically. Editors do
not need to add it manually.

### Practical Focus

Keep content grounded in what a user is actually trying to understand
or decide. Abstract explanations are acceptable only when they serve
a practical point. If a paragraph could be cut without the user losing
anything actionable, cut it.

---

## Content Types and Editorial Expectations

### Summary (`jx-summary`)

The plain-language overview of whistleblower protections for a
jurisdiction. One per jurisdiction.

The summary is inherently plain language — there is no separate plain
language overlay. It is the plain language document. Write it for Maya.

Every summary should answer: "Does the law in this jurisdiction protect
someone like me, and what are the most important things I should know?"

The `ws_jx_limitations` repeater captures important caveats and
limitations that do not fit in the main summary body — narrow coverage
scope, short deadlines, significant exclusions. These appear at the
bottom of the summary section.

### Statute (`jx-statute`)

The detailed record for a specific law or regulation. Statutes are the
primary legal content of the platform — every other content type
relates back to one or more statutes.

The plain English overlay (`ws_plain_english_wysiwyg`) is optional per
record. When added, it should explain what the statute means in
practice, not restate the legal text. A good plain English note
answers: "What does this statute actually do for a whistleblower?"

The `attach_flag` marks statutes for the curated summary page view.
Flag three to five statutes per jurisdiction — the ones that are most
broadly applicable and most important for the primary personas. More
flagged records dilutes the curation signal.

### Citation (`jx-citation`)

A court decision or administrative ruling that interprets a statute.
Citations show how law works in practice, which is often different
from how it reads on paper.

Write the plain English overlay to answer: "What did this decision
establish, and why does it matter to a whistleblower?"

The `attach_flag` marks citations significant enough for the summary
view. A flagged citation should be genuinely important — a landmark
ruling, a ruling that limits protection in ways users need to know
about, or a ruling that extends protection in ways that are
non-obvious from the statute text.

### Interpretation (`jx-interpretation`)

A structured record of how a specific court has interpreted a
statutory protection. More structured than a general citation — links
explicitly to a statute and a specific court.

The `ws_jx_interp_favorable` boolean records whether the outcome
favored the whistleblower. This is editorial judgment and should
reflect the practical outcome for the whistleblower, not just the
technical disposition of the case.

### Agency (`ws-agency`)

A government body that receives disclosures or investigates
retaliation. The agency record is the "who do I contact" answer.

Keep the description focused on what the agency actually does for
whistleblowers. Do not describe the agency's general mission — describe
its whistleblower-specific role.

The `ws_agency_confidentiality_notes` field should capture any
non-obvious identity protection policies. Many users' first question
is "can I report anonymously?" — this field answers it for agencies
where the answer is nuanced.

### Filing Procedure (`ws-ag-procedure`)

The most operationally critical content type on the platform. Mistakes
here have direct real-world consequences.

The walkthrough (`ws_proc_walkthrough`) is the plain language core of
the procedure record. It should answer: what should I prepare, how do
I submit, what happens after I file, and what is a realistic timeline?
Write it as if explaining to someone who has never filed a legal
complaint before.

**Filing deadlines** must be accurate. The `ws_proc_deadline_days`
field holds the number of calendar days. When the deadline varies by
statute, use the shortest applicable deadline as the value and explain
the variance in the walkthrough.

The `ws_proc_exclusivity_note` is critical and frequently overlooked.
Filing under some procedures forecloses other remedies or procedures.
This must be documented clearly. If there are no known exclusivity
implications, leave the field blank — do not write "none" or a
placeholder.

### Assist Organization (`ws-assist-org`)

Organizations that help whistleblowers. The record must be accurate
about what the organization can and cannot do.

The cost model, income eligibility, and anonymous client fields are
the most practically important for Maya and James. Lead with those in
the description if they are non-obvious.

Do not recommend organizations over others. The platform surfaces
organizations based on what they offer and where they operate — the
editorial role is accuracy, not endorsement.

---

## The Review Workflow

### Source Verification

Every record carries source verification fields (from the shared
`acf-source-verify.php` group):

- `ws_auto_source_method` — how the record was created
- `ws_verification_status` — `verified`, `needs_review`, `unverified`,
  or `outdated`
- `ws_needs_review` — a flag that can be set at any time to mark a
  record for editorial attention

Matrix-seeded records start with `ws_auto_source_method = matrix_seed`.
AI-assisted records carry `ws_auto_source_method = ai_assisted`. Both
require human verification before `ws_verification_status` should be
set to `verified`.

### Plain Language Review

Records with a plain English overlay carry a reviewed toggle
(`ws_plain_english_reviewed`). When toggled on, the reviewer's name
and date are auto-stamped. The trust badge on the public-facing page
reflects this review status.

The "Editor Reviewed" badge signals to users — particularly Daniel —
that a human editor has reviewed the plain language content for
accuracy. Do not toggle it on unless the review has genuinely occurred.

### Major Edit Logging

When making a significant change to any content record, flag the save
as a major edit and provide a description. This creates a `ws-legal-update`
post automatically and maintains the site's public legal update log
without additional manual work.

Significant changes include: statute amendments, new court decisions,
deadline changes, agency restructuring, procedure URL changes. Minor
changes (fixing a typo, updating formatting) do not need to be flagged.

---

## Source Handling

### Primary vs. Secondary Sources

Always prefer primary legal sources:
- Statutes: govinfo.gov, state legislature websites
- Regulations: the Code of Federal Regulations (eCFR), state
  administrative codes
- Court decisions: official court websites, PACER, state court portals
- Agency guidance: agency official websites

Secondary sources (law review articles, advocacy organization guides,
news coverage) may be used for context and are appropriate in the
`ws-reference` CPT. They must never be the sole basis for a legal claim.

### Currency

Legal information goes stale. Statutes are amended. Agencies
restructure. Deadlines change. Court decisions alter interpretations.

The `ws_proc_last_reviewed` and equivalent last-reviewed fields on
other CPTs record when a record was last verified against its source.
These fields must be updated when a record is verified, not just when
it is edited.

The URL health monitor checks that source links are still reachable.
A working link is not the same as current content — verify the content
at the link, not just the link itself.

### What the Platform Does Not Provide

The platform provides legal information. It does not provide:
- Legal advice for a specific situation
- Recommendations of specific attorneys
- Predictions of legal outcomes
- Exhaustive coverage of all statutes in any jurisdiction

Content should never imply otherwise. When the platform's coverage is
incomplete, say so plainly rather than implying comprehensiveness.
