# Architectural Proposal: Decommissioning `jx-procedures` in Favor of `ws-agencies`

**Status:** Proposed  
**Author:** Dejunai / Gemini  
**Date:** 2026-03-14  
**Related Components:** `ws-core`, `ws-agencies`, `jx-statutes`

## 1. Executive Summary
This proposal suggests the removal of the `jx-procedures` Custom Post Type (CPT) and the associated `jx-procedures` stub. Analysis of the project goals for *whistleblowershield.org* indicates that "Procedures" are almost always specific to a "Reporting Agency." By integrating procedural data directly into the `ws-agencies` CPT, we reduce database overhead, simplify the user experience, and eliminate data fragmentation.

## 2. Problem Statement
The current architecture treats "Procedures" as a top-level object. However, a whistleblower's primary need is identifying **where** to report (Agency). Managing a separate post type for "How to Report" creates several issues:
- **Redundancy:** Agency contact info and their specific intake procedures are split across two different records.
- **Query Complexity:** Displaying a single jurisdiction page requires an additional `WP_Query` to fetch a procedure that is already conceptually linked to an agency.
- **Maintenance:** Updates to an agency's web portal often require updating both the Agency record and the Procedure record.

## 3. Proposed Solution: The Agency-Centric Model
We will pivot to an **Entity-Driven Architecture**. The `ws-agencies` CPT will become the primary source of truth for both the identity of the oversight body and the mechanics of reporting to it.

### 3.1 Data Migration Strategy
- **Fields:** Relevant procedural text from the old stub will be moved into a new `ws_agency_steps` (WYSIWYG) field in `acf-agencies.php`.
- **Classification:** Agencies will continue to use the `ws_disclosure_cat` taxonomy to define *what* they handle, which implicitly defines *which* procedure applies.
- **Statute Linking:** If a statute (e.g., False Claims Act) has a unique legal procedure (e.g., *Qui Tam* filing), that text will be housed in a "Procedural Requirements" field within the `jx-statutes` record rather than a separate procedure post.

## 4. Technical Impact
- **Database:** Deletion of `jx-procedures` table entries and removal of `register_post_type` logic from `ws-core`.
- **Performance:** Reduced complexity in the jurisdiction page "assembler" function.
- **UI/UX:** A cleaner admin sidebar and a more cohesive "Reporting Profile" for the end-user.

## 5. Decision Logic (Perl/PHP Developer Perspective)
From a "hacker" standpoint, we are normalizing the database. We are moving from a 1:1 or 1:M relationship between Jurisdictions and Procedures to a more logical Relationship model where:
`Jurisdiction` -> `Statutes` -> `Agencies (with embedded procedures)`.

## 6. Implementation Steps
1. [ ] Update `acf-agencies.php` to include a "Step-by-Step Instructions" field.
2. [ ] Audit existing procedures in `/documentation/` and map them to their respective Agency Codes.
3. [ ] Remove `cpt-procedures.php` from the `ws-core` plugin.
4. [ ] Update frontend templates to pull instructions directly from the agency meta.

---
*Note: This change reflects a shift toward "Actionable Intelligence" for whistleblowers—prioritizing the destination (Agency) over the abstract legal process.*