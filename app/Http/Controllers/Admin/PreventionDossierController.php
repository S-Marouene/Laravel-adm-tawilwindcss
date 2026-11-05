<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DossierAppointment;
use App\Models\DossierDocument;
use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\PreventionCertificate;
use App\Models\PreventionDossier;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreventionDossierController extends Controller
{
    // =========================================================================
    // Index — list all non-draft dossiers
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);

        $query = PreventionDossier::with(['user', 'governorate', 'delegation', 'buildingType'])
            ->whereNotIn('status', ['draft'])
            ->latest();

        // Territory scope: delegation admins see only their delegation,
        // gouvernorat admins only their governorate, everyone else everything.
        AdminScope::apply($query);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_prenom', 'like', "%{$search}%")
                  ->orWhere('cin', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $dossiers = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.prevention-dossiers.index', compact('dossiers'));
    }

    // =========================================================================
    // Show — full dossier details
    // =========================================================================

    public function show(PreventionDossier $preventionDossier): View
    {
        $this->authorize('view prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $preventionDossier->load(['user', 'governorate', 'delegation', 'buildingType', 'buildingActivity', 'reviewer', 'appointments.scheduler', 'certificate.governorate', 'certificate.delegation', 'events.actor.roles', 'events.dossier:id,building_address']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)->orderBy('sort_order')->get();
        $uploadedDocs  = DossierDocument::where('prevention_dossier_id', $preventionDossier->id)
            ->get()
            ->keyBy('document_type_id');

        // Delegations grouped by governorate — used by the "issue certificate" form.
        // Scoped administrators are restricted to their own territory.
        $governorates = match (auth()->user()->adminScope()) {
            'governorate' => Governorate::whereKey(auth()->user()->governorate_id)
                ->with('delegations')->orderBy('id')->get(),
            'delegation' => Governorate::whereKey(auth()->user()->governorate_id)
                ->with(['delegations' => fn ($q) => $q->whereKey(auth()->user()->delegation_id)])
                ->orderBy('id')->get(),
            default => Governorate::with('delegations')->orderBy('id')->get(),
        };

        // All correction rounds — each round has the old field values + archived file metadata
        // so the admin can consult every version of the dossier at any time.
        $allCorrectionRounds = $preventionDossier->allCorrectionRounds();

        // Archived documents — kept on disk, downloadable from the admin view
        $archivedDocuments = DossierDocument::where('prevention_dossier_id', $preventionDossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        return view('admin.prevention-dossiers.show', compact('preventionDossier', 'documentTypes', 'uploadedDocs', 'governorates', 'allCorrectionRounds', 'archivedDocuments'));
    }

    public function advanceToConsultation(PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        if (! in_array($preventionDossier->status, ['completed', 'submitted', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this dossier from its current status.']);
        }

        $preventionDossier->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('advanced_to_consultation');

        ActivityLogger::log('prevention_update', "Prevention — Dossier #{$preventionDossier->id} advanced to consultation by " . auth()->user()->name, $preventionDossier, [
            'applicant' => $preventionDossier->nom_prenom,
            'reference' => $preventionDossier->reference,
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $validated = $request->validate([
            'admin_note'    => ['required', 'string', 'max:2000'],
            // Which dossier fields need fixing (e.g. 'phone', 'cin', 'building_type_id'…)
            'fields_to_fix' => ['nullable', 'array'],
            'fields_to_fix.*' => ['string', 'in:cin,nom_prenom,phone,governorate_id,delegation_id,building_address,building_type_id,building_activity_id'],
            // Which document types need a new upload (array of document_type_id integers)
            'docs_to_fix'   => ['nullable', 'array'],
            'docs_to_fix.*' => ['integer', 'exists:document_types,id'],
        ]);

        if (! $preventionDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this dossier.']);
        }

        // Un RDV en attente : la phase RDV est ouverte — la correction n'est plus
        // possible, le résultat de la visite (passé/échoué) décide de la suite.
        if ($preventionDossier->appointments->contains(fn ($a) => $a->isPending())) {
            return back()->withErrors(['status' => 'A visit (RDV) is already scheduled — record its result instead of requesting a correction.']);
        }

        $fieldsToFix = $validated['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $validated['docs_to_fix'] ?? []);

        // Snapshot the current field values so the user can see what changed later
        $previousParams = [
            'cin'              => $preventionDossier->cin,
            'nom_prenom'       => $preventionDossier->nom_prenom,
            'phone'            => $preventionDossier->phone,
            'governorate'      => $preventionDossier->governorate?->name_fr ?? $preventionDossier->governorate?->name_ar,
            'delegation'       => $preventionDossier->delegation?->name_fr ?? $preventionDossier->delegation?->name_ar,
            'building_address' => $preventionDossier->building_address,
            'latitude'         => $preventionDossier->latitude,
            'longitude'        => $preventionDossier->longitude,
            'building_type'    => $preventionDossier->buildingType?->name,
            'building_activity'=> $preventionDossier->buildingActivity?->name ?? $preventionDossier->activity_other,
        ];

        // Archive ONLY the documents whose type was flagged for correction
        // (not everything — unflagged documents stay active and don't need re-uploading).
        $archivedDocsMeta = [];

        if (! empty($docsToFix)) {
            $docsToArchive = $preventionDossier->documents
                ->filter(fn ($d) => $d->status !== 'archived' && in_array($d->document_type_id, $docsToFix));

            foreach ($docsToArchive as $doc) {
                $archivedDocsMeta[] = [
                    'document_type_id' => $doc->document_type_id,
                    'type'        => $doc->documentType?->name,
                    'file'        => $doc->original_name,
                    'size'        => $doc->size,
                    'uploaded_at' => $doc->created_at?->format('d/m/Y H:i'),
                ];
            }

            if ($docsToArchive->isNotEmpty()) {
                DossierDocument::whereIn('id', $docsToArchive->pluck('id'))
                    ->update(['status' => 'archived']);
            }
        }

        $preventionDossier->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('correction_requested', $validated['admin_note'], [
            'fields_to_fix'  => $fieldsToFix,
            'docs_to_fix'    => $docsToFix,
            'previous'       => $previousParams,
            'archived_files' => $archivedDocsMeta,
        ]);

        ActivityLogger::log('prevention_update', "Prevention — Correction requested for dossier #{$preventionDossier->id} by " . auth()->user()->name . " (fields: " . implode(', ', $fieldsToFix) . ")", $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'fields'     => $fieldsToFix,
            'docs'       => $docsToFix,
            'note'       => $validated['admin_note'],
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        if (! $preventionDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this dossier from its current status.']);
        }

        $preventionDossier->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('approved');

        ActivityLogger::log('prevention_update', "Prevention — Dossier #{$preventionDossier->id} approved by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (in_array($preventionDossier->status, ['approved', 'cancelled'])) {
            return back()->withErrors(['status' => 'Cannot cancel a dossier that is already approved or cancelled.']);
        }

        $preventionDossier->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('cancelled', $validated['admin_note']);

        ActivityLogger::log('prevention_update', "Prevention — Dossier #{$preventionDossier->id} cancelled by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'reason'     => $validated['admin_note'],
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Appointments — Schedule a new visit (RDV)
    // =========================================================================

    public function scheduleAppointment(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $validated = $request->validate([
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'visit_time' => ['required', 'date_format:H:i'],
            'note'       => ['nullable', 'string', 'max:2000'],
        ]);

        // No location here: the visit takes place at the building whose
        // address is stored on the dossier itself.
        DossierAppointment::create([
            'prevention_dossier_id' => $preventionDossier->id,
            'scheduled_by'          => auth()->id(),
            'visit_date'            => $validated['visit_date'],
            'visit_time'            => $validated['visit_time'],
            'note'                  => $validated['note'] ?? null,
            'status'                => 'pending',
        ]);

        $preventionDossier->logEvent('appointment_scheduled', $validated['note'] ?? null, [
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
        ]);

        ActivityLogger::log('prevention_create', "Prevention — RDV scheduled for dossier #{$preventionDossier->id} on {$validated['visit_date']} at {$validated['visit_time']} by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Appointments — Update RDV result (passed or failed + note)
    // =========================================================================

    public function updateAppointment(Request $request, PreventionDossier $preventionDossier, DossierAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        if ($appointment->prevention_dossier_id !== $preventionDossier->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed'],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        // note is required when failed
        if ($validated['status'] === 'failed' && empty($validated['note'])) {
            return back()->withErrors(['note' => 'A note is required when marking a visit as failed.']);
        }

        $appointment->update([
            'status' => $validated['status'],
            'note'   => $validated['note'] ?? null,
        ]);

        $preventionDossier->logEvent(
            $validated['status'] === 'passed' ? 'appointment_passed' : 'appointment_failed',
            $validated['note'] ?? null,
            [
                'visit_date' => $appointment->visit_date->toDateString(),
                'visit_time' => $appointment->visit_time,
            ]
        );

        ActivityLogger::log('prevention_update', "Prevention — RDV marked as {$validated['status']} for dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'status'     => $validated['status'],
            'visit_date' => $appointment->visit_date->toDateString(),
            'visit_time' => $appointment->visit_time,
            'note'       => $validated['note'] ?? null,
        ]);

        if ($validated['status'] === 'passed') {
            // ── Everything is OK: the applicant is now waiting for the
            // certificate delivery (he will pick a delivery method). ──
            $preventionDossier->update([
                'status'      => 'waiting_delivery',
                'admin_note'  => null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $preventionDossier->logEvent('waiting_delivery');

            ActivityLogger::log('prevention_update', "Prevention — Dossier #{$preventionDossier->id} moved to waiting_delivery by " . auth()->user()->name, $preventionDossier, [
                'admin'     => auth()->user()->name,
                'applicant' => $preventionDossier->nom_prenom,
            ]);
        } else {
            // ── Inspection failed: if no visit ever passed, the dossier goes
            // back to "awaiting RDV" with the failure note. The applicant must
            // fix the building issues and a new RDV will be scheduled. ──
            if (! $preventionDossier->hasPassedAppointment() && $preventionDossier->status !== 'under_consultation') {
                $preventionDossier->update([
                    'status'      => 'under_consultation',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }
        }

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Appointments — Cancel a pending RDV (with a required note — no deletion:
    // the RDV record always stays in the history).
    // =========================================================================

    public function cancelAppointment(Request $request, PreventionDossier $preventionDossier, DossierAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        if ($appointment->prevention_dossier_id !== $preventionDossier->id) {
            abort(403);
        }

        if (! $appointment->isPending()) {
            return back()->withErrors(['status' => 'Only pending appointments can be cancelled.']);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        // Capture details before the update so the event keeps the full record.
        $rdvDetails = [
            'visit_date' => $appointment->visit_date->toDateString(),
            'visit_time' => $appointment->visit_time,
        ];

        // The RDV is never deleted: its row keeps the cancellation reason,
        // so the timeline and the RDV list always show what happened.
        $appointment->update([
            'status' => 'cancelled',
            'note'   => $validated['note'],
        ]);

        $preventionDossier->logEvent('appointment_cancelled', $validated['note'], $rdvDetails);

        ActivityLogger::log('prevention_update', "Prevention — RDV cancelled for dossier #{$preventionDossier->id} (was: {$rdvDetails['visit_date']} {$rdvDetails['visit_time']}) by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'note'       => $validated['note'],
            'visit_date' => $rdvDetails['visit_date'],
            'visit_time' => $rdvDetails['visit_time'],
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Certificate — issue (affect) the prevention certificate
    // Only allowed after at least one visit (RDV) was marked as passed.
    // Delivery: pickup at an office, or post (B3 — Poste Tunisienne).
    // Digital download is "coming soon" and cannot be selected yet.
    // =========================================================================

    public function issueCertificate(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        if ($preventionDossier->isCancelled()) {
            return back()->withErrors(['status' => 'Cannot issue a certificate for a cancelled dossier.']);
        }

        if (! $preventionDossier->hasPassedAppointment()) {
            return back()->withErrors(['status' => 'A certificate can only be issued after a visit (RDV) was marked as passed.']);
        }

        if ($preventionDossier->certificate) {
            return back()->withErrors(['status' => 'This dossier already has a certificate.']);
        }

        // The applicant's delivery choice is applied by default (prefilled form),
        // but the issuing administrator can adjust it before confirming.
        $validated = $request->validate([
            'delivery_method' => ['required', 'in:' . PreventionCertificate::DELIVERY_PICKUP . ',' . PreventionCertificate::DELIVERY_POST],
            'governorate_id'  => ['nullable', 'integer', 'exists:governorates,id'],
            'delegation_id'   => ['nullable', 'integer', 'exists:delegations,id'],
            'post_recipient'  => ['required_if:delivery_method,post', 'nullable', 'string', 'max:255'],
            'post_phone'      => ['nullable', 'string', 'max:40'],
            'post_address'    => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
            'post_tracking'   => ['nullable', 'string', 'max:60'],
            'valid_until'     => ['nullable', 'date', 'after_or_equal:today'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        $deliveryMethod = $validated['delivery_method'];
        $certificateStatus = PreventionCertificate::STATUS_READY;

        if ($deliveryMethod === PreventionCertificate::DELIVERY_POST) {
            // Post (B3): recipient data comes pre-filled from the applicant's choice
            $validated['post_recipient'] ??= $preventionDossier->nom_prenom;
            $validated['post_phone']     ??= $preventionDossier->phone;
            $validated['post_address']   ??= $preventionDossier->post_address;

            if (empty($validated['post_address'])) {
                return back()->withErrors(['post_address' => 'A postal address is required for the Poste Tunisienne (B3) delivery.']);
            }
        } else {
            // Pickup: an office to collect the certificate from is required
            if (empty($validated['governorate_id'])) {
                return back()->withErrors(['governorate_id' => 'A pickup gouvernorat office is required.']);
            }

            // Scoped administrators can only assign a pickup location inside
            // their own territory (delegation scope is clamped automatically).
            $user = auth()->user();
            if (in_array($user->adminScope(), ['governorate', 'delegation'])) {
                if ((int) $validated['governorate_id'] !== (int) $user->governorate_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'governorate_id' => 'The pickup governorate must be inside your territory.',
                    ]);
                }

                if ($user->adminScope() === 'delegation') {
                    $validated['delegation_id'] = $user->delegation_id;
                }
            }

            $governorate = \App\Models\Governorate::find($validated['governorate_id']);
            $delegation  = !empty($validated['delegation_id']) ? \App\Models\Delegation::find($validated['delegation_id']) : null;

            // If a delegation is selected, it must belong to the selected governorate
            if ($delegation && (int) $delegation->governorate_id !== (int) $governorate->id) {
                return back()->withErrors(['delegation_id' => 'The selected delegation does not belong to the selected governorate.']);
            }
        }

        $certificate = PreventionCertificate::create([
            'prevention_dossier_id' => $preventionDossier->id,
            'reference'             => PreventionCertificate::makeReference($preventionDossier),
            'issued_by'             => auth()->id(),
            'issued_at'             => now()->toDateString(),
            'valid_until'           => $validated['valid_until'] ?? null,
            'governorate_id'        => $validated['governorate_id'] ?? null,
            'delegation_id'         => $validated['delegation_id'] ?? null,
            'status'                => $certificateStatus,
            'delivery_method'       => $deliveryMethod,
            'post_recipient'        => $validated['post_recipient'] ?? null,
            'post_phone'            => $validated['post_phone'] ?? null,
            'post_address'          => $validated['post_address'] ?? null,
            'post_tracking'         => $validated['post_tracking'] ?? null,
            'note'                  => $validated['note'] ?? null,
        ]);

        $preventionDossier->logEvent('certificate_issued', $validated['note'] ?? null, [
            'reference'   => $certificate->reference,
            'method'      => $deliveryMethod,
            'governorate' => ($governorate ?? null)?->name_fr ?? ($governorate ?? null)?->name_ar,
            'delegation'  => ($delegation ?? null)?->name_fr ?? ($delegation ?? null)?->name_ar,
        ]);

        ActivityLogger::log('prevention_create', "Prevention — Certificate {$certificate->reference} issued for dossier #{$preventionDossier->id} ({$deliveryMethod}) by " . auth()->user()->name, $preventionDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $preventionDossier->nom_prenom,
            'reference'  => $certificate->reference,
            'method'     => $deliveryMethod,
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Certificate — update state
    //   pickup : ready ↔ collected (picked up at the office)
    //   post   : ready → sent (B3 + tracking) → delivered
    // =========================================================================

    public function updateCertificate(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $validated = $request->validate([
            'action'        => ['required', 'in:collected,ready,sent,delivered'],
            'post_tracking' => ['nullable', 'string', 'max:60'],
        ]);

        $certificate = $preventionDossier->certificate;

        if (! $certificate) {
            return back()->withErrors(['status' => 'This dossier has no certificate to update.']);
        }

        // Illegal state moves per delivery method:
        //   post   : collected is never legal (it goes ready → sent → delivered)
        //   pickup : sent/delivered are never legal (it goes ready ↔ collected)
        if ($certificate->isPost() && $validated['action'] === 'collected') {
            return back()->withErrors(['action' => 'This certificate is sent by post — use the "sent / delivered" actions.']);
        }
        if ($certificate->isPickup() && in_array($validated['action'], ['sent', 'delivered'])) {
            return back()->withErrors(['action' => 'This certificate is collected at the office — use the "collected" action.']);
        }

        switch ($validated['action']) {
            case 'collected':
                $certificate->update([
                    'status'       => PreventionCertificate::STATUS_COLLECTED,
                    'collected_at' => now(),
                ]);
                $preventionDossier->logEvent('certificate_collected', null, [
                    'reference'   => $certificate->reference,
                    'governorate' => $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar,
                    'delegation'  => $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar,
                ]);
                ActivityLogger::log('prevention_update', "Prevention — Certificate {$certificate->reference} collected for dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                $msg = 'Certificate marked as collected.';
                break;

            case 'sent':
                $tracking = $validated['post_tracking'] ?? null;
                if ($tracking) {
                    $certificate->update(['post_tracking' => $tracking]);
                    $certificate->refresh();
                }
                if (empty($certificate->post_tracking)) {
                    return back()->withErrors(['post_tracking' => 'A tracking number is required to mark the certificate as sent.']);
                }
                $certificate->update([
                    'status'       => PreventionCertificate::STATUS_POST_SENT,
                    'post_sent_at' => now(),
                    'collected_at' => null,
                ]);
                $preventionDossier->logEvent('certificate_post_sent', null, [
                    'reference' => $certificate->reference,
                    'tracking'  => $certificate->post_tracking,
                ]);
                ActivityLogger::log('prevention_update', "Prevention — Certificate {$certificate->reference} sent by post (tracking: {$certificate->post_tracking}) for dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference, 'tracking' => $certificate->post_tracking,
                ]);
                $msg = "Certificate registered at Poste Tunisienne (B3) — tracking {$certificate->post_tracking}.";
                break;

            case 'delivered':
                $certificate->update([
                    'status'           => PreventionCertificate::STATUS_POST_DELIVERED,
                    'post_delivered_at' => now(),
                ]);
                $preventionDossier->logEvent('certificate_post_delivered', null, [
                    'reference' => $certificate->reference,
                    'tracking'  => $certificate->post_tracking,
                ]);
                ActivityLogger::log('prevention_update', "Prevention — Certificate {$certificate->reference} delivered by post for dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                $msg = 'Post delivery confirmed — the applicant received the certificate.';
                break;

            case 'ready':
            default:
                $certificate->update([
                    'status'            => PreventionCertificate::STATUS_READY,
                    'collected_at'      => null,
                    'post_sent_at'      => null,
                    'post_delivered_at' => null,
                    'post_tracking'     => null,
                ]);
                $preventionDossier->logEvent('certificate_reset', null, [
                    'reference' => $certificate->reference,
                ]);
                ActivityLogger::log('prevention_update', "Prevention — Certificate {$certificate->reference} reset to ready for dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                $msg = 'Certificate marked as ready.';
                break;
        }

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Certificate — revoke (delete) — not possible once collected
    // =========================================================================

    public function revokeCertificate(PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($preventionDossier);

        $certificate = $preventionDossier->certificate;

        if (! $certificate) {
            return back()->withErrors(['status' => 'This dossier has no certificate.']);
        }

        if ($certificate->isCollected()) {
            return back()->withErrors(['status' => 'A collected certificate cannot be deleted.']);
        }

        $reference   = $certificate->reference;
        $delegation  = $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar;
        $governorate = $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar;

        $certificate->delete();

        $preventionDossier->logEvent('certificate_deleted', null, [
            'reference'   => $reference,
            'governorate' => $governorate,
            'delegation'  => $delegation,
        ]);

        ActivityLogger::log('prevention_delete', "Prevention — Certificate {$reference} revoked from dossier #{$preventionDossier->id} by " . auth()->user()->name, $preventionDossier, [
            'admin'     => auth()->user()->name,
            'applicant' => $preventionDossier->nom_prenom,
            'reference' => $reference,
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier);
    }

    // =========================================================================
    // Download an uploaded document (active or archived)
    // =========================================================================

    public function downloadDocument(DossierDocument $document)
    {
        $this->authorize('view prevention-dossiers');
        AdminScope::authorizeService(Service::SERVICE_PREVENTION);
        AdminScope::authorize($document->dossier);

        abort_unless(\Storage::disk('local')->exists($document->file_path), 404);

        return \Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
