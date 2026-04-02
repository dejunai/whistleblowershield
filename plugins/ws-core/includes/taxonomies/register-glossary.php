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
 * Invalidated on created_ws_glossary, edited_ws_glossary, and delete_ws_glossary hooks.
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
 * @version 3.10.1
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
 * 3.8.1  try/catch/finally block in ws_apply_glossary_tooltips() normalized
 *        to consistent single-tab indentation. Misaligned closing brace on
 *        the early-return if(!$body) guard corrected.
 * 3.10.0 Three terms added: whistleblower, internal-reporting, compliance-program.
 *        Seven definitions rewritten for plain-language clarity — away from
 *        legal-test framing toward the whistleblower's perspective:
 *        qui-tam, false-claims-act, original-source, retaliation,
 *        contributing-factor, back-pay, treble-damages.
 * 3.10.1 h1, h2, h3 added to skip_tags — tooltips in headings look awkward.
 *        ws_apply_glossary_tooltips(): inner term-match loop refactored to
 *        match against $plain (the original escaped text node) only — never
 *        against the accumulating $fragment_html. Previous approach allowed
 *        terms present in an already-injected data-tooltip attribute value
 *        (e.g. "retaliation" inside the protected-disclosure definition) to
 *        be matched by a subsequent regex pass and double-injected into the
 *        attribute itself, corrupting the output. Fix: collect all matches
 *        from $plain first via preg_match, then apply spans in a second pass.
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
        'key'      => 'group_glossary_metadata',
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

        // wp_filter_kses (applied by WP on term description save) may encode
        // HTML as entities rather than stripping it. Decode first so strip_tags
        // inside sanitize_text_field() catches literal tags, then re-sanitize
        // to remove any residual whitespace or control characters.
        $definition = sanitize_text_field( html_entity_decode( $term->description, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
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
    $skip_tags = [ 'a', 'span', 'abbr', 'button', 'script', 'style', 'code', 'pre', 'h1', 'h2', 'h3' ];

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

	try {

		$doc->loadHTML( $charset_hint . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body ) {
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

			// ── Match against original text only ─────────────────────────────
			//
			// All pattern matching runs against $plain — the escaped original
			// text node value. Never against $fragment_html, which accumulates
			// injected span markup. Matching against the growing fragment would
			// allow terms found inside data-tooltip attribute values (e.g.
			// "retaliation" inside the protected-disclosure definition) to be
			// matched and double-injected into the attribute itself.
			//
			// Strategy: collect all (term → matched_text) pairs from $plain,
			// then build $fragment_html with a single preg_replace_callback
			// that replaces each matched term exactly once using its pre-built
			// span, touching only the original text positions.

			$plain   = htmlspecialchars( $original, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$pending = []; // term_lower => [ 'span' => string, 'pattern' => string ]

			foreach ( $lookup as $term => $definition ) {

				$term_lower = strtolower( $term );

				if ( isset( $matched[ $term_lower ] ) ) {
					continue;
				}

				$pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b/iu';

				// Test against $plain — the unmodified original text only.
				preg_match( $pattern, $plain, $m );
				if ( empty( $m ) ) {
					continue;
				}

				$pending[ $term_lower ] = [
					'pattern' => $pattern,
					'span'    => '<span class="ws-term-highlight" data-tooltip="'
							   . esc_attr( $definition )
							   . '">$1</span>',
				];
			}

			if ( empty( $pending ) ) {
				continue;
			}

			// Apply all pending replacements to $plain in a single pass.
			// Each term replaces exactly once (limit = 1) and is then marked
			// matched so it cannot fire again in this or any later text node.
			$fragment_html = $plain;
			$modified      = false;

			foreach ( $pending as $term_lower => $entry ) {
				$new_html = preg_replace( $entry['pattern'], $entry['span'], $fragment_html, 1, $count );
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

		return $result ?: $html;

	} catch ( Exception $e ) {
		error_log( sprintf(
			'[ws-core] Glossary scanner exception: %s (in %s line %d)',
			$e->getMessage(),
			__FILE__,
			__LINE__
		) );
		return $html;

	} finally {
		libxml_use_internal_errors( $prev_libxml );
	}
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

        // ── Core Whistleblower Terms ──────────────────────────────────────

        'whistleblower' => [
            'name'    => 'Whistleblower',
            'desc'    => 'A person who reports illegal activity, fraud, safety violations, or other wrongdoing — typically by their employer or within their organization — to someone in a position to act on it.',
            'aliases' => 'whistle-blower|whistle blower',
        ],
        'qui-tam' => [
            'name'    => 'Qui Tam',
            'desc'    => 'A type of lawsuit where a private citizen reports fraud against the government and — if the case succeeds — receives a percentage of what the government recovers. Filed under the False Claims Act.',
            'aliases' => 'qui tam action|qui tam lawsuit|qui tam suit',
        ],
        'relator' => [
            'name'    => 'Relator',
            'desc'    => 'The private citizen who files a qui tam lawsuit. The relator brings the case on behalf of the government and shares in any financial recovery.',
            'aliases' => 'whistleblower plaintiff|qui tam relator',
        ],
        'false-claims-act' => [
            'name'    => 'False Claims Act',
            'desc'    => 'A federal law that protects and rewards people who report fraud against the government. Whistleblowers can file a lawsuit, and if the government recovers money, they receive a share — typically 15–30%.',
            'aliases' => 'FCA|Lincoln Law',
        ],
        'protected-disclosure' => [
            'name'    => 'Protected Disclosure',
            'desc'    => 'A report of suspected wrongdoing that the law shields from retaliation. Whether your report qualifies as protected depends on what you reported, who you reported it to, and which law applies.',
            'aliases' => 'protected activity|protected report|protected complaint',
        ],
        'original-source' => [
            'name'    => 'Original Source',
            'desc'    => 'Under the False Claims Act, a person who has firsthand knowledge of the fraud — not just information from news or public records — and reported it to the government before filing a lawsuit.',
            'aliases' => '',
        ],

        // ── Retaliation & Employment ──────────────────────────────────────

        'retaliation' => [
            'name'    => 'Retaliation',
            'desc'    => 'When an employer punishes an employee for reporting wrongdoing or engaging in other protected activity. Retaliation can include firing, demotion, pay cuts, harassment, or other harmful actions.',
            'aliases' => 'whistleblower retaliation|retaliatory action',
        ],
        'adverse-action' => [
            'name'    => 'Adverse Action',
            'desc'    => 'Any action by an employer that harms an employee\'s job, pay, or working conditions — including termination, demotion, suspension, pay reduction, or hostile reassignment.',
            'aliases' => 'adverse employment action|materially adverse action',
        ],
        'constructive-discharge' => [
            'name'    => 'Constructive Discharge',
            'desc'    => 'When an employer deliberately makes working conditions so unbearable that an employee feels forced to quit. Courts treat this as an involuntary termination, not a voluntary resignation.',
            'aliases' => 'forced resignation|constructive termination',
        ],
        'contributing-factor' => [
            'name'    => 'Contributing Factor',
            'desc'    => 'Under many whistleblower laws, you do not have to prove that retaliation was the only reason for the adverse action — only that your protected activity was one of the reasons.',
            'aliases' => 'contributing factor standard',
        ],
        'internal-reporting' => [
            'name'    => 'Internal Reporting',
            'desc'    => 'Reporting wrongdoing through channels inside your organization — such as a supervisor, HR department, ethics hotline, or compliance officer — rather than to a government agency.',
            'aliases' => 'internal complaint|internal disclosure|internal report',
        ],
        'compliance-program' => [
            'name'    => 'Compliance Program',
            'desc'    => 'An organization\'s internal system for detecting and preventing legal violations — typically including a code of conduct, training, and a reporting mechanism such as a hotline.',
            'aliases' => '',
        ],

        // ── Remedies & Recovery ───────────────────────────────────────────

        'back-pay' => [
            'name'    => 'Back Pay',
            'desc'    => 'Money you are owed for wages and benefits lost between the time you were retaliated against and the time your case is resolved.',
            'aliases' => 'lost wages|back wages',
        ],
        'front-pay' => [
            'name'    => 'Front Pay',
            'desc'    => 'Compensation for future lost wages when returning to your old job is not possible — for example, when the workplace relationship has broken down beyond repair.',
            'aliases' => 'future lost earnings',
        ],
        'reinstatement' => [
            'name'    => 'Reinstatement',
            'desc'    => 'A court order requiring your employer to give you your job back after a wrongful termination.',
            'aliases' => '',
        ],
        'treble-damages' => [
            'name'    => 'Treble Damages',
            'desc'    => 'A False Claims Act penalty that requires a wrongdoer to pay three times the amount of harm caused. For whistleblowers, this multiplier significantly increases the government\'s recovery — and the relator\'s share.',
            'aliases' => 'triple damages',
        ],
        'bounty' => [
            'name'    => 'Bounty',
            'desc'    => 'The financial award a whistleblower receives when their report leads to a successful government enforcement action. Typically 15–30% of the amount recovered under the False Claims Act and SEC/CFTC programs.',
            'aliases' => 'whistleblower award|whistleblower reward|relator\'s share',
        ],

        // ── Key Statutes ──────────────────────────────────────────────────

        'sarbanes-oxley' => [
            'name'    => 'Sarbanes-Oxley Act',
            'desc'    => 'A federal law protecting employees of publicly traded companies who report securities fraud, accounting violations, or other corporate wrongdoing.',
            'aliases' => 'SOX|Sarbanes-Oxley',
        ],
        'dodd-frank' => [
            'name'    => 'Dodd-Frank Act',
            'desc'    => 'A federal law that created the SEC and CFTC whistleblower programs. Offers financial awards for reporting securities and commodities fraud and protects whistleblowers from retaliation.',
            'aliases' => 'Dodd-Frank',
        ],

        // ── Process & Procedure ───────────────────────────────────────────

        'statute-of-limitations' => [
            'name'    => 'Statute of Limitations',
            'desc'    => 'The deadline for filing a complaint or lawsuit. If you miss it, your legal claim is typically gone permanently — no matter how strong your case is.',
            'aliases' => 'filing deadline|limitations period',
        ],
        'tolling' => [
            'name'    => 'Tolling',
            'desc'    => 'A legal exception that can pause or extend a filing deadline — for example, when the employer actively concealed the retaliation, or when the whistleblower was a minor.',
            'aliases' => 'equitable tolling',
        ],
        'administrative-exhaustion' => [
            'name'    => 'Administrative Exhaustion',
            'desc'    => 'Some laws require you to file a complaint with a government agency first — such as OSHA — before you can take your case to federal court. Skipping this step can forfeit your right to sue.',
            'aliases' => 'exhaust administrative remedies|administrative complaint',
        ],
        'seal' => [
            'name'    => 'Seal',
            'desc'    => 'Under the False Claims Act, qui tam lawsuits are filed confidentially while the government decides whether to investigate. The case stays sealed — hidden from the public and the defendant — for at least 60 days.',
            'aliases' => 'filed under seal|complaint under seal',
        ],
        'intervention' => [
            'name'    => 'Intervention',
            'desc'    => 'When the government decides to join a qui tam lawsuit and take the lead on prosecuting it. Government intervention greatly improves the odds of a successful outcome.',
            'aliases' => 'government intervention|DOJ intervention',
        ],
        'declination' => [
            'name'    => 'Declination',
            'desc'    => 'When the government decides not to join a qui tam lawsuit. The whistleblower can still pursue the case independently, but without the government\'s resources behind it.',
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