# Ingest Pipeline — Current Status

**Last updated:** 2026-03-26
**Prompt version:** v2.0.4 (untested — see below)
**Schema version:** 2.0

---

## What This Document Is

The single ground resource for the AI-assisted legal data ingest pipeline.
Captures what exists, what doesn't exist yet, what the open decisions are,
and what the next concrete step is.

When work resumes on the pipeline, start here.

---

## What Exists

### The Prompt Template
`tools/ws-prompt-template-v2.0.4.txt` — the current working prompt.
Directs an AI assistant to produce structured JSON statute records.
Three top-level keys: `meta`, `records`, `integrity`.
Schema version 2.0.

v2.0.4 incorporates all fixes identified in the v1.6.5 test run
(see `archive/research/ai-assistant-comparison.md`):
- Strengthened parent slug prohibition
- Strengthened invented slug zero-tolerance rule
- Added `batch_completed` sentinel as last key in meta
- Renamed third block from `completed` to `integrity`

**v2.0.4 has not been tested against any model.** It was finalized as a
proposal after the v2.0.3 run but no production or validation run was
executed before work shifted to plugin development.

### The Schema
`json-schema-reference.md` — canonical reference for the JSON data
structure. Current version: 2.0. Documents every field in the `meta`,
`records`, and `integrity` blocks with field notes and constraints.

The `batch_completed` sentinel is the ingest tool's completion gate —
its absence or empty value aborts ingest entirely.

### The Supporting Documents
- `build-json-directive.md` — system design for the prompt generator
  tool (`tool-generate-prompt.php`). Describes why prompts are generated
  rather than handwritten, the template structure, and the taxonomy
  placeholder injection system.
- `Update-WsIngestDoc.md` — ingest tool design spec. Two-tool
  architecture: prompt generator + ingest processor. Directory
  structure, version handler pattern, field mapping approach.
- `how-to-use-the-LLM-guide.md` — operational researcher guide.
  Three-phase session workflow (plan → generate → review). What to
  check after each run. What the ingest tool handles vs. what requires
  human eyes.

### Test Data
`archive/research/` — six JSON test files from the v1.6.5 run:
CA and DE statutes from ChatGPT, Gemini, and Grok. Useful for
calibrating model behavior before a new run.

---

## What Does Not Exist

### The PHP Tools (not yet built)
Neither tool exists as implemented code:

- `tools/tool-generate-prompt.php` — the WordPress admin tool that
  reads `register-taxonomies.php` at runtime, injects live taxonomy
  term slugs into the prompt template, and outputs a ready-to-paste
  directive. **This is the most important missing piece.** Without it,
  the taxonomy tables in the prompt must be manually updated every time
  a taxonomy changes — which creates the exact drift problem the tool
  was designed to prevent.

- `tools/tool-ingest.php` — the WordPress admin tool that processes
  validated JSON files and writes records to ws-core CPTs. Includes
  version handler routing, taxonomy validation, record deduplication,
  and the `batch_completed` sentinel check.

Both tools are fully specified in `Update-WsIngestDoc.md`.

---

## Open Decisions

**1. v2.0.4 validation run needed**
Before any production use, v2.0.4 needs at least one test run against
a single jurisdiction with a small batch (3–5 statutes). Use the CA or
DE test jurisdiction for comparison against the archive.

**2. Prompt generator build priority**
The prompt generator should be built before any production data runs.
Running prompts with manually maintained taxonomy tables is viable for
a few runs but will drift. The generator is a WordPress admin tool —
a PHP file in `ws-core/tools/` — and is a bounded, well-specified build.

**3. Schema version lock**
The schema is at v2.0. Before building the ingest tool version handlers,
confirm the schema is stable. Any field additions or renames after the
tool is built require a new version handler.

**4. `proposed-terms-log.json`**
`tools/proposed-terms-log.json` is the target file for taxonomy term
proposals from AI runs. Currently empty. The ingest tool should write
proposed terms here; a human resolves them before terms are added to
`register-taxonomies.php`. The workflow for this is described in
`how-to-use-the-LLM-guide.md`.

---

## Next Concrete Step

Build `tool-generate-prompt.php`. It is the prerequisite for reliable
production data runs. Spec is in `Update-WsIngestDoc.md`. Once it
exists, run v2.0.4 against a small CA batch and compare output against
the archive to validate the prompt improvements landed as intended.
