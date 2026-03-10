# ws-core — WhistleblowerShield Core Plugin

**Version:** 1.9.2
**Site:** https://whistleblowershield.org
**Requires:** WordPress 6.0+, Advanced Custom Fields Pro

---

## What This Plugin Does

`ws-core` is the foundation of WhistleblowerShield.org. It registers all Custom Post Types, ACF Pro field groups, shortcodes, and the hidden audit trail system. No other ws- plugin works without this one active.

---

## File Structure

```
ws-core/
├── ws-core.php              ← Main plugin file — loads all includes, enqueues stylesheet
├── ws-core-front.css        ← Frontend stylesheet for all ws-core output
├── README.md                ← This file
└── includes/
    ├── cpt-jurisdiction.php   ← `jurisdiction` CPT + one-time taxonomy cleanup routine
    ├── cpt-summaries.php      ← Addendum CPTs: jx-summary, jx-resources, jx-procedures, jx-statutes
    ├── cpt-legal-updates.php  ← `ws-legal-update` CPT
    ├── acf-jurisdiction.php   ← ACF field group: Jurisdiction Core
    ├── acf-summary.php        ← ACF field group: Jurisdiction Summary + deprecated field cleanup
    ├── acf-legal-updates.php  ← ACF field group: Legal Update
    ├── audit-trail.php        ← Hidden post meta audit trail (save_post, priority 99)
    └── shortcodes.php         ← All shortcode handlers
```

---

## Installation

1. Upload the `ws-core` folder to `/wp-content/plugins/ws-core/`
2. In WordPress admin: **Plugins → Installed Plugins → Activate WhistleblowerShield Core**
3. Confirm ACF Pro is also active — ws-core displays an admin notice if it is not
4. Go to **Settings → Permalinks → Save Changes** to register the `/ws-legal-update/` archive slug

---

## Custom Post Types

| CPT slug | Public | Archive URL | Purpose |
|---|---|---|---|
| `jurisdiction` | Yes | `/jurisdiction/` | Primary page for each of the 57 jurisdictions |
| `jx-summary` | No | — | Legal protections overview (rendered via shortcode) |
| `jx-resources` | No | — | Resources overview — future |
| `jx-procedures` | No | — | Coming forward procedures — future |
| `jx-statutes` | No | — | Statutes of limitations — future |
| `ws-legal-update` | Yes | `/ws-legal-update/` | Site-wide legal updates change log |

**Prefix notes:** `jx-` = "jurisdiction" addendum CPTs (non-public). `ws-` = site-wide content CPTs (public). The `jx-` prefix keeps all addendum slugs within WordPress's 20-character post type name limit.

---

## Shortcodes

All shortcodes accepting a `jurisdiction` parameter take either a post slug (e.g., `california`) or a post ID.

### `[ws_jurisdiction_header jurisdiction="california"]`
Renders the full jurisdiction page header: flag image with Wikimedia attribution, jurisdiction name (H1), and a government offices panel containing the portal link (all jurisdictions), governor link (states and territories), mayor link (D.C. only), and legal authority link (all except federal). Empty fields are suppressed automatically.

### `[ws_flag jurisdiction="california"]`
Renders only the flag image with attribution. Use when the flag needs to appear separately from the full header.

### `[ws_summary jurisdiction="california"]`
Renders the full jurisdiction summary block: summary HTML content, author, date created, last reviewed date, review status badges, and sources/citations.

### `[ws_review_status jurisdiction="california"]`
Renders review status badges only — Human Reviewed or Pending Human Review, and Legally Reviewed (with reviewer name) or Pending Legal Review.

### `[ws_legal_updates jurisdiction="california" count="5"]`
Renders recent legal updates. Scoped to a jurisdiction when the `jurisdiction` parameter is provided; site-wide when omitted. Queries the `ws-legal-update` CPT. Default count is 5.

### `[ws_jurisdiction_index]`
Renders the full jurisdictions index page: type filter tabs (All, U.S. States, Federal, U.S. Territories, District of Columbia) with item counts, and an alphabetical grid of all published jurisdictions. Tabs with no matching jurisdictions are hidden automatically. Client-side filtering — no jQuery dependency.

### `[ws_disclaimer_notice]`
Renders the standard "not legal advice" notice box. Copy is managed centrally in `shortcodes.php` — editing `$notice_text` there propagates to all jurisdiction pages automatically. Styled by `.ws-summary-notice` in `ws-core-front.css`.

