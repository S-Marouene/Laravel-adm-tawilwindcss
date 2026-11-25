<?php

use App\Models\Delegation;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\TrainingParticipant;
use App\Models\TrainingPlannedSession;
use App\Models\TrainingRequest;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The yearly plan of centralized formations is read-only for regional
 * managers, while the centrale (and admins) can insert/update/delete/assign.
 */
beforeEach(function () {
    $this->app->setLocale('fr');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (array_merge(['view training', 'manage training', 'edit users'], TrainingPlannedSession::PERMISSIONS) as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    foreach (['super-admin', 'admin', 'centrale', 'manager', 'user'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    Role::findByName('manager')->syncPermissions([
        'view training', TrainingPlannedSession::PERM_VIEW,
    ]);
    Role::findByName('centrale')->syncPermissions(array_merge([
        'view training', 'manage training',
    ], TrainingPlannedSession::PERMISSIONS));

    $this->trainingService = Service::firstOrCreate(
        ['key' => Service::SERVICE_TRAINING],
        [
            'name_ar' => 'خدمات التكوين', 'name_fr' => 'Formations',
            'name_en' => 'Training', 'sort_order' => 9, 'is_active' => true,
        ],
    );
});

function trainingStaff(string $role, Service $service): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->services()->attach($service);

    return $user;
}

function plannedTrainingSession(): TrainingPlannedSession
{
    return TrainingPlannedSession::create([
        'training_type' => 'prevention_level1',
        'starts_at'     => now()->addMonth(),
        'capacity'      => 20,
        'status'        => TrainingPlannedSession::STATUS_PLANNED,
    ]);
}

function benArousGovernorate(): Governorate
{
    return Governorate::firstOrCreate(
        ['id' => 4],
        [
            'name_ar' => 'الإدارة الجهوية ببن عروس',
            'name_fr' => 'Direction régionale de Ben Arous',
            'name_en' => 'Regional Directorate of Ben Arous',
        ],
    );
}

function citizenTerritory(): array
{
    $governorate = benArousGovernorate();
    $delegation  = Delegation::create([
        'governorate_id' => $governorate->id,
        'name_ar'        => 'بن عروس',
        'name_fr'        => 'Ben Arous',
        'name_en'        => 'Ben Arous',
    ]);

    return [$governorate, $delegation];
}

test('a regional manager can view the plan but only in read-only mode', function () {
    $manager = trainingStaff('manager', $this->trainingService);

    $this->actingAs($manager)
        ->get(route('admin.training.planned.index'))
        ->assertOk()
        ->assertSee(__('training.planned_readonly_notice'));
});

test('a read-only manager cannot insert, update, delete or assign', function () {
    $manager = trainingStaff('manager', $this->trainingService);
    $session = plannedTrainingSession();

    $this->actingAs($manager)->post(route('admin.training.planned.store'), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15 10:00',
    ])->assertForbidden();

    $this->actingAs($manager)->get(route('admin.training.planned.edit', $session))->assertForbidden();

    $this->actingAs($manager)->put(route('admin.training.planned.update', $session), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15 10:00',
    ])->assertForbidden();

    $this->actingAs($manager)->delete(route('admin.training.planned.destroy', $session))->assertForbidden();

    $this->actingAs($manager)->post(route('admin.training.planned.assign'), [
        'planned_session_id' => $session->id,
    ])->assertForbidden();
});

test('the centrale can insert, update and delete planned sessions', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $this->actingAs($centrale)->post(route('admin.training.planned.store'), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15 10:00',
        'capacity'      => 20,
    ])->assertRedirect(route('admin.training.planned.index'));

    $session = TrainingPlannedSession::firstOrFail();

    $this->actingAs($centrale)->put(route('admin.training.planned.update', $session), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-02-15 10:00',
        'capacity'      => 25,
        'status'        => TrainingPlannedSession::STATUS_PLANNED,
    ])->assertRedirect(route('admin.training.planned.index'));

    expect($session->fresh()->capacity)->toBe(25);

    $this->actingAs($centrale)->delete(route('admin.training.planned.destroy', $session))
        ->assertRedirect(route('admin.training.planned.index'));

    expect(TrainingPlannedSession::find($session->id))->toBeNull();
});

