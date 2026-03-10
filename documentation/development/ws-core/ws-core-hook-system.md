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
