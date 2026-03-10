Write-Host "Creating design documentation..."

$files = @{

"documentation/development/ws-core/ws-core-design-principles.md" = @"
# ws-core Design Principles

## Purpose

The ws-core plugin is the core application layer powering the
WhistleblowerShield platform.

It is responsible for defining structured legal data, registering
content types, and rendering jurisdiction-based legal information.

This document defines the guiding principles used when developing
the plugin.

---

## Core Philosophy

The plugin exists to model **legal knowledge**, not just manage content.

WordPress is used as a framework, but the plugin itself defines
the domain model for the platform.

---

## Design Goals

### Structured Legal Data

All important legal information should exist as structured data
rather than free-form text.

This allows:

- search
- comparison across jurisdictions
- future automation

---

### Jurisdiction-Centric Architecture

The primary object in the system is the **jurisdiction**.

All other information attaches to it.

Example:

Jurisdiction
 ├ summary
 ├ statutes
 ├ procedures
 ├ resources
 └ legal updates

---

### Clear Naming Conventions

Internal identifiers use underscores.

Examples:

ws_legal_update  
ws_jurisdiction

Public slugs use hyphens.

Examples:

ws-legal-update  
jurisdiction

---

### Separation of Responsibilities

The plugin should separate responsibilities across modules.

Expected module boundaries:

bootstrap  
post-types  
acf-schema  
shortcodes  
rendering  
audit-trail

Each module should perform a clearly defined task.

---

### WordPress as Framework

WordPress provides:

- user management
- database abstraction
- content storage
- admin UI

The plugin defines the **domain logic**.

---

### Data Integrity

Data relationships should always be explicit.

ACF relationship fields should be used to connect entities.

Implicit assumptions should be avoided.

---

### Documentation First

Major architectural decisions should be documented in the repository
before significant code changes are implemented.

This ensures the system remains understandable as it grows.
"@

"documentation/architecture/jurisdiction-knowledge-model.md" = @"
# Jurisdiction Knowledge Model

## Purpose

This document defines the core legal knowledge structure used
by the WhistleblowerShield platform.

The platform organizes information around **jurisdictions**.

Each jurisdiction acts as a root node in the legal knowledge graph.

---

## Core Entity: Jurisdiction

A jurisdiction represents a legal authority.

Examples include:

United States  
California  
European Union

Each jurisdiction contains multiple types of information.

---

## Knowledge Structure

Jurisdiction
 ├ summary
 ├ statutes
 ├ procedures
 ├ resources
 └ legal updates

Each component represents a different aspect of
whistleblower protection law.

---

## Summaries

Summaries provide a human-readable explanation of
whistleblower protections in a jurisdiction.

They include:

overview text  
citations  
review status  
author metadata

---

## Statutes

Statutes represent formal legal authorities.

They may include:

statute name  
citation  
link to official text  
notes

---

## Procedures

Procedures describe how a whistleblower can report violations.

Examples:

agency reporting process  
internal reporting options  
external reporting channels

---

## Resources

Resources include external materials useful to whistleblowers.

Examples:

government guidance  
advocacy organizations  
legal assistance resources

---

## Legal Updates

Legal updates track changes in whistleblower law.

Each update contains:

law name  
summary of change  
source citation  
effective date

These updates help keep jurisdiction summaries current.

---

## Relationships

Entities are connected using structured relationships.

Example:

ws_legal_update → jurisdiction  
statute → jurisdiction  
procedure → jurisdiction

This structure allows future features such as:

cross-jurisdiction comparison  
legal change tracking  
search and filtering

---

## Future Expansion

Additional entities may be introduced later.

Possible future entities include:

case law  
regulatory agencies  
enforcement actions

The jurisdiction model is designed to support these expansions.
"@
}

foreach ($path in $files.Keys) {
    $dir = Split-Path $path
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $files[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add .

Write-Host "Creating commit..."

git commit -m "Add ws-core design principles and jurisdiction knowledge model"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Documentation added."