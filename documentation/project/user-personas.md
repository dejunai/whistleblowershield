# User Personas

## Purpose

This document defines the three primary audiences for WhistleblowerShield.org.

Every frontend design decision — navigation structure, page layout, reading
level, what information appears first, what gets buried — should be tested
against these three people. If a design choice serves all three without
compromising any one of them, it is probably the right choice. Where
tradeoffs exist, Persona 1 wins.

The platform has a dual nature: it is a rigorous legal archive on the
backend, and an accessible public resource on the frontend. These personas
define who the frontend is built for.

---

## Persona 1 — The Person Considering Coming Forward

**Name:** Maya

**Who she is:**
An employee at a mid-size company. She recently witnessed what she believes
is financial fraud. She has not told anyone yet. She is scared — of losing
her job, of being wrong, of not being believed, of legal consequences she
doesn't fully understand.

**How she arrives:**
Google search. Something like "am I protected if I report my boss" or
"whistleblower laws in California." She may be searching from her phone,
quite possibly not from her work computer.

**Mental state:**
Anxious. Overwhelmed. Low trust in institutions. Short attention span
because of stress. Skeptical of anything that feels like a law firm
trying to get her business. If the site feels cold or corporate she will
leave before she finds what she needs.

**What she needs:**
- A fast, plain-language answer to: "Does the law protect someone like me?"
- Enough information to decide whether it is safe to move forward
- A clear sense of what a "whistleblower" actually is and whether she qualifies
- Links to legitimate help organizations and official government resources
- To feel like the site is on her side, not just presenting legal data at her

**What a successful visit looks like:**
Maya leaves understanding what protections exist in her state, has a sense
of what her next step could be, and feels less alone. She does not need to
have read everything — she needs to have found something that helps.

**Design implications:**
- Jurisdiction pages must lead with protections in plain language
- The question "am I protected?" should be answerable without reading a
  full legal summary
- Navigation must support "I don't know what I'm looking for" entry points
- Trust signals matter: citation transparency, last-reviewed dates, and
  clear editorial standards make the site feel reliable without feeling
  like a firm
- The disclaimer that this is not legal advice must never feel like a wall

---

## Persona 2 — The Person Facing Retaliation

**Name:** James

**Who he is:**
Someone who has already reported misconduct — internally or to a government
agency — and is now experiencing or fearing retaliation. He may have been
demoted, threatened, or fired. He knows something has happened to him. He
is looking for specific, actionable help: what are his rights, who does he
contact, and how long does he have.

**How he arrives:**
Urgent search. "Whistleblower retaliation rights," "what to do if fired for
whistleblowing," "OSHA complaint deadline." He may be searching under
significant time pressure — deadlines are real and short.

**Mental state:**
Distressed. More urgent than Maya because something has already happened.
Needs actionable information immediately, not background context. May
already know some basics about whistleblower law — what he needs is the
next concrete step. Every unnecessary click is a cost.

**What he needs:**
- Filing procedures: who to contact, how to file, what the deadlines are
- Time limits — this is often the most critical piece of information on
  the entire site; a missed deadline forfeits legal remedies permanently
- Direct links to agency complaint portals or intake hotlines
- Assist organizations: legal aid, advocacy groups, attorneys
- Validation that what is happening to him may be illegal retaliation

**What a successful visit looks like:**
James finds the reporting procedure for his situation, identifies the
responsible agency, confirms the filing deadline, and has a direct link
to start the process or find legal help — without having to navigate
through content written for someone still deciding whether to come forward.

**Design implications:**
- Procedure records must be actionable: agency name, contact, deadline, link
- Filing time limits must be displayed prominently and clearly labeled —
  not buried in a summary paragraph
- Assist organizations should surface early on procedure and agency pages
- "What to do if you're experiencing retaliation" must be a navigable
  entry point at the site level, not buried inside a jurisdiction summary
- James and Maya need different entry paths; the site must serve both
  without forcing either through the other's flow

---

## Persona 3 — The Researcher or Observer

**Name:** Daniel

**Who he is:**
A journalist, policy advocate, law student, paralegal, or engaged citizen
researching whistleblower protections across multiple jurisdictions or at
the federal level. He may be writing an article, preparing testimony,
comparing state laws, or doing background research on a specific statute.
He is not in crisis — he has time, and he is comfortable with legal
language.

**How he arrives:**
Direct search for specific legal information. Something like "False Claims
Act qui tam provisions" or "state whistleblower laws comparison." He may
arrive via a citation or link from another legal resource. He knows what
he is looking for before he arrives.

**Mental state:**
Purposeful and methodical. Has a specific question or gap he is trying to
fill. Values precision and sourcing above all else. Comfortable reading
legal language but appreciates clear organization. Will evaluate the site's
credibility before relying on or attributing anything from it.

**What he needs:**
- Accurate, well-cited legal information he can rely on and attribute
- Clear indication of when information was last reviewed and by whom
- Easy navigation between jurisdictions, statutes, and citations
- Access to primary source links (govinfo.gov, state legislature sites)
- Enough editorial transparency to evaluate the reliability of the content

**What a successful visit looks like:**
Daniel finds the specific statute, procedure, or jurisdiction data he was
looking for, confirms the primary source citation, and can use or attribute
the information with confidence. The site gave him what a competent
paralegal research assistant would have found.

**Design implications:**
- Citation records must surface source information prominently
- Last-reviewed dates and review status badges are functional, not decorative
- Source links must be visibly labeled and open without disrupting reading flow
- A jurisdiction comparison capability — not yet built — would serve this
  persona directly and is worth prioritizing in the product roadmap

---

## The Ordering Is Intentional

The persona order reflects both priority and urgency, not importance.
Daniel's needs are not less valid than Maya's or James's — but he has time,
resources, and search skills that Maya and James do not. He will find what
he needs on this site even if the design is imperfect. Maya and James may
not.

Persona 1 over Persona 2: Maya has not yet acted. James has. The cost of
Maya making a poorly-informed decision to come forward — or not come
forward — is as high as any consequence James faces. She gets priority
because she is more vulnerable to confusion and more likely to leave.

Persona 2 over Persona 3: James is in active legal jeopardy with hard
deadlines. Every design choice that makes his path harder has a measurable
real-world cost. Daniel can work around an imperfect site. James may not
get a second chance.

---

## The Archive vs. Resource Tension

These three personas expose a real tension in how pages should be
structured.

The archive perspective organizes by jurisdiction, then by content type
(summary, statutes, procedures, agencies, resources). This is correct for
the data model and serves Daniel well.

The resource perspective organizes by situation:
- "I want to report something — am I protected?"
- "I've already reported — what are my rights and what do I do next?"
- "I need legal help right now"

The platform must serve both. Jurisdiction pages are the right unit for
the archive layer. But the site also needs situation-based entry points
that route users to the right jurisdiction and content type for their
specific need. This does not require changing the data model — it requires
thoughtful navigation, introductory content, and page structure.

---

## Out of Scope

This site is not designed for:

- Licensed attorneys doing formal legal research (they have Westlaw and Lexis)
- Employers seeking compliance guidance
- Government agencies

These audiences are not excluded, but they are not the primary design
target. If a backend for legal professionals is added later, it should be
a separate layer with its own design considerations — not a modification
of the public-facing resource.
