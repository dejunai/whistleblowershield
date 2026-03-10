# ws-core Module Structure

The plugin should evolve toward a modular architecture.

Example structure:

ws-core/
 â”œ bootstrap
 â”œ post-types
 â”œ acf
 â”œ rendering
 â”œ shortcodes
 â”œ audit
 â”” utilities

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
