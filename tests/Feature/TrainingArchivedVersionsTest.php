<?php

use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\TrainingDocument;
use App\Models\TrainingRequest;
use App\Models\User;

beforeEach(function () {
    $this->app->setLocale('fr');

    $governorate = Governorate::create([
        'id'      => 4,
        'name_ar' => 'بن عروس',
        'name_fr' => 'Ben Arous',
        'name_en' => 'Ben Arous',
    ]);
    $delegation = Delegation::create([
        'governorate_id' => $governorate->id,
        'name_ar'        => 'بن عروس',
        'name_fr'        => 'Ben Arous',
        'name_en'        => 'Ben Arous',
    ]);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->request = TrainingRequest::create([
        'user_id'        => $this->user->id,
        'governorate_id' => $governorate->id,
        'delegation_id'  => $delegation->id,
        'status'         => TrainingRequest::STATUS_COMPLETED,
        'depositor_type' => 'individual',
        'nom_prenom'     => 'Nouveau Nom',
        'cin'            => '12345678',
        'phone'          => '0123456789',
        'training_type'  => 'taxi_drivers',
        'training_kind'  => 'new',
    ]);
});

function archivedTrainingDocument(TrainingRequest $request): TrainingDocument
{
    return TrainingDocument::create([
        'training_request_id' => $request->id,
        'document_key'        => TrainingRequest::DOC_CIN_FRONT,
        'file_path'           => 'training-documents/test/old.pdf',
        'original_name'       => 'old-identity.pdf',
        'mime_type'           => 'application/pdf',
        'size'                => 4096,
        'status'              => 'archived',
    ]);
}

function correctionRound(TrainingRequest $request, string $note, string $oldName): void
{
    $request->logEvent('correction_requested', ['fr' => $note, 'ar' => $note], [
        'fields_to_fix'  => ['nom_prenom', 'cin'],
        'docs_to_fix'    => [TrainingRequest::DOC_CIN_FRONT],
        'previous'       => [
            'nom_prenom'  => $oldName,
            'cin'         => '87654321',
            'phone'       => '0987654321',
            'notes'       => 'Ancienne observation',
        ],
        'archived_files' => [[
            'document_key' => TrainingRequest::DOC_CIN_FRONT,
            'type'         => 'Pièce d’identité (recto)',
            'file'         => 'old-identity.pdf',
            'size'         => 4096,
            'uploaded_at'  => '01/01/2026 10:00',
        ]],
    ]);
}

test('the dossier detail page lists every archived correction version', function () {
    archivedTrainingDocument($this->request);
    correctionRound($this->request, 'Première demande de correction', 'Ancien Nom Un');
    correctionRound($this->request, 'Deuxième demande de correction', 'Ancien Nom Deux');

    $this->get(route('training.show', $this->request))
        ->assertOk()
        ->assertSee('Historique des corrections')
        ->assertSee('2 tours de correction')
        ->assertSee('Deuxième demande de correction')
        ->assertSee('Ancien Nom Deux')
        ->assertSee('Anciennes informations')
        ->assertSee('Anciens fichiers (consultables)')
        ->assertSee('old-identity.pdf');
});

test('the correction form shows the current version and prior rounds', function () {
    archivedTrainingDocument($this->request);
    correctionRound($this->request, 'Première demande de correction', 'Ancien Nom Un');
    correctionRound($this->request, 'Deuxième demande de correction', 'Ancien Nom Deux');

    $this->request->update(['status' => TrainingRequest::STATUS_NEEDS_CORRECTION]);

    $this->get(route('training.correct', $this->request))
        ->assertOk()
        ->assertSee('Ancienne version — Correction n°2')
        ->assertSee('Deuxième demande de correction')
        ->assertSee('1 correction(s) antérieure(s)')
        ->assertSee('Première demande de correction')
        ->assertSee('Ancien Nom Un');
});
