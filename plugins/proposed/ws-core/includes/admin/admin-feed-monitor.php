<?php
/**
 * admin-feed-monitor.php
 *
 * Inoreader Feed Monitor — Legislative Ingest Pipeline for jx-statute.
 *
 * PURPOSE
 * -------
 * Polls the Inoreader API daily for new items in the LegalResearch folder.
 * Filters items for enacted/signed legislation using keyword matching.
 * Stages matching items as a human-reviewable JSON file. An admin reviews
 * each item (accept / reject / edit) before ingest creates jx-statute posts.
 *
 * PIPELINE
 * --------
 *   1. WP-Cron daily: ws_feed_monitor_poll()
 *        → Fetch LegalResearch folder from Inoreader API
 *        → Keyword-filter for enacted/signed bills
 *        → Deduplicate against already-staged and already-ingested items
 *        → Append new items to staged JSON file
 *
 *   2. Admin review UI (Tools → Feed Monitor):
 *        → List staged items with accept / reject / edit controls
 *        → Accepted items trigger ws_feed_ingest_item()
 *        → Rejected items are removed from staged JSON
 *        → Edited items update staged JSON before ingest
 *
 *   3. ws_feed_ingest_item():
 *        → Creates jx-statute post (draft)
 *        → Writes ws_ingest_source, ws_ingest_date, ws_ingest_file meta
 *        → Sets plain_reviewed = 0 (pending legal review)
 *        → Removes item from staged JSON
 *
 * KEYWORD FILTER
 * --------------
 * Items must contain at least one enacted keyword in title or description:
 *   signed into law, enacted, became law, signed by the president,
 *   chaptered, effective law, public law
 *
 * Items containing exclusion keywords are skipped:
 *   introduced, referred to committee, passed house, passed senate,
 *   failed, vetoed, tabled, withdrawn
 *
 * INOREADER API
 * -------------
 * Stream:   user/-/label/LegalResearch
 * Endpoint: https://www.inoreader.com/reader/api/0/stream/contents/
 * Auth:     Bearer token (required). App ID + App Key (optional, tier-dependent).
 * Docs:     https://www.inoreader.com/developers/
 *
 * STAGED JSON FILE
 * ----------------
 * Location: {uploads_dir}/ws-feed-data/feed-staged.json
 * Shape per item:
 * {
 *     "guid":        string,   // Unique item identifier from feed
 *     "title":       string,   // Bill title from feed
 *     "url":         string,   // Link to LegiScan or Congress.gov bill page
 *     "description": string,   // Feed item description (plain text)
 *     "published":   string,   // Y-m-d H:i:s (local)
 *     "source":      string,   // Feed source label
 *     "status":      string,   // "pending" | "accepted" | "rejected"
 *     "staged_at":   string,   // Y-m-d H:i:s when staged
 *     // Editable fields — blank by default, filled by reviewer:
 *     "jx_code":     string,   // USPS code e.g. "US"
 *     "notes":       string    // Reviewer notes
 * }
 *
 * INGEST META KEYS
 * ----------------
 * ws_ingest_source  — 'feed-monitor'
 * ws_ingest_date    — Y-m-d of ingest
 * ws_ingest_guid    — original feed item GUID (deduplication key)
 * ws_ingest_url     — original feed item URL
 *
 * SETTINGS (wp_options)
 * ---------------------
 * ws_feed_monitor_token    — Inoreader OAuth bearer token
 * ws_feed_monitor_app_id   — Inoreader App ID (optional)
 * ws_feed_monitor_app_key  — Inoreader App Key (optional)
 * ws_feed_monitor_last_run — Unix timestamp of last successful poll
 * ws_feed_monitor_ingested — array of {guid, ts} pairs already ingested (30-day rolling dedup log)
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
 * 3.2.1  WP_Error case in ws_feed_monitor_poll() now stores error message to
 *        ws_feed_monitor_last_error instead of silently returning -1.
 *        Added admin_notices banner surfacing feed poll failures to all
 *        admin screens, linking to Tools → Feed Monitor.
 * 3.8.1  echo $notice wrapped with wp_kses_post(). LOCK_EX flag added to
 *        file_put_contents() in ws_feed_monitor_write_staged().
 *        Staged JSON prune step added: pending items older than
 *        ws_feed_staged_max_age_days (default 90 days) are dropped on each
 *        poll run to prevent unbounded file growth.
 *        Default jx_code wrapped in apply_filters('ws_feed_monitor_default_jx_code')
 *        for non-US deployments.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Constants
// ════════════════════════════════════════════════════════════════════════════

define( 'WS_FEED_STREAM',   'user/-/label/LegalResearch' );
define( 'WS_FEED_API_BASE', 'https://www.inoreader.com/reader/api/0/stream/contents/' );
define( 'WS_FEED_MAX_ITEMS', 50 ); // Max items to fetch per poll

/**
 * Returns the feed data directory path (under wp-content/uploads/ws-feed-data/).
 * Computed at runtime so it respects multisite and filtered upload paths.
 * Never inside the plugin directory — safe from data loss on plugin update.
 */
