# Current Proposals

## What This Document Is

The living roadmap for WhistleblowerShield. Documents what is built
and working, what is structurally in place but not yet active, what
is deferred and why, and what the pre-launch checklist requires before
the plugin can be deployed to a live server.

This document changes as work progresses. When a proposal is
implemented, move it to the relevant technical document and note the
version it shipped in. Do not let this document become a historical
record — it should always reflect the current state of what is
ahead, not what has passed.

---

## Current Development Status

The plugin is in active development in a staging environment. It has
never been deployed to a live server with real user data. The
development-only notice block in `ws-core.php` must be removed before
activation, and all `@todo` items flagged "pre-launch only" must be
audited.

No data migrations are required for any architectural change until
the plugin is deployed to production. Architectural changes are free
to be destructive until that notice is removed.

---

## What Is Built and Working

The following systems are complete, tested in staging, and considered
ready for production subject to the pre-launch checklist below:

**Data layer:** All 10 CPTs registered. All 16 taxonomies registered
and seeded. All 14 ACF field groups registered. Matrix seeders for
all 9 seeder files with correct dependency order and idempotent gates.

**Query layer:** All dataset functions for all CPTs. Transient caching
with correct invalidation. Federal append pattern for statutes,
citations, and interpretations. Per-agency and per-statute procedure
transients with stash+diff invalidation.

**Assembly layer:** Jurisdiction assembler building full pages from
available datasets. Agency assembler appending procedure sections.
All section render functions. All shortcodes. Reference page pattern.
Two CSS files with correct conditional loading.

**Admin layer:** Admin columns for all CPTs. Navigation metaboxes for
jurisdictions and agencies. Audit trail. Major edit logger with
ws-legal-update auto-creation. Source verify workflow with role gates.
Plain English workflow with review stamps. Stamp fields on all CPTs.
Procedure statute link validation with publish gate and admin override.

**Monitoring systems:** URL health monitor (10-day and 3-day
schedules). Inoreader feed monitor with staged review UI. Matrix
divergence watch dashboard widget. Jurisdiction dashboard completion
tracker. Runtime health check admin notice.

---

## Phase 2: The Situation-Based Filter Cascade

The largest deferred feature. All infrastructure is in place; the
implementation gap is the user-facing entry mechanism and the
server-side filter resolution.

**What exists:**
- `ws_render_jx_filtered( $post, $jx_term_id, $filter_context )` —
  the filtered render function stub is in `render-jurisdiction.php`,
  marked Phase 2 priority. Currently returns `''`.
- `ws_render_directory_taxonomy_guide()` — the directory filter panel
  stub is in `render-directory.php`, marked Phase 2 priority.
  Currently returns `''`.
- The Phase 2 dispatch block in `ws_handle_jurisdiction_render()` is
  commented out, waiting for `ws_resolve_filter_context()`.
- All taxonomies needed for filtering (`ws_disclosure_type`,
  `ws_employment_sector`, `ws_case_stage`, `ws_process_type`,
  `ws_procedure_type`) are registered, seeded, and in use on records.
- `ws_get_nationwide_assist_org_data( $filters )` already accepts
  type, sector, stage, and cost_model filter parameters.

**What needs to be built:**
1. `ws_resolve_filter_context()` — reads `$_GET` params and resolves
   them to an array of taxonomy term IDs. Centralized param names
   should live in a new `ws-filter-config.php` file.
2. `ws_render_jx_filtered()` implementation — renders all published
   records (not just attach-flagged) filtered by `$filter_context`
   taxonomy cascade. See the implementation notes in
   `render-jurisdiction.php` for the full spec.
3. `ws_render_directory_taxonomy_guide()` implementation — right-side
   taxonomy cascade panel for the directory. PHP-only GET form;
   JS may be layered on for UX. See implementation notes in
   `render-directory.php`.
4. Situation-based entry points at the site navigation level — links
   that arrive at jurisdiction pages with the appropriate filter
   context pre-set.

**Implementation notes:**
- PHP-only, no AJAX required for core functionality. Filtered URLs
  must be bookmarkable and shareable.
- The directory filter (`ws_render_directory_taxonomy_guide`) operates
  on the nationwide dataset without jurisdiction scope. The jurisdiction
  page filter (`ws_render_jx_filtered`) is scoped to a single
  jurisdiction. These are parallel but distinct implementations.
