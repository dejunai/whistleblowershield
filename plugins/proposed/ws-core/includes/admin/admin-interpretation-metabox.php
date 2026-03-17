<?php
/**
 * admin-interpretation-metabox.php
 *
 * Adds a "Federal Court Interpretations" meta box to the jx-statute edit screen.
 *
 * PURPOSE
 * -------
 * Displays all jx-interpretation records linked to the current statute,
 * with a direct "Add New Interpretation" button that opens the creation
 * form in a new tab. Only renders when the statute's ws_jx_code is 'US',
 * since only federal statutes receive court interpretation records.
 *
 * WORKFLOW
 * --------
 * 1. Editor saves a jx-statute post with ws_jx_code = 'US'.
 * 2. The meta box appears below the ACF field groups.
 * 3. Existing interpretations are listed: case name, court, year, favorable?,
 *    and an Edit link.
 * 4. "Add New Interpretation" opens:
 *    post-new.php?post_type=jx-interpretation&statute_id={ID}&ws_jx_code=US
 *    in a new browser tab.
 * 5. After saving the new interpretation, the editor closes the tab and
 *    refreshes the statute screen to see the updated list.
 *
 * NOTE: The button is disabled (with tooltip) on auto-draft statutes because
 * the statute_id must reference a saved post to be meaningful.
 *
 * @package    WhistleblowerShield
 * @since      2.4.0
 * @author     Dejunai
 *
 * VERSION
 * -------
 * 2.4.0  Initial release.
 * 2.4.1  Bug #7 fix: get_posts() had two 'meta_key' entries in the same
 *         array. PHP silently used the second value ('ws_interp_year'),
 *         discarding the 'meta_value' => $post->ID filter entirely and
 *         returning interpretations across all statutes. Fixed by using
 *         a proper meta_query for the statute filter and a separate
 *         'meta_key' / 'orderby' pair for the year sort.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', 'ws_register_interpretation_metabox' );

/**
 * Registers the meta box on the jx-statute edit screen.
 */
function ws_register_interpretation_metabox() {
    add_meta_box(
        'ws_interpretations',
        'Federal Court Interpretations',
        'ws_render_interpretation_metabox',
        'jx-statute',
        'normal',
        'default'
    );
}


/**
 * Renders the Federal Court Interpretations meta box.
 *
 * Only shows interpretation content when ws_jx_code = 'US'. For all
 * other jurisdictions a short explanatory notice is displayed instead.
 *
 * @param WP_Post $post  The current jx-statute post object.
 */
function ws_render_interpretation_metabox( $post ) {

    global $ws_court_matrix;

    // ── Jurisdiction guard ────────────────────────────────────────────────

    $jx_code = get_post_meta( $post->ID, 'ws_jx_code', true );

    if ( $jx_code !== 'US' ) {
        echo '<p style="color:#666;font-style:italic;">Court interpretation records are only tracked for federal (US) statutes.</p>';
        return;
    }

    // ── Auto-draft guard ──────────────────────────────────────────────────

    $is_draft   = ( $post->post_status === 'auto-draft' );
    $add_url    = admin_url( 'post-new.php?post_type=jx-interpretation&statute_id=' . $post->ID . '&ws_jx_code=US' );
    $post_title = esc_attr( get_the_title( $post ) );
    if ( $post_title ) {
        $add_url .= '&post_title=' . rawurlencode( 'Interpretation — ' . get_the_title( $post ) );
    }

    // ── Fetch linked interpretations ──────────────────────────────────────
    //
    // Bug #7 fix: the original call had two 'meta_key' entries in the same
    // flat array. PHP silently kept only the last ('ws_interp_year'), which
    // discarded the 'meta_value' => $post->ID filter entirely. All
    // interpretations across all statutes were returned.
    //
    // Fixed by separating the two concerns:
    //   - meta_query handles the statute filter (ws_statute_id = $post->ID)
    //   - 'meta_key' + 'orderby' handle the sort-by-year behaviour
    //
    // WP_Query resolves both independently when meta_query is present
    // alongside a standalone meta_key for ordering.

    $interpretations = get_posts( [
        'post_type'      => 'jx-interpretation',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'ws_statute_id',
                'value'   => $post->ID,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ],
        'meta_key' => 'ws_interp_year',
        'orderby'  => 'meta_value_num',
        'order'    => 'DESC',
    ] );

    // ── Render ────────────────────────────────────────────────────────────
    ?>
    <style>
        #ws_interpretations .ws-interp-table { width:100%; border-collapse:collapse; margin-bottom:12px; }
        #ws_interpretations .ws-interp-table th,
        #ws_interpretations .ws-interp-table td { padding:6px 10px; border-bottom:1px solid #e0e0e0; text-align:left; font-size:13px; }
        #ws_interpretations .ws-interp-table th { background:#f6f7f7; font-weight:600; color:#1d2327; }
        #ws_interpretations .ws-interp-table .ws-favorable-yes { color:#1a7a1a; font-weight:600; }
        #ws_interpretations .ws-interp-table .ws-favorable-no  { color:#a00; }
        #ws_interpretations .ws-interp-empty { color:#666; font-style:italic; margin-bottom:12px; }
        #ws_interpretations .ws-interp-actions { display:flex; align-items:center; gap:10px; }
        #ws_interpretations .ws-interp-add-btn { text-decoration:none; }
        #ws_interpretations .ws-interp-add-btn[disabled] { opacity:.5; pointer-events:none; cursor:not-allowed; }
    </style>

    <?php if ( empty( $interpretations ) ) : ?>
        <p class="ws-interp-empty">No court interpretation records linked to this statute yet.</p>
    <?php else : ?>
        <table class="ws-interp-table">
            <thead>
                <tr>
                    <th>Case Name</th>
                    <th>Court</th>
                    <th>Year</th>
                    <th>Favorable?</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $interpretations as $interp_id ) :
                    $case_name   = get_the_title( $interp_id );
                    $court_key   = get_post_meta( $interp_id, 'ws_interp_court', true );
                    $year        = get_post_meta( $interp_id, 'ws_interp_year', true );
                    $favorable   = get_post_meta( $interp_id, 'ws_interp_favorable', true );
                    $court_label = ( $court_key && ! empty( $ws_court_matrix[ $court_key ] ) )
                        ? esc_html( $ws_court_matrix[ $court_key ]['short'] )
                        : esc_html( $court_key );
                    $edit_url    = get_edit_post_link( $interp_id );
                ?>
                <tr>
                    <td><?php echo esc_html( $case_name ?: '(untitled)' ); ?></td>
                    <td><?php echo $court_label; ?></td>
                    <td><?php echo esc_html( $year ?: '—' ); ?></td>
                    <td>
                        <?php if ( $favorable ) : ?>
                            <span class="ws-favorable-yes">Yes</span>
                        <?php else : ?>
                            <span class="ws-favorable-no">No</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?php echo esc_url( $edit_url ); ?>" target="_blank">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="ws-interp-actions">
        <?php if ( $is_draft ) : ?>
            <a class="button ws-interp-add-btn"
               disabled
               title="Save the statute first before adding interpretations.">
                + Add New Interpretation
            </a>
            <span style="color:#666;font-size:12px;">Save this statute first to enable this button.</span>
        <?php else : ?>
            <a class="button button-primary ws-interp-add-btn"
               href="<?php echo esc_url( $add_url ); ?>"
               target="_blank">
                + Add New Interpretation
            </a>
            <span style="color:#666;font-size:12px;">Opens in a new tab. Refresh this page after saving to update the list.</span>
        <?php endif; ?>
    </div>
    <?php
}
