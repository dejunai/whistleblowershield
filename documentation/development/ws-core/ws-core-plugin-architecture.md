# ws-core Plugin Architecture

## Purpose

The ws-core plugin is the primary application layer of the
WhistleblowerShield platform.

It defines:

- legal data structures
- WordPress post types
- ACF schema
- rendering logic
- editorial workflows

WordPress acts as a framework, while ws-core defines the
domain model for whistleblower law.

---

## Architectural Layers

Presentation Layer
Rendering templates and shortcodes.

Application Layer
Plugin logic, validation, and relationships.

Data Layer
WordPress posts + ACF structured data.

---

## Responsibilities

ws-core is responsible for:

- registering Custom Post Types
- defining data relationships
- managing legal updates
- rendering jurisdiction pages
- enforcing naming conventions

---

## Non-Responsibilities

ws-core does NOT manage:

- site theming
- CSS styling
- analytics
- infrastructure

These are handled by other components.
