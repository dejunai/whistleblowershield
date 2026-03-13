# Ensure proposals directory exists
$dir = "documentation/proposals"

if (!(Test-Path $dir)) {
    New-Item -ItemType Directory -Path $dir | Out-Null
}

############################################################
# Document 1: Project Vision and Mission
############################################################

$visionFile = "$dir/project_vision_and_mission.md"

$visionContent = @"
# Proposal: Project Vision and Mission

Status: Draft  
Purpose: Define the guiding mission and long-term direction of WhistleblowerShield.

---

## 1. Core Mission

WhistleblowerShield exists to provide **clear, reliable guidance for workers who witness wrongdoing and need to understand their legal protections**.

The platform aims to make complex whistleblower laws understandable and accessible to people without legal training.

The site prioritizes:

- clarity
- accessibility
- legal accuracy
- public trust

---

## 2. Long-Term Vision

The long-term vision of the project is a **dual-layer system**.

Public Layer  
A plain-language resource that helps workers understand whistleblower protections.

Knowledge Layer  
A structured legal archive that organizes whistleblower laws across jurisdictions.

The knowledge layer ensures the guidance is:

- accurate
- verifiable
- maintainable
- expandable over time

---

## 3. Core Principles

### Plain Language First

Legal complexity must always be translated into clear explanations understandable by non-lawyers.

### Guidance Before Law

The site should focus on answering practical questions before presenting legal citations.

### Accuracy and Verification

All legal information should be traceable to reliable sources such as statutes, regulations, or official guidance.

### Trust and Transparency

The platform should clearly explain:

- editorial policies
- correction procedures
- sourcing practices

These policies build confidence among users and researchers.

---

## 4. Intended Impact

If successful, the platform could serve as:

- a trusted resource for whistleblowers
- a research tool for journalists
- a reference archive for legal researchers
- an educational resource for the public

The goal is not simply to catalog laws but to **empower individuals with knowledge of their rights**.

---

## 5. Development Reality

The project currently operates as a small independent initiative developed by a single maintainer.

Constraints include:

- limited financial resources
- lack of staging infrastructure
- manual page development during early phases

Despite these limitations, the project emphasizes:

- strong documentation
- structured legal models
- long-term scalability

These practices are intended to make future collaboration possible.

---

## 6. Future Growth

If the project gains support, potential future development could include:

- expanded jurisdiction coverage
- automated legal data tools
- research interfaces
- collaboration with journalists or legal experts

However, the central mission will remain the same:

**Providing clear guidance to workers who need to understand their whistleblower protections.**

---

End of proposal.
"@

Set-Content -Path $visionFile -Value $visionContent -Encoding UTF8

############################################################
# Document 2: Public Guidance Design Standards
############################################################

$guidanceFile = "$dir/public_guidance_design_standards.md"

$guidanceContent = @"
# Proposal: Public Guidance Design Standards

Status: Draft  
Purpose: Define standards for writing and structuring public guidance content.

---

## 1. Design Philosophy

The WhistleblowerShield website is designed primarily for individuals who may be:

- concerned about reporting wrongdoing
- unsure of their legal protections
- experiencing retaliation

Content must therefore prioritize clarity and reassurance.

The site should feel:

- calm
- trustworthy
- accessible

---

## 2. Plain Language Requirement

All public content should be written for readers without legal training.

Guidelines:

- avoid unnecessary legal jargon
- explain legal concepts clearly
- keep sentences concise
- prefer practical explanations over technical descriptions

Example:

Instead of:

Employees are protected under California Labor Code §1102.5.

Prefer:

California law protects workers who report illegal activity.  
This protection comes from California Labor Code §1102.5.

---

## 3. Layered Information Model

Content should follow a layered structure:

Plain-language summary  
↓  
Practical guidance  
↓  
Legal citations

Most readers will only read the first layer.

Researchers and journalists can explore deeper layers when needed.

---

## 4. Question-Driven Navigation

Pages should be structured around questions that users are likely to ask.

Examples:

- Am I protected if I report misconduct?
- How can I report wrongdoing safely?
- What should I do if my employer retaliates?

This approach ensures the site serves real user needs rather than reflecting internal database structures.

---

## 5. Jurisdiction Page Structure

Jurisdiction pages should follow a consistent pattern.

Recommended sections:

Overview  
Protections  
Reporting Procedures  
Resources  
Statutes

The goal is to move from **explanation to detail**.

---

## 6. Tone Guidelines

Content should avoid sounding:

- alarmist
- overly legalistic
- dismissive

Instead, it should emphasize:

- clarity
- neutrality
- calm guidance

Readers should feel that the site is a reliable informational resource.

---

## 7. Accessibility Considerations

Pages should be designed for easy reading.

Guidelines include:

- short paragraphs
- descriptive headings
- clear navigation
- minimal visual clutter

The goal is to ensure users can quickly find answers even under stress.

---

## 8. Relationship to Internal Documentation

Public guidance pages serve a different purpose than internal documentation.

Internal documentation may include:

- legal data models
- editorial workflows
- technical architecture

These documents support the project internally but should not shape the complexity of public content.

---

## 9. Future Improvements

As the platform grows, guidance pages may incorporate:

- improved navigation tools
- clearer reporting pathways
- additional jurisdiction coverage

However, the core design principles should remain stable.

---

End of proposal.
"@

Set-Content -Path $guidanceFile -Value $guidanceContent -Encoding UTF8

Write-Host "Proposal documents created:"
Write-Host $visionFile
Write-Host $guidanceFile