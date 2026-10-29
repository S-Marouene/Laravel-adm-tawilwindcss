<?php

// =========================================================
// Détails de la démarche — Certificat de prévention
// (contenu officiel « Guichet numérique », cf. design/)
// =========================================================

return [
    'about_kicker' => "Certificat de prévention",
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
    'legal_name_ar_value' => "شهادة الوقاية",
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
    'tag_renewal'  => "Cas de renouvellement",
    'docs'         => [
        ['text' => "Fiche indicative à retirer au siège de la direction régionale de la Protection civile ou de la caserne concernée",   'tags' => ['form']],
        ['text' => "Copie de la carte d'identité nationale du demandeur (personne physique) ou du représentant légal (personne morale)",  'tags' => ['required']],
        ['text' => "Extrait de l'annonce légale publiée au Journal Officiel de la République Tunisienne relatif à la constitution de la personne morale", 'tags' => ['required']],
        ['text' => "Tout titre prouvant le droit d'agir du demandeur sur le bâtiment (à l'exception des bâtiments destinés à l'habitation de la 3e et 4e catégorie)", 'tags' => ['required']],
        ['text' => "Schéma du site, des accès et des parties du bâtiment ou tout document qui en tient lieu",                            'tags' => ['required']],
        ['text' => "Quittance de la redevance due lors du retrait du certificat auprès des services de la Protection civile",            'tags' => ['required']],
        ['text' => "Décision d'autorisation d'ouverture d'établissement classé (s'agissant des bâtiments comportant des établissements dangereux, insalubres ou incommodes)", 'tags' => ['required']],
        ['text' => "Fiche indicative à retirer au siège de la direction régionale ou de la caserne concernée (en cas de renouvellement)", 'tags' => ['required', 'renewal']],
        ['text' => "Copie du certificat de prévention sollicité (en cas de renouvellement)",                                              'tags' => ['required', 'renewal']],
    ],

    // ── Onglet 4 : frais ──
    'fees_payable_label' => "Procédure payante ?",
    'fees_payable_value' => "Payant",
    'fees_type_label'    => "Type de redevance",
    'fees_type_value'    => "Aucune",
    'fees_proof_label'   => "Justificatif de paiement",
    'fees_proof_value'   => "Quittance",

    // ── Onglet 5 : conditions & étapes ──
    'conditions_title' => "Conditions requises",
    'conditions_text'  => "La conformité à l'ensemble des règles et mesures de sécurité et de protection contre les risques d'incendie et de panique dans les bâtiments.",
    'legals_title'     => "Références juridiques",
    'legals'           => [
        ['type' => "Loi",     'prefix' => "Loi",           'text' => "Loi n° 11 de l'année 2009, du 2 mars 2009, portant promulgation du Code de la protection des bâtiments contre les risques d'incendie, d'explosion et de panique"],
        ['type' => "Arrêté",  'prefix' => "Arrêté",        'text' => "Arrêté du ministre de l'Intérieur du 16 septembre 2013 relatif aux documents constituant le dossier d'obtention du certificat de prévention ou de son renouvellement, au modèle du certificat et au registre qui lui est réservé"],
        ['type' => "Arrêté",  'prefix' => "Arrêté commun", 'text' => "Arrêté commun du ministre de l'Intérieur et du ministre des Finances du 25 septembre 2013 fixant le montant de la redevance applicable à la délivrance du certificat de prévention"],
    ],
    'delay_label'      => "Délai de traitement",
    'delay_value'      => "15 jours",
    'steps_title'      => "Étapes du traitement",
    'steps'            => [
        "Dépôt de la demande",
        "Vérification des documents",
        "Constatations de terrain (visite)",
        "Délivrance du certificat",
    ],
    'tracking_label' => "Suivi / notification de l'aboutiment de la demande",
    'tracking_value' => "Par téléphone",

    // ── Onglet 6 : informations complémentaires ──
    'validity_label' => "Durée de validité de la démarche",
    'validity_value' => "Deux ans (pour les autres bâtiments)",
];
