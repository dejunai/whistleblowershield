# Root documentation directory
$root = "documentation"

############################################################
# Create new directory structure
############################################################

$dirs = @(
    "$root/architecture",
    "$root/editorial",
    "$root/product",
    "$root/project",
    "$root/proposals",
    "$root/archive"
)

foreach ($d in $dirs) {
    if (!(Test-Path $d)) {
        New-Item -ItemType Directory -Path $d | Out-Null
    }
}

############################################################
# Helper function: move file if it exists
############################################################

function Move-Doc {
    param(
        [string]$source,
        [string]$dest
    )

    if (Test-Path $source) {
        Move-Item $source $dest -Force
        Write-Host "Moved $source -> $dest"
    }
}

############################################################
# Move architecture documents
############################################################

Move-Doc "$root/system-architecture.md" "$root/architecture/system-architecture.md"
Move-Doc "$root/jurisdiction-knowledge-model.md" "$root/architecture/jurisdiction-model.md"
Move-Doc "$root/legal-entity-model.md" "$root/architecture/legal-entity-model.md"
Move-Doc "$root/legal-citation-model.md" "$root/architecture/legal-citation-model.md"

############################################################
# Move editorial documents
############################################################

Move-Doc "$root/editorial/user-personas.md" "$root/editorial/user-personas.md"
Move-Doc "$root/editorial/content-standards.md" "$root/editorial/editorial-standards.md"
Move-Doc "$root/editorial/editorial-workflow.md" "$root/editorial/workflow.md"
Move-Doc "$root/editorial/source-verification-policy.md" "$root/editorial/verification-model.md"

############################################################
# Create product guidance-layer model
############################################################

$productDoc = "$root/product/guidance-layer-model.md"

$productContent = @"
# Guidance Layer Model

Purpose: Define how legal information is translated into plain-language public guidance.

---

## Core Principle

The legal database exists to support guidance.

The public website exists to help workers understand whistleblower protections in plain language.

---

## Layered Information Model

Public pages should follow three layers:

Plain Language Summary  
↓  
Practical Guidance  
↓  
Legal Citations

Most visitors will only read the first layer.

---

## Question Driven Design

Pages should answer real user questions:

- Am I protected if I report misconduct?
- How can I report wrongdoing safely?
- What happens if my employer retaliates?

---

## Relationship to Legal Database

The underlying legal archive contains structured data about:

- jurisdictions
- statutes
- procedures
- resources

Public pages interpret this information rather than exposing raw legal structure.

---

End of document.
"@

Set-Content -Path $productDoc -Value $productContent -Encoding UTF8

############################################################
# Create project vision file
############################################################

$visionDoc = "$root/project/project-vision.md"

$visionContent = @"
# Project Vision

WhistleblowerShield aims to provide clear, reliable guidance for workers who need to understand whistleblower protections.

The platform combines two layers:

Public Guidance Layer  
Plain-language explanations designed for non-lawyers.

Legal Knowledge Layer  
A structured archive of whistleblower laws that ensures guidance remains accurate and verifiable.

The public interface prioritizes clarity and accessibility.

The legal archive exists to support that mission.

---

End of document.
"@

Set-Content -Path $visionDoc -Value $visionContent -Encoding UTF8

############################################################
# Create project status document
############################################################

$statusDoc = "$root/project/project-status.md"

$statusContent = @"
# Project Status

WhistleblowerShield is currently developed by a single maintainer.

Development constraints include:

- limited financial resources
- lack of staging infrastructure
- manual prototyping on a live site

Despite these constraints the project prioritizes:

- strong documentation
- clear editorial standards
- structured legal models

The goal is to create a foundation that future collaborators can build upon.

---

End of document.
"@

Set-Content -Path $statusDoc -Value $statusContent -Encoding UTF8

############################################################
# Archive redundant docs if present
############################################################

$possibleRedundant = @(
    "$root/transparency-policy.md",
    "$root/old-editorial-policy.md",
    "$root/legacy-architecture.md"
)

foreach ($file in $possibleRedundant) {
    if (Test-Path $file) {
        Move-Item $file "$root/archive/" -Force
        Write-Host "Archived $file"
    }
}

############################################################
# Final output
############################################################

Write-Host ""
Write-Host "Documentation restructuring complete."
Write-Host "New structure created under /documentation."