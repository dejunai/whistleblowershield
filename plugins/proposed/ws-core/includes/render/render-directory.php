<?php
/**
 * render-directory.php
 *
 * Render Layer — Directory Page
 *
 * PURPOSE
 * -------
 * Provides rendering functions for the WhistleblowerShield Directory —
 * a dedicated public entry point for end-users who need immediate connection
 * to whistleblower support organizations (assist-orgs).
 *
 * The Directory is internally identified as "Directory" and is surfaced to
 * end-users via [Get Help] and [Get Help Now] calls-to-action placed on the
 * homepage and throughout the site. It is not the same as the jurisdiction
 * index — it is a help-seeking entry point, not a browsing interface.
 *
 *
 * INTENDED ARCHITECTURE
 * ---------------------
 *
 * The Directory operates in two modes:
 *
 *   1. Standalone Directory Page
 *      A dynamically maintained listing of assist-org records from the
 *      ws-assist-org CPT. Rendered from ws_get_assist_org_data() via the
 *      query layer. Includes a right-side taxonomy guide that allows
 *      end-users to refine results by disclosure type, industry sector,
 *      service type, cost model, and other relevant taxonomies.
 *
 *   2. Prefiltered Entry (deep-link from jurisdiction page)
 *      When an end-user clicks [Connect for Help] on a specific assist-org
 *      card within a jurisdiction page, they are forwarded to a prefiltered
 *      version of the Directory scoped to that assist-org and pre-applied
 *      taxonomy filters. The URL carries the filter state as query params.
 *      The targeted assist-org entry is highlighted or anchored to.
 *
 *
 * JURISDICTION PAGE INTEGRATION
 * ------------------------------
 *
 * The jurisdiction page will eventually include a right-side panel that
 * guides end-users through a taxonomy-based filtering experience. Filters
 * are presented as plain-language questions rather than technical taxonomy
 * labels, for example:
 *
 *      "What kind of industry are you in?"
 *      "What kind of wrongdoing do you need to report?"
 *      "Are you looking for free legal help?"
 *
 * As the end-user answers, selected taxonomy terms cascade and filter the
 * jurisdiction page content — without a page refresh. Relevant assist-org
 * cards surface within the jurisdiction view as filters are applied. Each
 * assist-org card carries a [Connect for Help] button that deep-links to
 * the Directory with the active filter state pre-applied.
 *
 * The taxonomy guide on the standalone Directory page uses different logic:
 * it allows the end-user to independently refine results without a
 * jurisdiction context, starting from the full assist-org dataset.
 *
 *
 * RENDER FUNCTIONS (planned)
 * --------------------------
 *
 *   ws_render_directory_page()
 *       Top-level render for the standalone Directory page. Composes the
 *       assist-org listing and the right-side taxonomy guide. Called by the
 *       [ws_directory] shortcode (planned in shortcodes-general.php).
 *
 *   ws_render_directory_listing( $items )
 *       Renders the assist-org card grid from a ws_get_assist_org_data()
 *       result set. Supports prefiltered entry via URL query params.
 *
 *   ws_render_directory_card( $org )
 *       Renders a single assist-org card with name, type, services summary,
 *       contact info, and a [Connect for Help] button. The button URL
 *       encodes the active taxonomy filter state and the assist-org post ID
 *       as query params targeting the Directory page.
 *
 *   ws_render_directory_taxonomy_guide()
 *       Renders the right-side taxonomy filtering panel. Presents taxonomy
 *       terms as plain-language filter questions. Differs from the
 *       jurisdiction-page right panel: operates on the full assist-org
 *       dataset without a jurisdiction scope.
 *
 *   ws_render_directory_empty()
 *       Renders the fallback state when no assist-orgs match the active
 *       filter combination.
 *
 *
 * DATA LAYER
 * ----------
 *
 * All data reads must go through the query layer:
 *
 *   ws_get_assist_org_data()  — primary data source for the listing
 *
 * This file must not call get_post_meta(), get_field(), or WP_Query directly.
 *
 *
 * LOAD ORDER
 * ----------
 *
 * Loaded in the ASSEMBLY LAYER (frontend only) alongside section-renderer.php
 * and render-jurisdiction.php. No dependency on either — fully standalone.
 *
 *
 * @package    WhistleblowerShield
 * @since      3.6.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION HISTORY
 * ---------------
 * 3.6.0  Stub created. Docblock establishes intended architecture for the
 *        Directory page render layer. No render functions implemented yet.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Render functions for the Directory page are planned — see docblock above.
// Implementation begins when the [ws_directory] shortcode and assist-org
// taxonomy filtering architecture are ready for build-out.
// ════════════════════════════════════════════════════════════════════════════
