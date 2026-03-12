<?php
/**
 * cpt-summaries.php
 *
 * Registers all jurisdiction addendum Custom Post Types:
 *   - jx-summary    (legal protections overview)
 *   - jx-resources  (resources overview — future)
 *   - jx-procedures (coming forward procedures — future)
 *   - jx-statutes   (statutes of limitations — future)
 *
 * NOTE: WordPress enforces a 20-character maximum on post type names (since v4.2.0).
 * The jx- prefix satisfies that constraint. "jx" is the project-wide abbreviation
 * for "jurisdiction" throughout this codebase.
 *
 * Old names (removed in v1.8.0):
 *   jurisdiction-summary    → jx-summary    (was 21 chars, now 10)
 *   jurisdiction-resources  → jx-resources  (was 22 chars, now 12)
 *   jurisdiction-procedures → jx-procedures (was 23 chars, now 13)
 *   jurisdiction-statutes   → jx-statutes   (was 21 chars, now 11)
 *
 * All addendum CPTs are non-public (not directly browsable by visitors).
 * They are rendered on jurisdiction pages exclusively via shortcodes.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_jx_summary_cpts' );
function ws_register_jx_summary_cpts() {
    ws_register_addendum_cpt(
        'jx-summary',
        'Jurisdiction Summary',
        'Jurisdiction Summaries',
        'jx-summary'
    );
    ws_register_addendum_cpt(
        'jx-resources',
        'Jurisdiction Resource',
        'Jurisdiction Resources',
        'jx-resources'
    );
    ws_register_addendum_cpt(
        'jx-procedures',
        'Jurisdiction Procedure',
        'Jurisdiction Procedures',
        'jx-procedures'
    );
    ws_register_addendum_cpt(
        'jx-statutes',
        'Jurisdiction Statute',
        'Jurisdiction Statutes',
        'jx-statutes'
    );
}

/**
 * Helper to register a non-public addendum CPT.
 *
 * @param string $post_type  Post type key.
 * @param string $singular   Singular label.
 * @param string $plural     Plural label.
 * @param string $slug       URL slug (used internally even though not public).
 */
function ws_register_addendum_cpt( $post_type, $singular, $plural, $slug ) {

    $labels = [
        'name'               => $plural,
        'singular_name'      => $singular,
        'menu_name'          => $plural,
        'add_new'            => 'Add New',
        'add_new_item'       => "Add New {$singular}",
        'edit_item'          => "Edit {$singular}",
        'new_item'           => "New {$singular}",
        'view_item'          => "View {$singular}",
        'search_items'       => "Search {$plural}",
        'not_found'          => "No {$plural} found.",
        'not_found_in_trash' => "No {$plural} found in trash.",
        'all_items'          => "All {$plural}",
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,   // Not browsable directly by visitors
        'publicly_queryable' => false,
        'show_ui'            => true,    // Visible in WP admin
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-text-page',
        'supports'           => [ 'title', 'editor', 'revisions' ],
    ];

    register_post_type( $post_type, $args );
}
