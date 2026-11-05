<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // ------------------------------------------------------------------
    // Back-office roles
    // ------------------------------------------------------------------

    /**
     * Role hierarchy:
     *  super-admin  – full access: settings, theme, users, all dossiers
     *  admin        – same except website/theme settings; can manage users
     *  centrale     – sees & manages ALL dossiers across all territories; filtered by assigned services
     *  manager      – sees & manages dossiers for assigned territories (multiple allowed);
     *                 each territory is either a full governorate or a single delegation;
     *                 also filtered by assigned services
     *  user         – simple public citizen; no back-office access
     */
    public const ADMIN_ROLES = ['super-admin', 'admin', 'centrale', 'manager'];

    /** Roles that see everything regardless of territory. */
    public const FULL_ACCESS_ROLES = ['super-admin', 'admin', 'centrale'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    /**
     * Territory assignments for manager users.
     * Each row is either a whole-governorate or a specific delegation.
     */
    public function territories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserTerritory::class)
            ->with(['governorate', 'delegation'])
            ->orderBy('governorate_id')
            ->orderBy('delegation_id');
    }

    /** Services this user handles (multiple allowed for centrale / manager). */
    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class)->orderBy('sort_order');
    }

    // ------------------------------------------------------------------
    // Broadcasting
    // ------------------------------------------------------------------

    /**
     * Channel name used by Laravel's notification broadcast system.
     * Echo subscribes to: private-user.{id}
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.' . $this->id;
    }

    // ------------------------------------------------------------------
    // Access helpers
    // ------------------------------------------------------------------

    public function homeUrl(): string
    {
        return $this->isAdministrator()
            ? route('admin.dashboard', absolute: false)
            : url('/');
    }

    public function isAdministrator(): bool
    {
        return $this->hasAnyRole(self::ADMIN_ROLES);
    }

    /**
     * Whether this user may access a given service's dossiers.
     * - super-admin / admin: always
     * - centrale / manager: only assigned services
     * - user: never
     */
    public function hasService(string $serviceKey): bool
    {
        if ($this->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if (! $this->hasAnyRole(['centrale', 'manager'])) {
            return false;
        }

        return $this->services->contains('key', $serviceKey);
    }

    /** Whether this user must have explicit service assignments. */
    public function serviceAssignmentRequired(): bool
    {
        return $this->hasAnyRole(['centrale', 'manager']);
    }

    /**
     * Scope label used in the dashboard header.
     * Full-access roles: "All territories"
     * Manager: comma-separated list of territory labels
     */
    public function scopeLabel(): string
    {
        if ($this->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            return 'all';
        }

        if ($this->hasRole('manager')) {
            $territories = $this->territories;
            if ($territories->isEmpty()) {
                return 'no territory';
            }
            return $territories->map(fn ($t) => $t->label())->join(', ');
        }

        return 'all';
    }

    /**
     * adminScope() is kept for compatibility with the dashboard view.
     * Returns 'all' for full-access roles, 'manager' for territory-scoped users.
     */
    public function adminScope(): string
    {
        if ($this->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            return 'all';
        }

        if ($this->hasRole('manager')) {
            return 'manager';
        }

        return 'all';
    }

    // ------------------------------------------------------------------
    // Post-login redirect
    // ------------------------------------------------------------------

    public function toHome(): \Illuminate\Http\RedirectResponse
    {
        $intended = session()->pull('url.intended');

        if (! $this->isAdministrator()) {
            if (is_string($intended) && preg_match(
                '#/(?:prevention-requests|preventive-presence|rescue-certificates|works-notifications)/create#',
                $intended,
            )) {
                return redirect($intended);
            }

            return redirect(url('/'));
        }

        return redirect()->intended($intended ?: $this->homeUrl());
    }
}
