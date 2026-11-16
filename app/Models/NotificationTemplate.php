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
            // Rescue certificates (service-scoped templates)
            'rescue_submitted'              => 'info',
            'rescue_resubmitted'            => 'warning',
            'rescue_under_consultation'     => 'info',
            'rescue_needs_correction'       => 'warning',
            'rescue_approved'               => 'success',
            'rescue_cancelled'              => 'danger',
            'rescue_certificate_delivered'  => 'success',
            // Works notifications (service-scoped templates)
            'works_submitted'              => 'info',
            'works_resubmitted'            => 'warning',
            'works_under_consultation'     => 'info',
            'works_needs_correction'       => 'warning',
            'works_approved'               => 'success',
            'works_cancelled'              => 'danger',
            // Safety file dossiers (service-scoped templates — twin services)
            'safety_submitted'              => 'info',
            'safety_resubmitted'            => 'warning',
            'safety_under_consultation'     => 'info',
            'safety_needs_correction'       => 'warning',
            'safety_approved'               => 'success',
            'safety_cancelled'              => 'danger',
            'safety_appointment_scheduled'  => 'info',
            'safety_appointment_cancelled'  => 'warning',
            'safety_appointment_failed'     => 'warning',
            'safety_waiting_delivery'       => 'success',
            'safety_delivery_chosen'        => 'info',
            'safety_certificate_issued'     => 'success',
            'safety_certificate_collected'  => 'success',
            'safety_certificate_post_sent'  => 'info',
            'safety_certificate_delivered'  => 'success',
            'safety_certificate_revoked'    => 'danger',
            // Internal intervention plan (service-scoped templates)
            'iip_submitted'              => 'info',
            'iip_resubmitted'            => 'warning',
            'iip_under_consultation'     => 'info',
            'iip_needs_correction'       => 'warning',
            'iip_approved'               => 'success',
            'iip_cancelled'              => 'danger',
            'iip_appointment_scheduled'  => 'info',
            'iip_appointment_cancelled'  => 'warning',
            'iip_appointment_failed'     => 'warning',
            'iip_waiting_delivery'       => 'success',
            'iip_delivery_chosen'        => 'info',
            'iip_certificate_issued'     => 'success',
            'iip_certificate_collected'  => 'success',
            'iip_certificate_revoked'    => 'danger',
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
            // Rescue certificates (service-scoped templates)
            'rescue_submitted'              => 'Secours — nouvelle demande',
            'rescue_resubmitted'            => 'Secours — re-soumission après correction',
            'rescue_under_consultation'     => 'Secours — vérification des données',
            'rescue_needs_correction'       => 'Secours — correction demandée',
            'rescue_approved'               => 'Secours — demande approuvée',
            'rescue_cancelled'              => 'Secours — demande annulée',
            'rescue_certificate_delivered'  => 'Secours — certificat remis',
            // Works notifications (service-scoped templates)
            'works_submitted'              => 'Travaux — nouvelle notification',
            'works_resubmitted'            => 'Travaux — re-soumission après correction',
            'works_under_consultation'     => 'Travaux — notification en cours de traitement',
            'works_needs_correction'       => 'Travaux — correction demandée',
            'works_approved'               => 'Travaux — notification approuvée',
            'works_cancelled'              => 'Travaux — notification annulée',
            // Safety file dossiers (service-scoped templates — twin services)
            'safety_submitted'              => 'Sécurité — dossier soumis',
            'safety_resubmitted'            => 'Sécurité — re-soumission après correction',
            'safety_under_consultation'     => 'Sécurité — dossier en cours d\'examen',
            'safety_needs_correction'       => 'Sécurité — correction demandée',
            'safety_approved'               => 'Sécurité — dossier approuvé (authentifié)',
            'safety_cancelled'              => 'Sécurité — dossier annulé',
            'safety_appointment_scheduled'  => 'Sécurité — visite planifiée',
            'safety_appointment_cancelled'  => 'Sécurité — visite annulée',
            'safety_appointment_failed'     => 'Sécurité — visite échouée',
            'safety_waiting_delivery'       => 'Sécurité — visite passée, certificat en préparation',
            'safety_delivery_chosen'        => 'Sécurité — mode de retrait choisi',
            'safety_certificate_issued'     => 'Sécurité — certificat émis',
            'safety_certificate_collected'  => 'Sécurité — certificat retiré',
            'safety_certificate_post_sent'  => 'Sécurité — certificat envoyé par courrier',
            'safety_certificate_delivered'  => 'Sécurité — certificat remis',
            'safety_certificate_revoked'    => 'Sécurité — certificat révoqué',
            // Internal intervention plan (service-scoped templates)
            'iip_submitted'              => 'Plan d\'intervention — dossier soumis',
            'iip_resubmitted'            => 'Plan d\'intervention — re-soumission',
            'iip_under_consultation'     => 'Plan d\'intervention — en cours d\'étude',
            'iip_needs_correction'       => 'Plan d\'intervention — correction demandée',
            'iip_approved'               => 'Plan d\'intervention — approuvé (authentifié)',
            'iip_cancelled'              => 'Plan d\'intervention — dossier annulé',
            'iip_appointment_scheduled'  => 'Plan d\'intervention — visite planifiée',
            'iip_appointment_cancelled'  => 'Plan d\'intervention — visite annulée',
            'iip_appointment_failed'     => 'Plan d\'intervention — visite échouée',
            'iip_waiting_delivery'       => 'Plan d\'intervention — visite réussie, document en préparation',
            'iip_delivery_chosen'        => 'Plan d\'intervention — mode de retrait choisi',
            'iip_certificate_issued'     => 'Plan d\'intervention — attestation émise',
            'iip_certificate_collected'  => 'Plan d\'intervention — attestation retirée',
            'iip_certificate_revoked'    => 'Plan d\'intervention — attestation révoquée',
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
