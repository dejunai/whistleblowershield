<?php
/**
 * acf-assist-org.php
 *
 * Registers ACF Pro fields for the `ws-assist-org` CPT.
 *
 * PURPOSE
 * -------
 * Provides structured fields for Whistleblower Assistance Organization
 * records. These records surface in the public directory to help
 * laypeople identify organizations that can assist with their specific
 * situation — jurisdiction, misconduct type, employment sector, and
 * cost constraints.
 *
 * FIELD SUMMARY
 * -------------
 * Identity tab:
 *   ws_ao_internal_id          Internal reference code (text, required)
 *   ws_ao_type                 Organization type (select, required)
 *   ws_ao_logo                 Logo image (image)
 *
 * Scope of Service tab:
 *   ws_ao_serves_nationwide    Serves all U.S. jurisdictions (true_false)
 *   ws_jurisdiction            Jurisdictions served (ws_jurisdiction taxonomy, checkbox)
 *   ws_ao_disclosure_type      Misconduct categories handled (taxonomy)
 *   ws_ao_services             Services offered (checkbox)
 *   ws_ao_employment_sectors   Employment sectors served (checkbox)
 *
 * Contact & Intake tab:
 *   ws_ao_website_url              Official website (url, required)
 *   ws_ao_intake_url               Intake / contact form URL (url)
 *   ws_ao_phone                    Phone number (text)
 *   ws_ao_email                    Contact email (email)
 *   ws_ao_mailing_address          Mailing address (textarea)
 *   ws_languages                   Languages served (taxonomy checkbox)
 *   ws_ao_additional_languages     Additional languages not in taxonomy list (text)
 *
 * Eligibility & Cost tab:
 *   ws_ao_cost_model           Cost structure (select, required)
 *   ws_ao_income_limit         Income eligibility required (true_false)
 *   ws_ao_income_limit_notes   Income limit details (textarea, conditional)
 *   ws_ao_accepts_anonymous    Can assist anonymous clients (true_false)
 *   ws_ao_eligibility_notes    Additional eligibility requirements (textarea)
 *
 * Credentials tab:
 *   ws_ao_licensed_attorneys   Licensed attorneys on staff (true_false)
 *   ws_ao_accreditation        Certifications and accreditations (text)
 *   ws_ao_bar_states           State bar memberships (text)
 *   ws_ao_verify_url           URL to verify current status (url)
 *
 * Authorship & Review tab:
 *   ws_ao_last_edited_author   Last edited by (user, stamp)
 *   ws_ao_date_created         Date created (text, readonly)
 *   ws_ao_last_edited          Last edited date (text, readonly)
 *   ws_ao_last_reviewed        Last verified date (date_picker)
 *
 * STAMP FIELDS
 * ------------
 * Written server-side via ws_acf_write_stamp_fields() in admin-hooks.php.
 * Meta prefix: ws_ao
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
 * 3.0.0  Phase 8: Replaced ws_ao_languages plain-text field with ws_languages
 *         taxonomy checkbox field + ws_ao_additional_languages text field.
 *         Auto-assign of "additional" term handled in admin-hooks.php.
 *         Phase 12.1: Replaced ws_ao_jurisdictions checkbox (dynamic choices via
 *         ws_jx_code meta) with ws_jurisdiction taxonomy field. Dynamic choice
 *         filter removed. Plain Language tab added (Phase 9.2).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'ws_register_acf_assist_org' );

function ws_register_acf_assist_org() {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [

        'key'                   => 'group_ws_assist_org',
        'title'                 => 'Assistance Organization Details',
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'active'                => true,

        'location' => [ [ [
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => 'ws-assist-org',
        ] ] ],

        'fields' => [

            // ────────────────────────────────────────────────────────────────
            // Tab: Identity
            //
            // Core identifiers and classification for each organization.
            // The post title serves as the public organization name.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_identity',
                'label' => 'Identity',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_ao_internal_id',
                'label'        => 'Internal Reference Code',
                'name'         => 'ws_ao_internal_id',
                'type'         => 'text',
                'instructions' => 'Slug-safe internal identifier — lowercase, hyphens only. Examples: "aclu-national", "nwc-dc", "gp-ca". Used for programmatic lookups and deduplication.',
                'required'     => 1,
                'placeholder'  => 'aclu-national',
            ],

            [
                'key'           => 'field_ws_ao_type',
                'label'         => 'Organization Type',
                'name'          => 'ws_ao_type',
                'type'          => 'select',
                'instructions'  => 'Select the category that best describes this organization.',
                'required'      => 1,
                'choices'       => [
                    'nonprofit'   => 'Nonprofit Organization (501c3)',
                    'legal_aid'   => 'Legal Aid Clinic',
                    'law_firm'    => 'Law Firm (Pro Bono Program)',
                    'bar_program' => 'Bar Association Program',
                    'advocacy'    => 'Advocacy / Policy Group',
                    'ombudsman'   => 'Government Ombudsman',
                    'union'       => 'Labor Union',
                ],
                'default_value' => 'nonprofit',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            [
                'key'           => 'field_ws_ao_logo',
                'label'         => 'Organization Logo',
                'name'          => 'ws_ao_logo',
                'type'          => 'image',
                'instructions'  => 'Upload the organization\'s logo (PNG or SVG preferred).',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'library'       => 'all',
                'max_size'      => '1',
                'mime_types'    => 'png,svg,jpg,jpeg',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Scope of Service
            //
            // Defines who this organization can help and how. These fields
            // drive the directory filtering logic so laypeople can quickly
            // surface organizations relevant to their specific situation.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_scope',
                'label' => 'Scope of Service',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_ao_serves_nationwide',
                'label'         => 'Serves All U.S. Jurisdictions',
                'name'          => 'ws_ao_serves_nationwide',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this organization operates in all 50 states, territories, and federal jurisdictions. If enabled, the Jurisdictions field below is not required.',
                'ui'            => 1,
                'ui_on_text'    => 'Nationwide',
                'ui_off_text'   => 'Limited',
                'default_value' => 0,
            ],

            [
                'key'           => 'field_ws_ao_jurisdiction',
                'label'         => 'Jurisdictions Served',
                'name'          => 'ws_jurisdiction',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_jurisdiction',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select every jurisdiction where this organization can provide assistance. If nationwide, enable the toggle above and leave this blank.',
                'required'      => 0,
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
                'allow_null'    => 1,
            ],

            [
                'key'           => 'field_ws_ao_disclosure_type',
                'label'         => 'Misconduct Categories Handled',
                'name'          => 'ws_ao_disclosure_type',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_disclosure_type',
                'instructions'  => 'Select all types of misconduct this organization has experience assisting with.',
                'required'      => 1,
                'field_type'    => 'multi_select',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_ws_ao_services',
                'label'        => 'Services Offered',
                'name'         => 'ws_ao_services',
                'type'         => 'checkbox',
                'instructions' => 'Select all services this organization provides to whistleblowers.',
                'required'     => 1,
                'choices'      => [
                    'legal_rep'    => 'Full Legal Representation',
                    'consultation' => 'Legal Consultation / Advice',
                    'referral'     => 'Intake & Referral',
                    'doc_review'   => 'Document Review',
                    'hotline'      => 'Whistleblower Hotline',
                    'retaliation'  => 'Retaliation Defense',
                    'financial'    => 'Financial Assistance',
                    'advocacy'     => 'Policy Advocacy',
                    'media'        => 'Media & Communications Support',
                ],
                'layout'        => 'vertical',
                'return_format' => 'value',
            ],

            [
                'key'          => 'field_ws_ao_employment_sectors',
                'label'        => 'Employment Sectors Served',
                'name'         => 'ws_ao_employment_sectors',
                'type'         => 'checkbox',
                'instructions' => 'Select the employment sectors this organization serves. Leave blank if all sectors are accepted.',
                'required'     => 0,
                'choices'      => [
                    'federal'   => 'Federal Government Employees',
                    'state'     => 'State & Local Government Employees',
                    'private'   => 'Private Sector Employees',
                    'military'  => 'Military & Defense Contractors',
                    'nonprofit' => 'Nonprofit & NGO Employees',
                    'any'       => 'All Sectors',
                ],
                'layout'        => 'vertical',
                'return_format' => 'value',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Contact & Intake
            //
            // How a whistleblower reaches this organization. All fields
            // except website_url are optional — not all organizations
            // publish every channel.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_contact',
                'label' => 'Contact & Intake',
                'type'  => 'tab',
            ],

            [
                'key'          => 'field_ws_ao_website_url',
                'label'        => 'Official Website',
                'name'         => 'ws_ao_website_url',
                'type'         => 'url',
                'instructions' => 'The organization\'s primary public website.',
                'required'     => 1,
            ],

            [
                'key'          => 'field_ws_ao_intake_url',
                'label'        => 'Intake / Contact Form URL',
                'name'         => 'ws_ao_intake_url',
                'type'         => 'url',
                'instructions' => 'Direct link to an intake form, referral request, or secure contact page — if different from the main website.',
            ],

            [
                'key'          => 'field_ws_ao_phone',
                'label'        => 'Phone Number',
                'name'         => 'ws_ao_phone',
                'type'         => 'text',
                'instructions' => 'Public-facing phone number for whistleblower inquiries.',
                'placeholder'  => '(555) 000-0000',
            ],

            [
                'key'          => 'field_ws_ao_email',
                'label'        => 'Contact Email',
                'name'         => 'ws_ao_email',
                'type'         => 'email',
                'instructions' => 'Public contact email address for whistleblower inquiries.',
            ],

            [
                'key'          => 'field_ws_ao_mailing_address',
                'label'        => 'Mailing Address',
                'name'         => 'ws_ao_mailing_address',
                'type'         => 'textarea',
                'instructions' => 'Physical or mailing address, if publicly available.',
                'rows'         => 3,
            ],

            [
                'key'           => 'field_ws_ao_languages',
                'label'         => 'Languages Served',
                'name'          => 'ws_languages',
                'type'          => 'taxonomy',
                'taxonomy'      => 'ws_languages',
                'field_type'    => 'checkbox',
                'instructions'  => 'Select languages this organization can serve. Check "Additional" if other languages are available — then specify them below.',
                'add_term'      => 0,
                'save_terms'    => 1,
                'load_terms'    => 1,
                'return_format' => 'id',
            ],

            [
                'key'          => 'field_ws_ao_additional_languages',
                'label'        => 'Additional Languages',
                'name'         => 'ws_ao_additional_languages',
                'type'         => 'text',
                'instructions' => 'List additional languages not in the checkbox list above (comma-separated). Saving a non-empty value here automatically assigns the "Additional" language term.',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Eligibility & Cost
            //
            // Critical information for a layperson evaluating whether this
            // organization can realistically help them. Cost model and
            // income limits are top concerns for financially stressed
            // whistleblowers considering retaliation.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_eligibility',
                'label' => 'Eligibility & Cost',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_ao_cost_model',
                'label'         => 'Cost Structure',
                'name'          => 'ws_ao_cost_model',
                'type'          => 'select',
                'instructions'  => 'Select the primary cost model for whistleblower services at this organization.',
                'required'      => 1,
                'choices'       => [
                    'free'          => 'Free of Charge',
                    'pro_bono'      => 'Pro Bono (Case-by-Case Selection)',
                    'sliding_scale' => 'Sliding Scale Fee',
                    'paid'          => 'Paid Services',
                ],
                'default_value' => 'free',
                'allow_null'    => 0,
                'ui'            => 1,
                'return_format' => 'value',
            ],

            [
                'key'           => 'field_ws_ao_income_limit',
                'label'         => 'Income Eligibility Required?',
                'name'          => 'ws_ao_income_limit',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this organization requires clients to meet income or financial eligibility criteria.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'          => 'field_ws_ao_income_limit_notes',
                'label'        => 'Income Eligibility Details',
                'name'         => 'ws_ao_income_limit_notes',
                'type'         => 'textarea',
                'instructions' => 'Describe the income thresholds or financial eligibility criteria — e.g., "Income must be below 200% of the federal poverty level."',
                'rows'         => 3,
                'conditional_logic' => [ [ [
                    'field'    => 'field_ws_ao_income_limit',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],

            [
                'key'           => 'field_ws_ao_accepts_anonymous',
                'label'         => 'Can Assist Anonymous Clients?',
                'name'          => 'ws_ao_accepts_anonymous',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this organization can provide meaningful assistance without requiring the client to disclose their identity.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'          => 'field_ws_ao_eligibility_notes',
                'label'        => 'Additional Eligibility Requirements',
                'name'         => 'ws_ao_eligibility_notes',
                'type'         => 'textarea',
                'instructions' => 'Describe any eligibility requirements not covered above — e.g., case type restrictions, geographic limits, employer size thresholds, or union membership requirements.',
                'rows'         => 4,
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Credentials
            //
            // Helps laypeople assess whether this organization can provide
            // reliable legal guidance vs. general advocacy support.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_credentials',
                'label' => 'Credentials',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_ws_ao_licensed_attorneys',
                'label'         => 'Licensed Attorneys on Staff?',
                'name'          => 'ws_ao_licensed_attorneys',
                'type'          => 'true_false',
                'instructions'  => 'Enable if this organization employs licensed attorneys who can provide formal legal advice and representation.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],

            [
                'key'          => 'field_ws_ao_accreditation',
                'label'        => 'Accreditations & Certifications',
                'name'         => 'ws_ao_accreditation',
                'type'         => 'text',
                'instructions' => 'Any relevant professional accreditations or certifications — e.g., "ABA-accredited", "NQAP member", "DOJ-recognized".',
            ],

            [
                'key'          => 'field_ws_ao_bar_states',
                'label'        => 'State Bar Memberships',
                'name'         => 'ws_ao_bar_states',
                'type'         => 'text',
                'instructions' => 'States where attorneys at this organization are bar-admitted — e.g., "CA, NY, DC, Federal".',
            ],

            [
                'key'          => 'field_ws_ao_verify_url',
                'label'        => 'Verification / Transparency URL',
                'name'         => 'ws_ao_verify_url',
                'type'         => 'url',
                'instructions' => 'Link to a page that verifies the organization\'s legitimacy — e.g., IRS Form 990, state bar directory, Charity Navigator, GuideStar.',
            ],

            // ────────────────────────────────────────────────────────────────
            // Tab: Authorship & Review
            //
            // Stamp fields are written server-side via ws_acf_write_stamp_fields()
            // in admin-hooks.php. All fields below are readonly for non-admins.
            // ────────────────────────────────────────────────────────────────

            [
                'key'   => 'field_ws_ao_tab_authorship',
                'label' => 'Authorship & Review',
                'type'  => 'tab',
            ],

            [
                'key'           => 'field_last_edited_author',
                'label'         => 'Last Edited By',
                'name'          => 'last_edited_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on every save. Editable by administrators only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'array',
                'wrapper'       => [ 'width' => '34' ],
            ],

            [
                'key'          => 'field_date_created',
                'label'        => 'Date Created',
                'name'         => 'date_created',
                'type'         => 'text',
                'instructions' => 'Set automatically on first save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'          => 'field_last_edited',
                'label'        => 'Last Edited',
                'name'         => 'last_edited',
                'type'         => 'text',
                'instructions' => 'Stamped automatically on every save. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
                'wrapper'      => [ 'width' => '33' ],
            ],

            [
                'key'           => 'field_create_author',
                'label'         => 'Created By',
                'name'          => 'create_author',
                'type'          => 'user',
                'instructions'  => 'Stamped automatically on first save. Read only.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
                'wrapper'       => [ 'width' => '33' ],
            ],

            [
                'key'            => 'field_ws_ao_last_reviewed',
                'label'          => 'Last Verified Date',
                'name'           => 'ws_ao_last_reviewed',
                'type'           => 'date_picker',
                'instructions'   => 'Update this date each time the organization record is verified for accuracy.',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
                'first_day'      => 1,
            ],

            // ── Tab: Plain Language (Phase 9.2) ───────────────────────────

            [
                'key'   => 'tab_ws_ao_plain_language',
                'label' => 'Plain Language',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_has_plain_english',
                'label'         => 'Has Plain Language Version',
                'name'          => 'has_plain_english',
                'type'          => 'true_false',
                'instructions'  => 'Enable when a plain-language description of this organization has been written below.',
                'ui'            => 1,
                'ui_on_text'    => 'Yes',
                'ui_off_text'   => 'No',
                'default_value' => 0,
            ],
            [
                'key'               => 'field_plain_english_wysiwyg',
                'label'             => 'Plain Language Content',
                'name'              => 'plain_english_wysiwyg',
                'type'              => 'wysiwyg',
                'instructions'      => 'Plain-language description of this organization for non-experts.',
                'tabs'              => 'all',
                'toolbar'           => 'full',
                'media_upload'      => 0,
                'conditional_logic' => [ [ [
                    'field'    => 'field_has_plain_english',
                    'operator' => '==',
                    'value'    => '1',
                ] ] ],
            ],
            [
                'key'           => 'field_plain_english_reviewed',
                'label'         => 'Plain Language Reviewed',
                'name'          => 'plain_english_reviewed',
                'type'          => 'true_false',
                'instructions'  => 'Check when a human has reviewed and approved the plain-language content.',
                'ui'            => 1,
                'ui_on_text'    => 'Reviewed',
                'ui_off_text'   => 'Pending',
                'default_value' => 0,
            ],
            [
                'key'           => 'field_plain_english_reviewed_by',
                'label'         => 'Reviewed By',
                'name'          => 'plain_english_reviewed_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped when Plain Language Reviewed is first enabled.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],
            [
                'key'           => 'field_plain_english_by',
                'label'         => 'Summarized By',
                'name'          => 'plain_english_by',
                'type'          => 'user',
                'instructions'  => 'Auto-stamped on first save after plain language content is created.',
                'role'          => [ 'author', 'editor', 'administrator' ],
                'return_format' => 'id',
                'readonly'      => 1,
                'disabled'      => 1,
            ],
            [
                'key'          => 'field_plain_english_date',
                'label'        => 'Summarized Date',
                'name'         => 'plain_english_date',
                'type'         => 'text',
                'instructions' => 'Auto-stamped on first save after plain language content is created. Read only.',
                'readonly'     => 1,
                'disabled'     => 1,
            ],

        ], // end fields

    ] ); // end acf_add_local_field_group

} // end ws_register_acf_assist_org


// Dynamic choice filter removed (Phase 3.2 / 12.1).
// ws_jurisdiction is now a taxonomy field — ACF loads terms natively.
