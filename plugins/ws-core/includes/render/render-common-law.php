<?php
/**
 * render-common-law.php
 *
 * Render Layer — Common Law Protection Record Renderers
 *
 * PURPOSE
 * -------
 * Provides HTML rendering functions for jx-common-law records on
 * jurisdiction pages. Called by shortcodes-jurisdiction.php or the
 * jurisdiction page assembler (render-jurisdiction.php).
 *
 * Common law records differ from statute records in two key ways:
 *   1. The anchor is a judicial doctrine, not a statute section.
 *      ws_cl_doctrine_basis and ws_cl_recognition_status are the
 *      primary content fields — both WYSIWYG.
 *   2. The statutory preclusion flag (ws_cl_statutory_preclusion)
 *      must be surfaced clearly when true — users need to know
 *      that a statutory remedy may block this claim.
 *
 *
 * ARCHITECTURE ROLE
 * -----------------
 *
 *   Assembler:   render-jurisdiction.php   — triggers shortcodes
 *   Shortcodes:  shortcodes-jurisdiction.php — calls functions here
 *   Data:        query-jurisdiction.php    — ws_get_jx_common_law_data()
 *
 *
 * FUNCTIONS
 * ---------
 *   ws_render_jx_common_law()   Renders all attached common law records
 *                               for a jurisdiction. STUB — implement in
 *                               Phase 2 or when Wyoming data build begins.
 *
 *
 * STUB STATUS
 * -----------
 * This file is a render stub. ws_render_jx_common_law() returns an empty
 * string and logs a debug notice. Full implementation is deferred until
 * the Wyoming data build and jurisdiction page layout work begins.
 *
 * Implementation notes for when this stub is filled:
 *   - Group local and federal doctrine records (is_fed flag) as the
 *     statute renderer does via ws_render_section_two_group().
 *   - Render ws_cl_doctrine_basis and ws_cl_recognition_status as
 *     WYSIWYG output (use wp_kses_post() not esc_html()).
 *   - Surface ws_cl_statutory_preclusion as a prominent notice when
 *     true — this is a critical user-facing signal that a statutory
 *     remedy may block the common law claim.
 *   - SOL for common law is almost always ambiguous (borrowed period) —
 *     render the sol_details field prominently when sol_has_details is
 *     true.
 *
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
 * 3.13.0  Initial stub. Parallel to render-section.php statute renderers.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Common Law Section Renderer — STUB
// ════════════════════════════════════════════════════════════════════════════

/**
 * Renders all attached jx-common-law records for a jurisdiction.
 *
 * STUB — returns empty string. Implement when Wyoming data build begins.
 *
 * @param array $common_law_data  Output of ws_get_jx_common_law_data().
 * @param array $options          Optional render options (reserved for Phase 2).
 * @return string                 Rendered HTML or empty string.
 */
function ws_render_jx_common_law( array $common_law_data, array $options = [] ): string {

    if ( empty( $common_law_data ) ) {
        return '';
    }

    // STUB — full implementation deferred.
    // See STUB STATUS in file docblock for implementation notes.
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[ws-core] ws_render_jx_common_law() called but not yet implemented — ' . count( $common_law_data ) . ' record(s) available.' );
    }

    return '';
}
