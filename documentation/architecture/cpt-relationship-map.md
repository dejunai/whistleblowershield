# CPT Relationship Map

## Purpose

This document defines the relationships between the Custom Post Types
used by the ws-core plugin.

The goal is to ensure that legal data is structured consistently and
that all entities connect through a clear jurisdiction model.

---

# Core Entity

The root entity in the system is the **Jurisdiction**.

Every legal object in the system must connect to a jurisdiction.

Example:

Jurisdiction
 â”œ Summary
 â”œ Statutes
 â”œ Procedures
 â”œ Resources
 â”” Legal Updates

---

# Custom Post Types

## jurisdiction

Represents a legal authority.

Examples:

United States  
California  
European Union

Relationships:

- has many summaries
- has many statutes
- has many procedures
- has many resources
- has many legal updates

---

## jx-summary

Human-readable explanation of whistleblower law
in a jurisdiction.

Relationships:

jx-summary â†’ jurisdiction

---

## jx-statute

Represents a legal statute or law.

Relationships:

jx-statute â†’ jurisdiction

---

## jx-procedure

Describes how a whistleblower can report violations.

Relationships:

jx-procedure â†’ jurisdiction

---

## jx-resource

External resource relevant to whistleblowers.

Relationships:

jx-resource â†’ jurisdiction

---

## ws-legal-update

Represents a change in law or policy.

Relationships:

ws-legal-update â†’ jurisdiction

---

# Relationship Overview

jurisdiction
 â”œ jx-summary
 â”œ jx-statute
 â”œ jx-procedure
 â”œ jx-resource
 â”” ws-legal-update

---

# Future Extensions

Possible future CPTs:

case-law  
regulatory-agency  
enforcement-action  
legal-commentary
