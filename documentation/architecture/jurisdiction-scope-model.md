# Jurisdiction Scope Model

## Purpose

This document defines the jurisdiction hierarchy used by
WhistleblowerShield and establishes the current scope of coverage.

The platform focuses exclusively on United States law.

---

## Current Scope

WhistleblowerShield covers 57 U.S. jurisdictions:

- 50 states
- Federal jurisdiction
- District of Columbia
- 5 U.S. territories

All 57 jurisdictions are active categories in the platform.
Coverage of each jurisdiction varies by development phase —
not all jurisdictions have published content at any given time,
but all are within scope.

---

## Jurisdiction Hierarchy

The U.S. legal system operates across multiple levels.
Whistleblower protections may exist at each level.

```
United States
 ├ Federal
 ├ States (50)
 │   ├ California
 │   ├ Texas
 │   ├ New York
 │   └ Other states...
 ├ District of Columbia
 └ U.S. Territories (5)
     ├ Puerto Rico
     ├ Guam
     ├ U.S. Virgin Islands
     ├ American Samoa
     └ Northern Mariana Islands
```

---

## Federal Jurisdiction

Federal laws apply across the entire United States.

Examples:

- False Claims Act
- Sarbanes-Oxley Act
- Dodd-Frank Act
- Whistleblower Protection Act

Federal agencies administer whistleblower programs under
statutory authority.

Examples:

- SEC (Securities and Exchange Commission)
- OSHA (Occupational Safety and Health Administration)
- IRS (Internal Revenue Service)
- OSC (Office of Special Counsel)

---

## State Jurisdictions

Each of the 50 states may implement its own whistleblower
protections. These laws typically apply only within the state
and vary significantly in scope, coverage, and remedies.

Examples:

- California Labor Code § 1102.5
- Texas Government Code § 554.002
- New York Labor Law § 740

---

## District of Columbia

The District of Columbia maintains its own whistleblower
protections applicable to employees within D.C., independent
of both federal law and state law frameworks.

---

## U.S. Territories

The five major U.S. territories — Puerto Rico, Guam, the
U.S. Virgin Islands, American Samoa, and the Northern Mariana
Islands — are within scope. Whistleblower protections in
territories may derive from federal law, territorial law,
or both.

Territory coverage uses USPS two-letter abbreviations
consistent with the jurisdiction code system:

- PR — Puerto Rico
- GU — Guam
- VI — U.S. Virgin Islands
- AS — American Samoa
- MP — Northern Mariana Islands

---

## Jurisdiction Relationships

Legal entities reference the jurisdiction where the law applies.

Example relationships:

- False Claims Act → Federal
- California Labor Code § 1102.5 → California
- Puerto Rico Law 426 → Puerto Rico

---

## Multi-Jurisdiction Laws

Some federal laws apply across all jurisdictions. In these cases,
the law is attached to the Federal jurisdiction as the primary
originating authority.

State and territory pages may reference relevant federal
protections where they interact with or supplement local law.

---

## Jurisdiction Code System

Each jurisdiction record uses a stable identifier field (jx_code)
based on USPS two-letter abbreviations.

Examples:

- CA — California
- TX — Texas
- US — Federal jurisdiction
- DC — District of Columbia
- PR — Puerto Rico

This code provides a permanent internal reference key that
remains stable even if jurisdiction titles or slugs change.

---

## Out of Scope

The following are not currently within scope:

- International jurisdictions
- Municipal or county laws
- Special regulatory jurisdictions below the state level

These may be considered in a future phase if a clear user
need is established. Any expansion should be documented
before implementation.
