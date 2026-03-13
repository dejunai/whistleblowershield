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
 â”œ summary
 â”œ statutes
 â”œ procedures
 â”œ resources
 â”” legal updates

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

ws_legal_update â†’ jurisdiction  
statute â†’ jurisdiction  
procedure â†’ jurisdiction

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