function ws_feed_data_dir() {
    static $dir = null;
    if ( null === $dir ) {
        $dir = wp_upload_dir()['basedir'] . '/ws-feed-data/';
    }
    return $dir;
}

/**
 * Returns the absolute path to the staged JSON file.
 */
function ws_feed_staged_file() {
    return ws_feed_data_dir() . 'feed-staged.json';
}


// ════════════════════════════════════════════════════════════════════════════
// Keyword Filter Lists
//
// Items matching any ENACTED keyword (in title or description) are staged.
// Items matching any EXCLUSION keyword are skipped regardless of enacted match.
// All matching is case-insensitive.
// ════════════════════════════════════════════════════════════════════════════

define( 'WS_FEED_ENACTED_KEYWORDS', [
    'signed into law',
    'enacted',
    'became law',
    'signed by the president',
    'chaptered',
    'public law',
    'effective law',
] );

define( 'WS_FEED_EXCLUSION_KEYWORDS', [
    'introduced',
    'referred to committee',
    'passed house',
    'passed senate',
    'failed',
    'vetoed',
    'tabled',
    'withdrawn',
    'died in committee',
] );


// ════════════════════════════════════════════════════════════════════════════
// Ensure Data Directory Exists
//
// Creates the feed data directory under wp-content/uploads on first load.
// Writes an .htaccess to block direct web access (Apache only; Nginx servers
// must block the path at the server-config level).
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', 'ws_feed_monitor_ensure_data_dir' );

/**
 * Creates the feed data directory and writes an Apache .htaccess guard.
 */
function ws_feed_monitor_ensure_data_dir() {
    $dir = ws_feed_data_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }

    // .htaccess blocks direct HTTP access on Apache. Nginx requires separate
    // server-level configuration to protect this path.
    $htaccess = $dir . '.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        file_put_contents( $htaccess, "Deny from all\n" );
    }
}


// ════════════════════════════════════════════════════════════════════════════
// Cron Schedule + Event
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_init', 'ws_feed_monitor_maybe_schedule' );

/**
 * Schedules the daily feed poll if not already scheduled.
 * Skips scheduling if no API token is configured.
 */
function ws_feed_monitor_maybe_schedule() {
    $token = get_option( 'ws_feed_monitor_token', '' );
    if ( empty( $token ) ) {
        // If credentials were removed after a schedule was created, unschedule
        // any lingering poll event so cron does not keep firing failed requests.
        wp_clear_scheduled_hook( 'ws_feed_monitor_poll_event' );
        return; // Do not schedule until credentials are configured.
    }
    if ( ! wp_next_scheduled( 'ws_feed_monitor_poll_event' ) ) {
        wp_schedule_event( time(), 'daily', 'ws_feed_monitor_poll_event' );
    }
}

/**
 * Unschedules the feed poll cron event on plugin deactivation.
 * Call via register_deactivation_hook() in ws-core.php.
 */
function ws_feed_monitor_deactivate() {
    wp_clear_scheduled_hook( 'ws_feed_monitor_poll_event' );
}

