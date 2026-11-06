<?php

use App\Models\User;
use App\Notifications\DossierNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Notification center — super-admin + admin only.
 */
function seedNotification(User $user, array $data): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => DossierNotification::class,
        'data' => $data,
    ]);
}

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage notifications', 'guard_name' => 'web']);
    foreach (['super-admin', 'admin', 'centrale', 'manager', 'user'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    Role::findByName('super-admin', 'web')->givePermissionTo('manage notifications');
    Role::findByName('admin', 'web')->givePermissionTo('manage notifications');
});

test('super-admin can access the notification center', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $this->actingAs($superAdmin)
        ->get(route('admin.notification-center.index'))
        ->assertOk();
});

test('admin can access the notification center', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.notification-center.index'))
        ->assertOk();
});

test('staff roles outside admin (centrale) are denied', function () {
    $centrale = User::factory()->create();
    $centrale->assignRole('centrale');

    $this->actingAs($centrale)
        ->get(route('admin.notification-center.index'))
        ->assertForbidden();
});

test('citizens are denied and redirected', function () {
    $citizen = User::factory()->create();
    $citizen->assignRole('user');

    $this->actingAs($citizen)
        ->get(route('admin.notification-center.index'))
        ->assertForbidden();
});

test('admins see notifications of other users listed', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $citizen = User::factory()->create();

    seedNotification($citizen, [
        'event' => 'test_event',
        'level' => 'info',
        'title_fr' => 'Notification de test',
        'body_fr' => 'Corps du message de test',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notification-center.index', ['q' => 'Notification de test']))
        ->assertOk()
        ->assertSee('Notification de test');
});

test('destroy removes a notification belonging to another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $citizen = User::factory()->create();

    seedNotification($citizen, [
        'event' => 'test_event',
        'level' => 'info',
        'title_fr' => 'A supprimer',
        'body_fr' => '',
        'url' => null,
    ]);

    $notification = $citizen->notifications()->first();

    expect($notification)->not->toBeNull();

    $this->actingAs($admin)
        ->delete(route('admin.notification-center.destroy', $notification))
        ->assertRedirect();

    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('mark-all-read marks every notification of every user', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $citizen = User::factory()->create();

    seedNotification($citizen, [
        'event' => 'test_event', 'level' => 'info',
        'title_fr' => 'Titre', 'body_fr' => '',
    ]);
    seedNotification($citizen, [
        'event' => 'test_event', 'level' => 'warning',
        'title_fr' => 'Titre 2', 'body_fr' => '',
    ]);

    expect(DatabaseNotification::whereNull('read_at')->count())->toBeGreaterThanOrEqual(2);

    $this->actingAs($superAdmin)
        ->post(route('admin.notification-center.mark-all-read'))
        ->assertRedirect();

    expect(DatabaseNotification::whereNull('read_at')->count())->toBe(0);
});
