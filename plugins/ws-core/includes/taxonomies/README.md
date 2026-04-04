# includes/taxonomies/

Taxonomy registration and seeding for all ws-core taxonomies.

---

## Files

| File | Purpose |
|---|---|
| `register-taxonomies.php` | Registers all 17 taxonomies and runs seeders on first admin load |
| `register-glossary.php` | Registers the `ws_glossary` taxonomy and seeds terms |
| `taxonomy-statutes.txt` | Taxonomy reference for jx-statute, jx-citation, jx-interpretation, jx-common-law |
| `taxonomy-citations.txt` | Taxonomy reference for jx-citation (mirrors statutes) |
| `taxonomy-interpretations.txt` | Taxonomy reference for jx-interpretation (mirrors statutes) |
| `taxonomy-agencies.txt` | Taxonomy reference for ws-agency |
| `taxonomy-aorgs.txt` | Taxonomy reference for ws-assist-org |
| `taxonomy-tables.txt` | Human-readable flat reference of all taxonomy terms and slugs |

`taxonomy-common-law.txt` — pending creation for jx-common-law pipeline reference. Create when Wyoming data build begins.

---

## Two-Phase Registration Behaviour

WordPress requires taxonomies to be registered before CPTs that use
them, but ACF `save_terms` / `load_terms` requires CPTs to be
registered before the taxonomy's `object_type` array is finalized.

This is resolved in `loader.php` by registering taxonomies in two
passes — initial registration on `init` before CPTs, then
`object_type` binding after CPTs are registered. This is documented
in the `TAXONOMY TWO-PHASE BEHAVIOUR` section of `loader.php` and
must not be collapsed into a single pass.

---

## Seeder Gate Standard

All taxonomy seeders use the `ws_seeded_{slug}` option key with a
semver string value:

```php
if ( get_option( 'ws_seeded_disclosure_taxonomy' ) !== '1.0.0' ) {
    ws_seed_disclosure_taxonomy();
    update_option( 'ws_seeded_disclosure_taxonomy', '1.0.0' );
}
```

**To re-run a seeder:** bump the version string in the seeder gate
comparison. Never delete the option — deleting it causes the seeder
to re-run against existing terms and produce duplicates or errors.

**To add new terms to an existing seeder:** bump the gate version,
add the new terms to the seeder function, and ensure
`term_exists()` guards prevent duplicate insertion.

---

## `ws_bulk_insert_hierarchical()` Helper

Hierarchical taxonomies use the shared helper defined in
`register-taxonomies.php`. It handles parent-child term creation
in the correct order and prevents duplicate insertion.

Flat taxonomies use `wp_insert_term()` directly with a
`term_exists()` guard.

---

## has-details Sentinel Pattern

Six taxonomies include a `has-details` sentinel term:
`ws_protected_class`, `ws_disclosure_targets`, `ws_adverse_action_types`,
`ws_remedies`, `ws_employer_defense`, `ws_employee_standard`.

When an editor selects `has-details` in a taxonomy multi-select field,
a companion ACF freetext `_details` textarea becomes visible via dynamic
conditional logic (injected at field load time — not at registration time,
because term IDs are only available at runtime).

This pattern applies to `jx-statute`, `jx-common-law`, `jx-citation`,
and `jx-interpretation`. The PHP is the single source of truth for which
taxonomies carry the sentinel. The `taxonomy-*.txt` reference files must
reflect this — any taxonomy listed without `has-details` in the text file
but with it in the PHP is a documentation divergence.

---

## PHP is the Single Source of Truth

`register-taxonomies.php` is authoritative. The `taxonomy-*.txt` reference
files exist for the AI research pipeline (prompt templates reference them
directly). Any divergence between PHP and text files is a bug in the text
files, not in the PHP.

Whenever taxonomy slugs, parents, or sentinel terms change in PHP, the
corresponding text file must be updated in the same pass.

---

## Taxonomy Reference

17 taxonomies registered.

### Shared Doctrinal Taxonomies
Attach to `jx-statute`, `jx-citation`, `jx-interpretation`, `jx-common-law`:

| Slug | Type | has-details | Notes |
|---|---|---|---|
| `ws_disclosure_type` | hierarchical | No | 6 parents, 26 children |
| `ws_protected_class` | hierarchical | Yes | 4 parents, 12 children |
| `ws_disclosure_targets` | hierarchical | Yes | 5 parents, 13 children |
| `ws_adverse_action_types` | flat | Yes | 14 terms |
| `ws_process_type` | flat | No | 9 terms |
| `ws_remedies` | flat | Yes | 20 terms |
| `ws_fee_shifting` | flat | No | 4 terms |
| `ws_employer_defense` | flat | Yes | 6 terms |
| `ws_employee_standard` | flat | Yes | 6 terms |

### Other Taxonomies

| Slug | Type | Attaches To | Notes |
|---|---|---|---|
| `ws_jurisdiction` | flat | All content CPTs | Canonical join key. USPS slug. |
| `ws_languages` | flat | `ws-agency`, `ws-assist-org` | `additional` sentinel |
| `ws_case_stage` | flat | `ws-assist-org` | Phase 2 filter axis |
| `ws_aorg_type` | flat | `ws-assist-org` | Single-value |
| `ws_employment_sector` | flat | `ws-assist-org` | Phase 2 filter axis |
| `ws_aorg_cost_model` | flat | `ws-assist-org` | Single-value |
| `ws_aorg_service` | flat | `ws-assist-org` | `additional` sentinel |
| `ws_procedure_type` | flat | `ws-ag-procedure` | 3 stable terms |
| `ws_glossary` | flat | *(unattached)* | Admin-only; no public archive |
