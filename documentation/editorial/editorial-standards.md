# Content Standards

## Purpose

This document defines how legal information should be written for
WhistleblowerShield.org's public-facing content.

The platform is a rigorous legal archive. It is also a public resource
intended to be useful to ordinary people — including people who are
scared, overwhelmed, or facing an urgent situation.

These two goals are not in conflict, but they require intentional
writing standards to achieve both simultaneously.

---

## The Core Writing Challenge

Legal accuracy requires precision. Plain language requires simplicity.
The goal is not to sacrifice one for the other — it is to write content
that is legally accurate AND readable by someone without legal training.

This is harder than writing for either audience alone. It requires:
- Using plain words where plain words are accurate
- Explaining legal terms when they cannot be avoided
- Leading with what matters most to the reader
- Citing sources without burying the reader in citations

---

## Reading Level Target

All public-facing summary content should be written at approximately
a **9th to 10th grade reading level**.

This does not mean dumbing content down. It means:
- Using shorter sentences
- Preferring common words over legal jargon
- Breaking complex ideas into steps or separate paragraphs
- Avoiding passive voice where possible

Legal citations, statute names, and agency names are exceptions —
these must appear in their correct legal form regardless of complexity.

Tools like the Hemingway Editor (hemingwayapp.com) can be used to
evaluate reading level during drafting.

---

## Summary Structure

Every jurisdiction summary (ws_summary field on jx-summary posts)
should follow this structure:

### 1. Opening Statement (1–2 sentences)
Answer the most important question first:
"Does [jurisdiction] protect whistleblowers?"

State this clearly. A person who reads only the first two sentences
should know whether this jurisdiction has meaningful whistleblower
protections or not. Describe what the law specifically does — who it
covers and in what circumstances — rather than characterizing it
comparatively. Let the facts speak.

Example of an effective opening:
"California protects both private and public sector employees —
including independent contractors — under multiple state statutes,
with anti-retaliation provisions covering disclosures to government
agencies, law enforcement, and internal supervisors."

Example of an ineffective opening:
"California Labor Code Section 1102.5 was enacted in 1984 and has
been amended several times..."

### 2. Who Is Covered (1–3 paragraphs)
Explain who qualifies for protection. Use concrete examples where helpful.
Address common situations: employees, contractors, government workers.
Note significant exclusions if they exist.

### 3. What Is Protected (1–3 paragraphs)
Explain what kinds of disclosures are protected. What can someone
report and still be protected? Use plain language.
Reference specific statutes with their citations.

### 4. Protection Against Retaliation (1–2 paragraphs)
Explain what the law prohibits employers from doing.
This is often the most important section for users in crisis.
Be specific: termination, demotion, harassment, threats.

### 5. How to Report / What to Do (1–2 paragraphs)
Point toward the procedures. Who do you report to?
Is there a government agency? An internal process?
Link to or reference the jurisdiction's jx-procedures records.

### 6. Notable Statutes
Reference key statutes with correct legal citations.
These should link to or align with jx-statutes records.

---

## Plain Language Rules

### Use plain words where accurate
| Avoid | Prefer |
|---|---|
| "pursuant to" | "under" |
| "notwithstanding" | "even if" or "regardless of" |
| "hereinafter" | (just use the name) |
| "aforementioned" | (refer to it directly) |
| "effectuate" | "carry out" |
| "in the event that" | "if" |

### Explain legal terms on first use
The first time a legal term appears in a summary, define it briefly.

Example:
"A qui tam lawsuit — a type of lawsuit filed by a private individual
on behalf of the government — allows whistleblowers to share in
any financial recovery."

After the first explanation, the term can be used on its own.

### Inline definitions: tooltips and parentheticals

Two tools are available for defining legal terms within summary content.
They serve different purposes and should be chosen deliberately.

**Tooltips — the `.ws-term-highlight` class**

The `ws-term-highlight` CSS class and `data-tooltip` attribute render a
hover definition without interrupting reading flow. Use tooltips for
legal terms or phrases that a non-lawyer is genuinely unlikely to know,
where the definition itself does not need to be read to understand the
surrounding sentence.

Example usage:
```html
<span class="ws-term-highlight"
      data-tooltip="A lawsuit filed by a private citizen on behalf of the government, allowing the filer to share in any financial recovery.">
  qui tam lawsuit
</span>
```

Tooltips are a functional accessibility tool, not a design feature. Do
not use them as decoration, as a way to signal that a term is legal, or
as a substitute for writing plainly. A page dense with tooltips is a
page that needed to be rewritten.

Ask before adding a tooltip: would a reader who never hovers still
understand the sentence? If yes, the tooltip is optional context. If the
definition is something the reader actually needs to follow the sentence,
it belongs in the text — not hidden behind a hover.

**Parentheticals**

