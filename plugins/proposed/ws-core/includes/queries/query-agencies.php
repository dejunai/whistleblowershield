<?php
/**
 * query-agencies.php
 *
 * Query Layer — Agency Dataset Functions
 *
 * PURPOSE
 * -------
 * Holds dataset functions for the ws-agency CPT and its child CPTs.
 * Loaded in the Universal Layer so procedure data is available in
 * both admin and frontend contexts.
 *
 * FUNCTIONS
 * ---------
 *   ws_get_agency_procedures( $agency_id )
 *       Returns all published ws-ag-procedure records belonging to a given
 *       agency, ordered alphabetically by title. Result is cached in a
 *       per-agency transient (24 hours). Invalidated on procedure save.
 *
 * LOAD ORDER
 * ----------
 * Must be loaded after query-shared.php (depends on ws_build_record_array).
 * Loaded fourth in the query file array: helpers → shared → jurisdiction → agencies.
 *
 * DATA CONTRACT
 * -------------
 * Procedures are linked to their parent agency via the ws_proc_agency_id
 * post meta key (ACF post_object field, stores integer post ID).
 *
 * Taxonomy fields (jurisdiction, disclosure_types, procedure_type) use
 * save_terms=1 in ACF, so their values are read via wp_get_post_terms() /
 * wp_get_object_terms(), not get_post_meta(). Simple scalar fields use
 * get_post_meta() directly. ws_procedure_type is single-value — the query
 * layer returns its slug as a plain string (first term slug, or '').
 *
 * CACHING
 * -------
 * Cache key:   ws_agency_procs_{$agency_id}
 * TTL:         DAY_IN_SECONDS (24 hours)
 * Invalidated: save_post_ws-ag-procedure hook (clears the parent agency's key)
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.9.0  Initial. ws_get_agency_procedures() + per-agency transient cache.
 *        Phase 2 of ws-ag-procedure feature build.
 * 3.10.0 ws_proc_type get_post_meta() reads replaced with wp_get_object_terms()
 *        on ws_procedure_type in both ws_build_agency_procedure_row() and
 *        ws_get_procedures_for_statute(). Returns first term slug as plain
 *        string; empty string when no term assigned.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// ws_get_agency_procedures( $agency_id )
//
// Returns all published ws-ag-procedure records for a given agency,
// ordered alphabetically by title. Grouped by type (disclosure / retaliation
// / both) in the render layer — this function returns the flat list.
//
// The caller (ws_render_agency_procedures in render-agency.php) groups
// the result before rendering. Returning the flat list here keeps the
// query layer free of display logic.
//
// Return shape per row:
//   id               int     Procedure post ID.
//   title            string  Procedure post title.
//   url              string  Permalink to the individual procedure post.
//   type             string  'disclosure' | 'retaliation' | 'both'
//   jurisdiction     array   WP_Term[] from ws_jurisdiction taxonomy.
//   disclosure_types array   WP_Term[] from ws_disclosure_type taxonomy.
//   entry_point      string  How the filer initiates: online/mail/phone/in_person/multi
//   intake_url       string  Direct link to the intake form/portal for this procedure.
//   phone            string  Specific phone number for this procedure (may differ from agency).
//   identity_policy  string  'anonymous' | 'confidential' | 'identified' | 'varies'
//   intake_only      bool    True if agency receives and refers only — does not investigate.
//   deadline_days    int     Statutory deadline in calendar days. 0 = none/unknown.
//   clock_start      string  'adverse_action' | 'knowledge' | 'last_act' | 'varies' | ''
//   has_prereqs      bool    True if prerequisites must be satisfied before filing.
//   prereq_note      string  Description of prerequisite conditions (when has_prereqs).
//   walkthrough      string  Raw HTML from WYSIWYG field. Sanitize with wp_kses_post() before output.
//   exclusivity_note string  Mutual exclusivity warning text. Plain text.
//   last_reviewed    string  Y-m-d date. Empty if not yet verified.
//   record           array   ws_build_record_array() sub-array (authorship stamps).
//
// @param  int    $agency_id  Post ID of the parent ws-agency.
// @return array              Flat array of procedure data rows, or empty array.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_agency_procedures( $agency_id ) {

    $agency_id = (int) $agency_id;
    if ( ! $agency_id ) {
        return [];
    }

    $cache_key = 'ws_agency_procs_' . $agency_id;
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-ag-procedure',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        // Link: procedure → parent agency via ws_proc_agency_id post meta.
        // ACF post_object fields store the referenced post's integer ID.
        'meta_query'     => [ [
            'key'   => 'ws_proc_agency_id',
            'value' => $agency_id,
            'type'  => 'NUMERIC',
        ] ],
    ] );

    $rows = [];

    foreach ( $q->posts as $post ) {
        $row = ws_build_agency_procedure_row( (int) $post->ID );
        if ( $row ) {
            $rows[] = $row;
        }
    }

    set_transient( $cache_key, $rows, DAY_IN_SECONDS );

    return $rows;
}

/**
 * Returns one published ws-ag-procedure row for single-procedure rendering.
 *
 * @param  int   $procedure_id Procedure post ID.
 * @return array               Procedure row or [] when not found/not published.
 */
