# ws-core Data Schema

## Purpose

This document defines the structured data schema used by the ws-core
plugin.

The schema is implemented using WordPress Custom Post Types and
Advanced Custom Fields (ACF).

The goal is to ensure that all legal data in the WhistleblowerShield
platform is stored in a structured, consistent format.

---

# Core Entity

## jurisdiction

Represents a legal authority.

Examples:

United States  
California  
European Union

### Fields

| Field | Type | Description |
|-----|-----|-----|
| name | text | Jurisdiction name |
| region | text | Geographic region |
| governing_body | text | Primary governing authority |
| notes | textarea | Internal notes |

### Relationships

jurisdiction is the root object.

Other entities reference it.

---

# jx-summary

Provides a narrative overview of whistleblower law
in a jurisdiction.

### Fields

| Field | Type | Description |
|-----|-----|-----|
| jurisdiction | relationship | Links to jurisdiction |
| overview | wysiwyg | Human readable legal summary |
| citations | textarea | Legal citations |
| review_status | select | draft / reviewed / published |

---

# jx-statute

Represents a formal legal statute.

### Fields

| Field | Type | Description |
|-----|-----|-----|
| jurisdiction | relationship | Parent jurisdiction |
| statute_name | text | Name of statute |
| citation | text | Legal citation |
| official_source | url | Link to official text |
| notes | textarea | Internal commentary |

---

# jx-procedure

Describes how a whistleblower can report violations.

### Fields

| Field | Type | Description |
|-----|-----|-----|
| jurisdiction | relationship | Parent jurisdiction |
| reporting_agency | text | Responsible agency |
| procedure_description | wysiwyg | Reporting instructions |
| official_guidance | url | Government guidance link |

---

# jx-resource

External resources useful to whistleblowers.

### Fields

| Field | Type | Description |
|-----|-----|-----|
| jurisdiction | relationship | Parent jurisdiction |
| organization_name | text | Resource provider |
| resource_type | select | legal aid / government / advocacy |
| resource_link | url | Official resource link |

---

# ws-legal-update

Represents a change in law or policy.

### Fields

| Field | Type | Description |
|-----|-----|-----|
| jurisdiction | relationship | Parent jurisdiction |
| law_name | text | Name of law |
| update_summary | wysiwyg | Description of change |
| source_url | url | Source of legal update |
| effective_date | date | Date law takes effect |

---

# Data Integrity Rules

1. Every entity must reference a jurisdiction.
2. Jurisdiction relationships must use ACF relationship fields.
3. Required fields should be enforced in ACF configuration.
4. Slugs should follow WordPress conventions.

---

# Slug Conventions

Internal identifiers use underscores.

Example:

ws_legal_update

Public slugs use hyphens.

Example:

ws-legal-update

---

# Future Schema Extensions

Possible additions:

case-law  
regulatory-agency  
enforcement-action  
legal-commentary

These entities would follow the same jurisdiction relationship model.
