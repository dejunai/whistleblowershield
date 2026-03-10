# ws-core Data Integrity Rules

## Purpose

This document defines rules that ensure the WhistleblowerShield
dataset remains accurate, consistent, and legally credible.

These rules should eventually be enforced by the ws-core plugin.

---

# Core Rule

Every legal entity must reference a jurisdiction.

No legal content should exist without jurisdiction context.

---

# Jurisdiction Rules

A jurisdiction must represent a real legal authority within the
United States legal system.

Examples:

United States (federal)
California
Texas
New York

Jurisdictions should not duplicate each other.

Example of incorrect duplication:

California
State of California

Only one canonical jurisdiction record should exist.

---

# Summary Rules

Each jurisdiction should normally have one primary summary.

Additional summaries may exist for:

- historical context
- specialized analysis

Summaries must include citations to primary legal sources.

---

# Statute Rules

Statutes must include:

- statute name
- citation
- official source link

Statutes should reference the jurisdiction where they apply.

Example:

False Claims Act → United States

---

# Procedure Rules

Procedures must describe the reporting process in clear,
step-by-step language.

Procedures should include:

- responsible agency
- reporting mechanism
- official instructions link

---

# Resource Rules

Resources should only include credible organizations.

Examples:

government agencies
legal aid organizations
recognized advocacy groups

Resources should not include promotional or commercial content.

---

# Legal Update Rules

Every legal update must include:

- law name
- summary of change
- source URL
- effective date

Updates should reference the jurisdiction where the change occurred.

---

# Duplicate Prevention

Duplicate entities should be avoided.

Before creating a new entry, editors should verify that a similar
record does not already exist.

---

# Future Enforcement

These rules may later be enforced through:

plugin validation
editorial review workflow
automated integrity checks