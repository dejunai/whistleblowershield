# ============================================================
# WhistleblowerShield
# Proposal Generator Script
#
# Generates proposal documents for ws-core administrative
# workflow improvements and jurisdiction data integrity.
#
# Target directory:
# documentation/proposals/
#
# Author: WS Project Automation
# ============================================================

$baseDir = "documentation/proposals"

if (!(Test-Path $baseDir)) {
    New-Item -ItemType Directory -Path $baseDir -Force | Out-Null
}

# ------------------------------------------------------------
# Proposal 1: Review Status Workflow
# ------------------------------------------------------------

$file1 = "$baseDir/ws_core_review_status_workflow.md"

$content1 = @"
# Proposal: Review Status Workflow System

Status: Draft  
Target Plugin: ws-core  
Purpose: Prevent accidental publication of incomplete legal information.

---

## Background

The WhistleblowerShield project is designed to publish reliable, plain-language summaries of whistleblower protections across 57 U.S. jurisdictions.

Because legal information must be carefully verified before publication, relying solely on WordPress default post states (`draft`, `published`) is insufficient.

A structured **Review Status workflow** allows datasets to move through verification stages before appearing on the public site.

---

## Problem

WordPress only provides:

- draft
- published

For legal datasets this creates risks:

- incomplete research may appear publicly
- statute citations may not be verified
- partial updates may be published accidentally
- inconsistent editorial discipline

Legal publishing systems typically maintain additional workflow states.

---

## Proposed Solution

Introduce a **Review Status field** for all jurisdiction dataset CPTs.

Field name:

review_status

Field type:

select

Allowed values:

draft  
needs_review  
verified

---

## Status Meaning

draft  
Content is incomplete and under active editing.

needs_review  
Research complete but awaiting verification.

verified  
Content has been reviewed and is safe to publish.

---

## CPTs Using Review Status

The field should be applied to the following post types:

jx_summary  
jx_statutes  
jx_procedures  
jx_resources  
legal_update

---

## Public Rendering Rule

The ws-core query layer should enforce:

Only records marked **verified** appear on public pages.

Pseudo logic:

if review_status != verified  
    do not render

This prevents accidental exposure of unfinished legal research.

---

## Admin UI Improvements

Add a Review Status column in WordPress admin list tables.

Example:

Title                    Jurisdiction     Review Status  
-------------------------------------------------------  
California Summary       California       verified  
Texas Summary            Texas            draft  
New York Summary         New York         needs_review

This allows fast visual scanning of dataset readiness.

---

## Optional Admin Warning

When editing a jurisdiction page in WordPress admin:

If a related dataset is not verified, display a notice.

Example:

⚠ California statutes are marked "needs_review".

This prevents publishing jurisdiction pages containing unverified sections.

---

## Benefits

Prevents accidental publication of incomplete legal content.

Provides a lightweight editorial workflow suitable for a solo maintainer.

Improves reliability of the legal archive.

Supports disciplined research processes.

---

## Implementation Location

Plugin:

ws-core

Recommended file:

includes/system/review-status.php

Responsibilities:

- register ACF field
- enforce rendering rules
- add admin list column
- display optional warnings

---

End of Proposal
"@

Set-Content -Path $file1 -Value $content1 -Encoding UTF8


# ------------------------------------------------------------
# Proposal 2: Jurisdiction Data Integrity Architecture
# ------------------------------------------------------------

$file2 = "$baseDir/ws_core_jurisdiction_data_integrity.md"

$content2 = @"
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
  ├ jx_summary  
  ├ jx_statutes  
  ├ jx_procedures  
  ├ jx_resources  
  └ legal_updates

Each dataset record contains a relationship field pointing to its jurisdiction.

---

## Jurisdiction Code System

Each jurisdiction should contain a stable identifier field.

Field name:

jx_code

Values use USPS two-letter abbreviations.

Examples:

CA – California  
NY – New York  
TX – Texas  
US – Federal jurisdiction  
DC – District of Columbia  
PR – Puerto Rico  
GU – Guam  
VI – U.S. Virgin Islands  
AS – American Samoa  
MP – Northern Mariana Islands

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
jurisdiction_ref → California

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
"@

Set-Content -Path $file2 -Value $content2 -Encoding UTF8


Write-Host ""
Write-Host "Proposal documents created:"
Write-Host $file1
Write-Host $file2
Write-Host ""
Write-Host "Next step: ws-core plugin refactor."