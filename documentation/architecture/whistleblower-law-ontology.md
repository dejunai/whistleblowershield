# U.S. Whistleblower Law Ontology

## Purpose

This document defines the conceptual model for whistleblower law
used by the WhistleblowerShield platform.

An ontology describes how legal concepts relate to one another.
It is not tied to specific implementation details such as
WordPress post types.

The ontology helps ensure that the platform models the legal
domain accurately.

---

# Core Concept

## Whistleblower

A whistleblower is an individual who reports information
about violations of law, regulation, or public policy.

Relationships:

Whistleblower
 ├ reports → violation
 ├ may be protected by → statute
 ├ may report to → agency
 └ may receive → financial award

---

# Violation

A violation is conduct that breaks a law or regulation.

Examples:

fraud  
securities violations  
environmental violations  
tax fraud  

Relationships:

Violation
 ├ reported by → whistleblower
 ├ investigated by → agency
 └ governed by → statute

---

# Statute

A statute is a law enacted by a legislative authority.

Examples:

False Claims Act  
Sarbanes-Oxley Act  
Dodd-Frank Act  

Relationships:

Statute
 ├ protects → whistleblower
 ├ defines → violation
 ├ administered by → agency
 └ applies within → jurisdiction

---

# Agency

A government body responsible for enforcing laws
or receiving reports.

Examples:

Securities and Exchange Commission  
Department of Labor  
Internal Revenue Service  

Relationships:

Agency
 ├ receives → whistleblower reports
 ├ investigates → violations
 └ enforces → statutes

---

# Reporting Process

A reporting process defines how a whistleblower
submits information.

Examples:

internal reporting  
government reporting  
qui tam lawsuit  

Relationships:

Reporting Process
 ├ used by → whistleblower
 ├ handled by → agency
 └ governed by → statute

---

# Legal Protection

Legal protection refers to statutory protections
against retaliation.

Examples:

anti-retaliation provisions  
confidentiality protections  

Relationships:

Legal Protection
 ├ granted by → statute
 └ protects → whistleblower

---

# Financial Award

Some statutes allow whistleblowers to receive
financial rewards.

Examples:

SEC whistleblower awards  
False Claims Act qui tam awards  

Relationships:

Financial Award
 ├ granted under → statute
 └ received by → whistleblower

---

# Jurisdiction

A jurisdiction defines where a law applies.

Examples:

United States (federal)  
California  
Texas  

Relationships:

Jurisdiction
 ├ contains → statutes
 ├ governs → violations
 └ hosts → agencies