When a legal term needs a brief clarification that the reader genuinely
needs — not optional context but necessary meaning — put it in a
parenthetical inline. Parentheticals are visible to every reader on
every device without any interaction required.

Use a parenthetical when:
- the clarification is short (one phrase or clause)
- the reader needs it to understand what follows, not just to learn
  a definition
- concrete examples serve the reader better than a definition

Examples:
```
...file a complaint with the DLSE (Division of Labor Standards Enforcement)...
...compensatory damages (money to cover your actual losses)...
...a violation of a state or federal statute (such as laws against
   discrimination, wage theft, or environmental violations)...
```

**Choosing between them**

If the term is unfamiliar and the definition is optional context
→ tooltip.

If the term needs a brief clarification the reader actually needs
to follow the sentence → parenthetical.

If the term requires more than a sentence to explain, or the
explanation is important enough to stand on its own → write it
into the body text.

When in doubt, write it out. A tooltip no one hovers is invisible.
A parenthetical no one needed is a minor distraction. Neither is
as harmful as a sentence a reader cannot follow.

---

## Framing and Tone

### Write for the person, not the record
The reader is a person trying to figure out whether they are protected,
or what to do next. Write with that in mind.

Avoid writing that sounds like it is describing a legal database entry.
The summary should feel like information written for a reader,
not metadata about a jurisdiction.

### Be direct about protections
Describe what a jurisdiction's law specifically does — who it covers,
what disclosures it protects, what remedies it provides, and what it
does not cover. State these facts concretely. Do not characterize
protections as "strong" or "weak" — these are comparative judgments
that are difficult to sustain consistently across 57 jurisdictions and
can read as editorial opinion rather than factual description.
Specific facts serve the reader better than relative characterizations.

Hedging every statement for legal caution is understandable but
ultimately harmful to the user — it defeats the purpose of the resource.
The disclaimer notice handles the "not legal advice" framing.
The summary itself should be as clear and useful as possible.

### Never editorialize about specific cases or individuals
Summaries describe laws, not specific disputes. Do not reference
specific whistleblower cases in summary content except where a court
ruling has directly shaped the law's interpretation.

---

## The Disclaimer Notice

The [ws_disclaimer_notice] shortcode renders the standard
"not legal advice" notice at the top of jurisdiction pages.

### What it is for
It is a legally necessary transparency statement. It tells the reader
what the site is and what it is not.

### What it is not for
It is not meant to be the reader's first impression of the site.
It should be styled to be present and readable without being alarming.
The current styling (soft border, warm background, smaller font) is
intentional — informative without being a warning sign.

### Do not modify the disclaimer to be more aggressive
More aggressive disclaimer language ("THIS IS NOT LEGAL ADVICE.
CONSULT AN ATTORNEY.") may feel legally safer but damages the user
experience significantly, particularly for Maya (persona 1) and James
(persona 3), who arrive in a fragile mental state.

The disclaimer copy is managed centrally in shortcodes.php.
To change it site-wide, edit $notice_text in that file only.

---

## Citation Standards in Summaries

The ws_summary_sources field (Sources & Citations tab on jx-summary)
is the place for formal citation lists. In the summary body itself,
citations should be woven in naturally.

### In-text citation format
When referencing a statute in the body of a summary:
"...protected under the False Claims Act (31 U.S.C. § 3729)..."

Do not use footnote-style references in the summary body —
they are awkward in the WYSIWYG rendered output.

### Sources field format
In the Sources & Citations textarea, list one source per line.
Include statute name, citation, and official source URL where available.

Example:
```
False Claims Act — 31 U.S.C. §§ 3729–3733 — https://www.govinfo.gov/...
California Labor Code § 1102.5 — https://leginfo.legislature.ca.gov/...
```

---

## Content That Is Out of Scope

Summary content should not include:

- Legal advice or recommendations for specific situations
- Predictions about legal outcomes
- Commentary on whether specific employers or industries comply
- Political commentary about whistleblower law policy
- References to specific unresolved legal disputes or pending cases
- Promotional references to law firms, attorneys, or legal services

When in doubt, omit and flag for review.

---

## Review Before Publishing

Before a jx-summary post is set to Published:

1. **Reading level check** — run through Hemingway Editor or equivalent.
   Target grade 9–10. Nothing above grade 12 without a specific reason.

2. **Source verification** — every legal claim must have a citation
   in Sources & Citations. Every cited URL should be verified active.

3. **Structure check** — does the summary follow the structure above?
   Does it answer "am I protected?" in the first two sentences?

4. **Human review toggle** — set ws_human_reviewed to Reviewed only
   after completing the above steps. This badge is visible publicly
   and represents a real commitment to quality.

5. **Last Reviewed date** — update ws_last_reviewed to today's date
   when publishing or re-publishing after substantive changes.
