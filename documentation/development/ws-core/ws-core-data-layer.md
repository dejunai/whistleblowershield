# ws-core Data Layer

This document describes how data is structured and stored within the ws-core system.

The data layer defines the shape of the system and serves as the foundation for all other layers.

---

## Purpose

The data layer exists to:

- define structured representations of legal information  
- support relationships between entities  
- enable consistent querying and output  
- provide a stable foundation for the system  

---

## Relationship to Other Layers

- The **data layer** defines structure and storage  
- The **query layer** retrieves and assembles data  
- The **output layer** renders it  

All higher-level behavior depends on the integrity of the data layer.

---

## Core Concepts

The data layer is built around a small set of core ideas:

- information is stored in structured entities  
- relationships between entities are explicit  
- jurisdiction is a primary organizing dimension  
- data should be reusable across multiple contexts  

These concepts guide how data is modeled, but are not rigid constraints.

---

## Data Model

The data model describes how information is organized conceptually.

It includes:

- entities (e.g., laws, concepts, jurisdictions)  
- attributes associated with those entities  
- relationships between entities  

The model is designed to reflect real-world legal structure while remaining practical to work with.

---

## Data Schema

The schema defines how the data model is implemented.

This includes:

- field definitions  
- data types  
- required and optional values  

The schema should:

- support the data model  
- remain consistent across similar entities  
- avoid unnecessary complexity  

---

## Custom Post Types (CPTs)

Custom Post Types are used to represent primary entities within the system.

Each CPT:

- corresponds to a specific type of data  
- groups related fields and relationships  
- provides a consistent structure for content  

CPT design should reflect the underlying data model rather than editorial convenience.

---

## Field Structure (ACF)

Advanced Custom Fields (ACF) are used to define field-level structure.

Fields are used to:

- store attributes of entities  
- define relationships between entities  
- enforce basic structure  

Field design should:

- remain consistent across similar use cases  
- avoid duplication where possible  
- support both querying and output needs  

---

## Relationships

Relationships between entities are a core part of the system.

Examples include:

- jurisdictional relationships  
- connections between legal concepts  
- links between laws and guidance  

Relationships should be:

- explicit where possible  
- consistent in how they are represented  
- usable by the query layer without excessive transformation  

---

## Data Integrity

Data integrity is important but not rigidly enforced.

The system should aim to:

- avoid conflicting or ambiguous data  
- maintain consistency where practical  
- reduce duplication where it causes confusion  

Perfect enforcement is not required, but obvious issues should be avoided.

---

## Alignment with Other Systems

The data layer should align with:

- the legal system model (conceptual structure)  
- the query layer (data retrieval patterns)  
- the output layer (presentation needs)  

It does not need to perfectly mirror any one layer, but should not conflict with them.

---

## Flexibility

The data layer should allow for:

- gradual refinement of the model  
- adjustment as new requirements emerge  
- expansion of entities and relationships  

Rigid structure is not required.

---

## Ongoing Refinement

The data layer is expected to evolve.

- entities may be adjusted  
- relationships may be refined  
- structure may be simplified or expanded  

The goal is to maintain a usable and coherent system without over-complicating it.