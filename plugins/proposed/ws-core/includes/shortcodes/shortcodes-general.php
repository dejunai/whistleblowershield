<?php
/**
 * File: shortcodes-general.php
 *
 * General purpose shortcodes not tied to specific jurisdiction data.
 * These shortcodes are available site-wide and may be placed manually
 * on any page, or called by the auto-renderer in render-jurisdiction.php.
 *
 * Shortcodes registered here:
 *
 *   [ws_nla_disclaimer_notice]
 *       Renders the standard "not legal advice" notice box.
 *       Copy is managed centrally in this file — editing $notice_text
 *       propagates to all jurisdiction pages automatically.
 *
 *   [ws_footer]
 *       Renders the site-wide footer block: mission statement,
 *       policy page links, contact email, and copyright line.
 *
 *   [ws_legal_updates jurisdiction="california" count="5" public_only="true"]
 *       Renders recent legal updates. Scoped to a jurisdiction when the
 *       jurisdiction parameter is provided; site-wide when omitted.
 *       public_only defaults true (public types only). Pass false for
 *       the internal site-wide changelog. Queries the ws-legal-update CPT.
 *
 *   [ws_reference_page post_id="123"]
 *       Renders the full reference materials page for a given jx-statute,
 *       jx-citation, or jx-interpretation post. Displays a back link,
 *       the parent post title, and a list of approved ws-reference items.
 *       If no approved references exist, renders a fallback message.
 *       All data reads delegated to ws_get_reference_page_data() in the
 *       query layer.
 *
 * VERSION
 * -------
 * 2.1.3  Full implementations restored from v2.0.0
 * 3.0.0  Phase 12.2: [ws_legal_updates] field reads moved to
 *         ws_get_legal_updates_data() in query-jurisdiction.php.
 *         Shortcode now delegates all data access to the query layer.
 * 3.1.0  Added [ws_reference_page] shortcode for ws-reference CPT system.
 * 3.3.3  DATA SOURCES docblock: corrected source_method value list (ai_assist →
 *         ai_assisted; added bulk_import). Evaluated ws_get_reference_page_url()
 *         relocation — no move needed, apply_filters() pattern is correct.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ── [ws_nla_disclaimer_notice] ────────────────────────────────────────────────
//
// To update the notice text site-wide: edit $notice_text below.
// The change propagates to all jurisdiction pages automatically.
// Styling is handled by .ws-nla-disclaimer-notice in ws-core-front.css.

add_shortcode( 'ws_nla_disclaimer_notice', function() {

    $notice_text = 'This page is provided for informational purposes only '
        . 'and does not constitute legal advice. The "Whistleblower Shield" '
        . 'is a database of legal information, not a law firm. Users should '
        . 'consult with a qualified legal professional regarding the specifics '
        . 'of their situation before initiating any formal disclosure or legal action.';

    return ws_render_nla_disclaimer( $notice_text );

} );


// ── [ws_footer] ───────────────────────────────────────────────────────────────

add_shortcode( 'ws_footer', function() {

    return ws_render_footer( [
        'year'         => date( 'Y' ),
        'policy_links' => [
            'Privacy Policy'     => '/privacy-policy/',
            'Disclaimer'         => '/disclaimer/',
            'Corrections Policy' => '/corrections-policy/',
            'Editorial Policy'   => '/editorial-policy/',
        ],
    ] );

} );


// ── [ws_legal_updates] ────────────────────────────────────────────────────────
//
// Renders recent legal updates for a specified jurisdiction, or site-wide
// if no jurisdiction parameter is given.
//
// Usage:
//   [ws_legal_updates jurisdiction="california" count="5"]
//   [ws_legal_updates count="10"]                        <- site-wide, public types only
//   [ws_legal_updates count="100" public_only="false"]   <- full site changelog
//
// public_only defaults true -- safe for all public-facing placements.
// Pass public_only="false" only for the internal site-wide changelog page.
// Public types are defined by WS_LEGAL_UPDATE_PUBLIC_TYPES in ws-core.php.
//
// DEPLOYMENT
// Use 1 -- Jurisdiction page (assembled by render-jurisdiction.php):
//   [ws_legal_updates jurisdiction="CA" count="5"]
//   Shows the last 5 public-type updates scoped to the current jurisdiction.
//   public_only is true by default -- internal and other types are excluded.
//
// Use 2 -- Site-wide changelog page (standalone WP page, manually placed):
//   [ws_legal_updates count="100" public_only="false"]
//   Shows the last 100 updates of all types across all jurisdictions.
//   Intended for internal review and site history; not linked from public pages.

add_shortcode( 'ws_legal_updates', 'ws_shortcode_legal_updates' );
function ws_shortcode_legal_updates( $atts ) {

    $atts = shortcode_atts( [
        'jurisdiction' => '',
        'count'        => 5,
        'public_only'  => 'true',
    ], $atts, 'ws_legal_updates' );

    // ── Resolve jurisdiction parameter to a post ID ───────────────────────
    //
    // Accepts: numeric post ID, USPS code ("CA"), or post slug ("california").
    // All data reads are delegated to ws_get_legal_updates_data().

    $jx_id = 0;
    if ( ! empty( $atts['jurisdiction'] ) ) {
        if ( is_numeric( $atts['jurisdiction'] ) ) {
            $jx_id = (int) $atts['jurisdiction'];
        } else {
            $jx_id = ws_get_id_by_code( strtoupper( $atts['jurisdiction'] ) );
            if ( ! $jx_id ) {
                $posts = get_posts( [
                    'post_type'      => 'jurisdiction',
                    'name'           => sanitize_title( $atts['jurisdiction'] ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ] );
                $jx_id = ! empty( $posts ) ? $posts[0] : 0;
            }
        }
    }

    // Shortcode attributes are always strings; convert to bool explicitly.
    // Any value other than the string "false" is treated as true.
    $public_only = ( strtolower( trim( $atts['public_only'] ) ) !== 'false' );

    $items = ws_get_legal_updates_data( $jx_id, (int) $atts['count'], $public_only );

    if ( empty( $items ) ) {
        return '';
    }

    return ws_render_legal_updates( $items );
}


// ── [ws_reference_page] ───────────────────────────────────────────────────────
//
// Renders the reference materials page for a jx-statute, jx-citation, or
// jx-interpretation post. Intended to be placed on a dedicated WP page.
//
// Usage:
//   [ws_reference_page post_id="123"]
//
// post_id must resolve to a jx-statute, jx-citation, or jx-interpretation.
// The "More Info" button in statute/citation shortcodes links here only when
// references exist — so the fallback message below is defensive only.

// ── ws_get_reference_page_url() ───────────────────────────────────────────────
//
// Returns the URL of the dedicated reference materials page, appending
// ?post_id=N for the given parent post. Looks up a published WP page at
// the slug defined by the filter ws_reference_page_slug (default:
// 'reference-materials'). Returns '' if no such page exists.
//
// Relocation evaluated (v3.3.3): function uses apply_filters() for the page
// slug, making it overridable without a constant. No relocation needed --
// this is the correct pattern and the correct file.
//
// Usage: ws_get_reference_page_url( $post_id )

function ws_get_reference_page_url( $post_id ) {
    $slug = apply_filters( 'ws_reference_page_slug', 'reference-materials' );
    $page = get_page_by_path( $slug );
    if ( ! $page ) return '';
    return add_query_arg( 'post_id', (int) $post_id, get_permalink( $page->ID ) );
}


add_shortcode( 'ws_reference_page', 'ws_shortcode_reference_page' );
function ws_shortcode_reference_page( $atts ) {

    $atts    = shortcode_atts( [ 'post_id' => 0 ], $atts, 'ws_reference_page' );
    $post_id = (int) $atts['post_id'];

    // Accept post_id from URL query param when not passed as shortcode attribute.
    // This allows a single page with [ws_reference_page] to serve all records.
    if ( ! $post_id && isset( $_GET['post_id'] ) ) {
        $post_id = (int) $_GET['post_id'];
    }

    if ( ! $post_id ) {
        return '';
    }

    $data = ws_get_reference_page_data( $post_id );

    if ( null === $data ) {
        return '';
    }

    $refs = $data['references'];

    ob_start();
    ?>
    <div class="ws-reference-page">

        <div class="ws-reference-page__back">
            <a href="<?php echo esc_url( $data['parent_url'] ); ?>"
               class="ws-reference-page__back-link">
                &larr; <?php echo esc_html( $data['parent_title'] ); ?>
            </a>
        </div>

        <h2 class="ws-reference-page__heading">Reference Materials</h2>
        <p class="ws-reference-page__subheading">
            External resources related to:
            <strong><?php echo esc_html( $data['parent_title'] ); ?></strong>
        </p>

        <?php if ( empty( $refs ) ) : ?>
            <p class="ws-reference-page__empty">
                No reference materials are currently available for this record.
            </p>
        <?php else : ?>
            <ul class="ws-reference-page__list">
                <?php foreach ( $refs as $ref ) : ?>
                    <li class="ws-reference-page__item">
                        <div class="ws-reference-page__item-header">
                            <a href="<?php echo esc_url( $ref['url'] ); ?>"
                               class="ws-reference-page__item-title"
                               target="_blank"
                               rel="noopener noreferrer">
                                <?php echo esc_html( $ref['title'] ); ?>
                            </a>
                            <?php if ( ! empty( $ref['type'] ) ) : ?>
                                <span class="ws-reference-page__item-type">
                                    <?php echo esc_html( $ref['type'] ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $ref['source_name'] ) ) : ?>
                            <div class="ws-reference-page__item-source">
                                <?php echo esc_html( $ref['source_name'] ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $ref['description'] ) ) : ?>
                            <div class="ws-reference-page__item-description">
                                <?php echo esc_html( $ref['description'] ); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}


// ============================================================================
// QUERY LAYER RETURN REFERENCE
//
// Quick reference for every key returned by the query layer. All keys are
// plain PHP array keys -- the ws_ / ws_auto_ meta key prefixes are stripped
// at the query layer and must not reappear here.
//
// Use this as a map when building new shortcodes. All data reads must go
// through the query layer functions below -- no direct get_field() or
// get_post_meta() calls in shortcodes.
//
// Query layer: includes/queries/query-jurisdiction.php
// ============================================================================
//
// UNIVERSAL KEYS (every dataset function)
// ----------------------------------------
//   id           int     Post ID
//   title        string  Post title (get_the_title)
//   url          string  Permalink
//   status       string  WP post status (publish, draft, pending, etc.)
//
// RECORD SUB-ARRAY  $data['record']
// Stamp fields written by ws_acf_write_stamp_fields() on every save.
// ----------------------------------------
//   created_by        int     WP user ID of the record creator
//   created_by_name   string  Display name resolved from created_by
//   created_date      string  Creation date (Y-m-d local)
//   edited_by         int     WP user ID of the most recent editor
//   edited_by_name    string  Display name resolved from edited_by
//   edited_date       string  Date of most recent edit (Y-m-d local)
//
// PLAIN SUB-ARRAY  $data['plain']
// Present on: jx-statute, jx-citation, jx-interpretation,
//             jx-summary, ws-agency, ws-assist-org.
// ----------------------------------------
//   has_content        bool    True when a plain-language version exists
//   plain_content      string  Plain-language wysiwyg body (safe to echo)
//   written_by         int     WP user ID of the plain-language author
//   written_by_name    string  Display name resolved from written_by
//   written_date       string  Date plain-language version was written (Y-m-d)
//   is_reviewed        bool    True when plain-language review is complete
//   reviewed_by        int     WP user ID of the plain-language reviewer
//   reviewed_by_name   string  Display name resolved from reviewed_by
//
// VERIFY SUB-ARRAY  $data['verify']
// Present on all CPTs.
// ----------------------------------------
//   source_method    string  How the record was created:
//                            human_created | matrix_seed | feed_import |
//                            ai_assisted | bulk_import
//   source_name      string  Human-readable source label (e.g. publication name)
//   verified_by      int     WP user ID of the person who verified the record
//   verified_by_name string  Display name resolved from verified_by
//   verified_date    string  Date the record was verified (Y-m-d)
//   verify_status    string  Verification workflow status value
//   needs_review     bool    True when the record has been flagged for re-review
//
// ws_get_jurisdiction_data( $input )
// ----------------------------------------
//   id           int     Jurisdiction post ID
//   name         string  Jurisdiction display name
//   class        string  Type: state | territory | district | federal
//   code         string  Two-letter USPS code, uppercased (e.g. CA, TX, US)
//   jx_term_id   int     ws_jurisdiction taxonomy term ID
//   flag[
//     url          string  Flag image URL
//     attribution  string  Attribution credit string
//     source_url   string  URL of the flag source
//     license      string  License name or identifier
//   ]
//   gov[
//     portal_url        string  Official government portal URL
//     portal_label      string  Display label for the portal link
//     executive_url     string  Head-of-government office URL
//     executive_label   string  Display label for the executive link
//     authority_url     string  Whistleblower authority office URL
//     authority_label   string  Display label for the authority link
//     legislature_url   string  Legislature URL
//     legislature_label string  Display label for the legislature link
//   ]
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_summary_data( $jx_term_id )
// ----------------------------------------
//   content      string  Summary body wysiwyg (raw -- apply wp_kses_post before echo)
//   sources      string  Sources & citations textarea
//   limitations  string  Limitations wysiwyg
//   notes        string  Internal notes
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_statute_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content              string  Statute body (raw post_content)
//   order                int     Display sort order
//   is_fed               bool    True when appended from the US federal scope
//   official_name        string  Full official statute name
//   disclosure_type      mixed   ACF select value (disclosure category)
//   attach_flag          bool    True when attached to this jurisdiction page
//   limit_value          string  Statute of limitations value
//   limit_unit           string  Statute of limitations unit (days, years, etc.)
//   trigger              string  Event that starts the limitations clock
//   tolling_notes        string  Notes on tolling or clock suspension
//   exhaustion_required  bool    True when administrative exhaustion is required
//   exhaustion_details   string  Details on exhaustion requirements
//   burden_of_proof      string  Burden of proof standard
//   remedies             string  Available remedies description
//   related_agencies     mixed   ACF relationship field value (agency post objects)
//   last_reviewed        string  Date last reviewed (Y-m-d)
//   ref_materials        array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_citation_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content        string  Citation body (raw post_content)
//   is_fed         bool    True when appended from the US federal scope
//   type           string  Citation type identifier
//   disclosure_type mixed  ACF select value (disclosure category)
//   label          string  Display label for this citation
//   cite_url       string  URL of the cited source
//   is_pdf         bool    True when the cited source is a PDF
//   attach_flag    bool    True when attached to this jurisdiction page
//   order          int     Display sort order
//   last_reviewed  string  Date last reviewed (Y-m-d)
//   ref_materials  array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_jx_interpretation_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   content            string  Interpretation body (raw post_content)
//   order              int     Display sort order
//   is_fed             bool    True when appended from the US federal scope
//   case_name          string  Full case name
//   citation           string  Legal citation string (e.g. 123 F.3d 456)
//   opinion_url        string  URL to the court opinion
//   court              string  Court name
//   year               string  Year of decision
//   favorable          bool    True when the outcome favors the whistleblower
//   summary            string  Plain-text summary of the holding
//   parent_statute_id  int     Post ID of the related jx-statute record
//   process_type       mixed   ACF select value (disclosure process type)
//   attach_flag        bool    True when attached to this jurisdiction page
//   last_reviewed      string  Date last reviewed (Y-m-d)
//   ref_materials      array   Approved ws-reference items -- see ws_get_ref_materials()
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_agency_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   code                   string  Internal agency code
//   name                   string  Full agency name
//   logo                   mixed   ACF image field value
//   disclosure_type        mixed   ACF select value (disclosure category)
//   process_type           mixed   ACF select value (disclosure process type)
//   website_url            string  Agency main website URL
//   reporting_url          string  Direct URL to the reporting/complaint portal
//   phone                  string  Agency contact phone number
//   confidentiality_notes  string  Notes on confidentiality handling
//   anonymous              bool    True when anonymous reports are accepted
//   reward                 bool    True when a reward program exists
//   languages              mixed   ACF select value (supported languages)
//   additional_languages   string  Free-text additional language notes
//   last_reviewed          string  Date last reviewed (Y-m-d)
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_assist_org_data( $jx_term_id )  -- returns array of items
// ----------------------------------------
//   internal_id          string  Internal reference ID
//   type                 string  Organization type identifier
//   logo                 mixed   ACF image field value
//   serves_nationwide    bool    True when the org serves all jurisdictions
//   disclosure_type      mixed   ACF select value (disclosure category)
//   services             string  Description of services offered
//   employment_sectors   string  Employment sectors served
//   website_url          string  Organization main website URL
//   intake_url           string  Direct URL to the intake or contact form
//   phone                string  Organization phone number
//   email                string  Organization contact email
//   mailing_address      string  Mailing address
//   languages            mixed   ACF select value (supported languages)
//   additional_languages string  Free-text additional language notes
//   cost_model           string  Cost model identifier (free, sliding_scale, etc.)
//   income_limit         string  Income threshold for eligibility (if applicable)
//   income_limit_notes   string  Notes on income limit or eligibility criteria
//   anonymous            bool    True when anonymous inquiries are accepted
//   eligibility_notes    string  General eligibility notes
//   licensed_attorneys   bool    True when licensed attorneys are on staff
//   accreditation        string  Accreditation body or status
//   bar_states           string  States where the org is bar-accredited
//   verify_url           string  URL to an external accreditation verification page
//   last_reviewed        string  Date last reviewed (Y-m-d)
//   plain[ ... ]   See PLAIN SUB-ARRAY above
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_legal_updates_data( $jx_id, $count, $public_only )  -- returns array of items
//   $jx_id       int   Jurisdiction post ID to scope results. 0 = site-wide.
//   $count       int   Maximum records to return (default 5).
//   $public_only bool  When true, restricts results to WS_LEGAL_UPDATE_PUBLIC_TYPES.
//                      Shortcode attribute: public_only="false" for full changelog.
//                      Defaults true -- safe for all public-facing placements.
// ----------------------------------------
//   id                  int     Post ID
//   title               string  Post title
//   update_date         string  Date of the legal change (Y-m-d)
//   effective_date      string  Date the change takes effect (Y-m-d)
//   post_date           string  WordPress publish date (MySQL datetime)
//   update_type         string  Update category (legislation, ruling, guidance, etc.)
//   multi_jurisdiction  bool    True when the update affects multiple jurisdictions
//   law_name            string  Name of the law or ruling
//   source_url          string  URL of the primary source
//   summary             string  Summary wysiwyg (wp_kses_post applied -- safe to echo)
//   source_post_id      int     Post ID of the originating CPT record (if any)
//   source_post_type    string  Post type of source_post_id (if any)
//   verify[ ... ]  See VERIFY SUB-ARRAY above
//   record[ ... ]  See RECORD SUB-ARRAY above
//
// ws_get_ref_materials( $post_id )  -- returns array of items
// ----------------------------------------
//   title        string  Reference item title
//   url          string  URL of the external resource
//   description  string  Brief description of the resource
//   type         string  Resource type (statute, ruling, article, etc.)
//   source_name  string  Name of the publishing source
//
// ws_get_reference_page_data( $parent_post_id )
// ----------------------------------------
//   parent_title  string  Title of the parent jx-statute / citation / interpretation
//   parent_url    string  Permalink of the parent post
//   references    array   Array of ref_materials items (see ws_get_ref_materials above)
// ============================================================================

