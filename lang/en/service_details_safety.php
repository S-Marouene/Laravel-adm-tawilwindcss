<?php

// =========================================================
// Service details — Safety file approval
// (twin services: public / high-rise residential buildings and
//  hazardous, unhealthy or nuisance establishments)
// Free procedure — 10-day delay — phone tracking — permanent validity
// =========================================================

return [
    'about_kicker' => "Safety file approval",
    'about_title'  => "Service details",

    'tab_current'    => "Your file",
    'tab_process'    => "The procedure",
    'tab_docs'       => "Required documents",
    'tab_fees'       => "Fees & payment",
    'tab_conditions' => "Conditions & stages",
    'tab_more'       => "Additional info",

    // ── Tab 1: current file ──
    'empty_draft_title' => "Create a new request",
    'empty_draft_desc'  => "No draft in progress. Click here to fill in your request details — if you start the process without submitting your request yet, your progress is kept as a draft.",
    'empty_draft_aria'  => "Create a new request — click to open the request form",
    'create_new_draft'  => "Create a new request",
    'draft_saved_note'  => "If you start the process without submitting your request yet, your progress is kept as a draft.",

    // ── Tab 2: the procedure ──
    'legal_name_label'    => "Official legal title",
    'legal_name_ar_label' => "Legal title (in Arabic)",
    // The Arabic legal title is resolved per variant inside the component
    'receiving_label'     => "Administration receiving the request",
    'providing_label'     => "Administration issuing the request",
    'level_regional'      => "Regional level",
    'receiving_entities'  => [
        "Regional Directorate of Civil Protection of Ariana",
        "Regional Directorate of Civil Protection of Le Kef",
    ],
    'providing_entities'  => [
        "Regional Directorate of Civil Protection of Tunis",
        "Regional Directorate of Civil Protection of Ariana",
    ],

    // ── Tab 3: required documents (per variant) ──
    'tag_form'     => "Form to fill in",
    'tag_required' => "Required document",
    'tag_renewal'  => "Renewal case",
    'docs_habitation' => [
        ['text' => "Site plan example drawn on the urban planning map extract", 'tags' => ['required']],
        ['text' => "Site layout plan example 1/500",                            'tags' => ['required']],
        ['text' => "Shelter locations plan example 1/200",                      'tags' => ['required']],
        ['text' => "Floor plans, sections and facades examples 1/100",          'tags' => ['required']],
        ['text' => "Safety study",                                              'tags' => ['required']],
    ],
    'docs_hazardous' => [
        ['text' => "Decision authorizing the opening of a classified establishment", 'tags' => ['required']],
        ['text' => "Non-technical summary of the hazard study",                      'tags' => ['required']],
        ['text' => "Site plan example 1/1000",                                       'tags' => ['required']],
        ['text' => "Architectural plans examples 1/100",                             'tags' => ['required']],
        ['text' => "Site layout plan example 1/200",                                 'tags' => ['required']],
    ],

    // ── Tab 4: fees — free procedure ──
    'fees_payable_label' => "Paid procedure?",
    'fees_payable_value' => "No — free",
    'fees_type_label'    => "Type of fee",
    'fees_type_value'    => "None",
    'fees_proof_label'   => "Proof of payment",
    'fees_proof_value'   => "Not required",

    // ── Tab 5: conditions & stages ──
    'conditions_title' => "Required conditions",
    'conditions_text_habitation' => "No specific required conditions — depositing a complete safety file is sufficient.",
    'conditions_text_hazardous'  => "On-site inspection of the establishment before the safety file is approved.",
    'conditions_text'  => "Compliance with all safety rules and measures protecting buildings against fire and panic hazards, verified through the safety file attached to the building permit application.",
    'legals_title'     => "Legal references",
    'legals'           => [
        ['type' => "Law",   'prefix' => "Law",   'text' => "Law No. 11 of the year 2009, dated 2 March 2009, promulgating the Code of building protection against fire, explosion and panic hazards"],
        ['type' => "Order", 'prefix' => "Order", 'text' => "Order of the Minister of the Interior dated 16 September 2013 on the documents constituting the safety file attached to the building permit application"],
    ],
    'delay_label'      => "Processing time",
    'delay_value'      => "10 days",
    'steps_title'      => "Processing stages",
    'steps'            => [
        "Filing of the request",
        "Site inspection (معاينة ميدانية)",
        "File study",
        "Approval (المصادقة)",
    ],
    'tracking_label' => "Follow-up / notification of the request's outcome",
    'tracking_value' => "By telephone",

    // ── Tab 6: additional information ──
    'validity_label' => "Validity period",
    'validity_value' => "Permanent",
];
