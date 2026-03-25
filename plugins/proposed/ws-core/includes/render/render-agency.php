<?php
/**
 * render-agency.php
 *
 * Render Layer — Agency Page
 *
 * PURPOSE
 * -------
 * Automatically appends filing procedure sections to the public-facing
 * ws-agency page. Intercepts WordPress content rendering via the_content
 * filter, queries procedures through the query layer, and renders them
 * in grouped sections answering the end-user question: "What do I do next?"
 *
 *
 * ARCHITECTURE
 * ------------
 *
 * ws-agency (public CPT — has_archive: 'agencies')
 *      └── ws-ag-procedure (child CPT, many-to-one via ws_proc_agency_id)
 *
 * The agency page post_content holds an editorial overview of the agency
 * (capabilities, jurisdiction, general description). This file appends
 * the structured procedures section after that content.
 *
 *
 * RENDERING MODEL
 * ---------------
 *
 *      WordPress loads agency post content
 *            ↓
 *      ws_handle_agency_render() intercepts via the_content filter
 *            ↓
 *      ws_get_agency_procedures() queries published procedures from cache
 *            ↓
 *      ws_render_agency_procedures() groups by type and renders sections
 *
 *
 * GROUPING ORDER
 * --------------
 * Procedures are grouped by type and displayed in this order:
 *
 *   1. disclosure  → "How to Report Wrongdoing"
 *   2. retaliation → "How to File a Complaint After Retaliation"
 *   3. both        → "Disclosure & Retaliation Procedure"
 *
 * Disclosure is shown first because it is the primary entry point for
 * most end-users. Retaliation procedures are elevated because deadline
 * urgency is higher — users in retaliation situations need to see the
 * deadline clearly.
 *
 *
 * DATA LAYER
 * ----------
 * All data reads go through the query layer:
 *
 *   ws_get_agency_procedures( $agency_id ) — primary data source
 *
 * This file must not call get_post_meta(), get_field(), or WP_Query directly.
 *
 *
 * LOAD ORDER
 * ----------
 * Loaded in the ASSEMBLY LAYER (frontend only) alongside other render files.
 * Depends on query-agencies.php (Universal Layer) being loaded first.
 *
 *
 * CSS CLASSES
 * -----------
 * .ws-agency-procedures            Outer wrapper for the procedures section.
 * .ws-agency-procedures__heading   "Filing Procedures" h2.
 * .ws-agency-procedures__group     Type group container (--disclosure, --retaliation, --both).
 * .ws-proc-card                    Individual procedure card.
 * .ws-proc-card__intake-only-notice  Prominent intake-only warning callout.
 * .ws-proc-card__meta              dl: identity policy, deadline, entry point.
 * .ws-proc-card__prereqs-notice    Prerequisites required warning callout.
 * .ws-proc-card__walkthrough       Step-by-step walkthrough content (WYSIWYG HTML).
 * .ws-proc-card__exclusivity-notice  Mutual exclusivity warning callout.
 * .ws-proc-card__actions           CTA button row.
 * .ws-proc-card__last-reviewed     "Verified: [date]" attribution line.
 *
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.9.0  Initial. ws_handle_agency_render(), ws_render_agency_procedures(),
 *        ws_render_agency_procedure_card(). Phase 2 of ws-ag-procedure feature.
 *        Grouped by type; deadline, identity policy, entry point in meta dl;
 *        intake-only, prerequisites, and exclusivity notice callouts.
 */

defined( 'ABSPATH' ) || exit;


// ── Label maps ───────────────────────────────────────────────────────────────
//
// Converts stored slug/key values to human-readable strings for display.
// Defined at module scope so both card and any future filter function share
// the same canonical set.

/**
 * Procedure type → section heading + subtext for the grouped display.
 * Order here controls the display order on the agency page.
 *
 * @var array<string, array{label: string, subtext: string}>
 */
$_ws_proc_type_groups = [
    'disclosure'  => [
        'label'   => 'How to Report Wrongdoing',
        'subtext' => 'Procedures for disclosing fraud, waste, abuse, or illegal activity to this agency.',
    ],
    'retaliation' => [
        'label'   => 'How to File a Complaint After Retaliation',
        'subtext' => 'If you have already experienced an adverse action, use one of the procedures below to file a formal complaint.',
    ],
    'both'        => [
        'label'   => 'Disclosure &amp; Retaliation Procedure',
        'subtext' => 'This procedure covers both initial disclosures and complaints filed after retaliation.',
    ],
];

