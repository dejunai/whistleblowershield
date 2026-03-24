/**
 * ws-core-front.js
 *
 * Frontend JavaScript for ws-core shortcode output.
 * Enqueued by ws-core.php on all singular posts and pages.
 *
 * Version history:
 *   2.3.1 — Initial file. Extracted filter tab logic from section-renderer.php.
 *   2.4.0 — Added tooltip keyboard/screen reader accessibility and external
 *            link screen reader hints. Fixed early return that prevented
 *            accessibility code from running on non-index pages.
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

        if ( buttons.length && cards.length ) {

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

    }


    // ── Term Highlight Tooltip Accessibility ──────────────────────────────────
    //
    // Makes .ws-term-highlight tooltips accessible to keyboard and screen reader
    // users. The CSS in ws-core-front-general.css handles visual display on
    // :hover and :focus via ::after. This block handles the screen reader layer:
    //
    //   1. tabindex="0"       — makes the span keyboard-focusable
    //   2. role="tooltip"     — injects a hidden DOM element with the definition
    //   3. aria-describedby   — links the trigger span to that element so screen
    //                           readers announce the definition on focus
    //
    // The injected .ws-tooltip-content span is visually hidden (sr-only pattern)
    // and never shown on screen — the ::after pseudo-element handles display.

    var tooltipTriggers = document.querySelectorAll( '.ws-term-highlight[data-tooltip]' );

    tooltipTriggers.forEach( function ( el, index ) {

        var tooltipId   = 'ws-tt-' + index;
        var tooltipText = el.getAttribute( 'data-tooltip' );

        el.setAttribute( 'tabindex', '0' );
        el.setAttribute( 'aria-describedby', tooltipId );

        var popup = document.createElement( 'span' );
        popup.id        = tooltipId;
        popup.className = 'ws-tooltip-content';
        popup.setAttribute( 'role', 'tooltip' );
        popup.textContent = tooltipText;

        el.appendChild( popup );

    } );


    // ── External Link Screen Reader Hints ─────────────────────────────────────
    //
    // Screen readers do not announce that a link opens in a new tab unless told.
    // For every a[target="_blank"] on the page, a visually hidden span is
    // appended inside the link so assistive technology reads "(opens in new tab)"
    // after the link text.
    //
    // The check for .ws-sr-new-tab makes this idempotent — running the script
    // twice (e.g. in a cached/partial-hydration scenario) will not double-inject.

    var extLinks = document.querySelectorAll( 'a[target="_blank"]' );

    extLinks.forEach( function ( link ) {

        if ( link.querySelector( '.ws-sr-new-tab' ) ) { return; }

        var hint = document.createElement( 'span' );
        hint.className   = 'ws-sr-new-tab sr-only';
        hint.textContent = ' (opens in new tab)';

        link.appendChild( hint );

    } );

} )();
