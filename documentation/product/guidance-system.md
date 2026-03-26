# Guidance System

## What This Document Is

The guidance system is the user-facing design layer — how legal
information is organized, presented, and connected so that the three
primary users (Maya, James, Daniel) can each find what they need
without having to understand how the data model works.

This document covers the two core user questions, the navigation
model, how the data model serves guidance without being exposed to
the user, and where the current implementation leaves room for Phase 2.

---

## The Two Questions

The platform is designed to answer two questions for real people in
real situations. These questions are kept separate by design.

### "Who can help me?"

Answered through the assist organization directory. A user who does
not yet know what to do, or who needs support before taking action,
needs to find an organization that can help them — a legal aid clinic,
an advocacy group, an attorney who handles whistleblower cases.

This question is answered at the jurisdiction level (assist orgs
scoped to the user's state) and at the national level (nationwide
organizations that serve all jurisdictions). The directory is filterable
by employment sector, services offered, cost model, and case stage.

The `[ws_assist_org_directory]` shortcode powers the directory page.
The assembly layer surfaces jurisdiction-scoped assist organizations
on each jurisdiction page automatically.

### "What do I do next?"

Answered through agency filing procedures. A user who has already
reported wrongdoing, or who has decided to report and needs to know
the mechanics, needs to find the right agency and understand the
process — specifically the deadline, the entry point, and what happens
after filing.

This question is answered on individual agency pages via the
`render-agency.php` assembler. Procedures are grouped by type
(disclosure first, retaliation second, both last) so that a user in
a specific situation finds the relevant procedures without reading
through procedures designed for a different situation.

### Why They Are Separate

A user considering whether to come forward and a user facing active
retaliation have different urgencies, different needs, and different
decision-making contexts. Presenting both "who can help" and "what do
I do next" on the same page creates noise at the moment when clarity
matters most.

The data model supports both questions from the same underlying records.
The navigation and page structure keep the answers in separate places.

---

## Navigation Model

The platform supports two entry paths simultaneously:

### Archive Entry (by jurisdiction)

Organized by jurisdiction, then by content type: summary, statutes,
citations, interpretations, agencies, assist organizations, legal
updates. This is the correct model for the data layer and serves
Daniel well — he knows what he is looking for and navigates directly.

The jurisdiction index (`[ws_jurisdiction_index]`) is the hub.
Type filter tabs (All, States, Federal, Territories, District)
allow rapid narrowing. The alphabetical grid provides direct access.

### Situation Entry (by user state)

Organized by where the user is in their situation:
- "I want to report something — am I protected?"
- "I've already reported — what are my rights and what do I do next?"
- "I need legal help right now"

This entry path does not require changing the data model. It requires
navigation elements, introductory content, and page structure that
route users to the right jurisdiction and content type for their need.

The Phase 2 filtered render path (`ws_render_jx_filtered()`) is the
dispatch hook point for this. It is currently dormant — the curated
render path handles all jurisdiction page requests. Phase 2 will
activate this path for situation-based entry.

---

## The Jurisdiction Page Structure

The jurisdiction page is the primary content surface of the platform.
It is assembled automatically by `render-jurisdiction.php` — no manual
shortcode placement is required in jurisdiction posts.

**Assembled sections (in order, conditional on published data):**

1. **Header** — jurisdiction flag, name, government offices panel
   (portal, executive, whistleblower authority, legislature)
2. **Not Legal Advice notice**
3. **Plain Language Summary** — the first and most important section;
   answers "am I protected?" for Maya
4. **Statutes** — curated (attach-flagged) statute blocks with
   local/federal two-group split; statute of limitations,
   enforcement details, burden of proof, remedies
5. **Citations** — curated case law with local/federal split
6. **Interpretations** — court-specific statutory interpretations
7. **Agencies** — agencies with whistleblower jurisdiction; links
   to agency pages with full procedure detail
8. **Assist Organizations** — organizations scoped to this jurisdiction
   plus nationwide organizations
9. **Legal Updates** — recent legal developments affecting this
   jurisdiction

