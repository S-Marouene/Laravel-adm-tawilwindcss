<?php

namespace App\Services;

use App\Models\PreventionDossier;
use App\Models\RescueCertificate;
use App\Models\SafetyFileDossier;
use App\Models\Service;
use App\Models\SpecialIntervention;
use App\Models\TrainingRequest;
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
 * Supports every dossier-like model (PreventionDossier, RescueCertificate,
 * SafetyFileDossier):
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
        PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier,
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
        PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier,
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
    private static function managerCoversDossier(User $manager, PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier): bool
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

    private static function buildContent(PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier, string $event, string $serviceKey): array
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

        // Safety file dossiers (the two twin services) use their own
        // service-scoped templates (safety_submitted, safety_under_consultation…):
        // same pipeline as prevention (site visit → study → approval) but a
        // free procedure (fee = 0) and the applicant is tracked by phone.
        if (in_array($serviceKey, [Service::SERVICE_SAFETY_FILE_HABITATION, Service::SERVICE_SAFETY_FILE_HAZARDOUS, Service::SERVICE_INTERNAL_INTERVENTION_PLAN], true)) {
            $prefix = $serviceKey === Service::SERVICE_INTERNAL_INTERVENTION_PLAN ? 'iip' : 'safety';
            $tpl = \App\Models\NotificationTemplate::forEvent("{$prefix}_{$event}");
            if ($tpl) {
                [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
                [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
                return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
            }

            if ($serviceKey === Service::SERVICE_INTERNAL_INTERVENTION_PLAN) {
                return self::iipFallback($event, $ref, $name);
            }

            return self::safetyFallback($event, $ref, $name);
        }

        if ($serviceKey === Service::SERVICE_SPECIAL_INTERVENTIONS) {
            $tpl = \App\Models\NotificationTemplate::forEvent("special_{$event}");
            if ($tpl) {
                [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
                [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
                return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
            }

            return self::specialInterventionFallback($event, $ref, $name);
        }

        if ($serviceKey === Service::SERVICE_TRAINING) {
            $tpl = \App\Models\NotificationTemplate::forEvent("training_{$event}");
            if ($tpl) {
                [$title_fr, $body_fr, $level] = $tpl->resolve('fr', $ref, $name);
                [$title_ar, $body_ar]         = array_slice($tpl->resolve('ar', $ref, $name), 0, 2);
                return [$title_fr, $body_fr, $title_ar, $body_ar, $level];
            }

            return self::trainingFallback($event, $ref, $name);
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

    private static function adminUrl(PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier, string $serviceKey): string
    {
        return match ($serviceKey) {
            Service::SERVICE_RESCUE_CERTIFICATE => route('admin.rescue-certificates.show', $dossier),
            Service::SERVICE_WORKS_NOTIFICATION => route('admin.building-works-declarations.show', $dossier),
            Service::SERVICE_INTERNAL_INTERVENTION_PLAN => route('admin.internal-intervention-plan.show', $dossier),
            Service::SERVICE_SAFETY_FILE_HABITATION => route('admin.safety-file-public.show', $dossier),
            Service::SERVICE_SPECIAL_INTERVENTIONS => route('admin.special-interventions.show', $dossier),
            Service::SERVICE_TRAINING               => route('admin.training.show', $dossier),
            default => route('admin.prevention-dossiers.show', $dossier),
        };
    }

    private static function citizenUrl(PreventionDossier|RescueCertificate|BuildingWorksDeclaration|SafetyFileDossier|SpecialIntervention|TrainingRequest $dossier, string $serviceKey): string
    {
        return match ($serviceKey) {
            Service::SERVICE_RESCUE_CERTIFICATE => route('rescue-certificates.show', $dossier),
            Service::SERVICE_WORKS_NOTIFICATION => route('building-works-declarations.show', $dossier),
            Service::SERVICE_INTERNAL_INTERVENTION_PLAN => route('internal-intervention-plan.show', $dossier),
            Service::SERVICE_SAFETY_FILE_HABITATION => route('safety-file-public.show', $dossier),
            Service::SERVICE_SPECIAL_INTERVENTIONS => route('special-interventions.show', $dossier),
            Service::SERVICE_TRAINING               => route('training.show', $dossier),
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
    // Safety file fallback content — used until the safety_* notification
    // templates are seeded (or for unknown events). Same pipeline as
    // prevention (site visit → study → approval), free procedure, the
    // applicant is tracked by phone.
    // Returns: [title_fr, body_fr, title_ar, body_ar, level]
    // ------------------------------------------------------------------

    private static function safetyFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted' => [
                'Nouveau dossier de sécurité soumis',
                "Le dossier de sécurité {$ref} ({$name}) vient d'être soumis et est en attente d'examen.",
                'ملف سلامة جديد مُقدَّم',
                "تم تقديم ملف السلامة {$ref} ({$name}) وهو في انتظار المراجعة.",
                'info',
            ],
            'resubmitted' => [
                'Dossier de sécurité re-soumis après correction',
                "Le dossier de sécurité {$ref} ({$name}) a été corrigé et re-soumis par le demandeur.",
                'إعادة تقديم ملف السلامة بعد التصحيح',
                "أعاد المتقدم {$name} تقديم ملف السلامة {$ref} بعد إجراء التصحيحات المطلوبة.",
                'warning',
            ],
            'under_consultation' => [
                'Dossier en cours de consultation',
                "Votre dossier de sécurité {$ref} a été examiné. Une visite du bâtiment (معاينة ميدانية) sera programmée prochainement. Vous serez informé du résultat par téléphone.",
                'الملف قيد الدراسة',
                "تم فحص ملف السلامة الخاص بكم {$ref}. ستتم جدولة زيارة ميدانية للمبنى قريبًا، وسيتم إبلاغكم بالنتيجة عبر الهاتف.",
                'info',
            ],
            'needs_correction' => [
                'Correction requise',
                "Votre dossier de sécurité {$ref} nécessite des corrections. Veuillez consulter la note de l'administration.",
                'مطلوب تصحيح',
                "يستلزم ملف السلامة الخاص بكم {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.",
                'warning',
            ],
            'approved' => [
                'Dossier de sécurité approuvé',
                "Votre dossier de sécurité {$ref} a été approuvé — le fichier de sécurité est authentifié. La procédure est gratuite : vous serez contacté pour le retrait.",
                'تمت المصادقة على ملف السلامة',
                "تمت المصادقة على ملف السلامة الخاص بكم {$ref}. الإجراء مجاني — سيتم الاتصال بكم لتحديد موعد استلام الشهادة.",
                'success',
            ],
            'cancelled' => [
                'Dossier de sécurité annulé',
                "Le dossier de sécurité {$ref} a été annulé. Veuillez consulter la raison communiquée par l'administration.",
                'تم إلغاء ملف السلامة',
                "تم إلغاء ملف السلامة {$ref}. يرجى الاطلاع على السبب المُبلَّغ من الإدارة.",
                'danger',
            ],
            default => [
                'Dossier de sécurité mis à jour',
                "Le dossier de sécurité {$ref} a été mis à jour.",
                'تم تحديث ملف السلامة',
                "تم تحديث ملف السلامة {$ref}.",
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

    // ------------------------------------------------------------------
    // Internal intervention plan fallback content
    // ------------------------------------------------------------------

    private static function iipFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted'          => ['Nouveau dossier de plan d\'intervention soumis', "Le dossier {$ref} ({$name}) vient d'être soumis.", 'ملف جديد لمخطط التدخل الداخلي', "تم تقديم الملف {$ref} ({$name}) وهو في انتظار المراجعة.", 'info'],
            'resubmitted'        => ['Plan d\'intervention re-soumis', "Le dossier {$ref} a été corrigé et re-soumis.", 'إعادة تقديم ملف مخطط التدخل', "أعاد المتقدم تقديم الملف {$ref} بعد إجراء التصحيحات.", 'warning'],
            'under_consultation' => ['Plan d\'intervention en cours d\'étude', "Votre dossier {$ref} est en cours d'étude. Vous serez informé par courrier.", 'الملف قيد الدراسة', "ملف مخطط التدخل {$ref} قيد الدراسة. سيتم إبلاغكم بالبريد.", 'info'],
            'needs_correction'   => ['Correction requise', "Votre dossier {$ref} nécessite des corrections.", 'مطلوب تصحيح', "يستلزم الملف {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.", 'warning'],
            'approved'           => ['Plan d\'intervention approuvé', "Votre plan d\'intervention {$ref} a été approuvé. Procédure gratuite — vous serez contacté par courrier pour le retrait.", 'تمت المصادقة على مخطط التدخل الداخلي', "تمت المصادقة على مخطط التدخل الداخلي {$ref}. الإجراء مجاني — سيتم التواصل معكم بالبريد.", 'success'],
            'cancelled'          => ['Dossier annulé', "Le dossier {$ref} a été annulé.", 'تم إلغاء الملف', "تم إلغاء ملف مخطط التدخل الداخلي {$ref}.", 'danger'],
            default              => ['Dossier mis à jour', "Le dossier {$ref} a été mis à jour.", 'تم تحديث الملف', "تم تحديث الملف {$ref}.", 'info'],
        };
    }

    // ------------------------------------------------------------------
    // Special paid interventions fallback content
    // Returns: [title_fr, body_fr, title_ar, body_ar, level]
    // ------------------------------------------------------------------

    private static function specialInterventionFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted' => [
                'Nouvelle demande d\'intervention spéciale',
                "La demande {$ref} ({$name}) vient d'être soumise et est en attente d'examen.",
                'طلب تدخل خاص جديد',
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
                "Votre demande {$ref} est en cours de vérification. Vous serez contacté par téléphone pour fixer la date de l'intervention.",
                'جارٍ التحقق من المعطيات',
                "يجري حاليًا التحقق من طلبكم {$ref}. سيتم الاتصال بكم هاتفيًا لتحديد تاريخ التدخل.",
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
                "Votre demande d'intervention spéciale {$ref} a été approuvée. L'administration vous contactera pour fixer la date et percevoir le montant dû.",
                'تمت الموافقة على الطلب',
                "تمت الموافقة على طلب التدخل الخاص {$ref}. سيتم التواصل معكم لتحديد تاريخ التدخل وتحصيل المبلغ المستحق.",
                'success',
            ],
            'cancelled' => [
                'Demande annulée',
                "La demande {$ref} a été annulée. Veuillez consulter la raison communiquée par l'administration.",
                'تم إلغاء الطلب',
                "تم إلغاء الطلب {$ref}. يرجى الاطلاع على السبب المُبلَّغ من الإدارة.",
                'danger',
            ],
            'served' => [
                'Intervention réalisée',
                "Votre intervention spéciale {$ref} a été réalisée. Cette demande est maintenant terminée.",
                'تم إنجاز التدخل',
                "تم إنجاز التدخل الخاص {$ref}. اكتمل هذا الطلب.",
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
    // Training / formation fallback content
    // ------------------------------------------------------------------

    private static function trainingFallback(string $event, string $ref, string $name): array
    {
        return match ($event) {
            'submitted' => [
                'Nouvelle demande de formation',
                "La demande de formation {$ref} ({$name}) vient d'être soumise et est en attente d'examen.",
                'طلب تكوين جديد',
                "تم تقديم طلب التكوين {$ref} ({$name}) وهو في انتظار المراجعة.",
                'info',
            ],
            'resubmitted' => [
                'Demande de formation re-soumise',
                "La demande {$ref} a été corrigée et re-soumise.",
                'إعادة تقديم طلب التكوين',
                "أعاد المتقدم {$name} تقديم طلب التكوين {$ref} بعد إجراء التصحيحات.",
                'warning',
            ],
            'under_consultation' => [
                'Demande de formation en cours d\'examen',
                "Votre demande de formation {$ref} est en cours d'examen par l'administration.",
                'طلب التكوين قيد الدراسة',
                "طلب التكوين {$ref} قيد الدراسة من طرف الإدارة.",
                'info',
            ],
            'needs_correction' => [
                'Correction requise',
                "Votre demande de formation {$ref} nécessite des corrections.",
                'مطلوب تصحيح',
                "يستلزم طلب التكوين {$ref} إجراء تصحيحات. يرجى الاطلاع على ملاحظة الإدارة.",
                'warning',
            ],
            'payment_pending' => [
                'Demande approuvée — paiement requis',
                "Votre demande de formation {$ref} a été approuvée. Veuillez vous présenter à l'administration pour régler les frais de formation.",
                'مقبول — الرجاء أداء معلوم التكوين',
                "تم قبول طلب التكوين {$ref}. يرجى التوجه إلى الإدارة لأداء معلوم التكوين.",
                'success',
            ],
            'payment_confirmed' => [
                'Paiement confirmé',
                "Le paiement de votre formation {$ref} a été confirmé. La date de début vous sera communiquée prochainement.",
                'تم تأكيد الدفع',
                "تم تأكيد دفع معلوم التكوين {$ref}. سيتم إعلامكم بتاريخ انطلاق الدورة قريبًا.",
                'success',
            ],
            'scheduled' => [
                'Date de formation fixée',
                "Votre formation {$ref} a été planifiée. Vous recevrez les détails de la date et du lieu par téléphone.",
                'تم تحديد تاريخ التكوين',
                "تم تحديد تاريخ دورة التكوين {$ref}. سيتم إعلامكم بالتفاصيل هاتفيًا.",
                'info',
            ],
            'in_progress' => [
                'Formation en cours',
                "Votre formation {$ref} a démarré. Assistez à toutes les sessions pour valider votre participation.",
                'الدورة التكوينية جارية',
                "انطلقت دورة التكوين {$ref}. يرجى الحضور في جميع الجلسات لإتمام التكوين.",
                'info',
            ],
            'passed' => [
                'Formation réussie — certificat en cours',
                "Félicitations ! Vous avez réussi la formation {$ref}. Votre certificat sera prêt prochainement.",
                'نجاح في التكوين — الشهادة قيد الإعداد',
                "تهانينا! لقد نجحتم في دورة التكوين {$ref}. شهادتكم قيد الإعداد وستكون جاهزة قريبًا.",
                'success',
            ],
            'failed' => [
                'Formation non validée',
                "Votre formation {$ref} n'a pas été validée. Consultez la note de l'administration pour plus de détails.",
                'لم يتم اجتياز التكوين',
                "لم يتم اجتياز دورة التكوين {$ref}. يرجى الاطلاع على ملاحظة الإدارة للمزيد من التفاصيل.",
                'danger',
            ],
            'certificate_issued' => [
                'Certificat de formation émis',
                "Votre certificat de formation {$ref} a été émis et est prêt à être retiré.",
                'صدرت شهادة التكوين',
                "صدرت شهادة التكوين الخاصة بالطلب {$ref} وهي جاهزة للاستلام.",
                'success',
            ],
            'certificate_delivered' => [
                'Certificat remis',
                "Votre certificat de formation {$ref} vous a été remis. Cette demande est terminée.",
                'تم تسليم الشهادة',
                "تم تسليم شهادة التكوين {$ref}. اكتمل هذا الطلب.",
                'success',
            ],
            'cancelled' => [
                'Demande annulée',
                "La demande de formation {$ref} a été annulée.",
                'تم إلغاء الطلب',
                "تم إلغاء طلب التكوين {$ref}.",
                'danger',
            ],
            default => [
                'Demande de formation mise à jour',
                "La demande {$ref} a été mise à jour.",
                'تم تحديث طلب التكوين',
                "تم تحديث طلب التكوين {$ref}.",
                'info',
            ],
        };
    }
}
