<?php

namespace App\Services;

use App\Models\PreventionDossier;
use App\Models\RescueCertificate;
use App\Models\Service;
use App\Models\User;
use App\Models\BuildingWorksDeclaration;
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
 * Supports every dossier-like model (PreventionDossier, RescueCertificate):
 * each must expose id, reference, nom_prenom, user, governorate_id, delegation_id.
 * Content templates are service-scoped: rescue looks up `rescue_{event}` keys in
 * the notification_templates table before falling back to hardcoded strings.
 */
class DossierNotifier
{
    /**
     * Dispatch notifications for a dossier workflow event.
     *
     * @param  PreventionDossier|RescueCertificate  $dossier
     * @param  string             $eventType   Internal event slug
     * @param  string             $audience    'admin' | 'user' | 'both'
     * @param  string             $serviceKey  Which service this notification is about
     */
    public static function notify(
        PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier,
        string $eventType,
        string $audience = 'both',
        string $serviceKey = Service::SERVICE_PREVENTION,
    ): void {
        [$title_fr, $body_fr, $title_ar, $body_ar, $level] = self::buildContent($dossier, $eventType, $serviceKey);

        if (in_array($audience, ['both', 'user'])) {
            $citizen = $dossier->user;
            if ($citizen) {
                $citizenUrl = self::citizenUrl($dossier, $serviceKey);
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
            $adminUrl = self::adminUrl($dossier, $serviceKey);
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
        PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier,
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
    private static function managerCoversDossier(User $manager, PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier): bool
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

    private static function buildContent(PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier, string $event, string $serviceKey): array
    {
        $ref  = $dossier->reference ?? "#{$dossier->id}";
        $name = $dossier->nom_prenom;

        // Works notifications use their own service-scoped templates
        // (works_submitted, works_under_consultation…) — the wording differs:
        // free procedure, 15-day delay, and the applicant is notified by phone.
        if ($serviceKey === Service::SERVICE_WORKS_NOTIFICATION) {
            $tpl = \App\Models\NotificationTemplate::forEvent("works_{$event}");
            if ($tpl) {
                [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
                [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
                return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
            }

            return self::worksFallback($event, $ref, $name);
        }

        // Rescue certificates use their own service-scoped templates
        // (rescue_submitted, rescue_under_consultation…) — the wording differs
        // from prevention: verification is by consulting rescue operation
        // records, there is no site visit, and the applicant is notified by phone.
        if ($serviceKey === Service::SERVICE_RESCUE_CERTIFICATE) {
            $tpl = \App\Models\NotificationTemplate::forEvent("rescue_{$event}");
            if ($tpl) {
                [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
                [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
                return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
            }

            return self::rescueFallback($event, $ref, $name);
        }

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

    private static function adminUrl(PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier, string $serviceKey): string
    {
        return match ($serviceKey) {
            Service::SERVICE_RESCUE_CERTIFICATE => route('admin.rescue-certificates.show', $dossier),
            Service::SERVICE_WORKS_NOTIFICATION => route('admin.building-works-declarations.show', $dossier),
            default => route('admin.prevention-dossiers.show', $dossier),
        };
    }

    private static function citizenUrl(PreventionDossier|RescueCertificate|BuildingWorksDeclaration $dossier, string $serviceKey): string
    {
        return match ($serviceKey) {
            Service::SERVICE_RESCUE_CERTIFICATE => route('rescue-certificates.show', $dossier),
            Service::SERVICE_WORKS_NOTIFICATION => route('building-works-declarations.show', $dossier),
            default => route('prevention-requests.show', $dossier),
        };
    }

    // ------------------------------------------------------------------
    // Rescue certificate fallback content — used until the rescue_*
    // notification templates are seeded (or for unknown events).
    // Returns: [title_fr, body_fr, title_ar, body_ar, level]
    // ------------------------------------------------------------------

    private static function rescueFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted' => [
                'Nouvelle demande de certificat de secours',
                "La demande {$ref} ({$name}) vient d'être soumise et est en attente d'examen.",
                'طلب جديد لشهادة نجدة وإنقاذ',
                "تم تقديم الطلب {$ref} ({$name}) وهو في انتظار المراجعة.",
                'info',
            ],
            'resubmitted' => [
                'Demande re-soumise après correction',
                "La demande {$ref} ({$name}) a été corrigée et re-soumise par le demandeur.",
                'إعادة تقديم الطلب بعد التصحيح',
                "أعاد المتقدم {$name} تقديم الطلب {$ref} بعد إجراء التصحيحات المطلوبة.",
                'warning',
            ],
            'under_consultation' => [
                'Vérification des données en cours',
                "Votre demande {$ref} est en cours de vérification par rapport aux enregistrements de l'opération de secours. Vous serez informé du résultat par téléphone.",
                'جارٍ التأكد من المعطيات',
                "يجري حاليًا التأكد من طلبكم {$ref} بالرجوع لسجلات عملية النجدة والإنقاذ. سيتم إبلاغكم بالنتيجة عبر الهاتف.",
                'info',
            ],
            'needs_correction' => [
                'Correction requise',
                "Votre demande {$ref} nécessite des corrections. Veuillez consulter la note de l'administration.",
                'مطلوب تصحيح',
                "يستلزم طلبكم {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.",
                'warning',
            ],
            'approved' => [
                'Demande approuvée',
                "Votre demande {$ref} a été approuvée. La procédure est gratuite — veuillez retirer votre certificat à la Direction Régionale de la Protection Civile.",
                'تمت الموافقة على الطلب',
                "تمت الموافقة على طلبكم {$ref}. الإجراء مجاني — يرجى استلام شهادتكم من الإدارة الجهوية للحماية المدنية.",
                'success',
            ],
            'cancelled' => [
                'Demande annulée',
                "La demande {$ref} a été annulée. Veuillez consulter la raison communiquée par l'administration.",
                'تم إلغاء الطلب',
                "تم إلغاء الطلب {$ref}. يرجى الاطلاع على السبب المُبلَّغ من الإدارة.",
                'danger',
            ],
            'certificate_delivered' => [
                'Certificat de secours remis',
                "Votre certificat de secours et de sauvetage (demande {$ref}) vous a été remis. Cette demande est désormais terminée.",
                'تم تسليم شهادة النجدة',
                "تم تسليم شهادة النجدة والإنقاذ الخاصة بطلبكم {$ref}. اعتُبر هذا الطلب منتهيًا.",
                'success',
            ],
            default => [
                'Demande mise à jour',
                "La demande {$ref} a été mise à jour.",
                'تم تحديث الطلب',
                "تم تحديث الطلب {$ref}.",
                'info',
            ],
        };
    }

    // ------------------------------------------------------------------
    // Works notification fallback content — used until the works_*
    // notification templates are seeded (or for unknown events).
    // Returns: [title_fr, body_fr, title_ar, body_ar, level]
    // ------------------------------------------------------------------

    private static function worksFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted' => [
                'Nouvelle notification de travaux',
                "La notification {$ref} ({$name}) vient d'être soumise et est en attente d'examen (délai : 15 jours).",
                'إعلام جديد بأشغال',
                "تم تقديم إعلام الأشغال {$ref} ({$name}) وهو في انتظار المراجعة (الأجل : 15 يومًا).",
                'info',
            ],
            'resubmitted' => [
                'Notification re-soumise après correction',
                "La notification {$ref} ({$name}) a été corrigée et re-soumise par le demandeur.",
                'إعادة تقديم الإعلام بعد التصحيح',
                "أعاد المتقدم {$name} تقديم إعلام الأشغال {$ref} بعد إجراء التصحيحات المطلوبة.",
                'warning',
            ],
            'under_consultation' => [
                'Notification en cours de consultation',
                "Votre notification {$ref} est en cours d'examen par la Direction Régionale de la Protection Civile. Vous serez informé du résultat par téléphone.",
                'الإعلام قيد الدراسة',
                "إعلام الأشغال {$ref} قيد الدراسة من طرف الإدارة الجهوية للحماية المدنية. سيتم إبلاغكم بالنتيجة عبر الهاتف.",
                'info',
            ],
            'needs_correction' => [
                'Correction requise',
                "Votre notification {$ref} nécessite des corrections. Veuillez consulter la note de l'administration.",
                'مطلوب تصحيح',
                "يستلزم إعلام الأشغال {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.",
                'warning',
            ],
            'approved' => [
                'Notification approuvée',
                "Votre notification {$ref} a été approuvée — la Protection Civile est informée des travaux. Vous serez notifié par téléphone.",
                'تمت الموافقة على الإعلام',
                "تمت الموافقة على إعلام الأشغال {$ref} — تم إعلام الحماية المدنية بالأشغال. سيتم إبلاغكم عبر الهاتف.",
                'success',
            ],
            'cancelled' => [
                'Notification annulée',
                "La notification {$ref} a été annulée. Veuillez consulter la raison communiquée par l'administration.",
                'تم إلغاء الإعلام',
                "تم إلغاء إعلام الأشغال {$ref}. يرجى الاطلاع على السبب المُبلَّغ من الإدارة.",
                'danger',
            ],
            default => [
                'Notification mise à jour',
                "La notification {$ref} a été mise à jour.",
                'تم تحديث الإعلام',
                "تم تحديث الإعلام {$ref}.",
                'info',
            ],
        };
    }
}