test('the centrale can assign a wait-listed request to a planned session', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);
    $session  = plannedTrainingSession();
    $request  = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level1',
        'nom_prenom'    => 'Test Applicant',
        'cin'           => '12345678',
    ]);

    $this->actingAs($centrale)->post(route('admin.training.planned.assign'), [
        'training_request_id' => $request->id,
        'planned_session_id'  => $session->id,
    ])->assertRedirect(route('admin.training.planned.index'));

    expect($request->fresh()->planned_session_id)->toBe($session->id);
});

test('the centrale can assign wait-listed requests after closing submissions', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);
    $session  = plannedTrainingSession();
    $session->update(['is_active' => false]);

    $request = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level1',
        'nom_prenom'    => 'Late Applicant',
        'cin'           => '11223344',
    ]);

    $this->actingAs($centrale)->post(route('admin.training.planned.assign'), [
        'training_request_id' => $request->id,
        'planned_session_id'  => $session->id,
    ])->assertRedirect(route('admin.training.planned.index'));

    expect($request->fresh()->planned_session_id)->toBe($session->id);
});

test('an administrator can grant update rights to one manager only', function () {
    $manager = trainingStaff('manager', $this->trainingService);
    $session = plannedTrainingSession();

    $this->actingAs($manager)->get(route('admin.training.planned.edit', $session))->assertForbidden();

    $manager->givePermissionTo(TrainingPlannedSession::PERM_EDIT);

    $this->actingAs($manager)->get(route('admin.training.planned.edit', $session))->assertOk();
});

test('citizens have no access to the planning page', function () {
    $citizen = User::factory()->create();
    $citizen->assignRole('user');

    $this->actingAs($citizen)
        ->get(route('admin.training.planned.index'))
        ->assertForbidden();
});

test('an administrator can grant update rights from the user edit form', function () {
    Role::findByName('admin')->givePermissionTo('edit users');

    $admin   = User::factory()->create();
    $admin->assignRole('admin');
    $manager = trainingStaff('manager', $this->trainingService);
    $session = plannedTrainingSession();

    $governorate = Governorate::create([
        'id' => 99, 'name_ar' => 'تونس', 'name_fr' => 'Tunis', 'name_en' => 'Tunis',
    ]);

    $this->actingAs($admin)->put(route('admin.users.update', $manager), [
        'name'        => $manager->name,
        'email'       => $manager->email,
        'role'        => 'manager',
        'services'    => [$this->trainingService->id],
        'permissions' => [TrainingPlannedSession::PERM_EDIT],
        'territories' => [
            ['governorate_id' => $governorate->id, 'delegation_id' => null],
        ],
    ])->assertRedirect(route('admin.users.index'));

    expect($manager->fresh()->hasDirectPermission(TrainingPlannedSession::PERM_EDIT))->toBeTrue();

    $this->actingAs($manager)->get(route('admin.training.planned.edit', $session))->assertOk();
});

test('a new planned session defaults to Ben Arous and is open for submissions', function () {
    $centrale    = trainingStaff('centrale', $this->trainingService);
    $governorate = benArousGovernorate();

    $this->actingAs($centrale)->post(route('admin.training.planned.store'), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15 10:00',
        'capacity'      => 20,
    ])->assertRedirect(route('admin.training.planned.index'));

    $session = TrainingPlannedSession::firstOrFail();

    expect($session->governorate_id)->toBe($governorate->id);
    expect($session->is_active)->toBeTrue();
    expect($session->placeLabel())->toBe('Ben Arous');
});

