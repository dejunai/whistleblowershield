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