function ws_get_agency_procedure( $procedure_id ) {
    return ws_build_agency_procedure_row( (int) $procedure_id );
}

/**
 * Builds one normalized procedure row used by list and single-procedure views.
 *
 * @param  int   $pid Procedure post ID.
 * @return array      Procedure row, or [] when invalid/not published.
 */
function ws_build_agency_procedure_row( $pid ) {
    if ( ! $pid || get_post_type( $pid ) !== 'ws-ag-procedure' || get_post_status( $pid ) !== 'publish' ) {
        return [];
    }

    $agency_id  = (int) get_post_meta( $pid, 'ws_proc_agency_id', true );
    $agency_url = $agency_id ? get_permalink( $agency_id ) : '';

    // Taxonomy fields use save_terms=1 in ACF — read via WP term functions,
    // not get_post_meta(). is_wp_error() guard handles unregistered taxonomies
    // or posts with no terms assigned.
    $jx_terms    = wp_get_post_terms( $pid, WS_JURISDICTION_TAXONOMY );
    $disc_types  = wp_get_object_terms( $pid, 'ws_disclosure_type' );

    // ws_procedure_type is a single-value taxonomy. Return the slug string
    // so render-agency.php can use it as an array key without further
    // processing. Empty string when no term is assigned (draft/incomplete).
    $proc_type_terms = wp_get_object_terms( $pid, 'ws_procedure_type', [ 'fields' => 'slugs' ] );
    $proc_type       = ( ! is_wp_error( $proc_type_terms ) && ! empty( $proc_type_terms ) )
                       ? $proc_type_terms[0]
                       : '';

    return [
        'id'               => $pid,
        'title'            => get_the_title( $pid ),
        'url'              => get_permalink( $pid ),
        'agency_id'        => $agency_id,
        'agency_name'      => $agency_id ? get_the_title( $agency_id ) : '',
        'agency_url'       => $agency_url ? (string) $agency_url : '',
        'type'             => $proc_type,
        'jurisdiction'     => ( $jx_terms   && ! is_wp_error( $jx_terms   ) ) ? $jx_terms   : [],
        'disclosure_types' => ( $disc_types && ! is_wp_error( $disc_types ) ) ? $disc_types : [],
        'entry_point'      => get_post_meta( $pid, 'ws_proc_entry_point',           true ),
        'intake_url'       => get_post_meta( $pid, 'ws_proc_intake_url',            true ),
        'phone'            => get_post_meta( $pid, 'ws_proc_phone',                 true ),
        'identity_policy'  => get_post_meta( $pid, 'ws_proc_identity_policy',       true ),
        'intake_only'      => (bool) get_post_meta( $pid, 'ws_proc_intake_only',    true ),
        'deadline_days'    => (int)  get_post_meta( $pid, 'ws_proc_deadline_days',  true ),
        'clock_start'      => get_post_meta( $pid, 'ws_proc_deadline_clock_start',  true ),
        'has_prereqs'      => (bool) get_post_meta( $pid, 'ws_proc_prerequisites',  true ),
        'prereq_note'      => get_post_meta( $pid, 'ws_proc_prerequisites_note',    true ),
        // walkthrough is a WYSIWYG field — stored as raw HTML.
        // Sanitize with wp_kses_post() before output; never echo raw.
        'walkthrough'      => get_post_meta( $pid, 'ws_proc_walkthrough',           true ),
        'exclusivity_note' => get_post_meta( $pid, 'ws_proc_exclusivity_note',      true ),
        'last_reviewed'    => get_post_meta( $pid, 'ws_proc_last_reviewed',         true ),
        // Standard authorship stamp sub-array (created_by, edited_by, dates).
        'record'           => ws_build_record_array( $pid ),
    ];
}


