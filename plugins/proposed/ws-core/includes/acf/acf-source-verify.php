<?php
/**
 * File: acf-source-verify.php
 *
 * WhistleblowerShield Core Plugin
 *
 * PURPOSE
 * -------
 * Registers a single ACF field group — "Source & Verification" — that
 * attaches to all research-content CPTs via location rules. Centralising
 * these fields in one file ensures consistent field keys, names, and
 * behaviour across every post type without duplication.
 *
 * FIELD GROUP
 * -----------
 *   Source & Verification (tab, side column)
 *
 *   Provenance cluster:
 *     source_method      Locked readonly. Auto-stamped at creation.
 *                        Three write paths: ingest tooling calls
 *                        ws_set_source_method(); manual admin creation
 *                        defaults to human_created; matrix seeder writes
 *                        matrix_seed directly. Admin-only visibility.
 *     source_name        Locked readonly. Auto-stamped at creation.
 *                        Identifies the specific source within the method
 *                        (e.g. 'Claude AI', 'Direct'). Three write paths:
 *                        ingest tooling calls ws_set_source_name();
 *                        human_created and matrix_seed default to 'Direct';
 *                        other methods leave empty until ingest sets it.
 *                        Admin-only visibility. Required before
 *                        verification_status can be set to 'verified'.
 *     verified_by        Readonly text. Display name of the user who
 *                        first set verification_status to 'verified'.
 *                        Auto-stamped by hook; never editable.
 *     verified_date      Readonly text. Local datetime of the
 *                        verification transition. Auto-stamped by hook;
 *                        never editable.
 *
 *   Status cluster:
 *     verification_status  Select. Hidden until source_name has a value.
 *                          Author+ may set to 'verified'. Admin may
 *                          revert to 'unverified'. Server-side hook
 *                          enforces both rules.
 *     needs_review         True/False. Admin-only. When true, all
 *                          front-end render blocking is active regardless
 *                          of verification_status. Admin sets and clears.
 *
 * ATTACHED CPTs
 * -------------
 *   jx-statute, jx-citation, jx-interpretation, ws-agency,
 *   ws-assist-org, jx-summary, ws-reference
 *
 * POLICY NOTE
 * -----------
 *   jx-summary posts must always carry source_method = 'human_created'.
 *   This is enforced in admin-hooks.php, not here. The field group itself
 *   applies no CPT-specific logic.
 *
 * CONDITIONAL LOGIC
 * -----------------
 *   verification_status is hidden until source_name is non-empty.
 *   This is enforced both in ACF conditional logic (UI layer) and in
 *   ws_enforce_source_verify_roles() (server-side layer).
 *
 * HOOK DEPENDENCIES
 * -----------------
 *   All write logic lives in admin-hooks.php:
 *     - source_method set at acf/save_post priority 5 (first save only)
 *     - source_name auto-set to 'Direct' for matrix_seed + human_created
 *     - verification_status defaulted at priority 5 (first save only)
 *     - verified_by + verified_date stamped on transition to 'verified'
 *     - needs_review and verification_status role-gating at priority 20
 *
 * INGEST NOTE
 * -----------
 *   JSON ingest files should include a header block specifying
 *   source_method and source_name so the import tooling can stamp
 *   every record in the batch consistently without prompting.
 *   See ingest tooling documentation for the expected header schema.
 *
 *
 * VERSION
 * -------
 * 1.0.0  Initial implementation
 * 1.1.0  Added source_name field; conditional logic on verification_status;
 *        added feed_import constant; updated method table.
 * 1.2.0  ws_auto_ pass (ws-core v3.2.0): stamp field meta keys prefixed:
 *        source_method → ws_auto_source_method, source_name → ws_auto_source_name,
 *        verified_by → ws_auto_verified_by, verified_date → ws_auto_verified_date.
 *        source_name locked readonly/disabled; both source fields hidden from
 *        roles below administrator via ws_hide_source_fields_for_non_admins().
 *        Docblock updated to reflect three-path ingest design.
 * 3.10.0 ws-ag-procedure added to location rules. Omission — matrix-seeded
 *        procedures are high-staleness-risk records and require the same
 *        source verification and ws_needs_review workflow as other seeded CPTs.
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
