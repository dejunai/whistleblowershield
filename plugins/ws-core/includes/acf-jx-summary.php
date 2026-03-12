<?php
/**
 * acf-summary.php
 *
 * Registers the ACF Pro field group for the `jx-summary` CPT.
 * (Renamed from `jurisdiction-summary` in v1.8.0 — see ws-core.php changelog.)
 *
 * Fields registered:
 *   Content:     ws_jx_sum_jurisdiction, ws_jx_sum_jurisdiction_type, ws_jurisdiction_summary, ws_jx_summary_sources
 *   Dates:       ws_jx_sum_date_created, ws_jx_sum_last_reviewed
 *   Authorship:  ws_jx_sum_author (User field, Author+ only), ws_jx_sum_human_reviewed,
 *                ws_jx_sum_legal_review_completed, ws_jx_sum_legal_reviewer
 *
 * Auto-fill behavior for date and author fields is handled via
 * ACF load_value filters below.
 *
 * DEPRECATED field `sources_public` is NOT registered here.
 * A cleanup routine removes it from existing post meta — see bottom of file.
 */

defined( 'ABSPATH' ) || exit;

// ── Field group registration ──────────────────────────────────────────────────

add_action( 'acf/init', 'ws_register_acf_jurisdiction_summary_fields' );
function ws_register_acf_jurisdiction_summary_fields() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'      => 'group_ws_jurisdiction_summary',
        'title'    => 'Jurisdiction Summary',
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'jx-summary',
        ] ] ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'fields' => [

            // ── Tab: Content ──────────────────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_content',
                'label' => 'Content',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_jx_sum_jurisdiction',
                'label'        => 'Jurisdiction',
                'name'         => 'ws_jx_sum_jurisdiction',
                'type'         => 'text',
                'instructions' => 'Jurisdiction display name — e.g., California',
                'required'     => 1,
            ],
            [
                'key'          => 'field_ws_jx_sum_jurisdiction_type',
                'label'        => 'Jurisdiction Type',
                'name'         => 'ws_jx_sum_jurisdiction_type',
                'type'         => 'select',
                'required'     => 1,
                'choices'      => [
                    'state'     => 'U.S. State',
                    'federal'   => 'Federal',
                    'territory' => 'U.S. Territory',
                    'district'  => 'District (D.C.)',
                ],
                'default_value' => 'state',
                'allow_null'    => 0,
                'ui'            => 1,
            ],
            [
                'key'          => 'field_ws_jurisdiction_summary',
                'label'        => 'Jurisdiction Summary',
                'name'         => 'ws_jurisdiction_summary',
                'type'         => 'wysiwyg',
                'instructions' => '<strong>IMPORTANT:</strong> Use the editor toolbar for all formatting. Do NOT paste raw Markdown (**, ##, ---). Content must be clean HTML. This field is rendered directly on the jurisdiction page.',
                'required'     => 1,
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload'  => 0,
                'delay'         => 0,
            ],
            [
                'key'          => 'field_ws_jx_summary_sources',
                'label'        => 'Sources & Citations',
                'name'         => 'ws_jx_summary_sources',
                'type'         => 'textarea',
                'instructions' => 'List source citations, statute references, and attribution. One per line recommended.',
                'rows'         => 6,
            ],

            // ── Tab: Dates ────────────────────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_dates',
                'label' => 'Dates',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_jx_sum_date_created',
                'label'        => 'Date Created',
                'name'         => 'ws_jx_sum_date_created',
                'type'         => 'date_picker',
                'instructions' => 'Set automatically on creation. Do not change after the initial save.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 0,
            ],
            [
                'key'          => 'field_ws_jx_last_reviewed',
                'label'        => 'Last Reviewed',
                'name'         => 'ws_last_reviewed',
                'type'         => 'date_picker',
                'instructions' => 'Update this date each time the summary content is meaningfully revised. This date is displayed publicly on the jurisdiction page.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 0,
            ],

            // ── Tab: Authorship & Review ───────────────────────────────────

            [
                'key'   => 'field_ws_jx_sum_tab_authorship',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_ws_jx_sum_author',
                'label'        => 'Author',
                'name'         => 'ws_jx_sum_author',
                'type'         => 'user',
                'instructions' => 'Credited author displayed on the front end. Defaults to the current user. May be changed to any registered user with Author role or above.',
                'role'         => [ 'author', 'editor', 'administrator' ],
                'allow_null'   => 0,
                'multiple'     => 0,
                'return_format' => 'array',
            ],
            [
                'key'          => 'field_ws_jx_sum_human_reviewed',
                'label'        => 'Human Reviewed',
                'name'         => 'ws_jx_sum_human_reviewed',
                'type'         => 'true_false',
                'instructions' => 'Check when a human (non-AI) has reviewed and approved this summary.',
                'ui'           => 1,
                'ui_on_text'   => 'Reviewed',
                'ui_off_text'  => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'          => 'field_ws_jx_sum_legal_review_completed',
                'label'        => 'Legal Review Completed',
                'name'         => 'ws_jx_sum_legal_review_completed',
                'type'         => 'true_false',
                'instructions' => 'Check when a licensed attorney has reviewed this summary.',
                'ui'           => 1,
                'ui_on_text'   => 'Completed',
                'ui_off_text'  => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'          => 'field_ws_jx_sum_legal_reviewer',
                'label'        => 'Legal Reviewer',
                'name'         => 'ws_jx_sum_legal_reviewer',
                'type'         => 'text',
                'instructions' => 'Full name of the licensed attorney who reviewed this summary. Populate only when Legal Review Completed is checked.',
                'conditional_logic' => [ [ [
                    'field'    => 'field_ws_jx_sum_legal_review_completed',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

        ], // end fields
    ] ); // end acf_add_local_field_group
}

