<?php
/**
 * shortcodes.php
 *
 * Registers all ws-core shortcodes:
 *
 *   [ws_jurisdiction_header jurisdiction="california"]
 *       Renders the full jurisdiction header: flag, attribution, name,
 *       government portal link, governor/mayor link (conditional),
 *       and legal authority link (conditional).
 *
 *   [ws_jx_flag jurisdiction="california"]
 *       Renders only the flag image with Wikimedia attribution.
 *
 *   [ws_jx_summary jurisdiction="california"]
 *       Renders the full jurisdiction summary with footer metadata.
 *
 *   [ws_jx_review_status jurisdiction="california"]
 *       Renders human-reviewed and legal-review status badges.
 *
 *   [ws_legal_updates jurisdiction="california" count="5"]
 *       Renders recent legal updates for a jurisdiction (or site-wide).
 *       Queries the `ws-legal-update` CPT. Scoped by ws_legal_update_jurisdiction
 *       relationship field when jurisdiction parameter is provided.
 *
 *   [ws_jurisdiction_index]
 *       Renders the full jurisdictions index: type filter tabs + alphabetical
 *       grid of all published jurisdictions. Intended for the Jurisdictions page.
 *
 *   [ws_nla_disclaimer_notice]
 *       Renders the standard "not legal advice" notice box.
 *       Copy is centrally managed here. Styling via .ws-summary-notice
 *       in ws-core-front.css.
 *
 *   [ws_footer]
 *       Renders the site-wide footer block.
 *
 * Usage on jurisdiction pages:
 *   Place shortcodes in the WordPress block editor using a Shortcode block,
 *   or in a Custom HTML block.
 *
 * The `jurisdiction` parameter accepts the post slug (e.g., "california")
 * or the post ID (e.g., "42").
 *
 * v1.9.1 — Updated [ws_legal_updates] to use `ws_legal_update_*` ACF field names.
 * v1.9.2 — Updated [ws_legal_updates] post_type query to `ws-legal-update`.
 */

defined( 'ABSPATH' ) || exit;

// ── Helper: resolve jurisdiction post from slug or ID ─────────────────────────

/**
 * @param  string|int $jurisdiction  Slug or post ID.
 * @return WP_Post|null
 */
function ws_get_jurisdiction_post( $jurisdiction ) {
    if ( empty( $jurisdiction ) ) {
        return null;
    }
    if ( is_numeric( $jurisdiction ) ) {
        $post = get_post( (int) $jurisdiction );
        return ( $post && $post->post_type === 'jurisdiction' ) ? $post : null;
    }
    $posts = get_posts( [
        'post_type'      => 'jurisdiction',
        'name'           => sanitize_title( $jurisdiction ),
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ] );
    return ! empty( $posts ) ? $posts[0] : null;
}

// ── Helper: get related summary post ─────────────────────────────────────────

function ws_get_related_summary( $jurisdiction_post_id ) {
    $related = get_field( 'ws_related_summary', $jurisdiction_post_id );
    if ( ! empty( $related ) && is_array( $related ) ) {
        return $related[0]; // Relationship field returns array
    }
    return null;
}

// ── [ws_nla_disclaimer_notice] ────────────────────────────────────────────────────
//
// Renders the standard "not legal advice" notice box shown at the top
// of every jurisdiction summary.
//
// To update the notice text site-wide: edit $notice_text below and save.
// The change propagates to all jurisdiction pages automatically.
//
// Styling is handled entirely by .ws-nla-notice in ws-core-front.css.
// Do not add inline styles here.

add_shortcode( 'ws_nla_disclaimer_notice', 'ws_shortcode_nla_disclaimer_notice' );
function ws_shortcode_nla_disclaimer_notice() {

    $notice_text = 'This page is provided for informational purposes only '
        . 'and does not constitute legal advice. The "Whistleblower Shield" '
        . 'is a database of legal information, not a law firm. Users should '
        . 'consult with a qualified legal professional regarding the specifics '
        . 'of their situation before initiating any formal disclosure or legal action.';

    return '<div class="ws-nla-disclaimer-notice">'
        . '<strong>NOTICE:</strong> '
        . esc_html( $notice_text )
        . '</div>';
}

