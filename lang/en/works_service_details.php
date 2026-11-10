<?php

// =========================================================
// Service details — Works notification (no building permit required)
// =========================================================

return [
    'about_kicker' => "Works Notification",
    'about_title'  => "Service Details",

    'tab_current'    => "Your notification",
    'tab_process'    => "The process",
    'tab_docs'       => "Required documents",
    'tab_fees'       => "Fees & payment",
    'tab_conditions' => "Conditions & steps",
    'tab_more'       => "Additional info",

    // ── Tab 1 : current notification ──
    'empty_draft_title' => "Create a new notification",
    'empty_draft_desc'  => "No notification in progress. Click here to fill in the information — your progress will be saved as a draft if you do not submit immediately.",
    'empty_draft_aria'  => "Create a new notification — click to open the form",
    'create_new_draft'  => "Create a new notification",
    'draft_saved_note'  => "If you start the procedure without submitting your notification, your progress will be saved as a draft.",

    // ── Tab 2 : the process ──
    'legal_name_label'    => "Official legal name",
    'legal_name_ar_label' => "Legal name (Arabic)",
    'legal_name_ar_value' => "الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء",
    'receiving_label'     => "Administration responsible for receiving the application",
    'providing_label'     => "Administration responsible for delivering the service",
    'level_regional'      => "Regional level",
    'receiving_entities'  => [
        "Regional Directorate of Civil Protection – Sousse",
        "Regional Directorate of Civil Protection – Ariana",
    ],
    'providing_entities'  => [
        "Regional Directorate of Civil Protection – Tunis",
        "Regional Directorate of Civil Protection – Ariana",
    ],

    // ── Tab 3 : required documents ──
    'tag_form'        => "Form to fill",
    'tag_required'    => "Required document",
    'tag_conditional' => "Conditional",
    'docs'            => [
        ['text' => "Consultation card to be collected from the Regional Directorate of Civil Protection or the relevant Civil Protection unit",
         'tags' => ['form']],
        ['text' => "Copy of the national ID card of the building owner (natural person) or legal representative (legal entity)",
         'tags' => ['required']],
        ['text' => "Extract of the legal notice in the Official Gazette of the Tunisian Republic establishing the legal entity",
         'tags' => ['required']],
    ],

    // ── Tab 4 : fees ──
    'fees_payable_label' => "Is the procedure paid?",
    'fees_payable_value' => "Free of charge",
    'fees_type_label'    => "Fee type",
    'fees_type_value'    => "None",
    'fees_proof_label'   => "Proof of payment",
    'fees_proof_value'   => "None",

    // ── Tab 5 : conditions & steps ──
    'conditions_title' => "Required conditions",
    'conditions_text'  => "No prior conditions are required to carry out this procedure.",
    'legals_title'     => "Legal references",
    'legals'           => [
        ['type' => "Law", 'prefix' => "Law No. 11 of 2009 dated 2 March 2009",
         'text' => "Issuing the Code of Safety and Prevention of Risks of Fire, Explosion and Panic in Buildings"],
        ['type' => "Order", 'prefix' => "Order of the Minister of the Interior and the Minister of Equipment dated 28 March 2014",
         'text' => "Specifying the cases and procedures for informing Civil Protection of works not requiring a building permit"],
    ],
    'delay_label'      => "Processing time",
    'delay_value'      => "15 days",
    'steps_title'      => "Processing steps",
    'steps'            => [
        "Submit the notification and required documents",
        "Review by the Regional Directorate (15-day deadline)",
        "Applicant notified of the outcome by phone",
    ],
    'tracking_label'   => "Follow-up / notification",
    'tracking_value'   => "By phone",

    // ── Tab 6 : additional info ──
    'validity_label' => "Validity period",
    'validity_value' => "Permanent",
];
