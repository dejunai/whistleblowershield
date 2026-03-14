<?php
/**
 * acf-statutes.php
 *
 * Registers ACF Pro fields for the `ws-statute` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for individual statutes, moving away from 
 * the "blob" model. This enables granular queries for deadlines, 
 * enforcement agencies, and misconduct categories.
 *
 * @package    WhistleblowerShield
 * @author     Dejunai
 */

if ( function_exists( 'acf_add_local_field_group' ) ) :

    acf_add_local_field_group( [
        'key'                   => 'group_ws_statute_details',
        'title'                 => 'Statute Details & Deadlines',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-statute',
        ] ] ],

        'fields' => [

            // ────────────────────────────────────────────────────────────────
            // Tab: Legal Basis
            //
            // Identifies the official name of the law and links it to the 
            // canonical jurisdiction code and misconduct taxonomy.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_statute_legal',
                'label' => 'Legal Basis',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_statute_official_name',
                'label'        => 'Official Statutory Name',
                'name'         => 'ws_statute_official_name',
                'type'         => 'text',
                'instructions' => 'Use standard legal notation, e.g., "California Labor Code § 1102.5" or "5 U.S.C. § 2302".',
                'required'     => 1,
                'wrapper'      => [ 'width' => '70' ],
            ],

            [
                'key'          => 'field_ws_jx_code',
                'label'        => 'Jurisdiction Code',
                'name'         => 'ws_jx_code',
                'type'         => 'text',
                'instructions' => 'USPS Code (e.g., CA, TX, US).',
                'required'     => 1,
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_ws_disclosure_cat',
                'label'        => 'Disclosure Categories',
                'name'         => 'ws_disclosure_cat',
                'type'         => 'taxonomy',
                'taxonomy'     => 'ws_disclosure_cat',
                'field_type'   => 'multi_select',
                'instructions' => 'Classify the types of misconduct this law protects.',
                'add_term'     => 0,
                'save_terms'   => 1,
                'load_terms'   => 1,
                'return_format' => 'id',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Statutes of Limitations
            //
            // Structured data for critical filing deadlines. This replaces 
            // prose with queryable time units and triggers.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_statute_deadlines',
                'label' => 'Statutes of Limitations',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_statute_limit_value',
                'label'        => 'Filing Window Value',
                'name'         => 'ws_statute_limit_value',
                'type'         => 'number',
                'instructions' => 'The numeric count for the deadline.',
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_ws_statute_limit_unit',
                'label'        => 'Time Unit',
                'name'         => 'ws_statute_limit_unit',
                'type'         => 'select',
                'choices'      => [
                    'days'   => 'Days',
                    'months' => 'Months',
                    'years'  => 'Years',
                ],
                'default_value' => 'days',
                'wrapper'      => [ 'width' => '30' ],
            ],

            [
                'key'          => 'field_ws_statute_trigger',
                'label'        => 'Deadline Trigger',
                'name'         => 'ws_statute_trigger',
                'type'         => 'select',
                'instructions' => 'When does the clock start ticking?',
                'choices'      => [
                    'adverse_action' => 'Date of Adverse Action',
                    'discovery'      => 'Date of Discovery',
                    'violation'      => 'Date of Violation',
                ],
                'wrapper'      => [ 'width' => '40' ],
            ],

            [
                'key'          => 'field_ws_statute_tolling_notes',
                'label'        => 'Tolling & Extension Notes',
                'name'         => 'ws_statute_tolling_notes',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Describe specific conditions that pause the statutory clock.',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Statutes of Limitations (Additions)
            // ────────────────────────────────────────────────────────────────

            [
                'key'           => 'field_ws_statute_exhaustion_required',
                'label'         => 'Administrative Exhaustion Required?',
                'name'          => 'ws_statute_exhaustion_required',
                'type'          => 'true_false',
                'instructions'  => 'Must the whistleblower file with an agency before going to court?',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
                'wrapper'       => [ 'width' => '30' ],
            ],

            [
                'key'           => 'field_ws_statute_exhaustion_details',
                'label'         => 'Exhaustion Procedure & Deadline',
                'name'          => 'ws_statute_exhaustion_details',
                'type'          => 'textarea',
                'rows'          => 3,
                'instructions'  => 'Describe the agency filing deadline (e.g., 90 days to OSHA).',
                'required'      => 1,
                'conditional_logic' => [
                    [
                        [
                            'field'    => 'field_ws_statute_exhaustion_required',
                            'operator' => '==',
                            'value'    => '1',
                        ],
                    ],
                ],
                'wrapper'       => [ 'width' => '70' ],
            ],

            [
                'key'           => 'field_ws_statute_remedies',
                'label'         => 'Available Remedies',
                'name'          => 'ws_statute_remedies',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_remedy',
                'field_type'    => 'checkbox',
                'instructions'  => 'What can a whistleblower recover under this specific law?',
                'add_term'      => 1,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],
			
			// ────────────────────────────────────────────────────────────────
            // Tab: Relationships
            //
            // Links this statute to enforcement agencies and cross-references 
            // the parent jurisdiction record.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_statute_rel',
                'label' => 'Relationships',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_statute_related_agencies',
                'label'        => 'Primary Oversight Agencies',
                'name'         => 'ws_statute_related_agencies',
                'type'         => 'post_object',
                'post_type'    => [ 'ws-agencies' ],
                'instructions' => 'Select agencies that enforce or provide intake for this statute.',
                'multiple'     => 1,
                'ui'           => 1,
                'return_format' => 'id',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Authorship & Review
            //
            // Administrative metadata for data integrity and review cycles.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_tab_statute_review',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_statute_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'ws_statute_last_edited_author',
                'type'          => 'user',
                'readonly'      => 1,
                'wrapper'       => [ 'width' => '50' ],
            ],

            [
                'key'            => 'field_ws_statute_last_reviewed',
                'label'          => 'Last Verified Date',
                'name'           => 'ws_statute_last_reviewed',
                'type'           => 'date_picker',
                'display_format' => 'm/d/Y',
                'return_format'  => 'Ymd',
                'wrapper'       => [ 'width' => '50' ],
            ],
        ],
    ] );

