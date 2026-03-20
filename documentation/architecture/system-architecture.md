# System Architecture

This document provides a high-level view of how the system is structured and how its major components interact.

It is intended as an overview, not a detailed specification.

---

## Overview

The system is composed of several interconnected layers:

- legal system model (conceptual structure)  
- ws-core system (data and internal logic)  
- editorial system (content creation and standards)  
- guidance system (user-facing interpretation)  

These layers work together to transform legal information into usable guidance.

---

## Layer Interaction

### Legal System Model → ws-core

The legal system model defines how legal information is structured conceptually.

ws-core implements this structure in a usable form:

- translating concepts into data entities  
- defining relationships in a structured way  
- supporting jurisdiction-aware data  

---

### ws-core → Editorial System

The editorial system works with structured data provided by ws-core.

- content is written based on defined concepts  
- relationships in the data inform how content is organized  
- structure supports consistency across content  

---

### Editorial System → Guidance System

The guidance system builds on editorial content.

- content is organized around user scenarios  
- explanations are adapted to user needs  
- structure supports navigation and understanding  

---

### Guidance System → User

The guidance system presents information in a way that:

- reflects real-world situations  
- supports understanding and decision-making  
- maintains a connection to underlying legal sources  

---

## Data Flow

At a high level, the system follows this flow:

1. Legal concepts and sources are defined (legal system model)  
2. Data is structured and stored (ws-core data layer)  
3. Data is retrieved and assembled (ws-core query layer)  
4. Data is rendered (ws-core output layer)  
5. Content and guidance are presented to the user  

This flow is not strictly linear and may loop or evolve over time.

---

## Separation of Concerns

Each layer has a distinct role:

- **Legal system model** — conceptual structure  
- **ws-core** — implementation and internal logic  
- **Editorial system** — content creation  
- **Guidance system** — user-facing interpretation  

The boundaries are not rigid, but help maintain clarity.

---

## Flexibility

The architecture is intentionally flexible.

- layers may evolve independently  
- structure may be refined over time  
- responsibilities may shift where needed  

Strict enforcement of boundaries is not required.

---

## Scope

This document is intended to:

- provide a mental model of the system  
- show how major parts relate  
- support navigation of the documentation  

It is not intended to define implementation details.

---

## Ongoing Refinement

The architecture is expected to evolve.

- relationships between layers may be adjusted  
- structure may be simplified or expanded  
- inconsistencies may be resolved over time  

The goal is clarity and usability, not a fixed design.