<?php
/**
 * acf-major-edit.php
 *
 * ACF Field Group: Major Edit Flag
 *
 * PURPOSE
 * -------
 * Adds a "Major Edit Flag" field group to the four content CPTs that
 * feed the ws-legal-update changelog system:
 *
 *   jx-summary, jx-statute, jx-citation, jx-interpretation
 *
 * When an editor checks `is_major_edit` and provides a description,
 * ws_acf_log_major_edit() in admin-hooks.php intercepts the save,
 * creates a ws-legal-update post with auto-stamped metadata, then
 * resets both fields so they are clean for the next save.
 *
 * FIELDS
 * ------
 *   is_major_edit            — true/false toggle. Default off.
 *                              Label: "Flag as Major Edit"
 *
 *   major_edit_description   — textarea. Conditional on is_major_edit = 1.
 *                              Label: "Describe the Change"
 *                              Required: enforced in ws_acf_log_major_edit()
 *                              — a missing description bails with an admin
 *                              notice rather than creating an empty changelog
 *                              entry. Both fields are reset to empty on every
 *                              save where is_major_edit = 1.
 *
 * INTEGRATION
 * -----------
 * The save hook that consumes these fields lives in admin-hooks.php.
 * See ws_acf_log_major_edit() (acf/save_post priority 20).
 *
 * @package    WhistleblowerShield
 * @since      3.2.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.2.0  Initial release.
 */

defined( 'ABSPATH' ) || exit;


add_action( 'acf/init', 'ws_register_acf_major_edit' );

/**
 * Registers the Major Edit Flag field group for all supported content CPTs.
 */
function ws_register_acf_major_edit() {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'                   => 'group_ws_major_edit_metadata',
		'title'                 => 'Major Edit Metadata',
		'menu_order'            => 99,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,

		// Applies to all four content CPTs that feed the changelog.
		'location' => [
			[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-summary',        ] ],
			[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-statute',         ] ],
			[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-citation',        ] ],
			[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'jx-interpretation',  ] ],
		],

		'fields' => [

			// ── is_major_edit ─────────────────────────────────────────────────
			[
				'key'           => 'field_ws_is_major_edit',
				'label'         => 'Flag as Major Edit',
				'name'          => 'is_major_edit',
				'type'          => 'true_false',
				'instructions'  => 'Check this box if this save represents a significant content change that should be logged in the site-wide Legal Updates changelog. You must provide a description below — saving without one will clear this flag and show a warning.',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Major Edit?',
				'ui_off_text'   => '',
			],

			// ── major_edit_description ────────────────────────────────────────
			[
				'key'               => 'field_ws_major_edit_description',
				'label'             => 'Describe the Change',
				'name'              => 'major_edit_description',
				'type'              => 'textarea',
				'instructions'      => 'Briefly describe what changed and why. This text will appear as the summary in the Legal Updates changelog entry. Both this field and the flag above are automatically cleared after saving.',
				'rows'              => 4,
				'placeholder'       => 'e.g. Updated filing deadline from 180 days to 300 days following the 2026 amendment to the State Whistleblower Act.',
				'conditional_logic' => [ [ [
					'field'    => 'field_ws_is_major_edit',
					'operator' => '==',
					'value'    => '1',
				] ] ],
			],

		],
	] );
}
