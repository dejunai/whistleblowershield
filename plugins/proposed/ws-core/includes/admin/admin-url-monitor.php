<?php
/**
 * admin-url-monitor.php
 *
 * URL Health Monitor for ws-core seeded and editorial records.
 *
 * PURPOSE
 * -------
 * Checks standard URL meta fields across jurisdiction, agency, and assist-org
 * CPTs every 10 days via WP-Cron. High-priority procedure intake URLs run on a
 * separate 3-day schedule. Failures (4xx/5xx) and warnings (redirects) are
 * stored in a structured option and surfaced via a dashboard widget.
 * Administrator-role users are notified by email on failures and recoveries.
 *
 * BEHAVIOR
 * --------
 * On each run:
 *   1. Loop all published posts in each monitored CPT.
 *   2. For each known URL meta key, fire wp_remote_head() with a 10s timeout.
 *   3. Classify response:
 *        2xx            — pass  (clear from log if previously flagged; email recovery)
 *        3xx            — warning (logged separately from failures)
 *        4xx / 5xx      — failure (logged; email sent to all administrators)
 *        WP_Error       — unreachable (skipped; retried next run; not logged)
 *   4. Persist results to ws_url_monitor_log option.
 *   5. Send email digests to all administrator-role users.
 *
 * CRON SCHEDULE
 * -------------
 * Custom schedules:
 *   ws_every_ten_days   (864000 seconds) — standard URLs
 *   ws_every_three_days (259200 seconds) — high-priority procedure intake URLs
 * WP-Cron fires on page load — interval is approximate on low-traffic sites.
 * For guaranteed timing, add a server-side crontab:
 *
 *   Every 5 minutes:
 *   curl -s https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null
 *
 * URL CONFIG MAP
 * --------------
 * $ws_url_monitor_map defines which meta keys to check per CPT. To add a new
 * CPT or URL field, add an entry here — no other changes required.
 *
 * OPTION KEYS
 * -----------
 * ws_url_monitor_log     — structured failure/warning log (array)
 * ws_url_monitor_last_run — Unix timestamp of last completed run
 *
 * LOG ENTRY SHAPE
 * ---------------
 * [
 *     'post_id'    => int,
 *     'post_title' => string,
 *     'post_type'  => string,
 *     'meta_key'   => string,
 *     'url'        => string,
 *     'status'     => int,       // HTTP status code
 *     'type'       => string,    // 'failure' | 'warning'
 *     'detected'   => string,    // Y-m-d H:i:s local
 * ]
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
 * 3.2.1  Added inline comment to direct meta read in monitor loop explaining
 *        why the query layer is not used in WP-Cron context.
 * 3.2.2  Added admin_notices banner surfacing active URL failures to all
 *        admin screens, linking to the Dashboard widget.
 * 3.8.1  posts_per_page => 1000 ceiling on get_posts() in cron handler.
 *        detected timestamp now preserved for existing warnings/failures so
 *        cron reruns no longer reset the "first seen" date to today.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// URL Config Map
//
// Defines which meta keys to check per CPT. Add new CPTs or URL fields here.
// All keys must be registered post meta — no ACF field keys, no field names.
// ════════════════════════════════════════════════════════════════════════════

$ws_url_monitor_map = [
    'jurisdiction' => [
        'ws_jx_gov_portal_url',
        'ws_jx_wb_authority_url',
        'ws_jx_legislature_url',
        'ws_jx_executive_url',
        'ws_jx_flag_source_url',
    ],
    'ws-agency' => [
        'ws_agency_url',
        'ws_agency_reporting_url',
    ],
    'ws-assist-org' => [
        'ws_aorg_website_url',
        'ws_aorg_intake_url',
        'ws_aorg_verify_url',
    ],
];

$ws_url_monitor_priority_map = [
    'ws-ag-procedure' => [
        'ws_proc_intake_url',
    ],
];


// ════════════════════════════════════════════════════════════════════════════
// Custom Cron Schedule
//
// Registers a ws_every_ten_days interval. WordPress does not include a
// 10-day schedule natively.
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'cron_schedules', 'ws_url_monitor_register_schedule' );

/**
 * Adds the ws_every_ten_days cron schedule.
 *
 * @param  array $schedules  Existing WP-Cron schedules.
 * @return array
 */