// ── [ws_jurisdiction_header] ──────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_header', 'ws_shortcode_jurisdiction_header' );
function ws_shortcode_jurisdiction_header( $atts ) {
    $atts = shortcode_atts( [ 'jurisdiction' => '' ], $atts, 'ws_jurisdiction_header' );

    $jpost = ws_get_jurisdiction_post( $atts['jurisdiction'] );
    if ( ! $jpost ) {
        return '<!-- ws_jurisdiction_header: jurisdiction not found -->';
    }

    $pid  = $jpost->ID;
    $type = get_field( 'ws_jurisdiction_type', $pid );
    $name = get_field( 'ws_jurisdiction_name', $pid ) ?: get_the_title( $pid );

    // Flag
    $flag_array  = get_field( 'ws_jurisdiction_flag', $pid );
    $flag_url    = $flag_array ? esc_url( $flag_array['url'] ) : '';
    $flag_alt    = $flag_array ? esc_attr( $flag_array['alt'] ?: $name . ' flag' ) : '';
    $attrib_text = get_field( 'ws_jx_flag_attribution', $pid );
    $attrib_url  = get_field( 'ws_jx_flag_attribution_url', $pid );
    $license     = get_field( 'ws_jx_flag_license', $pid );

    // Government URLs
    $portal_url     = get_field( 'ws_gov_portal_url', $pid );
    $portal_label   = get_field( 'ws_gov_portal_label', $pid ) ?: 'Official Government Portal';
    $head_url   	= get_field( 'ws_head_of_government_url', $pid );
	//
	function get_government_label( $pid ) {
    $choices_gl = [
        'governor' => 'Office of the Governor',
        'mayor'    => 'Office of the Mayor',
    ];
    $key_gl        = get_field( 'ws_head_of_government_label', $pid ) ?: 'governor';
    return $choices_gl[$key_gl] ?? 'Office of the Governor';
	}
	$head_label     = get_government_label( $pid );
	//
	$legal_url      = get_field( 'ws_legal_authority_url', $pid );
    //
	function get_legal_authority_label( $pid ) {
    $choices_lal = [
		'attorney'   => 'Office of the Attorney General',
		'inspector'  => 'D.C. Office of the Inspector General',
		'secretary'  => 'Office of the Secretary of Justice',
		'special'    => 'U.S. Office of Special Counsel',
	];
    $key_lal         = get_field( 'ws_legal_authority_label', $pid ) ?: 'attorney';
    return $choices_lal[$key_lal] ?? 'Office of the Attorney General';
	}
	$legal_label    = get_legal_authority_label( $pid );
	//
	

    ob_start();
    ?>

	<h1 class="ws-jurisdiction-title"><?php echo esc_html( $name ); ?></h1>
    <div class="ws-jurisdiction-header">
        <div class="ws-jurisdiction-flag-row">

        <?php if ( $flag_url ) : ?>
        <div class="ws-jurisdiction-flag">
            <img src="<?php echo $flag_url; ?>"
                 alt="<?php echo $flag_alt; ?>"
                 class="ws-jx-flag-img" />
            <?php if ( $attrib_text ) : ?>
            <div class="ws-jx-flag-attribution">
                <?php if ( $attrib_url ) : ?>
                    <a href="<?php echo esc_url( $attrib_url ); ?>"
                       target="_blank" rel="noopener noreferrer" class="ws-term-highlight" 
                        data-tooltip="<?php echo esc_html( $attrib_text )?> &mdash; Click to WikiMedia Commons">
						Attribution
                    </a>
                <?php else : ?>
					<span class="ws-term-highlight"
                    data-tooltip="<?php echo esc_html( $attrib_text )?>">
					Attribution</span>
                <?php endif; ?>
                <?php if ( $license ) : ?>
                    &mdash; <?php echo esc_html( $license ); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div><!-- /.ws-jurisdiction-flag-row -->
        <div class="ws-jurisdiction-title-block">

            <?php
            // Dynamic panel label based on jurisdiction type
            $offices_label = 'Official Offices';
            if ( $type === 'state' )     $offices_label = 'State Leadership Offices';
            if ( $type === 'territory' ) $offices_label = 'Territory Leadership Offices';
            if ( $type === 'district' )  $offices_label = 'District Leadership Offices';
            if ( $type === 'federal' )   $offices_label = 'Federal Offices';
            ?>

            <div class="ws-jx-gov-offices-box">
                <p class="ws-jx-gov-offices-label"><?php echo esc_html( $offices_label ); ?></p>
                <ul class="ws-jx-gov-links">

                    <?php if ( $portal_url ) : ?>
                    <li class="ws-jx-gov-link ws-jx-gov-portal">
                        <a href="<?php echo esc_url( $portal_url ); ?>"
                           target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $portal_label ); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // Head of government
					if ( $head_url ) : 
						// Determine CSS class based on the jurisdiction type (district vs others)
						$gov_class = ( $type === 'district' ) ? 'ws-head-mayor' : 'ws-head-governor';
						?>
						<li class="ws-jx-gov-link <?php echo esc_attr( $gov_class ); ?>">
							<a href="<?php echo esc_url( $head_url ); ?>" 
							   target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $head_label ); ?>
							</a>
						</li>
					<?php endif; ?>

                     <?php
                    // Legal Authority
					if ( $legal_url ) : ?>
						<li class="ws-jx-gov-link ws-jx-legal-authority">
                        <a href="<?php echo esc_url( $legal_url ); ?>"
                           target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html( $legal_label ); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </div>

        </div>



    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_jx_flag] ─────────────────────────────────────────────────────────────────

