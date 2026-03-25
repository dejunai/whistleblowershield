<?php
/**
 * matrix-ag-procedures.php
 *
 * Seeds filing procedures for the nine nationwide federal whistleblower agencies
 * seeded by matrix-agencies.php. One procedure per primary intake path.
 *
 * PURPOSE
 * -------
 * Creates ws-ag-procedure CPT posts for each agency's primary whistleblower
 * intake path. Each record links to its parent agency via ws_proc_agency_id,
 * is scoped to the US jurisdiction, and is cross-referenced to applicable
 * jx-statute records where those statutes are present in the matrix.
 *
 * All records are seeded as 'publish'. Editors should update walkthrough
 * text and verify intake URLs regularly — see ws_proc_last_reviewed.
 *
 * SEEDER RULES
 * ------------
 * - All seeded records receive ws_matrix_source = 'procedure-matrix'.
 * - Gate: ws_seeded_procedure_matrix / 1.0.0 (Unified Option-Gate Method).
 * - Idempotent: get_page_by_path() check on post_name prevents duplicates.
 * - Depends on matrix-agencies.php having run first (agencies must exist).
 * - Depends on matrix-fed-statutes.php having run first (statutes must exist).
 * - Load order in loader.php guarantees both run before this file.
 *
 * STATUTE LINKS
 * -------------
 * Only statutes present in the seeded matrix are linked. Agencies whose
 * primary statutes are not in the matrix (NLRB/NLRA, CFTC/CEA, IRS/IRC §7623,
 * EPA-specific environmental statutes) have ws_proc_statute_ids left empty.
 * Link those statutes manually once their jx-statute records are created.
 *
 * DEADLINE NOTES
 * --------------
 * Deadlines reflect the shortest applicable deadline under the primary intake
 * path. Many agencies enforce multiple statutes with differing deadlines —
 * the walkthrough text describes the variance. ws_proc_deadline_days is the
 * floor the end-user must be aware of immediately.
 *
 * @package    WhistleblowerShield
 * @since      3.9.0
 * @author     Whistleblower Shield
 * @link       https://whistleblowershield.org
 * @copyright  Copyright (c) Whistleblower Shield
 *
 * VERSION
 * -------
 * 3.9.0  Initial release. Phase 4 of ws-ag-procedure feature build.
 *        Ten procedures across nine agencies: SEC, OSHA, OSC (×2), MSPB,
 *        NLRB, CFTC, IRS, EPA, DOJ.
 */

defined( 'ABSPATH' ) || exit;


// ════════════════════════════════════════════════════════════════════════════
// Procedure Data
// ════════════════════════════════════════════════════════════════════════════

