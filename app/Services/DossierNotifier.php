<?php

namespace App\Services;

use App\Models\PreventionDossier;
use App\Models\Service;
use App\Models\User;
use App\Notifications\DossierNotification;
use Illuminate\Support\Collection;

/**
 * Central service that decides:
 *  1. Which users should be notified for a given dossier event
 *  2. What the notification content should be
 *
 * Audience rules:
 *  - The dossier's owner (citizen) gets notified of admin actions on their dossier
 *  - super-admin & admin: always notified (full access — all services, all territories)
 *  - centrale: notified only if assigned to the relevant service (no territory restriction)
 *  - manager: notified only if assigned to the relevant service AND has a territory
 *             matching the dossier's governorate/delegation
 *
 * The $serviceKey parameter (defaults to prevention) makes this future-proof:
 * when rescue certificates, preventive presence, or works notifications need
 * notifications, just pass Service::SERVICE_RESCUE_CERTIFICATE etc.
 */
class DossierNotifier
{
    /**
     * Dispatch notifications for a dossier workflow event.
     *
     * @param  PreventionDossier  $dossier
     * @param  string             $eventType   Internal event slug
     * @param  string             $audience    'admin' | 'user' | 'both'
     * @param  string             $serviceKey  Which service this notification is about
     *                                         (defaults to prevention; pass other keys for future services)
     */
    public static function notify(
        PreventionDossier $dossier,
        string $eventType,
        string $audience = 'both',
        string $serviceKey = Service::SERVICE_PREVENTION,
    ): void {
        [$title, $body, $level] = self::buildContent($dossier, $eventType);

        if (in_array($audience, ['both', 'user'])) {
            $citizen = $dossier->user;
            if ($citizen) {
                $citizenUrl = self::citizenUrl($dossier);
                $citizen->notify(new DossierNotification(
                    dossier: $dossier,
                    event: $eventType,
                    title: $title,
                    body: $body,
                    level: $level,
                    url: $citizenUrl,
                ));
            }
        }

        if (in_array($audience, ['both', 'admin'])) {
            $adminUrl = self::adminUrl($dossier);
            foreach (self::eligibleAdmins($dossier, $serviceKey) as $admin) {
                $admin->notify(new DossierNotification(
                    dossier: $dossier,
                    event: $eventType,
                    title: $title,
                    body: $body,
                    level: $level,
                    url: $adminUrl,
                ));
            }
        }
    }

    // ------------------------------------------------------------------
    // Audience resolver
    // ------------------------------------------------------------------

