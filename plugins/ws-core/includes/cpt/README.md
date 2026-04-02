# includes/cpt/

CPT registration files for all ws-core post types.

Each file registers one CPT and nothing else. No hooks, no ACF
fields, no query logic. One file, one CPT, one job.

---

## CPT Registry

| Slug | File | `menu_position` | `publicly_queryable` | `has_archive` |
|---|---|---|---|---|
| `jurisdiction` | `cpt-jurisdictions.php` | 25 | true | false |
| `jx-summary` | `cpt-jx-summaries.php` | 26 | false | false |
| `jx-statute` | `cpt-jx-statutes.php` | 26 | false | false |
| `jx-citation` | `cpt-jx-citations.php` | 27 | false | false |
| `jx-interpretation` | `cpt-jx-interpretations.php` | 29 | false | false |
| `ws-agency` | `cpt-agencies.php` | 28 | true | — |
| `ws-ag-procedure` | `cpt-ag-procedures.php` | 29 | true | false |
| `ws-assist-org` | `cpt-assist-orgs.php` | 30 | true | — |
| `ws-legal-update` | `cpt-legal-updates.php` | 25 | false | false |
| `ws-reference` | `cpt-references.php` | 32 | true | false |

---

## Why `has_archive: false` Everywhere

Archive pages are not part of the platform's information architecture.
Content is surfaced via jurisdiction pages (assembled by the render
layer) and the directory shortcode — not via WordPress archive URLs.
Enabling archives would produce unstyled, uncontrolled listing pages.

---

## Why Some CPTs Are Not `publicly_queryable`

`jx-summary`, `jx-statute`, `jx-citation`, `jx-interpretation`, and
`ws-legal-update` are not publicly queryable. They are never accessed
directly by URL — their content is assembled and rendered on
jurisdiction pages by the Assembly Layer. Direct URL access would
produce unstyled raw post content.

`jurisdiction`, `ws-agency`, `ws-ag-procedure`, `ws-assist-org`, and
`ws-reference` are publicly queryable because they have dedicated
render handlers that produce a styled page.

---

## `menu_position` Allocation

Admin sidebar positions are allocated to keep related CPTs adjacent:

```
25  jurisdiction, ws-legal-update
26  jx-summary, jx-statute
27  jx-citation
28  ws-agency
29  jx-interpretation, ws-ag-procedure
30  ws-assist-org
32  ws-reference
```

If adding a new CPT, check this table before assigning a position
to avoid collision with WordPress core menu items (80, 85, 90, 99)
and existing CPTs.