test('an administrator can close and reopen submissions on a planned session', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);
    $session  = plannedTrainingSession();

    $payload = [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15 10:00',
        'capacity'      => 20,
    ];

    $this->actingAs($centrale)->put(
        route('admin.training.planned.update', $session),
        $payload + ['is_active' => '0']
    )->assertRedirect(route('admin.training.planned.index'));

    expect($session->fresh()->is_active)->toBeFalse();

    // An update without the checkbox field must preserve the closed state.
    $this->actingAs($centrale)->put(route('admin.training.planned.update', $session), $payload)
        ->assertRedirect(route('admin.training.planned.index'));

    expect($session->fresh()->is_active)->toBeFalse();

    $this->actingAs($centrale)->put(
        route('admin.training.planned.update', $session),
        $payload + ['is_active' => '1']
    )->assertRedirect(route('admin.training.planned.index'));

    expect($session->fresh()->is_active)->toBeTrue();
});

test('citizens can submit to a full session because final selection is made later', function () {
    [$governorate, $delegation] = citizenTerritory();
    $session = plannedTrainingSession();
    $session->update([
        'governorate_id' => $governorate->id,
        'capacity'       => 1,
    ]);

    // Occupy the only seat.
    TrainingRequest::create([
        'user_id'            => trainingStaff('centrale', $this->trainingService)->id,
        'status'             => TrainingRequest::STATUS_COMPLETED,
        'training_type'      => 'prevention_level1',
        'planned_session_id' => $session->id,
        'nom_prenom'         => 'Already Chosen',
        'cin'                => '87654321',
    ]);

    expect($session->fresh()->isFull())->toBeTrue();
    expect($session->fresh()->isSelectable())->toBeTrue();

    $citizen = User::factory()->create();
    $citizen->assignRole('user');

    $this->actingAs($citizen)->post(route('training.step1.store'), [
        'governorate_id'     => $governorate->id,
        'delegation_id'      => $delegation->id,
        'training_type'      => 'prevention_level1',
        'planned_session_id' => $session->id,
    ])->assertRedirect();

    $created = TrainingRequest::where('user_id', $citizen->id)->firstOrFail();
    expect($created->planned_session_id)->toBe($session->id);
    expect($created->isWaitListed())->toBeFalse();
});

test('an inactive planned session is not selectable by citizens', function () {
    [$governorate, $delegation] = citizenTerritory();
    $session = plannedTrainingSession();
    $session->update(['is_active' => false]);

    $citizen = User::factory()->create();
    $citizen->assignRole('user');

    $this->actingAs($citizen)->post(route('training.step1.store'), [
        'governorate_id'     => $governorate->id,
        'delegation_id'      => $delegation->id,
        'training_type'      => 'prevention_level1',
        'planned_session_id' => $session->id,
    ])->assertSessionHasErrors('planned_session_id');

    expect(TrainingRequest::where('user_id', $citizen->id)->exists())->toBeFalse();
});

test('the citizen form exposes the session place but not remaining seats', function () {
    [$governorate] = citizenTerritory();
    $session = plannedTrainingSession();
    $session->update(['governorate_id' => $governorate->id, 'capacity' => 1]);

    $citizen = User::factory()->create();
    $citizen->assignRole('user');

    $this->actingAs($citizen)->get(route('training.create'))
        ->assertOk()
        ->assertSee('place_label', false)
        ->assertSee('Ben Arous', false)
        ->assertDontSee('Places restantes');
});

test('planned session dates are date-only in forms, storage and listings', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $this->actingAs($centrale)->post(route('admin.training.planned.store'), [
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15',
        'ends_at'       => '2027-01-20',
        'capacity'      => 20,
    ])->assertRedirect(route('admin.training.planned.index'));

    $session = TrainingPlannedSession::firstOrFail();
    expect($session->starts_at->toDateString())->toBe('2027-01-15');
    expect($session->starts_at->format('H:i'))->toBe('00:00');
    expect($session->ends_at->toDateString())->toBe('2027-01-20');

    $this->actingAs($centrale)->get(route('admin.training.planned.index'))
        ->assertOk()
        ->assertSee('15/01/2027')
        ->assertSee('20/01/2027')
        ->assertDontSee('15/01/2027 10:00');

    $this->actingAs($centrale)->get(route('admin.training.planned.edit', $session))
        ->assertOk()
        ->assertSee('type="date"', false)
        ->assertDontSee('datetime-local');
});

