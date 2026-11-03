<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_admin', 'governorate_id', 'delegation_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // ------------------------------------------------------------------
    // Back-office roles
    // ------------------------------------------------------------------

    /** Every role that grants access to the admin panel. */
    public const ADMIN_ROLES = [
        'super-admin',      // site settings, theme, user management, dossiers…
        'admin',            // user management, roles, references, all dossiers
        'centrale',         // all dossiers from every delegation
        'gouvernorat',      // dossiers of one governorate (all its delegations)
        'delegation',       // dossiers of one delegation only
        'reference-manager',// governorates, delegations, building & document types
    ];

    /** Roles that see everything, regardless of territory. */
    public const FULL_ACCESS_ROLES = ['super-admin', 'admin', 'centrale', 'reference-manager'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Where this user should be redirected after logging in:
     * admins go to the admin dashboard, everyone else to the
     * public landing page.
     */
    public function homeUrl(): string
    {
        return $this->isAdministrator()
            ? route('admin.dashboard', absolute: false)
            : url('/');
    }

    public function isAdministrator(): bool
    {
        return $this->is_admin || $this->hasAnyRole(self::ADMIN_ROLES);
    }

    // ------------------------------------------------------------------
    // Territory hierarchy (delegation → gouvernorat → centrale)
    // ------------------------------------------------------------------

    public function governorate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function delegation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /** Services this administrator handles (an administrator can hold several). */
    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class)->orderBy('sort_order');
    }

    /**
     * Whether this administrator may access a given service's dossiers.
     * - super-admin / admin: always (everything, every service);
     * - centrale / gouvernorat / delegation: only their assigned services;
     * - other roles: never (they don't handle dossiers).
     */
    public function hasService(string $serviceKey): bool
    {
        if ($this->is_admin || $this->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if (!$this->hasAnyRole(['centrale', 'gouvernorat', 'delegation'])) {
            return false;
        }

        // Memoize the pivot once per user instance/query cycle.
        $assigned ??= $this->services()->get();

        return $assigned->contains('key', $serviceKey);
    }

    /** Whether this administrator's dossier access is restricted to a hand-picked list of services. */
    public function serviceAssignmentRequired(): bool
    {
        return $this->hasAnyRole(['centrale', 'gouvernorat', 'delegation']);
    }

    /**
     * How wide this administrator's view of the dossiers is:
     * - 'all'         : centrale / admin / super-admin / reference-manager (no filter)
     * - 'governorate' : gouvernorat administrator — only his governorate
     * - 'delegation'  : delegation administrator — only his delegation
     */
    public function adminScope(): string
    {
        if ($this->is_admin || $this->hasAnyRole(self::FULL_ACCESS_ROLES)) {
            return 'all';
        }

        if ($this->hasRole('gouvernorat')) {
            return 'governorate';
        }

        if ($this->hasRole('delegation')) {
            return 'delegation';
        }

        // Unknown back-office role → full access is blocked per-permission anyway.
        return 'all';
    }

    /**
     * Short label of the administrator's territory, e.g.
     * "Tunis — Bab El Bhar" (delegation) or "Tunis" (gouvernorat).
     */
    public function scopeLabel(): string
    {
        return match ($this->adminScope()) {
            'governorate' => $this->governorate?->name ?? 'all',
            'delegation'  => trim(($this->governorate?->name ?? '') . ' — ' . ($this->delegation?->name ?? '')) ?: 'all',
            default       => 'all',
        };
    }

    /**
     * Destination après authentification (connexion, vérification email,
     * confirmation de mot de passe…) :
     * - les utilisateurs simples vont sur la page d'accueil, sauf s'ils
     *   avaient cliqué sur un service (formulaire de création d'une
     *   demande) : la demande en cours reprend après connexion ;
     * - les administrateurs suivent l'URL prévue (souvent une page admin),
     *   avec le tableau de bord en secours.
     */
    public function toHome(): \Illuminate\Http\RedirectResponse
    {
        $intended = session()->pull('url.intended');

        if (! $this->isAdministrator()) {
            // Reprise d'une demande entamée (clic sur une carte du service)
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
