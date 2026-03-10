# User Personas

## Purpose

This document defines the primary audiences for WhistleblowerShield.org.

Every frontend design decision — navigation structure, page layout, reading level,
what information appears first — should be tested against these personas.

The platform has a dual nature: it is a rigorous legal archive on the backend,
and an accessible public resource on the frontend. These personas define who
the frontend is built for.

---

## Persona 1 — The Person in Crisis

**Name:** Maya

**Who she is:**
An employee at a mid-size company. She recently witnessed what she believes
is financial fraud. She has not told anyone yet. She is scared — of losing
her job, of being wrong, of not being believed, of legal consequences
she doesn't fully understand.

**How she arrives:**
Google search. Something like "am I protected if I report my boss" or
"whistleblower laws in California." She may be searching from her phone,
possibly not from her work computer.

**Mental state:**
Anxious. Overwhelmed. Low trust in institutions. Short attention span
because of stress. Skeptical of anything that feels like a law firm
trying to get her business.

**What she needs from this site:**
- Fast, plain-language answer to: "Does the law protect someone like me?"
- Enough information to decide whether it is safe to move forward
- A clear sense of what a "whistleblower" actually is and whether she qualifies
- Links to official government resources or legitimate help organizations
- To feel like the site is on her side, not just presenting legal data at her

**What a successful visit looks like:**
Maya leaves understanding what protections exist in her state, has a sense
of what her next step could be, and feels less alone. She does not need
to have read everything — she needs to have found something that helps.

**Design implications:**
- Jurisdiction pages should lead with protections in plain language
- The most important question — "am I protected?" — should be answerable
  without reading a full legal summary
- Navigation should support "I don't know what I'm looking for" entry points
- Trust signals matter: citation transparency, last-reviewed dates, clear
  editorial standards make the site feel reliable without feeling like a firm

---

## Persona 2 — The Informed Researcher

**Name:** Daniel

**Who he is:**
A journalist, policy advocate, law student, or engaged citizen researching
whistleblower protections across multiple states or at the federal level.
He may be writing an article, preparing testimony, or doing background
research on a specific statute.

**How he arrives:**
Direct search for specific legal information. Something like
"False Claims Act qui tam provisions" or "state whistleblower laws comparison."
May arrive via a link from another resource or citation.

**Mental state:**
Purposeful. Has a specific question or gap he is trying to fill.
Comfortable reading legal language but appreciates clear organization.
Values precision and sourcing above all.

**What he needs from this site:**
- Accurate, well-cited legal information he can rely on and attribute
- Clear indication of when information was last reviewed
- Easy navigation between jurisdictions and statutes
- Access to primary source links (govinfo.gov, state legislature sites)
- Enough editorial transparency to evaluate the reliability of the content

**What a successful visit looks like:**
Daniel finds the specific statute, procedure, or jurisdiction comparison
he was looking for, confirms the primary source citation, and can
use or attribute the information with confidence.

**Design implications:**
- Statute and procedure records should surface citation information prominently
- Last-reviewed dates and review status badges are functional, not decorative
- Source links should be visibly labeled and open-able without disrupting reading
- A jurisdiction comparison capability (future) would serve this persona directly

---

## Persona 3 — The Person Facing Retaliation

**Name:** James

**Who he is:**
Someone who has already reported misconduct — internally or to a government
agency — and is now experiencing or fearing retaliation. He may have been
demoted, threatened, or fired. He is looking for specific help: what are
his rights, who does he contact, how long does he have.

**How he arrives:**
Urgent search. "Whistleblower retaliation rights," "what to do if fired
for whistleblowing," "OSHA complaint deadline." May be searching under
significant time pressure.

**Mental state:**
Distressed. More urgent than Maya because something has already happened.
Needs actionable information, not background context. May already know
some basics about whistleblower law — he needs the next step.

**What he needs from this site:**
- Reporting procedures: who to contact, how to file, what the deadlines are
- Time limits — this is often the most critical piece of information
- Links directly to agency complaint portals or hotlines
- Resources: legal aid, advocacy organizations
- Validation that what is happening to him may be illegal

**What a successful visit looks like:**
James finds the reporting procedure for his jurisdiction, identifies
the responsible agency, confirms the filing deadline, and has a direct
link to start the process or find legal help.

**Design implications:**
- Procedures pages must be actionable: agency name, contact, deadline, link
- Filing time limits should be displayed prominently and clearly labeled
- Resources pages should lead with organizations that can help immediately
- "What to do if you're being retaliated against" should be a navigable
  entry point, not buried inside a jurisdiction summary

---

## Design Tension: Archive vs. Resource

These three personas expose a real tension in how pages should be structured.

The archive perspective organizes by jurisdiction, then by content type
(summary, statutes, procedures, resources). This is correct for the data model.

The resource perspective organizes by situation:
- "I want to report something — am I protected?"
- "I've already reported — what are my rights?"
- "I need help right now"

The platform should serve both. Jurisdiction pages are the right unit for
the archive layer. But the site also needs situation-based entry points
that route users to the right jurisdiction and content type for their need.

This does not require changing the data model. It requires thoughtful
navigation, introductory content, and page structure on the frontend.

---

## What These Personas Are Not

This site is not initially designed for:

- Licensed attorneys doing formal legal research (they have Westlaw, Lexis)
- Employers seeking compliance guidance
- Government agencies

These audiences are not excluded, but they are not the primary design target.
If a backend for legal professionals is added later, it should be a separate
layer with its own design considerations — not a modification of the
public-facing resource.