Each section is conditionally rendered — if a dataset has no published
records, the section is omitted. If no sections render at all, a
placeholder notice is shown. The page never displays empty section
headings.

Section anchors (`id="ws-statutes"`, `id="ws-citations"`,
`id="ws-interpretations"`) allow the reference page back-link to return
the user to the correct position after viewing external sources.

---

## The Agency Page Structure

Agency pages are assembled by `render-agency.php`. The WordPress
`post_content` holds an editorial overview of the agency (written
by an editor). The assembler appends the structured procedures section
below that content automatically.

**Procedure card contents:**
- Intake-only warning (if the agency only receives and refers)
- Identity policy (anonymous / confidential / identified)
- Filing deadline with the event that starts the clock
- Entry point (online / mail / phone / in-person / multiple)
- Prerequisites notice (if exhaustion or other conditions required)
- Plain-language step-by-step walkthrough
- Mutual exclusivity note (remedies or procedures the filer may forfeit)
- CTA buttons: intake form URL + direct phone
- Last verified date

The deadline and identity policy are the highest-priority fields on
the card. A user who has experienced retaliation needs to know
immediately how long they have and whether they can file anonymously.

---

## The Curated Summary vs. Full Record

Two distinct views exist for statutes, citations, and interpretations:

**Curated summary view** (jurisdiction page): Shows only
attach-flagged records, ordered by `ws_display_order`. Typically
three to five records per section. This is the editorially selected
set — the most important records for most users most of the time.

**Full record view** (future): All records for the jurisdiction,
accessible via filtering or direct taxonomy query. Not yet built —
the data model fully supports it. The curated view is not a
substitute for the full record view; it is a starting point.

The attach-flag is an editorial curation tool. It does not control
visibility or access. Unflagged records exist and are queryable — they
simply do not appear on the curated summary page.

---

## The Reference Page

External source links (statutes, court opinions, agency guidance) are
surfaced through a dedicated reference page rather than inline within
jurisdiction content. This serves two purposes:

1. **User safety**: The two-part disclaimer (not legal advice +
   external link warning) appears before the user follows an external
   link, rather than appearing as a footnote after they have already
   made the decision to click.

2. **Navigation integrity**: The reference page back-link returns the
   user to their exact position on the jurisdiction page via the
   `?section=` parameter and the `#ws-{section}` anchor. Users are
   not lost when they return from an external source.

A single WordPress page with `[ws_reference_page]` serves all records.
The specific record is resolved from the `?post_id=` URL parameter.

---

## Plain Language as a Trust Signal

The platform's editorial investment in plain language is not just a
usability choice — it is a trust signal to all three personas.

For Maya: plain language signals that the site is for people like her,
not for lawyers. The absence of jargon signals safety.

For James: plain language in the procedure walkthrough signals that
the platform understands the urgency of his situation and is not
wasting his time with background he already knows.

For Daniel: the "Editor Reviewed" trust badge signals that a human
editor has verified the plain language content for accuracy. The
presence of primary source citations below the plain language signals
that the clarity did not come at the cost of rigor.

The plain English overlay system (via `acf-plain-english-fields.php`)
and the trust badge rendering (via `ws_render_plain_english_reviewed_badge()`)
exist to make this trust signal systematic and consistent across all
content types.

---

## Phase 2: Situation-Based Entry

The current implementation handles all jurisdiction page requests
through the curated render path. Phase 2 will activate the filtered
render path for situation-based entry — a user arriving via "I want
to report something" would see a pre-filtered view that emphasizes
summary, relevant statutes, and assist organizations. A user arriving
via "I have been retaliated against" would see a view that emphasizes
procedures, deadlines, and legal help.

The dispatch hook point (`ws_render_jx_filtered()`) and the
`$filter_context` parameter are already in place in
`render-jurisdiction.php`. The taxonomy infrastructure (`ws_case_stage`,
`ws_disclosure_type`, `ws_process_type`) is already seeded and in use
on assist organizations and statutes. Phase 2 is a rendering and
navigation problem, not a data model problem.
