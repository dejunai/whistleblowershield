Write-Host "Creating ws-core documentation..."

$base = "documentation/development/ws-core"

$dirs = @(
$base
)

foreach ($d in $dirs) {
    New-Item -ItemType Directory -Force -Path $d | Out-Null
}

$files = @{

"$base/ws-core-overview.md" = @"
# ws-core Plugin Overview

ws-core is the core application plugin powering the WhistleblowerShield platform.

Responsibilities include:

- registering Custom Post Types
- defining ACF data schemas
- rendering frontend components via shortcodes
- recording audit metadata on edits

Dependency:

Advanced Custom Fields Pro (ACF Pro)

Plugin entry point:

ws-core.php
"@

"$base/ws-core-file-structure.md" = @"
# File Structure

Current plugin structure:

ws-core/
 ├ ws-core.php
 ├ ws-core-front.css
 └ includes/
    ├ cpt-jurisdiction.php
    ├ cpt-summaries.php
    ├ cpt-legal-updates.php
    ├ acf-jurisdiction.php
    ├ acf-summary.php
    ├ acf-legal-updates.php
    ├ shortcodes.php
    └ audit-trail.php

Each module handles a discrete responsibility within the plugin.
"@

"$base/ws-core-cpts.md" = @"
# Custom Post Types

The plugin registers the following Custom Post Types.

jurisdiction
Root entity representing a government jurisdiction.

jx-summary
Legal protections overview for a jurisdiction.

jx-resources
Placeholder for jurisdiction resources.

jx-procedures
Placeholder for reporting procedures.

jx-statutes
Placeholder for statute references.

ws-legal-update
Tracks legal changes affecting site content.
"@

"$base/ws-core-acf-schema.md" = @"
# ACF Schema

ACF field groups define structured data for each CPT.

jurisdiction
Identity metadata, government links, related content.

jx-summary
Summary text, sources, authorship, review metadata.

ws-legal-update
Law name, jurisdiction relationship, summary, source URL,
effective date, author.
"@

"$base/ws-core-shortcodes.md" = @"
# Shortcodes

The plugin registers several shortcodes for rendering content.

[ws_jurisdiction_header]
Displays jurisdiction header information.

[ws_flag]
Displays jurisdiction flag.

[ws_summary]
Displays jurisdiction summary.

[ws_review_status]
Displays review status badges.

[ws_legal_updates]
Displays recent legal updates.
"@

"$base/ws-core-audit-trail.md" = @"
# Audit Trail

The plugin records tamper-resistant metadata on each post save.

Meta keys written:

_ws_last_edited_by
Most recent editor.

_ws_edit_history
Append-only log of edits.

Each entry records:

user ID
display name
UTC timestamp
"@

"$base/ws-core-data-model.md" = @"
# Data Model

High level structure:

Jurisdiction
 ├ summary (jx-summary)
 ├ resources
 ├ procedures
 ├ statutes
 └ legal updates

Relationships are implemented using ACF relationship fields.
"@
}

foreach ($path in $files.Keys) {
    $files[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add .

git commit -m "Add ws-core plugin documentation"

git push

Write-Host "ws-core documentation added."