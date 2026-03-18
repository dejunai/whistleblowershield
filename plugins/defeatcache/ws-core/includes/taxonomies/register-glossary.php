<?php
/**
 * register-glossary.php
 *
 * WhistleblowerShield Glossary System
 *
 * PURPOSE
 * -------
 * Registers the ws_glossary taxonomy, provides a transient-cached term
 * registry, and exposes a ws_glossary_scan filter that shortcodes opt into
 * by passing their rendered HTML through it.
 *
 * Matching terms are wrapped in:
 *   <span class="ws-term-highlight" data-tooltip="[definition]">[term]</span>
 *
 * TAXONOMY
 * --------
 * ws_glossary — private, non-hierarchical, no public archive.
 * Each term is one glossary entry:
 *   name        — canonical term (e.g. "qui tam")
 *   description — tooltip definition text
 *   term_meta   — ws_glossary_aliases (pipe-delimited alias string)
 *
 * SCANNER BEHAVIOR
 * ----------------
 * - Scans raw text nodes only — never injects inside existing HTML tags.
 * - Skips text nodes inside: <a>, <span>, <abbr>, <button>, <script>, <style>, <code>, <pre>.
 * - Matches are case-insensitive, whole-word only.
 * - Aliases and canonical terms are sorted longest-first so multi-word
 *   phrases match before shorter substrings (e.g. "qui tam lawsuit"
 *   before "qui tam").
 * - First occurrence per content block only — each term is matched once
 *   per ws_glossary_scan() call, never twice in the same HTML string.
 *
 * TRANSIENT
 * ---------
 * ws_glossary_cache — stores the full flat lookup array.
 * Invalidated on edited_ws_glossary and created_ws_glossary hooks.
 * TTL: 24 hours as a safety net.
 *
 * INTEGRATION
 * -----------
 * Shortcodes opt in by applying the filter to their rendered HTML:
 *
 *   $html = apply_filters( 'ws_glossary_scan', $html );
 *
 * No global the_content filter — only explicitly opted-in content
 * is scanned.
 *
 * @package    WhistleblowerShield
 * @since      3.2.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.2.0  Initial release.
 * 3.2.1  Added ws_seed_glossary_taxonomy() with admin_init option-gate.
 *        Fixed: taxonomy_exists() guard added. ws_get_taxonomy_caps() used
 *        for capabilities. libxml error state saved and restored.
 *        Docblock corrected: <code> and <pre> added to skip-tags list.
 *        add_action placement moved to after function definition.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Taxonomy Registration
// ════════════════════════════════════════════════════════════════════════════

/**
 * Registers the ws_glossary taxonomy.
 *
 * Private and non-public — no archive page, no frontend URL.
 * Unattached to any post type — admin-side term management only.
 */
function ws_register_glossary_taxonomy() {
    if ( taxonomy_exists( 'ws_glossary' ) ) {
        return;
    }
    register_taxonomy( 'ws_glossary', null, [
        'label'             => 'Glossary',
        'labels'            => [
            'name'              => 'Glossary',
            'singular_name'     => 'Glossary Term',
            'menu_name'         => 'Glossary',
            'add_new_item'      => 'Add New Term',
            'edit_item'         => 'Edit Term',
            'update_item'       => 'Update Term',
            'search_items'      => 'Search Terms',
            'not_found'         => 'No terms found.',
        ],
        'public'            => false,
        'publicly_queryable'=> false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => false,
        'show_tagcloud'     => false,
        'show_in_rest'      => false,
        'hierarchical'      => false,
        'rewrite'           => false,
        'query_var'         => false,
        'capabilities'      => ws_get_taxonomy_caps(),
    ] );
}
add_action( 'init', 'ws_register_glossary_taxonomy' );


// ════════════════════════════════════════════════════════════════════════════
// Admin Menu Placement
//
// Surfaces the Glossary taxonomy admin page under the main WP menu
// rather than buried under a post type. Appears under Tools for
// administrators only.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_menu', 'ws_glossary_admin_menu' );

/**
 * Adds the Glossary taxonomy edit page to the Tools menu.
 */
function ws_glossary_admin_menu() {
    add_management_page(
        'WhistleblowerShield Glossary',
        'WBS Glossary',
        'manage_options',
        'edit-tags.php?taxonomy=ws_glossary'
    );
}