add_action( 'ws_feed_monitor_poll_event', 'ws_feed_monitor_poll' );


// ════════════════════════════════════════════════════════════════════════════
// Poll: Fetch + Filter + Stage
// ════════════════════════════════════════════════════════════════════════════

/**
 * Fetches the LegalResearch folder from Inoreader, filters for enacted
 * legislation, deduplicates, and appends new items to the staged JSON file.
 *
 * @return int Number of new items staged, or -1 on fetch failure.
 */
function ws_feed_monitor_poll() {

    $token   = get_option( 'ws_feed_monitor_token', '' );
    $app_id  = get_option( 'ws_feed_monitor_app_id', '' );
    $app_key = get_option( 'ws_feed_monitor_app_key', '' );

    if ( empty( $token ) ) {
        return -1;
    }

    // ── Build request ─────────────────────────────────────────────────────

    $url = WS_FEED_API_BASE . urlencode( WS_FEED_STREAM ) . '?n=' . WS_FEED_MAX_ITEMS . '&output=json';

    $headers = [
        'Authorization' => 'Bearer ' . $token,
    ];

    if ( ! empty( $app_id ) && ! empty( $app_key ) ) {
        $headers['AppId']  = $app_id;
        $headers['AppKey'] = $app_key;
    }

    $response = wp_remote_get( $url, [
        'timeout' => 15,
        'headers' => $headers,
    ] );

    if ( is_wp_error( $response ) ) {
        update_option( 'ws_feed_monitor_last_error', 'Network error — ' . $response->get_error_message() );
        return -1;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( (int) $code !== 200 ) {
        // Store the error code for display in the admin UI.
        update_option( 'ws_feed_monitor_last_error', "HTTP {$code}" );
        return -1;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! isset( $data['items'] ) || ! is_array( $data['items'] ) ) {
        update_option( 'ws_feed_monitor_last_error', 'Invalid API response — no items key.' );
        return -1;
    }

    delete_option( 'ws_feed_monitor_last_error' );

    // ── Load existing staged items + ingested GUID log ────────────────────

    $staged   = ws_feed_monitor_read_staged();
    $ingested = get_option( 'ws_feed_monitor_ingested', [] );

    // Extract GUIDs — supports both legacy bare strings and current {guid, ts} entries.
    $ingested_guids = array_map(
        fn( $e ) => is_array( $e ) ? $e['guid'] : $e,
        $ingested
    );
    $staged_guids = array_column( $staged, 'guid' );
    $new_count    = 0;

    // ── Process items ─────────────────────────────────────────────────────

    foreach ( $data['items'] as $item ) {

        $guid  = sanitize_text_field( $item['id']                          ?? '' );
        $title = sanitize_text_field( $item['title']                       ?? '' );
        $url   = esc_url_raw( $item['canonical'][0]['href']                ?? $item['alternate'][0]['href'] ?? '' );
        $desc  = wp_strip_all_tags( $item['summary']['content']            ?? $item['content']['content'] ?? '' );
        $pub   = isset( $item['published'] )
                    ? date_i18n( 'Y-m-d H:i:s', (int) $item['published'] )
                    : current_time( 'Y-m-d H:i:s' );
        $source = sanitize_text_field( $item['origin']['title']            ?? 'Inoreader' );

        if ( empty( $guid ) || empty( $title ) ) {
            continue;
        }

        // Skip already ingested or already staged.
        if ( in_array( $guid, $ingested_guids, true ) ) {
            continue;
        }
        if ( in_array( $guid, $staged_guids, true ) ) {
            continue;
        }

        // ── Keyword filter ────────────────────────────────────────────────

        $haystack = strtolower( $title . ' ' . $desc );

        $has_enacted = false;
        foreach ( WS_FEED_ENACTED_KEYWORDS as $keyword ) {
            if ( str_contains( $haystack, $keyword ) ) {
                $has_enacted = true;
                break;
            }
        }

        if ( ! $has_enacted ) {
            continue;
        }

        $has_exclusion = false;
        foreach ( WS_FEED_EXCLUSION_KEYWORDS as $keyword ) {
            if ( str_contains( $haystack, $keyword ) ) {
                $has_exclusion = true;
                break;
            }
        }

        if ( $has_exclusion ) {
            continue;
        }

        // ── Stage item ────────────────────────────────────────────────────

        $staged[] = [
            'guid'        => $guid,
            'title'       => $title,
            'url'         => $url,
            'description' => wp_trim_words( $desc, 60, '...' ),
            'published'   => $pub,
            'source'      => $source,
            'status'      => 'pending',
            'staged_at'   => current_time( 'Y-m-d H:i:s' ),
            // Default jurisdiction for staged items. Override via the
            // ws_feed_monitor_default_jx_code filter (e.g. for state-focused deployments).
            'jx_code'     => apply_filters( 'ws_feed_monitor_default_jx_code', 'US' ),
            'notes'       => '',
        ];

        $staged_guids[] = $guid;
        $new_count++;
    }

    // ── Prune stale pending items ─────────────────────────────────────────
    //
    // Accepted and rejected items are removed from staging immediately on
    // each admin action, so only 'pending' items accumulate over time.
    // Items that have sat in staging past ws_feed_staged_max_age_days (default
    // 90 days) are pruned automatically each poll run to prevent unbounded
    // file growth. A reviewer discarding the queue is more useful than
    // silently accumulating months of unreviewed bills.

    $max_age_days  = (int) apply_filters( 'ws_feed_staged_max_age_days', 90 );
    $prune_cutoff  = time() - ( $max_age_days * DAY_IN_SECONDS );

    $staged = array_values( array_filter(
        $staged,
        function ( $item ) use ( $prune_cutoff ) {
            if ( ( $item['status'] ?? 'pending' ) !== 'pending' ) {
                return true; // Non-pending items should not exist here, but keep them if present.
            }
            $ts = strtotime( $item['staged_at'] ?? '' );
            return $ts !== false && $ts >= $prune_cutoff;
        }
    ) );

    // ── Persist ───────────────────────────────────────────────────────────

    ws_feed_monitor_write_staged( $staged );
    update_option( 'ws_feed_monitor_last_run', time() );

    return $new_count;
}


// ════════════════════════════════════════════════════════════════════════════
// Ingest: Staged Item → jx-statute Post
// ════════════════════════════════════════════════════════════════════════════

/**
 * Creates a jx-statute draft post from a staged item and removes it from
 * the staged JSON file.
 *
 * @param  string $guid  GUID of the staged item to ingest.
 * @return int|false     New post ID on success, false on failure.
 */
function ws_feed_ingest_item( $guid ) {

    $staged = ws_feed_monitor_read_staged();
    $entry  = null;
    $index  = null;

    foreach ( $staged as $i => $item ) {
        if ( $item['guid'] === $guid ) {
            $entry = $item;
            $index = $i;
            break;
        }
    }

    if ( ! $entry ) {
        return false;
    }

    // ── Resolve ws_jurisdiction term ──────────────────────────────────────

    $term = ws_jx_term_by_code( sanitize_text_field( $entry['jx_code'] ?? 'us' ) );

    // ── Create post ───────────────────────────────────────────────────────

    $post_id = wp_insert_post( [
        'post_title'   => $entry['title'],
        'post_status'  => 'draft',
        'post_type'    => 'jx-statute',
        'post_content' => '',
    ] );

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        return false;
    }

    // ── Assign taxonomy term ──────────────────────────────────────────────

    if ( $term && ! is_wp_error( $term ) ) {
        wp_set_object_terms( $post_id, $term->term_id, WS_JURISDICTION_TAXONOMY );
        update_post_meta( $post_id, 'ws_jx_term_id', $term->term_id );
    }

    // ── Write ingest meta ─────────────────────────────────────────────────

    // Direct update_post_meta() calls are intentional here. ws_ingest_* are
    // administrative flags consumed exclusively by admin tooling and the
    // deduplication log. They are not statute content and do not belong in
    // the query layer.
    update_post_meta( $post_id, 'ws_ingest_source', 'feed-monitor' );
    update_post_meta( $post_id, 'ws_ingest_date',   current_time( 'Y-m-d' ) );
    update_post_meta( $post_id, 'ws_ingest_guid',   $entry['guid'] );
    update_post_meta( $post_id, 'ws_ingest_url',    $entry['url'] );

    // ── Source & verification stamps ──────────────────────────────────────
    //
    // Feed-ingested posts are created programmatically, not by a human editor.
    // ws_auto_source_method is set here (not by ws_stamp_source_method() in
    // admin-hooks.php) because wp_insert_post() above bypasses acf/save_post.
    // ws_needs_review is set to 1 so the post surfaces in the review queue
    // pending editorial acceptance.

    update_post_meta( $post_id, 'ws_auto_source_method', WS_SOURCE_FEED_IMPORT );
    update_post_meta( $post_id, 'ws_needs_review',       1 );

    // ── Plain English defaults ────────────────────────────────────────────

    update_post_meta( $post_id, 'ws_has_plain_english', 0 );
    update_post_meta( $post_id, 'ws_plain_english_reviewed', 0 );

    // ── Reviewer notes → post excerpt ─────────────────────────────────────

    if ( ! empty( $entry['notes'] ) ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_excerpt' => sanitize_textarea_field( $entry['notes'] ),
        ] );
    }

    // ── Remove from staged, add to ingested log ───────────────────────────

    array_splice( $staged, $index, 1 );
    ws_feed_monitor_write_staged( $staged );

    $ingested   = get_option( 'ws_feed_monitor_ingested', [] );
    $ingested[] = [ 'guid' => $entry['guid'], 'ts' => time() ];

    // Trim log to entries within the last 30 days to keep the option bounded.
    // Legacy bare-string entries (no ts) are dropped on first trim pass.
    $cutoff   = time() - ( 30 * DAY_IN_SECONDS );
    $ingested = array_values( array_filter(
        $ingested,
        fn( $e ) => is_array( $e ) && $e['ts'] >= $cutoff
    ) );

    update_option( 'ws_feed_monitor_ingested', $ingested );

    return $post_id;
}


