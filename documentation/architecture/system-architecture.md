# System Architecture

## Overview

WhistleblowerShield is a legal knowledge platform designed to organize, verify, and publish United States whistleblower protection laws. The system architecture is intentionally modular, allowing legal data, editorial workflow, and presentation layers to evolve independently.

The platform is implemented using WordPress with a custom plugin (ws-core) that defines the legal data model and system behavior.

The architecture separates concerns into four major layers:

1. Legal Knowledge Layer
2. Data Management Layer
3. Editorial Layer
4. Presentation Layer

## Legal Knowledge Layer

This layer defines the ontology and taxonomy used to describe whistleblower laws.

Key components include:

- Whistleblower Law Ontology
- Whistleblower Law Taxonomy
- Jurisdiction Scope Model
- Legal Citation Model
- Source Verification Policy

These documents define the conceptual model for how legal knowledge is represented within the system.

## Data Management Layer

The ws-core plugin implements the legal data model using WordPress Custom Post Types and structured metadata.

Core entities include:

- Statutes
- Regulations
- Agencies
- Programs
- Legal Updates

Each entity is connected through defined relationships documented in the CPT Relationship Map.

## Editorial Layer

The editorial layer governs how legal information enters and is validated within the system.

Editorial functions include:

- Legal research
- Source verification
- Content drafting
- Editorial review
- Publication

These processes ensure legal accuracy and transparency.

## Presentation Layer

The presentation layer exposes structured legal data to end users.

Initial presentation mechanisms include:

- Shortcodes
- Structured pages
- Knowledge articles

Future implementations may include:

- Block-based layouts
- API access
- Advanced search interfaces

## Design Goals

The architecture prioritizes:

- legal accuracy
- traceable sources
- structured data
- extensibility
- long-term maintainability
