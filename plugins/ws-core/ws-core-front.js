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
 *   3.8.0 — No JS changes; version bumped to match plugin.
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
    //
    // HTML contract: filter buttons are rendered as native <button> elements
    // by ws_render_jurisdiction_index() in render-general.php. The selector
    // '.ws-jx-filter-btn' works on any element, but click handling and
    // classList depend on the rendered markup using <button> tags.

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


    // ── Reference Page — Back Link (opener focus) ────────────────────────────
    //
    // When a reference page is opened in a new window (window.opener exists),
    // clicking the back link focuses the opener tab and closes this window
    // instead of navigating. Falls back to normal href navigation otherwise.

    var backLink = document.querySelector( '.ws-reference-page__back-link' );

    if ( backLink && window.opener && ! window.opener.closed ) {
        backLink.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            window.opener.focus();
            window.close();
        } );
    }


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
