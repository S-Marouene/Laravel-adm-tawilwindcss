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
     * Unified list of every request the user has submitted,
     * across all services (prevention dossier, preventive presence,
     * rescue & relief certificate, works notification).
     * Only submitted requests are shown — local drafts stay
     * visible on each individual service page.
     */
    public function index()
    {
        $user = auth()->user();

        $items = collect();

        // ── Prevention dossiers (certificat de prévention) ──
        PreventionDossier::where('user_id', $user->id)
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get()
            ->each(function ($dossier) use ($items) {
                $items->push($this->makeItem(
                    kind:  'prevention',
                    lang:  'prevention_request',
                    label: __('services.prevention_certificate'),
                    url:   route('prevention-requests.show', $dossier),
                    title: $dossier->num_demande
                        ? __('demandes.title_prevention', ['ref' => $dossier->num_demande])
                        : __('demandes.title_generic'),
                    meta: array_values(array_filter([
                        $dossier->building_address,
                        $this->place($dossier),
                    ])),
                    status:    $dossier->status,
                    createdAt: $dossier->created_at,
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
                        ?: __('demandes.title_generic'),
                    meta: array_values(array_filter([
                        $presenceRequest->event_title ? $presenceRequest->event_address : null,
                        $presenceRequest->event_date
                            ? __('preventive_presence.event_label') . ' : ' .
                              $presenceRequest->event_date->format('d/m/Y') .
                              ($presenceRequest->event_end_date
                                ? ' → ' . $presenceRequest->event_end_date->format('d/m/Y')
                                : '')
                            : null,
                        $this->place($presenceRequest),
                    ])),
                    status:    $presenceRequest->status,
                    createdAt: $presenceRequest->created_at,
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
                    title: $rescueCertificate->certificate_number
                        ? __('demandes.title_rescue', ['ref' => $rescueCertificate->certificate_number])
                        : __('demandes.title_generic'),
                    meta: array_values(array_filter([
                        $rescueCertificate->incident_address,
                        $rescueCertificate->incident_date
                            ? __('demandes.incident_on', ['date' => $rescueCertificate->incident_date->format('d/m/Y')])
                            : null,
                        $this->place($rescueCertificate),
                    ])),
                    status:    $rescueCertificate->status,
                    createdAt: $rescueCertificate->created_at,
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
                        ?: __('demandes.title_generic'),
                    meta: array_values(array_filter([
                        $worksNotification->works_start_date
                            ? __('demandes.works_on', ['date' => $worksNotification->works_start_date->format('d/m/Y')])
                            : null,
                        $this->place($worksNotification),
                    ])),
                    status:    $worksNotification->status,
                    createdAt: $worksNotification->created_at,
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
    ): array {
        return [
            'kind'         => $kind,
            'label'        => $label,
            'url'          => $url,
            'title'        => $title,
            'meta'         => $meta,
            'status'       => $status,
            'status_label' => __($lang . '.status_' . str_replace('-', '_', $status)),
            'created_at'   => $createdAt,
        ];
    }
}
