<?php
/**
 * audit-trail.php
 *
 * Records tamper-resistant audit metadata on every save of any ws-core CPT.
 *
 * Two hidden meta keys are written directly to wp_postmeta — they are NOT
 * ACF fields and are never exposed in the WordPress admin UI:
 *
 *   _ws_last_edited_by  — The WordPress user ID and display name of whoever
 *                         most recently saved the post. Overwritten on each save.
 *
 *   _ws_edit_history    — An append-only log. Each save appends one entry:
 *                         { user_id, display_name, timestamp (UTC ISO 8601) }
 *                         Never overwritten. Grows for the life of the post.
 *
 * These fields fire at save_post priority 99 — after ACF has finished
 * writing its own fields — to avoid any race condition.
 *
 * v1.8.0: addendum CPTs renamed to jx-* prefix.
 * v1.9.0: legal-update renamed to ws-update. jurisdiction-type taxonomy removed.
 * v1.9.2: ws-update renamed to ws-legal-update.
 */

defined( 'ABSPATH' ) || exit;

function ws_audited_post_types() {
    return [
        'jurisdiction',
        'jx-summary',
        'jx-resources',
        'jx-procedures',
        'jx-statutes',
        'ws-legal-update',
    ];
}

add_action( 'save_post', 'ws_record_audit_trail', 99, 2 );
function ws_record_audit_trail( $post_id, $post ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! in_array( $post->post_type, ws_audited_post_types(), true ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $current_user = wp_get_current_user();
    $user_id      = (int) $current_user->ID;
    $display_name = sanitize_text_field( $current_user->display_name );
    $timestamp    = gmdate( 'c' );

    $last_edited = [
        'user_id'      => $user_id,
        'display_name' => $display_name,
        'timestamp'    => $timestamp,
    ];

    update_post_meta( $post_id, '_ws_last_edited_by', $last_edited );

    $history = get_post_meta( $post_id, '_ws_edit_history', true );

    if ( ! is_array( $history ) ) {
        $history = [];
    }

    $history[] = [
        'user_id'      => $user_id,
        'display_name' => $display_name,
        'timestamp'    => $timestamp,
    ];

    update_post_meta( $post_id, '_ws_edit_history', $history );
}

function ws_get_last_editor( $post_id ) {
    $data = get_post_meta( (int) $post_id, '_ws_last_edited_by', true );
    return is_array( $data ) ? $data : null;
}

function ws_get_edit_history( $post_id ) {
    $data = get_post_meta( (int) $post_id, '_ws_edit_history', true );
    return is_array( $data ) ? $data : [];
}
/**
 * DATA INTEGRITY: Automatically sync two-way relationships.
 * When an addendum (jx-*) is saved, update the parent Jurisdiction's relationship field.
 */
add_action( 'acf/save_post', 'ws_sync_jurisdiction_relationships', 20 );
function ws_sync_jurisdiction_relationships( $post_id ) {
    $post_type = get_post_type( $post_id );
    $addendum_types = ['jx-summary', 'jx-statutes', 'jx-procedures', 'jx-resources'];

    if ( ! in_array( $post_type, $addendum_types ) ) {
        return;
    }

    // Get the linked jurisdiction ID from this post
    // Note: Adjust the 'ws_*_jurisdiction' key if your field names differ per type
    $jurisdiction_id = get_field( 'ws_jurisdiction', $post_id ); 

    if ( $jurisdiction_id ) {
        // Map CPT to the corresponding relationship field on the Jurisdiction CPT
        $map = [
            'jx-summary'    => 'ws_related_summary',
            'jx-statutes'   => 'ws_related_statutes',
            'jx-procedures' => 'ws_related_procedures',
            'jx-resources'  => 'ws_related_resources'
        ];

        update_field( $map[$post_type], $post_id, $jurisdiction_id );
    }
}