<?php
/**
 * acf-source-verify.php — Source & Verification ACF field group.
 *
 * Group key: group_source_verify_metadata  (menu_order 95)
 *
 * Attaches to: jx-statute, jx-citation, jx-interpretation, ws-agency,
 *              ws-ag-procedure, ws-assist-org, jx-summary, ws-reference
 *
 * Fields:
 *   ws_auto_source_method    Locked readonly. One of the WS_SOURCE_* constants.
 *                            Three write paths: matrix seeders write directly;
 *                            human-created defaults to WS_SOURCE_HUMAN_CREATED;
 *                            ingest tooling calls ws_set_source_method().
 *                            Admin-only visibility. Immutable after first write.
 *   ws_auto_source_name      Locked readonly. Specific origin within the method.
 *                            'Direct' for matrix_seed and human_created.
 *                            Required before verification_status can be 'verified'.
 *   ws_auto_verified_by      Readonly. Display name of first verifier.
 *   ws_auto_verified_date    Readonly. Local Y-m-d of verification transition.
 *   ws_verification_status   Select. Hidden until source_name is non-empty.
 *                            Author+ may set to 'verified'. Admin may revert.
 *   ws_needs_review          True/false. Admin-only.
 *
 * Hook dependencies (admin-hooks.php):
 *   priority 5  — ws_stamp_source_method, ws_default_verification_status
 *   priority 6  — ws_stamp_source_name
 *   priority 20 — ws_stamp_verified_by_date, ws_enforce_source_verify_roles
 *
 * @package WhistleblowerShield
 * @since   1.0.0
 * @version 3.10.0
 *
 * VERSION
 * -------
 * 1.0.0   Initial release.
 * 3.4.0   Centralized into acf/workflow/. Three-path ingest design documented.
 * 3.10.0  ws-ag-procedure added to location rules.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'acf/init', 'ws_register_source_verify_field_group' );

function ws_register_source_verify_field_group() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group( [

        'key'      => 'group_source_verify_metadata',
        'title'    => 'Source & Verification',
        'position' => 'side',
        'style'    => 'default',
        'order'    => 100,

        // ── Location rules ────────────────────────────────────────────────
        // One rule group per CPT. Each group is an OR condition; rules
        // within a group are AND. A single-rule group per CPT is the
        // cleanest pattern for multi-CPT attachment.

        'location' => [
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-statute'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-citation'       ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-interpretation' ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-agency'         ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-ag-procedure'   ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-assist-org'     ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-summary'        ] ],
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'ws-reference'      ] ],
        ],

        'fields' => [

            // ── Tab ───────────────────────────────────────────────────────

            [
                'key'   => 'field_sv_tab',
                'label' => 'Source & Verification',
                'name'  => '',
                'type'  => 'tab',
            ],

            // ════════════════════════════════════════════════════════════
            // PROVENANCE CLUSTER
            //
            // source_method and stamp fields (verified_by, verified_date)
            // are readonly and disabled — written exclusively by hooks.
            //
            // source_name is editable — supplied by the post author or
            // ingest tooling. Auto-set to 'Direct' for matrix_seed and
            // human_created posts by hook. Required before
            // verification_status can be set to 'verified'.
            // ════════════════════════════════════════════════════════════

            [
                'key'           => 'field_source_method',
                'label'         => 'Source Method',
                'name'          => 'ws_auto_source_method',
                'type'          => 'text',
                'instructions'  => 'Set automatically at post creation. Cannot be changed.',
                'required'      => 0,
                'wrapper'       => [ 'class' => 'ws-readonly-stamp' ],
                'default_value' => '',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            [
                'key'           => 'field_source_name',
                'label'         => 'Source Name',
                'name'          => 'ws_auto_source_name',
                'type'          => 'text',
                'instructions'  => 'Auto-set at creation: "Direct" for human and seeder posts; ingest tooling sets the tool or feed name. Admin-only.',
                'required'      => 0,
                'wrapper'       => [ 'class' => 'ws-source-name' ],
                'default_value' => '',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            [
                'key'           => 'field_verified_by',
                'label'         => 'Verified By',
                'name'          => 'ws_auto_verified_by',
                'type'          => 'text',
                'instructions'  => 'Auto-stamped when verification status is set to Verified.',
                'required'      => 0,
                'wrapper'       => [ 'class' => 'ws-readonly-stamp' ],
                'default_value' => '',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            [
                'key'           => 'field_verified_date',
                'label'         => 'Verified Date',
                'name'          => 'ws_auto_verified_date',
                'type'          => 'text',
                'instructions'  => 'Auto-stamped when verification status is set to Verified.',
                'required'      => 0,
                'wrapper'       => [ 'class' => 'ws-readonly-stamp' ],
                'default_value' => '',
                'readonly'      => 1,
                'disabled'      => 1,
            ],

            // ════════════════════════════════════════════════════════════
            // STATUS CLUSTER
            //
            // verification_status is hidden until source_name has a value
            // (ACF conditional logic = UI layer gate). Server-side
            // enforcement in ws_enforce_source_verify_roles() acts as the
            // hard gate regardless of UI state.
            //
            // needs_review is admin-only. Render-blocking when true,
            // completely independent of verification_status.
            // ════════════════════════════════════════════════════════════

            [
                'key'               => 'field_verification_status',
                'label'             => 'Verification Status',
                'name'              => 'ws_verification_status',
                'type'              => 'select',
                'instructions'      => 'Authors and above may mark as Verified. Only admins may revert to Unverified. Requires Source Name to be set.',
                'required'          => 0,
                'choices'           => [
                    'unverified' => 'Unverified',
                    'verified'   => 'Verified',
                ],
                'default_value'     => 'unverified',
                'allow_null'        => 0,
                'return_format'     => 'value',

                // ── Conditional logic (UI layer) ──────────────────────────
                // Hides this field until source_name is non-empty.
                // The server-side hook is the hard enforcement layer.

                'conditional_logic' => [
                    [
                        [
                            'field'    => 'field_source_name',
                            'operator' => '!=empty',
                        ],
                    ],
                ],
            ],

            [
                'key'           => 'field_needs_review',
                'label'         => 'Needs Review',
                'name'          => 'ws_needs_review',
                'type'          => 'true_false',
                'instructions'  => 'Admin only. When enabled, this post is blocked from front-end rendering regardless of verification status. Clear this flag once the review is complete.',
                'required'      => 0,
                'default_value' => 0,
                'ui'            => 1,
                'ui_on_text'    => 'Review Required',
                'ui_off_text'   => 'Clear',
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_source_verify_field_group
