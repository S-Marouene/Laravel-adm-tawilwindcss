<?php

// =========================================================
// Procedure details — Rescue and Relief Certificate
// (official content for شهادة نجدة وإنقاذ)
// =========================================================

return [
    'about_kicker' => "Rescue and Relief Certificate",
    'about_title'  => "Procedure Details",

    'tab_current'    => "Your Request",
    'tab_process'    => "The Procedure",
    'tab_docs'       => "Required Documents",
    'tab_fees'       => "Fees & Payment",
    'tab_conditions' => "Conditions & Steps",
    'tab_more'       => "Additional Info",

    // ── Tab 1: current request ──
    'empty_draft_title' => "Create a New Request",
    'empty_draft_desc'  => "No request in progress. Click here to fill in the details — your progress will be saved as a draft if you don't submit immediately.",
    'empty_draft_aria'  => "Create a new request — click to open the application form",
    'create_new_draft'  => "Create a New Request",
    'draft_saved_note'  => "If you start the process without submitting your request, your progress will be saved as a draft.",

    // ── Tab 2: the procedure ──
    'legal_name_label'    => "Official Legal Name",
    'legal_name_ar_label' => "Legal Name (Arabic)",
    'legal_name_ar_value' => "شهادة نجدة وإنقاذ",
    'receiving_label'     => "Administration responsible for receiving the request",
    'providing_label'     => "Administration responsible for delivering the service",
    'level_regional'      => "Regional",
    'receiving_entities'  => [
        "Regional Civil Protection Directorate of Sousse",
        "Regional Civil Protection Directorate of Ariana",
    ],
    'providing_entities'  => [
        "Regional Civil Protection Directorate of Tunis",
        "Regional Civil Protection Directorate of Ariana",
    ],

    // ── Tab 3: required documents ──
    'tag_form'        => "Form to fill",
    'tag_required'    => "Required document",
    'tag_conditional' => "Conditional",
    'docs'            => [
        ['text' => "Copy of the National Identity Card of the victim",
         'tags' => ['required']],
        ['text' => "Copy of the National Identity Card of the father or mother (if the victim is a child under 18 years old)",
         'tags' => ['required']],
        ['text' => "Copy of the spouse's National Identity Card (muharram) or new marriage certificate + copy of the husband's National Identity Card (for certificate on behalf of a spouse)",
         'tags' => ['required']],
        ['text' => "Official rescue and relief certificate application form",
         'tags' => ['form']],
    ],

    // ── Tab 4: fees ──
    'fees_payable_label' => "Paid procedure?",
    'fees_payable_value' => "Free",
    'fees_type_label'    => "Fee type",
    'fees_type_value'    => "None",
    'fees_proof_label'   => "Proof of payment",
    'fees_proof_value'   => "None required",

    // ── Tab 5: conditions & steps ──
    'conditions_title' => "Required Conditions",
    'conditions_text'  => "Intervention by Civil Protection units to rescue and save people and protect property following accidents and fires.",
    'legals_title'     => "Legal References",
    'legals'           => [
        ['type' => "Text", 'prefix' => "No specific legal text", 'text' => "—"],
    ],
    'delay_label'      => "Processing time",
    'delay_value'      => "7 days to 1 month",
    'steps_title'      => "Processing Steps",
    'steps'            => [
        "Submit the request",
        "Verify data against the rescue operation records",
        "Issue the certificate",
    ],
    'tracking_label'   => "Follow-up / Notification method",
    'tracking_value'   => "By phone",

    // ── Tab 6: additional info ──
    'validity_label' => "Procedure validity period",
    'validity_value' => "Permanent",
];
