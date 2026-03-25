<?php
/**
 * admin-health-check.php
 *
 * Runtime dependency checks surfaced as admin_notices.
 *
 * PURPOSE
 * -------
 * Verifies that the four dependencies most likely to fail silently are
 * actually in place after all plugin files have loaded:
 *
 *   1. ACF active          — function_exists('acf_add_local_field_group')
 *                            If ACF deactivates, all field groups silently vanish.
 *
 *   2. Core CPTs registered — post_type_exists() for each content CPT.
 *                            Catches loader ordering regressions during development.
 *
 *   3. Core taxonomy registered — taxonomy_exists('ws_jurisdiction').
 *                            Catches taxonomy load failures; breaks term queries,
 *                            metabox jurisdiction guard, and Add URL tax_input param.
 *
 *   4. Query layer callable — function_exists() for the two top-level query sentinels.
 *                            Confirms query-jurisdiction.php loaded and assembled
 *                            correctly (depends on query-helpers and query-shared).
 *
 *   5. Procedure seeder gate — verifies ws_seeded_procedure_matrix reached 1.0.0
 *                            and confirms published procedure count is non-zero
 *                            once that gate has been marked complete.
 *
 * BEHAVIOR
 * --------
 * All checks run on admin_notices (fires after init, after all plugins loaded).
 * Failures are collected into a single consolidated error notice shown to
 * administrator-role users only. No notice is shown when everything is healthy.
 *
 * @package    WhistleblowerShield
 * @since      3.6.1
 * @author     Dejunai
 *
 * VERSION
 * -------
 * 3.6.1  Initial release.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_notices', 'ws_health_check_admin_notice' );

/**
 * Collects runtime dependency failures and renders a single error notice.
 *
 * Shown only to administrator-role users. Silent when all checks pass.
 */
function ws_health_check_admin_notice() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $issues = [];

    // ── 1. ACF availability ───────────────────────────────────────────────
    //
    // acf_add_local_field_group() is the canonical ACF function used by every
    // ws-core ACF registration file. If it is absent, ACF is inactive and all
    // custom fields are silently gone — posts save with no meta.

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        $issues[] = 'Advanced Custom Fields (ACF) is not active. All custom fields are unavailable.';
    }

    // ── 2. Core CPT registration ──────────────────────────────────────────
    //
    // Checks the six content CPTs that the query and render layers depend on.
    // A missing CPT causes 404s on existing posts and breaks query results.

    $required_cpts = [
        'jx-statute',
        'jx-citation',
        'jx-interpretation',
        'jurisdiction',
        'ws-agency',
        'ws-assist-org',
    ];

    foreach ( $required_cpts as $cpt ) {
        if ( ! post_type_exists( $cpt ) ) {
            $issues[] = "CPT not registered: <code>{$cpt}</code>";
        }
    }

    // ── 3. Core taxonomy registration ─────────────────────────────────────
    //
    // ws_jurisdiction drives the jurisdiction metabox guard, the tax_input
    // pre-fill on interpretation/citation add URLs, and front-end term queries.

    if ( ! taxonomy_exists( WS_JURISDICTION_TAXONOMY ) ) {
        $issues[] = "Taxonomy not registered: <code>" . WS_JURISDICTION_TAXONOMY . "</code>";
    }

    // ── 4. Query layer sentinels ──────────────────────────────────────────
    //
    // ws_get_jurisdiction_data()       — core single-jurisdiction dataset builder
    //                                   (defined in query-jurisdiction.php)
    // ws_get_jurisdiction_index_data() — directory index dataset builder
    //                                   (defined in query-jurisdiction.php; depends
    //                                    on query-helpers and query-shared loading first)
    //
    // If either is absent the render and shortcode layers will fatal on first call.

    $required_fns = [
        'ws_get_jurisdiction_data'       => 'query-jurisdiction.php',
        'ws_get_jurisdiction_index_data' => 'query-jurisdiction.php',
    ];

    foreach ( $required_fns as $fn => $source ) {
        if ( ! function_exists( $fn ) ) {
            $issues[] = "Query function not callable: <code>{$fn}()</code> (expected from {$source})";
        }
    }

    // ── 5. Procedure seeder gate ─────────────────────────────────────────
    //
    // ws_seeded_procedure_matrix marks whether the procedure matrix seeder
    // has run. Once set to 1.0.0, zero published procedures is an integrity
    // failure worth surfacing in admin notices.

    $proc_seed_gate = (string) get_option( 'ws_seeded_procedure_matrix', '' );
    if ( $proc_seed_gate === '' || version_compare( $proc_seed_gate, '1.0.0', '<' ) ) {
        $issues[] = "Procedure seeder gate incomplete: <code>ws_seeded_procedure_matrix</code> expected <code>1.0.0</code>, found <code>" . esc_html( $proc_seed_gate ?: '(not set)' ) . '</code>.';
    } else {
        $proc_counts = wp_count_posts( 'ws-ag-procedure' );
        $proc_total  = (int) ( $proc_counts->publish ?? 0 );
        if ( $proc_total < 1 ) {
            $issues[] = 'Procedure seeder gate is marked complete but there are 0 published procedures.';
        }
    }

    // ── Render ────────────────────────────────────────────────────────────

    if ( empty( $issues ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>'
        . '<strong>WhistleblowerShield — dependency check failed:</strong>'
        . '</p><ul style="margin:.4em 0 0 1.2em;list-style:disc;">';

    foreach ( $issues as $issue ) {
        echo '<li>' . wp_kses( $issue, [ 'code' => [] ] ) . '</li>';
    }

    echo '</ul></div>';
}
