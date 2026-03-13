# Proposal: Jurisdiction Parent Relationship & Dashboard Improvements

## Status
Proposed

## Author
WhistleblowerShield Development

## Purpose

This proposal introduces a standardized relationship field linking all jurisdiction datasets to their parent jurisdiction record. It also proposes improvements to the internal dashboard used to track dataset completeness across jurisdictions.

The goal is to improve:

- data integrity
- editorial workflow
- automated page rendering
- long-term maintainability of the legal archive

This change supports the project's long-term architecture while maintaining the site's commitment to simple public-facing content.

---

# Part 1 â€” Jurisdiction Parent Relationship Field

## Field Name

ws_jx_parent

## Field Type

ACF Post Object

## Target Post Type

jurisdiction

## Required

Yes

## Example Definition

`

ws_jx_parent
type: post_object
target: jurisdiction
required: true
return: id

`

---

## Dataset CPTs Using This Field

The following dataset post types will include the ws_jx_parent field.

`

jx_summary
jx_procedures
jx_statutes
jx_resources

`

Each dataset record must reference exactly one jurisdiction.

---

## Data Model

The system becomes:

`

jurisdiction

â†‘
â”‚ ws_jx_parent
â”‚
â”œ jx_summary
â”œ jx_procedures
â”œ jx_statutes
â”” jx_resources

`

Each dataset record belongs to a single jurisdiction.

---

## Benefits

### 1. Data Integrity

Prevents orphan records or incorrectly assigned datasets.

### 2. Simplified Queries

Example query for retrieving a summary:

`

post_type: jx_summary
meta_key: ws_jx_parent
value: jurisdiction_id

`

This allows the renderer to reliably locate the correct dataset.

### 3. Automated Rendering

Jurisdiction pages can automatically render sections based on dataset availability.

Example logic:

`

if summary exists â†’ render summary
if procedures exist â†’ render procedures
if statutes exists â†’ render statutes
if resources exists â†’ render resources

`

This eliminates manual shortcode insertion when building jurisdiction pages.

---

# Part 2 â€” Jurisdiction Dashboard Improvements

The current dashboard provides a list of jurisdictions.

This proposal expands the dashboard to show dataset completeness.

---

## Proposed Dashboard Layout

`

## Jurisdiction        Summary   Procedures   Statutes   Resources

California            âœ“          âœ“          âœ“          âœ“
Texas                 âœ“          âœ“          âœ—          âœ“
New York              âœ“          âœ—          âœ—          âœ“

`

---

## Benefits

### Editorial Visibility

Editors can instantly identify missing datasets.

### Data Quality Control

Prevents incomplete jurisdiction pages from being overlooked.

### Efficient Development

Especially valuable as the project approaches the full set of:

`

57 jurisdictions

`

Including:

- 50 U.S. states
- Federal government
- District of Columbia
- 5 major U.S. territories

---

# Alignment with Project Vision

The WhistleblowerShield platform is designed to provide:

- plain-English guidance for whistleblowers
- reliable legal summaries
- structured archival data for research

The ws_jx_parent relationship supports this architecture by ensuring that all datasets are correctly linked to the jurisdiction they describe.

This allows the public website to remain simple while the underlying system evolves into a structured legal archive.

---

# Implementation Summary

Add the field:

`

ws_jx_parent

`

to the following ACF definitions:

`

acf-jx-summary.php
acf-jx-procedures.php
acf-jx-statutes.php
acf-jx-resources.php

`

Update the jurisdiction dashboard to show dataset completion status.

---

# Impact

Low implementation risk.

Minimal code changes.

High long-term benefit for maintainability and scalability.

---

# Recommendation

Adopt the ws_jx_parent relationship model across all jurisdiction dataset CPTs and upgrade the dashboard to track dataset completeness.

This provides a stable foundation for managing the full set of 57 U.S. jurisdictions within the WhistleblowerShield legal archive.

---
