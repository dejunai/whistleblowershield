# Research and Transparency

## Purpose

This document describes how legal information is sourced, verified,
and kept current on WhistleblowerShield. It also describes the
transparency commitments the platform makes to its users — what they
can know about where information came from, when it was last checked,
and what its limitations are.

Research and transparency are not soft editorial values here. The
platform's users make real decisions based on what they read. A wrong
deadline, a stale procedure, or a mischaracterized statute has direct
real-world consequences for people who are already in a difficult
situation.

---

## Source Hierarchy

All legal information on the platform is grounded in sources, and
sources are ranked by reliability:

**Tier 1 — Primary legal sources** (preferred)
- Statutes: govinfo.gov, state legislature websites, official code
  publications
- Regulations: the Code of Federal Regulations (eCFR), state
  administrative codes
- Court decisions: official court websites, PACER, state court portals
- Agency guidance: official agency websites (not summaries of them)

**Tier 2 — Authoritative secondary sources** (acceptable for context)
- Law review articles from peer-reviewed journals
- Government accountability reports (GAO, OIG, CRS)
- Bar association publications
- Academic legal databases

**Tier 3 — General secondary sources** (context only, never sole basis)
- News coverage
- Advocacy organization guides
- Wikipedia or general reference sites

Tier 3 sources may appear as `ws-reference` records for context and
navigation. They must never be the sole basis for any legal claim
in the platform's primary content. If a claim can only be supported
by a Tier 3 source, it should not appear in the primary content until
a Tier 1 or Tier 2 source is found.

---

## The Source Verify Workflow

Every content record carries source verification fields managed by the
`acf-source-verify.php` field group (see `ws-core-data-layer.md` for
the complete field list). These fields are the operational expression
of the platform's transparency commitment.

### Source Method

`ws_auto_source_method` records how a record entered the system. It
is set once at creation and never changed. The five values:

| Value | Meaning |
|---|---|
| `matrix_seed` | Created by a matrix seeder at install; canonical reference data |
| `human_created` | Created directly by a human editor |
| `ai_assisted` | Created or substantially drafted with AI assistance |
| `bulk_import` | Created via a structured import process |
| `feed_import` | Created via the Inoreader legislative feed monitor |

`jx-summary` records always carry `human_created` regardless of how
they were initiated. Summaries are the plain-language editorial voice
of the platform — AI assistance in drafting is acceptable but the
method stamp must reflect human authorship.

### Source Name

`ws_auto_source_name` identifies the specific tool or process within
the method. Matrix-seeded and human-created records carry `'Direct'`
— the source and the method are the same person or system. AI-assisted
records should carry the specific tool name (e.g. `'Claude AI'`).
Feed-imported records carry the feed name.

`ws_verification_status` cannot be set to `verified` if `source_name`
is empty. This is enforced server-side — the UI hides the status field
until a source name is present.

### Verification Status

`ws_verification_status` has four values:

| Value | Meaning |
|---|---|
| `unverified` | Default at creation; content has not been reviewed against primary sources |
| `needs_review` | Previously verified but flagged for re-review (stale, changed, or uncertain) |
| `verified` | A human editor has reviewed the content against its primary source |
| `outdated` | Known to be out of date; content needs update before it can be verified |

Editors at Author level or above may set status to `verified`.
Only administrators may revert from `verified` to `unverified`. This
asymmetry is intentional: reverting a verification requires
administrative judgment about whether the content has materially changed.

### Needs Review Flag

`ws_needs_review` is a simple boolean flag that any administrator can
set on any record at any time. When set, the record is surfaced in
admin columns for editorial attention. The flag does not affect
public-facing display directly, but content that needs review should
not be considered current.

This flag is the primary tool for flagging staleness that is discovered
outside the automated systems — for example, when an editor reads a
news article about a statute amendment and needs to mark the relevant
records for follow-up before making the actual updates.

---

## Automated Staleness Detection

Three automated systems surface records that may have gone stale
without editorial action:

**URL Health Monitor** (`admin-url-monitor.php`)
Checks URL fields across jurisdiction, agency, and assist-org CPTs
every 10 days. Procedure intake URLs are checked every 3 days.
Failures (4xx/5xx) and warnings (redirects) surface in a dashboard
widget and trigger email notifications to administrators.

A broken URL is not the same as stale content — but a broken link to
the authoritative source is a strong signal that the record needs
review. A statute URL that now 404s may mean the statute was moved,
amended, or repealed.

**Matrix Divergence Watch** (`admin-matrix-watch.php`)
Flags matrix-seeded records that have been manually edited after
install. Matrix records carry `ws_matrix_source` post meta. When such
a record is saved, `ws_matrix_divergence` is set and the editor is
recorded. The dashboard widget surfaces all unresolved divergences.

