<?php
/**
 * ws-statute-bold.php — Statute citation bold scanner.
 *
 * PURPOSE
 * -------
 * Registers the ws_statute_bold_scan filter. Wraps statute citations in
 * <strong> tags to give scanning readers a visual anchor. Two patterns:
 *
 *   Named cite:  jx_name + up to 40 chars + §{1,2} + section number
 *                e.g. "California Labor Code § 1102.5"
 *
 *   Bare cite:   §{1,2} + section number, no preceding code name
 *                e.g. "§ 1102.5" or "§§ 1981-1983"
 *
 * Runs as a straight preg_replace on the HTML string — no DOM walking.
 * Statute citations are unlikely to appear in href or data-tooltip
 * attributes, making DOM traversal unnecessary overhead here.
 *
 * INTEGRATION
 * -----------
 * Shortcodes opt in by applying the filter to their rendered HTML:
 *
 *   $html = apply_filters( 'ws_statute_bold_scan', $html, $jx_name );
 *
 * $jx_name should be the full jurisdiction name (e.g. "California").
 * Pass an empty string for contexts where no jurisdiction name is available
 * — bare cite pattern still runs.
 *
 * MATCHING RULES
 * --------------
 * - Named cites: first occurrence per unique full citation string only.
 *   Subsequent identical citations are not re-bolded.
 * - Bare cites: all occurrences are bolded — context-free by definition.
 *   Bare pattern runs only on HTML segments outside existing <strong> blocks
 *   to prevent double-wrapping citations already caught by the named pass.
 * - Trailing periods excluded from matches (sentence punctuation).
 * - Headings (h1-h3): not explicitly skipped. Revisit if bold in headings
 *   looks heavy in practice.
 *
 * SECTION NUMBER FORMAT
 * ---------------------
 * Matches digits followed by any combination of: digits, periods, hyphens,
 * parentheses, and word characters. Covers:
 *   § 1102.5       § 1102.5(b)     § 1102.5(b)(1)
 *   §§ 1981-1983   § 12940         § 2611
 * Trailing period excluded via negative lookbehind.
 *
 * @package    WhistleblowerShield
 * @since      3.10.1
 * @version    3.10.1
 */

defined( 'ABSPATH' ) || exit;


add_filter( 'ws_statute_bold_scan', 'ws_apply_statute_bold', 10, 2 );

/**
 * Wraps statute citations in <strong> tags.
 *
 * @param  string $html     HTML string from a shortcode render.
 * @param  string $jx_name  Full jurisdiction name (e.g. "California").
 * @return string           HTML with statute citations bolded.
 */
function ws_apply_statute_bold( $html, $jx_name = '' ) {

    if ( empty( $html ) || ! is_string( $html ) ) {
        return $html;
    }

    // ── Pattern 1: Named cite ─────────────────────────────────────────────
    //
    // Anchors on jx_name, allows up to 40 characters of code name text
    // between the jurisdiction name and the § symbol, then captures the
    // section number. First occurrence of each unique citation string only
    // — $matched_named guards against re-bolding the same cite twice when
    // e.g. "California Labor Code § 1102.5" appears more than once.

    if ( ! empty( $jx_name ) ) {

        $pattern_named = '/('
            . preg_quote( $jx_name, '/' )
            . '[^§]{1,40}'
            . '§{1,2}\s*'
            . '[\d]+[\d.\-()\w]*'
            . '(?<![.]))/u';

        $matched_named = [];

        $html = preg_replace_callback(
            $pattern_named,
            function( $m ) use ( &$matched_named ) {
                $cite = $m[1];
                $key  = strtolower( $cite );
                if ( isset( $matched_named[ $key ] ) ) {
                    return $cite;
                }
                $matched_named[ $key ] = true;
                return '<strong>' . $cite . '</strong>';
            },
            $html
        );
    }

    // ── Pattern 2: Bare cite ──────────────────────────────────────────────
    //
    // Matches § or §§ followed by a section number. No code name required.
    // All occurrences bolded — bare cites are unambiguous.
    //
    // To prevent double-wrapping citations already caught by the named pass,
    // the HTML is split on existing <strong>...</strong> blocks. The bare
    // pattern runs only on the segments between them (even indices after
    // PREG_SPLIT_DELIM_CAPTURE). Captured <strong> blocks (odd indices)
    // are passed through unchanged.

    $pattern_bare = '/(§{1,2}\s*[\d]+[\d.\-()\w]*(?<![.]))/u';

    $parts  = preg_split( '/(<strong>.*?<\/strong>)/us', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
    $result = '';

    foreach ( $parts as $i => $part ) {
        if ( $i % 2 === 1 ) {
            // Odd index — captured <strong> block, pass through unchanged.
            $result .= $part;
        } else {
            // Even index — plain text segment, apply bare cite pattern.
            $result .= preg_replace_callback(
                $pattern_bare,
                function( $m ) {
                    return '<strong>' . $m[1] . '</strong>';
                },
                $part
            );
        }
    }

    return $result;
}
