<?php
/**
 * File: admin-columns.php
 *
 * Adds dataset status columns to the Jurisdiction list table
 * in the WordPress admin. Each column shows a visual indicator
 * for whether the corresponding addendum (summary, statutes)
 * exists and is published.
 *
 * VERSION
 * -------
 * 2.1.0  Initial implementation
 * 2.1.3  Added column header registration (was missing)
 *        Visual status icons via dashicons
 * 2.3.1  Added Citations column. Uses ws_get_attached_citation_count()
 *        (defined in admin-navigation.php, which loads first).
 *        Badge shows count with red/orange/green thresholds (0/1-2/3+).
 * 3.0.0  Removed Resources column (CPT deleted). Replaced ACF relationship
 *        field lookups (ws_related_summary, ws_related_statutes) with
 *        taxonomy queries on ws_jurisdiction — matches admin-navigation.php.
 * 3.1.0  Added columns for jx-statute, jx-citation, jx-interpretation,
 *        ws-legal-update, ws-agency, and ws-assist-org list tables.
 * 3.1.1  Added inline comments to direct meta reads explaining why the
 *        query layer is not used in admin list table context.
 * 3.8.0  jx-interpretation Court column updated to use ws_court_lookup()
 *        for label resolution. 'other' court key shows the free-text
 *        ws_jx_interp_court_name value instead of the raw key.
 * 3.8.1  ws_agency_code column migrated here from cpt-agencies.php.
 *        Duplicate manage_ws-agency_posts_columns / _custom_column hooks in
 *        that file removed — admin-columns.php is the single source for all
 *        CPT column definitions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── Register custom columns ───────────────────────────────────────────────────

add_filter( 'manage_jurisdiction_posts_columns', 'ws_add_jx_status_columns' );
function ws_add_jx_status_columns( $columns ) {
    // Insert after the title column
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['summary']   = 'Summary';
            $new['statutes']  = 'Statutes';
            $new['citations'] = 'Citations';
        }
    }
    return $new;
}


// ── Render column content ─────────────────────────────────────────────────────

add_action( 'manage_jurisdiction_posts_custom_column', 'ws_render_jx_status_column', 10, 2 );
function ws_render_jx_status_column( $column, $post_id ) {

    // Citations column uses count-based display.
    if ( $column === 'citations' ) {
        $count = ws_get_attached_citation_count( $post_id );
        if ( $count === 0 ) {
            echo '<span style="color:#dc3232; font-weight:600;">0</span>';
        } elseif ( $count <= 2 ) {
            echo '<span style="color:#ffa500; font-weight:600;">' . $count . '</span>';
        } else {
            echo '<span style="color:#46b450; font-weight:600;">' . $count . '</span>';
        }
        return;
    }

    $cpt_map = [
        'summary'  => 'jx-summary',
        'statutes' => 'jx-statute',
    ];

    if ( ! isset( $cpt_map[ $column ] ) ) return;

    // Resolve jurisdiction term for this post.
    $terms   = wp_get_post_terms( $post_id, WS_JURISDICTION_TAXONOMY );
    $term_id = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->term_id : 0;

    $related_id = 0;
    if ( $term_id ) {
        $ids = get_posts( [
            'post_type'      => $cpt_map[ $column ],
            'post_status'    => [ 'publish', 'draft', 'pending' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => [ [
                'taxonomy' => WS_JURISDICTION_TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ] ],
        ] );
        $related_id = ! empty( $ids ) ? $ids[0] : 0;
    }

    if ( $related_id ) {
        $status = get_post_status( $related_id );
        if ( $status === 'publish' ) {
            echo '<span class="dashicons dashicons-yes" style="color:#46b450;" title="Published"></span>';
        } else {
            echo '<span class="dashicons dashicons-warning" style="color:#ffa500;" title="' . esc_attr( ucfirst( $status ) ) . '"></span>';
        }
    } else {
        echo '<span class="dashicons dashicons-no-alt" style="color:#dc3232;" title="Missing"></span>';
    }
}


// ── Make columns sortable (optional — non-sortable by default) ────────────────
// These columns are status indicators, not data fields, so sorting is omitted.


// ════════════════════════════════════════════════════════════════════════════
// jx-statute columns: Jurisdiction, Attach, Disclosure Type
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_jx-statute_posts_columns', 'ws_add_statute_columns' );
function ws_add_statute_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']            = 'Jurisdiction';
            $new['ws_attach']        = 'Attached';
            $new['ws_disclosure']    = 'Disclosure Type';
        }
    }
    return $new;
}

add_action( 'manage_jx-statute_posts_custom_column', 'ws_render_statute_column', 10, 2 );
function ws_render_statute_column( $column, $post_id ) {
    if ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_attach' ) {
        // Direct meta read — admin list table display only; query layer is for front-end shortcode rendering.
        $flag = get_post_meta( $post_id, 'ws_attach_flag', true );
        echo $flag ? '<span class="dashicons dashicons-yes" style="color:#46b450;"></span>'
                   : '<span class="dashicons dashicons-minus" style="color:#999;"></span>';
    } elseif ( $column === 'ws_disclosure' ) {
        $terms = get_the_terms( $post_id, 'ws_disclosure_type' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// jx-citation columns: Jurisdiction, Attach, Type
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_jx-citation_posts_columns', 'ws_add_citation_columns' );
function ws_add_citation_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']     = 'Jurisdiction';
            $new['ws_attach'] = 'Attached';
            $new['ws_type']   = 'Type';
        }
    }
    return $new;
}

add_action( 'manage_jx-citation_posts_custom_column', 'ws_render_citation_column', 10, 2 );
function ws_render_citation_column( $column, $post_id ) {
    if ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_attach' ) {
        // Direct meta read — admin list table display only; query layer is for front-end shortcode rendering.
        $flag = get_post_meta( $post_id, 'ws_attach_flag', true );
        echo $flag ? '<span class="dashicons dashicons-yes" style="color:#46b450;"></span>'
                   : '<span class="dashicons dashicons-minus" style="color:#999;"></span>';
    } elseif ( $column === 'ws_type' ) {
        // Direct meta read — admin list table display only.
        $type = get_post_meta( $post_id, 'ws_jx_citation_type', true );
        echo $type ? esc_html( $type ) : '<span style="color:#999;">—</span>';
    }
}


// ════════════════════════════════════════════════════════════════════════════
// jx-interpretation columns: Jurisdiction, Court, Year, Favorable
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_jx-interpretation_posts_columns', 'ws_add_interp_columns' );
function ws_add_interp_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']        = 'Jurisdiction';
            $new['ws_court']     = 'Court';
            $new['ws_year']      = 'Year';
            $new['ws_favorable'] = 'Favorable';
        }
    }
    return $new;
}

add_action( 'manage_jx-interpretation_posts_custom_column', 'ws_render_interp_column', 10, 2 );
function ws_render_interp_column( $column, $post_id ) {
    if ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_court' ) {
        // Direct meta reads — admin list table display only; query layer is for front-end shortcode rendering.
        $court_key = get_post_meta( $post_id, 'ws_jx_interp_court', true );
        if ( $court_key === 'other' ) {
            $name = get_post_meta( $post_id, 'ws_jx_interp_court_name', true ) ?: 'Other';
            echo esc_html( $name );
        } else {
            $court_entry = ws_court_lookup( $court_key );
            echo $court_entry ? esc_html( $court_entry['short'] ) : ( $court_key ? esc_html( $court_key ) : '<span style="color:#999;">—</span>' );
        }
    } elseif ( $column === 'ws_year' ) {
        $year = get_post_meta( $post_id, 'ws_jx_interp_year', true );
        echo $year ? esc_html( $year ) : '<span style="color:#999;">—</span>';
    } elseif ( $column === 'ws_favorable' ) {
        $favorable = get_post_meta( $post_id, 'ws_jx_interp_favorable', true );
        if ( $favorable === '' ) {
            echo '<span style="color:#999;">—</span>';
        } elseif ( $favorable ) {
            echo '<span class="dashicons dashicons-yes" style="color:#46b450;" title="Favorable"></span>';
        } else {
            echo '<span class="dashicons dashicons-no-alt" style="color:#dc3232;" title="Unfavorable"></span>';
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// ws-legal-update columns: Jurisdiction, Update Type, Date
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_ws-legal-update_posts_columns', 'ws_add_legal_update_columns' );
function ws_add_legal_update_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']          = 'Jurisdiction';
            $new['ws_update_type'] = 'Type';
            $new['ws_update_date'] = 'Update Date';
        }
    }
    return $new;
}

add_action( 'manage_ws-legal-update_posts_custom_column', 'ws_render_legal_update_column', 10, 2 );
function ws_render_legal_update_column( $column, $post_id ) {
    if ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_update_type' ) {
        // Direct meta reads — admin list table display only; query layer is for front-end shortcode rendering.
        $type = get_post_meta( $post_id, 'ws_legal_update_type', true );
        echo $type ? esc_html( ucfirst( str_replace( '_', ' ', $type ) ) ) : '<span style="color:#999;">—</span>';
    } elseif ( $column === 'ws_update_date' ) {
        $date = get_post_meta( $post_id, 'ws_legal_update_date', true );
        echo $date ? esc_html( $date ) : '<span style="color:#999;">—</span>';
    }
}


// ════════════════════════════════════════════════════════════════════════════
// ws-agency columns: Jurisdiction, Process Types, Languages
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_ws-agency_posts_columns', 'ws_add_agency_columns' );
function ws_add_agency_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']           = 'Jurisdiction';
            $new['ws_agency_code']  = 'Agency Code';
            $new['ws_process_type'] = 'Process Types';
            $new['ws_languages']    = 'Languages';
        }
    }
    return $new;
}

add_action( 'manage_ws-agency_posts_custom_column', 'ws_render_agency_column', 10, 2 );
function ws_render_agency_column( $column, $post_id ) {
    if ( $column === 'ws_agency_code' ) {
        // Direct meta read — admin list table display only.
        echo esc_html( get_post_meta( $post_id, 'ws_agency_code', true ) );
    } elseif ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_process_type' ) {
        $terms = get_the_terms( $post_id, 'ws_process_type' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    } elseif ( $column === 'ws_languages' ) {
        $terms = get_the_terms( $post_id, 'ws_languages' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// ws-ag-procedure columns: Agency, Type, Disclosure Types, Deadline
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_ws-ag-procedure_posts_columns', 'ws_add_procedure_columns' );
function ws_add_procedure_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_proc_agency']           = 'Agency';
            $new['ws_proc_type']             = 'Type';
            $new['ws_proc_disclosure_types'] = 'Disclosure Types';
            $new['ws_proc_deadline']         = 'Deadline';
        }
    }
    return $new;
}

add_action( 'manage_ws-ag-procedure_posts_custom_column', 'ws_render_procedure_column', 10, 2 );
function ws_render_procedure_column( $column, $post_id ) {
    if ( $column === 'ws_proc_agency' ) {
        // Direct meta read — admin list table display only.
        $agency_id = (int) get_post_meta( $post_id, 'ws_proc_agency_id', true );
        if ( $agency_id ) {
            $edit_url = get_edit_post_link( $agency_id );
            echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( get_the_title( $agency_id ) ) . '</a>';
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_proc_type' ) {
        $type   = get_post_meta( $post_id, 'ws_proc_type', true );
        $labels = [
            'disclosure'  => 'Disclosure',
            'retaliation' => 'Retaliation',
            'both'        => 'Both',
        ];
        echo esc_html( $labels[ $type ] ?? '—' );
    } elseif ( $column === 'ws_proc_disclosure_types' ) {
        $terms = get_the_terms( $post_id, 'ws_disclosure_type' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    } elseif ( $column === 'ws_proc_deadline' ) {
        $days  = (int) get_post_meta( $post_id, 'ws_proc_deadline_days', true );
        $start = get_post_meta( $post_id, 'ws_proc_deadline_clock_start', true );
        if ( $days > 0 ) {
            $start_labels = [
                'adverse_action' => 'adverse action',
                'knowledge'      => 'date of knowledge',
                'last_act'       => 'last act',
                'varies'         => 'varies',
            ];
            $start_label = $start_labels[ $start ] ?? '';
            echo esc_html( $days . ' days' . ( $start_label ? ' from ' . $start_label : '' ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }
}


// ════════════════════════════════════════════════════════════════════════════
// ws-assist-org columns: Jurisdiction, Case Stages, Languages
// ════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_ws-assist-org_posts_columns', 'ws_add_assist_org_columns' );
function ws_add_assist_org_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ws_jx']         = 'Jurisdiction';
            $new['ws_case_stage'] = 'Case Stages';
            $new['ws_languages']  = 'Languages';
        }
    }
    return $new;
}

add_action( 'manage_ws-assist-org_posts_custom_column', 'ws_render_assist_org_column', 10, 2 );
function ws_render_assist_org_column( $column, $post_id ) {
    if ( $column === 'ws_jx' ) {
        $terms = get_the_terms( $post_id, WS_JURISDICTION_TAXONOMY );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#dc3232;">—</span>';
        }
    } elseif ( $column === 'ws_case_stage' ) {
        $terms = get_the_terms( $post_id, 'ws_case_stage' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    } elseif ( $column === 'ws_languages' ) {
        $terms = get_the_terms( $post_id, 'ws_languages' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }
}
