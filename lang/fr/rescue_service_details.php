<?php

// =========================================================
// Détails de la démarche — Certificat de secours et de sauvetage
// (contenu officiel « شهادة نجدة وإنقاذ »)
// =========================================================

return [
    'about_kicker' => "Certificat de secours et de sauvetage",
    'about_title'  => "Détails de la démarche",

    'tab_current'    => "Votre demande",
    'tab_process'    => "La démarche",
    'tab_docs'       => "Documents requis",
    'tab_fees'       => "Frais & paiement",
    'tab_conditions' => "Conditions & étapes",
    'tab_more'       => "Infos complémentaires",

    // ── Onglet 1 : demande en cours ──
    'empty_draft_title' => "Créer une nouvelle demande",
    'empty_draft_desc'  => "Aucune demande en cours. Cliquez ici pour compléter les informations — votre progression sera enregistrée comme brouillon si vous ne soumettez pas immédiatement.",
    'empty_draft_aria'  => "Créer une nouvelle demande — cliquez pour ouvrir le formulaire",
    'create_new_draft'  => "Créer une nouvelle demande",
    'draft_saved_note'  => "Si vous démarrez la démarche sans encore soumettre votre demande, votre progression restera enregistrée comme brouillon.",

    // ── Onglet 2 : la démarche ──
    'legal_name_label'    => "Intitulé légal officiel",
    'legal_name_ar_label' => "Intitulé légal (en arabe)",
    'legal_name_ar_value' => "شهادة نجدة وإنقاذ",
    'receiving_label'     => "Administration chargée de recevoir la demande",
    'providing_label'     => "Administration chargée de délivrer la demande",
    'level_regional'      => "Échelle régionale",
    'receiving_entities'  => [
        "Direction régionale de la Protection civile de Sousse",
        "Direction régionale de la Protection civile d'Ariana",
    ],
    'providing_entities'  => [
        "Direction régionale de la Protection civile de Tunis",
        "Direction régionale de la Protection civile d'Ariana",
    ],

    // ── Onglet 3 : documents requis ──
    'tag_form'     => "Formulaire à remplir",
    'tag_required' => "Pièce requise",
    'tag_conditional' => "Conditionnel",
    'docs'         => [
        ['text' => "Copie de la carte d'identité nationale de la victime",
         'tags' => ['required']],
        ['text' => "Copie de la carte d'identité nationale du père ou de la mère (si la victime est un enfant de moins de 18 ans)",
         'tags' => ['required']],
        ['text' => "Pour la demande de certificat au nom du conjoint : copie de la CIN de l'épouse (muharram) ou attestation de nouveau mariage + copie de la CIN de l'époux",
         'tags' => ['required']],
        ['text' => "Formulaire de demande de certificat de secours et de sauvetage (imprimé officiel)",
         'tags' => ['form']],
    ],

    // ── Onglet 4 : frais ──
    'fees_payable_label' => "Procédure payante ?",
    'fees_payable_value' => "Gratuite",
    'fees_type_label'    => "Redevance",
    'fees_type_value'    => "Aucune",
    'fees_proof_label'   => "Justificatif",
    'fees_proof_value'   => "Aucun",

    // ── Onglet 5 : conditions & étapes ──
    'conditions_title' => "Conditions requises",
    'conditions_text'  => "Intervention des unités de la Protection Civile pour le secours et le sauvetage des personnes et la protection des biens à la suite d'accidents et d'incendies.",
    'legals_title'     => "Références juridiques",
    'legals'           => [
        ['type' => "Texte", 'prefix' => "Aucun texte particulier", 'text' => "—"],
    ],
    'delay_label'      => "Délai de traitement",
    'delay_value'      => "7 jours à 1 mois",
    'steps_title'      => "Étapes du traitement",
    'steps'            => [
        "Dépôt de la demande",
        "Vérification des données par rapport à l'opération de secours",
        "Remise du certificat",
    ],
    'tracking_label'   => "Suivi / notification",
    'tracking_value'   => "Par téléphone",

    // ── Onglet 6 : informations complémentaires ──
    'validity_label' => "Durée de validité",
    'validity_value' => "Permanente",
];
