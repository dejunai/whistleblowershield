Write-Host "Creating ws-core architecture documentation..."

$docs = @{

"documentation/development/ws-core/ws-core-plugin-architecture.md" = @"
# ws-core Plugin Architecture

## Purpose

The ws-core plugin is the primary application layer of the
WhistleblowerShield platform.

It defines:

- legal data structures
- WordPress post types
- ACF schema
- rendering logic
- editorial workflows

WordPress acts as a framework, while ws-core defines the
domain model for whistleblower law.

---

## Architectural Layers

Presentation Layer
Rendering templates and shortcodes.

Application Layer
Plugin logic, validation, and relationships.

Data Layer
WordPress posts + ACF structured data.

---

## Responsibilities

ws-core is responsible for:

- registering Custom Post Types
- defining data relationships
- managing legal updates
- rendering jurisdiction pages
- enforcing naming conventions

---

## Non-Responsibilities

ws-core does NOT manage:

- site theming
- CSS styling
- analytics
- infrastructure

These are handled by other components.
"@


"documentation/development/ws-core/ws-core-module-structure.md" = @"
# ws-core Module Structure

The plugin should evolve toward a modular architecture.

Example structure:

ws-core/
 ├ bootstrap
 ├ post-types
 ├ acf
 ├ rendering
 ├ shortcodes
 ├ audit
 └ utilities

---

## Module Responsibilities

bootstrap
Plugin initialization.

post-types
Registers all CPTs.

acf
Defines ACF field groups.

rendering
Template helpers.

shortcodes
Frontend display logic.

audit
Tracks editorial changes.

utilities
Shared helper functions.
"@


"documentation/development/ws-core/ws-core-hook-system.md" = @"
# ws-core Hook System

The plugin relies on WordPress hooks.

Two types are used:

Actions
Filters

---

## Action Hooks

Used for initialization.

Examples:

init  
acf/init  
admin_init

---

## Filter Hooks

Used for modifying output or behavior.

Examples:

the_content  
acf/prepare_field

---

## Design Principle

Hooks should be used to connect modules rather than
directly coupling code components.
"@


"documentation/development/ws-core/ws-core-shortcode-system.md" = @"
# ws-core Shortcode System

The current rendering system relies on WordPress shortcodes.

Example:

[jx_summary jurisdiction="california"]

---

## Purpose

Shortcodes allow structured legal data to be embedded
inside WordPress pages.

---

## Rendering Flow

Page request  
↓  
Shortcode execution  
↓  
Query CPT data  
↓  
Render formatted output

---

## Future Direction

Shortcodes are considered a transitional architecture.

Long-term migration to Gutenberg blocks is planned.
"@


"documentation/development/ws-core/ws-core-future-block-architecture.md" = @"
# Future Block Architecture

The platform will eventually migrate from shortcodes
to WordPress block-based rendering.

---

## Why Blocks

Blocks provide:

visual editing  
structured content  
component reuse

---

## Migration Path

Phase 1  
Shortcodes (current)

Phase 2  
Shortcodes wrapped by blocks

Phase 3  
Native Gutenberg blocks

---

## Block Examples

Jurisdiction Summary Block

Legal Update Feed Block

Statute List Block

---

## Compatibility Strategy

Shortcodes will remain supported to prevent
breaking existing pages.
"@

}

foreach ($path in $docs.Keys) {
    $dir = Split-Path $path
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $docs[$path] | Out-File -Encoding utf8 $path
}

Write-Host "Adding files to git..."

git add documentation/development/ws-core

Write-Host "Creating commit..."

git commit -m "Add ws-core architecture documentation set"

Write-Host "Pushing to GitHub..."

git push

Write-Host "Architecture docs added."