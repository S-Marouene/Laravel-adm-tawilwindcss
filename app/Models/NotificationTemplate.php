<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Editable notification message templates.
 *
 * Stored per event_key (e.g. 'submitted', 'needs_correction').
 * Placeholders resolved at send time: :reference, :nom_prenom
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'event_key',
        'level',
        'title_fr',
        'body_fr',
        'title_ar',
        'body_ar',
    ];

    /**
     * All supported event keys with their default level.
     * Used for seeding and for the admin listing.
     */
    public static function defaultLevel(string $eventKey): string
    {
        return match ($eventKey) {
            'submitted'              => 'info',
            'resubmitted'            => 'warning',
            'under_consultation'     => 'info',
            'needs_correction'       => 'warning',
            'approved'               => 'success',
            'cancelled'              => 'danger',
            'appointment_scheduled'  => 'info',
            'appointment_cancelled'  => 'warning',
            'waiting_delivery'       => 'success',
            'appointment_failed'     => 'warning',
            'certificate_issued'     => 'success',
            'certificate_collected'  => 'success',
            'certificate_post_sent'  => 'info',
            'certificate_delivered'  => 'success',
            'certificate_revoked'    => 'danger',
            'delivery_chosen'        => 'info',
            default                  => 'info',
        };
    }

    /**
     * Human-readable event label for the admin UI.
     */
    public function eventLabel(): string
    {
        return match ($this->event_key) {
            'submitted'              => 'Nouvelle soumission',
            'resubmitted'            => 'Re-soumission après correction',
            'under_consultation'     => 'Passage en consultation',
            'needs_correction'       => 'Correction demandée',
            'approved'               => 'Dossier approuvé',
            'cancelled'              => 'Dossier annulé',
            'appointment_scheduled'  => 'Visite planifiée',
            'appointment_cancelled'  => 'Visite annulée',
            'waiting_delivery'       => 'Visite passée — certificat prêt',
            'appointment_failed'     => 'Visite échouée',
            'certificate_issued'     => 'Certificat émis',
            'certificate_collected'  => 'Certificat retiré',
            'certificate_post_sent'  => 'Certificat envoyé par courrier',
            'certificate_delivered'  => 'Certificat remis',
            'certificate_revoked'    => 'Certificat révoqué',
            'delivery_chosen'        => 'Mode de retrait choisi',
            default                  => $this->event_key,
        };
    }

    /**
     * Resolve the title and body for a given locale, replacing placeholders.
     *
     * @param  string  $locale       'fr' or 'ar'
     * @param  string  $reference    e.g. "PRV26-0001"
     * @param  string  $nomPrenom    e.g. "Mohamed Ben Ali"
     */
    public function resolve(string $locale, string $reference, string $nomPrenom): array
    {
        $suffix = $locale === 'ar' ? '_ar' : '_fr';
        $title = str_replace([':reference', ':nom_prenom'], [$reference, $nomPrenom], $this->{"title{$suffix}"});
        $body  = str_replace([':reference', ':nom_prenom'], [$reference, $nomPrenom], $this->{"body{$suffix}"});
        return [$title, $body, $this->level];
    }

    /**
     * Load one template by event key, or return null if not yet seeded.
     */
    public static function forEvent(string $eventKey): ?self
    {
        return static::where('event_key', $eventKey)->first();
    }
}
