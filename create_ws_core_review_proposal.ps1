$dir = "documentation/proposals"

if (!(Test-Path $dir)) {
    New-Item -ItemType Directory -Path $dir | Out-Null
}

$file = "$dir/ws_core_plugin_review.md"

$content = @"
# Proposal: ws-core Plugin Review and Structural Improvements

Status: Draft  
Purpose: Document improvements recommended after a review of the ws-core WordPress plugin.

---

# Overview

The ws-core plugin forms the foundation of the WhistleblowerShield platform's legal knowledge system.

The plugin currently implements:

- Custom Post Types for legal entities
- Advanced Custom Fields schemas
- Shortcodes for rendering front-end content
- A basic audit trail mechanism

This architecture provides a strong starting point for a structured legal information system.

However several improvements are recommended to increase reliability, maintainability, and long-term scalability.

---

# 1. Dependency Protection

The plugin relies on Advanced Custom Fields (ACF). If ACF is disabled the plugin may fail.

Recommendation:

Add a dependency check during plugin initialization.

Example:

    if ( ! function_exists('acf_add_local_field_group') ) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>ws-core requires Advanced Custom Fields.</p></div>';
        });
        return;
    }

---

# 2. Include File Protection

All include files should prevent direct access.

Each file in the includes directory should begin with:

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

This prevents direct web access to internal PHP files.

---

# 3. Module Loading Order

Plugin modules should load in a predictable order:

1. Custom Post Types
2. ACF field groups
3. Functional modules (shortcodes, audit trail)

This ensures dependencies are available when needed.

---

# 4. Function Prefixing

All plugin functions should use a consistent prefix.

Recommended prefix:

    ws_

Example:

    ws_jurisdiction_index()
    ws_get_jurisdiction_summary()

This avoids conflicts with other plugins.

---

# 5. Variable Naming Improvements

Variables should match the legal data model defined in the documentation.

Avoid generic variables such as:

    $data
    $item
    $value

Prefer descriptive variables:

    $jurisdiction
    $statute_record
    $reporting_agency
    $legal_update

This improves readability and future maintainability.

---

# 6. CPT Slug Stability

Custom Post Type slugs should remain stable once deployed.

Current slugs appear suitable:

- jurisdiction
- summaries
- legal_updates

Renaming these later could break URLs, queries, and internal references.

---

# 7. Shortcode Output Escaping

Shortcode output should escape variables where appropriate.

Example:

    esc_html()
    esc_url()

This protects against malformed data and improves security.

---

# 8. CSS Enqueue Versioning

Plugin CSS should be loaded with a version parameter.

Example:

    wp_enqueue_style(
        'ws-core-front',
        plugin_dir_url(__FILE__) . 'ws-core-front.css',
        array(),
        '2.0.0'
    );

This helps prevent caching issues after updates.

---

# 9. ACF Function Guards

Because the plugin relies on ACF functions such as get_field(), calls should be guarded where appropriate.

Example:

    if ( function_exists('get_field') ) {
        \$value = get_field('field_name');
    }

This prevents fatal errors if ACF is temporarily unavailable.

---

# 10. Audit Trail Expansion

The audit trail feature could be expanded to include:

- timestamped updates
- user attribution
- summary of legal changes

This would support the platform's long-term credibility as a legal information resource.

---

# Conclusion

The current ws-core plugin architecture is a strong foundation for a structured legal information platform.

Implementing these improvements will enhance:

- code safety
- maintainability
- scalability
- future collaboration potential

These improvements can be implemented incrementally without major refactoring.

---

End of proposal.
"@

Set-Content -Path $file -Value $content -Encoding UTF8

Write-Host "Proposal document created:"
Write-Host $file