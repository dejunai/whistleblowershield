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
 * Custom schedules: - now set by loader.php in the Universal Layer
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
 * @version 3.10.0
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
// added custom schedules to the loader.php in the Universal Layer
// ════════════════════════════════════════════════════════════════════════════

// ════════════════════════════════════════════════════════════════════════════
// Cron Event Registration
//
// Schedules the event on activation-equivalent (admin_init gate). Unschedules
// on plugin deactivation.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', 'ws_url_monitor_schedule' );

/**
 * Schedules the ws_url_health_check event if not already scheduled.
 */
function ws_url_monitor_schedule() {
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
    ws_url_monitor_run_check_for_map( $ws_url_monitor_priority_map, 'priority' );
}

/**
 * Shared URL monitor loop for a supplied CPT/meta map.
 *
 * @param array  $monitor_map Post type => URL meta keys map.
 * @param string $scope       Monitor scope key: standard|priority.
 */
function ws_url_monitor_run_check_for_map( array $monitor_map, $scope = 'standard' ) {

    $scope            = ( $scope === 'priority' ) ? 'priority' : 'standard';
    $lock_option      = "ws_url_monitor_lock_{$scope}";
    $log_option       = "ws_url_monitor_log_{$scope}";
    $last_run_option  = "ws_url_monitor_last_run_{$scope}";
    $stats_option     = "ws_url_monitor_last_stats_{$scope}";
    $error_option     = "ws_url_monitor_last_error_{$scope}";

    // Recover from stale locks left behind by a fatal/terminated process.
    $now      = time();
    $lock_ttl = (int) apply_filters( 'ws_url_monitor_lock_ttl', 30 * MINUTE_IN_SECONDS );
    $existing = (int) get_option( $lock_option, 0 );

    if ( $existing > 0 && ( $now - $existing ) > $lock_ttl ) {
        delete_option( $lock_option );
    }

    if ( ! add_option( $lock_option, time(), '', false ) ) {
        $locked_at = (int) get_option( $lock_option, 0 );
        update_option( $stats_option, [
            'status'         => 'skipped_locked',
            'scope'          => $scope,
            'timestamp'      => $now,
            'lock_age'       => ( $locked_at > 0 ) ? max( 0, $now - $locked_at ) : 0,
            'checked_urls'   => 0,
            'unreachable'    => 0,
            'scanned_posts'  => 0,
            'warnings'       => 0,
            'failures'       => 0,
            'recoveries'     => 0,
        ] );
        return;
    }

    try {
        $previous_log = get_option( $log_option, [] );
        $new_log      = [];
        $new_failures = [];
        $new_warnings = [];
        $recoveries   = [];
        $checked_urls = 0;
        $unreachable  = 0;
        $scanned_posts = 0;

        // Build a lookup of previously flagged URLs for recovery detection.
        // Keyed by "post_id|meta_key" for O(1) lookup.
        $previous_flagged = [];
        foreach ( $previous_log as $entry ) {
            $previous_flagged[ $entry['post_id'] . '|' . $entry['meta_key'] ] = $entry;
        }

        foreach ( $monitor_map as $post_type => $meta_keys ) {

            // Paginate to avoid silently truncating checks on large datasets.
            $page      = 1;
            $per_page  = 500;
            $has_posts = true;

            while ( $has_posts ) {

                $posts = get_posts( [
                    'post_type'      => $post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => $per_page,
                    'paged'          => $page,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                ] );

                if ( empty( $posts ) ) {
                    break;
                }

                $scanned_posts += count( $posts );

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

                    $checked_urls++;

                    $log_key = $post_id . '|' . $meta_key;

                    $response = wp_remote_head( $url, [
                        'timeout'     => 10,
                        'redirection' => 0, // Do not follow redirects — detect them as warnings.
                        'user-agent'  => 'WhistleblowerShield URL Monitor/1.0',
                    ] );

                    // WP_Error means unreachable — skip silently, retry next run.
                    if ( is_wp_error( $response ) ) {
                        $unreachable++;
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

                $has_posts = count( $posts ) === $per_page;
                $page++;
            }
        }

        // Persist updated log and last-run timestamp.
        update_option( $log_option,      $new_log );
        update_option( $last_run_option, time() );
        update_option( $stats_option, [
            'status'         => 'ok',
            'scope'          => $scope,
            'timestamp'      => time(),
            'checked_urls'   => $checked_urls,
            'unreachable'    => $unreachable,
            'scanned_posts'  => $scanned_posts,
            'warnings'       => count( $new_warnings ),
            'failures'       => count( $new_failures ),
            'recoveries'     => count( $recoveries ),
        ] );
        delete_option( $error_option );

        // Send email digests.
        if ( ! empty( $new_failures ) || ! empty( $new_warnings ) ) {
            ws_url_monitor_send_failure_email( $new_failures, $new_warnings );
        }
        if ( ! empty( $recoveries ) ) {
            ws_url_monitor_send_recovery_email( $recoveries );
        }

        // Escalate when transport-level failures are high (unreachable URLs).
        if ( $checked_urls > 0 ) {
            $rate            = $unreachable / max( 1, $checked_urls );
            $min_count       = (int) apply_filters( 'ws_url_monitor_unreachable_alert_min_count', 10 );
            $min_rate        = (float) apply_filters( 'ws_url_monitor_unreachable_alert_rate', 0.20 );
            $cooldown        = (int) apply_filters( 'ws_url_monitor_unreachable_alert_cooldown', 12 * HOUR_IN_SECONDS );
            $alert_option    = "ws_url_monitor_last_transport_alert_{$scope}";
            $last_alert_time = (int) get_option( $alert_option, 0 );

            if ( $unreachable >= $min_count && $rate >= $min_rate && ( time() - $last_alert_time ) >= $cooldown ) {
                ws_url_monitor_send_transport_alert_email( [
                    'scope'       => $scope,
                    'checked_urls'=> $checked_urls,
                    'unreachable' => $unreachable,
                    'rate'        => $rate,
                ] );
                update_option( $alert_option, time() );
            }
        }
    } catch ( Throwable $e ) {
        update_option( $error_option, 'Run aborted: ' . $e->getMessage() );
        update_option( $stats_option, [
            'status'         => 'error',
            'scope'          => $scope,
            'timestamp'      => time(),
            'checked_urls'   => 0,
            'unreachable'    => 0,
            'scanned_posts'  => 0,
            'warnings'       => 0,
            'failures'       => 0,
            'recoveries'     => 0,
        ] );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[ws-core][url-monitor][' . $scope . '] ' . $e->getMessage() );
        }
    } finally {
        delete_option( $lock_option );
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
// Private Helper: Send Transport Degradation Email
// ════════════════════════════════════════════════════════════════════════════

/**
 * Sends an escalation email when unreachable URL checks cross configured
 * count and rate thresholds.
 *
 * @param array $stats Scope and transport stats for the run.
 */
function ws_url_monitor_send_transport_alert_email( array $stats ) {

    $emails = ws_url_monitor_get_admin_emails();
    if ( empty( $emails ) ) {
        return;
    }

    $site    = get_bloginfo( 'name' );
    $scope   = esc_html( strtoupper( (string) ( $stats['scope'] ?? 'STANDARD' ) ) );
    $checked = (int) ( $stats['checked_urls'] ?? 0 );
    $unreach = (int) ( $stats['unreachable'] ?? 0 );
    $rate    = (float) ( $stats['rate'] ?? 0 );
    $subject = "[{$site}] URL Health Monitor — Transport Degradation ({$scope})";

    $body  = "The URL monitor detected elevated unreachable responses.\n\n";
    $body .= "Scope:       {$scope}\n";
    $body .= "Checked:     {$checked}\n";
    $body .= "Unreachable: {$unreach}\n";
    $body .= "Rate:        " . number_format_i18n( $rate * 100, 1 ) . "%\n\n";
    $body .= "This usually indicates network, DNS, TLS, firewall, or upstream transport issues.\n";
    $body .= "Dashboard: " . admin_url( 'index.php' ) . "\n\n";
    $body .= "WhistleblowerShield URL Health Monitor\n";
    $body .= get_bloginfo( 'url' ) . "\n";

    foreach ( $emails as $email ) {
        wp_mail( $email, $subject, $body );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Private Helper: Run Self-Test
// ════════════════════════════════════════════════════════════════════════════

/**
 * Runs monitor prerequisite checks and returns detailed results.
 *
 * @return array
 */
function ws_url_monitor_run_self_test() {

    $tests = [];

    $standard_cron = wp_next_scheduled( 'ws_url_health_check' );
    $priority_cron = wp_next_scheduled( 'ws_url_priority_health_check' );
    $tests[] = [
        'label'   => 'Standard cron scheduled',
        'pass'    => (bool) $standard_cron,
        'details' => $standard_cron ? date_i18n( 'Y-m-d H:i', $standard_cron ) : 'Not scheduled',
    ];
    $tests[] = [
        'label'   => 'Priority cron scheduled',
        'pass'    => (bool) $priority_cron,
        'details' => $priority_cron ? date_i18n( 'Y-m-d H:i', $priority_cron ) : 'Not scheduled',
    ];

    $admin_emails = ws_url_monitor_get_admin_emails();
    $tests[] = [
        'label'   => 'Admin email recipients',
        'pass'    => ! empty( $admin_emails ),
        'details' => ! empty( $admin_emails ) ? (string) count( $admin_emails ) : 'No administrator emails found',
    ];

    $tmp_key = 'ws_url_monitor_self_test_tmp_' . wp_generate_password( 8, false );
    $write_ok = update_option( $tmp_key, 'ok', false );
    $read_ok  = ( get_option( $tmp_key, '' ) === 'ok' );
    delete_option( $tmp_key );
    $tests[] = [
        'label'   => 'Option persistence (read/write)',
        'pass'    => (bool) ( $write_ok && $read_ok ),
        'details' => ( $write_ok && $read_ok ) ? 'OK' : 'Failed option write/read',
    ];

    $transport = wp_remote_head( 'https://example.com', [
        'timeout'     => 10,
        'redirection' => 0,
        'user-agent'  => 'WhistleblowerShield URL Monitor Self-Test/1.0',
    ] );
    if ( is_wp_error( $transport ) ) {
        $tests[] = [
            'label'   => 'Outbound HTTP transport',
            'pass'    => false,
            'details' => $transport->get_error_message(),
        ];
    } else {
        $status = (int) wp_remote_retrieve_response_code( $transport );
        $tests[] = [
            'label'   => 'Outbound HTTP transport',
            'pass'    => ( $status >= 200 && $status < 500 ),
            'details' => 'HTTP ' . $status,
        ];
    }

    $all_passed = ! in_array( false, array_column( $tests, 'pass' ), true );

    return [
        'timestamp'  => time(),
        'all_passed' => $all_passed,
        'tests'      => $tests,
    ];
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

    $standard_log      = get_option( 'ws_url_monitor_log_standard', [] );
    $priority_log      = get_option( 'ws_url_monitor_log_priority', [] );
    $log               = array_merge( $standard_log, $priority_log );
    $last_run_standard = (int) get_option( 'ws_url_monitor_last_run_standard', 0 );
    $last_run_priority = (int) get_option( 'ws_url_monitor_last_run_priority', 0 );
    $next_run          = wp_next_scheduled( 'ws_url_health_check' );
    $next_priority_run = wp_next_scheduled( 'ws_url_priority_health_check' );
    $stats_standard    = get_option( 'ws_url_monitor_last_stats_standard', [] );
    $stats_priority    = get_option( 'ws_url_monitor_last_stats_priority', [] );
    $err_standard      = (string) get_option( 'ws_url_monitor_last_error_standard', '' );
    $err_priority      = (string) get_option( 'ws_url_monitor_last_error_priority', '' );

    // ── Manual trigger ────────────────────────────────────────────────────

    if ( isset( $_POST['ws_url_monitor_run_now'] )
        && check_admin_referer( 'ws_url_monitor_trigger' )
        && current_user_can( 'manage_options' )
    ) {
        ws_run_url_health_check();
        echo '<div class="notice notice-success inline"><p>Standard URL health check completed.</p></div>';
        $standard_log      = get_option( 'ws_url_monitor_log_standard', [] );
        $priority_log      = get_option( 'ws_url_monitor_log_priority', [] );
        $log               = array_merge( $standard_log, $priority_log );
        $last_run_standard = (int) get_option( 'ws_url_monitor_last_run_standard', 0 );
        $last_run_priority = (int) get_option( 'ws_url_monitor_last_run_priority', 0 );
        $stats_standard    = get_option( 'ws_url_monitor_last_stats_standard', [] );
        $stats_priority    = get_option( 'ws_url_monitor_last_stats_priority', [] );
        $err_standard      = (string) get_option( 'ws_url_monitor_last_error_standard', '' );
        $err_priority      = (string) get_option( 'ws_url_monitor_last_error_priority', '' );
    }

    if ( isset( $_POST['ws_url_monitor_run_priority_now'] )
        && check_admin_referer( 'ws_url_monitor_trigger_priority' )
        && current_user_can( 'manage_options' )
    ) {
        ws_run_url_priority_health_check();
        echo '<div class="notice notice-success inline"><p>Priority URL health check completed.</p></div>';

        // Refresh values after manual run.
        $standard_log = get_option( 'ws_url_monitor_log_standard', [] );
        $priority_log = get_option( 'ws_url_monitor_log_priority', [] );
        $log          = array_merge( $standard_log, $priority_log );
        $last_run_standard = (int) get_option( 'ws_url_monitor_last_run_standard', 0 );
        $last_run_priority = (int) get_option( 'ws_url_monitor_last_run_priority', 0 );
        $stats_standard    = get_option( 'ws_url_monitor_last_stats_standard', [] );
        $stats_priority    = get_option( 'ws_url_monitor_last_stats_priority', [] );
        $err_standard      = (string) get_option( 'ws_url_monitor_last_error_standard', '' );
        $err_priority      = (string) get_option( 'ws_url_monitor_last_error_priority', '' );
    }

    if ( isset( $_POST['ws_url_monitor_self_test_now'] )
        && check_admin_referer( 'ws_url_monitor_self_test' )
        && current_user_can( 'manage_options' )
    ) {
        $result = ws_url_monitor_run_self_test();
        set_transient( 'ws_url_monitor_self_test_' . get_current_user_id(), $result, 15 * MINUTE_IN_SECONDS );
        echo '<div class="notice notice-info inline"><p>URL monitor self-test completed.</p></div>';
    }

    $self_test = get_transient( 'ws_url_monitor_self_test_' . get_current_user_id() );

    // ── Meta bar ──────────────────────────────────────────────────────────

    $last_run_standard_str = $last_run_standard
        ? esc_html( date_i18n( 'Y-m-d H:i', $last_run_standard ) )
        : 'Never';
    $last_run_priority_str = $last_run_priority
        ? esc_html( date_i18n( 'Y-m-d H:i', $last_run_priority ) )
        : 'Never';
    $next_run_str = $next_run
        ? esc_html( date_i18n( 'Y-m-d H:i', $next_run ) )
        : 'Not scheduled';
    $next_priority_run_str = $next_priority_run
        ? esc_html( date_i18n( 'Y-m-d H:i', $next_priority_run ) )
        : 'Not scheduled';

    echo '<p style="color:#999;font-size:11px;margin-top:0;">'
        . 'Last standard run: ' . $last_run_standard_str
        . ' &nbsp;|&nbsp; Last priority run: ' . $last_run_priority_str
        . ' &nbsp;|&nbsp; Next standard run: ' . $next_run_str
        . ' &nbsp;|&nbsp; Next priority run: ' . $next_priority_run_str
        . '</p>';

    $std_checked = (int) ( $stats_standard['checked_urls'] ?? 0 );
    $std_unreach = (int) ( $stats_standard['unreachable'] ?? 0 );
    $pri_checked = (int) ( $stats_priority['checked_urls'] ?? 0 );
    $pri_unreach = (int) ( $stats_priority['unreachable'] ?? 0 );
    $std_status  = (string) ( $stats_standard['status'] ?? 'unknown' );
    $pri_status  = (string) ( $stats_priority['status'] ?? 'unknown' );

    echo '<p style="color:#666;font-size:11px;margin-top:-6px;">'
        . 'Standard checked/unreachable: ' . esc_html( $std_checked ) . '/' . esc_html( $std_unreach )
        . ' &nbsp;|&nbsp; Priority checked/unreachable: ' . esc_html( $pri_checked ) . '/' . esc_html( $pri_unreach )
        . ' &nbsp;|&nbsp; Status: ' . esc_html( $std_status ) . ' / ' . esc_html( $pri_status )
        . '</p>';

    if ( $err_standard || $err_priority ) {
        echo '<p style="color:#b32d2e;font-size:11px;margin-top:-4px;">';
        if ( $err_standard ) {
            echo '<strong>Standard run error:</strong> ' . esc_html( $err_standard ) . ' ';
        }
        if ( $err_priority ) {
            echo '<strong>Priority run error:</strong> ' . esc_html( $err_priority );
        }
        echo '</p>';
    }

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
    echo '</form>';

    echo '<form method="post" style="margin-top:8px;">';
    wp_nonce_field( 'ws_url_monitor_self_test' );
    echo '<input type="hidden" name="ws_url_monitor_self_test_now" value="1">';
    submit_button( 'Run Monitor Self-Test', 'secondary small', '', false );
    echo '</form><br>';

    if ( is_array( $self_test ) && ! empty( $self_test['tests'] ) ) {
        $badge = ! empty( $self_test['all_passed'] ) ? '#46b450' : '#d63638';
        echo '<p><strong style="color:' . esc_attr( $badge ) . ';">Self-Test '
            . ( ! empty( $self_test['all_passed'] ) ? 'PASS' : 'FAIL' )
            . '</strong> '
            . '<span style="font-size:11px;color:#666;">'
            . esc_html( date_i18n( 'Y-m-d H:i', (int) $self_test['timestamp'] ) )
            . '</span></p>';
        echo '<ul style="margin:0 0 12px;padding-left:1.2em;">';
        foreach ( $self_test['tests'] as $test ) {
            $ok = ! empty( $test['pass'] );
            echo '<li>'
                . ( $ok ? '<span style="color:#46b450;">&#10003;</span> ' : '<span style="color:#d63638;">&#10007;</span> ' )
                . esc_html( $test['label'] )
                . ' — <span style="color:#666;">' . esc_html( (string) $test['details'] ) . '</span></li>';
        }
        echo '</ul>';
    }

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
    $standard_log = get_option( 'ws_url_monitor_log_standard', [] );
    $priority_log = get_option( 'ws_url_monitor_log_priority', [] );
    $log          = array_merge( $standard_log, $priority_log );
    $failures = array_filter( $log, fn( $e ) => $e['type'] === 'failure' );

    if ( ! empty( $failures ) ) {
        $count = count( $failures );
        $label = $count === 1 ? '1 URL failure' : $count . ' URL failures';
        echo '<div class="notice notice-error"><p>'
            . '<strong>WhistleblowerShield URL Monitor:</strong> '
            . esc_html( $label ) . ' detected. '
            . '<a href="' . esc_url( admin_url( 'index.php' ) ) . '">View on Dashboard &rarr;</a>'
            . '</p></div>';
        return;
    }

    $stats_standard = get_option( 'ws_url_monitor_last_stats_standard', [] );
    $stats_priority = get_option( 'ws_url_monitor_last_stats_priority', [] );
    $status_standard = (string) ( $stats_standard['status'] ?? '' );
    $status_priority = (string) ( $stats_priority['status'] ?? '' );
    $err_standard = (string) get_option( 'ws_url_monitor_last_error_standard', '' );
    $err_priority = (string) get_option( 'ws_url_monitor_last_error_priority', '' );

    if ( in_array( $status_standard, [ 'error', 'skipped_locked' ], true ) || in_array( $status_priority, [ 'error', 'skipped_locked' ], true ) ) {
        echo '<div class="notice notice-warning"><p>'
            . '<strong>WhistleblowerShield URL Monitor:</strong> '
            . 'Last run status is degraded (' . esc_html( $status_standard ?: 'unknown' ) . ' / ' . esc_html( $status_priority ?: 'unknown' ) . '). '
            . ( $err_standard ? 'Standard: ' . esc_html( $err_standard ) . '. ' : '' )
            . ( $err_priority ? 'Priority: ' . esc_html( $err_priority ) . '. ' : '' )
            . '<a href="' . esc_url( admin_url( 'index.php' ) ) . '">View on Dashboard &rarr;</a>'
            . '</p></div>';
        return;
    }

    $unreachable    = (int) ( $stats_standard['unreachable'] ?? 0 ) + (int) ( $stats_priority['unreachable'] ?? 0 );
    $checked        = (int) ( $stats_standard['checked_urls'] ?? 0 ) + (int) ( $stats_priority['checked_urls'] ?? 0 );

    if ( $checked > 0 && $unreachable > 0 ) {
        echo '<div class="notice notice-warning"><p>'
            . '<strong>WhistleblowerShield URL Monitor:</strong> '
            . esc_html( $unreachable ) . ' of ' . esc_html( $checked ) . ' URL checks were unreachable in the last run. '
            . 'This may indicate transient network or DNS issues. '
            . '<a href="' . esc_url( admin_url( 'index.php' ) ) . '">View on Dashboard &rarr;</a>'
            . '</p></div>';
    }
}