// ════════════════════════════════════════════════════════════════════════════
// ACF Field Group: Aliases
//
// Adds a single text field to the ws_glossary taxonomy term edit screen.
// Stores aliases as a pipe-delimited string in ws_glossary_aliases term meta.
//
// Example: "qui tam lawsuit|qui tam action|false claims"
// ════════════════════════════════════════════════════════════════════════════

add_action( 'acf/init', 'ws_register_glossary_acf_fields' );

/**
 * Registers the ACF field group for the ws_glossary taxonomy term.
 */
function ws_register_glossary_acf_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'      => 'group_ws_glossary_term',
        'title'    => 'Glossary Term Settings',
        'active'   => true,
        'location' => [ [ [
            'param'    => 'taxonomy',
            'operator' => '==',
            'value'    => 'ws_glossary',
        ] ] ],
        'fields'   => [
            [
                'key'          => 'field_glossary_aliases',
                'label'        => 'Aliases',
                'name'         => 'ws_glossary_aliases',
                'type'         => 'text',
                'instructions' => 'Additional terms or phrases that should trigger this tooltip. '
                                . 'Separate multiple aliases with a pipe character ( | ). '
                                . 'Example: qui tam lawsuit|qui tam action|false claims suit. '
                                . 'Longer phrases are matched before shorter ones automatically.',
                'placeholder'  => 'alias one|alias two|alias three',
            ],
        ],
    ] );
}


// ════════════════════════════════════════════════════════════════════════════
// Transient Cache: Build and Invalidate
//
// The glossary lookup is a flat array:
//   [ term_string => definition, ... ]
//
// Both canonical terms and all aliases point to the same definition.
// Sorted by string length descending so multi-word phrases match first.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Returns the glossary lookup array, building and caching it if needed.
 *
 * @return array  Flat [ term_string => definition ] lookup, longest-first.
 */
function ws_get_glossary_lookup() {

    $cached = get_transient( 'ws_glossary_cache' );
    if ( false !== $cached ) {
        return $cached;
    }

    $terms = get_terms( [
        'taxonomy'   => 'ws_glossary',
        'hide_empty' => false,
        'number'     => 0,
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        // Cache empty result briefly to avoid hammering get_terms() on
        // every page load when no terms exist yet.
        set_transient( 'ws_glossary_cache', [], 5 * MINUTE_IN_SECONDS );
        return [];
    }

    $lookup = [];

    foreach ( $terms as $term ) {

        $definition = sanitize_text_field( $term->description );
        if ( empty( $definition ) ) {
            continue; // Skip terms with no definition — nothing to show.
        }

        // Canonical term.
        $lookup[ $term->name ] = $definition;

        // Aliases from term meta.
        $aliases_raw = get_term_meta( $term->term_id, 'ws_glossary_aliases', true );
        if ( ! empty( $aliases_raw ) ) {
            $aliases = array_filter(
                array_map( 'trim', explode( '|', $aliases_raw ) )
            );
            foreach ( $aliases as $alias ) {
                if ( ! empty( $alias ) ) {
                    $lookup[ $alias ] = $definition;
                }
            }
        }
    }

    // Sort longest string first so multi-word phrases take priority.
    uksort( $lookup, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );

    set_transient( 'ws_glossary_cache', $lookup, DAY_IN_SECONDS );

    return $lookup;
}

// Invalidate on term create or edit.
add_action( 'created_ws_glossary', 'ws_glossary_invalidate_cache' );
add_action( 'edited_ws_glossary',  'ws_glossary_invalidate_cache' );
add_action( 'delete_ws_glossary',  'ws_glossary_invalidate_cache' );

/**
 * Deletes the glossary transient cache.
 */
function ws_glossary_invalidate_cache() {
    delete_transient( 'ws_glossary_cache' );
}


// ════════════════════════════════════════════════════════════════════════════
// Scanner: ws_glossary_scan Filter
//
// Accepts an HTML string, injects tooltip spans around first occurrences
// of glossary terms in text nodes only, and returns the modified HTML.
//
// Shortcodes opt in by applying this filter to their rendered output:
//
//   $html = apply_filters( 'ws_glossary_scan', $html );
//
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'ws_glossary_scan', 'ws_apply_glossary_tooltips' );

