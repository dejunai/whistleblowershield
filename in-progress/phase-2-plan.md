# Phase 2 — Plan & Design

**Last updated:** 2026-03-26
**Status:** Planning complete — ready to execute after demo milestone

---

## Overview

Phase 2 is the situation-based filter cascade. It activates the dormant
filtered render path already stubbed in `render-jurisdiction.php` and
builds the directory filter panel. Together they transform the platform
from a legal reference archive into a guided experience that answers
two questions for real people in real situations.

The infrastructure is complete. Phase 2 is a rendering and navigation
problem, not a data model problem.

---

## Sequencing

### Before Phase 2 begins

1. **Install/seed loop** — exit criteria: all 57 jurisdiction posts exist
   with correct taxonomy assignments, all matrix seeders confirmed via
   health check, jurisdiction dashboard green across seeded content, at
   least one jurisdiction page renders without errors.

2. **Manual data build — Federal** — statutes, agencies, procedures.
   Taxonomy-complete, not just field-complete. Every record tagged on
   every relevant taxonomy axis.

3. **Manual data build — California** — full depth: statutes, citations,
   interpretations, agencies, procedures, assist orgs. California is the
   stress test — complex, well-documented, lots of case law. Taxonomy
   completeness audit required before cascade testing begins.

4. **Manual data build — thin jurisdiction** — Wyoming recommended.
   Genuinely thin legal landscape. Proves the fallback sequence works
   and that the platform handles incompleteness honestly.

5. **Build `tool-generate-prompt.php`** — prerequisite for production
   data runs. Build after demo data is complete so you have a validated
   dataset to test against.

6. **Build `tool-ingest.php`** — same reasoning. Manual population for
   demo phase. Tools built against real validated data post-demo.

### Phase 2 build — five unified pieces

Design all five together before building any of them.
`ws_resolve_filter_context()` is the hub — everything else consumes it.

1. `ws-filter-config.php` — param names, thin threshold table, default
2. `ws_resolve_filter_context()` — hub function, owns logging
3. `ws_render_jx_filtered()` — jurisdiction filtered path
4. `ws_render_directory_taxonomy_guide()` — directory filtered path
5. Log storage + admin dashboard surface

### After Phase 2

- Demo validation across all three entry paths
- Outreach campaign (org review)
- Social pressure testing (friends/family)
- Crowdfunding campaign
- Ingest tools built against validated dataset

---

## Architecture Decisions

### Filter vs. curated path

Two parallel render paths. Never merged.

**Curated path** (`ws_render_jx_curated()`):
- `attach_flag = true` gates the dataset
- Editorial selection — 3–5 records per section
- Already built and working

**Filtered path** (`ws_render_jx_filtered()`):
- `attach_flag` is **completely ignored**
- All published records are candidates
- Taxonomy match gates the dataset
- `attach_flag` is an editorial curation tool for the curated path only
- It is invisible to the filtered path — document this explicitly in
  every function that touches the filtered render

### Directory filter vs. jurisdiction filter

Share the same taxonomy vocabulary, the same `ws_resolve_filter_context()`
input mechanism, and the same filter param names from `ws-filter-config.php`.

They do **not** share:
- Query scope (directory = nationwide unscoped; jurisdiction = single jx scoped)
- Result set ceiling
- Fallback logic
- The attach-flag gate
- Federal append behavior

Implemented as independent functions consuming shared config.
**Never** as one function with conditionals branching on context.
The moment a branch says "if jurisdiction context, else directory" —
the functions have been incorrectly merged.

`ws_render_jx_filtered()` owns the jurisdiction path.
`ws_render_directory_taxonomy_guide()` owns the directory path.
`ws_resolve_filter_context()` serves both but is owned by neither.

### JS strategy

PHP-first. GET form, page reloads, progressive rendering.
JS layers on top as progressive enhancement — intercepts form
submissions, fetches updated panel HTML, swaps without full page reload.
PHP fallback stays intact. JS failure degrades gracefully to full reload.

Two PHP endpoints:
- Filter panel state (returns next question HTML)
- Results panel (returns filtered results HTML)

JS is ~40–50 lines of vanilla fetch + DOM swap. No framework required.
The PHP is the complex part. The JS is mechanical.

### Sidebar layout

WordPress sidebar hidden globally. Re-enabled for:
- `jurisdiction` CPT singles — filter panel lives here
- Directory page — filter panel lives here

GeneratePress Premium handles layout assignment per post type.
No conditional logic required in the plugin for the layout itself.

The filter panel is a stateless GET form. Neutral state on jurisdiction
page = curated view already rendered. Neutral state on directory = full
nationwide org listing. "Clear filters" returns to neutral state in both.

---

## The Question Tree

### Core design principle

> Ask only what the user genuinely knows right now.
> Map their answer to taxonomy terms invisibly.
> "Not sure" is always valid and always means broader results.
> Never expose the data model.

The taxonomy serves the engine. The questions serve the user.
These are not the same thing and must never be conflated.

### Taxonomy mapping (invisible to user)