test('creating a planned session attaches the checked wait-listed requests', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $first = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level1',
        'nom_prenom'    => 'Wait One',
        'cin'           => '11111111',
    ]);
    $second = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level1',
        'nom_prenom'    => 'Wait Two',
        'cin'           => '22222222',
    ]);

    $this->actingAs($centrale)->post(route('admin.training.planned.store'), [
        'training_type'   => 'prevention_level1',
        'starts_at'       => '2027-01-15',
        'assign_requests' => [$first->id, $second->id],
    ])->assertRedirect(route('admin.training.planned.index'));

    $session = TrainingPlannedSession::firstOrFail();
    expect($first->fresh()->planned_session_id)->toBe($session->id);
    expect($second->fresh()->planned_session_id)->toBe($session->id);
});

test('a wait-listed request of another type cannot be attached on creation', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $request = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level2',
        'nom_prenom'    => 'Wrong Type',
        'cin'           => '33333333',
    ]);

    $this->actingAs($centrale)->post(route('admin.training.planned.store'), [
        'training_type'   => 'prevention_level1',
        'starts_at'       => '2027-01-15',
        'assign_requests' => [$request->id],
    ])->assertSessionHasErrors('assign_requests');

    expect(TrainingPlannedSession::exists())->toBeFalse();
    expect($request->fresh()->planned_session_id)->toBeNull();
});

test('the create form shows the wait-list count with every request pre-checked', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $request = TrainingRequest::create([
        'user_id'       => $centrale->id,
        'status'        => TrainingRequest::STATUS_COMPLETED,
        'training_type' => 'prevention_level1',
        'nom_prenom'    => 'Visible Wait',
        'cin'           => '44444444',
    ]);

    $this->actingAs($centrale)->get(route('admin.training.planned.index'))
        ->assertOk()
        ->assertSee(__('training.planned_waitlist_create_title'))
        ->assertSee('data-people-count="1"', false)
        ->assertSee('name="assign_requests[]"', false)
        ->assertSee('value="' . $request->id . '"', false)
        ->assertSee('checked', false);
});

test('the create form counts and lists every person of a company wait-list request', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    $request = TrainingRequest::create([
        'user_id'        => $centrale->id,
        'status'         => TrainingRequest::STATUS_COMPLETED,
        'training_type'  => 'prevention_level1',
        'depositor_type' => TrainingRequest::DEPOSITOR_COMPANY,
        'nom_prenom'     => 'Company Contact',
        'cin'            => '55555555',
    ]);

    foreach (['First Person', 'Second Person'] as $name) {
        TrainingParticipant::create([
            'training_request_id' => $request->id,
            'nom_prenom'          => $name,
            'cin'                 => '66666666',
        ]);
    }

    $this->actingAs($centrale)->get(route('admin.training.planned.index'))
        ->assertOk()
        ->assertSee('data-people-count="2"', false)
        ->assertSee('First Person')
        ->assertSee('Second Person')
        ->assertSee('value="' . $request->id . '"', false);
});

test('sessions are grouped by formation type in collapsible sections', function () {
    $centrale = trainingStaff('centrale', $this->trainingService);

    plannedTrainingSession()->update([
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-01-15',
    ]);
    TrainingPlannedSession::create([
        'training_type' => 'prevention_level1',
        'starts_at'     => '2027-03-20',
    ]);
    TrainingPlannedSession::create([
        'training_type' => 'prevention_level2',
        'starts_at'     => '2027-02-10',
    ]);

    $this->actingAs($centrale)->get(route('admin.training.planned.index'))
        ->assertOk()
        ->assertSee('<details class="admin-table-card planned-group mb-4"', false)
        ->assertSee('15/01/2027 · 20/03/2027')
        ->assertSee('10/02/2027')
        ->assertSee('Prévention niveau 1')
        ->assertSee('Prévention niveau 2');
});
