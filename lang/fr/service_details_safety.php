<?php

// =========================================================
// Détails de la démarche — Validation du dossier de sécurité
// (services jumeaux : bâtiments publics / habitation grande hauteur
//  et établissements dangereux, insalubres ou incommodes)
// Procédure gratuite — délai 10 jours — suivi par téléphone — validité permanente
// =========================================================

return [
    'about_kicker' => "Validation du dossier de sécurité",
    'about_title'  => "Détails de la démarche",

    'tab_current'    => "Votre dossier",
    'tab_process'    => "La démarche",
    'tab_docs'       => "Documents requis",
    'tab_fees'       => "Frais & paiement",
    'tab_conditions' => "Conditions & étapes",
    'tab_more'       => "Infos complémentaires",

    // ── Onglet 1 : dossier en cours ──
    'empty_draft_title' => "Créer un nouveau dossier",
    'empty_draft_desc'  => "Aucun brouillon en cours. Cliquez ici pour compléter les informations de votre démarche — si vous démarrez le processus sans encore soumettre votre demande, votre progression restera enregistrée comme brouillon.",
    'empty_draft_aria'  => "Créer un nouveau dossier — cliquez pour ouvrir le formulaire de demande",
    'create_new_draft'  => "Créer un nouveau dossier",
    'draft_saved_note'  => "Si vous démarrez la démarche sans encore soumettre votre demande, votre progression restera enregistrée comme brouillon.",

    // ── Onglet 2 : la démarche ──
    'legal_name_label'    => "Intitulé légal officiel",
    'legal_name_ar_label' => "Intitulé légal (en arabe)",
    // Intitulé légal arabe du service (défini côté composant selon la variante)
    'receiving_label'     => "Administration chargée de recevoir la demande",
    'providing_label'     => "Administration chargée de délivrer la demande",
    'level_regional'      => "Échelle régionale",
    'receiving_entities'  => [
        "Direction régionale de la Protection civile d'Ariana",
        "Direction régionale de la Protection civile du Kef",
    ],
    'providing_entities'  => [
        "Direction régionale de la Protection civile de Tunis",
        "Direction régionale de la Protection civile d'Ariana",
    ],

    // ── Onglet 3 : documents requis (par variante) ──
    'tag_form'     => "Formulaire à remplir",
    'tag_required' => "Pièce requise",
    'tag_renewal'  => "Cas de renouvellement",
    'docs_habitation' => [
        ['text' => "Exemple de plan de situation reporté sur l'extrait du plan d'aménagement", 'tags' => ['required']],
        ['text' => "Exemple de plan de masse 1/500",                                        'tags' => ['required']],
        ['text' => "Exemple de plan des abris 1/200",                                       'tags' => ['required']],
        ['text' => "Exemples de plans des étages, coupes et façades 1/100",                 'tags' => ['required']],
        ['text' => "Étude de sécurité",                                                     'tags' => ['required']],
    ],
    'docs_hazardous' => [
        ['text' => "Décision d'autorisation d'ouverture d'un établissement classé",          'tags' => ['required']],
        ['text' => "Résumé non technique de l'étude des dangers",                            'tags' => ['required']],
        ['text' => "Exemple de plan de situation 1/1000",                                    'tags' => ['required']],
        ['text' => "Exemples de plans d'architecture 1/100",                                 'tags' => ['required']],
        ['text' => "Exemple de plan de masse 1/200",                                         'tags' => ['required']],
    ],

    // ── Onglet 4 : frais — procédure gratuite ──
    'fees_payable_label' => "Procédure payante ?",
    'fees_payable_value' => "Non — gratuite",
    'fees_type_label'    => "Type de redevance",
    'fees_type_value'    => "Aucune",
    'fees_proof_label'   => "Justificatif de paiement",
    'fees_proof_value'   => "Non exigé",

    // ── Onglet 5 : conditions & étapes ──
    'conditions_title' => "Conditions requises",
    'conditions_text_habitation' => "Aucune condition spécifique requise — le dépôt d'un dossier de sécurité complet suffit.",
    'conditions_text_hazardous'  => "Inspection sur site de l'établissement avant l'approbation du dossier de sécurité.",
    'conditions_text'  => "La conformité à l'ensemble des règles et mesures de sécurité et de protection contre les risques d'incendie et de panique dans les bâtiments, constatée sur le dossier déposé avec la demande de permis de construire.",
    'legals_title'     => "Références juridiques",
    'legals'           => [
        ['type' => "Loi",    'prefix' => "Loi",    'text' => "Loi n° 11 de l'année 2009, du 2 mars 2009, portant promulgation du Code de la protection des bâtiments contre les risques d'incendie, d'explosion et de panique"],
        ['type' => "Arrêté", 'prefix' => "Arrêté", 'text' => "Arrêté du ministre de l'Intérieur du 16 septembre 2013 relatif aux documents constituant le dossier de sécurité joint au dossier de la demande de permis de construire"],
    ],
    'delay_label'      => "Délai de traitement",
    'delay_value'      => "10 jours",
    'steps_title'      => "Étapes du traitement",
    'steps'            => [
        "Dépôt de la demande",
        "Constatations de terrain (معاينة ميدانية)",
        "Étude du dossier",
        "Approbation (المصادقة)",
    ],
    'tracking_label' => "Suivi / notification de l'aboutiment de la demande",
    'tracking_value' => "Par téléphone",

    // ── Onglet 6 : informations complémentaires ──
    'validity_label' => "Durée de validité de la démarche",
    'validity_value' => "Permanente",
];