| Question | User sees | Underlying taxonomy |
|---|---|---|
| Q1 | Situation | `ws_case_stage` |
| Q2A | What you're concerned about | `ws_disclosure_type` |
| Q2B | What happened to you | `ws_adverse_action_types` |
| Q3 | Kind of organization | `ws_employment_sector` |
| Q4A | Internal vs. external reporting | `ws_disclosure_targets` (simplified) |
| Q4B | What you reported | `ws_disclosure_type` |
| Q5 | What's most helpful right now | Presentation priority hint — not a taxonomy filter |

### Q1 — The primary split (no "not sure" option)

```
What best describes your situation?

  I'm thinking about reporting something
  — I haven't reported yet, or I'm deciding whether to

  I've already reported and something happened to me
  — I reported something and my employer responded negatively

  I'm just doing research
  — Routes directly to curated jurisdiction view, no further questions
```

Q1 is the only question without a "not sure" option. The tree branches
on it. A user genuinely unsure is almost certainly pre-disclosure —
the clarifying subtext handles this without a third ambiguous option.

"I'm just doing research" bypasses the cascade entirely. Routes to the
existing curated jurisdiction page. No new infrastructure required.

---

### Path A — Thinking about reporting

```
Q2: What are you concerned about?
    Financial fraud or corruption
    Workplace safety
    Environmental violations
    Healthcare or patient safety
    Government waste or abuse
    Discrimination or civil rights
    Something else / not sure

Q3: What kind of organization do you work for?
    Federal government
    State or local government
    Private company
    Publicly traded company
    Military or defense contractor
    Nonprofit or NGO
    Not sure

Q4: Are you thinking of reporting internally or to an outside authority?
    Internally first — supervisor, HR, or ethics hotline
    Directly to a government agency or law enforcement
    Both or undecided
    Not sure

→ Results:
   Priority 1 — Relevant statutes (what protects you)
   Priority 2 — Relevant agencies (who enforces it)
   Priority 3 — Assist organizations (who can help you decide)
```

---

### Path B — Already reported, something happened

```
Q2: What happened after you reported?
    I was fired or let go
    I was demoted or reassigned
    My pay or hours were cut
    I'm being harassed or the environment became hostile
    I was suspended
    I received threats
    Something else / not sure

Q3: What kind of organization do you work for?
    (same options as Path A Q3)

Q4: What were you concerned about when you reported?
    (same options as Path A Q2, plus "Prefer not to say")

Q5: What would be most helpful right now?
    Understand what my rights are
    Find an agency I can file a complaint with
    Find legal help
    All of the above / not sure
```

**Q5 drives results ordering, not filtering:**

| Q5 answer | Results order |
|---|---|
| Understand my rights | Statutes → procedures → assist orgs |
| File a complaint | Procedures (deadline-bearing first) → agencies → statutes |
| Find legal help | Assist orgs → procedures → statutes |
| All of the above / not sure | Statutes → procedures → agencies → assist orgs |

---

### Directory path — "Get Help" entry point

Same Q1/Q2/Q3 question sequence. Independent implementation.

Results: nationwide assist organizations only.
No statutes. No procedures. This is "Get Help" not "Know Your Rights."

Filtered by situation context, disclosure type or adverse action,
and employment sector. Same taxonomy terms, different dataset scope.

Fallback: "No organizations match all your criteria"
+ suggestion to broaden by removing one filter
+ link to full directory

---

### "Not sure" behavior

| Question | Effect |
|---|---|
| Q1 | Not available — must choose a path |
| Q2 | Omit disclosure_type / adverse_action — broader results |
| Q3 | Omit employment_sector — broader results |
| Q4 | Omit disclosure_targets — broader results |
| Q4B "Prefer not to say" | Omit disclosure_type from retaliation filter |
| Q5 | Default results ordering |

---

## Fallback Sequence

### Near-zero threshold

Defined as a per-jurisdiction lookup table in `ws-filter-config.php`.
A ratio calculation is not used — it implies false precision about
coverage depth that the data doesn't support.

```php
$ws_filter_thin_thresholds = [
    'wy' => 2,
    'de' => 3,
    'ca' => 5,
    'us' => 8,
    // grows as editorial judgment accumulates per jurisdiction
];
$ws_filter_thin_threshold_default = 3;
```

The table is a living config. Values are editorial judgments about
what "thin results" means for a given jurisdiction's actual legal
landscape — not arithmetic. Calibrated against real data as coverage
grows. May also need per-jurisdiction calculation as a ratio of
filtered results to total published records for that jurisdiction —
surfaces as a refinement once real data is available.

### Fallback behavior

Show what matched — even thin results — then append:
- "Limited coverage for this situation in [jurisdiction]"
- Filtered nationwide assist orgs (same situation context applied)
- Link to full directory

Never hide thin results in favor of a clean fallback. Show everything
the platform has, then be honest about the limits.

Zero results: same behavior. The percentage math bottoms out at zero.
The user still gets the fallback resources.

---

## Performance Logging

`ws_resolve_filter_context()` owns the log. One log call, complete picture.

