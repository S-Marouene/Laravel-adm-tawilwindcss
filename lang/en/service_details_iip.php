<?php

return [
    'about_kicker' => 'Internal Intervention Plan Approval',
    'about_title'  => 'Procedure details',

    'tab_current'    => 'Your dossier',
    'tab_process'    => 'The procedure',
    'tab_docs'       => 'Required documents',
    'tab_fees'       => 'Fees',
    'tab_conditions' => 'Conditions & stages',
    'tab_more'       => 'Additional information',

    'empty_draft_title' => 'Create a new application',
    'empty_draft_desc'  => 'No draft in progress. Click here to start your application.',
    'empty_draft_aria'  => 'Create a new application',
    'create_new_draft'  => 'Create a new application',
    'draft_saved_note'  => 'If you start without submitting, your progress is saved as a draft.',

    'legal_name_label'    => 'Official legal name',
    'legal_name_ar_label' => 'Legal name in Arabic',
    'legal_name_ar_value' => 'المصادقة على مخطط التدخل الداخلي',
    'receiving_label'     => 'Administration receiving the request',
    'providing_label'     => 'Administration delivering the service',
    'level_central'       => 'Central',
    'level_regional'      => 'Regional',
    'receiving_entities'  => [
        'Central Directorate of the National Civil Protection Office',
        'Regional Directorate of Civil Protection of Ariana',
        'Regional Directorate of Civil Protection of Le Kef',
    ],
    'providing_entities'  => [
        'Central Directorate of the National Civil Protection Office',
        'Regional Directorate of Civil Protection of Tunis',
        'Regional Directorate of Civil Protection of Ariana',
    ],

    'tag_form'        => 'Form',
    'tag_required'    => 'Required document',
    'tag_conditional' => 'Conditional',
    'docs'            => [
        ['text' => 'Copy of the authorization decision for opening a classified establishment (cat. 2–5)', 'tags' => ['required']],
        ['text' => 'Copy of the non-technical summary of the project hazard study', 'tags' => ['required']],
        ['text' => 'Site plan 1/1000 showing the establishment\'s environment (buildings, roads, water points, hospitals, schools, airports, ports…)', 'tags' => ['required']],
        ['text' => 'Architectural plans 1/100 identifying building components and room allocation', 'tags' => ['required']],
        ['text' => 'Overall plan 1/200 showing machinery, storage tanks, networks, safety equipment, emergency exits and first-aid means', 'tags' => ['required']],
        ['text' => 'Declaration for class-3 classified establishments — waiver of items 2, 3 and 4', 'tags' => ['conditional']],
    ],

    'fees_payable_label' => 'Paid procedure?',
    'fees_payable_value' => 'No — free of charge',
    'fees_type_label'    => 'Fee type',
    'fees_type_value'    => 'None',
    'fees_proof_label'   => 'Proof of payment',
    'fees_proof_value'   => 'Not required',

    'conditions_title' => 'Required conditions',
    'conditions_text'  => 'Compliance with the reference formats defined by the ministerial order.',
    'legals_title'     => 'Legal references',
    'legals'           => [
        ['type' => 'Law',   'prefix' => 'Law',   'text' => 'Law No. 11 of 2009 (2 March 2009) promulgating the code on safety and prevention of fire, explosion and panic risks in buildings'],
        ['type' => 'Order', 'prefix' => 'Order', 'text' => 'Order of the Minister of Interior (16 September 2013) on the content of the internal intervention plan'],
        ['type' => 'Order', 'prefix' => 'Order', 'text' => 'Joint Order of the Ministers of Interior and Industry (20 February 2010) on reference elements for hazard studies and internal emergency plans'],
    ],
    'delay_label'  => 'Processing deadline',
    'delay_value'  => 'No regulatory deadline',
    'steps_title'  => 'Procedure stages',
    'steps'        => [
        'Application submission',
        'After approval of the safety study or hazard study',
        'Validation (authentication)',
    ],
    'tracking_label' => 'Follow-up / notification method',
    'tracking_value' => 'By ordinary mail',

    'validity_label' => 'Validity period',
    'validity_value' => 'Annual',
];
