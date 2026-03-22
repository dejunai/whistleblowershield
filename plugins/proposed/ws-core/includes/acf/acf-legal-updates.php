<?php
/**
 * acf-ws-legal-update.php
 *
 * Registers ACF Pro fields for the `ws-legal-update` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured metadata for Legal Update records, capturing
 * the nature, source, date, and affected jurisdictions of each
 * significant development in whistleblower law.
 *
 * Legal Updates are linked to one or more Jurisdiction records
 * through the ws_update_jurisdictions taxonomy field. Jurisdiction
 * scoping uses the ws_jurisdiction taxonomy (save_terms=0 — terms
 * are selected for filtering purposes and not written to the taxonomy
 * table from this field).
 *
 * FUTURE USE
 * ----------
 * These records are intended for:
 *
 *      • internal legal tracking
 *      • journalist research
 *      • future public update feeds and timelines
 *
 * @package    WhistleblowerShield
 * @since      1.0.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 1.0.0  Initial release.
 * 2.1.0  Refactored for ws-core architecture. CPT slug corrected
 *         to ws-legal-update (hyphenated). File renamed from
 *         acf-legal-update.php to acf-ws-legal-update.php.
 *         Full header and inline comments added.
 * 3.1.1  Pass 2 ACF audit fix:
 *        - Added Content tab (field_legal_update_content_tab) before
 *          field_update_jurisdictions. All content fields now render
 *          inside a tab, consistent with all other CPT field groups.
 * 3.1.2  Pass 3 ACF audit — instructions fixes:
 *        - PURPOSE docblock: updated to remove reference to deleted
 *          admin-relationships.php; clarified ws_update_jurisdictions
 *          as a taxonomy field with save_terms=0.
 * 3.4.0  Stamp field centralization:
 *        - Removed Authorship & Review tab and all stamp fields — now
 *          registered centrally in acf-stamp-fields.php (menu_order 90).
 * 3.5.0  Legal update system overhaul:
 *        - Jurisdiction field: renamed ws_update_jurisdictions → ws_update_jurisdiction
 *          (singular); field_type changed from multi_select to select. One update
 *          maps to one jurisdiction — federal updates affect the federal term only;
 *          distribution to state records is handled separately.
 *        - save_terms changed 0 → 1: ACF now writes the selected jurisdiction term
 *          to wp_term_relationships on save, enabling tax_query in the query layer.
 *        - load_terms changed 0 → 1: jurisdiction field reloads from the taxonomy
 *          table on admin edit, staying in sync with save_terms.
 *        - add_term remains 0: no new jurisdiction terms may ever be created via
 *          this field.
 *        - Added Multi-Jurisdiction flag (ws_update_multi_jurisdiction, true_false):
 *          reserved for future use when an update affects more than one jurisdiction
 *          (e.g. a federal change with confirmed downstream state impact). No
 *          additional logic fires on this flag yet.
 *        - Source URL field renamed ws_update_source → ws_update_source_url to
 *          comply with project convention: all URL-valued meta keys end in _url.
 *        - Update Type choices expanded: statute, citation, summary, interpretation,
 *          regulation, policy, internal, other. 'internal' label updated to
 *          'WhistleblowerShield.org Internal Adjustment' for admin clarity.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_legal_update' );

function ws_register_acf_legal_update() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_legal_update_metadata',
        'title'                 => 'Legal Update Metadata',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        // Location: ws-legal-update CPT only (hyphenated slug)
        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-legal-update',
        ] ] ],

        'fields' => [

            // ── Tab: Content ──────────────────────────────────────────────

            [
                'key'   => 'field_legal_update_content_tab',
                'label' => 'Content',
                'type'  => 'tab',
            ],

            // ── Affected Jurisdictions ────────────────────────────────────
			// Taxonomy multi-select — one update may affect many
			// jurisdictions. Scoped via ws_jurisdiction taxonomy terms.
			[
				'key'           => 'field_update_jurisdiction',
				'label'         => 'Affected Jurisdiction',
				'name'          => 'ws_legal_update_jurisdiction',
				'type'          => 'taxonomy',
				'instructions'  => 'Select the jurisdiction affected by this legal update.',
				'taxonomy'      => 'ws_jurisdiction',
				'field_type'    => 'select',
				'return_format' => 'id',
				'save_terms'    => 1,
				'load_terms'    => 1,
				'add_term'      => 0,
			],

            // ── Multi-Jurisdiction Flag ───────────────────────────────────

            [
                'key'           => 'field_update_multi_jurisdiction',
                'label'         => 'Multi-Jurisdiction',
                'name'          => 'ws_legal_update_multi_jurisdiction',
                'type'          => 'true_false',
                'instructions'  => 'Check if this update affects jurisdictions beyond the one listed above. No additional processing occurs — this flag is reserved for future use.',
                'default_value' => 0,
                'ui'            => 1,
                'ui_on_text'    => 'Multi-Jurisdiction',
                'ui_off_text'   => '',
            ],

            // ── Update Date ───────────────────────────────────────────────

            [
                'key'            => 'field_update_date',
                'label'          => 'Update Date',
                'name'           => 'ws_legal_update_date',
                'type'           => 'date_picker',
                'instructions'   => 'Date the legal change took effect or was officially published.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Primary Source ────────────────────────────────────────────

            [
                'key'          => 'field_update_source_url',
                'label'        => 'Primary Source URL',
                'name'         => 'ws_legal_update_source_url',
                'type'         => 'url',
                'instructions' => 'Official source for the legal change — e.g., court decision, statute, regulation, or agency policy document.',
            ],

            // ── Update Type ───────────────────────────────────────────────

            [
                'key'          => 'field_update_type',
                'label'        => 'Update Type',
                'name'         => 'ws_legal_update_type',
                'type'         => 'select',
                'instructions' => 'Select the category that best describes this legal development.',
                'choices'      => [
                    'statute'        => 'Statutory Change',
                    'citation'       => 'Citation Update',
                    'summary'        => 'Summary Update',
                    'interpretation' => 'Interpretation Update',
                    'regulation'     => 'Regulatory Change',
                    'policy'         => 'Agency Policy',
                    'internal'       => 'WhistleblowerShield.org Internal Adjustment',
                    'other'          => 'Other',
                ],
                'default_value' => 'statute',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            // ── Law / Statute Name ────────────────────────────────────────

            [
                'key'          => 'field_legal_update_law_name',
                'label'        => 'Law / Statute Name',
                'name'         => 'ws_legal_update_law_name',
                'type'         => 'text',
                'instructions' => 'The name of the law or statute affected by this update.',
            ],

            // ── Summary ───────────────────────────────────────────────────

            [
                'key'          => 'field_legal_update_summary',
                'label'        => 'Summary',
                'name'         => 'ws_legal_update_summary_wysiwyg',
                'type'         => 'wysiwyg',
                'instructions' => 'Brief summary of the legal change and its significance for whistleblowers.',
                'tabs'         => 'all',
                'toolbar'      => 'basic',
                'media_upload' => 0,
            ],

            // ── Effective Date ────────────────────────────────────────────

            [
                'key'            => 'field_legal_update_effective_date',
                'label'          => 'Effective Date',
                'name'           => 'ws_legal_update_effective_date',
                'type'           => 'date_picker',
                'instructions'   => 'When does this change take effect? Leave blank if not yet determined.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Tab: Authorship & Review ──────────────────────────────────
            // Removed — registered centrally in acf-stamp-fields.php
            // (group_stamp_metadata, menu_order 90).

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_legal_update
