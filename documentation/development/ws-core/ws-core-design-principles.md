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
 â”œ summary
 â”œ statutes
 â”œ procedures
 â”œ resources
 â”” legal updates

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
