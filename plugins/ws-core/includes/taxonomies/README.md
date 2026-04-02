# includes/taxonomies/

Taxonomy registration and seeding for all ws-core taxonomies.

---

## Files

| File | Purpose |
|---|---|
| `register-taxonomies.php` | Registers all 16 taxonomies and runs seeders on first admin load |
| `register-glossary.php` | Registers the `ws_glossary` taxonomy and seeds 25 initial terms |
| `taxonomy-tables.txt` | Human-readable flat reference of all taxonomy terms and slugs |

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

## Taxonomy Reference

16 taxonomies registered. Key ones:

| Slug | Type | Attaches To | Notes |
|---|---|---|---|
| `ws_jurisdiction` | flat | All content CPTs | Canonical join key. USPS slug (e.g. `ca`, `us`). |
| `ws_disclosure_type` | hierarchical | `jx-statute`, `jx-citation`, `ws-agency`, `ws-ag-procedure`, `ws-assist-org` | |
| `ws_process_type` | flat | `jx-statute`, `jx-interpretation`, `ws-agency` | |
| `ws_procedure_type` | flat | `ws-ag-procedure` | Replaces retired `ws_proc_type` ACF select |
| `ws_employment_sector` | flat | `ws-assist-org` | For Phase 2 filter cascade |
| `ws_case_stage` | flat | `ws-assist-org` | Q1 split in Phase 2 cascade |
| `ws_glossary` | flat | *(unattached)* | Admin-only; no public archive |

Full taxonomy inventory in `ws-core/README.md`.
