/**
 * ws-core-front.js
 *
 * Frontend JavaScript for ws-core shortcode output.
 * Enqueued by ws-core.php on all public-facing pages.
 *
 * Version history:
 *   2.3.1 — Initial file. Extracted filter tab logic from section-renderer.php.
 */

( function () {

    'use strict';

    // ── Jurisdiction Index Filter Tabs ────────────────────────────────────────
    //
    // Filters .ws-jx-card elements by their data-type attribute when a
    // .ws-jx-filter-btn is clicked. The 'all' filter shows every card.
    //
    // Expects:
    //   .ws-jx-filter-btn[data-filter]  — filter buttons in .ws-jx-filter-nav
    //   .ws-jx-card[data-type]          — jurisdiction cards in .ws-jx-grid

    var filterNav = document.querySelector( '.ws-jx-filter-nav' );

    if ( filterNav ) {

        var buttons = filterNav.querySelectorAll( '.ws-jx-filter-btn' );
        var cards   = document.querySelectorAll( '.ws-jx-card' );

        buttons.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {

                var filter = btn.dataset.filter;

                // Update active state
                buttons.forEach( function ( b ) {
                    b.classList.remove( 'ws-active' );
                } );
                btn.classList.add( 'ws-active' );

                // Show/hide cards
                cards.forEach( function ( card ) {
                    var match = ( filter === 'all' || card.dataset.type === filter );
                    card.style.display = match ? 'flex' : 'none';
                } );

            } );
        } );

    }

} )();
