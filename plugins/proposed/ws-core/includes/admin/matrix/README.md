# includes/admin/matrix/

Idempotent matrix seeders that populate the canonical dataset on
first install. All seeders run once and are gated against re-running.

---

## Files

| File | Seeds | Gate Key |
|---|---|---|
| `matrix-helpers.php` | Shared utilities — no data, loads first | — |
| `matrix-jurisdictions.php` | 57 jurisdiction posts + taxonomy terms | `ws_seeded_jurisdiction_matrix` |
| `matrix-federal-courts.php` | Federal court registry (`$ws_court_matrix`) | `ws_seeded_court_matrix` |
| `matrix-state-courts.php` | State/territory court registry (`$ws_state_court_matrix`) | `ws_seeded_state_court_matrix` |
| `matrix-fed-statutes.php` | Major federal whistleblower statutes | `ws_seeded_fed_statutes_matrix` |
| `matrix-agencies.php` | Nationwide federal agencies | `ws_seeded_agency_matrix` |
| `matrix-assist-orgs.php` | Nationwide and federal-scope assist organizations | `ws_seeded_assist_org_matrix` |
| `matrix-ag-procedures.php` | Federal agency filing procedures | `ws_seeded_procedure_matrix` |
| `admin-matrix-watch.php` | Divergence detection — not a seeder | — |

**Load order is non-negotiable:** `matrix-helpers.php` first, then
`matrix-jurisdictions.php`. All other seeders depend on the `us`
`ws_jurisdiction` term existing before they run.

---

## Seeder Gate Pattern

```php
if ( get_option( 'ws_seeded_agency_matrix' ) !== '1.0.0' ) {
    ws_seed_agency_matrix();
    update_option( 'ws_seeded_agency_matrix', '1.0.0' );
}
```

Gates run on `admin_init`. To re-run a seeder: bump the version
string in the comparison. Never delete the option.

---

## `WS_MATRIX_SEEDING_IN_PROGRESS` Constant

Defined at the start of each seeder run, undefined at the end.
Prevents `save_post` hooks — specifically `admin-matrix-watch.php`
— from flagging matrix-created records as diverged during seeding.

```php
define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
// ... seeder runs ...
// constant cannot be undefined in PHP — presence is the signal
```

Any hook that should not fire during seeding checks:

```php
if ( defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) return;
```

---

## Matrix Divergence Pattern

Every seeded record receives `ws_matrix_source` post meta set to
the seeder's slug (e.g. `'agency-matrix'`). This is the divergence
signal.

When `admin-matrix-watch.php` detects a save on a record carrying
`ws_matrix_source`, it writes:
- `ws_matrix_divergence = 1`
- `ws_matrix_divergence_editor` = the editing user's ID

The dashboard widget in `jurisdiction-dashboard.php` surfaces all
unresolved divergences. To dismiss a divergence: set
`ws_matrix_divergence_resolved = 1` on the post.

---

## Court Matrices (In-Memory Only)

`$ws_court_matrix` and `$ws_state_court_matrix` are PHP globals
defined in their respective files. They are never seeded to CPT
posts — they exist in memory only. `ws_court_lookup( $key )` in
`matrix-helpers.php` searches both globals and returns the entry
or `null`.

On the frontend both globals are empty — court matrices are
admin-only. Callers must handle `null` gracefully.
