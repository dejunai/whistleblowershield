# Proposal Notes — Editorial Policy Revision + California Summary

**Proposals:** editorial-policy-revision.md, california_summary_revised.html
**Target pages:** /editorial-policy/, /jurisdiction/california/
**Status:** Ready for implementation
**Prepared:** 2026-03-10

---

## Editorial Policy — What Changed and Why

### Change 1 — Core Contradiction Fixed

The live editorial policy prohibited characterizing protections as "strong
or weak." content-standards.md explicitly requires summaries to state
clearly whether a jurisdiction has meaningful protections. These were in
direct conflict.

**Root cause:** The policy conflated two distinct types of neutrality:
political/partisan neutrality (correct, preserved) and descriptive
accuracy about legal coverage (not political, and necessary for users).

**Fix:** New paragraph in Nonpartisan and Neutral Language Standards
separates the two. Factual descriptions of relative coverage and strength
are permitted and expected. Political and partisan framing remains
prohibited.

### Change 2 — "Who Maintains This Site" Section Added

Live site had inconsistent self-description across pages. The editorial@
contact address is intentional — a role address designed to support a
future contributing team. This is legitimate and not misleading.

**Fix:** New section states honestly that the site is currently maintained
by an independent amateur legal researcher and developer, that the site
is designed to grow, and that the editorial role structure is intended
to support a contributing team. Resolves the inconsistency without
overstating what currently exists.

### Change 3 — Writing Standards Section Added

Short new section cross-references the 9th–10th grade reading level
target and summary structure from content-standards.md.

### Change 4 — AI Policy Tightened

Language aligned with transparency-policy.md in documentation.
No substantive change.

---

## California Summary — Tooltip & Parenthetical Changes

### Tooltips kept (unchanged)
- `retaliation` — correct and useful
- `contributing factor` — best tooltip on the page
- `personal grievances` — well-targeted, good content

### Tooltips rewritten
- `reasonable cause` — was using legal vocabulary ("subjective,"
  "objective") to explain a legal term. Rewritten to answer Maya's
  actual fear: you don't have to be proven right.
- `person with authority` — was formatted as label-style pairs
  (Internal: / External:). Rewritten as plain prose.

### Tooltips added
- `independent contractors` — was italics-only with no explanation.
  Coverage of contractors is a real threshold question for many users
  and broader than most states. Tooltip added.
- `statute of limitations` — added in the Administrative Filing step.
  Filing deadlines are critical for James (retaliation persona).

### Tooltips removed — converted to inline treatment
- `state or federal statute` — not a confusing term. Examples
  converted to a parenthetical in the bullet text.
- `news media` — tooltip was hiding actionable guidance users need
  regardless of whether they hover. Guidance moved into body text.
- `disclosures that are known to be false` — tooltip restated the
  surrounding text. Parenthetical moved inline.

### Parentheticals added
- Statute examples: `(such as laws against discrimination, wage theft,
  fraud, or environmental violations)`
- DLSE acronym: `(Division of Labor Standards Enforcement, DLSE)`
- Compensatory damages: `(money to cover your actual losses)`

### Footnote return arrows
- `↩` was rendering at full page width — Unicode character inheriting
  theme font size, not constrained by `<small>`.
- **Short-term fix (applied):** Replaced with `[return]` plain text.
- **Permanent fix (pending):** Add CSS rule to ws-core-front.css —
  see CSS Changes section below.

---

## CSS Changes — Pending Implementation

### File: wp-content/plugins/ws-core/ws-core-front.css

Add the following rule. This constrains footnote return links to the
surrounding `<small>` element's font size and prevents the ↩ character
(or any link text) from inheriting a larger theme size.

Once this rule is deployed, the `[return]` placeholder in the California
summary can be swapped back to ↩ if preferred.

```css
/* Footnote return links — constrain to small text baseline */
.ws-case-law small a {
    font-size: inherit;
    line-height: inherit;
    vertical-align: baseline;
}
```

**Where to add it:** After existing `.ws-case-law` rules if any exist,
otherwise at the end of the file in a comment-labeled section:

```css
/* ── Case Law Footnotes ── */
.ws-case-law small a {
    font-size: inherit;
    line-height: inherit;
    vertical-align: baseline;
}
```

**To apply in WordPress UI:**
Appearance is not the right place for plugin CSS. This file lives at:
wp-content/plugins/ws-core/ws-core-front.css

Edit it directly via SFTP/SSH or through the plugin editor:
WordPress Admin → Plugins → Plugin Editor → select ws-core →
select ws-core-front.css → add rule → Save.

Note: The plugin editor may be disabled on hardened installs.
SFTP is the safer method.

---

## Implementation Order

1. Apply editorial-policy-revision.md to /editorial-policy/ in WordPress
2. Apply california_summary_revised.html to /jurisdiction/california/
3. Add .ws-case-law small a CSS rule to ws-core-front.css
4. After CSS is confirmed live, optionally replace [return] with ↩
   in the California summary
