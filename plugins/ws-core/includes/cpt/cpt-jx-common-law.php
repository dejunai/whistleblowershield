<?php
/**
 * cpt-jx-common-law.php
 *
 * Registers the Common Law Protection Custom Post Type.
 *
 * PURPOSE
 * -------
 * This CPT stores common law whistleblower protection records for a
 * specific U.S. jurisdiction. It covers judicially-recognized doctrines
 * — public policy exceptions to at-will employment, implied covenant
 * claims, constitutional protections — that provide whistleblower
 * protection without a codified statute.
 *
 * Many jurisdictions, particularly thin ones like Wyoming, rely primarily
 * or exclusively on common law doctrine rather than codified statute.
 * This CPT provides a first-class home for those protections rather than
 * forcing them into jx-statute (incorrect) or jx-citation (subordinate).
 *
 * ARCHITECTURE
 * ------------
 * jurisdiction (public CPT)
 *      └── jx-statute       (codified statutory protection)
 *      └── jx-common-law    (judicially-recognized doctrine)
 *
 * RELATIONSHIP TO jx-statute
 * --------------------------
 * jx-common-law uses the same taxonomy palette as jx-statute and
 * participates in the same query and render layers via parallel
 * functions. It differs in two ways:
 *   1. The anchor is a judicial doctrine, not a statute section.
 *      Two WYSIWYG fields — ws_cl_doctrine_basis and
 *      ws_cl_recognition_status — replace the citation/URL model.
 *   2. SOL is almost always derived (limit_ambiguous true by design)
 *      because common law claims borrow limitations periods from the
 *      nearest analogous statute.
 *
 * @package    WhistleblowerShield
 * @since      3.13.0
 * @version    3.13.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.13.0  Initial release.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ws_register_cpt_common_law' );

function ws_register_cpt_common_law() {

    $labels = [
        'name'               => 'Common Law Protections',
        'singular_name'      => 'Common Law Protection',
        'menu_name'          => 'JX Common Law',
        'add_new'            => 'Add Protection',
        'add_new_item'       => 'Add New Common Law Protection',
        'new_item'           => 'New Common Law Protection',
        'edit_item'          => 'Edit Common Law Protection',
        'view_item'          => 'View Common Law Protection',
        'all_items'          => 'All Common Law Protections',
        'search_items'       => 'Search Common Law Protections',
        'not_found'          => 'No common law protections found',
        'not_found_in_trash' => 'No common law protections found in trash',
    ];

    $args = [

        'labels' => $labels,

        // ── Visibility ────────────────────────────────────────────────────
        // Private dataset — rendered through the jurisdiction page assembler.
        // Not directly accessible to the public.

        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,

        // ── Editor ────────────────────────────────────────────────────────
        // Title: doctrine name. All structured metadata via ACF.

        'supports'   => [ 'title', 'editor', 'revisions' ],
        'taxonomies' => [
            'ws_disclosure_type',
            'ws_process_type',
            'ws_remedies',
            'ws_protected_class',
            'ws_adverse_action_types',
            'ws_disclosure_targets',
            'ws_fee_shifting',
            'ws_employer_defense',
            'ws_employee_standard',
            WS_JURISDICTION_TAXONOMY,
        ],

        // ── REST ──────────────────────────────────────────────────────────

        'show_in_rest' => true,

        // ── Capabilities ──────────────────────────────────────────────────

        'capability_type' => 'post',

        // ── Admin Menu ────────────────────────────────────────────────────

        'menu_icon'     => 'dashicons-hammer',
        'menu_position' => 33,

    ];

    register_post_type( 'jx-common-law', $args );
}