// ════════════════════════════════════════════════════════════════════════════
// JSON File Helpers
// ════════════════════════════════════════════════════════════════════════════

/**
 * Reads and decodes the staged JSON file.
 *
 * @return array  Staged items array, empty array if file missing or invalid.
 */
function ws_feed_monitor_read_staged() {
    $file = ws_feed_staged_file();
    if ( ! file_exists( $file ) ) {
        return [];
    }
    $raw  = file_get_contents( $file );
    $data = json_decode( $raw, true );
    return is_array( $data ) ? $data : [];
}

/**
 * Encodes and writes the staged items array to the JSON file.
 *
 * @param  array $staged  Staged items to persist.
 * @return bool           True on success.
 */
function ws_feed_monitor_write_staged( array $staged ) {
    $json = wp_json_encode( $staged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    return (bool) file_put_contents( ws_feed_staged_file(), $json, LOCK_EX );
}


// ════════════════════════════════════════════════════════════════════════════
// Admin Menu: Tools → Feed Monitor
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_menu', 'ws_feed_monitor_register_menu' );

/**
 * Registers the Feed Monitor page under Tools.
 */
function ws_feed_monitor_register_menu() {
    add_management_page(
        'WhistleblowerShield — Feed Monitor',
        'WBS Feed Monitor',
        'manage_options',
        'ws-feed-monitor',
        'ws_feed_monitor_render_page'
    );
}


// ════════════════════════════════════════════════════════════════════════════
// Admin Page: Render
// ════════════════════════════════════════════════════════════════════════════

/**
 * Renders the Feed Monitor admin page — settings, manual poll trigger,
 * and per-item review UI.
 */
function ws_feed_monitor_render_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Insufficient permissions.' ) );
    }

    // ── Handle POST actions ───────────────────────────────────────────────

    $notice = '';

    if ( isset( $_POST['ws_feed_action'] ) && check_admin_referer( 'ws_feed_monitor_action' ) ) {

        $action = sanitize_key( wp_unslash( $_POST['ws_feed_action'] ) );

        // Save settings
        if ( $action === 'save_settings' ) {
            update_option( 'ws_feed_monitor_token',   sanitize_text_field( wp_unslash( $_POST['ws_feed_token']   ?? '' ) ) );
            update_option( 'ws_feed_monitor_app_id',  sanitize_text_field( wp_unslash( $_POST['ws_feed_app_id']  ?? '' ) ) );
            update_option( 'ws_feed_monitor_app_key', sanitize_text_field( wp_unslash( $_POST['ws_feed_app_key'] ?? '' ) ) );
            ws_feed_monitor_maybe_schedule();
            $notice = '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        // Manual poll
        if ( $action === 'poll_now' ) {
            $count  = ws_feed_monitor_poll();
            $notice = $count >= 0
                ? '<div class="notice notice-success"><p>Poll complete. ' . $count . ' new item(s) staged.</p></div>'
                : '<div class="notice notice-error"><p>Poll failed. Check your API credentials and try again.</p></div>';
        }

        // Accept item
        if ( $action === 'accept' && ! empty( $_POST['ws_feed_guid'] ) ) {
            $guid = sanitize_text_field( wp_unslash( $_POST['ws_feed_guid'] ) );

            // Save any edits first.
            ws_feed_monitor_save_item_edits( $guid );

            $post_id = ws_feed_ingest_item( $guid );
            $notice  = $post_id
                ? '<div class="notice notice-success"><p>Item ingested. <a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">Edit post &rarr;</a></p></div>'
                : '<div class="notice notice-error"><p>Ingest failed. Item remains in staging.</p></div>';
        }

        // Reject item
        if ( $action === 'reject' && ! empty( $_POST['ws_feed_guid'] ) ) {
            $guid   = sanitize_text_field( wp_unslash( $_POST['ws_feed_guid'] ) );
            $staged = ws_feed_monitor_read_staged();
            $staged = array_filter( $staged, fn( $i ) => $i['guid'] !== $guid );
            ws_feed_monitor_write_staged( array_values( $staged ) );
            $notice = '<div class="notice notice-success"><p>Item rejected and removed from staging.</p></div>';
        }

        // Save edits only
        if ( $action === 'save_edits' && ! empty( $_POST['ws_feed_guid'] ) ) {
            $guid   = sanitize_text_field( wp_unslash( $_POST['ws_feed_guid'] ) );
            ws_feed_monitor_save_item_edits( $guid );
            $notice = '<div class="notice notice-success"><p>Item edits saved.</p></div>';
        }
    }

    // ── Load current state ────────────────────────────────────────────────

    $token    = get_option( 'ws_feed_monitor_token',   '' );
    $app_id   = get_option( 'ws_feed_monitor_app_id',  '' );
    $app_key  = get_option( 'ws_feed_monitor_app_key', '' );
    $last_run = get_option( 'ws_feed_monitor_last_run', 0 );
    $last_err = get_option( 'ws_feed_monitor_last_error', '' );
    $next_run = wp_next_scheduled( 'ws_feed_monitor_poll_event' );
    $staged   = ws_feed_monitor_read_staged();
    $pending  = array_filter( $staged, fn( $i ) => $i['status'] === 'pending' );

    ?>
    <div class="wrap">
        <h1>WhistleblowerShield — Feed Monitor</h1>
        <?php echo wp_kses_post( $notice ); ?>

        <?php if ( $last_err ) : ?>
            <div class="notice notice-warning"><p><strong>Last poll error:</strong> <?php echo esc_html( $last_err ); ?></p></div>
        <?php endif; ?>

        <!-- ── Settings ──────────────────────────────────────────────── -->

        <h2>Inoreader API Credentials</h2>
        <form method="post">
            <?php wp_nonce_field( 'ws_feed_monitor_action' ); ?>
            <input type="hidden" name="ws_feed_action" value="save_settings">
            <table class="form-table">
                <tr>
                    <th><label for="ws_feed_token">Bearer Token</label></th>
                    <td>
                        <input type="password" id="ws_feed_token" name="ws_feed_token"
                               value="<?php echo esc_attr( $token ); ?>"
                               class="regular-text" autocomplete="off">
                        <p class="description">Required. Your Inoreader OAuth access token.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ws_feed_app_id">App ID</label></th>
                    <td>
                        <input type="text" id="ws_feed_app_id" name="ws_feed_app_id"
                               value="<?php echo esc_attr( $app_id ); ?>"
                               class="regular-text" autocomplete="off">
                        <p class="description">Optional. Required only if your Inoreader API tier demands it.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="ws_feed_app_key">App Key</label></th>
                    <td>
                        <input type="text" id="ws_feed_app_key" name="ws_feed_app_key"
                               value="<?php echo esc_attr( $app_key ); ?>"
                               class="regular-text" autocomplete="off">
                        <p class="description">Optional. Required only if your Inoreader API tier demands it.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Credentials' ); ?>
        </form>

        <!-- ── Poll status + manual trigger ──────────────────────────── -->

        <h2>Poll Status</h2>
        <p>
            <strong>Last run:</strong> <?php echo $last_run ? esc_html( date_i18n( 'Y-m-d H:i', $last_run ) ) : 'Never'; ?><br>
            <strong>Next scheduled run:</strong> <?php echo $next_run ? esc_html( date_i18n( 'Y-m-d H:i', $next_run ) ) : 'Not scheduled — save credentials first'; ?><br>
            <strong>Pending review:</strong> <?php echo count( $pending ); ?> item(s)<br>
            <strong>Stream:</strong> <code><?php echo esc_html( WS_FEED_STREAM ); ?></code>
        </p>
        <form method="post" style="display:inline;">
            <?php wp_nonce_field( 'ws_feed_monitor_action' ); ?>
            <input type="hidden" name="ws_feed_action" value="poll_now">
            <?php submit_button( 'Poll Now', 'secondary', '', false ); ?>
        </form>

        <!-- ── Staged items review UI ─────────────────────────────────── -->

        <h2 style="margin-top:2em;">
            Staged Items Pending Review
            <span style="font-size:13px;font-weight:normal;color:#666;">
                — <?php echo count( $pending ); ?> item(s)
            </span>
        </h2>

        <?php if ( empty( $pending ) ) : ?>
            <p style="color:#46b450;">&#10003; No items pending review.</p>
        <?php else : ?>
            <?php foreach ( $pending as $item ) : ?>
                <div style="background:#fff;border:1px solid #c3c4c7;border-radius:3px;padding:16px;margin-bottom:16px;">

                    <strong style="font-size:14px;">
                        <a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer nofollow">
                            <?php echo esc_html( $item['title'] ); ?>
                        </a>
                    </strong>

                    <p style="color:#666;font-size:12px;margin:4px 0 8px;">
                        <?php echo esc_html( $item['source'] ); ?>
                        &nbsp;&mdash;&nbsp;
                        Published: <?php echo esc_html( $item['published'] ); ?>
                        &nbsp;&mdash;&nbsp;
                        Staged: <?php echo esc_html( $item['staged_at'] ); ?>
                    </p>

                    <p style="font-size:13px;color:#444;margin-bottom:12px;">
                        <?php echo esc_html( $item['description'] ); ?>
                    </p>

                    <form method="post" style="display:inline-block;margin-right:8px;">
                        <?php wp_nonce_field( 'ws_feed_monitor_action' ); ?>
                        <input type="hidden" name="ws_feed_action" value="save_edits">
                        <input type="hidden" name="ws_feed_guid" value="<?php echo esc_attr( $item['guid'] ); ?>">
                        <label style="font-size:12px;font-weight:600;">
                            Jurisdiction Code:&nbsp;
                            <input type="text" name="ws_feed_jx_code"
                                   value="<?php echo esc_attr( strtoupper( $item['jx_code'] ?? 'US' ) ); ?>"
                                   maxlength="2" size="3"
                                   style="text-transform:uppercase;font-family:monospace;">
                        </label>
                        &nbsp;&nbsp;
                        <label style="font-size:12px;font-weight:600;">
                            Reviewer Notes:&nbsp;
                            <input type="text" name="ws_feed_notes"
                                   value="<?php echo esc_attr( $item['notes'] ?? '' ); ?>"
                                   size="40">
                        </label>
                        &nbsp;
                        <?php submit_button( 'Save Edits', 'small secondary', '', false ); ?>
                    </form>

                    <form method="post" style="display:inline-block;margin-right:4px;">
                        <?php wp_nonce_field( 'ws_feed_monitor_action' ); ?>
                        <input type="hidden" name="ws_feed_action" value="accept">
                        <input type="hidden" name="ws_feed_guid" value="<?php echo esc_attr( $item['guid'] ); ?>">
                        <input type="hidden" name="ws_feed_jx_code" value="<?php echo esc_attr( $item['jx_code'] ?? 'US' ); ?>">
                        <input type="hidden" name="ws_feed_notes"   value="<?php echo esc_attr( $item['notes'] ?? '' ); ?>">
                        <?php submit_button( '✓ Accept & Ingest', 'small primary', '', false ); ?>
                    </form>

                    <form method="post" style="display:inline-block;">
                        <?php wp_nonce_field( 'ws_feed_monitor_action' ); ?>
                        <input type="hidden" name="ws_feed_action" value="reject">
                        <input type="hidden" name="ws_feed_guid" value="<?php echo esc_attr( $item['guid'] ); ?>">
                        <?php submit_button( '✗ Reject', 'small delete', '', false,
                            [ 'onclick' => 'return confirm("Reject and permanently remove this item from staging?");' ]
                        ); ?>
                    </form>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    <?php
}