// ════════════════════════════════════════════════════════════════════════════
// ws_get_procedures_for_statute( $statute_id )
//
// Returns all published ws-ag-procedure records that explicitly link to the
// given jx-statute post. Used by the statute section renderer to surface
// "Filing Procedures Under This Statute" on jurisdiction pages.
//
// Relationship fields (ws_proc_statute_ids) are stored by ACF as a serialized
// array of post IDs. Depending on save path these may be serialized as strings
// (common ACF UI save) or integers (programmatic/meta writes). Query both
// shapes to avoid false negatives:
//   — string shape:  ...s:3:"123";...
//   — integer shape: ...i:123;...
//
// Return shape per row:
//   id            int     Procedure post ID.
//   title         string  Procedure post title.
//   url           string  Permalink.
//   type          string  'disclosure' | 'retaliation' | 'both'
//   agency_id     int     Parent agency post ID.
//   agency_name   string  Parent agency title (empty string if agency not found).
//   agency_url    string  Parent agency permalink (empty string if not found).
//   deadline_days int     Statutory deadline in calendar days. 0 = none/unknown.
//   intake_only   bool    True if agency receives and refers only.
//
// Result cached per statute (ws_statute_procs_{id}, 24h).
// Invalidated by the acf/save_post stash hooks below.
//
// @param  int    $statute_id  Post ID of the jx-statute.
// @return array               Flat array of procedure rows, or empty array.
// ════════════════════════════════════════════════════════════════════════════

function ws_get_procedures_for_statute( $statute_id ) {

    $statute_id = (int) $statute_id;
    if ( ! $statute_id ) {
        return [];
    }

    $cache_key = 'ws_statute_procs_' . $statute_id;
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $q = new WP_Query( [
        'post_type'      => 'ws-ag-procedure',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        // Match both possible serialized value shapes used by ACF/meta writes.
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'ws_proc_statute_ids',
                'value'   => '"' . $statute_id . '"',
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'ws_proc_statute_ids',
                'value'   => ';i:' . $statute_id . ';',
                'compare' => 'LIKE',
            ],
        ],
    ] );

    $rows = [];

    foreach ( $q->posts as $post ) {

        $pid       = $post->ID;
        $agency_id = (int) get_post_meta( $pid, 'ws_proc_agency_id', true );

        // ws_procedure_type is single-value — take first slug, empty string if unset.
        $pt_terms = wp_get_object_terms( $pid, 'ws_procedure_type', [ 'fields' => 'slugs' ] );
        $pt_slug  = ( ! is_wp_error( $pt_terms ) && ! empty( $pt_terms ) ) ? $pt_terms[0] : '';

        $rows[] = [
            'id'            => $pid,
            'title'         => get_the_title( $pid ),
            'url'           => get_permalink( $pid ),
            'type'          => $pt_slug,
            'agency_id'     => $agency_id,
            'agency_name'   => $agency_id ? get_the_title( $agency_id )  : '',
            'agency_url'    => $agency_id ? (string) get_permalink( $agency_id ) : '',
            'deadline_days' => (int)  get_post_meta( $pid, 'ws_proc_deadline_days', true ),
            'intake_only'   => (bool) get_post_meta( $pid, 'ws_proc_intake_only',   true ),
        ];
    }

    set_transient( $cache_key, $rows, DAY_IN_SECONDS );

    return $rows;
}


// ════════════════════════════════════════════════════════════════════════════
// Cache Invalidation — Agency Procedures
//
// Fires on save_post_ws-ag-procedure. Reads ws_proc_agency_id from the saved
// procedure and deletes the per-agency transient so the next page load
// reflects updated procedure data.
//
// Uses save_post_ws-ag-procedure (not acf/save_post) to cover programmatic
// saves as well as ACF edit-screen saves.
// ════════════════════════════════════════════════════════════════════════════

/**
 * Request-level stash for prior ws_proc_agency_id during updates/deletes.
 *
 * @param  int      $post_id
 * @param  int|null $agency_id  Pass an int to write; null to read.
 * @return int
 */
function ws_proc_agency_stash( $post_id, $agency_id = null ) {
    static $stash = [];
    if ( null !== $agency_id ) {
        $stash[ $post_id ] = (int) $agency_id;
    }
    return (int) ( $stash[ $post_id ] ?? 0 );
}

// Capture old agency linkage before post/meta updates are written.
add_action( 'pre_post_update', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'ws-ag-procedure' ) {
        return;
    }
    ws_proc_agency_stash( $post_id, (int) get_post_meta( $post_id, 'ws_proc_agency_id', true ) );
} );

add_action( 'save_post_ws-ag-procedure', function( $post_id ) {

    // Ignore autosaves/revisions and new inserts with no previous state.
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    $old_agency_id = ws_proc_agency_stash( $post_id );

    $agency_id = (int) get_post_meta( $post_id, 'ws_proc_agency_id', true );

    if ( $agency_id ) {
        delete_transient( 'ws_agency_procs_' . $agency_id );
    }

    if ( $old_agency_id && $old_agency_id !== $agency_id ) {
        delete_transient( 'ws_agency_procs_' . $old_agency_id );
    }

}, 10, 1 );


