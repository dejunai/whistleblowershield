# Proposal: Jurisdiction Data Integrity Architecture

Status: Draft  
Target Plugin: ws-core  
Purpose: Maintain clean, normalized jurisdiction datasets.

---

## Background

The WhistleblowerShield project maintains a legal archive covering 57 U.S. jurisdictions:

- 50 states
- Federal jurisdiction
- District of Columbia
- 5 major U.S. territories

Each jurisdiction contains multiple datasets:

summary  
statutes  
procedures  
resources  
legal updates

Maintaining clean relationships between these datasets is critical for long-term scalability.

---

## Common Structural Mistake

Many legal reference websites attach large quantities of legal content directly to jurisdiction pages.

Example structure:

Jurisdiction Page  
- summary  
- statutes  
- procedures  
- resources

This approach creates problems:

- large monolithic records
- difficult editing workflows
- inability to version datasets
- poor maintainability
- limited future expansion

---

## Correct Architecture

The project should maintain **separate CPT records for each dataset**.

Example structure:

jurisdiction  
  â”œ jx_summary  
  â”œ jx_statutes  
  â”œ jx_procedures  
  â”œ jx_resources  
  â”” legal_updates

Each dataset record contains a relationship field pointing to its jurisdiction.

---

## Jurisdiction Code System

Each jurisdiction should contain a stable identifier field.

Field name:

jx_code

Values use USPS two-letter abbreviations.

Examples:

CA â€“ California  
NY â€“ New York  
TX â€“ Texas  
US â€“ Federal jurisdiction  
DC â€“ District of Columbia  
PR â€“ Puerto Rico  
GU â€“ Guam  
VI â€“ U.S. Virgin Islands  
AS â€“ American Samoa  
MP â€“ Northern Mariana Islands

This provides a permanent internal reference key.

---

## Advantages of a Jurisdiction Code

Titles may change.

Slugs may change.

The jurisdiction code remains stable.

Example usage:

ws_get_jx_by_code("CA")

This ensures reliable queries throughout the plugin.

---

## Relationship Field

Each dataset CPT must contain:

jurisdiction_ref

ACF type:

Post Object

Target:

jurisdiction

Example record:

Title: California Procedures  
jurisdiction_ref â†’ California

---

## Query Layer Responsibility

All dataset access should pass through the ws-core query layer.

Example functions:

ws_get_jx()  
ws_get_jx_by_code()  
ws_get_jx_summary()  
ws_get_jx_statutes()  
ws_get_jx_procedures()  
ws_get_jx_resources()  
ws_get_jx_updates()

Shortcodes and templates must not query ACF directly.

---

## Benefits

Maintains a normalized legal database.

Supports independent dataset updates.

Allows multiple legal updates per jurisdiction.

Improves long-term maintainability.

Provides clean architecture for a future legal archive backend.

---

## Implementation Location

Plugin:

ws-core

Relevant components:

includes/cpt  
includes/queries  
includes/shortcodes

---

End of Proposal
