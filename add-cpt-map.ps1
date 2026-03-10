Write-Host "Creating CPT relationship map documentation..."

$path = "documentation/architecture/cpt-relationship-map.md"

New-Item -ItemType Directory -Force -Path "documentation/architecture" | Out-Null

$content = @"
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
 ├ Summary
 ├ Statutes
 ├ Procedures
 ├ Resources
 └ Legal Updates

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

jx-summary → jurisdiction

---

## jx-statute

Represents a legal statute or law.

Relationships:

jx-statute → jurisdiction

---

## jx-procedure

Describes how a whistleblower can report violations.

Relationships:

jx-procedure → jurisdiction

---

## jx-resource

External resource relevant to whistleblowers.

Relationships:

jx-resource → jurisdiction

---

## ws-legal-update

Represents a change in law or policy.

Relationships:

ws-legal-update → jurisdiction

---

# Relationship Overview

jurisdiction
 ├ jx-summary
 ├ jx-statute
 ├ jx-procedure
 ├ jx-resource
 └ ws-legal-update

---

# Future Extensions

Possible future CPTs:

case-law  
regulatory-agency  
enforcement-action  
legal-commentary
"@

$content | Out-File -Encoding utf8 $path

Write-Host "Adding file to git..."

git add $path

Write-Host "Creating commit..."

git commit -m "Add CPT relationship map documentation"

Write-Host "Pushing to GitHub..."

git push

Write-Host "CPT relationship map added."