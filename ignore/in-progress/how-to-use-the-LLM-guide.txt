Here's the rewrite:

How to Use an LLM as a Legal Research Assistant
WhistleblowerShield.org — Researcher Guide

What You Are Actually Doing
You are using an LLM as a research assistant, not a legal authority and not a data entry clerk. Its job is to hand you its best notes on a jurisdiction — confidently where it can, and honestly where it cannot. Your job is to verify, correct, and make the final call on everything.
A record with five confident, accurate fields is more valuable to this project than a record with fifteen fields where three are invented. The model knows this. You should too.

Core Principles
Omission is not failure. Fabrication is.
The model is motivated to be helpful. Left unchecked, that motivation produces plausible-sounding statute of limitations values, case names that don't exist, and URLs that resolve to nothing. A person acting on that data could be genuinely harmed. The prompt is designed to redirect that helpfulness toward honesty — but you are the last line of defense.
Schema shape is non-negotiable. Field completeness is not.
The model must produce valid JSON with the correct field names and data types. It does not need to fill every field. Empty strings and empty arrays are correct and expected outputs, not errors.
Human review is not a safety net. It is the process.
The model's output is a first draft. Statute texts, citations, URLs, and legal interpretations require your verification before anything enters the database. No exceptions.

How to Run a Session
Phase 1 — Plan before you generate
Before asking for any JSON, ask the model to outline its plan:

Which statutes it intends to cover
For each statute: which fields it expects to know confidently, and which it expects to leave empty
Which taxonomy terms it anticipates needing, flagging any that may not exist in the known list

No JSON yet. Review the plan. If something looks overconfident or off-scope, correct it before proceeding.
Phase 2 — Generate against the approved plan
Feed the plan back and ask for JSON records only for the approved statutes. The prompt template handles the behavioral instructions — you do not need to re-state omission rules here. Your job at this stage is scope, not coaching.
Keep batches small
One to three statutes per run is the right size. Larger batches increase pressure to fill gaps and make review harder. You can always run another batch.

What to Review After Each Run
Taxonomy arrays
The ingest tool will flag any term ID not in the registered taxonomy. Before ingest, scan new_terms_proposed — these are terms the model identified as needed but couldn't match to an existing ID. You decide whether to accept, modify, or reject each proposal. Do not let proposed terms sit unresolved.
URLs
Any URL in the output needs a click before ingest. Official government domains are preferred. If the model left a URL field empty, that is correct behavior — find the URL yourself rather than asking the model to retry.
Citations
Case names require independent verification. If the model left attached_citations empty, that is correct behavior. Do not prompt the model to "try harder" on citations — that is the fastest path to invented case law.
Statute of limitations values
These are among the highest-risk fields in the schema. A wrong number is worse than a blank. If the model flagged limit_ambiguous: true, take that seriously and verify before overriding it.
json_run_notes
Read this field. It is where the model flags its own uncertainty, scope decisions, and taxonomy recommendations. It is the most honest part of the output.

What the Ingest Tool Handles
You do not need to manually enforce these — the ingest tool will catch them:

Taxonomy values not present in the registered term list
Missing required keys
Type mismatches

What the ingest tool cannot catch: a plausible-sounding but wrong statute citation, a real-looking but nonexistent URL, or a case name that doesn't exist. Those require you.

A Note on Model Memory
Each session is fresh. The model does not carry forward corrections or taxonomy decisions from previous runs. If you have refined your taxonomy since the last session, use the prompt generator to produce an updated directive — do not assume the model remembers.

What Success Looks Like
A good run produces a JSON object where:

Every included value can be verified against a real source
Empty fields are empty because the data wasn't findable, not because the model gave up
new_terms_proposed reflects genuine taxonomy gaps, not invented terms smuggled into a proposal wrapper
json_run_notes gives you something useful to act on

You will not always get a complete record. You will sometimes get a nearly empty record. Both are acceptable outcomes. An honest incomplete record can be enriched over time. A confidently wrong record causes harm and erodes trust in the platform.

See also: build-json-directive.md, legal-research-methodology.md