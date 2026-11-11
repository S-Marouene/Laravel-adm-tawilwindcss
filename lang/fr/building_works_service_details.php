<?php

// =========================================================
// Détails de la démarche — Déclaration de travaux ne nécessitant pas de permis de bâtir
// (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء)
// =========================================================

return [
    'about_kicker' => "Déclaration de travaux",
    'about_title'  => "Détails de la démarche",

    'tab_current'    => "Votre déclaration",
    'tab_process'    => "La démarche",
    'tab_docs'       => "Documents requis",
    'tab_fees'       => "Frais & paiement",
    'tab_conditions' => "Conditions & étapes",
    'tab_more'       => "Infos complémentaires",

    // ── Onglet 1 : déclaration en cours ──
    'empty_draft_title' => "Créer une nouvelle déclaration",
    'empty_draft_desc'  => "Aucune déclaration en cours. Cliquez ici pour compléter les informations — votre progression sera enregistrée comme brouillon si vous ne soumettez pas immédiatement.",
    'empty_draft_aria'  => "Créer une nouvelle déclaration — cliquez pour ouvrir le formulaire",
    'create_new_draft'  => "Créer une nouvelle déclaration",
    'draft_saved_note'  => "Si vous démarrez la démarche sans encore soumettre votre déclaration, votre progression restera enregistrée comme brouillon.",

    // ── Onglet 2 : la démarche ──
    'legal_name_label'    => "Intitulé légal officiel",
    'legal_name_ar_label' => "Intitulé légal (en arabe)",
    'legal_name_ar_value' => "الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء",
    'receiving_label'     => "Administration chargée de recevoir la déclaration",
    'providing_label'     => "Administration chargée d'instruire la déclaration",
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
    'tag_form'        => "Formulaire à remplir",
    'tag_required'    => "Pièce requise",
    'tag_conditional' => "Conditionnel",
    'docs'            => [
        ['text' => "Fiche consultative à retirer du siège de la Direction Régionale de la Protection Civile ou du siège de l'unité concernée",
         'tags' => ['form']],
        ['text' => "Copie de la CIN du propriétaire du bâtiment (personne physique) ou du représentant légal (personne morale)",
         'tags' => ['required']],
        ['text' => "Extrait de l'annonce légale au Journal Officiel de la République Tunisienne constitutive de la personne morale",
         'tags' => ['required']],
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
    'conditions_text'  => "Aucune condition préalable n'est requise pour effectuer cette démarche.",
    'legals_title'     => "Références juridiques",
    'legals'           => [
        ['type' => "Loi", 'prefix' => "Loi n° 11 de 2009 du 2 mars 2009",
         'text' => "Relative à la promulgation du code de la sécurité et de la prévention des risques d'incendie, d'explosion et de panique dans les bâtiments"],
        ['type' => "Arrêté", 'prefix' => "Arrêté du ministre de l'Intérieur et du ministre de l'Équipement du 28 mars 2014",
         'text' => "Relatif à la fixation des cas et des procédures d'information de la Protection Civile concernant les travaux ne nécessitant pas de permis de bâtir"],
    ],
    'delay_label'      => "Délai d'instruction",
    'delay_value'      => "15 jours",
    'steps_title'      => "Étapes du traitement",
    'steps'            => [
        "Dépôt de la déclaration et des documents requis",
        "Examen de la déclaration par la Direction Régionale (délai : 15 jours)",
        "Information de l'issue au déclarant par téléphone",
    ],
    'tracking_label'   => "Suivi / information",
    'tracking_value'   => "Par téléphone",

    // ── Onglet 6 : informations complémentaires ──
    'validity_label' => "Durée de validité",
    'validity_value' => "Permanente",
];
