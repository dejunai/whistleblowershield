<?php
/**
 * cpt-jx-citations.php
 *
 * Registers the Jurisdiction Citation Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores individual citation records associated with a
 * jurisdiction — case law, statutes, regulatory references, and
 * secondary sources. Citations are reusable across summaries and
 * are selectively rendered on the public jurisdiction page via the
 * [ws_jx_case_law] shortcode based on their Attach toggle.
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-citation (private dataset, many per jurisdiction)
 *
 * Citations are linked to their parent jurisdiction via the
 * ws_jx_code field (e.g. "CA", "TX", "federal"). This field is
 * the primary query key used by the shortcode render layer.
 * The ws_jurisdiction post_object field provides the admin UI
 * relationship and supports two-way sync.
 *
 * RENDER MODEL
 * ------------
 * The [ws_jx_case_law] shortcode queries all jx-citation records
 * where:
 *   - ws_jx_code matches the current jurisdiction
 *   - ws_jx_cite_attach is true (1)
 *
 * Records are ordered by ws_jx_cite_position (numeric, ascending).
 * The shortcode renders the full ws-case-law section including
 * footnote anchors and Unicode return links.
 *
 * Citations where ws_jx_cite_attach is false are stored for
 * reference and admin review but do not appear on the public page.
 *
 * @package    WhistleblowerShield
 * @since      2.3.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 2.3.0  Initial release.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_jx_citation' );

function ws_register_cpt_jx_citation() {

    $labels = [
        'name'               => 'Jurisdiction Citations',
        'singular_name'      => 'Jurisdiction Citation',
        'menu_name'          => 'JX Citations',
        'add_new'            => 'Add Citation',
        'add_new_item'       => 'Add New Jurisdiction Citation',
        'edit_item'          => 'Edit Jurisdiction Citation',
        'new_item'           => 'New Jurisdiction Citation',
        'view_item'          => 'View Citation',
        'search_items'       => 'Search Citations',
        'not_found'          => 'No citations found',
        'not_found_in_trash' => 'No citations found in trash',
        'all_items'          => 'All Citations',
    ];

    $args = [

        'labels'             => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — rendered through the jurisdiction page assembler.
        // Not directly accessible to the public.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title used as the citation display label in the admin list.
        // No post editor — all content managed via ACF fields.

        'supports'            => [ 'title', 'revisions' ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest'        => true,

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'       => 'dashicons-book-alt',
        'menu_position'   => 27,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',
        'rewrite'         => false,

    ];

    register_post_type( 'jx-citation', $args );
}
