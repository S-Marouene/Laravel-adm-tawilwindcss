<?php

namespace App\Http\Controllers;

use App\Models\PreventionDossier;
use App\Models\PreventivePresenceRequest;
use App\Models\RescueCertificate;
use App\Models\BuildingWorksDeclaration;
use App\Models\SafetyFileDossier;
use App\Models\SpecialIntervention;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;

class MesDemandesController extends Controller
{
    /**
     * Unified list of every request the user has,
     * across all services (prevention dossier, preventive presence,
     * rescue & relief certificate, works notification).
     * Submitted requests are shown first — local drafts are also
     * listed (amber "Brouillon" badge) with a direct resume link.
     */
    public function index()
    {
        $user = auth()->user();

        $items = collect();

        // ── Prevention dossiers (certificat de prévention) ──
        PreventionDossier::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation', 'buildingType', 'buildingActivity', 'appointments'])
            ->withCount('documents')
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
                $pendingAppt = $dossier->appointments->first(fn ($a) => $a->isPending());
                $rdv = $pendingAppt ? [
                    'date' => $pendingAppt->visit_date->format(app()->getLocale() === 'ar' ? 'Y/m/d' : 'd/m/Y'),
                    'time' => substr($pendingAppt->visit_time, 0, 5),
                ] : null;

                $items->push($this->makeItem(
                    kind:  'prevention',
                    lang:  'prevention_request',
                    label: __('services.prevention_certificate'),
                    url:   route('prevention-requests.show', $dossier),
                    title: __('demandes.title_prevention', ['ref' => $this->requestReference($dossier, 'prevention', $dossier->num_demande)]),
                    meta: [],
                    details: $this->dossierDetails($dossier),
                    status:    $dossier->status,
                    createdAt: $dossier->created_at,
                    rdv: $rdv,
                ));
            });

        // ── Prevention dossiers — drafts (incomplete) ──
        PreventionDossier::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType', 'buildingActivity'])
            ->withCount('documents')
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
                $resumeUrl = $dossier->current_step >= 2
                    ? route('prevention-requests.step3.show', $dossier)
                    : route('prevention-requests.step2.show', $dossier);
                $items->push($this->makeItem(
                    kind:  'prevention',
                    lang:  'prevention_request',
                    label: __('services.prevention_certificate'),
                    url:   $resumeUrl,
                    title: __('demandes.title_prevention', ['ref' => $this->requestReference($dossier, 'prevention', $dossier->num_demande)]),
                    meta: [],
                    details: $this->dossierDetails($dossier),
                    status:    'draft',
                    createdAt: $dossier->created_at,
                    destroyUrl: route('prevention-requests.destroy', $dossier) . '?redirect=/mes-demandes',
                ));
            });

        // ── Safety file dossiers (twin services: habitation / hazardous) ──
        SafetyFileDossier::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation', 'buildingType', 'buildingActivity', 'appointments'])
            ->withCount('documents')
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
                $pendingAppt = $dossier->appointments->first(fn ($a) => $a->isPending());
                $rdv = $pendingAppt ? [
                    'date' => $pendingAppt->visit_date->format(app()->getLocale() === 'ar' ? 'Y/m/d' : 'd/m/Y'),
                    'time' => substr($pendingAppt->visit_time, 0, 5),
                ] : null;

                $titleKey = $dossier->isHabitation() ? 'title_safety_public' : ($dossier->isInterventionPlan() ? 'title_iip' : 'title_safety_hazardous');

                $items->push($this->makeItem(
                    kind:  'safety',
                    lang:  'safety_file',
                    label: __($dossier->isHabitation() ? 'services.safety_file_public' : ($dossier->isInterventionPlan() ? 'services.internal_intervention_plan' : 'services.safety_file_hazardous')),
                    url:   route($dossier->isHazardous() ? 'safety-file-hazardous.show' : ($dossier->isInterventionPlan() ? 'internal-intervention-plan.show' : 'safety-file-public.show'), $dossier),
                    title: __('demandes.' . $titleKey, ['ref' => $this->requestReference($dossier, $dossier->service)]),
                    meta: [],
                    details: $this->safetyDetails($dossier),
                    status:    $dossier->status,
                    createdAt: $dossier->created_at,
                    rdv: $rdv,
                ));
            });

        // ── Safety file dossiers — drafts (incomplete) ──
        SafetyFileDossier::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType', 'buildingActivity'])
            ->withCount('documents')
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
                $resumeUrl = route(
                    $dossier->isHazardous() ? 'safety-file-hazardous.step3.show'
                        : ($dossier->isInterventionPlan() ? 'internal-intervention-plan.step3.show' : 'safety-file-public.step3.show'),
                    $dossier
                );

                $titleKey = $dossier->isHabitation() ? 'title_safety_public' : ($dossier->isInterventionPlan() ? 'title_iip' : 'title_safety_hazardous');

                $items->push($this->makeItem(
                    kind:  'safety',
                    lang:  'safety_file',
                    label: __($dossier->isHabitation() ? 'services.safety_file_public' : ($dossier->isInterventionPlan() ? 'services.internal_intervention_plan' : 'services.safety_file_hazardous')),
                    url:   $resumeUrl,
                    title: __('demandes.' . $titleKey, ['ref' => $this->requestReference($dossier, $dossier->service)]),
                    meta: [],
                    details: $this->safetyDetails($dossier),
                    status:    'draft',
                    createdAt: $dossier->created_at,
                    destroyUrl: route(
                        $dossier->isHazardous() ? 'safety-file-hazardous.destroy'
                            : ($dossier->isInterventionPlan() ? 'internal-intervention-plan.destroy' : 'safety-file-public.destroy'),
                        $dossier
                    ) . '?redirect=/mes-demandes',
                ));
            });

        // ── Preventive presence requests (présence préventive) ──
        PreventivePresenceRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($presenceRequest) use ($items) {
                $items->push($this->makeItem(
                    kind:  'presence',
                    lang:  'preventive_presence',
                    label: __('services.preventive_presence'),
                    url:   route('preventive-presence.index'),
                    title: $presenceRequest->event_title
                        ?: $presenceRequest->nom_prenom
                        ?: __('demandes.title_presence', ['ref' => $this->requestReference($presenceRequest, 'presence')]),
                    meta: $this->presenceSummary($presenceRequest),
                    details: $this->presenceDetails($presenceRequest),
                    status:    $presenceRequest->status,
                    createdAt: $presenceRequest->created_at,
                ));
            });

        // ── Preventive presence — drafts (incomplete) ──
        PreventivePresenceRequest::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($presenceRequest) use ($items) {
                $resumeUrl = $presenceRequest->current_step >= 2
                    ? route('preventive-presence.step2.show', $presenceRequest)
                    : route('preventive-presence.step1.edit', $presenceRequest);
                $items->push($this->makeItem(
                    kind:  'presence',
                    lang:  'preventive_presence',
                    label: __('services.preventive_presence'),
                    url:   $resumeUrl,
                    title: $presenceRequest->event_title
                        ?: $presenceRequest->nom_prenom
                        ?: __('demandes.title_presence', ['ref' => $this->requestReference($presenceRequest, 'presence')]),
                    meta: $this->presenceSummary($presenceRequest),
                    details: $this->presenceDetails($presenceRequest),
                    status:    'draft',
                    createdAt: $presenceRequest->created_at,
                    destroyUrl: route('preventive-presence.destroy', $presenceRequest) . '?redirect=/mes-demandes',
                ));
            });

        // ── Rescue & relief certificates (certificat de secours et sauvetage) ──
        RescueCertificate::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($rescueCertificate) use ($items) {
                $items->push($this->makeItem(
                    kind:  'rescue',
                    lang:  'rescue_certificate',
                    label: __('services.rescue_certificate'),
                    url:   route('rescue-certificates.show', $rescueCertificate),
                    title: __('demandes.title_rescue', ['ref' => $this->requestReference($rescueCertificate, 'rescue', $rescueCertificate->certificate_number)]),
                    meta: $this->rescueSummary($rescueCertificate),
                    details: $this->rescueDetails($rescueCertificate),
                    status:    $rescueCertificate->status,
                    createdAt: $rescueCertificate->created_at,
                ));
            });

        // ── Rescue & relief certificates — drafts (incomplete) ──
        RescueCertificate::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($rescueCertificate) use ($items) {
                $resumeUrl = $rescueCertificate->current_step >= 2
                    ? route('rescue-certificates.step2.show', $rescueCertificate)
                    : route('rescue-certificates.step1.edit', $rescueCertificate);
                $items->push($this->makeItem(
                    kind:  'rescue',
                    lang:  'rescue_certificate',
                    label: __('services.rescue_certificate'),
                    url:   $resumeUrl,
                    title: __('demandes.title_rescue', ['ref' => $this->requestReference($rescueCertificate, 'rescue', $rescueCertificate->certificate_number)]),
                    meta: $this->rescueSummary($rescueCertificate),
                    details: $this->rescueDetails($rescueCertificate),
                    status:    'draft',
                    createdAt: $rescueCertificate->created_at,
                    destroyUrl: route('rescue-certificates.destroy', $rescueCertificate) . '?redirect=/mes-demandes',
                ));
            });

        // ── Special paid interventions (التدخلات والعمليات والخدمات الخاصة بمقابل) ──
        SpecialIntervention::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($intervention) use ($items) {
                $items->push($this->makeItem(
                    kind:  'special-intervention',
                    lang:  'special_intervention',
                    label: __('services.special_interventions'),
                    url:   route('special-interventions.show', $intervention),
                    title: __('demandes.title_special_intervention', ['ref' => $this->requestReference($intervention, 'special-intervention', $intervention->reference)]),
                    meta: $this->specialInterventionSummary($intervention),
                    details: $this->specialInterventionDetails($intervention),
                    status:    $intervention->status,
                    createdAt: $intervention->created_at,
                ));
            });

        // ── Special paid interventions — drafts (incomplete) ──
        SpecialIntervention::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($intervention) use ($items) {
                $resumeUrl = $intervention->current_step >= 3
                    ? route('special-interventions.step3.show', $intervention)
                    : ($intervention->current_step >= 2
                        ? route('special-interventions.step2.show', $intervention)
                        : route('special-interventions.step1.edit', $intervention));
                $items->push($this->makeItem(
                    kind:  'special-intervention',
                    lang:  'special_intervention',
                    label: __('services.special_interventions'),
                    url:   $resumeUrl,
                    title: __('demandes.title_special_intervention', ['ref' => $this->requestReference($intervention, 'special-intervention', $intervention->reference)]),
                    meta: $this->specialInterventionSummary($intervention),
                    details: $this->specialInterventionDetails($intervention),
                    status:    'draft',
                    createdAt: $intervention->created_at,
                    destroyUrl: route('special-interventions.destroy', $intervention) . '?redirect=/mes-demandes',
                ));
            });

        // ── Training / formation (خدمات التكوين والرسكلة بمقابل) ──
        TrainingRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($tr) use ($items) {
                $items->push($this->makeItem(
                    kind:  'training',
                    lang:  'training',
                    label: __('services.training'),
                    url:   route('training.show', $tr),
                    title: __('demandes.title_training', ['ref' => $this->requestReference($tr, 'training', $tr->reference)]),
                    meta:  $this->trainingSummary($tr),
                    details: $this->trainingDetails($tr),
                    status:    $tr->status,
                    createdAt: $tr->created_at,
                ));
            });

        TrainingRequest::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($tr) use ($items) {
                $resumeUrl = $tr->current_step >= 2
                    ? route('training.step2.show', $tr)
                    : route('training.step1.edit', $tr);
                $items->push($this->makeItem(
                    kind:  'training',
                    lang:  'training',
                    label: __('services.training'),
                    url:   $resumeUrl,
                    title: __('demandes.title_training', ['ref' => $this->requestReference($tr, 'training', $tr->reference)]),
                    meta:  $this->trainingSummary($tr),
                    details: $this->trainingDetails($tr),
                    status:    'draft',
                    createdAt: $tr->created_at,
                    destroyUrl: route('training.destroy', $tr) . '?redirect=/mes-demandes',
                ));
            });

        // ── Works notifications (notification de travaux) ──
        BuildingWorksDeclaration::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($BuildingWorksDeclaration) use ($items) {
                $items->push($this->makeItem(
                    kind:  'works',
                    lang:  'works_notification',
                    label: __('services.works_notification'),
                    url:   route('building-works-declarations.show', $BuildingWorksDeclaration),
                    title: $BuildingWorksDeclaration->property_address
                        ?: $BuildingWorksDeclaration->owner_name
                        ?: __('demandes.title_works', ['ref' => $this->requestReference($BuildingWorksDeclaration, 'works')]),
                    meta: $this->worksSummary($BuildingWorksDeclaration),
                    details: $this->worksDetails($BuildingWorksDeclaration),
                    status:    $BuildingWorksDeclaration->status,
                    createdAt: $BuildingWorksDeclaration->created_at,
                ));
            });

        // ── Works notifications — drafts (incomplete) ──
        BuildingWorksDeclaration::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($BuildingWorksDeclaration) use ($items) {
                $resumeUrl = $BuildingWorksDeclaration->current_step >= 2
                    ? route('building-works-declarations.step2.show', $BuildingWorksDeclaration)
                    : route('building-works-declarations.step1.edit', $BuildingWorksDeclaration);
                $items->push($this->makeItem(
                    kind:  'works',
                    lang:  'works_notification',
                    label: __('services.works_notification'),
                    url:   $resumeUrl,
                    title: $BuildingWorksDeclaration->property_address
                        ?: $BuildingWorksDeclaration->owner_name
                        ?: __('demandes.title_works', ['ref' => $this->requestReference($BuildingWorksDeclaration, 'works')]),
                    meta: $this->worksSummary($BuildingWorksDeclaration),
                    details: $this->worksDetails($BuildingWorksDeclaration),
                    status:    'draft',
                    createdAt: $BuildingWorksDeclaration->created_at,
                    destroyUrl: route('building-works-declarations.destroy', $BuildingWorksDeclaration) . '?redirect=/mes-demandes',
                ));
            });

        return view('mes-demandes.index', [
            'items' => $items->sortByDesc('created_at')->values(),
        ]);
    }

    /**
     * A small localized "governorate / delegation" helper line.
     */
    private function place($model): ?string
    {
        $parts = array_filter([
            $model->governorate->name ?? null,
            $model->delegation->name ?? null,
        ]);

        return implode(' — ', $parts) ?: null;
    }

    /**
     * Normalize a model row into the unified card format.
     */
    private function makeItem(
        string $kind,
        string $lang,
        string $label,
        string $url,
        string $title,
        array $meta,
        string $status,
        $createdAt,
        string $destroyUrl = '',
        array $details = [],
        ?array $rdv = null,
    ): array {
        return [
            'kind'         => $kind,
            'lang'         => $lang,
            'label'        => $label,
            'url'          => $url,
            'title'        => $title,
            'meta'         => $meta,
            'details'      => $details,
            'status'       => $status,
            'status_label' => __($lang . '.status_' . str_replace('-', '_', $status)),
            'created_at'   => $createdAt,
            'destroy_url'  => $destroyUrl ?: null,
            'rdv'          => $rdv,
        ];
    }

    /**
     * Référence affichée : numéro officiel (num_demande, certificate_number…)
     * > référence générée en base (colonne reference) > emplacement provisoire
     * « PRV26-#### » pour les brouillons (numéro attribué à la soumission).
     */
    private function requestReference($model, string $service, ?string $official = null): string
    {
        return $official ?: ($model->reference ?: \App\Support\Reference::provisional($service));
    }

    /**
     * Ligne « Référence » du panneau de détails. Les brouillons indiquent
     * explicitement que le numéro sera attribué à la soumission.
     */
    private function referenceDetailText($model, string $service, ?string $official = null): array
    {
        if ($official || $model->reference) {
            return ['icon' => 'tag', 'text' => __('demandes.reference') . ' : ' . $this->requestReference($model, $service, $official)];
        }

        return ['icon' => 'tag', 'text' => __('demandes.reference_provisional') . ' : ' . \App\Support\Reference::provisional($service)];
    }

    /**
     * Détails riches d'un dossier de prévention, affichés dans le panneau
     * dépliable (icône + libellé) de la fiche « Mes demandes ».
     */
    private function dossierDetails(PreventionDossier $dossier): array
    {
        $identityParts = array_filter([
            $dossier->nom_prenom,
            $dossier->cin ? __('demandes.cin', ['cin' => $dossier->cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($dossier, 'prevention', $dossier->num_demande),
            $dossier->buildingType
                ? ['icon' => 'apartment', 'text' => trim(__('demandes.building_type') . ' : ' . $dossier->buildingType->name
                    . ($dossier->buildingActivity ? ' · ' . $dossier->buildingActivity->name : ''))]
                : null,
            $dossier->building_address
                ? ['icon' => 'location_on', 'text' => $dossier->building_address]
                : null,
            $this->place($dossier)
                ? ['icon' => 'map', 'text' => $this->place($dossier)]
                : null,
            $identityParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)]
                : null,
            $dossier->phone
                ? ['icon' => 'call', 'text' => $dossier->phone]
                : null,
            ['icon' => 'description', 'text' => __('demandes.documents_count', ['n' => $dossier->documents_count ?? 0])],
        ]));
    }

    /**
     * Détails riches d'un dossier de sécurité (services jumeaux « dossier de
     * sécurité »), affichés dans le panneau dépliable de la fiche « Mes demandes ».
     */
    private function safetyDetails(SafetyFileDossier $dossier): array
    {
        $identityParts = array_filter([
            $dossier->nom_prenom,
            $dossier->cin ? __('demandes.cin', ['cin' => $dossier->cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($dossier, $dossier->service),
            $dossier->buildingType
                ? ['icon' => 'apartment', 'text' => trim(__('demandes.building_type') . ' : ' . $dossier->buildingType->name
                    . ($dossier->buildingActivity ? ' · ' . $dossier->buildingActivity->name : ''))]
                : null,
            $dossier->building_address
                ? ['icon' => 'location_on', 'text' => $dossier->building_address]
                : null,
            $this->place($dossier)
                ? ['icon' => 'map', 'text' => $this->place($dossier)]
                : null,
            $identityParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)]
                : null,
            $dossier->phone
                ? ['icon' => 'call', 'text' => $dossier->phone]
                : null,
            ['icon' => 'description', 'text' => __('demandes.documents_count', ['n' => $dossier->documents_count ?? 0])],
        ]));
    }

    /**
     * Ligne de synthèse courte d'une demande de présence préventive.
     */
    private function presenceSummary(PreventivePresenceRequest $presenceRequest): array
    {
        return array_values(array_filter([
            $presenceRequest->event_date
                ? __('preventive_presence.event_label') . ' : ' .
                  $presenceRequest->event_date->format('d/m/Y') .
                  ($presenceRequest->event_end_date
                    ? ' → ' . $presenceRequest->event_end_date->format('d/m/Y')
                    : '')
                : null,
            $this->place($presenceRequest),
        ]));
    }

    /**
     * Détails complets d'une demande de présence préventive (panneau dépliable).
     */
    private function presenceDetails(PreventivePresenceRequest $presenceRequest): array
    {
        $identityParts = array_filter([
            $presenceRequest->nom_prenom,
            $presenceRequest->cin ? __('demandes.cin', ['cin' => $presenceRequest->cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($presenceRequest, 'presence'),
            $presenceRequest->event_title
                ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)]
                : null,
            $presenceRequest->event_address
                ? ['icon' => 'location_on', 'text' => $presenceRequest->event_address]
                : null,
            $this->place($presenceRequest)
                ? ['icon' => 'map', 'text' => $this->place($presenceRequest)]
                : null,
            $presenceRequest->phone
                ? ['icon' => 'call', 'text' => $presenceRequest->phone]
                : null,
        ]));
    }

    /**
     * Ligne de synthèse courte d'un certificat de secours et sauvetage.
     */
    private function rescueSummary(RescueCertificate $rescueCertificate): array
    {
        return array_values(array_filter([
            $rescueCertificate->incident_date
                ? __('demandes.incident_on', ['date' => $rescueCertificate->incident_date->format('d/m/Y')])
                : null,
            $this->place($rescueCertificate),
        ]));
    }

    /**
     * Détails complets d'un certificat de secours et sauvetage (panneau dépliable).
     */
    private function rescueDetails(RescueCertificate $rescueCertificate): array
    {
        $identityParts = array_filter([
            $rescueCertificate->nom_prenom,
            $rescueCertificate->cin ? __('demandes.cin', ['cin' => $rescueCertificate->cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($rescueCertificate, 'rescue', $rescueCertificate->certificate_number),
            $rescueCertificate->incident_address
                ? ['icon' => 'location_on', 'text' => $rescueCertificate->incident_address]
                : null,
            $this->place($rescueCertificate)
                ? ['icon' => 'map', 'text' => $this->place($rescueCertificate)]
                : null,
            $identityParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)]
                : null,
            $rescueCertificate->phone
                ? ['icon' => 'call', 'text' => $rescueCertificate->phone]
                : null,
        ]));
    }

    /**
     * Ligne de synthèse courte d'une intervention spéciale.
     */
    private function specialInterventionSummary(SpecialIntervention $intervention): array
    {
        return array_values(array_filter([
            $intervention->intervention_date
                ? __('demandes.intervention_on', ['date' => $intervention->intervention_date->format('d/m/Y')])
                : null,
            $this->place($intervention),
            $intervention->total_amount > 0
                ? __('demandes.total_amount', ['amount' => number_format($intervention->total_amount, 0) . ' TND'])
                : null,
        ]));
    }

    /**
     * Détails complets d'une intervention spéciale (panneau dépliable).
     */
    private function specialInterventionDetails(SpecialIntervention $intervention): array
    {
        $identityParts = array_filter([
            $intervention->nom_prenom,
            $intervention->cin ? __('demandes.cin', ['cin' => $intervention->cin]) : null,
        ]);

        $details = array_values(array_filter([
            $this->referenceDetailText($intervention, 'special-intervention', $intervention->reference),
            $intervention->intervention_date
                ? ['icon' => 'event', 'text' => $intervention->intervention_date->format('d/m/Y') . ($intervention->intervention_time ? ' · ' . $intervention->intervention_time->format('H:i') : '')]
                : null,
            $this->place($intervention)
                ? ['icon' => 'map', 'text' => $this->place($intervention)]
                : null,
            $identityParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)]
                : null,
            $intervention->phone
                ? ['icon' => 'call', 'text' => $intervention->phone]
                : null,
            $intervention->total_amount > 0
                ? ['icon' => 'payments', 'text' => __('demandes.total_amount', ['amount' => number_format($intervention->total_amount, 0) . ' TND'])]
                : null,
            ['icon' => 'description', 'text' => __('demandes.documents_count', ['n' => $intervention->documents->count()])],
        ]));

        return $details;
    }

    /**
     * Ligne de synthèse courte d'une notification de travaux.
     */
    private function worksSummary(BuildingWorksDeclaration $BuildingWorksDeclaration): array
    {
        return array_values(array_filter([
            $BuildingWorksDeclaration->works_start_date
                ? __('demandes.works_on', ['date' => $BuildingWorksDeclaration->works_start_date->format('d/m/Y')])
                : null,
            $this->place($BuildingWorksDeclaration),
        ]));
    }

    /**
     * Détails complets d'une notification de travaux (panneau dépliable).
     */
    private function worksDetails(BuildingWorksDeclaration $BuildingWorksDeclaration): array
    {
        $ownerParts = array_filter([
            $BuildingWorksDeclaration->owner_name,
            $BuildingWorksDeclaration->owner_cin ? __('demandes.cin', ['cin' => $BuildingWorksDeclaration->owner_cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($BuildingWorksDeclaration, 'works'),
            $BuildingWorksDeclaration->property_address
                ? ['icon' => 'location_on', 'text' => $BuildingWorksDeclaration->property_address]
                : null,
            $this->place($BuildingWorksDeclaration)
                ? ['icon' => 'map', 'text' => $this->place($BuildingWorksDeclaration)]
                : null,
            $ownerParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $ownerParts)]
                : null,
            $BuildingWorksDeclaration->phone
                ? ['icon' => 'call', 'text' => $BuildingWorksDeclaration->phone]
                : null,
        ]));
    }

    private function trainingSummary(\App\Models\TrainingRequest $tr): array
    {
        return array_values(array_filter([
            $tr->training_type ? __('training.type_' . $tr->training_type) : null,
            $tr->training_kind ? __('training.kind_' . $tr->training_kind) : null,
            $this->place($tr),
        ]));
    }

    private function trainingDetails(\App\Models\TrainingRequest $tr): array
    {
        $identityParts = array_filter([
            $tr->nom_prenom,
            $tr->cin ? __('demandes.cin', ['cin' => $tr->cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($tr, 'training', $tr->reference),
            $tr->training_type ? ['icon' => 'school', 'text' => __('training.type_' . $tr->training_type)] : null,
            $identityParts ? ['icon' => 'badge', 'text' => implode(' · ', $identityParts)] : null,
            $this->place($tr) ? ['icon' => 'map', 'text' => $this->place($tr)] : null,
            $tr->phone ? ['icon' => 'call', 'text' => $tr->phone] : null,
        ]));
    }
}
