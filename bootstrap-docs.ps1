# WhistleblowerShield Documentation Bootstrap Script

Write-Host "Creating documentation directories..."

$dirs = @(
"documentation",
"documentation/architecture",
"documentation/development",
"documentation/editorial",
"documentation/policy",
"documentation/project"
)

foreach ($dir in $dirs) {
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
}

Write-Host "Creating markdown files..."

$files = @{

"documentation/README.md" = @"
# WhistleblowerShield Documentation

This repository contains the architecture and governance documentation
for the WhistleblowerShield legal knowledge platform.

## Structure

documentation/
  architecture/
  development/
  editorial/
  policy/
  project/

The platform is designed as a jurisdiction-centric legal database
for whistleblower protections.
"@

"documentation/architecture/system-architecture.md" = @"
# System Architecture

WhistleblowerShield is designed as a jurisdiction-centric
legal knowledge platform.

Each jurisdiction contains structured information including:

- summary
- statutes
- reporting procedures
- resources
- legal updates

Architecture layers:

Infrastructure
Application
Data
Editorial
"@

"documentation/architecture/security-model.md" = @"
# Security Model

The platform handles sensitive legal information.

Threat considerations include:

- defacement
- content manipulation
- admin compromise

Mitigations include:

- Cloudflare WAF
- limited admin access
- plugin code review
- daily backups
"@

"documentation/development/ws-core-plugin-architecture.md" = @"
# ws-core Plugin Architecture

The ws-core plugin powers the structured legal database.

Plugin layout:

ws-core/
  ws-core.php
  includes/
  post-types/
  acf/
  queries/
  render/
  shortcodes/

Responsibilities:

- register custom post types
- define data schema
- provide query helpers
- render jurisdiction pages
"@

"documentation/development/query-layer.md" = @"
# Query Layer

To prevent duplicated logic the plugin exposes helper functions.

Examples:

get_jurisdiction_summary()
get_jurisdiction_statutes()
get_jurisdiction_procedures()

These abstract WordPress queries into reusable functions.
"@

"documentation/editorial/editorial-workflow.md" = @"
# Editorial Workflow

Content pipeline:

Research
Draft
Editorial Review
Legal Review
Publish

This ensures legal information is reviewed before publication.
"@

"documentation/policy/legal-research-methodology.md" = @"
# Legal Research Methodology

Sources used:

- statutes
- agency guidance
- official government publications
- court decisions

All claims should cite primary sources whenever possible.
"@

"documentation/policy/transparency-policy.md" = @"
# Transparency Policy

The project commits to:

- citing legal sources
- maintaining revision history
- correcting errors quickly
- documenting methodology
"@

"documentation/project/roadmap.md" = @"
# Project Roadmap

Phase 1
Platform architecture and documentation.

Phase 2
Jurisdiction coverage.

Phase 3
Search system.

Phase 4
Case law database.

Phase 5
Comparative law tools.
"@

"documentation/project/governance.md" = @"
# Governance

Roles:

Founder
Technical Lead
Editorial Lead
Legal Advisor

Major architectural decisions should be documented in this repository.
"@
}

foreach ($path in $files.Keys) {
    $files[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add .

Write-Host "Creating commit..."

git commit -m "Bootstrap documentation structure"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Done."