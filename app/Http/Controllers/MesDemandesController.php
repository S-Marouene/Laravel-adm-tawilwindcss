<?php

namespace App\Http\Controllers;

use App\Models\PreventionDossier;
use App\Models\PreventivePresenceRequest;
use App\Models\RescueCertificate;
use App\Models\WorksNotification;
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
            ->with(['governorate', 'delegation', 'buildingType', 'buildingActivity'])
            ->withCount('documents')
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
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
                    url:   route('rescue-certificates.index'),
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

        // ── Works notifications (notification de travaux) ──
        WorksNotification::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($worksNotification) use ($items) {
                $items->push($this->makeItem(
                    kind:  'works',
                    lang:  'works_notification',
                    label: __('services.works_notification'),
                    url:   route('works-notifications.index'),
                    title: $worksNotification->property_address
                        ?: $worksNotification->owner_name
                        ?: __('demandes.title_works', ['ref' => $this->requestReference($worksNotification, 'works')]),
                    meta: $this->worksSummary($worksNotification),
                    details: $this->worksDetails($worksNotification),
                    status:    $worksNotification->status,
                    createdAt: $worksNotification->created_at,
                ));
            });

        // ── Works notifications — drafts (incomplete) ──
        WorksNotification::where('user_id', $user->id)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($worksNotification) use ($items) {
                $resumeUrl = $worksNotification->current_step >= 2
                    ? route('works-notifications.step2.show', $worksNotification)
                    : route('works-notifications.step1.edit', $worksNotification);
                $items->push($this->makeItem(
                    kind:  'works',
                    lang:  'works_notification',
                    label: __('services.works_notification'),
                    url:   $resumeUrl,
                    title: $worksNotification->property_address
                        ?: $worksNotification->owner_name
                        ?: __('demandes.title_works', ['ref' => $this->requestReference($worksNotification, 'works')]),
                    meta: $this->worksSummary($worksNotification),
                    details: $this->worksDetails($worksNotification),
                    status:    'draft',
                    createdAt: $worksNotification->created_at,
                    destroyUrl: route('works-notifications.destroy', $worksNotification) . '?redirect=/mes-demandes',
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
     * Ligne de synthèse courte d'une notification de travaux.
     */
    private function worksSummary(WorksNotification $worksNotification): array
    {
        return array_values(array_filter([
            $worksNotification->works_start_date
                ? __('demandes.works_on', ['date' => $worksNotification->works_start_date->format('d/m/Y')])
                : null,
            $this->place($worksNotification),
        ]));
    }

    /**
     * Détails complets d'une notification de travaux (panneau dépliable).
     */
    private function worksDetails(WorksNotification $worksNotification): array
    {
        $ownerParts = array_filter([
            $worksNotification->owner_name,
            $worksNotification->owner_cin ? __('demandes.cin', ['cin' => $worksNotification->owner_cin]) : null,
        ]);

        return array_values(array_filter([
            $this->referenceDetailText($worksNotification, 'works'),
            $worksNotification->property_address
                ? ['icon' => 'location_on', 'text' => $worksNotification->property_address]
                : null,
            $this->place($worksNotification)
                ? ['icon' => 'map', 'text' => $this->place($worksNotification)]
                : null,
            $ownerParts
                ? ['icon' => 'badge', 'text' => implode(' · ', $ownerParts)]
                : null,
            $worksNotification->phone
                ? ['icon' => 'call', 'text' => $worksNotification->phone]
                : null,
        ]));
    }
}