/** @var array<string,string> Identity policy slug → plain-English label. */
$_ws_proc_identity_labels = [
    'anonymous'    => 'Anonymous — your identity is never disclosed to the agency',
    'confidential' => 'Confidential — your identity is known to the agency but will not be shared externally',
    'identified'   => 'Identified — your name will be disclosed as required by this procedure',
    'varies'       => 'Identity policy varies — see the walkthrough below for details',
];

/** @var array<string,string> Deadline clock-start slug → plain-English phrase. */
$_ws_proc_clock_labels = [
    'adverse_action' => 'the adverse action',
    'knowledge'      => 'the date you learned of the action',
    'last_act'       => 'the last act in a pattern of retaliation',
    'varies'         => 'varies — see walkthrough for details',
];

/** @var array<string,string> Entry-point slug → plain-English label. */
$_ws_proc_entry_labels = [
    'online'    => 'Online — web form or portal',
    'mail'      => 'Mail — written submission',
    'phone'     => 'Phone — hotline or direct call',
    'in_person' => 'In person — regional office',
    'multi'     => 'Multiple options available — see walkthrough',
];


/*
---------------------------------------------------------
Dispatcher
---------------------------------------------------------
*/

add_filter( 'the_content', 'ws_handle_agency_render' );

/**
 * Intercepts the_content for ws-agency and ws-ag-procedure posts.
 *
 * ws-agency:
 *   Appends grouped procedure sections beneath editorial content.
 *
 * ws-ag-procedure:
 *   Renders a standalone procedure page using procedure ACF data so
 *   publicly queryable procedure permalinks do not display as blank pages.
 *
 * Guards against:
 *   - Non-main-query loops (widgets, sidebars, REST contexts).
 *   - Non-agency post types.
 *   - Recursive calls triggered by nested do_shortcode inside the render.
 *
 * Returns original $content unchanged when no published procedures exist.
 *
 * @param  string  $content  The post content from WordPress.
 * @return string  Content with procedures section appended, or $content unchanged.
 */