add_shortcode( 'ws_jx_flag', 'ws_shortcode_jx_flag' );
function ws_shortcode_jx_flag( $atts ) {
    $atts = shortcode_atts( [ 'jurisdiction' => '' ], $atts, 'ws_jx_flag' );

    $jpost = ws_get_jurisdiction_post( $atts['jurisdiction'] );
    if ( ! $jpost ) {
        return '<!-- ws_jx_flag: jurisdiction not found -->';
    }

    $pid        = $jpost->ID;
    $name       = get_field( 'ws_jurisdiction_name', $pid ) ?: get_the_title( $pid );
    $flag_array = get_field( 'ws_jurisdiction_flag', $pid );

    if ( ! $flag_array ) {
        return '';
    }

    $flag_url    = esc_url( $flag_array['url'] );
    $flag_alt    = esc_attr( $flag_array['alt'] ?: $name . ' flag' );
    $attrib_text = get_field( 'ws_jx_flag_attribution', $pid );
    $attrib_url  = get_field( 'ws_jx_flag_attribution_url', $pid );
    $license     = get_field( 'ws_jx_flag_license', $pid );

    ob_start();
    ?>
    <div class="ws-jx-flag-block">
        <img src="<?php echo $flag_url; ?>"
             alt="<?php echo $flag_alt; ?>"
             class="ws-jx-flag-img" />
        <?php if ( $attrib_text ) : ?>
        <p class="ws-jx-flag-attribution">
            <?php if ( $attrib_url ) : ?>
                <a href="<?php echo esc_url( $attrib_url ); ?>"
					target="_blank" rel="noopener noreferrer" class="ws-term-highlight" 
					data-tooltip="<?php esc_html( $attrib_text )?>">
					Attribution
                    </a>
            <?php else : ?>
				<span class="ws-term-highlight"
				data-tooltip="<?php esc_html( $attrib_text )?>">
				Attribution</span>
            <?php endif; ?>
            <?php if ( $license ) : ?>
                &mdash; <?php echo esc_html( $license ); ?>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_jurisdiction_summary] ──────────────────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_summary', 'ws_shortcode_jurisdiction_summary' );
