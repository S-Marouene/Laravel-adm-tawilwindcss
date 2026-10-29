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

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
        return $this->is_admin || $this->hasRole('admin');
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