// ════════════════════════════════════════════════════════════════════════════
// Helper: Save Item Edits to Staged JSON
// ════════════════════════════════════════════════════════════════════════════

/**
 * Reads editable fields from $_POST and updates the matching staged item.
 *
 * @param  string $guid  GUID of the item to update.
 */
function ws_feed_monitor_save_item_edits( $guid ) {

    $staged = ws_feed_monitor_read_staged();

    foreach ( $staged as &$item ) {
        if ( $item['guid'] !== $guid ) {
            continue;
        }
        if ( isset( $_POST['ws_feed_jx_code'] ) ) {
            $normalized = strtoupper( preg_replace( '/[^A-Za-z]/', '', sanitize_text_field( wp_unslash( $_POST['ws_feed_jx_code'] ) ) ) );
            $normalized = substr( $normalized, 0, 2 );
            if ( $normalized !== '' ) {
                $item['jx_code'] = $normalized;
            }
        }
        if ( isset( $_POST['ws_feed_notes'] ) ) {
            $item['notes'] = sanitize_textarea_field( wp_unslash( $_POST['ws_feed_notes'] ) );
        }
        break;
    }
    unset( $item );

    ws_feed_monitor_write_staged( $staged );
}


// ════════════════════════════════════════════════════════════════════════════
// Admin Notice
//
// Shows a persistent error banner across all admin screens when the feed
// monitor poll has a stored error. Visible to administrators only.
// Links to Tools → Feed Monitor for details and manual retry.
// ════════════════════════════════════════════════════════════════════════════

add_action( 'admin_notices', 'ws_feed_monitor_admin_notice' );

/**
 * Displays an error admin notice when the last feed poll failed.
 *
 * Cleared automatically when the next poll succeeds (delete_option call
 * in ws_feed_monitor_poll() on HTTP 200 with valid response).
 */
function ws_feed_monitor_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $error = get_option( 'ws_feed_monitor_last_error', '' );
    if ( empty( $error ) ) {
        return;
    }
    $url = admin_url( 'tools.php?page=ws-feed-monitor' );
    echo '<div class="notice notice-error"><p>'
        . '<strong>WhistleblowerShield Feed Monitor:</strong> Last poll failed — '
        . esc_html( $error ) . '. '
        . '<a href="' . esc_url( $url ) . '">View Feed Monitor &rarr;</a>'
        . '</p></div>';
}
