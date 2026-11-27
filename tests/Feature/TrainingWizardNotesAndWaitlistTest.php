<?php

use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\TrainingPlannedSession;
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

    $this->location = [
        'governorate_id' => $governorate->id,
        'delegation_id'  => $delegation->id,
    ];

    $this->actingAs(User::factory()->create());
});

function wizardPlannedSession(): TrainingPlannedSession
{
    return TrainingPlannedSession::create([
        'training_type' => 'prevention_level1',
        'starts_at'     => now()->addMonth(),
        'status'        => TrainingPlannedSession::STATUS_PLANNED,
        'is_active'     => true,
    ]);
}

test('step 1 keeps a "another date" choice on the waiting list with its observation', function () {
    wizardPlannedSession();

    $this->post(route('training.step1.store'), $this->location + [
        'training_type'      => 'prevention_level1',
        'planned_session_id' => TrainingRequest::PLANNED_SESSION_OTHER,
        'notes'              => 'Je souhaite la date entre mars et avril 2026.',
    ])->assertRedirect();

    $request = TrainingRequest::firstOrFail();

    expect($request->planned_session_id)->toBeNull()
        ->and($request->isWaitListed())->toBeTrue()
        ->and($request->notes)->toBe('Je souhaite la date entre mars et avril 2026.');
});

test('step 1 still stores a chosen programmed session', function () {
    $session = wizardPlannedSession();

    $this->post(route('training.step1.store'), $this->location + [
        'training_type'      => 'prevention_level1',
        'planned_session_id' => (string) $session->id,
        'notes'              => null,
    ])->assertRedirect();

    expect(TrainingRequest::firstOrFail()->planned_session_id)->toBe($session->id);
});

test('step 2 no longer overwrites the observation saved in step 1', function () {
    $this->post(route('training.step1.store'), $this->location + [
        'training_type'      => 'prevention_level1',
        'planned_session_id' => TrainingRequest::PLANNED_SESSION_OTHER,
        'notes'              => 'أريد التاريخ بين مارس وأفريل 2026.',
    ])->assertRedirect();

    $request = TrainingRequest::firstOrFail();

    $this->post(route('training.step2.store', $request), [
        'depositor_type' => 'individual',
        'nom_prenom'     => 'Test User',
        'cin'            => '12345678',
        'phone'          => '0123456789',
        'training_kind'  => 'new',
    ])->assertRedirect();

    expect($request->fresh()->notes)->toBe('أريد التاريخ بين مارس وأفريل 2026.');
});