function ws_handle_agency_render( $content ) {

    global $post;

    // Guard against infinite loops from nested do_shortcode calls.
    static $is_rendering = false;

    // Only run on the main query loop — not widgets, sidebars, or REST calls.
    if ( ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( ! $post || $is_rendering ) {
        return $content;
    }

    $is_rendering = true;

    if ( $post->post_type === 'ws-agency' ) {
        $procedures   = ws_get_agency_procedures( $post->ID );
        $proc_section = ws_render_agency_procedures( $procedures );
        $is_rendering = false;
        return $content . $proc_section;
    }

    if ( $post->post_type === 'ws-ag-procedure' ) {
        $rendered     = ws_render_single_agency_procedure_page( $post->ID );
        $is_rendering = false;
        return $rendered ?: $content;
    }

    $is_rendering = false;
    return $content;
}


/*
---------------------------------------------------------
Render Functions
---------------------------------------------------------
*/

// ════════════════════════════════════════════════════════════════════════════
// ws_render_agency_procedures( $procedures )
//
// Top-level render for the procedures section on an agency page.
// Groups procedures by type (disclosure → retaliation → both), renders a
// section heading for each non-empty group, and calls
// ws_render_agency_procedure_card() for each procedure in the group.
//
// Returns empty string when $procedures is empty — the agency page content
// is not modified when no published procedures exist.
//
// @param  array  $procedures  Flat array from ws_get_agency_procedures().
// @return string  HTML output, or '' when empty.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_agency_procedures( $procedures ) {

    if ( empty( $procedures ) ) {
        return '';
    }

    global $_ws_proc_type_groups;

    // Build groups in display order, using array_keys from $_ws_proc_type_groups
    // to guarantee the ordering defined there is honoured regardless of query order.
    $grouped = array_fill_keys( array_keys( $_ws_proc_type_groups ), [] );

    foreach ( $procedures as $proc ) {
        $type = $proc['type'] ?? '';
        if ( isset( $grouped[ $type ] ) ) {
            $grouped[ $type ][] = $proc;
        } else {
            // Unknown type — surface under disclosure as a safe fallback.
            $grouped['disclosure'][] = $proc;
        }
    }

    // Remove empty groups so no section heading is rendered without content.
    $grouped = array_filter( $grouped );

    ob_start();
    ?>
    <div class="ws-agency-procedures" id="ws-procedures">

        <h2 class="ws-agency-procedures__heading">Filing Procedures</h2>

        <?php foreach ( $grouped as $type => $procs ) : ?>

            <?php $group = $_ws_proc_type_groups[ $type ]; ?>

            <div class="ws-agency-procedures__group ws-agency-procedures__group--<?php echo esc_attr( $type ); ?>">

                <h3 class="ws-agency-procedures__group-heading">
                    <?php
                    // Label may contain &amp; for the 'both' type — output as HTML.
                    echo wp_kses( $group['label'], [ 'strong' => [], 'em' => [], 'a' => [ 'href' => [] ] ] );
                    ?>
                </h3>

                <?php if ( ! empty( $group['subtext'] ) ) : ?>
                    <p class="ws-agency-procedures__group-subtext">
                        <?php echo esc_html( $group['subtext'] ); ?>
                    </p>
                <?php endif; ?>

                <?php foreach ( $procs as $proc ) : ?>
                    <?php echo ws_render_agency_procedure_card( $proc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </div>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_agency_procedure_card( $proc )
//
// Renders a single procedure card. Sections rendered in order:
//
//   1. Header: title (linked to procedure permalink)
//   2. Intake-only warning (if intake_only === true) — prominent callout
//   3. Meta dl: identity policy, filing deadline (with clock start), entry point
//   4. Prerequisites notice (if has_prereqs === true)
//   5. Walkthrough (WYSIWYG HTML — sanitized with wp_kses_post)
//   6. Exclusivity notice (if exclusivity_note is set)
//   7. CTA buttons: intake form URL and/or direct phone
//   8. Last verified date
//
// Sections 2, 4, 6 are callout blocks styled to attract attention; 3 is a
// structured metadata list; 5 is the primary user-facing content.
//
// @param  array  $proc  Single procedure data array from ws_get_agency_procedures().
// @return string  HTML output.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_agency_procedure_card( $proc ) {

    global $_ws_proc_identity_labels, $_ws_proc_clock_labels, $_ws_proc_entry_labels;

    $identity_label = $_ws_proc_identity_labels[ $proc['identity_policy'] ?? '' ] ?? '';
    $entry_label    = $_ws_proc_entry_labels[    $proc['entry_point']      ?? '' ] ?? '';
    $clock_label    = $_ws_proc_clock_labels[    $proc['clock_start']      ?? '' ] ?? '';
    $deadline_days  = (int) ( $proc['deadline_days'] ?? 0 );

    ob_start();
    ?>
    <div class="ws-proc-card" id="proc-<?php echo absint( $proc['id'] ); ?>">

        <?php // ── Header ──────────────────────────────────────────────────── ?>
        <div class="ws-proc-card__header">

            <h4 class="ws-proc-card__title">
                <a href="<?php echo esc_url( $proc['url'] ); ?>">
                    <?php echo esc_html( $proc['title'] ); ?>
                </a>
            </h4>

            <?php
            // Intake-only warning: displayed immediately after the title so the
            // end-user sees it before reading the walkthrough. This prevents
            // someone from filing here under the mistaken belief enforcement
            // action will follow from this agency alone.
            ?>
            <?php if ( ! empty( $proc['intake_only'] ) ) : ?>
                <div class="ws-proc-card__intake-only-notice" role="alert">
                    <strong>Intake Only:</strong> This agency receives and refers reports — it does not investigate or adjudicate. Filing here alone does not result in enforcement action.
                </div>
            <?php endif; ?>

        </div>

        <?php // ── Metadata summary ─────────────────────────────────────────── ?>
        <?php if ( $identity_label || $deadline_days > 0 || $entry_label ) : ?>
            <dl class="ws-proc-card__meta">

                <?php if ( $identity_label ) : ?>
                    <div class="ws-proc-card__meta-row">
                        <dt>Identity Policy</dt>
                        <dd><?php echo esc_html( $identity_label ); ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ( $deadline_days > 0 ) : ?>
                    <div class="ws-proc-card__meta-row ws-proc-card__meta-row--deadline">
                        <dt>Filing Deadline</dt>
                        <dd>
                            <strong><?php echo absint( $deadline_days ); ?> days</strong>
                            <?php if ( $clock_label ) : ?>
                                from <?php echo esc_html( $clock_label ); ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ( $entry_label ) : ?>
                    <div class="ws-proc-card__meta-row">
                        <dt>How to File</dt>
                        <dd><?php echo esc_html( $entry_label ); ?></dd>
                    </div>
                <?php endif; ?>

            </dl>
        <?php endif; ?>

        <?php // ── Prerequisites notice ──────────────────────────────────────── ?>
        <?php if ( ! empty( $proc['has_prereqs'] ) ) : ?>
            <div class="ws-proc-card__prereqs-notice" role="note">
                <strong>Prerequisites Required Before Filing:</strong>
                <?php if ( ! empty( $proc['prereq_note'] ) ) : ?>
                    <?php echo esc_html( $proc['prereq_note'] ); ?>
                <?php else : ?>
                    You must satisfy certain conditions before using this procedure. See the walkthrough below for details.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php // ── Step-by-step walkthrough ───────────────────────────────────── ?>
        <?php if ( ! empty( $proc['walkthrough'] ) ) : ?>
            <div class="ws-proc-card__walkthrough">
                <h5 class="ws-proc-card__walkthrough-heading">What to Do</h5>
                <?php echo wp_kses_post( $proc['walkthrough'] ); ?>
            </div>
        <?php endif; ?>

        <?php // ── Mutual exclusivity notice ──────────────────────────────────── ?>
        <?php if ( ! empty( $proc['exclusivity_note'] ) ) : ?>
            <div class="ws-proc-card__exclusivity-notice" role="note">
                <strong>Important — Other Procedures May Be Affected:</strong>
                <?php echo esc_html( $proc['exclusivity_note'] ); ?>
            </div>
        <?php endif; ?>

        <?php
        // ── CTA buttons ──────────────────────────────────────────────────────
        //
        // aria-label includes the procedure title so that links are unique in
        // the accessibility tree when multiple "Start This Procedure" buttons
        // appear on the same page.
        ?>
        <?php if ( ! empty( $proc['intake_url'] ) || ! empty( $proc['phone'] ) ) : ?>
            <div class="ws-proc-card__actions">

                <?php if ( ! empty( $proc['intake_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $proc['intake_url'] ); ?>"
                       class="ws-btn ws-btn--primary"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Start: <?php echo esc_attr( $proc['title'] ); ?> (opens in new tab)">
                        Start This Procedure
                        <span class="screen-reader-text">(opens in new tab)</span>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $proc['phone'] ) ) : ?>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $proc['phone'] ) ); ?>"
                       class="ws-btn ws-btn--secondary"
                       aria-label="Call <?php echo esc_attr( $proc['title'] ); ?>: <?php echo esc_attr( $proc['phone'] ); ?>">
                        <?php echo esc_html( $proc['phone'] ); ?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <?php // ── Last verified ───────────────────────────────────────────────── ?>
        <?php if ( ! empty( $proc['last_reviewed'] ) ) : ?>
            <p class="ws-proc-card__last-reviewed">
                <small>Procedure last verified: <?php echo esc_html( date_i18n( 'F j, Y', strtotime( $proc['last_reviewed'] ) ) ); ?></small>
            </p>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}


/**
 * Renders full procedure content on single ws-ag-procedure permalinks.
 *
 * @param  int    $procedure_id Procedure post ID.
 * @return string HTML block for single procedure page.
 */
function ws_render_single_agency_procedure_page( $procedure_id ) {
    $proc = ws_get_agency_procedure( (int) $procedure_id );
    if ( empty( $proc ) ) {
        return '';
    }

    ob_start();
    ?>
    <section class="ws-procedure-single">
        <?php if ( ! empty( $proc['agency_name'] ) && ! empty( $proc['agency_url'] ) ) : ?>
            <p class="ws-procedure-single__agency">
                <strong>Agency:</strong>
                <a href="<?php echo esc_url( $proc['agency_url'] ); ?>">
                    <?php echo esc_html( $proc['agency_name'] ); ?>
                </a>
            </p>
        <?php endif; ?>
        <?php echo ws_render_agency_procedure_card( $proc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </section>
    <?php
    return ob_get_clean();
}