- `attach_flag` is ignored in the filtered path — all published
  records are candidates, constrained by the filter context.

---

## Pre-Launch Checklist

Items that must be addressed before the plugin can be deployed to a
live server and the development-only notice removed from `ws-core.php`:

**Code:**
- [ ] Remove the `DEVELOPMENT ONLY` notice block from `ws-core.php`
- [ ] Audit all `@todo` items flagged "pre-launch only" throughout
      the codebase
- [ ] Run the full testing pass documented separately in
      `project-status.md` (not yet written — required before launch)
- [ ] Verify CSS conditional loading is optimized for production — both
      CSS files currently enqueue globally for singular contexts;
      a final design pass against GeneratePress Premium is deferred
      to pre-launch

**Design:**
- [ ] GeneratePress theme CSS integration pass — color, border, and
      spacing refinements flagged in `ws-core-front-general.css` and
      `ws-core-front-jx.css` are deferred to this pass
- [ ] Trust badge color and border refinement (flagged in
      `ws-core-front-jx.css`)

**Data:**
- [ ] Verify all 57 jurisdiction posts exist and have correct taxonomy
      term assignments
- [ ] Verify all matrix-seeded records (agencies, statutes, procedures,
      assist orgs) are accurate as of launch date and
      `ws_verification_status` reflects their review state
- [ ] Confirm `ws_seeded_procedure_matrix` gate reached `1.0.0` and
      procedure count is non-zero (health check will surface this)

**Monitoring:**
- [ ] Configure Inoreader API credentials (`wp_options`) — Bearer
      token required, App ID/App Key optional
- [ ] Confirm server-side crontab is configured to hit
      `wp-cron.php?doing_wp_cron` every five minutes for reliable
      URL monitor and feed monitor scheduling
- [ ] Verify `ws-core-error.log` exists at the configured path and
      is writable — PHP will not create the file, only write to it

---

## Deferred Items

Items that are explicitly deferred and will not block launch but
should be addressed in a subsequent iteration:

**Jurisdiction comparison view**
A cross-jurisdictional comparison capability would serve Daniel
(the researcher persona) directly — the ability to compare how two
or more states handle a specific disclosure type or remedy. The data
model supports this entirely. It requires a new page template and
query, not architectural changes.

**County and municipal coverage**
Currently out of scope. The jurisdiction taxonomy and CPT structure
can accommodate county-level entries — the data model does not
preclude it. A separate CPT for county-level data is recommended
over extending the existing `jurisdiction` CPT.

**Contributor onboarding**
The codebase is explicitly commented for future contributors, but a
formal onboarding document and contribution workflow do not yet exist.
This document set is the first step; a separate contributor guide
and pull request process are deferred to when the first external
contributor joins.

**Legal professional backend**
A backend layer for licensed attorneys doing formal legal research
is explicitly out of scope for the initial launch. If added, it
should be a separate layer with its own design considerations —
not a modification of the public-facing resource.

**Multi-jurisdiction legal update display**
The `ws_legal_update_multi_jurisdiction` boolean flag on legal update
records is reserved for a future feature that surfaces updates
affecting multiple jurisdictions in a cross-jurisdictional feed. The
flag is stored and editable but no rendering or query logic has been
built for it yet.

**Block-based rendering**
The current output layer uses shortcodes and `the_content` filter
interception. Block-based rendering (Gutenberg blocks) is a possible
future direction that would not require architectural changes to the
data or query layers. Deferred indefinitely — shortcodes are
sufficient for the current scope.

---

## Known Technical Debt

Items that are functional but should be cleaned up before or shortly
after launch:

**`ws_legal_update_multi_jurisdiction` rendering** — the field is
stored but the query layer and render layer do not use it. The field
instructions in the ACF group say "reserved for future use." If the
feature is not planned in the near term, consider removing the field
to reduce editorial confusion.

**GMT date fields** — several dataset functions return GMT date fields
with `@todo` notes to remove them later. These should be audited
against their consumers before launch and removed if nothing reads them.

**CSS enqueue optimization** — both CSS files currently load on
`is_singular()`. A final pass to confirm that `ws-core-front-general.css`
is not loading unnecessarily on pages with no ws-core shortcodes
would improve frontend performance.

**`project-status.md`** — referenced in the development-only notice
block as containing the full testing pass. This document does not yet
exist and must be written before launch.
