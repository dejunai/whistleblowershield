# Legal System Model

This document describes how legal information is conceptually structured within the project.

It defines how laws, concepts, jurisdictions, and sources relate to each other in a way that supports consistent storage, querying, and guidance.

---

## Purpose

The legal system model exists to:

- provide a coherent structure for legal information  
- define relationships between legal concepts  
- support jurisdiction-aware organization  
- enable consistent interpretation across the system  

---

## Core Approach

Legal information is treated as structured data rather than unstructured text.

The model emphasizes:

- identifiable entities  
- explicit relationships  
- consistent categorization  
- traceability to legal sources  

These ideas guide the system, but are not rigid constraints.

---

## Key Components

The model is composed of several closely related parts:

- concepts (what something is)  
- categories (how things are grouped)  
- relationships (how things connect)  
- sources (where information comes from)  
- jurisdictions (where it applies)  

These are described below.

---

## Legal Concepts

Concepts represent discrete legal ideas.

Examples include:

- protected activity  
- retaliation  
- whistleblower status  

Concepts are:

- reusable across jurisdictions  
- independent of any single law  
- connected to other concepts through relationships  

---

## Categorization

Concepts and entities may be grouped into categories.

Categories help:

- organize related concepts  
- support navigation and querying  
- provide structure without defining meaning  

Categories are flexible and may evolve over time.

---

## Relationships

Relationships define how entities connect.

Examples include:

- one concept depends on another  
- a law defines or modifies a concept  
- a concept applies within a jurisdiction  

Relationships should be:

- explicit where practical  
- consistent in representation  
- usable by the data and query layers  

---

## Jurisdiction

Jurisdiction is a primary organizing dimension.

Legal information may vary by:

- country  
- state or region  
- regulatory authority  

The model should:

- clearly associate information with its jurisdiction  
- support comparison across jurisdictions  
- avoid conflating rules from different jurisdictions  

---

## Legal Sources

Sources represent the origin of legal information.

Examples include:

- statutes  
- regulations  
- official materials  

Sources are used to:

- support accuracy  
- enable traceability  
- ground concepts in real legal text  

Not all content needs to directly expose sources, but the connection should exist.

---

## Citation

Citations provide structured references to legal sources.

They should:

- identify the source clearly  
- allow retrieval or verification  
- remain consistent in format where practical  

Citations support traceability but do not need to follow a rigid standard.

---

## Concept vs. Law

The model distinguishes between:

- **concepts** (general legal ideas)  
- **laws** (jurisdiction-specific implementations)  

A single concept may:

- exist across multiple jurisdictions  
- be defined differently depending on the law  

This distinction allows the system to:

- reuse concepts  
- handle variation without duplication  

---

## Alignment with Data Layer

The legal system model is conceptual.

It informs:

- the data model  
- the schema  
- relationships between entities  

It does not define implementation details directly, but should align with them.

---

## Flexibility

The model is not fixed.

- concepts may be added or refined  
- categories may change  
- relationships may be adjusted  

Perfect consistency is not required.

---

## Ongoing Refinement

The model is expected to evolve.

- definitions may shift over time  
- structure may be simplified or expanded  
- inconsistencies may be resolved gradually  

The goal is a usable and coherent system, not a perfectly formal ontology.