/**
 * Scans an HTML string and injects ws-term-highlight spans around first
 * occurrences of registered glossary terms in text nodes.
 *
 * Uses DOMDocument for safe, tag-aware text node traversal. Never injects
 * markup inside existing HTML tags or attributes.
 *
 * @param  string $html  Raw HTML string from a shortcode render.
 * @return string        HTML with tooltip spans injected.
 */
function ws_apply_glossary_tooltips( $html ) {

    if ( empty( $html ) || ! is_string( $html ) ) {
        return $html;
    }

    $lookup = ws_get_glossary_lookup();
    if ( empty( $lookup ) ) {
        return $html;
    }

    // Tags whose text content must never receive tooltip injection.
    $skip_tags = [ 'a', 'span', 'abbr', 'button', 'script', 'style', 'code', 'pre' ];

    // Track which terms have already been matched in this scan pass.
    // Keyed by lowercase term string — first match wins.
    $matched = [];

    // ── DOMDocument setup ─────────────────────────────────────────────────
    //
    // DOMDocument requires valid UTF-8 and struggles with HTML5 entities
    // without a charset meta hint. Wrap content, parse, then extract body.

    $charset_hint = '<html><head><meta charset="UTF-8"></head><body>';
    $doc          = new DOMDocument();

    $prev_libxml = libxml_use_internal_errors( true );
    $doc->loadHTML( $charset_hint . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();

    $body = $doc->getElementsByTagName( 'body' )->item( 0 );
    if ( ! $body ) {
        libxml_use_internal_errors( $prev_libxml );
        return $html;
    }

    // ── Walk text nodes ───────────────────────────────────────────────────

    // Build a list of text nodes first — modifying the DOM during traversal
    // causes iterator issues with recursive descent.
    $text_nodes = [];
    ws_glossary_collect_text_nodes( $body, $skip_tags, $text_nodes );

    foreach ( $text_nodes as $text_node ) {

        $original = $text_node->nodeValue;
        if ( empty( trim( $original ) ) ) {
            continue;
        }

        // Track whether this text node was modified.
        $fragment_html = htmlspecialchars( $original, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $modified      = false;

        foreach ( $lookup as $term => $definition ) {

            $term_lower = strtolower( $term );

            // Skip if already matched in this scan pass.
            if ( isset( $matched[ $term_lower ] ) ) {
                continue;
            }

            // Whole-word, case-insensitive pattern.
            // \b word boundary anchors prevent partial-word matches.
            $pattern     = '/\b(' . preg_quote( $term, '/' ) . ')\b/iu';
            $replacement = '<span class="ws-term-highlight" data-tooltip="'
                         . esc_attr( $definition )
                         . '">$1</span>';

            // Replace first occurrence only (limit = 1).
            $new_html = preg_replace( $pattern, $replacement, $fragment_html, 1, $count );

            if ( $count > 0 && null !== $new_html ) {
                $fragment_html          = $new_html;
                $matched[ $term_lower ] = true;
                $modified               = true;
            }
        }

        if ( ! $modified ) {
            continue;
        }

        // ── Replace text node with parsed HTML fragment ───────────────────
        //
        // Since DOMText cannot contain child elements, we replace the text
        // node with a temporary container, parse the injected HTML into it,
        // then move its children into the parent and remove the container.

        $parent    = $text_node->parentNode;
        $container = $doc->createElement( 'ws-gloss-tmp' );
        $parent->replaceChild( $container, $text_node );

        $frag_doc = new DOMDocument();
        libxml_use_internal_errors( true );
        $frag_doc->loadHTML(
            '<html><head><meta charset="UTF-8"></head><body>' . $fragment_html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $frag_body = $frag_doc->getElementsByTagName( 'body' )->item( 0 );
        if ( ! $frag_body ) {
            // Restore original text node on parse failure.
            $parent->replaceChild( $text_node, $container );
            continue;
        }

        foreach ( iterator_to_array( $frag_body->childNodes ) as $child ) {
            $imported = $doc->importNode( $child, true );
            $parent->insertBefore( $imported, $container );
        }

        $parent->removeChild( $container );
    }

    // ── Extract modified body HTML ────────────────────────────────────────

    $result = '';
    foreach ( $body->childNodes as $child ) {
        $result .= $doc->saveHTML( $child );
    }

    libxml_use_internal_errors( $prev_libxml );
    return $result ?: $html;
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Collect Text Nodes
//
// Recursively walks the DOM and collects all DOMText nodes that are not
// descendants of a skip tag. Populates $text_nodes by reference.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Recursively collects DOMText nodes, skipping descendants of skip tags.
 *
 * @param  DOMNode $node        Node to walk.
 * @param  array   $skip_tags   Lowercase tag names to skip entirely.
 * @param  array   &$text_nodes Accumulator for collected DOMText nodes.
 */
function ws_glossary_collect_text_nodes( DOMNode $node, array $skip_tags, array &$text_nodes ) {

    foreach ( $node->childNodes as $child ) {

        if ( $child->nodeType === XML_TEXT_NODE ) {
            $text_nodes[] = $child;
            continue;
        }

        if ( $child->nodeType === XML_ELEMENT_NODE ) {
            if ( in_array( strtolower( $child->nodeName ), $skip_tags, true ) ) {
                continue; // Skip this subtree entirely.
            }
            ws_glossary_collect_text_nodes( $child, $skip_tags, $text_nodes );
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Seed Execution Gate
//
// Unified Option-Gate Method — key: ws_seeded_glossary / version: '1.0.0'
// Runs once on first admin_init after plugin activation.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_glossary' ) !== '1.0.0' ) {
        ws_seed_glossary_taxonomy();
        update_option( 'ws_seeded_glossary', '1.0.0' );
    }
} );


// ════════════════════════════════════════════════════════════════════════════
// Seed Function
// ════════════════════════════════════════════════════════════════════════════

/**
 * Seeds the ws_glossary taxonomy with an initial set of whistleblower legal terms.
 *
 * Each entry creates a term (name + description) and optionally sets the
 * ws_glossary_aliases term meta for pipe-delimited alias matching.
 */
function ws_seed_glossary_taxonomy() {
    $taxonomy = 'ws_glossary';
    $terms    = [

        // ── Core Whistleblower Law ────────────────────────────────────────

        'qui-tam' => [
            'name'    => 'Qui Tam',
            'desc'    => 'A provision allowing a private citizen (the relator) to sue on behalf of the government and share in any monetary recovery for fraud against the government.',
            'aliases' => 'qui tam action|qui tam lawsuit|qui tam suit',
        ],
        'relator' => [
            'name'    => 'Relator',
            'desc'    => 'A private citizen who files a qui tam lawsuit under the False Claims Act on behalf of the government.',
            'aliases' => 'whistleblower plaintiff|qui tam relator',
        ],
        'false-claims-act' => [
            'name'    => 'False Claims Act',
            'desc'    => 'Federal law imposing liability on anyone who defrauds governmental programs. Allows private citizens to file qui tam suits and share in the government\'s recovery.',
            'aliases' => 'FCA|Lincoln Law',
        ],
        'protected-disclosure' => [
            'name'    => 'Protected Disclosure',
            'desc'    => 'A report or complaint about suspected fraud, waste, or legal violations that is legally shielded from retaliation.',
            'aliases' => 'protected activity|protected report|protected complaint',
        ],
        'original-source' => [
            'name'    => 'Original Source',
            'desc'    => 'Under the False Claims Act, a person with direct and independent knowledge of the fraud who voluntarily disclosed it to the government before filing suit.',
            'aliases' => '',
        ],

        // ── Retaliation & Employment ──────────────────────────────────────

        'retaliation' => [
            'name'    => 'Retaliation',
            'desc'    => 'Adverse action taken by an employer against an employee because of a protected disclosure or whistleblower activity.',
            'aliases' => 'whistleblower retaliation|retaliatory action',
        ],
        'adverse-action' => [
            'name'    => 'Adverse Action',
            'desc'    => 'Any employment action that materially harms the whistleblower, including demotion, suspension, termination, or hostile reassignment.',
            'aliases' => 'adverse employment action|materially adverse action',
        ],
        'constructive-discharge' => [
            'name'    => 'Constructive Discharge',
            'desc'    => 'When an employer makes working conditions so intolerable that a reasonable employee is forced to resign, treated legally as an involuntary termination.',
            'aliases' => 'forced resignation|constructive termination',
        ],
        'contributing-factor' => [
            'name'    => 'Contributing Factor',
            'desc'    => 'A causation standard requiring only that protected activity was one factor in the adverse action, not the sole cause.',
            'aliases' => 'contributing factor standard',
        ],

        // ── Remedies & Recovery ───────────────────────────────────────────

        'back-pay' => [
            'name'    => 'Back Pay',
            'desc'    => 'Compensation for lost wages and benefits from the date of the adverse action to the date of judgment or settlement.',
            'aliases' => 'lost wages|back wages',
        ],
        'front-pay' => [
            'name'    => 'Front Pay',
            'desc'    => 'Compensation for future lost wages when reinstatement is not feasible.',
            'aliases' => 'future lost earnings',
        ],
        'reinstatement' => [
            'name'    => 'Reinstatement',
            'desc'    => 'A remedy requiring an employer to return a wrongfully terminated whistleblower to their former position.',
            'aliases' => '',
        ],
        'treble-damages' => [
            'name'    => 'Treble Damages',
            'desc'    => 'A False Claims Act remedy providing three times the government\'s actual damages caused by the fraud.',
            'aliases' => 'triple damages',
        ],
        'bounty' => [
            'name'    => 'Bounty',
            'desc'    => 'The whistleblower\'s share of a government recovery — typically 15–30% under the False Claims Act and SEC/CFTC programs.',
            'aliases' => 'whistleblower award|whistleblower reward|relator\'s share',
        ],

        // ── Key Statutes ──────────────────────────────────────────────────

        'sarbanes-oxley' => [
            'name'    => 'Sarbanes-Oxley Act',
            'desc'    => 'Federal law providing whistleblower protections for employees of publicly traded companies who report securities fraud or other violations.',
            'aliases' => 'SOX|Sarbanes-Oxley',
        ],
        'dodd-frank' => [
            'name'    => 'Dodd-Frank Act',
            'desc'    => 'Federal law establishing the SEC and CFTC whistleblower programs with financial awards and anti-retaliation protections for reporting securities and commodities fraud.',
            'aliases' => 'Dodd-Frank',
        ],

        // ── Process & Procedure ───────────────────────────────────────────

        'statute-of-limitations' => [
            'name'    => 'Statute of Limitations',
            'desc'    => 'The deadline by which a whistleblower must file a complaint or lawsuit. Missing this deadline typically bars the claim entirely.',
            'aliases' => 'filing deadline|limitations period',
        ],
        'tolling' => [
            'name'    => 'Tolling',
            'desc'    => 'A legal doctrine that pauses or extends the statute of limitations, such as when fraud was actively concealed.',
            'aliases' => 'equitable tolling',
        ],
        'administrative-exhaustion' => [
            'name'    => 'Administrative Exhaustion',
            'desc'    => 'The requirement under some statutes to first file a complaint with an agency (e.g., OSHA) before proceeding to federal court.',
            'aliases' => 'exhaust administrative remedies|administrative complaint',
        ],
        'seal' => [
            'name'    => 'Seal',
            'desc'    => 'Under the False Claims Act, qui tam complaints are filed under seal — kept confidential while the government investigates — for at least 60 days.',
            'aliases' => 'filed under seal|complaint under seal',
        ],
        'intervention' => [
            'name'    => 'Intervention',
            'desc'    => 'The government\'s decision to join and take over a qui tam lawsuit after investigation. Government intervention significantly increases recovery prospects.',
            'aliases' => 'government intervention|DOJ intervention',
        ],
        'declination' => [
            'name'    => 'Declination',
            'desc'    => 'The government\'s decision not to intervene in a qui tam case, leaving the relator to proceed independently.',
            'aliases' => 'government declination',
        ],
    ];

    foreach ( $terms as $slug => $data ) {
        if ( ! term_exists( $slug, $taxonomy ) ) {
            $result = wp_insert_term( $data['name'], $taxonomy, [
                'slug'        => $slug,
                'description' => $data['desc'],
            ] );
            if ( ! is_wp_error( $result ) && ! empty( $data['aliases'] ) ) {
                update_term_meta( $result['term_id'], 'ws_glossary_aliases', $data['aliases'] );
            }
        }
    }
}