endif;

// ── Readonly: lock date_created for non-admins ────────────────────────────────

add_filter( 'acf/load_field/name=ws_statute_date_created', 'ws_statute_lock_date_created' );
function ws_statute_lock_date_created( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}

// ── Readonly: lock last_edited_author for non-admins ─────────────────────────

add_filter( 'acf/load_field/name=ws_statute_last_edited_author', 'ws_statute_lock_last_edited_author' );
function ws_statute_lock_last_edited_author( $field ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        $field['readonly'] = 1;
        $field['disabled'] = 1;
    }
    return $field;
}

// ── Auto-fill: ws_statute_last_reviewed (new posts only) ─────────────────────

add_filter( 'acf/load_value/name=ws_statute_last_reviewed', 'ws_statute_autofill_last_reviewed', 10, 3 );
function ws_statute_autofill_last_reviewed( $value, $post_id, $field ) {
    if ( empty( $value ) ) {
        $value = date( 'Y-m-d' );
    }
    return $value;
}

// ── Stamp fields: written via acf/save_post priority 20 ──────────────────────

add_action( 'acf/save_post', 'ws_statute_write_stamp_fields', 20 );
function ws_statute_write_stamp_fields( $post_id ) {

    if ( get_post_type( $post_id ) !== 'ws-statute' ) {
        return;
    }

    $now_local = current_time( 'Y-m-d' );
    $now_gmt   = current_time( 'mysql', true );
    $now_gmt_d = substr( $now_gmt, 0, 10 );
    $user_id   = get_current_user_id();

    // ── Created stamps (once only) ────────────────────────────────────────

    if ( ! get_post_meta( $post_id, 'ws_statute_date_created', true ) ) {
        update_post_meta( $post_id, 'ws_statute_date_created',     $now_local );
        update_post_meta( $post_id, 'ws_statute_date_created_gmt', $now_gmt_d );
        update_post_meta( $post_id, 'ws_statute_create_author',    $user_id );
    }

    // ── Last-edited stamps (every save) ───────────────────────────────────

    update_post_meta( $post_id, 'ws_statute_last_edited',     $now_local );
    update_post_meta( $post_id, 'ws_statute_last_edited_gmt', $now_gmt_d );

    // Match the ACF key defined in acf-statutes.php for the author field
    $submitted = isset( $_POST['acf'] ) &&
                 isset( $_POST['acf']['field_ws_statute_last_edited_author'] );

    if ( ! $submitted ) {
        update_post_meta( $post_id, 'ws_statute_last_edited_author', $user_id );
    }
}