$_ws_procedure_matrix = [

    // ── 1. SEC — Dodd-Frank Whistleblower Tip ─────────────────────────────

    [
        'title'               => 'SEC Dodd-Frank Whistleblower Tip Submission',
        'slug'                => 'sec-dodd-frank-tip-submission',
        'agency_slug'         => 'sec-whistleblower-program',
        'ws_proc_type'        => 'disclosure',
        'disclosure_types'    => [ 'securities-commodities-fraud' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://www.sec.gov/tcr',
        // Anonymous is correct: Dodd-Frank Rule 21F-9 expressly permits anonymous
        // submissions through an attorney. Anonymous reporters remain eligible for
        // awards as long as their attorney identifies them to the SEC before any
        // award is paid.
        'ws_proc_identity_policy'   => 'anonymous',
        'ws_proc_intake_only'       => false,
        'ws_proc_deadline_days'     => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'     => false,
        'ws_proc_prerequisites_note'=> '',
        'statute_slugs'             => [ 'dodd-frank-section-922' ],
        'ws_proc_walkthrough'       => '<ol>
<li><strong>Gather your evidence.</strong> Collect documents, communications, or financial records evidencing the potential securities law violation. The more specific and credible your tip, the more likely the SEC will prioritize it.</li>
<li><strong>Submit through the TCR portal.</strong> Go to sec.gov/tcr (Tips, Complaints, and Referrals). You may submit under your own name or anonymously — anonymous submission requires attorney representation, which the SEC mandates before any award is paid to an anonymous tipster.</li>
<li><strong>Preserve your submission records.</strong> Save a copy of your submission confirmation and all supporting materials. The SEC may contact you for additional information; you will also need documentation to support a future award application.</li>
<li><strong>Understand the investigation timeline.</strong> The SEC does not notify tipsters of investigation status or outcome. Investigations can take years. Monitor SEC enforcement actions for actions that may relate to your tip.</li>
<li><strong>File an award application when applicable.</strong> If the SEC brings an enforcement action resulting in sanctions exceeding $1 million and you believe your tip contributed, submit Form WB-APP to the SEC Whistleblower Office to claim your award (10–30% of collected sanctions).</li>
</ol>',
        'ws_proc_exclusivity_note'  => 'Submitting a tip to the SEC does not constitute a formal retaliation complaint. If you experienced adverse action for reporting to the SEC, you may also have a separate anti-retaliation claim under Dodd-Frank § 78u-6(h). That claim must be filed in federal district court within 180 days of the adverse action — it is not resolved through this tip submission portal.',
    ],

    // ── 2. OSHA — Whistleblower Retaliation Complaint ─────────────────────

    [
        'title'               => 'OSHA Whistleblower Retaliation Complaint',
        'slug'                => 'osha-whistleblower-retaliation-complaint',
        'agency_slug'         => 'osha-whistleblower-protection-program',
        'ws_proc_type'        => 'retaliation',
        'disclosure_types'    => [ 'retaliation-protection', 'occupational-health-safety', 'securities-commodities-fraud' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://www.osha.gov/workers/file-a-complaint',
        'ws_proc_identity_policy'      => 'identified',
        'ws_proc_intake_only'          => false,
        // 30 days is the shortest deadline (OSH Act § 11(c), SDWA, CERCLA, and others).
        // SOX, Clean Air Act, and several transportation statutes allow 180 days.
        // File as early as possible — the walk-through explains the variance.
        'ws_proc_deadline_days'        => 30,
        'ws_proc_deadline_clock_start' => 'adverse_action',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        'statute_slugs'                => [ 'osh-act-section-11c', 'sarbanes-oxley-section-806' ],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Act immediately — deadlines vary and some are very short.</strong> OSHA administers retaliation protections under more than 25 federal statutes. Deadlines range from 30 days (OSH Act § 11(c), Safe Drinking Water Act, CERCLA) to 180 days (Sarbanes-Oxley, Clean Air Act, STAA) to 365 days (Consumer Financial Protection Act). The clock starts on the date of the adverse action. If you are unsure which statute applies to your situation, file as soon as possible — do not wait to consult an attorney first.</li>
<li><strong>Identify your protected activity and adverse action.</strong> Describe the specific safety complaint, regulatory disclosure, or protected activity you engaged in (e.g., reporting a workplace safety violation, refusing unsafe work, filing a workers&#8217; compensation claim). Describe the adverse action (termination, demotion, suspension, harassment). Connect the two by time proximity or direct evidence.</li>
<li><strong>File your complaint.</strong> Submit online at whistleblowers.gov, by mail to your local OSHA area office, or by calling 1-800-321-OSHA. OSHA can accept an oral complaint; a written complaint creates a cleaner record.</li>
<li><strong>OSHA investigates.</strong> OSHA will notify your employer and request a written response. A compliance officer will interview you, your employer, and relevant witnesses. Under most statutes, OSHA has 90 days to issue preliminary findings.</li>
<li><strong>Preliminary findings.</strong> If OSHA finds merit, it may issue a preliminary order requiring reinstatement, back pay, attorney fees, and compensatory damages. Either party may object within 30 days, triggering a formal hearing before an OSHA Administrative Law Judge.</li>
<li><strong>If OSHA takes no action within 180 days.</strong> Under several statutes (notably SOX and AIR21), you may file a new action in federal district court if OSHA has not issued a final order within 180 days of your complaint. Consult an attorney to evaluate whether to proceed administratively or judicially.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing a complaint with OSHA under some statutes (e.g., SOX § 806) may restrict your ability to file in federal court while the OSHA proceeding is pending. Under other statutes you may withdraw from OSHA and proceed in court after 180 days. Review the specific statute governing your claim with an attorney before choosing your forum.',
    ],

    // ── 3. OSC — Disclosure of Wrongdoing ─────────────────────────────────

    [
        'title'               => 'OSC Disclosure of Wrongdoing',
        'slug'                => 'osc-wrongdoing-disclosure',
        'agency_slug'         => 'office-of-special-counsel',
        'ws_proc_type'        => 'disclosure',
        'disclosure_types'    => [ 'public-corruption-ethics', 'procurement-spending-fraud' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://osc.gov/Services/Pages/FileDisclosure.aspx',
        'ws_proc_identity_policy'      => 'confidential',
        // OSC receives the disclosure, forwards it to the relevant agency head,
        // and requires a written report. It does not adjudicate or award.
        'ws_proc_intake_only'          => true,
        'ws_proc_deadline_days'        => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        'statute_slugs'                => [ 'whistleblower-protection-act' ],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Confirm the scope of OSC disclosures.</strong> OSC accepts disclosures of: (1) a violation of law, rule, or regulation; (2) gross mismanagement; (3) a gross waste of funds; (4) an abuse of authority; or (5) a substantial and specific danger to public health or safety. This pathway is for federal employees and applicants disclosing government wrongdoing — not private-sector workers or retaliation complaints.</li>
<li><strong>Submit your disclosure online.</strong> Use the OSC Disclosure Form at osc.gov. You do not need an attorney. Provide a clear factual account of the wrongdoing, the agency or officials involved, and any supporting documentation.</li>
<li><strong>OSC evaluates your disclosure.</strong> OSC will review the disclosure and determine whether it is "substantial in likelihood of disclosure" — meaning OSC believes it credibly alleges one of the five categories above. Not all submissions are referred.</li>
<li><strong>OSC forwards to the agency head and Congress.</strong> If OSC finds the disclosure substantial, it will be forwarded to the head of the relevant agency, who must report back in writing on their findings and any corrective action taken. OSC then transmits that report to the President and certain congressional committees.</li>
<li><strong>You may comment on the agency&#8217;s response.</strong> OSC will provide you with the agency&#8217;s report. You have the right to submit comments, which become part of the congressional record.</li>
<li><strong>Understand the limits of this pathway.</strong> An OSC wrongdoing disclosure does not produce individual remedies, monetary relief, or direct enforcement action by OSC. If you experienced retaliation for disclosing wrongdoing, file a separate prohibited personnel practice complaint — this disclosure pathway does not protect you from retaliation on its own.</li>
</ol>',
        'ws_proc_exclusivity_note'     => '',
    ],

    // ── 4. OSC — Prohibited Personnel Practice Complaint ──────────────────

    [
        'title'               => 'OSC Prohibited Personnel Practice Complaint',
        'slug'                => 'osc-prohibited-personnel-practice',
        'agency_slug'         => 'office-of-special-counsel',
        'ws_proc_type'        => 'retaliation',
        'disclosure_types'    => [ 'retaliation-protection' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://osc.gov/Services/Pages/FileComplaint.aspx',
        'ws_proc_identity_policy'      => 'confidential',
        'ws_proc_intake_only'          => false,
        // No hard statutory deadline for filing with OSC itself, but OSC has
        // 240 days to complete its investigation. Filing promptly is essential
        // because the clock to the MSPB Individual Right of Action (IRA) appeal
        // starts running from OSC's final determination.
        'ws_proc_deadline_days'        => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        'statute_slugs'                => [ 'whistleblower-protection-act', 'whistleblower-protection-enhancement-act' ],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Confirm OSC jurisdiction.</strong> OSC has jurisdiction over federal employees, former employees, and applicants for federal employment. Prohibited personnel practices (PPPs) under the Whistleblower Protection Act include retaliation for protected disclosures, recommending retaliation, and taking personnel actions for prohibited reasons. Private-sector employees do not file here.</li>
<li><strong>File your complaint online.</strong> Use the OSC online complaint form. Provide the date of the adverse action, a description of your protected disclosure (what you reported, to whom, when), and documentation of the adverse action (termination letter, performance review, suspension notice). Attach supporting materials where possible.</li>
<li><strong>OSC investigates.</strong> OSC will review your complaint, notify the relevant agency, and may conduct interviews. OSC has 240 days to issue a final determination. If OSC finds probable cause that a PPP occurred, it may seek corrective action, disciplinary action, or civil penalties against responsible officials.</li>
<li><strong>Monitor the 120-day window carefully.</strong> If OSC has not issued a final determination within 120 days of your filing, you may choose to file an Individual Right of Action (IRA) appeal at the Merit Systems Protection Board (MSPB) without waiting for OSC to conclude. You do not have to wait the full 240 days.</li>
<li><strong>After OSC&#8217;s determination.</strong> If OSC declines to pursue your complaint, it will issue a written determination. You then have 65 days from that determination to file an IRA appeal at the MSPB. Track this deadline precisely — missing it forfeits your right to independent adjudication.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing an OSC complaint is a prerequisite for an MSPB Individual Right of Action (IRA) appeal. Once you receive OSC\'s final determination (or 120 days pass without one), file promptly at the MSPB. Pursuing an OSC PPP complaint does not preclude separate claims under other statutes if your situation involves violations beyond WPA/WPEA coverage.',
    ],

    // ── 5. MSPB — Individual Right of Action Appeal ───────────────────────

    [
        'title'               => 'MSPB Individual Right of Action Appeal',
        'slug'                => 'mspb-individual-right-of-action',
        'agency_slug'         => 'merit-systems-protection-board',
        'ws_proc_type'        => 'retaliation',
        'disclosure_types'    => [ 'retaliation-protection' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://e-appeal.mspb.gov/',
        'ws_proc_identity_policy'      => 'identified',
        'ws_proc_intake_only'          => false,
        // 65 days from the date of OSC's final written determination declining to pursue.
        // Alternatively, after 120 days of no OSC final determination.
        'ws_proc_deadline_days'        => 65,
        'ws_proc_deadline_clock_start' => 'knowledge',
        'ws_proc_prerequisites'        => true,
        'ws_proc_prerequisites_note'   => 'You must have first filed a prohibited personnel practice complaint with the U.S. Office of Special Counsel (OSC). An IRA appeal may be filed either (a) within 65 days of OSC\'s written final determination declining to pursue your complaint, or (b) after 120 days have passed since your OSC filing without a final determination. Filing at the MSPB before satisfying one of these conditions will result in dismissal.',
        'statute_slugs'                => [ 'whistleblower-protection-act' ],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Confirm you have met the OSC prerequisite.</strong> You must have a pending or completed OSC complaint. You may file an IRA appeal (a) within 65 days of OSC&#8217;s written final determination declining to pursue, or (b) after 120 days without a final OSC determination. Keep your OSC complaint number and any OSC correspondence ready.</li>
<li><strong>File through MSPB e-Appeal Online.</strong> Go to e-appeal.mspb.gov. Create an account and file electronically. Include your OSC complaint number, OSC&#8217;s determination letter (if any), a description of your protected disclosure, and documentation of the adverse personnel action (removal, demotion, suspension, etc.).</li>
<li><strong>Respond to initial MSPB processing.</strong> An MSPB administrative judge (AJ) will be assigned. The AJ may issue an acknowledgment order setting deadlines for discovery and the hearing. Respond to all orders promptly — missing an MSPB deadline can result in default judgment.</li>
<li><strong>Discovery.</strong> You are entitled to discovery: depositions, interrogatories, requests for documents, and requests for admissions. Use discovery to obtain personnel files, emails, and agency records documenting the timeline of your protected activity and the adverse action.</li>
<li><strong>Hearing before an Administrative Judge.</strong> The hearing is an evidentiary proceeding similar to a bench trial. You bear the initial burden of proving (1) you made a protected disclosure, (2) the agency took a personnel action, and (3) the agency official knew of your disclosure. The burden then shifts to the agency to prove by clear and convincing evidence that it would have taken the same action absent your disclosure.</li>
<li><strong>Initial Decision and further appeal.</strong> After the hearing, the AJ issues an Initial Decision. Either party may petition the full MSPB Board for review within 35 days. After the Board&#8217;s final decision, you may petition the U.S. Court of Appeals for the Federal Circuit for judicial review.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing an IRA appeal at the MSPB generally precludes pursuing the same claims in federal district court while the MSPB proceeding is pending. If the MSPB issues a final order, subsequent judicial review goes to the Federal Circuit, not a district court. Consult an attorney before choosing between the MSPB/Federal Circuit track and any available district court pathway.',
    ],

    // ── 6. NLRB — Unfair Labor Practice Charge ────────────────────────────

    [
        'title'               => 'NLRB Unfair Labor Practice Charge',
        'slug'                => 'nlrb-unfair-labor-practice-charge',
        'agency_slug'         => 'national-labor-relations-board',
        'ws_proc_type'        => 'both',
        'disclosure_types'    => [ 'retaliation-protection', 'collective-bargaining' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://www.nlrb.gov/reports/nlrb-case-activity-statistics/case-filings/file-a-case',
        'ws_proc_identity_policy'      => 'identified',
        'ws_proc_intake_only'          => false,
        'ws_proc_deadline_days'        => 180,
        'ws_proc_deadline_clock_start' => 'adverse_action',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        // NLRA is not in the statute matrix — leave statute_slugs empty.
        'statute_slugs'                => [],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Confirm NLRB jurisdiction.</strong> The NLRB protects employees in the private sector (and some non-profit and healthcare employees) who engage in concerted activity — taking collective action with coworkers to improve working conditions. Retaliation for reporting wrongdoing may be protected if it involved concerted activity (e.g., a joint complaint with co-workers, union grievance activity, or collective refusal of unsafe work). The NLRB does not cover federal employees, state and local government employees, supervisors, or agricultural workers.</li>
<li><strong>Act within 6 months.</strong> An ULP charge must be filed within 6 months (180 days) of the unfair labor practice. This deadline is jurisdictional — there are no exceptions or tolling provisions.</li>
<li><strong>File Form NLRB-501 (Charge Against Employer).</strong> Submit online at nlrb.gov or at your regional NLRB office. Identify the employer, the specific conduct alleged to be an ULP (e.g., terminating an employee for protected concerted activity), and the dates involved.</li>
<li><strong>Regional investigation.</strong> An NLRB field attorney will investigate your charge. Both you and your employer will be interviewed. The regional director decides whether to issue a formal complaint (merits found) or dismiss the charge. You may appeal a dismissal to the NLRB General Counsel.</li>
<li><strong>If a complaint is issued.</strong> The case proceeds to a hearing before an NLRB Administrative Law Judge. Remedies for retaliation may include reinstatement, back pay, and a notice posting. The Board&#8217;s order is enforced by the applicable U.S. Court of Appeals.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing an ULP charge with the NLRB does not preclude filing claims under other federal statutes (e.g., SOX, FCA). However, remedies for the same underlying harm may overlap; consult an attorney to coordinate parallel claims and avoid double recovery arguments.',
    ],

    // ── 7. CFTC — Whistleblower Tip Submission ────────────────────────────

    [
        'title'               => 'CFTC Whistleblower Tip Submission',
        'slug'                => 'cftc-whistleblower-tip-submission',
        'agency_slug'         => 'cftc-whistleblower-program',
        'ws_proc_type'        => 'disclosure',
        'disclosure_types'    => [ 'securities-commodities-fraud' ],
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://www.whistleblower.gov/tipsubmission',
        // CFTC Rule 165.7 expressly allows anonymous submissions through an attorney.
        'ws_proc_identity_policy'      => 'anonymous',
        'ws_proc_intake_only'          => false,
        'ws_proc_deadline_days'        => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        // No CEA statute in the matrix — leave empty.
        'statute_slugs'                => [],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Gather evidence of the CEA violation.</strong> Collect documents, communications, and trading records evidencing the potential violation of the Commodity Exchange Act — manipulation, fraud, swap dealer misconduct, or other violations. Specific, documented evidence significantly improves your tip&#8217;s likelihood of generating an enforcement action.</li>
<li><strong>Submit your tip online.</strong> Use the CFTC&#8217;s online tip portal at whistleblower.gov. You may submit under your own name or anonymously. To maintain anonymity, you must be represented by an attorney who certifies your identity to the CFTC — anonymous tips are eligible for awards as long as your attorney identifies you before the award is paid.</li>
<li><strong>Preserve your records.</strong> Save copies of everything submitted. CFTC may contact you or your attorney for additional information during any resulting investigation.</li>
<li><strong>Monitor CFTC enforcement actions.</strong> CFTC does not notify tipsters of investigation status. If you believe an enforcement action is based substantially on your information, submit a CFTC Whistleblower Award Application (WB-APP) after the action is publicly announced.</li>
<li><strong>Award determination.</strong> If CFTC sanctions exceed $1 million and CFTC finds your tip contributed, you may receive 10–30% of collected sanctions. Award determinations may take years after the enforcement action closes.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Submitting a tip here does not constitute an anti-retaliation complaint. If you experienced adverse action for providing information to the CFTC, you may have a separate retaliation claim under CEA § 23(h)(1) in federal district court. That claim must be filed within 2 years of the retaliation.',
    ],

    // ── 8. IRS — Whistleblower Award Claim (Form 211) ─────────────────────

    [
        'title'               => 'IRS Whistleblower Award Claim (Form 211)',
        'slug'                => 'irs-form-211-award-claim',
        'agency_slug'         => 'irs-whistleblower-office',
        'ws_proc_type'        => 'disclosure',
        'disclosure_types'    => [ 'tax-evasion-fraud' ],
        // Form 211 is mailed to the IRS Whistleblower Office — no online portal.
        'ws_proc_entry_point' => 'mail',
        'ws_proc_intake_url'  => 'https://www.irs.gov/pub/irs-pdf/f211.pdf',
        'ws_proc_identity_policy'      => 'confidential',
        'ws_proc_intake_only'          => false,
        'ws_proc_deadline_days'        => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        // IRC § 7623 is not in the statute matrix — leave empty.
        'statute_slugs'                => [],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Confirm the threshold.</strong> The mandatory award program (15–30% of collected proceeds) applies when the tax, penalties, and interest in dispute exceed $2 million and, for individual taxpayer targets, the target&#8217;s gross income exceeded $200,000 in at least one year covered by the disclosure. If the amount is below these thresholds, a discretionary award of up to 15% may still apply under IRC § 7623(a).</li>
<li><strong>Download and complete IRS Form 211.</strong> Obtain Form 211 (Application for Award for Original Information) from the IRS website. Provide: the taxpayer&#8217;s name, address, and taxpayer identification number; a detailed description of the alleged tax law violation; the approximate amount of unpaid tax; and a description of the documents and information supporting your claim.</li>
<li><strong>Compile supporting documentation.</strong> Attach copies of all documents supporting your claim. Original documents are preferred where available. The quality and specificity of your documentation directly affects how the IRS assesses the value of your information.</li>
<li><strong>Mail your submission via certified mail.</strong> Send Form 211 and all supporting materials to the IRS Whistleblower Office, 1973 N. Rulon White Blvd., M/S 4110, Ogden, UT 84404. The IRS does not accept electronic Form 211 submissions. Use certified mail with return receipt to document your filing date and delivery.</li>
<li><strong>Wait for the IRS process.</strong> The IRS Whistleblower Office will acknowledge receipt and may request additional information. If the IRS opens an audit or examination based on your disclosure, the process can take several years. You will not receive updates on the investigation&#8217;s status.</li>
<li><strong>Award determination after collection.</strong> If the IRS collects additional taxes, penalties, and interest substantially based on your information, you will receive a preliminary award determination letter. You may contest the award amount by filing a petition in U.S. Tax Court within 30 days of the determination letter.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'The IRS Whistleblower program does not provide anti-retaliation protection. If you experience retaliation for reporting tax fraud, your civil remedy is a separate lawsuit in federal district court under IRC § 7623(d), which must be filed within 180 days of the adverse action.',
    ],

    // ── 9. EPA — Environmental Whistleblower Retaliation Complaint ────────

    [
        'title'               => 'EPA Environmental Whistleblower Retaliation Complaint',
        'slug'                => 'epa-environmental-retaliation-complaint',
        'agency_slug'         => 'epa-whistleblower-protection',
        'ws_proc_type'        => 'retaliation',
        'disclosure_types'    => [ 'environmental-protection', 'retaliation-protection' ],
        // EPA environmental retaliation complaints are filed with and investigated
        // by OSHA on behalf of EPA statutes — the intake portal belongs to OSHA.
        'ws_proc_entry_point' => 'online',
        'ws_proc_intake_url'  => 'https://www.osha.gov/workers/file-a-complaint',
        'ws_proc_identity_policy'      => 'identified',
        'ws_proc_intake_only'          => false,
        // 30 days is the deadline under Clean Air Act § 322, Clean Water Act § 507,
        // Safe Drinking Water Act § 1450(b), CERCLA § 110(b), TSCA § 23, and SWDA § 7001(b).
        'ws_proc_deadline_days'        => 30,
        'ws_proc_deadline_clock_start' => 'adverse_action',
        'ws_proc_prerequisites'        => false,
        'ws_proc_prerequisites_note'   => '',
        // EPA environmental statutes are not individually seeded in the matrix.
        'statute_slugs'                => [],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Act within 30 days — this is one of the shortest deadlines in federal law.</strong> Most EPA environmental whistleblower statutes (Clean Air Act § 322, Clean Water Act § 507, Safe Drinking Water Act § 1450(b), CERCLA § 110(b), TSCA § 23, SWDA § 7001(b)) require filing within 30 days of the adverse action. Missing this deadline bars your claim under the applicable statute. File immediately.</li>
<li><strong>File with OSHA, not the EPA.</strong> Environmental whistleblower retaliation complaints are filed with and investigated by OSHA&#8217;s Whistleblower Protection Program, acting under authority delegated by EPA. Use the OSHA online complaint form or contact your local OSHA area office. The EPA itself does not process these retaliation complaints.</li>
<li><strong>Identify your protected activity and the applicable statute.</strong> Describe specifically what environmental violation you reported (e.g., illegal discharge to a waterway under the Clean Water Act), to whom you reported it (internal management, EPA, state environmental agency), and what adverse action followed. Identify the statute you believe governs your claim — OSHA may need this to route your complaint correctly.</li>
<li><strong>OSHA investigates under the applicable EPA statute.</strong> OSHA will notify your employer, conduct interviews, and issue findings. Remedies may include reinstatement, back pay, attorney fees, and compensatory damages.</li>
<li><strong>Federal court option after OSHA inaction.</strong> Under several environmental statutes, if OSHA has not issued a final order within 30 days of the complaint, you may file a new civil action in federal district court. Consult an attorney about the timing and forum selection for your specific statute.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing under an environmental statute does not preclude filing under other applicable statutes (e.g., OSH Act § 11(c) if workplace safety was also involved). However, some statutes specify that filing with OSHA waives certain federal court rights. Confirm the specific statute&#8217;s election-of-remedies rules with an attorney.',
    ],

    // ── 10. DOJ — False Claims Act Qui Tam Complaint ──────────────────────

    [
        'title'               => 'False Claims Act Qui Tam Complaint',
        'slug'                => 'doj-false-claims-act-qui-tam',
        'agency_slug'         => 'doj-false-claims-act',
        'ws_proc_type'        => 'both',
        'disclosure_types'    => [ 'procurement-spending-fraud', 'healthcare-medicare-fraud', 'retaliation-protection' ],
        // Qui tam complaints are filed in federal district court — not through
        // a government agency intake portal. The intake_url points to DOJ Civil Fraud
        // for initial inquiries; the actual complaint is filed with the court clerk.
        'ws_proc_entry_point' => 'mail',
        'ws_proc_intake_url'  => 'https://www.justice.gov/civil/fraud-section/contact-fraud-section',
        'ws_proc_identity_policy'      => 'identified',
        'ws_proc_intake_only'          => false,
        // No hard filing deadline in the traditional sense, but the FCA has a
        // 6-year statute of limitations from the date of the violation and a
        // 3-year window from when the government knew or should have known,
        // not to exceed 10 years total (31 U.S.C. § 3731(b)).
        'ws_proc_deadline_days'        => 0,
        'ws_proc_deadline_clock_start' => '',
        'ws_proc_prerequisites'        => true,
        'ws_proc_prerequisites_note'   => 'You must retain an attorney before filing — the False Claims Act does not permit pro se qui tam relators. The complaint must be filed in federal district court under seal (not served on the defendant) and served simultaneously on the U.S. Department of Justice through the U.S. Attorney\'s Office for the filing district. Your attorney will also prepare a written disclosure statement containing all supporting information about the fraud.',
        'statute_slugs'                => [ 'false-claims-act-qui-tam' ],
        'ws_proc_walkthrough'          => '<ol>
<li><strong>Retain a False Claims Act attorney immediately.</strong> The FCA requires all qui tam relators to be represented by counsel. Your attorney is essential for maintaining confidentiality, structuring the complaint, analyzing whether prior disclosure bars apply (if the government already knows about the fraud, your claim may be dismissed), and advising on the parallel retaliation claim under FCA § 3730(h).</li>
<li><strong>Gather and preserve evidence.</strong> Document the fraudulent scheme: contracts, invoices, billing records, emails, and any other materials showing that a person or company submitted false claims for payment to the federal government. Do not remove employer property without authorization — improper removal can expose you to counterclaims and compromise your case.</li>
<li><strong>Your attorney drafts the qui tam complaint and disclosure statement.</strong> The complaint is filed in federal district court under seal. The written disclosure statement (served on DOJ but not filed with the court) must contain all material evidence and information supporting the allegations. DOJ needs this to investigate without revealing your identity to the defendant.</li>
<li><strong>File under seal with the court and serve DOJ.</strong> The complaint is filed with the federal district court and served only on the U.S. Attorney&#8217;s Office for the district and the U.S. Attorney General. The defendant does not receive notice during the sealed period. You may not discuss the case publicly while it is under seal.</li>
<li><strong>Government investigation period.</strong> DOJ has 60 days to investigate, which is routinely extended by motion. Investigations can last months or years. During this period, DOJ interviews witnesses, reviews documents, and decides whether to intervene.</li>
<li><strong>Government decision.</strong> DOJ may: (a) intervene and take over prosecution — you receive 15–25% of recovered funds; (b) decline intervention — you may proceed on your own and receive 25–30%; or (c) move to dismiss. If DOJ intervenes, it controls the litigation, though you retain rights to participate. Settlements require court approval.</li>
<li><strong>Anti-retaliation protection (FCA § 3730(h)).</strong> If you experienced retaliation for investigating, filing, or supporting an FCA action, you have a separate anti-retaliation claim. File in federal district court within 3 years of the retaliation. This claim does not need to be filed under seal and is independent of the qui tam action.</li>
</ol>',
        'ws_proc_exclusivity_note'     => 'Filing a qui tam lawsuit initiates a sealed federal lawsuit — you generally cannot discuss the substance of the case publicly during the seal period without risking dismissal. If you have already filed an agency complaint (e.g., with OSHA under SOX) for the same underlying retaliation, discuss the interaction with your FCA attorney. Some courts have held that election-of-remedies principles may affect parallel claims for the same adverse action.',
    ],

];


// ════════════════════════════════════════════════════════════════════════════
// Helper: Resolve statute post IDs from slugs
//
// Returns an array of post IDs for jx-statute records whose post_name
// matches the given slugs. Silently skips any slug not found — the seeder
// will not fatal if a statute is not yet in the database.
//
// @param  string[]  $slugs  Array of jx-statute post slugs.
// @return int[]             Array of post IDs.
// ════════════════════════════════════════════════════════════════════════════

function ws_procedure_matrix_resolve_statute_ids( array $slugs ) {
    $ids = [];
    foreach ( $slugs as $slug ) {
        $post = get_page_by_path( $slug, OBJECT, 'jx-statute' );
        if ( $post && ! is_wp_error( $post ) ) {
            $ids[] = (int) $post->ID;
        }
    }
    return $ids;
}


// ════════════════════════════════════════════════════════════════════════════
// Seeder: ws_seed_procedure_matrix
// ════════════════════════════════════════════════════════════════════════════

function ws_seed_procedure_matrix() {
    global $_ws_procedure_matrix;

    // Resolve the US jurisdiction term — all seeded procedures are federal.
    $us_term = ws_jx_term_by_code( 'us' );
    if ( ! $us_term || is_wp_error( $us_term ) ) {
        return; // Jurisdiction taxonomy terms not yet seeded — bail.
    }
    $us_term_id = (int) $us_term->term_id;

    if ( ! defined( 'WS_MATRIX_SEEDING_IN_PROGRESS' ) ) {
        define( 'WS_MATRIX_SEEDING_IN_PROGRESS', true );
    }

    foreach ( $_ws_procedure_matrix as $proc ) {

        // ── Resolve parent agency ─────────────────────────────────────────

        $agency = get_page_by_path( $proc['agency_slug'], OBJECT, 'ws-agency' );
        if ( ! $agency || is_wp_error( $agency ) ) {
            // Parent agency not seeded yet — skip this procedure.
            // Bumping the gate version re-runs the seeder, which will retry.
            continue;
        }
        $agency_id = (int) $agency->ID;

        // ── Upsert the procedure post ──────────────────────────────────────

        $existing = get_page_by_path( $proc['slug'], OBJECT, 'ws-ag-procedure' );

        if ( $existing ) {
            $post_id = $existing->ID;
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => $proc['title'],
                'post_name'  => $proc['slug'],
            ] );
        } else {
            $post_id = wp_insert_post( [
                'post_title'  => $proc['title'],
                'post_name'   => $proc['slug'],
                'post_type'   => 'ws-ag-procedure',
                'post_status' => 'publish',
            ] );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        // ── Write scalar meta fields ───────────────────────────────────────

        $meta = [
            'ws_proc_agency_id'               => $agency_id,
            'ws_proc_type'                    => $proc['ws_proc_type'],
            'ws_proc_entry_point'             => $proc['ws_proc_entry_point'],
            'ws_proc_intake_url'              => $proc['ws_proc_intake_url'],
            'ws_proc_identity_policy'         => $proc['ws_proc_identity_policy'],
            'ws_proc_intake_only'             => $proc['ws_proc_intake_only'] ? 1 : 0,
            'ws_proc_deadline_days'           => (int) $proc['ws_proc_deadline_days'],
            'ws_proc_deadline_clock_start'    => $proc['ws_proc_deadline_clock_start'] ?? '',
            'ws_proc_prerequisites'           => $proc['ws_proc_prerequisites'] ? 1 : 0,
            'ws_proc_prerequisites_note'      => $proc['ws_proc_prerequisites_note'] ?? '',
            'ws_proc_walkthrough'             => $proc['ws_proc_walkthrough'] ?? '',
            'ws_proc_exclusivity_note'        => $proc['ws_proc_exclusivity_note'] ?? '',
        ];

        foreach ( $meta as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        // ── Assign jurisdiction (taxonomy table) ───────────────────────────
        // save_terms = 1 on the ACF field means WP taxonomy table is the source
        // of truth. wp_set_object_terms writes directly so the admin UI and
        // tax_query both see the assignment without a separate ACF save cycle.

        wp_set_object_terms( $post_id, [ $us_term_id ], WS_JURISDICTION_TAXONOMY );

        // ── Assign disclosure types (taxonomy table) ───────────────────────

        if ( ! empty( $proc['disclosure_types'] ) ) {
            ws_matrix_assign_terms( $post_id, $proc['disclosure_types'], 'ws_disclosure_type' );
        }

        // ── Link related statutes ──────────────────────────────────────────
        // Resolve slugs to post IDs and write as a serialized array so the
        // ws_get_procedures_for_statute() LIKE query finds them correctly.

        if ( ! empty( $proc['statute_slugs'] ) ) {
            $statute_ids = ws_procedure_matrix_resolve_statute_ids( $proc['statute_slugs'] );
            if ( ! empty( $statute_ids ) ) {
                update_post_meta( $post_id, 'ws_proc_statute_ids', $statute_ids );
                // Invalidate statute transients so the cross-reference panel
                // on jurisdiction pages reflects the new links immediately.
                foreach ( $statute_ids as $sid ) {
                    delete_transient( 'ws_statute_procs_' . $sid );
                }
            }
        }

        // ── Invalidate agency procedures transient ─────────────────────────

        delete_transient( 'ws_agency_procs_' . $agency_id );

        // ── Mark as seeded ─────────────────────────────────────────────────

        update_post_meta( $post_id, 'ws_matrix_source', 'procedure-matrix' );
    }
}


// ── Gate ──────────────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( get_option( 'ws_seeded_procedure_matrix' ) !== '1.0.0' ) {
        ws_seed_procedure_matrix();
        update_option( 'ws_seeded_procedure_matrix', '1.0.0' );
    }
} );
