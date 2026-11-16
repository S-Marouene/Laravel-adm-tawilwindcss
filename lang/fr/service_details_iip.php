<?php

return [
    'about_kicker' => 'Validation du plan d\'intervention interne',
    'about_title'  => 'Détails de la démarche',

    'tab_current'    => 'Votre dossier',
    'tab_process'    => 'La démarche',
    'tab_docs'       => 'Documents requis',
    'tab_fees'       => 'Frais',
    'tab_conditions' => 'Conditions & étapes',
    'tab_more'       => 'Informations complémentaires',

    'empty_draft_title' => 'Créer un nouveau dossier',
    'empty_draft_desc'  => 'Aucun brouillon en cours. Cliquez ici pour démarrer votre demande.',
    'empty_draft_aria'  => 'Créer un nouveau dossier',
    'create_new_draft'  => 'Créer un nouveau dossier',
    'draft_saved_note'  => 'Si vous commencez sans soumettre, votre progression est sauvegardée comme brouillon.',

    'legal_name_label'    => 'Dénomination légale officielle',
    'legal_name_ar_label' => 'Dénomination légale en arabe',
    'legal_name_ar_value' => 'المصادقة على مخطط التدخل الداخلي',
    'receiving_label'     => 'Administration chargée de recevoir la demande',
    'providing_label'     => 'Administration chargée de délivrer le service',
    'level_central'       => 'Central',
    'level_regional'      => 'Régional',
    'receiving_entities'  => [
        'Direction Centrale de l\'Office National de la Protection Civile',
        'Direction Régionale de la Protection Civile d\'Ariana',
        'Direction Régionale de la Protection Civile du Kef',
    ],
    'providing_entities'  => [
        'Direction Centrale de l\'Office National de la Protection Civile',
        'Direction Régionale de la Protection Civile de Tunis',
        'Direction Régionale de la Protection Civile d\'Ariana',
    ],

    'tag_form'        => 'Formulaire',
    'tag_required'    => 'Document requis',
    'tag_conditional' => 'Conditionnel',
    'docs'            => [
        ['text' => 'Copie de la décision d\'autorisation d\'ouverture d\'un établissement classé (cat. 2 à 5)', 'tags' => ['required']],
        ['text' => 'Copie du résumé non technique de l\'étude des dangers du projet', 'tags' => ['required']],
        ['text' => 'Plan de situation 1/1000 illustrant l\'environnement de l\'établissement (bâtiments, voies, points d\'eau, hôpitaux, écoles, aéroports, ports…)', 'tags' => ['required']],
        ['text' => 'Plans d\'architecture 1/100 permettant d\'identifier les composants du bâtiment et l\'affectation des locaux', 'tags' => ['required']],
        ['text' => 'Plan de masse 1/200 indiquant l\'emplacement des machines, réservoirs, réseaux, équipements de sécurité, issues de secours et moyens de premiers secours', 'tags' => ['required']],
        ['text' => 'Déclaration pour établissements classés de 3ème catégorie — dispense des pièces 2, 3 et 4', 'tags' => ['conditional']],
    ],

    'fees_payable_label' => 'Procédure payante ?',
    'fees_payable_value' => 'Non — gratuite',
    'fees_type_label'    => 'Type de redevance',
    'fees_type_value'    => 'Aucune',
    'fees_proof_label'   => 'Justificatif de paiement',
    'fees_proof_value'   => 'Non exigé',

    'conditions_title' => 'Conditions requises',
    'conditions_text'  => 'Respecter les formats de référence définis par l\'arrêté ministériel.',
    'legals_title'     => 'Références légales',
    'legals'           => [
        ['type' => 'Loi',   'prefix' => 'Loi',   'text' => 'Loi n° 11 de 2009 du 2 mars 2009 portant promulgation du code de la sécurité et de la prévention des risques d\'incendie, d\'explosion et de panique dans les bâtiments'],
        ['type' => 'Arrêté','prefix' => 'Arrêté','text' => 'Arrêté du Ministre de l\'Intérieur du 16 septembre 2013 relatif à la fixation du contenu du plan d\'intervention interne'],
        ['type' => 'Arrêté','prefix' => 'Arrêté','text' => 'Arrêté conjoint des ministres de l\'Intérieur et de l\'Industrie du 20 février 2010 relatif aux éléments de référence de l\'étude de dangers et du plan d\'urgence interne'],
    ],
    'delay_label'  => 'Délai de réalisation',
    'delay_value'  => 'Sans délai réglementaire',
    'steps_title'  => 'Étapes de réalisation',
    'steps'        => [
        'Dépôt du dossier',
        'Après approbation de l\'étude de sécurité ou de l\'étude de dangers',
        'Validation (authentification)',
    ],
    'tracking_label' => 'Mode de suivi/notification',
    'tracking_value' => 'Par courrier ordinaire',

    'validity_label' => 'Durée de validité',
    'validity_value' => 'Annuelle',
];