function ws_url_monitor_register_schedule( $schedules ) {
    $schedules['ws_every_ten_days'] = [
        'interval' => 864000, // 10 days in seconds
        'display'  => __( 'Every 10 Days (WhistleblowerShield URL Monitor)' ),
    ];
    $schedules['ws_every_three_days'] = [
        'interval' => 259200, // 3 days in seconds
        'display'  => __( 'Every 3 Days (WhistleblowerShield Priority URL Monitor)' ),
    ];
    return $schedules;
}


// ════════════════════════════════════════════════════════════════════════════
// Cron Event Registration
//
// Schedules the event on activation-equivalent (admin_init gate). Unschedules
// on plugin deactivation.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', 'ws_url_monitor_maybe_schedule' );

/**
 * Schedules the ws_url_health_check event if not already scheduled.
 */
function ws_url_monitor_maybe_schedule() {
    if ( ! wp_next_scheduled( 'ws_url_health_check' ) ) {
        wp_schedule_event( time(), 'ws_every_ten_days', 'ws_url_health_check' );
    }
    if ( ! wp_next_scheduled( 'ws_url_priority_health_check' ) ) {
        wp_schedule_event( time(), 'ws_every_three_days', 'ws_url_priority_health_check' );
    }
}

/**
 * Unschedules the cron event. Call on plugin deactivation.
 * Hook this to register_deactivation_hook() in your main plugin file.
 */