Each render function returns a lightweight result summary alongside
its HTML — counts per content type plus whether fallback triggered.
The render functions produce this internally anyway to decide on
fallback. Logging observes it without changing their logic.

```php
[
    'timestamp'          => gmdate( 'Y-m-d H:i:s' ),
    'jurisdiction'       => 'ca',
    'filter_context'     => [ 'ws_disclosure_type' => [12], 'ws_employment_sector' => [8] ],
    'result_counts'      => [ 'statutes' => 3, 'citations' => 1, 'interpretations' => 0 ],
    'fallback_triggered' => false,
    'directory_request'  => false,
]
```

Stored as rolling append, capped at 500 entries.
Admin surface: tab on jurisdiction dashboard.
Shows: most common filter combinations, jurisdictions triggering
fallback most, content types returning zero results most often.
This is the data gap map — tells you where to prioritize next data build.

No user data at any point. The log captures what the system was asked
and what it had. Not who asked.

---

## Demo Milestone

Three jurisdictions. Three entry paths. One coherent demonstration.

**California** — stress test. Deep, complex, well-documented.
Proves the cascade returns meaningful filtered results at depth.
Federal append working correctly under filter conditions.

**Federal** — required. Appends to California. Proves append logic
holds under filter conditions.

**Wyoming** — honesty test. Genuinely thin legal landscape.
Proves the fallback sequence reads as honest and helpful rather
than broken. The platform handles incompleteness with integrity.

**Three entry paths to validate:**
1. Jurisdiction page → filtered cascade view
2. Directory → "Get Help" narrowed results
3. Thin jurisdiction → fallback sequence

Demo is internally solid before any external contact.

---

## Post-Demo: Outreach Campaign

### Sequencing

1. Internal demo validation complete
2. Org outreach launches — 4–6 week feedback window
3. Incorporate critical feedback
4. Crowdfunding campaign launches — org responses become social proof

### Three outreach tiers

**Tier 1 — Legal aid and direct representation orgs**
Most sophisticated. Will catch errors. Feedback is highest value.
Questions: accuracy for their practice area, what would make it
more useful, would they consider linking to it.

**Tier 2 — Advocacy and worker rights organizations**
Understand user need deeply. Questions: does it address what their
constituents ask, is plain language accessible, what situations
are missing.

**Tier 3 — General support organizations**
HR consultants, union reps, employee assistance programs. High
referral volume potential. Questions: comfortable directing someone
here, what would increase confidence in accuracy.

**Framing across all tiers:**
"We built a tool, we want it to help your clients, please review
and comment." California only. Demo state. Needs pressure testing.
Not asking for users — asking for review and feedback.

### Social pressure testing

Facebook / personal network. Ask casual friends to try to break it.
One specific ask: "Go to this page, pretend you're a California worker
who just got fired for reporting something. Tell me if you can find
what you need. Tell me where you got stuck."

Send some people to Wyoming deliberately — tests fallback sequence
with real lay users.

Feedback via email reply. Not a form — too much friction. One sentence
is enough: "I was trying to find X and got stuck at Y."

---

## Post-Demo: Crowdfunding Campaign

**Platform:** Open Collective
Provides fiscal hosting (non-profit shield), transparent expense
tracking, public ledger of income and expenses. No need to form
a separate non-profit.

### Expense basis

- Domain registration: March 3rd 2026 (citable via WHOIS)
- Labor: California minimum wage ($17.28/hour) × 28 hours/week
- Retro pay calculated from March 3rd forward
- Ongoing: ~$1,935/month

### Funding tiers

**Community Supporter** — any amount, named in contributor list

**Jurisdiction Sponsor** — $35 one-time
Funds data population of one jurisdiction (~1.87 hours at $17.28/hr).
Named on that jurisdiction's page as a founding sponsor.

**Maintenance Patron** — $10/month recurring
Funds ongoing maintenance of one jurisdiction per month.
Named on that jurisdiction's page.

**Founding Supporter** — $500+
Funds a full state or territory end-to-end (population + first year
maintenance). Prominently recognized.

### Operational commitments

- 15 jurisdictions/month data population (4-month goal to full dataset)
- 15 jurisdictions/month maintenance schedule ongoing
- Newsfeed monitor: significant legal changes applied within 7 days
  (the feed monitor makes this a system commitment, not just a promise)

### Campaign narrative

Transparent expense ledger. Modest retro pay at minimum wage.
Specific operational commitment with a 7-day update window.
Jurisdiction-funding model makes donations concrete and mission-connected.
Org outreach responses provide social proof before launch.

---

## Data Build Discipline

Taxonomy completeness is the data build requirement — not just field
completeness. A statute with no `ws_disclosure_type` terms is invisible
to Path A entirely. A procedure with no taxonomy coverage is invisible
to Path B.

**Pre-cascade checklist for California data:**
Every statute, citation, interpretation, agency, procedure, and assist
org audited for taxonomy coverage on every relevant axis. Gaps documented
explicitly rather than left as silent omissions.

The cascade is only as good as the taxonomy assignments on the records
it queries. This is the editorial discipline the data build requires.