function ws_shortcode_jurisdiction_summary( $atts ) {
    $atts = shortcode_atts( [ 'jurisdiction' => '' ], $atts, 'ws_jurisdiction_summary' );

    $jpost = ws_get_jurisdiction_post( $atts['jurisdiction'] );
    if ( ! $jpost ) {
        return '<!-- ws_jurisdiction_summary: jurisdiction not found -->';
    }

    $summary_post = ws_get_related_summary( $jpost->ID );
    if ( ! $summary_post ) {
        return '<!-- ws_jurisdiction_summary: no related summary found for this jurisdiction -->';
    }

    $sid = $summary_post->ID;

    $summary_content = get_field( 'ws_jurisdiction_summary', $sid );
    $sources         = get_field( 'ws_jx_summary_sources', $sid );
    $date_created    = get_field( 'ws_jx_sum_date_created', $sid );
    $last_reviewed   = get_field( 'ws_jx_sum_last_reviewed', $sid );
    $author_data     = get_field( 'ws_jx_sum_author', $sid );
    $human_reviewed  = get_field( 'ws_jx_sum_human_reviewed', $sid );
    $legal_reviewed  = get_field( 'ws_jx_sum_legal_review_completed', $sid );
    $legal_reviewer  = get_field( 'ws_jx_sum_legal_reviewer', $sid );

    // Format author display name
    $author_name = '';
    if ( is_array( $author_data ) && ! empty( $author_data['display_name'] ) ) {
        $author_name = esc_html( $author_data['display_name'] );
    } elseif ( is_numeric( $author_data ) ) {
        $user = get_userdata( (int) $author_data );
        if ( $user ) {
            $author_name = esc_html( $user->display_name );
        }
    }

    // Format dates
    $fmt_created  = $date_created  ? date( 'F j, Y', strtotime( $date_created ) )  : '';
    $fmt_reviewed = $last_reviewed ? date( 'F j, Y', strtotime( $last_reviewed ) ) : '';

    ob_start();
    ?>
    <div class="ws-jx-summary-block">

        <div class="ws-jx-summary-content">
            <?php echo wp_kses_post( $summary_content ); ?>
        </div>

        <div class="ws-jx-summary-footer">

            <?php if ( $author_name ) : ?>
            <p class="ws-jx-summary-author">
                <strong>Author:</strong> <?php echo $author_name; ?>
            </p>
            <?php endif; ?>

            <?php if ( $fmt_created ) : ?>
            <p class="ws-jx-summary-date-created">
                <strong>Date Created:</strong> <?php echo esc_html( $fmt_created ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $fmt_reviewed ) : ?>
            <p class="ws-jx-summary-last-reviewed">
                <strong>Last Reviewed:</strong> <?php echo esc_html( $fmt_reviewed ); ?>
            </p>
            <?php endif; ?>

            <div class="ws-review-badges">
                <?php if ( $human_reviewed ) : ?>
                    <span class="ws-badge ws-badge-reviewed">&#10003; Human Reviewed</span>
                <?php else : ?>
                    <span class="ws-badge ws-badge-pending">&#9679; Pending Human Review</span>
                <?php endif; ?>

                <?php if ( $legal_reviewed ) : ?>
                    <span class="ws-badge ws-badge-legal-reviewed">
                        &#10003; Legally Reviewed
                        <?php if ( $legal_reviewer ) : ?>
                            &mdash; <?php echo esc_html( $legal_reviewer ); ?>
                        <?php endif; ?>
                    </span>
                <?php else : ?>
                    <span class="ws-badge ws-badge-pending">&#9679; Pending Legal Review</span>
                <?php endif; ?>
            </div>

            <?php if ( $sources ) : ?>
            <div class="ws-jx-summary-sources">
                <strong>Sources &amp; Citations:</strong>
                <pre class="ws-jx-sources-text"><?php echo esc_html( $sources ); ?></pre>
            </div>
            <?php endif; ?>

        </div>

    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_review_status] ────────────────────────────────────────────────────────

