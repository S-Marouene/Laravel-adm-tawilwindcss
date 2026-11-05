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
        [$title_fr, $body_fr, $title_ar, $body_ar, $level] = self::buildContent($dossier, $eventType);

        if (in_array($audience, ['both', 'user'])) {
            $citizen = $dossier->user;
            if ($citizen) {
                $citizenUrl = self::citizenUrl($dossier);
                $citizen->notify(new DossierNotification(
                    dossier:  $dossier,
                    event:    $eventType,
                    title_fr: $title_fr,
                    body_fr:  $body_fr,
                    title_ar: $title_ar,
                    body_ar:  $body_ar,
                    level:    $level,
                    url:      $citizenUrl,
                ));
            }
        }

        if (in_array($audience, ['both', 'admin'])) {
            $adminUrl = self::adminUrl($dossier);
            foreach (self::eligibleAdmins($dossier, $serviceKey) as $admin) {
                $admin->notify(new DossierNotification(
                    dossier:  $dossier,
                    event:    $eventType,
                    title_fr: $title_fr,
                    body_fr:  $body_fr,
                    title_ar: $title_ar,
                    body_ar:  $body_ar,
                    level:    $level,
                    url:      $adminUrl,
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

    // ------------------------------------------------------------------
    // Content builder — loads from NotificationTemplate DB table,
    // falls back to hardcoded strings if the template hasn't been seeded.
    // Returns: [title_fr, body_fr, title_ar, body_ar, level]
    // ------------------------------------------------------------------

    private static function buildContent(PreventionDossier $dossier, string $event): array
    {
        $ref  = $dossier->reference ?? "#{$dossier->id}";
        $name = $dossier->nom_prenom;

        // Try the editable DB template first
        $tpl = \App\Models\NotificationTemplate::forEvent($event);
        if ($tpl) {
            [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
            [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
            return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
        }

        // Hardcoded fallbacks (used until the seeder runs or if unknown event)
        return match ($event) {
            'submitted' => [
                'Nouveau dossier soumis',
                "Le dossier {$ref} ({$name}) vient d'être soumis et est en attente d'examen.",
                'ملف جديد مُقدَّم',
                "تم تقديم الملف {$ref} ({$name}) وهو في انتظار المراجعة.",
                'info',
            ],
            'resubmitted' => [
                'Dossier re-soumis après correction',
                "Le dossier {$ref} ({$name}) a été corrigé et re-soumis par le demandeur.",
                'إعادة تقديم الملف بعد التصحيح',
                "أعاد المتقدم {$name} تقديم الملف {$ref} بعد إجراء التصحيحات المطلوبة.",
                'warning',
            ],
            'under_consultation' => [
                'Dossier en cours de consultation',
                "Votre dossier {$ref} a été examiné. Une visite d'inspection sera programmée prochainement.",
                'الملف قيد الدراسة',
                "تم فحص ملفكم {$ref} وستتم جدولة زيارة تفتيشية قريبًا.",
                'info',
            ],
            'needs_correction' => [
                'Correction requise',
                "Votre dossier {$ref} nécessite des corrections. Veuillez consulter la note de l'administration.",
                'مطلوب تصحيح',
                "يستلزم ملفكم {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.",
                'warning',
            ],
            'approved' => [
                'Dossier approuvé',
                "Votre dossier {$ref} a été approuvé par l'administration.",
                'تمت الموافقة على الملف',
                "تمت الموافقة على ملفكم {$ref} من قِبل الإدارة.",
                'success',
            ],
            'cancelled' => [
                'Dossier annulé',
                "Le dossier {$ref} a été annulé.",
                'تم إلغاء الملف',
                "تم إلغاء الملف {$ref}.",
                'danger',
            ],
            default => [
                'Dossier mis à jour',
                "Le dossier {$ref} a été mis à jour.",
                'تم تحديث الملف',
                "تم تحديث الملف {$ref}.",
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
