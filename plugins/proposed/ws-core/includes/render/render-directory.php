<?php
/**
 * render-directory.php — Assist org directory render functions.
 *
 * Renders the [ws_assist_org_directory] shortcode output.
 * Loaded in Assembly Layer (! is_admin()) only.
 *
 * ws_render_directory_taxonomy_guide() is a Phase 2 stub — returns ''.
 * Do not remove it.
 *
 * @package WhistleblowerShield
 * @since   3.6.0
 * @version 3.10.2
 *
 * VERSION
 * -------
 * 3.6.0   Initial release.
 * 3.7.0   ws_render_directory_taxonomy_guide() Phase 2 stub added.
 * 3.7.1   ws_render_directory_card() — services rendered from taxonomy terms.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// ws_render_directory_page( $items )
//
// Top-level render for the standalone Directory page. Wraps the intro,
// listing, and (Phase 2) taxonomy guide. Called by [ws_assist_org_directory].
//
// @param  array  $items  Assist-org data arrays from ws_get_nationwide_assist_org_data().
// @return string  HTML output.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_directory_page( $items ) {
    ob_start();
    ?>
    <div class="ws-directory">

        <div class="ws-directory__intro">
            <p>The organizations listed below provide free or low-cost legal support,
            confidential guidance, and advocacy services to whistleblowers across
            the United States. All listings are reviewed by our editorial team.</p>
        </div>

        <div class="ws-directory__content">
            <?php if ( empty( $items ) ) : ?>
                <?php echo ws_render_directory_empty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <?php echo ws_render_directory_listing( $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_directory_listing( $items )
//
// Renders the full card grid from a ws_get_nationwide_assist_org_data()
// result set. Iterates items and calls ws_render_directory_card() for each.
//
// @param  array  $items  Non-empty assist-org data array.
// @return string  HTML output.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_directory_listing( $items ) {
    ob_start();
    ?>
    <div class="ws-directory__grid" role="list">
        <?php foreach ( $items as $org ) : ?>
            <?php echo ws_render_directory_card( $org ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_directory_card( $org )
//
// Renders a single assist-org card. Sections rendered:
//   - Name (linked to CPT permalink), type badge, cost badge, attorney/anon flags
//   - Description (plain text, wpautop applied)
//   - Services as inline tag pills
//   - Phone and email contact lines
//   - CTA buttons: "Get Help Now" (intake_url) and "Visit Website" (website_url)
//
// @param  array  $org  Single assist-org data array (from the query layer).
// @return string  HTML output.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_directory_card( $org ) {

    // Cost map inlined — avoids global variable scope issues.
    $cost_labels = [
        'free'            => 'Free',
        'pro-bono'        => 'Pro Bono',
        'sliding-scale'   => 'Sliding Scale',
        'contingency'     => 'Contingency Fee',
        'fee-for-service' => 'Fee for Service',
        'mixed'           => 'Mixed',
    ];

    // ── Resolve display values ─────────────────────────────────────────────

    $type_name = ( $org['type'] instanceof WP_Term ) ? $org['type']->name : '';
    $type_slug = ( $org['type'] instanceof WP_Term ) ? $org['type']->slug : '';

    $cost_slug  = is_array( $org['cost_model'] ) ? ( $org['cost_model'][0] ?? '' ) : '';
    $cost_label = $cost_labels[ $cost_slug ] ?? '';

    $services = ( is_array( $org['services'] ) && ! is_wp_error( $org['services'] ) )
        ? array_values( array_filter( $org['services'] ) )
        : [];

    $anchor_id = ! empty( $org['internal_id'] )
        ? 'aorg-' . sanitize_html_class( $org['internal_id'] )
        : 'aorg-' . absint( $org['id'] );

    // ── Build services string ──────────────────────────────────────────────
    //
    // "Services Provided: Foo, Bar & Baz"
    // Oxford-style: comma-separated, last item joined with &.

    $services_html = '';
    if ( ! empty( $services ) ) {
        $count = count( $services );
        if ( $count === 1 ) {
            $services_html = esc_html( $services[0] );
        } elseif ( $count === 2 ) {
            $services_html = esc_html( $services[0] ) . ' &amp; ' . esc_html( $services[1] );
        } else {
            $parts = array_map( 'esc_html', array_slice( $services, 0, -1 ) );
            $last  = esc_html( end( $services ) );
            $services_html = implode( ', ', $parts ) . ' &amp; ' . $last;
        }
    }

    ob_start();
    ?>
    <div class="ws-aorg-card" id="<?php echo esc_attr( $anchor_id ); ?>"
         role="listitem"
         data-type="<?php echo esc_attr( $type_slug ); ?>"
         data-cost="<?php echo esc_attr( $cost_slug ); ?>">

        <?php // ── Header: name + badge row ────────────────────────────────────── ?>
        <div class="ws-aorg-card__header">

            <h2 class="ws-aorg-card__name">

                <?php echo esc_html( $org['title'] ); ?>

            </h2>

            <div class="ws-aorg-card__badges"
                 aria-label="<?php echo esc_attr( $org['title'] ); ?> details">

                <?php if ( $type_name ) : ?>
                    <span class="ws-aorg-card__badge ws-aorg-card__badge--type"
                          data-slug="<?php echo esc_attr( $type_slug ); ?>">
                        <?php echo esc_html( $type_name ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $cost_label ) : ?>
                    <span class="ws-aorg-card__badge ws-aorg-card__badge--cost">
                        <?php echo esc_html( $cost_label ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( ! empty( $org['licensed_attorneys'] ) ) : ?>
                    <span class="ws-aorg-card__badge ws-aorg-card__badge--attorneys">
                        Licensed Attorneys
                    </span>
                <?php endif; ?>

                <?php if ( ! empty( $org['anonymous'] ) ) : ?>
                    <span class="ws-aorg-card__badge ws-aorg-card__badge--anon">
                        Accepts Anonymous
                    </span>
                <?php endif; ?>

            </div>

        </div>

        <?php // ── Description ─────────────────────────────────────────────────── ?>
        <?php if ( ! empty( $org['description'] ) ) : ?>
            <div class="ws-aorg-card__description">
                <?php echo wp_kses_post( wpautop( $org['description'] ) ); ?>
            </div>
        <?php endif; ?>

        <?php // ── Services ────────────────────────────────────────────────────── ?>
        <?php if ( $services_html ) : ?>
            <div class="ws-aorg-card__services">
                <strong>Services Provided:</strong> <?php echo $services_html; ?>
            </div>
        <?php endif; ?>

        <?php // ── Contact ─────────────────────────────────────────────────────── ?>
        <?php if ( ! empty( $org['phone'] ) || ! empty( $org['email'] ) ) : ?>
            <div class="ws-aorg-card__contact">
                <?php if ( ! empty( $org['phone'] ) ) : ?>
                    <span class="ws-aorg-card__phone">
                        <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $org['phone'] ) ); ?>"
                           aria-label="Call <?php echo esc_attr( $org['title'] ); ?>: <?php echo esc_attr( $org['phone'] ); ?>">
                            <?php echo esc_html( $org['phone'] ); ?>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if ( ! empty( $org['email'] ) ) : ?>
                    <span class="ws-aorg-card__email">
                        <a href="mailto:<?php echo esc_attr( sanitize_email( $org['email'] ) ); ?>"
                           aria-label="Email <?php echo esc_attr( $org['title'] ); ?>: <?php echo esc_attr( sanitize_email( $org['email'] ) ); ?>">
                            <?php echo esc_html( $org['email'] ); ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        // ── CTA buttons ──────────────────────────────────────────────────────
        // Visit Website first, Get Started second.
        // aria-label includes org name so each link is unique in the
        // accessibility tree. "(opens in new tab)" satisfies WCAG 2.1
        // SC 3.2.2 and SC 3.2.5 for _blank links.
        ?>
        <div class="ws-aorg-card__actions">
            <?php if ( ! empty( $org['website_url'] ) ) : ?>
                <a href="<?php echo esc_url( $org['website_url'] ); ?>"
                   class="ws-btn ws-btn--secondary ws-btn--sm"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Visit the <?php echo esc_attr( $org['title'] ); ?> website (opens in new tab)">
                    Visit Website
                    <span class="screen-reader-text">(opens in new tab)</span>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $org['intake_url'] ) ) : ?>
                <a href="<?php echo esc_url( $org['intake_url'] ); ?>"
                   class="ws-btn ws-btn--primary ws-btn--sm"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Get started with <?php echo esc_attr( $org['title'] ); ?> (opens in new tab)">
                    Get Started
                    <span class="screen-reader-text">(opens in new tab)</span>
                </a>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_directory_empty()
//
// Fallback rendered when no assist-orgs match the active filter combination.
//
// @return string  HTML output.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_directory_empty() {
    ob_start();
    ?>
    <div class="ws-directory__empty" role="status" aria-live="polite">
        <p>No organizations match the selected filters. Try broadening your
        search or <a href="<?php echo esc_url( remove_query_arg( [ 'aorg_type', 'aorg_sector', 'aorg_stage', 'aorg_cost' ] ) ); ?>"
                    aria-label="Clear all directory filters and show all organizations">clear all filters</a>
        to see all available organizations.</p>
    </div>
    <?php
    return ob_get_clean();
}


// ════════════════════════════════════════════════════════════════════════════
// ws_render_directory_taxonomy_guide()
//
// !! PHASE 2 PRIORITY — DO NOT REMOVE !!
//
// Renders the right-side taxonomy cascade filtering panel for the Directory.
// Presents taxonomy terms as plain-language filter questions (industry,
// disclosure type, etc.). Operates on the full nationwide assist-org dataset
// without a jurisdiction scope — contrast with ws_render_jx_filtered() in
// render-jurisdiction.php, which is scoped to a single jurisdiction.
//
// Implementation approach (Phase 2):
//   - PHP-only: panel submits a GET form; page re-renders with $_GET params.
//   - No AJAX required for core functionality; JS may be layered on for UX.
//   - attach_flag is not applicable to the directory dataset.
//   - Filtered URLs are bookmarkable and shareable.
//
// @return string  Empty string until Phase 2 implementation.
// ════════════════════════════════════════════════════════════════════════════

function ws_render_directory_taxonomy_guide() {
    // Phase 2: Taxonomy cascade panel for Directory — see block comment above.
    return '';
}