add_shortcode( 'ws_review_status', 'ws_shortcode_review_status' );
function ws_shortcode_review_status( $atts ) {
    $atts = shortcode_atts( [ 'jurisdiction' => '' ], $atts, 'ws_review_status' );

    $jpost = ws_get_jurisdiction_post( $atts['jurisdiction'] );
    if ( ! $jpost ) {
        return '';
    }

    $summary_post = ws_get_related_summary( $jpost->ID );
    if ( ! $summary_post ) {
        return '';
    }

    $sid            = $summary_post->ID;
    $human_reviewed = get_field( 'ws_jx_sum_human_reviewed', $sid );
    $legal_reviewed = get_field( 'ws_jx_sum_legal_review_completed', $sid );
    $legal_reviewer = get_field( 'ws_jx_sum_legal_reviewer', $sid );

    ob_start();
    ?>
    <div class="ws-review-status">
        <?php if ( $human_reviewed ) : ?>
            <span class="ws-badge ws-badge-reviewed">&#10003; Human Reviewed</span>
        <?php else : ?>
            <span class="ws-badge ws-badge-pending">&#9679; Pending Human Review</span>
        <?php endif; ?>

        <?php if ( $legal_reviewed ) : ?>
            <span class="ws-badge ws-badge-legal-reviewed">
                &#10003; Legally Reviewed
                <?php if ( $legal_reviewer ) : ?>
                    &mdash; <?php echo esc_html( $legal_reviewer ); ?>
                <?php endif; ?>
            </span>
        <?php else : ?>
            <span class="ws-badge ws-badge-pending">&#9679; Pending Legal Review</span>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_legal_updates] ────────────────────────────────────────────────────────
//
// Renders recent legal updates for a specified jurisdiction, or site-wide
// if no jurisdiction parameter is given.
//
// Queries the `ws-legal-update` CPT. When a jurisdiction is specified, filters
// by the ws_legal_update_jurisdiction relationship field.
//
// Usage:
//   [ws_legal_updates jurisdiction="california" count="5"]
//   [ws_legal_updates count="10"]   ← site-wide (Legal Updates page)

add_shortcode( 'ws_legal_updates', 'ws_shortcode_legal_updates' );
function ws_shortcode_legal_updates( $atts ) {
    $atts = shortcode_atts( [
        'jurisdiction' => '',
        'count'        => 5,
    ], $atts, 'ws_legal_updates' );

    $count = max( 1, (int) $atts['count'] );

    $meta_query = [];
    if ( ! empty( $atts['jurisdiction'] ) ) {
        $jpost = ws_get_jurisdiction_post( $atts['jurisdiction'] );
        if ( $jpost ) {
            $meta_query = [ [
                'key'     => 'ws_legal_update_jurisdiction',
                'value'   => '"' . $jpost->ID . '"',
                'compare' => 'LIKE',
            ] ];
        }
    }

    $query_args = [
        'post_type'      => 'ws-legal-update',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $updates = get_posts( $query_args );

    if ( empty( $updates ) ) {
        return '<p class="ws-no-legal-updates">No legal updates found.</p>';
    }

    ob_start();
    ?>
    <div class="ws-legal-updates">
        <?php foreach ( $updates as $update ) : ?>
        <?php
            $law_name       = get_field( 'ws_legal_update_law_name',       $update->ID );
            $effective_date = get_field( 'ws_legal_update_effective_date', $update->ID );
            $source_url     = get_field( 'ws_legal_update_source_url',     $update->ID );
            $summary_html   = get_field( 'ws_legal_update_summary',        $update->ID );
            $fmt_effective  = $effective_date ? date( 'F j, Y', strtotime( $effective_date ) ) : '';
            $post_date      = get_the_date( 'F j, Y', $update->ID );
        ?>
        <div class="ws-legal-update-item">
            <h3 class="ws-legal-update-title">
                <?php if ( $source_url ) : ?>
                    <a href="<?php echo esc_url( $source_url ); ?>"
                       target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html( get_the_title( $update->ID ) ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( get_the_title( $update->ID ) ); ?>
                <?php endif; ?>
            </h3>

            <?php if ( $law_name ) : ?>
            <p class="ws-legal-update-law"><strong>Law / Statute:</strong>
                <?php echo esc_html( $law_name ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $fmt_effective ) : ?>
            <p class="ws-legal-update-effective">
                <strong>Effective:</strong> <?php echo esc_html( $fmt_effective ); ?>
            </p>
            <?php endif; ?>

            <p class="ws-legal-update-posted">
                <strong>Posted:</strong> <?php echo esc_html( $post_date ); ?>
            </p>

            <?php if ( $summary_html ) : ?>
            <div class="ws-legal-update-summary">
                <?php echo wp_kses_post( $summary_html ); ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_footer] ───────────────────────────────────────────────────────────────

add_shortcode( 'ws_footer', 'ws_shortcode_footer' );
function ws_shortcode_footer( $atts ) {

    $current_year = date( 'Y' );

    $policy_links = [
        'Privacy Policy'     => '/privacy-policy/',
        'Disclaimer'         => '/disclaimer/',
        'Corrections Policy' => '/corrections-policy/',
        'Editorial Policy'   => '/editorial-policy/',
    ];

    ob_start();
    ?>
    <div class="ws-footer-block">

        <p class="ws-footer-mission">
            A nonpartisan educational reference of U.S. whistleblower protections &mdash; state by state and federal.
        </p>

        <nav class="ws-footer-policy-links" aria-label="Site policies">
            <?php foreach ( $policy_links as $label => $slug ) : ?>
                <a href="<?php echo esc_url( home_url( $slug ) ); ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="ws-footer-contact">
            Contact: <a href="mailto:admin@whistleblowershield.org">admin@whistleblowershield.org</a>
        </p>

        <p class="ws-footer-copyright">
            &copy; <?php echo esc_html( $current_year ); ?> WhistleblowerShield.org &mdash; All rights reserved.
        </p>

    </div>
    <?php
    return ob_get_clean();
}

// ── [ws_jurisdiction_index] ───────────────────────────────────────────────────

add_shortcode( 'ws_jurisdiction_index', 'ws_shortcode_jurisdiction_index' );
function ws_shortcode_jurisdiction_index( $atts ) {

    $jurisdictions = get_posts( [
        'post_type'      => 'jurisdiction',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    if ( empty( $jurisdictions ) ) {
        return '<p class="ws-jx-index-empty">No jurisdictions have been published yet.</p>';
    }

    $items = [];
    foreach ( $jurisdictions as $post ) {
        $type = get_field( 'ws_jurisdiction_type', $post->ID ) ?: 'state';
        $items[] = [
            'id'    => $post->ID,
            'title' => get_the_title( $post ),
            'url'   => get_permalink( $post ),
            'type'  => $type,
        ];
    }

    $types = [
        'all'       => 'All Jurisdictions',
        'state'     => 'U.S. States',
        'federal'   => 'Federal',
        'territory' => 'U.S. Territories',
        'district'  => 'District of Columbia',
    ];

    ob_start();
    ?>
    <div class="ws-jurisdiction-index" id="ws-jurisdiction-index">

        <div class="ws-jx-index-filter" role="tablist" aria-label="Filter jurisdictions by type">
            <?php foreach ( $types as $value => $label ) : ?>
            <button
                class="ws-jx-filter-tab<?php echo $value === 'all' ? ' ws-jx-filter-active' : ''; ?>"
                data-filter="<?php echo esc_attr( $value ); ?>"
                role="tab"
                aria-selected="<?php echo $value === 'all' ? 'true' : 'false'; ?>"
                type="button">
                <?php echo esc_html( $label ); ?>
                <span class="ws-jx-filter-count" data-count-for="<?php echo esc_attr( $value ); ?>"></span>
            </button>
            <?php endforeach; ?>
        </div>

        <ul class="ws-jx-index-grid" role="list">
            <?php foreach ( $items as $item ) : ?>
            <li class="ws-jx-index-card"
                data-type="<?php echo esc_attr( $item['type'] ); ?>">
                <a href="<?php echo esc_url( $item['url'] ); ?>">
                    <?php echo esc_html( $item['title'] ); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <p class="ws-jx-index-no-results" style="display:none;">
            No jurisdictions found for this filter.
        </p>

    </div>

    <script>
    ( function() {
        const index   = document.getElementById( 'ws-jurisdiction-index' );
        if ( ! index ) return;

        const tabs      = index.querySelectorAll( '.ws-jx-filter-tab' );
        const cards     = index.querySelectorAll( '.ws-jx-index-card' );
        const noResults = index.querySelector( '.ws-jx-index-no-results' );

        tabs.forEach( function( tab ) {
            const filter  = tab.dataset.filter;
            const countEl = tab.querySelector( '.ws-jx-filter-count' );
            if ( ! countEl ) return;
            if ( filter === 'all' ) {
                countEl.textContent = '(' + cards.length + ')';
            } else {
                const n = index.querySelectorAll( '.ws-jx-index-card[data-type="' + filter + '"]' ).length;
                if ( n > 0 ) {
                    countEl.textContent = '(' + n + ')';
                } else {
                    tab.style.display = 'none';
                }
            }
        } );

        tabs.forEach( function( tab ) {
            tab.addEventListener( 'click', function() {
                const filter = tab.dataset.filter;

                tabs.forEach( function( t ) {
                    t.classList.remove( 'ws-jx-filter-active' );
                    t.setAttribute( 'aria-selected', 'false' );
                } );
                tab.classList.add( 'ws-jx-filter-active' );
                tab.setAttribute( 'aria-selected', 'true' );

                let visible = 0;
                cards.forEach( function( card ) {
                    const match = filter === 'all' || card.dataset.type === filter;
                    card.style.display = match ? '' : 'none';
                    if ( match ) visible++;
                } );

                noResults.style.display = visible === 0 ? '' : 'none';
            } );
        } );
    } )();
    </script>
    <?php
    return ob_get_clean();
}
