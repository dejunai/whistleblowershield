# Phase X — Platform Reuse Proposal

**Written:** 2026-03-26
**Status:** Deferred — revisit when platform is established
**Prerequisite:** 100% data fill + minimum 12 months of live operation

---

## What This Document Is

A prebuilt proposal for the long-horizon reuse of the WhistleblowerShield
platform architecture beyond its original whistleblower protection scope.

Do not act on this document until the platform has proven itself. The
conditions for revisiting this are specific and intentional — see
Prerequisites below. The ideas here are worth keeping, but they are
worth nothing without the credibility that only time and real use can build.

---

## The Core Insight

The ws-core architecture is domain-agnostic. The engine does not know it
is about whistleblower law. It knows how to:

- Model structured legal information across 57 jurisdictions
- Present that information in plain language grounded in primary sources
- Surface it through a situation-based cascade that answers real questions
  for real people in real situations
- Maintain it through an editorial workflow with traceability and verification

That capability applies to any legal domain where the same gap exists:
scattered jurisdiction-specific law, technical language inaccessible to
the people it affects, and no plain-language reference that stays current.

The content is whistleblower-specific. The engine is not.

---

## Prerequisites for Revisiting

None of the following should be considered until all conditions are met:

- [ ] 100% data fill across all 57 jurisdictions
- [ ] Minimum 12 months of live operation with real user traffic
- [ ] Phase 2 filter cascade fully operational and validated
- [ ] At least one successful outreach campaign cycle with documented
      feedback from Tier 1 legal aid organizations
- [ ] Open Collective campaign established with at least modest
      recurring support — proves public interest exists

If any of these conditions are not met, close this document and come back.
Premature expansion with an unproven platform produces nothing of value
and risks the reputation the platform is still building.

---

## Candidate Domains

Listed in order of architectural fit and public-interest alignment.
Each domain has been assessed against the platform's data model,
not just the general idea.

### 1. Tenant Rights *(closest fit)*

**Why it fits:** Structurally identical to whistleblower protections.
Jurisdiction-specific, varies enormously by state and city, affects
people in crisis who need plain-language answers fast. "Am I protected
from this eviction?" maps directly to "Am I protected from this
retaliation?" The cascade question tree is nearly identical. The CPT
model — statutes, agencies, procedures, assist organizations — maps
directly with minimal modification.

**What would need to change:** New taxonomy terms for tenant-specific
disclosure types and adverse actions. New matrix data. New jurisdiction
pages. The engine: unchanged.

**Public interest case:** Tenant rights are among the least accessible
areas of law for the people most affected by them. Low-income renters
facing eviction have the same information gap as workers facing
retaliation — and the same urgent deadline problem.

**Recommended approach:** Separate WordPress instance, separate plugin
fork (`tr-core`), shared architectural patterns. Do not attempt to
combine both domains in one plugin — the content separation matters
for credibility and maintenance.

---

### 2. Workers Compensation

**Why it fits:** Jurisdiction-specific, highly procedural, deadline-
bearing, affects people without attorneys. The two-question split
("who can help me?" / "what do I do next?") applies directly. Filing
procedures are the highest-priority content — the same pattern that
drove the `ws-ag-procedure` CPT design.

**What would need to change:** Different taxonomy axes (injury type,
employer type, claim status). New agency records (state workers comp
boards). The cascade question tree needs redesign but the infrastructure
supports it.

**Complication:** Workers compensation intersects with personal injury
law in ways that create stronger not-legal-advice pressure than
whistleblower law. The disclaimer infrastructure would need to be
more prominent and more specific.

---

### 3. Consumer Protection

**Why it fits:** Lemon laws, debt collection, predatory lending —
all jurisdiction-specific, all affecting people who don't have
attorneys, all with real deadlines. The statute + agency + procedure
model maps cleanly.

**What would need to change:** New taxonomy terms. New agency data.
The assist organization model works as-is — consumer protection
nonprofits and legal aid clinics serve this population already.

**Note:** Consumer protection law is broader and more fragmented than
whistleblower law. A focused initial scope (debt collection only, or
lemon laws only) would be more achievable than "all consumer protection."

---

### 4. Criminal Record Expungement

**Why it fits:** Eligibility varies enormously by jurisdiction, highly
procedural, affects people making significant life decisions. The
cascade model — what is your situation, what jurisdiction, what happened
— maps directly. Filing deadlines exist in some jurisdictions.

**Complication:** Expungement eligibility depends on specific case facts
in ways that push closer to legal advice than the other domains.
The not-legal-advice line requires more care here.

**Public interest case:** Strong. Expungement affects employment,
housing, and education access. The information gap is real and the
existing resources are poor.

---

### 5. Policy Research Tool *(different model)*

**Why it fits:** The jurisdiction comparison view deferred in
`current-proposals.md` — the ability to compare how multiple states
handle a specific protection — is a policy research tool. A state
legislator's office drafting whistleblower protection legislation
would want exactly this. The data model supports cross-jurisdictional
comparison natively; it just needs a presentation layer.

**What would need to change:** A new page template for comparison
views. Minimal architectural change. This is an extension of the
existing platform, not a separate deployment.

**Who would use it:** Legislative staff, law school clinics, policy
researchers, bar association committees, advocacy organizations doing
state-level comparison research.

**Note:** This is the lowest-effort Phase X because it reuses the
existing dataset without a new domain build. It surfaces the platform's
research value to a professional audience that Daniel represents.

---

## The Licensing Question

If the platform proves credible and the architecture proves reusable,
there are three paths for Phase X expansion:

**1. Internal fork** — Build a separate instance for a new domain
using the same architecture. Maintain separately. Full control,
full responsibility.

**2. White-label licensing** — License the architecture to a legal
aid organization that wants to build their own domain-specific
reference platform. They supply the domain expertise and editorial
capacity; ws-core supplies the infrastructure. This requires a
licensing model and support capacity that don't exist yet.

**3. Open source release** — Release ws-core under GPL-2.0 (consistent
with WordPress conventions) and allow the community to build domain-
specific forks. Maximum reach, minimum control over quality. Only
appropriate if the platform has proven itself and the documentation
is sufficient for independent contributors to maintain it correctly.

No recommendation is made here. The right path depends on the platform's
credibility, the available capacity, and the funding situation at the
time of revisiting.

---

## What Makes Phase X Possible

Phase X is only possible because of decisions made during the original
build:

- The taxonomy system is domain-agnostic — new taxonomy terms can
  be added without architectural changes
- The CPT model (statutes, agencies, procedures, assist organizations)
  maps to most legal aid domains with minimal modification
- The query layer contract means new domains don't introduce new
  technical debt — the data retrieval pattern holds
- The plain language overlay system works for any content type,
  not just whistleblower law
- The source verification and editorial workflow apply equally to
  any legal domain
- The documentation set means a new contributor can understand the
  architecture without starting from scratch

The engine is ready. The question is whether the credibility is earned.

---

## What to Do With This Document

Revisit it in 12 months. Read the prerequisites first. If they are
not met, close it again.

If they are met, start with the policy research tool (Phase X option 5)
— it requires no new domain expertise, no new data build, and reuses
the existing dataset. It is the lowest-risk test of whether the
platform has research value beyond the primary user audience.

After that, tenant rights.