Matrix-seeded records are the highest-staleness-risk category because
they have no natural editorial touchpoint after the seeder runs. An
editor editing a seeded record is a signal worth investigating —
it may mean the canonical data has changed.

**Jurisdiction Dashboard** (`jurisdiction-dashboard.php`)
A completion tracker for all 57 jurisdictions showing the status of
every content CPT per jurisdiction. The primary tool for identifying
jurisdictions with missing or incomplete content.

---

## Transparency Commitments to Users

The platform makes four specific transparency commitments that are
visible to users:

### Last Reviewed Date

Every content type that can go stale carries a `last_reviewed` date
field. These dates are displayed publicly on the relevant pages.
A last-reviewed date is the date a human editor verified the record
against its primary source — not the date the record was last saved.

Editors must update this field when they verify a record, not when
they make editorial changes. The date is meaningful to users — Daniel
in particular uses it to evaluate whether information can be attributed.

### Review Status Badge

The "Editor Reviewed" trust badge (`ws_render_plain_english_reviewed_badge()`)
appears on records whose plain English content has been reviewed for
accuracy. It shows the reviewer's name on hover. It does not appear
on unreviewed content, and it never appears on records where the
review toggle has not been explicitly set.

The badge is a signal, not a guarantee. It means a human editor has
read the plain language content and judged it accurate as of the review
date. It does not mean the underlying law has not changed since.

### Primary Source Links

Every statute, citation, and interpretation record provides a direct
link to the primary source document. These links open via the reference
page pattern, which includes an explicit external-link disclaimer.
The link is labeled so users know it leads to the official source.

If a primary source link is not available, the record should be marked
accordingly rather than omitting the field silently. An absent link is
information — it tells Daniel that the source could not be located and
should be independently verified.

### The Not Legal Advice Disclaimer

The `[ws_nla_disclaimer_notice]` shortcode appears on every jurisdiction
page. Its text is managed centrally in `shortcodes-general.php` — a
change to the copy propagates to all jurisdiction pages automatically.

The disclaimer is not presented as a barrier or a legal hedge. It is
presented as honest information about what the platform provides and
what it does not. Content should be written so the disclaimer is
consistent with the content, not in tension with it.

---

## Limitations the Platform Acknowledges

The platform does not claim to be exhaustive. The following limitations
are inherent and should be communicated clearly wherever they are
relevant:

**Coverage is not complete.** Not every statute in every jurisdiction
is documented. The attach-flag curation model means some statutes that
exist are not surfaced on the summary page. This is a feature of the
editorial model, not a concealment — but users should understand that
the presence of certain records does not imply the absence of others.

**Legal information goes stale.** Statutes are amended. Courts issue
new decisions. Agencies change their procedures. The platform's content
reflects the state of the law as understood when each record was last
verified. Verification dates are provided precisely so users can assess
currency.

**Jurisdictional variation is real.** The same legal concept —
retaliation protection, for example — can mean materially different
things in different jurisdictions. Cross-jurisdictional comparison is
useful but requires care. The platform's structure (one record per
jurisdiction per statute) is designed to prevent conflation across
jurisdictions.

**This is not legal advice.** The platform provides legal information.
It does not provide advice about what a specific person should do in
their specific situation. Users with active legal situations involving
significant consequences should consult an attorney.

---

## AI Assistance and Transparency

The platform is developed and maintained with AI assistance. This
applies to both the codebase and the content. The source method
system records which content records were AI-assisted.

AI assistance in content creation is acceptable under the following
conditions:

1. The AI-generated content is reviewed and verified by a human editor
   against primary sources before the record is marked `verified`.
2. The `ws_auto_source_method` field correctly reflects
   `ai_assisted` for records where AI substantially drafted the
   content.
3. The plain English review toggle is set only after a human has
   reviewed the plain language content for accuracy, regardless of
   how it was drafted.

AI assistance does not change the accuracy standard. A record verified
by a human editor is held to the same standard whether the first draft
was written by an AI or a person.

---

## The JSON Ingest Schema

AI-assisted bulk ingest of statute data uses a structured JSON schema.
Ingest files must include a header block specifying `source_method`
and `source_name` so the import tooling can stamp every record in the
batch consistently. The schema includes a `batch_completed` sentinel
field as the last key in the meta block — the ingest tool aborts if
this key is absent or empty, preventing partial or interrupted batches
from silently producing incomplete records.

The specific schema format is maintained in the ingest tooling
documentation, not here. This document records the policy: every
batch ingest must be attributable to a specific source and method,
and the completion sentinel must be present before any records are
committed.
