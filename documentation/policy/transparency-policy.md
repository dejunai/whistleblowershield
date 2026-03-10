# Transparency Policy

## Overview

WhistleblowerShield is committed to providing transparent, honest, and
verifiable legal information to the public.

Users — including people in vulnerable situations who are deciding whether
to come forward — need to be able to trust what they read here.
That trust must be earned through genuine transparency, not just stated.

This policy defines what transparency means in practice for this platform.

---

## What We Are

WhistleblowerShield is an independent, nonpartisan educational reference
for U.S. whistleblower protection laws.

It is maintained by an amateur legal researcher and independent developer.
Contributors are not licensed attorneys unless specifically identified.

The platform is a structured legal archive presented as an accessible
public resource. It is not a law firm, not a legal services provider,
and not a government agency.

---

## What We Are Not

This platform does not:

- Provide legal advice or represent individuals
- Accept reports of misconduct or act as a whistleblower intake channel
- Guarantee the accuracy or completeness of any legal information
- Endorse specific attorneys, law firms, or legal services

---

## The Disclaimer Notice

Every jurisdiction page displays the standard disclaimer notice via
the [ws_disclaimer_notice] shortcode.

The notice informs users that the platform is an informational resource
and that they should consult a qualified legal professional regarding
their specific situation.

The notice is displayed consistently across all jurisdiction pages
and is not negotiable editorial content. Its copy is managed centrally
in shortcodes.php to ensure consistency.

The disclaimer is intentionally written in a calm, informational tone
rather than as an aggressive legal warning. Users in difficult situations
deserve a notice that informs without alarming — they can read the
site's limitations without being made to feel that the information
they find here is worthless.

---

## Source Transparency

### Every factual legal claim must be traceable

Legal statements in summary content must reference a verifiable source.
Preferred sources are Tier 1 (primary legal authorities) as defined
in source-verification-policy.md.

### Sources are listed publicly

The ws_summary_sources field on each jx-summary post is displayed
publicly in the summary footer. Users can see exactly where the
information came from and verify it themselves.

### Official source links are prioritized

All statute citations and procedure guidance links should point to
official government sources: govinfo.gov, congress.gov,
federalregister.gov, and official state legislature websites.
Secondary sources are used only when primary sources are unavailable.

---

## Editorial Transparency

### Review status is shown publicly

Each jurisdiction page displays review status badges:

- **Human Reviewed** — a human editor has reviewed the content
  against the citation, structure, and reading level standards
  in content-standards.md
- **Pending Human Review** — the content has not yet completed review
- **Legally Reviewed** — a licensed attorney has reviewed the content
  (reviewer name is displayed)
- **Pending Legal Review** — attorney review has not yet occurred

These badges reflect actual editorial status — they are not decorative.
A Human Reviewed badge is only set when review has genuinely occurred.

### Last Reviewed dates are shown publicly

The ws_last_reviewed date on each jx-summary post is displayed publicly.
Users can see how recently the content was checked against current law.

This date must be updated whenever substantive revisions are made.
Displaying a stale last-reviewed date would misrepresent the currency
of the information — which is a transparency failure.

### Audit trail is maintained internally

All content changes to ws-core CPTs are logged internally via the
ws-core audit trail (_ws_last_edited_by, _ws_edit_history).
This log is not publicly displayed but provides internal accountability
for every edit made to legal content.

---

## Correction Policy

When errors are identified:

- Corrections are made promptly
- The ws_last_reviewed date is updated
- The ws_human_reviewed flag is reset to Pending if the correction
  is substantive (the content should be re-reviewed before the badge
  is restored)
- A ws-legal-update entry is created if the correction reflects a
  change in law rather than a platform error

The corrections contact email (corrections@whistleblowershield.org)
is the designated channel for reporting potential errors.

This address must not appear on the same line or in the same sentence
as the general contact address (admin@whistleblowershield.org) in any
published template or shortcode output.

---

## Limitations We Acknowledge

### We are not attorneys

Content is researched and written by an amateur legal researcher.
Legal review by licensed attorneys is pursued for high-priority content
but is not guaranteed for all published material.

### Law changes

Whistleblower statutes, regulations, and agency rules change.
We maintain last-reviewed dates and legal update records to signal
the currency of information, but we cannot guarantee real-time accuracy.
Users should verify current law through official sources before acting.

### Coverage is not complete

The platform is actively being built. Not all jurisdictions are fully
documented at any given time. Absence of information about a jurisdiction
does not mean that jurisdiction has no whistleblower protections.

### We make mistakes

When we do, we correct them transparently. The corrections policy above
describes how.

---

## Our Commitment

We will not publish content we know to be incorrect.
We will correct errors when they are identified.
We will show users exactly where our information comes from.
We will be honest about who we are and what this platform is.

We believe that ordinary people facing difficult situations deserve
accurate, honest legal information — presented clearly, sourced
transparently, and free from commercial motive.

That is what this platform is trying to provide.
