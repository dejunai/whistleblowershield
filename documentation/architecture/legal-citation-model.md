# U.S. Legal Citation Model

## Purpose

This document defines how legal authorities should be cited and
stored within the WhistleblowerShield platform.

Consistent citation formatting is necessary to ensure that legal
information remains verifiable, searchable, and credible.

The model is loosely aligned with common legal citation standards
such as the Bluebook.

---

# Primary Legal Authorities

The platform primarily references three types of legal authority.

1. Statutes  
2. Regulations  
3. Case Law

Each type has a standardized citation structure.

---

# Statute Citation Format

Federal statutes are typically cited using the United States Code.

Example format:

Title U.S.C. Section

Examples:

31 U.S.C. § 3729  
15 U.S.C. § 78u-6

Recommended stored fields:

statute_name  
citation  
code_title  
section  
official_source_url

---

# State Statute Citation Format

State statutes should use the official citation format for the state.

Examples:

California Labor Code § 1102.5  
Texas Government Code § 554.002

Recommended stored fields:

statute_name  
citation  
state  
section  
official_source_url

---

# Regulation Citation Format

Federal regulations are typically cited using the Code of Federal
Regulations.

Example format:

Title C.F.R. Part or Section

Examples:

17 C.F.R. § 240.21F-2  
29 C.F.R. § 24.102

Recommended stored fields:

regulation_name  
citation  
cfr_title  
section  
agency

---

# Case Law Citation Format

Case law citations follow standard court citation formats.

Examples:

United States ex rel. Marcus v. Hess, 317 U.S. 537 (1943)

Fields:

case_name  
citation  
court  
year  
official_source_url

---

# Official Source Links

Whenever possible, links should point to authoritative sources.

Preferred sources include:

govinfo.gov  
congress.gov  
federalregister.gov  
state legislature websites

Secondary sources should only be used if primary sources
are unavailable.

---

# Citation Consistency Rules

All citations should:

use the official legal name of the statute or case  
include section numbers when available  
link to official sources whenever possible

Avoid informal references such as:

"the whistleblower law"  
"federal fraud statute"

---

# Future Expansion

The citation model may expand to support:

administrative decisions  
agency guidance documents  
enforcement actions