// ════════════════════════════════════════════════════════════════════════════
// Cache Invalidation — Statute Procedures (stash + diff pattern)
//
// When a procedure is saved via the ACF edit screen, the set of linked
// statutes may change. Both the old statutes (removed links) and the new
// statutes (added links) need their transients cleared.
//
// Requires a two-priority hook pattern because ACF writes field values at
// acf/save_post priority 10 — the stash captures pre-write values at
// priority 5, and the diff runs at priority 20 after the new values are
// written.
//
// STASH:  priority 5  — read ws_proc_statute_ids from DB (old values).
// DIFF:   priority 20 — read ws_proc_statute_ids from DB (new values),
//                       compute union of old+new, delete all affected keys.
//
// On delete: before_delete_post captures statute IDs while the post still
// exists; deleted_post reads the stash and clears those transients.
// ════════════════════════════════════════════════════════════════════════════


/**
 * Stash helper for ws_proc_statute_ids before/after an ACF edit-screen save.
 *
 * Pass $ids to write; omit to read. Static storage persists for the
 * current PHP request only — values do not survive the request boundary.
 *
 * @param  int        $post_id  Procedure post ID.
 * @param  array|null $ids      Array of statute IDs to stash, or null to read.
 * @return array                The stashed IDs (empty array if nothing stashed).
 */
function ws_proc_statute_save_stash( $post_id, $ids = null ) {
    static $stash = [];
    if ( $ids !== null ) {
        $stash[ $post_id ] = $ids;
    }
    return $stash[ $post_id ] ?? [];
}

/**
 * Stash helper for statute IDs captured before a procedure post is deleted.
 *
 * @param  int        $post_id  Procedure post ID.
 * @param  array|null $ids      Array of statute IDs to stash, or null to read.
 * @return array                The stashed IDs (empty array if nothing stashed).
 */
function ws_proc_statute_delete_stash( $post_id, $ids = null ) {
    static $stash = [];
    if ( $ids !== null ) {
        $stash[ $post_id ] = $ids;
    }
    return $stash[ $post_id ] ?? [];
}


// Priority 5: capture statute IDs currently in the DB before ACF overwrites them.
add_action( 'acf/save_post', function( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-ag-procedure' ) {
        return;
    }

    $raw = get_post_meta( $post_id, 'ws_proc_statute_ids', true );
    $ids = is_array( $raw ) ? array_map( 'intval', $raw ) : [];

    ws_proc_statute_save_stash( $post_id, $ids );

}, 5 );


// Priority 20: diff old vs new statute IDs and delete affected transients.
add_action( 'acf/save_post', function( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-ag-procedure' ) {
        return;
    }

    $old_ids = ws_proc_statute_save_stash( $post_id );

    $raw     = get_post_meta( $post_id, 'ws_proc_statute_ids', true );
    $new_ids = is_array( $raw ) ? array_map( 'intval', $raw ) : [];

    // Clear transients for every statute that was linked before OR after this save.
    // Covers removed links (old \ new) and added links (new \ old) in one pass.
    $affected = array_unique( array_merge( $old_ids, $new_ids ) );

    foreach ( $affected as $statute_id ) {
        if ( $statute_id ) {
            delete_transient( 'ws_statute_procs_' . $statute_id );
        }
    }

}, 20 );


// Before delete: stash statute IDs while the post still exists.
add_action( 'before_delete_post', function( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-ag-procedure' ) {
        return;
    }

    $raw = get_post_meta( $post_id, 'ws_proc_statute_ids', true );
    $ids = is_array( $raw ) ? array_map( 'intval', $raw ) : [];

    ws_proc_statute_delete_stash( $post_id, $ids );
    ws_proc_agency_stash( $post_id, (int) get_post_meta( $post_id, 'ws_proc_agency_id', true ) );

} );


// After delete: clear statute transients and agency transient.
add_action( 'deleted_post', function( $post_id ) {

    // get_post_type() is unreliable after deletion — gate on stash having data.
    // If neither stash has data this post was not a ws-ag-procedure.
    $statute_ids = ws_proc_statute_delete_stash( $post_id );

    foreach ( $statute_ids as $statute_id ) {
        if ( $statute_id ) {
            delete_transient( 'ws_statute_procs_' . $statute_id );
        }
    }

    $agency_id = ws_proc_agency_stash( $post_id );
    if ( $agency_id ) {
        delete_transient( 'ws_agency_procs_' . $agency_id );
    }

} );
