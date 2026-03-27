<?php
/**
 * admin-citation-metabox.php
 *
 * Adds a "Case Law & Citations" meta box to the jx-statute edit screen.
 *
 * PURPOSE
 * -------
 * Displays all jx-citation records linked to the current statute via
 * ws_jx_citation_statute_ids, with a direct "Add New Citation" button
 * that opens the creation form pre-populated with the statute relationship,
 * jurisdiction taxonomy, and post title.
 *
 * Unlike the interpretation metabox, this metabox is not jurisdiction-gated —
 * it appears on all jx-statute records regardless of jurisdiction.
 *
 * WORKFLOW
 * --------
 * 1. Editor saves a jx-statute post.
 * 2. The meta box appears below the ACF field groups.
 * 3. Existing linked citations are listed: official name, type, attached?, Edit.
 * 4. "Add New Citation" opens:
 *    post-new.php?post_type=jx-citation
 *      &statute_id={ID}
 *      &tax_input[ws_jurisdiction][]={term_id}
 *      &post_title=Citation — {statute title}
 *    in a new browser tab.
 * 5. The new citation screen has the statute relationship pre-selected (via
 *    acf/load_value in acf-jx-citations.php) and the ws_jurisdiction taxonomy
 *    pre-assigned (via tax_input URL parameter, handled by WordPress core).
 * 6. After saving, the editor closes the tab and refreshes the statute screen.
 *
 * NOTE: The button is disabled (with tooltip) on auto-draft statutes because
 * the statute_id must reference a saved post to be meaningful.
 *
 * @package    WhistleblowerShield
 * @since      3.6.0
 * @version 3.10.0
 * @author     Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.6.0  Initial release. Mirrors admin-interpretation-metabox.php pattern.
 *        No jurisdiction gate. Pre-assigns ws_jurisdiction via tax_input URL
 *        param (WordPress core); pre-selects statute via acf/load_value hook.
 *        Metabox reads ws_jx_statute_citation_ids (reverse index maintained
 *        by admin-hooks.php) — simple post__in query, no meta_query JOIN.
 */

defined( 'ABSPATH' ) || exit;


add_action( 'add_meta_boxes', 'ws_register_citation_metabox' );

/**
 * Registers the meta box on the jx-statute edit screen.
 */
function ws_register_citation_metabox() {
    add_meta_box(
        'ws_citations',
        'Case Law & Citations',
        'ws_render_citation_metabox',
        'jx-statute',
        'normal',
        'default'
    );
}


/**
 * Renders the Case Law & Citations meta box.
 *
 * Appears on all jx-statute records regardless of jurisdiction.
 *
 * @param WP_Post $post  The current jx-statute post object.
 */
function ws_render_citation_metabox( $post ) {

    $is_draft = ( $post->post_status === 'auto-draft' );

    // ── Build "Add New Citation" URL ──────────────────────────────────────
    //
    // statute_id         — read by acf/load_value in acf-jx-citations.php to
    //                      pre-select ws_jx_citation_statute_ids.
    // tax_input[...][]   — WordPress core pre-assigns the ws_jurisdiction taxonomy
    //                      term(s) on the new post screen without any ACF hook.
    //                      All terms from the statute are forwarded; a statute
    //                      may carry more than one jurisdiction term.
    // post_title         — WordPress core pre-fills the title field.

    $terms   = wp_get_post_terms( $post->ID, WS_JURISDICTION_TAXONOMY );
    $add_url = admin_url( 'post-new.php?post_type=jx-citation&statute_id=' . $post->ID );

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $add_url .= '&tax_input[' . WS_JURISDICTION_TAXONOMY . '][]=' . $term->term_id;
        }
    }

    $statute_title = get_the_title( $post );
    if ( $statute_title ) {
        $add_url .= '&post_title=' . rawurlencode( 'Citation — ' . $statute_title );
    }

    // ── Fetch linked citations ────────────────────────────────────────────
    //
    // ws_jx_statute_citation_ids is the reverse index maintained by
    // ws_rebuild_jx_statute_citation_index() in admin-hooks.php. Reading it
    // here is a single get_post_meta() call; the post__in query that follows
    // is a simple WHERE ID IN (...) with no meta JOIN.

    $citation_ids = array_filter( array_map( 'intval', (array) get_post_meta( $post->ID, 'ws_jx_statute_citation_ids', true ) ) );

    $citations = empty( $citation_ids ) ? [] : get_posts( [
        'post_type'      => 'jx-citation',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post__in'       => $citation_ids,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    $type_labels = [
        'case_law'   => 'Case Law',
        'statute'    => 'Statute',
        'regulatory' => 'Regulatory',
        'secondary'  => 'Secondary Source',
    ];

    // ── Render ────────────────────────────────────────────────────────────
    ?>
    <style>
        #ws_citations .ws-cite-table { width:100%; border-collapse:collapse; margin-bottom:12px; }
        #ws_citations .ws-cite-table th,
        #ws_citations .ws-cite-table td { padding:6px 10px; border-bottom:1px solid #e0e0e0; text-align:left; font-size:13px; }
        #ws_citations .ws-cite-table th { background:#f6f7f7; font-weight:600; color:#1d2327; }
        #ws_citations .ws-cite-table .ws-attached-yes { color:#1a7a1a; font-weight:600; }
        #ws_citations .ws-cite-table .ws-attached-no  { color:#999; }
        #ws_citations .ws-cite-empty  { color:#666; font-style:italic; margin-bottom:12px; }
        #ws_citations .ws-cite-actions { display:flex; align-items:center; gap:10px; }
        #ws_citations .ws-cite-add-btn { text-decoration:none; }
        #ws_citations .ws-cite-add-btn[disabled] { opacity:.5; pointer-events:none; cursor:not-allowed; }
    </style>

    <?php if ( empty( $citations ) ) : ?>
        <p class="ws-cite-empty">No citation records linked to this statute yet.</p>
    <?php else : ?>
        <table class="ws-cite-table">
            <thead>
                <tr>
                    <th>Official Name</th>
                    <th>Type</th>
                    <th>Attached?</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $citations as $cite_id ) :
                    $official_name = get_post_meta( $cite_id, 'ws_jx_citation_official_name', true );
                    $type_key      = get_post_meta( $cite_id, 'ws_jx_citation_type', true );
                    $attached      = get_post_meta( $cite_id, 'ws_attach_flag', true );
                    $type_label    = $type_labels[ $type_key ] ?? esc_html( $type_key );
                    $edit_url      = get_edit_post_link( $cite_id );
                ?>
                <tr>
                    <td><?php echo esc_html( $official_name ?: get_the_title( $cite_id ) ?: '(untitled)' ); ?></td>
                    <td><?php echo esc_html( $type_label ); ?></td>
                    <td>
                        <?php if ( $attached ) : ?>
                            <span class="ws-attached-yes">Yes</span>
                        <?php else : ?>
                            <span class="ws-attached-no">No</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?php echo esc_url( $edit_url ); ?>" target="_blank">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="ws-cite-actions">
        <?php if ( $is_draft ) : ?>
            <a class="button ws-cite-add-btn"
               disabled
               title="Save the statute first before adding citations.">
                + Add New Citation
            </a>
            <span style="color:#666;font-size:12px;">Save this statute first to enable this button.</span>
        <?php else : ?>
            <a class="button button-primary ws-cite-add-btn"
               href="<?php echo esc_url( $add_url ); ?>"
               target="_blank">
                + Add New Citation
            </a>
            <span style="color:#666;font-size:12px;">Opens in a new tab. Refresh this page after saving to update the list.</span>
        <?php endif; ?>
    </div>
    <?php
}