function ws_url_monitor_deactivate() {
    $timestamp = wp_next_scheduled( 'ws_url_health_check' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'ws_url_health_check' );
    }
    $priority_timestamp = wp_next_scheduled( 'ws_url_priority_health_check' );
    if ( $priority_timestamp ) {
        wp_unschedule_event( $priority_timestamp, 'ws_url_priority_health_check' );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Cron Handler: ws_url_health_check
//
// Main check loop. Runs on the ws_url_health_check cron event.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'ws_url_health_check', 'ws_run_url_health_check' );
add_action( 'ws_url_priority_health_check', 'ws_run_url_priority_health_check' );

/**
 * Loops all monitored CPTs and URL meta keys, checks each URL, classifies
 * the response, updates the log, and sends email digests.
 */
function ws_run_url_health_check() {
    global $ws_url_monitor_map;
    ws_url_monitor_run_check_for_map( $ws_url_monitor_map );
}

/**
 * Runs the high-priority URL check loop (procedures).
 */
function ws_run_url_priority_health_check() {
    global $ws_url_monitor_priority_map;
    ws_url_monitor_run_check_for_map( $ws_url_monitor_priority_map );
}

/**
 * Shared URL monitor loop for a supplied CPT/meta map.
 *
 * @param array $monitor_map Post type => URL meta keys map.
 */
function ws_url_monitor_run_check_for_map( array $monitor_map ) {

    $previous_log = get_option( 'ws_url_monitor_log', [] );
    $new_log      = [];
    $new_failures = [];
    $new_warnings = [];
    $recoveries   = [];

    // Build a lookup of previously flagged URLs for recovery detection.
    // Keyed by "post_id|meta_key" for O(1) lookup.
    $previous_flagged = [];
    foreach ( $previous_log as $entry ) {
        $previous_flagged[ $entry['post_id'] . '|' . $entry['meta_key'] ] = $entry;
    }

    foreach ( $monitor_map as $post_type => $meta_keys ) {

        // Limit per CPT — fetching IDs only (fields=ids) is cheap, but an
        // unbounded query on a very large dataset can still cause memory issues
        // on cron. 1000 per CPT covers any realistic editorial scale.
        $posts = get_posts( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );

        if ( empty( $posts ) ) {
            continue;
        }

        foreach ( $posts as $post_id ) {

            foreach ( $meta_keys as $meta_key ) {

                // Direct meta read — URL monitor needs the raw stored value to perform HTTP
                // validation. The query layer returns rendered shortcode output, not individual
                // field values, and is not available in this WP-Cron context.
                $url = get_post_meta( $post_id, $meta_key, true );

                // Skip empty or non-string values.
                if ( empty( $url ) || ! is_string( $url ) ) {
                    continue;
                }

                $url = esc_url_raw( trim( $url ) );
                if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
                    continue;
                }

                $log_key = $post_id . '|' . $meta_key;

                $response = wp_remote_head( $url, [
                    'timeout'     => 10,
                    'redirection' => 0, // Do not follow redirects — detect them as warnings.
                    'user-agent'  => 'WhistleblowerShield URL Monitor/1.0',
                ] );

                // WP_Error means unreachable — skip silently, retry next run.
                if ( is_wp_error( $response ) ) {
                    // If previously flagged, preserve the existing log entry
                    // so it is not silently cleared by an unreachable response.
                    if ( isset( $previous_flagged[ $log_key ] ) ) {
                        $new_log[] = $previous_flagged[ $log_key ];
                    }
                    continue;
                }

                $status     = (int) wp_remote_retrieve_response_code( $response );
                $post_title = get_the_title( $post_id );

                if ( $status >= 200 && $status < 300 ) {

                    // Pass — check for recovery.
                    if ( isset( $previous_flagged[ $log_key ] ) ) {
                        $recoveries[] = [
                            'post_id'    => $post_id,
                            'post_title' => $post_title,
                            'post_type'  => $post_type,
                            'meta_key'   => $meta_key,
                            'url'        => $url,
                            'status'     => $status,
                        ];
                    }

                } elseif ( $status >= 300 && $status < 400 ) {

                    // Warning — redirect detected.
                    // Preserve the original detected timestamp if this URL was
                    // already in the log so the admin can see when it first appeared
                    // rather than having the date reset on every cron run.
                    $is_new_warning = ! isset( $previous_flagged[ $log_key ] );
                    $entry = [
                        'post_id'    => $post_id,
                        'post_title' => $post_title,
                        'post_type'  => $post_type,
                        'meta_key'   => $meta_key,
                        'url'        => $url,
                        'status'     => $status,
                        'type'       => 'warning',
                        'detected'   => $is_new_warning
                            ? current_time( 'Y-m-d H:i:s' )
                            : $previous_flagged[ $log_key ]['detected'],
                    ];

                    $new_log[] = $entry;

                    if ( $is_new_warning ) {
                        $new_warnings[] = $entry;
                    }

                } elseif ( $status >= 400 ) {

                    // Failure — 4xx or 5xx.
                    // Same timestamp-preservation logic as warnings above.
                    $is_new_failure = ! isset( $previous_flagged[ $log_key ] );
                    $entry = [
                        'post_id'    => $post_id,
                        'post_title' => $post_title,
                        'post_type'  => $post_type,
                        'meta_key'   => $meta_key,
                        'url'        => $url,
                        'status'     => $status,
                        'type'       => 'failure',
                        'detected'   => $is_new_failure
                            ? current_time( 'Y-m-d H:i:s' )
                            : $previous_flagged[ $log_key ]['detected'],
                    ];

                    $new_log[] = $entry;

                    if ( $is_new_failure ) {
                        $new_failures[] = $entry;
                    }
                }
            }
        }
    }

    // Persist updated log and last-run timestamp.
    update_option( 'ws_url_monitor_log',      $new_log );
    update_option( 'ws_url_monitor_last_run', time() );

    // Send email digests.
    if ( ! empty( $new_failures ) || ! empty( $new_warnings ) ) {
        ws_url_monitor_send_failure_email( $new_failures, $new_warnings );
    }
    if ( ! empty( $recoveries ) ) {
        ws_url_monitor_send_recovery_email( $recoveries );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Get Administrator Emails
//
// Returns an array of email addresses for all users with the administrator
// role. Used by both email functions below.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Returns email addresses for all administrator-role users.
 *
 * @return string[]
 */
function ws_url_monitor_get_admin_emails() {
    $admins = get_users( [ 'role' => 'administrator' ] );
    $emails = [];
    foreach ( $admins as $admin ) {
        if ( ! empty( $admin->user_email ) ) {
            $emails[] = $admin->user_email;
        }
    }
    return $emails;
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Send Failure / Warning Email
// ════════════════════════════════════════════════════════════════════════════

/**
 * Sends a digest email to all administrators listing new URL failures and
 * warnings detected in the most recent health check run.
 *
 * @param  array $failures  New failure entries.
 * @param  array $warnings  New warning entries.
 */
function ws_url_monitor_send_failure_email( array $failures, array $warnings ) {

    $emails = ws_url_monitor_get_admin_emails();
    if ( empty( $emails ) ) {
        return;
    }

    $site    = get_bloginfo( 'name' );
    $subject = "[{$site}] URL Health Monitor — Issues Detected";

    $body  = "The WhistleblowerShield URL Health Monitor completed a check and found the following issues.\n\n";
    $body .= "Dashboard: " . admin_url( 'index.php' ) . "\n\n";

    if ( ! empty( $failures ) ) {
        $body .= "── FAILURES (" . count( $failures ) . ") ──────────────────────────────\n\n";
        foreach ( $failures as $entry ) {
            $edit_url = get_edit_post_link( $entry['post_id'], 'raw' );
            $body    .= "Post:     {$entry['post_title']} ({$entry['post_type']})\n";
            $body    .= "Field:    {$entry['meta_key']}\n";
            $body    .= "URL:      {$entry['url']}\n";
            $body    .= "Status:   HTTP {$entry['status']}\n";
            $body    .= "Edit:     {$edit_url}\n\n";
        }
    }

    if ( ! empty( $warnings ) ) {
        $body .= "── WARNINGS — REDIRECTS (" . count( $warnings ) . ") ──────────────────\n\n";
        $body .= "These URLs returned a redirect response. The destination may have changed.\n\n";
        foreach ( $warnings as $entry ) {
            $edit_url = get_edit_post_link( $entry['post_id'], 'raw' );
            $body    .= "Post:     {$entry['post_title']} ({$entry['post_type']})\n";
            $body    .= "Field:    {$entry['meta_key']}\n";
            $body    .= "URL:      {$entry['url']}\n";
            $body    .= "Status:   HTTP {$entry['status']}\n";
            $body    .= "Edit:     {$edit_url}\n\n";
        }
    }

    $body .= "──────────────────────────────────────────────────────────\n";
    $body .= "WhistleblowerShield URL Health Monitor\n";
    $body .= get_bloginfo( 'url' ) . "\n";

    foreach ( $emails as $email ) {
        wp_mail( $email, $subject, $body );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Send Recovery Email
// ════════════════════════════════════════════════════════════════════════════

/**
 * Sends a digest email to all administrators listing URLs that recovered
 * since the previous health check run.
 *
 * @param  array $recoveries  Recovery entries.
 */
function ws_url_monitor_send_recovery_email( array $recoveries ) {

    $emails = ws_url_monitor_get_admin_emails();
    if ( empty( $emails ) ) {
        return;
    }

    $site    = get_bloginfo( 'name' );
    $subject = "[{$site}] URL Health Monitor — URLs Recovered";

    $body  = "The following URLs that were previously flagged are now responding successfully.\n\n";

    foreach ( $recoveries as $entry ) {
        $body .= "Post:     {$entry['post_title']} ({$entry['post_type']})\n";
        $body .= "Field:    {$entry['meta_key']}\n";
        $body .= "URL:      {$entry['url']}\n";
        $body .= "Status:   HTTP {$entry['status']}\n\n";
    }

    $body .= "──────────────────────────────────────────────────────────\n";
    $body .= "WhistleblowerShield URL Health Monitor\n";
    $body .= get_bloginfo( 'url' ) . "\n";

    foreach ( $emails as $email ) {
        wp_mail( $email, $subject, $body );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Dashboard Widget
//
// Displays the current URL monitor log grouped by type (failures first,
// then warnings). Includes last-run timestamp and a manual trigger button.
// Visible to administrators only.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'wp_dashboard_setup', 'ws_url_monitor_register_widget' );

/**
 * Registers the URL monitor dashboard widget for administrators.
 */
function ws_url_monitor_register_widget() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    wp_add_dashboard_widget(
        'ws_url_monitor_widget',
        'WhistleblowerShield — URL Health Monitor',
        'ws_url_monitor_render_widget'
    );
}

/**
 * Renders the URL monitor dashboard widget.
 */
function ws_url_monitor_render_widget() {

    $log      = get_option( 'ws_url_monitor_log', [] );
    $last_run = get_option( 'ws_url_monitor_last_run', 0 );
    $next_run          = wp_next_scheduled( 'ws_url_health_check' );
    $next_priority_run = wp_next_scheduled( 'ws_url_priority_health_check' );

    // ── Manual trigger ────────────────────────────────────────────────────

    if ( isset( $_POST['ws_url_monitor_run_now'] )
        && check_admin_referer( 'ws_url_monitor_trigger' )
        && current_user_can( 'manage_options' )
    ) {
        ws_run_url_health_check();
        echo '<div class="notice notice-success inline"><p>Standard URL health check completed.</p></div>';
        $log      = get_option( 'ws_url_monitor_log', [] );
        $last_run = get_option( 'ws_url_monitor_last_run', 0 );
    }

    if ( isset( $_POST['ws_url_monitor_run_priority_now'] )
        && check_admin_referer( 'ws_url_monitor_trigger_priority' )
        && current_user_can( 'manage_options' )
    ) {
        ws_run_url_priority_health_check();
        echo '<div class="notice notice-success inline"><p>Priority URL health check completed.</p></div>';

        // Refresh values after manual run.
        $log      = get_option( 'ws_url_monitor_log', [] );
        $last_run = get_option( 'ws_url_monitor_last_run', 0 );
    }

    // ── Meta bar ──────────────────────────────────────────────────────────

    $last_run_str = $last_run
        ? esc_html( date_i18n( 'Y-m-d H:i', $last_run ) )
        : 'Never';
    $next_run_str = $next_run
        ? esc_html( date_i18n( 'Y-m-d H:i', $next_run ) )
        : 'Not scheduled';
    $next_priority_run_str = $next_priority_run
        ? esc_html( date_i18n( 'Y-m-d H:i', $next_priority_run ) )
        : 'Not scheduled';

    echo '<p style="color:#999;font-size:11px;margin-top:0;">'
        . 'Last run: ' . $last_run_str
        . ' &nbsp;|&nbsp; Next standard run: ' . $next_run_str
        . ' &nbsp;|&nbsp; Next priority run: ' . $next_priority_run_str
        . '</p>';

    // ── Manual trigger form ───────────────────────────────────────────────

    echo '<form method="post">';
    wp_nonce_field( 'ws_url_monitor_trigger' );
    echo '<input type="hidden" name="ws_url_monitor_run_now" value="1">';
    submit_button( 'Run Standard Check Now', 'secondary small', '', false );
    echo '</form>';

    echo '<form method="post" style="margin-top:8px;">';
    wp_nonce_field( 'ws_url_monitor_trigger_priority' );
    echo '<input type="hidden" name="ws_url_monitor_run_priority_now" value="1">';
    submit_button( 'Run Priority Check Now', 'secondary small', '', false );
    echo '</form><br>';

    // ── Log output ────────────────────────────────────────────────────────

    if ( empty( $log ) ) {
        echo '<p style="color:#46b450;">&#10003; No URL failures or warnings detected.</p>';
        return;
    }

    $failures = array_filter( $log, fn( $e ) => $e['type'] === 'failure' );
    $warnings = array_filter( $log, fn( $e ) => $e['type'] === 'warning' );

    if ( ! empty( $failures ) ) {
        echo '<p><strong style="color:#dc3232;">&#10007; Failures (' . count( $failures ) . ')</strong></p>';
        echo '<ul style="margin:0 0 12px;padding-left:1.2em;">';
        foreach ( $failures as $entry ) {
            $edit_url = get_edit_post_link( $entry['post_id'] );
            $pt       = get_post_type_object( $entry['post_type'] );
            $pt_name  = $pt ? $pt->labels->singular_name : $entry['post_type'];
            echo '<li style="margin-bottom:6px;">';
            echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $entry['post_title'] ) . '</a> ';
            echo '<span style="color:#999;font-size:11px;">(' . esc_html( $pt_name ) . ')</span><br>';
            echo '<span style="font-size:11px;color:#dc3232;">HTTP ' . esc_html( $entry['status'] ) . '</span> ';
            echo '<span style="font-size:11px;color:#999;">' . esc_html( $entry['meta_key'] ) . ' &mdash; ';
            echo '<a href="' . esc_url( $entry['url'] ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $entry['url'] ) . '</a></span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    if ( ! empty( $warnings ) ) {
        echo '<p><strong style="color:#ffb900;">&#9888; Warnings — Redirects (' . count( $warnings ) . ')</strong></p>';
        echo '<p style="font-size:11px;color:#666;margin-top:-8px;">These URLs redirect. The destination may have changed.</p>';
        echo '<ul style="margin:0;padding-left:1.2em;">';
        foreach ( $warnings as $entry ) {
            $edit_url = get_edit_post_link( $entry['post_id'] );
            $pt       = get_post_type_object( $entry['post_type'] );
            $pt_name  = $pt ? $pt->labels->singular_name : $entry['post_type'];
            echo '<li style="margin-bottom:6px;">';
            echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $entry['post_title'] ) . '</a> ';
            echo '<span style="color:#999;font-size:11px;">(' . esc_html( $pt_name ) . ')</span><br>';
            echo '<span style="font-size:11px;color:#ffb900;">HTTP ' . esc_html( $entry['status'] ) . '</span> ';
            echo '<span style="font-size:11px;color:#999;">' . esc_html( $entry['meta_key'] ) . ' &mdash; ';
            echo '<a href="' . esc_url( $entry['url'] ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $entry['url'] ) . '</a></span>';
            echo '</li>';
        }
        echo '</ul>';
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Admin Notice
//
// Shows a persistent error banner across all admin screens when the URL
// monitor log contains active failure entries. Visible to administrators
// only. Links to the Dashboard where the full widget is rendered.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_notices', 'ws_url_monitor_admin_notice' );

/**
 * Displays an error admin notice when active URL failures are logged.
 *
 * Shown on every admin screen to all administrator-role users until the
 * failures are resolved and the monitor runs again to clear them.
 */
function ws_url_monitor_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $log      = get_option( 'ws_url_monitor_log', [] );
    $failures = array_filter( $log, fn( $e ) => $e['type'] === 'failure' );
    if ( empty( $failures ) ) {
        return;
    }
    $count = count( $failures );
    $label = $count === 1 ? '1 URL failure' : $count . ' URL failures';
    echo '<div class="notice notice-error"><p>'
        . '<strong>WhistleblowerShield URL Monitor:</strong> '
        . esc_html( $label ) . ' detected. '
        . '<a href="' . esc_url( admin_url( 'index.php' ) ) . '">View on Dashboard &rarr;</a>'
        . '</p></div>';
}