### `[ws_footer]`
Renders the site-wide footer block: mission statement, policy page links, contact email, and copyright line.

---

## ACF Field Groups

### Jurisdiction Core (on `jurisdiction` CPT)

| Tab | Fields |
|---|---|
| Identity | `ws_jurisdiction_name`, `ws_jurisdiction_type` (select), `ws_jurisdiction_flag` (image), `ws_flag_attribution`, `ws_flag_attribution_url`, `ws_flag_license` |
| Government URLs | `ws_gov_portal_url/label`, `ws_governor_url/label`, `ws_mayor_url/label`, `ws_legal_authority_url/label` |
| Related Content | `ws_related_summary` → `jx-summary`, `ws_related_resources` → `jx-resources`, `ws_related_procedures` → `jx-procedures`, `ws_related_statutes` → `jx-statutes` |

`ws_jurisdiction_type` select values: `state`, `federal`, `territory`, `district`

### Jurisdiction Summary (on `jx-summary` CPT)

| Tab | Fields |
|---|---|
| Content | `ws_jurisdiction`, `ws_jurisdiction_type`, `ws_summary` (WYSIWYG), `ws_summary_sources` |
| Dates | `ws_date_created` (auto-fill on creation), `ws_last_reviewed` (auto-fill, editor updates manually) |
| Authorship & Review | `ws_author` (User, Author+, auto-fills current user), `ws_human_reviewed` (toggle), `ws_legal_review_completed` (toggle), `ws_legal_reviewer` (conditional — visible only when legal review is checked) |

### Legal Update (on `ws-legal-update` CPT)

Fields: `ws_legal_update_jurisdiction` (relationship → `jurisdiction`, multi-select), `ws_legal_update_law_name`, `ws_legal_update_summary` (WYSIWYG), `ws_legal_update_effective_date`, `ws_legal_update_source_url`, `ws_legal_update_author` (User, auto-fills current user).

---

## Audit Trail

Two hidden post meta keys are written on every save of any ws-core CPT (`jurisdiction`, `jx-summary`, `jx-resources`, `jx-procedures`, `jx-statutes`, `ws-legal-update`). Both use a leading underscore — WordPress treats them as hidden and they do not appear in the Custom Fields meta box.

| Meta Key | Behavior |
|---|---|
| `_ws_last_edited_by` | Overwritten on each save. Stores `{user_id, display_name, timestamp}`. |
| `_ws_edit_history` | Append-only. Each save adds one entry. Never overwritten. |

**Reading audit data from PHP:**
```php
$last    = ws_get_last_editor( $post_id );  // array or null
$history = ws_get_edit_history( $post_id ); // array of entries
```

---

## One-Time Cleanup Routines

Two routines run once on `admin_init` after first deployment and never again (guarded by option flags):

**`ws_cleanup_jurisdiction_type_taxonomy`** — Removes all orphaned `jurisdiction-type` taxonomy terms and term relationships from the database. The `jurisdiction-type` taxonomy was removed in favor of the `ws_jurisdiction_type` ACF select field. Completion flag: `ws_jx_type_taxonomy_cleanup`.

**`ws_cleanup_metabox_remnants`** — Deletes all post meta rows for deprecated Meta Box field keys (`sources_public`, `ws_postal_code`, `ws_government_portal_url`, `ws_flag_image`, `ws_state_leadership_last_verified`, `ws_state_gov_office_url`, `ws_state_ag_office_url`). Completion flag: `ws_metabox_cleanup_v2`.

Both routines display a dismissible admin notice confirming how many rows were removed.

---

## Notes for Future Development

- `ws_jurisdiction_type` select options can be extended to add `county` without breaking existing data. A separate CPT is recommended for county-level data rather than extending `jurisdiction`.
- `ws_legal_reviewer` is conditionally visible in ACF — it only appears when `ws_legal_review_completed` is checked.
- `[ws_legal_updates]` uses a LIKE meta query to match ACF relationship field data. If ACF changes how relationship fields are serialized, this query may need updating.
- All four `jx-*` addendum CPTs (`jx-resources`, `jx-procedures`, `jx-statutes`) are registered and visible in the admin but have no ACF field groups yet. They are placeholders for future content expansion.

---

## Contact Emails

`admin@whistleblowershield.org` — general contact (appears in `[ws_footer]` output).
`corrections@whistleblowershield.org` — corrections channel only. These two addresses must never appear on the same line or in the same sentence in any template or shortcode output.
