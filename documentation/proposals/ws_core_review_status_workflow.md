# Proposal: Review Status Workflow System

Status: Draft  
Target Plugin: ws-core  
Purpose: Prevent accidental publication of incomplete legal information.

---

## Background

The WhistleblowerShield project is designed to publish reliable, plain-language summaries of whistleblower protections across 57 U.S. jurisdictions.

Because legal information must be carefully verified before publication, relying solely on WordPress default post states (draft, published) is insufficient.

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

âš  California statutes are marked "needs_review".

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