    /**
     * Returns all admin users eligible to be notified for this dossier.
     * Applies the same territory + service-scoping logic as AdminScope.
     *
     * @param  string  $serviceKey  Which service the dossier belongs to
     * @return Collection<int, User>
     */
    public static function eligibleAdmins(
        PreventionDossier $dossier,
        string $serviceKey = Service::SERVICE_PREVENTION,
    ): Collection {
        return User::with(['roles', 'territories', 'services'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin', 'centrale', 'manager']))
            ->get()
            ->filter(function (User $admin) use ($dossier, $serviceKey) {
                // super-admin and admin always see everything (all services, all territories)
                if ($admin->hasAnyRole(['super-admin', 'admin'])) {
                    return true;
                }

                // centrale: no territory restriction, but must be assigned the relevant service
                if ($admin->hasRole('centrale')) {
                    return $admin->hasService($serviceKey);
                }

                // manager: must be assigned the relevant service AND have a territory matching this dossier
                if ($admin->hasRole('manager')) {
                    if (! $admin->hasService($serviceKey)) {
                        return false;
                    }
                    return self::managerCoversDossier($admin, $dossier);
                }

                return false;
            });
    }

    /**
     * Check if a manager's territory assignments cover this dossier's location.
     */
    private static function managerCoversDossier(User $manager, PreventionDossier $dossier): bool
    {
        foreach ($manager->territories as $territory) {
            if ($territory->delegation_id) {
                // Specific delegation — must match exactly
                if ((int) $territory->delegation_id === (int) $dossier->delegation_id) {
                    return true;
                }
            } else {
                // Whole governorate
                if ((int) $territory->governorate_id === (int) $dossier->governorate_id) {
                    return true;
                }
            }
        }

        return false;
    }

    // ------------------------------------------------------------------
    // Content builder
    // ------------------------------------------------------------------

    private static function buildContent(PreventionDossier $dossier, string $event): array
    {
        $ref = $dossier->reference ?? "#{$dossier->id}";

        return match ($event) {
            // Citizen submits / resubmits
            'submitted' => [
                'Nouveau dossier soumis',
                "Dossier {$ref} ({$dossier->nom_prenom}) vient d'être soumis et est en attente d'examen.",
                'info',
            ],
            'resubmitted' => [
                'Dossier re-soumis après correction',
                "Dossier {$ref} ({$dossier->nom_prenom}) a été corrigé et re-soumis par le demandeur.",
                'warning',
            ],

            // Admin advances to consultation (RDV phase)
            'under_consultation' => [
                'Dossier under consultation',
                "Your dossier {$ref} has been reviewed and an inspection visit will be scheduled.",
                'info',
            ],

            // Admin requests corrections
            'needs_correction' => [
                'Correction required',
                "Your dossier {$ref} requires corrections. Please review the admin note.",
                'warning',
            ],

            // Admin approves directly
            'approved' => [
                'Dossier approved',
                "Your dossier {$ref} has been approved.",
                'success',
            ],

            // Admin cancels
            'cancelled' => [
                'Dossier cancelled',
                "Dossier {$ref} has been cancelled.",
                'danger',
            ],

            // RDV scheduled
            'appointment_scheduled' => [
                'Inspection visit scheduled',
                "An inspection visit (RDV) has been scheduled for dossier {$ref}.",
                'info',
            ],

            // RDV cancelled by admin
            'appointment_cancelled' => [
                'Visit cancelled',
                "The scheduled inspection visit for dossier {$ref} has been cancelled.",
                'warning',
            ],

            // RDV passed → waiting delivery
            'waiting_delivery' => [
                'Inspection passed — certificate ready',
                "The inspection for dossier {$ref} passed. Your certificate will be issued shortly.",
                'success',
            ],

            // RDV failed
            'appointment_failed' => [
                'Inspection visit did not pass',
                "The inspection visit for dossier {$ref} did not pass. Please review the note.",
                'warning',
            ],

            // Certificate issued
            'certificate_issued' => [
                'Certificate issued',
                "The prevention certificate for dossier {$ref} has been issued.",
                'success',
            ],

            // Certificate collected at office
            'certificate_collected' => [
                'Certificate collected',
                "Your prevention certificate for dossier {$ref} has been collected at the office.",
                'success',
            ],

            // Certificate sent by post
            'certificate_post_sent' => [
                'Certificate sent by post',
                "Your prevention certificate for dossier {$ref} has been sent via Poste Tunisienne.",
                'info',
            ],

            // Certificate delivered by post
            'certificate_delivered' => [
                'Certificate delivered',
                "Your prevention certificate for dossier {$ref} has been delivered.",
                'success',
            ],

            // Certificate revoked
            'certificate_revoked' => [
                'Certificate revoked',
                "The prevention certificate for dossier {$ref} has been revoked. Please contact the office.",
                'danger',
            ],

            // Citizen chose delivery method
            'delivery_chosen' => [
                'Delivery method chosen',
                "The applicant chose a delivery method for dossier {$ref}.",
                'info',
            ],

            default => [
                'Dossier updated',
                "Dossier {$ref} was updated.",
                'info',
            ],
        };
    }

    // ------------------------------------------------------------------
    // URL helpers
    // ------------------------------------------------------------------

    private static function adminUrl(PreventionDossier $dossier): string
    {
        return route('admin.prevention-dossiers.show', $dossier);
    }

    private static function citizenUrl(PreventionDossier $dossier): string
    {
        return route('prevention-requests.show', $dossier);
    }
}
