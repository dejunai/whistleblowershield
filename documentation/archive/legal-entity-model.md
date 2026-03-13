# Legal Entity Model

## Overview

The Legal Entity Model defines the core legal objects represented within the WhistleblowerShield knowledge system.

The platform focuses on structured documentation of United States whistleblower protection laws. Each legal concept is represented as a discrete entity with defined relationships to other legal entities.

The model allows the system to represent legal frameworks in a way that supports research, verification, and long-term maintainability.

This document describes the primary legal entities and how they relate to one another.

---

## Design Goals

The legal entity model is designed to achieve several goals:

• Represent whistleblower law in structured form  
• Preserve traceable legal citations  
• Maintain relationships between statutes, agencies, and programs  
• Allow expansion as additional legal frameworks are documented  
• Support future query and analysis systems  

The system prioritizes legal clarity and data integrity over complexity.

---

## Core Legal Entities

### Statute

A statute is a law enacted by Congress and codified in the United States Code.

Statutes frequently establish whistleblower protections, enforcement authority, and reporting mechanisms.

Examples include:

• False Claims Act  
• Sarbanes-Oxley Act  
• Dodd-Frank Act whistleblower provisions  

Statutes often authorize regulatory agencies to implement rules.

---

### Regulation

Regulations are rules issued by federal agencies under statutory authority.

They are typically codified in the Code of Federal Regulations.

Regulations often clarify:

• reporting procedures  
• retaliation protections  
• enforcement mechanisms  
• eligibility for whistleblower programs

---

### Agency

Agencies administer whistleblower programs and enforce legal protections.

Examples include:

• Securities and Exchange Commission (SEC)  
• Commodity Futures Trading Commission (CFTC)  
• Department of Labor (OSHA)  
• Internal Revenue Service (IRS)

Agencies may enforce multiple statutes and regulatory frameworks.

---

### Whistleblower Program

Some agencies operate formal whistleblower reward or reporting programs.

Programs may define:

• eligibility criteria  
• reporting channels  
• monetary reward structures  
• confidentiality protections

Programs typically operate under statutory authority.

---

### Legal Update

Legal updates track significant developments affecting whistleblower law.

Examples include:

• statutory amendments  
• regulatory rule changes  
• major court decisions  
• policy changes by enforcement agencies

Legal updates help maintain current legal information within the platform.

---

## Entity Relationships

Legal entities interact through structured relationships.

Examples include:

Statute → authorizes → Agency

Agency → administers → Whistleblower Program

Agency → issues → Regulation

Regulation → implements → Statute

Legal Update → modifies → Statute or Regulation

These relationships allow the system to represent complex legal frameworks.

---

## Data Representation in ws-core

The ws-core plugin implements the legal entity model using WordPress Custom Post Types and structured metadata.

Each entity type corresponds to a structured content type with defined fields.

Relationships between entities are implemented through:

• post relationships  
• taxonomy connections  
• metadata fields

This structure allows WordPress to function as a structured legal knowledge database.

---

## Scope Limitations

The initial scope of the project focuses exclusively on United States federal whistleblower law.

Future expansion could include:

• state whistleblower protections  
• international whistleblower frameworks  
• judicial interpretations

Any expansion should maintain compatibility with the existing entity model.

---

## Future Evolution

The entity model may expand to include additional concepts such as:

• court decisions  
• enforcement actions  
• administrative procedures  
• regulatory guidance

Changes to the entity model should be carefully documented to preserve system integrity.

---

## Conclusion

The Legal Entity Model provides the conceptual structure that allows WhistleblowerShield to represent whistleblower law as structured legal knowledge.

By organizing statutes, regulations, agencies, and programs into a consistent model, the platform supports accurate documentation and long-term growth.
