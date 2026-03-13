# Proposal: ws-core Plugin Refactor Plan

Purpose: Improve readability, maintainability, and scalability of the ws-core plugin.

Status: Draft

---

## Goals

Refactor the plugin code to:

- improve readability
- organize functions logically
- introduce a query layer
- standardize naming
- improve documentation headers

---

## Major Improvements

### Query Layer

Shortcodes should not access ACF directly.

Instead:

shortcode â†’ query function â†’ ACF

Example:

ws_get_jx_summary()

---

### File Organization

Recommended structure:

includes/
    cpt/
    fields/
    queries/
    shortcodes/
    system/

---

### Variable Naming

Use descriptive variable names tied to the legal model.

Examples:

\
\
\

---

### Jurisdiction Code

Add field:

jx_code

Example values:

us-ca
us-ny
us-fed

This allows stable referencing of jurisdictions.

---

### Documentation Headers

All files should contain header blocks describing:

purpose
version
update date

---

### Version Update

Plugin version should be incremented to:

2.1.0

---

End of proposal.
