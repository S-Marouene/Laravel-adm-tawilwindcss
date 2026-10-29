<?php

// =========================================================
// Service details — Prevention Certificate
// (official "Digital Desk" content, cf. design/)
// =========================================================

return [
    'about_kicker' => "Prevention Certificate",
    'about_title'  => "Service details",

    'tab_current'    => "Your file",
    'tab_process'    => "The procedure",
    'tab_docs'       => "Required documents",
    'tab_fees'       => "Fees & payment",
    'tab_conditions' => "Conditions & stages",
    'tab_more'       => "Additional info",

    // ── Tab 1: current file ──
    'empty_draft_title' => "No draft in progress",
    'empty_draft_desc'  => "Complete the form below to start your request. Your progress is kept as a draft.",

    // ── Tab 2: the procedure ──
    'legal_name_label'    => "Official legal title",
    'legal_name_ar_label' => "Legal title (in Arabic)",
    'legal_name_ar_value' => "شهادة الوقاية",
    'receiving_label'     => "Administration receiving the request",
    'providing_label'     => "Administration issuing the request",
    'level_regional'      => "Regional level",
    'receiving_entities'  => [
        "Regional Directorate of Civil Protection of Sousse",
        "Regional Directorate of Civil Protection of Ariana",
    ],
    'providing_entities'  => [
        "Regional Directorate of Civil Protection of Tunis",
        "Regional Directorate of Civil Protection of Ariana",
    ],

    // ── Tab 3: required documents ──
    'tag_form'     => "Form to fill in",
    'tag_required' => "Required document",
    'tag_renewal'  => "Renewal case",
    'docs'         => [
        ['text' => "Information sheet, to be collected from the regional directorate of Civil Protection or the relevant brigade headquarters",          'tags' => ['form']],
        ['text' => "Copy of the national ID card of the applicant (natural person) or of the legal representative (legal entity)",                        'tags' => ['required']],
        ['text' => "Extract of the legal notice published in the Official Gazette of the Tunisian Republic relating to the establishment of the legal entity", 'tags' => ['required']],
        ['text' => "Any title proving the applicant's entitlement to act on the building (excluding dwellings of the 3rd and 4th categories)",            'tags' => ['required']],
        ['text' => "Site plan showing entrances and parts of the building, or any equivalent document",                                                  'tags' => ['required']],
        ['text' => "Receipt of the fee due upon collection of the prevention certificate from the Civil Protection services",                             'tags' => ['required']],
        ['text' => "Decision authorizing the opening of a classified establishment (for buildings housing hazardous, unhealthy or nuisance establishments)", 'tags' => ['required']],
        ['text' => "Information sheet, to be collected (in case of renewal)",                                                                            'tags' => ['required', 'renewal']],
        ['text' => "Copy of the requested prevention certificate (in case of renewal)",                                                                  'tags' => ['required', 'renewal']],
    ],

    // ── Tab 4: fees ──
    'fees_payable_label' => "Paid procedure?",
    'fees_payable_value' => "Paid",
    'fees_type_label'    => "Type of fee",
    'fees_type_value'    => "None",
    'fees_proof_label'   => "Proof of payment",
    'fees_proof_value'   => "Quittance",

    // ── Tab 5: conditions & stages ──
    'conditions_title' => "Required conditions",
    'conditions_text'  => "Compliance with all safety rules and measures protecting buildings against fire and panic hazards.",
    'legals_title'     => "Legal references",
    'legals'           => [
        ['type' => "Law",   'prefix' => "Law",            'text' => "Law No. 11 of the year 2009, dated 2 March 2009, promulgating the Code of building protection against fire, explosion and panic hazards"],
        ['type' => "Order", 'prefix' => "Order",          'text' => "Order of the Minister of the Interior dated 16 September 2013 on the documents constituting the file for obtaining the prevention certificate or renewing it, on the certificate model and on its dedicated register"],
        ['type' => "Order", 'prefix' => "Joint order",    'text' => "Joint order of the Minister of the Interior and the Minister of Finance dated 25 September 2013 setting the amount of the fee payable for issuing the prevention certificate"],
    ],
    'delay_label'      => "Processing time",
    'delay_value'      => "15 days",
    'steps_title'      => "Processing stages",
    'steps'            => [
        "Filing of the request",
        "Verification of documents",
        "On-site findings (visit)",
        "Issuance of the certificate",
    ],
    'tracking_label' => "Follow-up / notification of the request's outcome",
    'tracking_value' => "By telephone",

    // ── Tab 6: additional information ──
    'validity_label' => "Validity period",
    'validity_value' => "Two years (for the other buildings)",
];
