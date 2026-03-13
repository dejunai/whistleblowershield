# Proposal: Guidance Layer Design Principles

Status: Draft  
Author: Project Review Notes  
Purpose: Ensure the public site remains aligned with the core philosophy of WhistleblowerShield.

---

## 1. Project Philosophy

WhistleblowerShield has a dual structure:

1. A structured legal knowledge archive underneath
2. A plain-language public guidance resource on top

The public site should **never expose the full complexity of the legal archive**.  
Instead, the archive exists to ensure the guidance is **accurate, verifiable, and maintainable**.

Core principle:

The database serves the guidance.  
The guidance serves the user.

---

## 2. Primary Target Users

The platform serves three main audiences.

### 1. Person in Crisis

A worker who has witnessed wrongdoing and is afraid of retaliation.

Typical questions:

- Am I protected if I report this?
- Can I be fired for speaking up?
- Who should I report to?

Needs:

- reassurance
- plain language explanations
- immediate practical guidance

This user should always be considered the **primary audience** of the site.

---

### 2. Person Facing Retaliation

A worker who has already reported misconduct and is now experiencing retaliation.

Typical questions:

- What protections exist?
- What agency can help me?
- What deadlines apply?

Needs:

- step-by-step guidance
- procedural explanations
- clear legal protections

---

### 3. Journalist or Legal Researcher

A secondary audience interested in accurate legal information.

Needs:

- citations
- statutes
- jurisdiction comparisons
- source verification

The structured legal database supports this audience without dominating the public interface.

---

## 3. Guidance Layer Model

Public pages should follow a layered structure:

Plain English Summary  
â†“  
Practical Guidance  
â†“  
Legal Citations

Example structure:

Overview  
Protections  
Procedures  
Resources  
Statutes

Most visitors will read only the first layer.

---

## 4. Homepage Design Principle

The homepage should quickly answer the questions most likely to be asked by a distressed visitor.

Examples:

- What is whistleblowing?
- Am I protected?
- What should I do right now?

Recommended pattern:

If you have witnessed wrongdoing at work, you may have legal protections.

This site explains those protections in plain English.

Start here:
â€¢ Am I protected?
â€¢ How to report safely
â€¢ What to do if retaliation begins

---

## 5. Jurisdiction Page Structure

Jurisdiction pages should present legal information in the following order:

1. Plain-language overview
2. Key protections
3. Reporting procedures
4. Helpful resources
5. Relevant statutes

The existing California page is a manual prototype demonstrating this approach.

Future tooling should generate similar pages automatically from the underlying data model.

---

## 6. Tone and Accessibility

The public site must remain:

- calm
- plain language
- non-intimidating
- easy to navigate

Legal terminology should always be translated into explanations understandable to non-lawyers.

Example:

Instead of:

Employees are protected under California Labor Code Â§1102.5.

Prefer:

California law protects workers who report illegal activity.  
This protection comes from California Labor Code Â§1102.5.

---

## 7. Separation of Concerns

The project contains two distinct documentation audiences.

Public site content:
Plain language guidance for whistleblowers.

Internal documentation:
Technical and editorial documentation for developers, researchers, and future contributors.

Internal documentation should never drive the complexity of the public interface.

---

## 8. Development Reality

The project is currently developed by a single maintainer without staging infrastructure.

During early development:

- the live site functions as both prototype and production
- pages may be manually constructed as reference models
- automation and database tooling will come later

This approach is acceptable as long as the **guidance philosophy remains consistent**.

---

## 9. Long-Term Direction

If the platform grows, the underlying archive may support:

- research interfaces
- legal datasets
- jurisdiction comparisons
- APIs for journalists or researchers

However, these features should remain **secondary to the core mission**:

Providing clear guidance to whistleblowers in need.

---

End of proposal.