// ── Auto-fill: ws_jx_sum_date_created (new posts only) ───────────────────────────────

add_filter( 'acf/load_value/name=ws_jx_sum_date_created', 'ws_autofill_jx_sum_date_created', 10, 3 );
function ws_autofill_jx_sum_date_created( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = date( 'Y-m-d' );
    }
    return $value;
}

// ── Auto-fill: ws_jx_sum_last_reviewed (new posts only) ──────────────────────────────

add_filter( 'acf/load_value/name=ws_jx_sum_last_reviewed', 'ws_autofill_jx_sum_last_reviewed', 10, 3 );
function ws_autofill_jx_sum_last_reviewed( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = date( 'Y-m-d' );
    }
    return $value;
}

// ── Auto-fill: ws_jx_sum_author (current user, new posts only) ───────────────────────

add_filter( 'acf/load_value/name=ws_jx_sum_author', 'ws_autofill_jx_sum_author', 10, 3 );
function ws_autofill_jx_sum_author( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = get_current_user_id();
    }
    return $value;
}

// ── Cleanup: remove all orphaned Meta Box meta keys ──────────────────────────
//
// Runs once on admin_init per option flag. Safe to run repeatedly — the flag
// prevents it from firing again after the first successful pass.
//
// Keys targeted:
//   sources_public               — duplicate of ws_summary_sources (v1.0 cleanup)
//   ws_postal_code               — Meta Box field, no ws-core equivalent
//   ws_government_portal_url     — replaced by ws_gov_portal_url
//   ws_flag_image                — replaced by ws_jurisdiction_flag
//   ws_state_leadership_last_verified — replaced by ws_last_reviewed
//   ws_state_gov_office_url      — replaced by ws_governor_url
//   ws_state_mayor_office_url    — replaced by ws_mayor_url
//   ws_state_ag_office_url       — replaced by ws_legal_authority_url
//   ws_governor_url              — replaced by ws_head_url
//   ws_mayor_url                 — replaced by ws_head_url

add_action( 'admin_init', 'ws_cleanup_metabox_remnants' );
function ws_cleanup_metabox_remnants() {

    if ( get_option( 'ws_metabox_cleanup_v4' ) ) {
        return;
    }

    global $wpdb;

    $deprecated_keys = [
        'sources_public',
        'ws_postal_code',
        'ws_government_portal_url',
        'ws_flag_image',
        'ws_state_leadership_last_verified',
        'ws_state_gov_office_url',
        'ws_state_ag_office_url',
		'ws_gov_legal_authority_url',
		'ws_state_mayor_office_url',
		'ws_governor_url',
		'ws_mayor_url',
		'ws_flag_attribution',
		'_ws_flag_attribution',
		'ws_flag_license',
		'_ws_flag_license',
		'_ws_mayor_url',
		'ws_mayor_label',
		'_ws_mayor_label',
		'_ws_governor_url',
		'ws_governor_label',
		'_ws_governor_label',
		'ws_flag_attribution_url',
		'_ws_flag_attribution_url',
		'ws_author',
		'ws_summary_date',
		'ws_summary_sources',
		'_ws_summary',
		'_ws_summary_sources',
		'ws_date_created',
		'_ws_date_created',
		'ws_last_reviewed',
		'_ws_last_reviewed',
		'_ws_author',
		'ws_human_reviewed',
		'_ws_human_reviewed',
		'ws_legal_review_completed',
		'_ws_legal_review_completed',
		'ws_flag',
		'_ws_flag',
    ];

    $total_deleted = 0;
    $results       = [];

    foreach ( $deprecated_keys as $key ) {
        $deleted = $wpdb->delete(
            $wpdb->postmeta,
            [ 'meta_key' => $key ],
            [ '%s' ]
        );
        if ( $deleted ) {
            $results[ $key ] = $deleted;
            $total_deleted  += $deleted;
        }
    }

    update_option( 'ws_metabox_cleanup_v3', true );

    if ( $total_deleted > 0 && is_admin() ) {
        add_action( 'admin_notices', function() use ( $total_deleted, $results ) {
            $detail = implode( ', ', array_map(
                fn( $k, $v ) => "<code>{$k}</code> ({$v})",
                array_keys( $results ),
                $results
            ) );
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo "<strong>WhistleblowerShield Core:</strong> Removed {$total_deleted} orphaned Meta Box meta ";
            echo "entries: {$detail}.";
            echo '</p></div>';
        } );
    }
}
