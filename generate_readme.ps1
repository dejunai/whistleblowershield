$readme = "README.md"

$content = @"
# WhistleblowerShield

WhistleblowerShield is a public-interest project focused on helping workers understand whistleblower protections and navigate situations involving retaliation or reporting misconduct.

The project aims to provide clear, plain-language guidance while maintaining a structured legal archive that ensures accuracy and transparency.

---

# Mission

The goal of WhistleblowerShield is to make whistleblower protections understandable and accessible.

Many workers who witness wrongdoing face urgent decisions without clear guidance. Legal information is often scattered, technical, or difficult to interpret.

WhistleblowerShield attempts to bridge that gap.

---

# Target Users

The platform is designed to support three primary audiences.

## 1. Workers Seeking Guidance

People who:
- have witnessed misconduct
- are considering reporting wrongdoing
- are facing retaliation

These users need clear explanations and practical information rather than legal jargon.

---

## 2. Whistleblowers Facing Retaliation

Individuals who have already come forward and are dealing with consequences.

These users often need quick access to:
- legal protections
- reporting channels
- relevant agencies
- available resources

The platform prioritizes clarity and accessibility for these users.

---

## 3. Journalists and Researchers

A third audience includes:
- journalists
- legal researchers
- policy analysts

For these users, the platform aims to provide structured legal information and transparent sourcing.

---

# Core Philosophy

The project is built on two complementary layers.

## Public Guidance Layer

The public site focuses on:
- plain-English explanations
- simple navigation
- practical information
- guidance focused on real questions people face

This layer prioritizes accessibility for non-lawyers.

---

## Legal Knowledge Layer

Underneath the guidance layer is a structured legal archive.

This archive organizes information about:
- jurisdictions
- statutes
- legal procedures
- reporting mechanisms
- enforcement agencies

The legal structure exists to ensure the public guidance remains accurate and verifiable.

---

# Project Status

WhistleblowerShield is currently developed by a single maintainer.

Development constraints include:
- limited financial resources
- lack of staging infrastructure
- manual prototyping on a live site

Despite these limitations the project emphasizes:
- strong documentation
- editorial transparency
- structured legal models

The goal is to create a foundation that future collaborators can build upon.

---

# Documentation

Project documentation is located in the documentation directory.

Key areas include:

documentation/
    architecture/
    editorial/
    product/
    project/
    proposals/

Architecture documents define the structure of the legal knowledge system.

Editorial documents describe research standards and source verification practices.

Product documents describe how legal information is translated into public guidance.

Project documents contain mission statements, development status, and contributor guidance.

Proposals contain experimental ideas and early design proposals.

---

# Guiding Principle

The legal archive exists to support the guidance.

The public site exists to help workers understand their rights.

Maintaining this balance is central to the project.

---

# Contributing

The project is currently in an early stage of development.

Future contributions may include:
- legal research
- editorial review
- jurisdiction updates
- technical improvements

Contributors are encouraged to review the documentation before proposing changes.

---

# Disclaimer

WhistleblowerShield provides general legal information.

It does not provide legal advice.

Laws vary by jurisdiction and individual situations differ. Individuals facing legal issues should consider consulting qualified legal professionals.

---

End of README.
"@

Set-Content -Path $readme -Value $content -Encoding UTF8

Write-Host "README.md generated successfully."