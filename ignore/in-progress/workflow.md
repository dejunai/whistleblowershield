# AI Workflow — WhistleblowerShield.org

WhistleblowerShield covers U.S. whistleblower protections across 57 jurisdictions.
The data set is large, the legal landscape changes continuously, and the site is
built and maintained by a single developer. AI is not a shortcut here — it is a
deliberate architectural choice made with the end user in mind: a worker,
potentially in crisis, who needs accurate information they can act on.

This page documents how AI is used, why each tool was chosen, and what the human
layer looks like. It is written for developers and researchers, not for primary
users of the site.

---

## The Toolchain

Each tool in this workflow has a defined lane. No single model is trusted to do
everything.

**Claude (Anthropic) — Architecture and reasoning**
Used for system design, architectural decisions, and keeping the end user visible
throughout the build. The constitutional AI training produces something that
functions as a reasoning partner rather than a code generator — it pushes back
when a design decision drifts from the mission, surfaces downstream consequences
of upstream choices, and maintains awareness of the person the site is built to
serve. Twenty-one days from inception to a deployable plugin is partly a
consequence of this.

**Claude Code — Large-scale implementation**
Used for mechanical execution: global find-and-replace passes, codebase-wide
audits, file generation at scale. Fast, thorough, and appropriately scoped to
tasks where judgment matters less than completeness. Does not replace the
reasoning layer — it implements decisions made there.

**Perplexity — Uninformed audit**
Used specifically because it has no prior context. Fresh eyes on code files
catch drift, inconsistency, and documentation rot that familiarity obscures.
The absence of architectural knowledge is the feature, not a limitation.

**Gemini, ChatGPT, Grok — First-pass legal research**
Used to generate structured JSON data for the 57-jurisdiction legal dataset.
Each model produces a batch; a human reviews the output before anything reaches
the database. These models are explicitly positioned as first-pass researchers,
not authorities. The prompt template encodes this directly:

> "A record with five confident, sourced fields is more valuable than a record
> with fifteen fields where three are invented. Honest gaps do not cause harm.
> Confidently wrong records do."

---

## The Data Pipeline

Legal data enters the system through a structured JSON ingest pipeline. Every
batch produced by an AI model carries:

- A meta block identifying the model, version, jurisdiction, and generation date
- An integrity block — the model's honest self-report on what it could not
  confirm, which citations it omitted because URLs could not be verified, and
  whether the batch was truncated for quality reasons
- A `batch_completed` sentinel that the ingest tool checks before processing —
  a missing or empty sentinel aborts ingest entirely

The integrity block is not a formality. Models are explicitly instructed that
honest error reporting is more valuable than a clean-looking output. An
unreported error that passes through ingest is far more costly than one that
surfaces in the review queue.

All records enter a human review queue before publication. The AI produces the
draft. A human verifies it.

---

## Source Discipline

The prompt template enforces a strict approved-source hierarchy:

**Statute sources, in priority order:**
1. Official legislature URL for the jurisdiction (provided per-run)
2. `uscode.house.gov` / `congress.gov` for federal statutes
3. `legiscan.com` — acceptable secondary source
4. `law.justia.com` — acceptable secondary source

**Case law sources, in priority order:**
1. Official court websites
2. `courtlistener.com` — PACER-sourced
3. `casetext.com`
4. `law.justia.com`

Models are instructed to attempt sources in order, use the first that yields a
trustworthy result, and stop. `scholar.google.com` is explicitly rejected — URLs
are unstable and canonical identity cannot be confirmed. If no approved source
yields a verifiable URL, the field is omitted. A missing URL is correct output.
A fabricated URL is not.

---

## Taxonomy Discipline

All legal data is classified against a controlled taxonomy of seven tables
covering disclosure types, protected classes, disclosure targets, adverse action
types, process types, remedies, and fee shifting. Models may only use registered
term slugs in record arrays. When a concept does not fit an existing slug, models
are instructed to propose a new term rather than approximate with an existing one
or invent a slug and insert it directly.

Proposals surface in the batch output and are resolved by a human before the
term is added to the taxonomy. The taxonomy grows from evidence, not assumption.

---

## What AI Does Not Do

- AI does not publish anything. Every record passes through human review.
- AI does not determine whether a statute applies to a specific situation.
  The site is a reference, not legal advice.
- AI does not resolve taxonomy proposals. Humans do.
- AI does not verify that a cited case is still good law. That is a human
  reviewer responsibility flagged explicitly in the review queue.

---

## Why This Approach

The people this site serves are workers considering whether to report wrongdoing.
A wrong statute of limitations value could cause someone to miss a filing
deadline. A fabricated case citation could undermine their legal strategy. The
consequences of bad data are not abstract.

AI accelerates research and first-draft production significantly. It does not
replace the judgment required to determine whether that research is correct,
complete, and safe to publish. The workflow is designed around that distinction.

---

*WhistleblowerShield.org is an independent public-interest project.*
*Built in 21 days. Maintained by one developer. Reviewed by humans.*
