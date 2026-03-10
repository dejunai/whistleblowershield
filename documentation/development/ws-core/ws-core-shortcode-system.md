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
â†“  
Shortcode execution  
â†“  
Query CPT data  
â†“  
Render formatted output

---

## Future Direction

Shortcodes are considered a transitional architecture.

Long-term migration to Gutenberg blocks is planned.
