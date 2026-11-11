<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SafetyFileAppointment;
use App\Models\SafetyFileDocument;
use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\SafetyFileCertificate;
use App\Models\SafetyFileDossier;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SafetyFileDossierController extends Controller
{
    // =========================================================================
    // Index — list all non-draft dossiers
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view safety-file-dossiers');

        // The two twin services share this module — restrict the list to the
        // services the administrator is assigned to (?service= narrows further).
        $allowedServices = collect(SafetyFileDossier::SERVICE_BY_VARIANT)
            ->filter(fn ($key) => auth()->user()->hasService($key))
            ->values();
        if ($allowedServices->isEmpty()) {
            abort(403, 'You are not assigned to this service.');
        }

        $query = SafetyFileDossier::with(['user', 'governorate', 'delegation', 'buildingType'])
            ->whereNotIn('status', ['draft'])
            ->whereIn('service', $allowedServices->all())
            ->latest();

        // Twin-service filter (dropdown in the index header)
        if ($service = $request->get('service')) {
            if ($allowedServices->contains($service)) {
                $query->where('service', $service);
            }
        }

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

        $dossiers = $query->paginate(20)->appends($request->only('q', 'status', 'service'));

        // Service filter options (twin services the admin is assigned to)
        $serviceOptions = collect(SafetyFileDossier::SERVICE_BY_VARIANT)
            ->filter(fn ($key) => auth()->user()->hasService($key))
            ->mapWithKeys(fn ($serviceKey, $variant) => [
                $serviceKey => SafetyFileDossier::serviceKeyForVariant($variant) === Service::SERVICE_SAFETY_FILE_HAZARDOUS
                    ? __('services.safety_file_hazardous')
                    : __('services.safety_file_public'),
            ]);

        return view('admin.safety-file-dossiers.index', compact('dossiers', 'serviceOptions'));
    }

    // =========================================================================
    // Show — full dossier details
    // =========================================================================

    public function show(SafetyFileDossier $safetyFileDossier): View
    {
        $this->authorize('view safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $safetyFileDossier->load(['user', 'governorate', 'delegation', 'buildingType', 'buildingActivity', 'reviewer', 'appointments.scheduler', 'certificate.governorate', 'certificate.delegation', 'events.actor.roles', 'events.dossier:id,building_address']);

        $documentTypes = DocumentType::service($safetyFileDossier->service)->orderBy('sort_order')->get();
        $uploadedDocs  = SafetyFileDocument::where('safety_file_dossier_id', $safetyFileDossier->id)
            ->get()
            ->keyBy('document_type_id');

        // Delegations grouped by governorate — used by the "issue certificate" form.
        // Scoped managers are restricted to their assigned territories.
        $user = auth()->user();
        if ($user->adminScope() === 'manager') {
            $territories = $user->territories;
            $govIds = $territories->pluck('governorate_id')->unique();
            $governorates = Governorate::whereIn('id', $govIds)
                ->with(['delegations' => function ($q) use ($territories) {
                    // Include only delegations that are either within a whole-gov territory
                    // or explicitly assigned
                    $specificDelIds = $territories->whereNotNull('delegation_id')->pluck('delegation_id');
                    $wholeGovIds    = $territories->whereNull('delegation_id')->pluck('governorate_id');
                    $q->where(function ($sub) use ($specificDelIds, $wholeGovIds) {
                        $sub->whereIn('governorate_id', $wholeGovIds)
                            ->orWhereIn('id', $specificDelIds);
                    });
                }])
                ->orderBy('id')
                ->get();
        } else {
            $governorates = Governorate::with('delegations')->orderBy('id')->get();
        }

        // All correction rounds — each round has the old field values + archived file metadata
        // so the admin can consult every version of the dossier at any time.
        $allCorrectionRounds = $safetyFileDossier->allCorrectionRounds();

        // Archived documents — kept on disk, downloadable from the admin view
        $archivedDocuments = SafetyFileDocument::where('safety_file_dossier_id', $safetyFileDossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        return view('admin.safety-file-dossiers.show', compact('safetyFileDossier', 'documentTypes', 'uploadedDocs', 'governorates', 'allCorrectionRounds', 'archivedDocuments'));
    }

    public function advanceToConsultation(SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        if (! in_array($safetyFileDossier->status, ['completed', 'submitted', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this dossier from its current status.']);
        }

        $safetyFileDossier->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $safetyFileDossier->logEvent('advanced_to_consultation');

        ActivityLogger::log('safety_update', "Safety file — Dossier #{$safetyFileDossier->id} advanced to consultation by " . auth()->user()->name, $safetyFileDossier, [
            'applicant' => $safetyFileDossier->nom_prenom,
            'reference' => $safetyFileDossier->reference,
        ]);

        DossierNotifier::notify($safetyFileDossier, 'under_consultation', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $validated = $request->validate([
            // Bilingual note — both languages are required. Real-time
            // auto-translation prefills the Arabic but the wording stays
            // under the admin's control.
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
            // Which dossier fields need fixing (e.g. 'phone', 'cin', 'building_type_id'…)
            'fields_to_fix' => ['nullable', 'array'],
            'fields_to_fix.*' => ['string', 'in:cin,nom_prenom,phone,governorate_id,delegation_id,building_address,building_type_id,building_activity_id,map_position'],
            // Which document types need a new upload (array of document_type_id integers)
            'docs_to_fix'   => ['nullable', 'array'],
            'docs_to_fix.*' => ['integer', 'exists:document_types,id'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (! $safetyFileDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this dossier.']);
        }

        // Un RDV en attente : la phase RDV est ouverte — la correction n'est plus
        // possible, le résultat de la visite (passé/échoué) décide de la suite.
        if ($safetyFileDossier->appointments->contains(fn ($a) => $a->isPending())) {
            return back()->withErrors(['status' => 'A visit (RDV) is already scheduled — record its result instead of requesting a correction.']);
        }

        $fieldsToFix = $validated['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $validated['docs_to_fix'] ?? []);

        // Snapshot the current field values so the user can see what changed later
        $previousParams = [
            'cin'              => $safetyFileDossier->cin,
            'nom_prenom'       => $safetyFileDossier->nom_prenom,
            'phone'            => $safetyFileDossier->phone,
            'governorate'      => $safetyFileDossier->governorate?->name_fr ?? $safetyFileDossier->governorate?->name_ar,
            'delegation'       => $safetyFileDossier->delegation?->name_fr ?? $safetyFileDossier->delegation?->name_ar,
            'building_address' => $safetyFileDossier->building_address,
            'latitude'         => $safetyFileDossier->latitude,
            'longitude'        => $safetyFileDossier->longitude,
            'building_type'    => $safetyFileDossier->buildingType?->name,
            'building_activity'=> $safetyFileDossier->buildingActivity?->name ?? $safetyFileDossier->activity_other,
        ];

        // Archive ONLY the documents whose type was flagged for correction
        // (not everything — unflagged documents stay active and don't need re-uploading).
        $archivedDocsMeta = [];

        if (! empty($docsToFix)) {
            $docsToArchive = $safetyFileDossier->documents
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
                SafetyFileDocument::whereIn('id', $docsToArchive->pluck('id'))
                    ->update(['status' => 'archived']);
            }
        }

        $safetyFileDossier->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $safetyFileDossier->logEvent('correction_requested', $validated['admin_note'], [
            'fields_to_fix'  => $fieldsToFix,
            'docs_to_fix'    => $docsToFix,
            'previous'       => $previousParams,
            'archived_files' => $archivedDocsMeta,
        ]);

        ActivityLogger::log('safety_update', "Safety file — Correction requested for dossier #{$safetyFileDossier->id} by " . auth()->user()->name . " (fields: " . implode(', ', $fieldsToFix) . ")", $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'fields'     => $fieldsToFix,
            'docs'       => $docsToFix,
            'note'       => $validated['admin_note'],
        ]);

        DossierNotifier::notify($safetyFileDossier, 'needs_correction', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        if (! $safetyFileDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this dossier from its current status.']);
        }

        $safetyFileDossier->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $safetyFileDossier->logEvent('approved');

        ActivityLogger::log('safety_update', "Safety file — Dossier #{$safetyFileDossier->id} approved by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
        ]);

        DossierNotifier::notify($safetyFileDossier, 'approved', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $validated = $request->validate([
            // Bilingual cancellation reason — both languages are required.
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (in_array($safetyFileDossier->status, ['approved', 'cancelled'])) {
            return back()->withErrors(['status' => 'Cannot cancel a dossier that is already approved or cancelled.']);
        }

        $safetyFileDossier->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $safetyFileDossier->logEvent('cancelled', $validated['admin_note']);

        ActivityLogger::log('safety_update', "Safety file — Dossier #{$safetyFileDossier->id} cancelled by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'reason'     => $validated['admin_note'],
        ]);

        DossierNotifier::notify($safetyFileDossier, 'cancelled', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Appointments — Schedule a new visit (RDV)
    // =========================================================================

    public function scheduleAppointment(Request $request, SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $validated = $request->validate([
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'visit_time' => ['required', 'date_format:H:i'],
            // Bilingual note for the applicant (optional) — French AND Arabic.
            'note.fr'    => ['nullable', 'string', 'max:2000'],
            'note.ar'    => ['nullable', 'string', 'max:2000'],
        ]);

        // No location here: the visit takes place at the building whose
        // address is stored on the dossier itself.
        SafetyFileAppointment::create([
            'safety_file_dossier_id' => $safetyFileDossier->id,
            'scheduled_by'          => auth()->id(),
            'visit_date'            => $validated['visit_date'],
            'visit_time'            => $validated['visit_time'],
            'note'                  => $validated['note'] ?? null,
            'status'                => 'pending',
        ]);

        $safetyFileDossier->logEvent('appointment_scheduled', $validated['note'] ?? null, [
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
        ]);

        ActivityLogger::log('safety_create', "Safety file — RDV scheduled for dossier #{$safetyFileDossier->id} on {$validated['visit_date']} at {$validated['visit_time']} by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
        ]);

        DossierNotifier::notify($safetyFileDossier, 'appointment_scheduled', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Appointments — Update RDV result (passed or failed + note)
    // =========================================================================

    public function updateAppointment(Request $request, SafetyFileDossier $safetyFileDossier, SafetyFileAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        if ($appointment->safety_file_dossier_id !== $safetyFileDossier->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status'  => ['required', 'in:passed,failed'],
            // Bilingual inspection note — required (both languages) when failed.
            'note.fr' => ['nullable', 'string', 'max:2000'],
            'note.ar' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'note.fr' => 'Note (Français)',
            'note.ar' => 'Note (العربية)',
        ]);

        // The bilingual note is required when a visit is marked as failed
        // (the applicant must understand why, in his language — real-time
        // auto-translation prefills both fields as the admin types).
        if ($validated['status'] === 'failed' &&
            (empty(trim((string) ($validated['note']['fr'] ?? ''))) || empty(trim((string) ($validated['note']['ar'] ?? ''))))) {
            return back()->withErrors(['note.fr' => 'A note is required when marking a visit as failed.']);
        }

        $appointment->update([
            'status' => $validated['status'],
            'note'   => $validated['note'] ?? null,
        ]);

        $safetyFileDossier->logEvent(
            $validated['status'] === 'passed' ? 'appointment_passed' : 'appointment_failed',
            $validated['note'] ?? null,
            [
                'visit_date' => $appointment->visit_date->toDateString(),
                'visit_time' => $appointment->visit_time,
            ]
        );

        ActivityLogger::log('safety_update', "Safety file — RDV marked as {$validated['status']} for dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'status'     => $validated['status'],
            'visit_date' => $appointment->visit_date->toDateString(),
            'visit_time' => $appointment->visit_time,
            'note'       => $validated['note'] ?? null,
        ]);

        if ($validated['status'] === 'passed') {
            // ── Everything is OK: the applicant is now waiting for the
            // certificate delivery (he will pick a delivery method). ──
            $safetyFileDossier->update([
                'status'      => 'waiting_delivery',
                'admin_note'  => null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $safetyFileDossier->logEvent('waiting_delivery');

            ActivityLogger::log('safety_update', "Safety file — Dossier #{$safetyFileDossier->id} moved to waiting_delivery by " . auth()->user()->name, $safetyFileDossier, [
                'admin'     => auth()->user()->name,
                'applicant' => $safetyFileDossier->nom_prenom,
            ]);

            DossierNotifier::notify($safetyFileDossier, 'waiting_delivery', 'user', $safetyFileDossier->service);
        } else {
            // ── Inspection failed: if no visit ever passed, the dossier goes
            // back to "awaiting RDV" with the failure note. The applicant must
            // fix the building issues and a new RDV will be scheduled. ──
            if (! $safetyFileDossier->hasPassedAppointment() && $safetyFileDossier->status !== 'under_consultation') {
                $safetyFileDossier->update([
                    'status'      => 'under_consultation',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }

            DossierNotifier::notify($safetyFileDossier, 'appointment_failed', 'user', $safetyFileDossier->service);
        }

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Appointments — Cancel a pending RDV (with a required note — no deletion:
    // the RDV record always stays in the history).
    // =========================================================================

    public function cancelAppointment(Request $request, SafetyFileDossier $safetyFileDossier, SafetyFileAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        if ($appointment->safety_file_dossier_id !== $safetyFileDossier->id) {
            abort(403);
        }

        if (! $appointment->isPending()) {
            return back()->withErrors(['status' => 'Only pending appointments can be cancelled.']);
        }

        $validated = $request->validate([
            // Bilingual cancellation reason — both languages are required.
            'note.fr' => ['required', 'string', 'max:2000'],
            'note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'note.fr' => 'Note (Français)',
            'note.ar' => 'Note (العربية)',
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

        $safetyFileDossier->logEvent('appointment_cancelled', $validated['note'], $rdvDetails);

        ActivityLogger::log('safety_update', "Safety file — RDV cancelled for dossier #{$safetyFileDossier->id} (was: {$rdvDetails['visit_date']} {$rdvDetails['visit_time']}) by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'note'       => $validated['note'],
            'visit_date' => $rdvDetails['visit_date'],
            'visit_time' => $rdvDetails['visit_time'],
        ]);

        DossierNotifier::notify($safetyFileDossier, 'appointment_cancelled', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Certificate — issue (affect) the safety file certificate
    // Only allowed after at least one visit (RDV) was marked as passed.
    // Delivery: pickup at an office, or post (B3 — Poste Tunisienne).
    // Digital download is "coming soon" and cannot be selected yet.
    // =========================================================================

    public function issueCertificate(Request $request, SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        if ($safetyFileDossier->isCancelled()) {
            return back()->withErrors(['status' => 'Cannot issue a certificate for a cancelled dossier.']);
        }

        if (! $safetyFileDossier->hasPassedAppointment()) {
            return back()->withErrors(['status' => 'A certificate can only be issued after a visit (RDV) was marked as passed.']);
        }

        if ($safetyFileDossier->certificate) {
            return back()->withErrors(['status' => 'This dossier already has a certificate.']);
        }

        // The applicant's delivery choice is applied by default (prefilled form),
        // but the issuing administrator can adjust it before confirming.
        $validated = $request->validate([
            'delivery_method' => ['required', 'in:' . SafetyFileCertificate::DELIVERY_PICKUP . ',' . SafetyFileCertificate::DELIVERY_POST],
            'governorate_id'  => ['nullable', 'integer', 'exists:governorates,id'],
            'delegation_id'   => ['nullable', 'integer', 'exists:delegations,id'],
            'post_recipient'  => ['required_if:delivery_method,post', 'nullable', 'string', 'max:255'],
            'post_phone'      => ['nullable', 'string', 'max:40'],
            'post_address'    => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
            'post_tracking'   => ['nullable', 'string', 'max:60'],
            'valid_until'     => ['nullable', 'date', 'after_or_equal:today'],
            // Bilingual note (optional) — French AND Arabic.
            'note.fr'         => ['nullable', 'string', 'max:2000'],
            'note.ar'         => ['nullable', 'string', 'max:2000'],
        ]);

        $deliveryMethod = $validated['delivery_method'];
        $certificateStatus = SafetyFileCertificate::STATUS_READY;

        if ($deliveryMethod === SafetyFileCertificate::DELIVERY_POST) {
            // Post (B3): recipient data comes pre-filled from the applicant's choice
            $validated['post_recipient'] ??= $safetyFileDossier->nom_prenom;
            $validated['post_phone']     ??= $safetyFileDossier->phone;
            $validated['post_address']   ??= $safetyFileDossier->post_address;

            if (empty($validated['post_address'])) {
                return back()->withErrors(['post_address' => 'A postal address is required for the Poste Tunisienne (B3) delivery.']);
            }
        } else {
            // Pickup: an office to collect the certificate from is required
            if (empty($validated['governorate_id'])) {
                return back()->withErrors(['governorate_id' => 'A pickup gouvernorat office is required.']);
            }

            // Scoped managers can only assign a pickup location inside their territory.
            $user = auth()->user();
            if ($user->adminScope() === 'manager') {
                $ok = AdminScope::canAccess((object) [
                    'governorate_id' => $validated['governorate_id'],
                    'delegation_id'  => $validated['delegation_id'] ?? null,
                ], $user);
                if (! $ok) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'governorate_id' => 'The pickup location must be inside your assigned territory.',
                    ]);
                }
            }

            $governorate = \App\Models\Governorate::find($validated['governorate_id']);
            $delegation  = !empty($validated['delegation_id']) ? \App\Models\Delegation::find($validated['delegation_id']) : null;

            // If a delegation is selected, it must belong to the selected governorate
            if ($delegation && (int) $delegation->governorate_id !== (int) $governorate->id) {
                return back()->withErrors(['delegation_id' => 'The selected delegation does not belong to the selected governorate.']);
            }
        }

        $certificate = SafetyFileCertificate::create([
            'safety_file_dossier_id' => $safetyFileDossier->id,
            'reference'             => SafetyFileCertificate::makeReference($safetyFileDossier),
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

        $safetyFileDossier->logEvent('certificate_issued', $validated['note'] ?? null, [
            'reference'   => $certificate->reference,
            'method'      => $deliveryMethod,
            'governorate' => ($governorate ?? null)?->name_fr ?? ($governorate ?? null)?->name_ar,
            'delegation'  => ($delegation ?? null)?->name_fr ?? ($delegation ?? null)?->name_ar,
        ]);

        ActivityLogger::log('safety_create', "Safety file — Certificate {$certificate->reference} issued for dossier #{$safetyFileDossier->id} ({$deliveryMethod}) by " . auth()->user()->name, $safetyFileDossier, [
            'admin'      => auth()->user()->name,
            'applicant'  => $safetyFileDossier->nom_prenom,
            'reference'  => $certificate->reference,
            'method'     => $deliveryMethod,
        ]);

        DossierNotifier::notify($safetyFileDossier, 'certificate_issued', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Certificate — update state
    //   pickup : ready ↔ collected (picked up at the office)
    //   post   : ready → sent (B3 + tracking) → delivered
    // =========================================================================

    public function updateCertificate(Request $request, SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $validated = $request->validate([
            'action'        => ['required', 'in:collected,ready,sent,delivered'],
            'post_tracking' => ['nullable', 'string', 'max:60'],
        ]);

        $certificate = $safetyFileDossier->certificate;

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
                    'status'       => SafetyFileCertificate::STATUS_COLLECTED,
                    'collected_at' => now(),
                ]);
                $safetyFileDossier->logEvent('certificate_collected', null, [
                    'reference'   => $certificate->reference,
                    'governorate' => $certificate->governorate?->name_fr ?? $certificate->governorate?->name_ar,
                    'delegation'  => $certificate->delegation?->name_fr ?? $certificate->delegation?->name_ar,
                ]);
                ActivityLogger::log('safety_update', "Safety file — Certificate {$certificate->reference} collected for dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                DossierNotifier::notify($safetyFileDossier, 'certificate_collected', 'user', $safetyFileDossier->service);
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
                    'status'       => SafetyFileCertificate::STATUS_POST_SENT,
                    'post_sent_at' => now(),
                    'collected_at' => null,
                ]);
                $safetyFileDossier->logEvent('certificate_post_sent', null, [
                    'reference' => $certificate->reference,
                    'tracking'  => $certificate->post_tracking,
                ]);
                ActivityLogger::log('safety_update', "Safety file — Certificate {$certificate->reference} sent by post (tracking: {$certificate->post_tracking}) for dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference, 'tracking' => $certificate->post_tracking,
                ]);
                DossierNotifier::notify($safetyFileDossier, 'certificate_post_sent', 'user', $safetyFileDossier->service);
                $msg = "Certificate registered at Poste Tunisienne (B3) — tracking {$certificate->post_tracking}.";
                break;

            case 'delivered':
                $certificate->update([
                    'status'           => SafetyFileCertificate::STATUS_POST_DELIVERED,
                    'post_delivered_at' => now(),
                ]);
                $safetyFileDossier->logEvent('certificate_post_delivered', null, [
                    'reference' => $certificate->reference,
                    'tracking'  => $certificate->post_tracking,
                ]);
                ActivityLogger::log('safety_update', "Safety file — Certificate {$certificate->reference} delivered by post for dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                DossierNotifier::notify($safetyFileDossier, 'certificate_delivered', 'user', $safetyFileDossier->service);
                $msg = 'Post delivery confirmed — the applicant received the certificate.';
                break;

            case 'ready':
            default:
                $certificate->update([
                    'status'            => SafetyFileCertificate::STATUS_READY,
                    'collected_at'      => null,
                    'post_sent_at'      => null,
                    'post_delivered_at' => null,
                    'post_tracking'     => null,
                ]);
                $safetyFileDossier->logEvent('certificate_reset', null, [
                    'reference' => $certificate->reference,
                ]);
                ActivityLogger::log('safety_update', "Safety file — Certificate {$certificate->reference} reset to ready for dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
                    'admin' => auth()->user()->name, 'reference' => $certificate->reference,
                ]);
                $msg = 'Certificate marked as ready.';
                break;
        }

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Certificate — revoke (delete) — not possible once collected
    // =========================================================================

    public function revokeCertificate(SafetyFileDossier $safetyFileDossier): RedirectResponse
    {
        $this->authorize('manage safety-file-dossiers');
        AdminScope::authorizeService($safetyFileDossier->service);
        AdminScope::authorize($safetyFileDossier);

        $certificate = $safetyFileDossier->certificate;

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

        $safetyFileDossier->logEvent('certificate_deleted', null, [
            'reference'   => $reference,
            'governorate' => $governorate,
            'delegation'  => $delegation,
        ]);

        ActivityLogger::log('safety_delete', "Safety file — Certificate {$reference} revoked from dossier #{$safetyFileDossier->id} by " . auth()->user()->name, $safetyFileDossier, [
            'admin'     => auth()->user()->name,
            'applicant' => $safetyFileDossier->nom_prenom,
            'reference' => $reference,
        ]);

        DossierNotifier::notify($safetyFileDossier, 'certificate_revoked', 'user', $safetyFileDossier->service);

        return redirect()->route('admin.safety-file-dossiers.show', $safetyFileDossier);
    }

    // =========================================================================
    // Download an uploaded document (active or archived)
    // =========================================================================

    public function downloadDocument(SafetyFileDocument $document)
    {
        $this->authorize('view safety-file-dossiers');
        AdminScope::authorizeService($document->dossier->service);
        AdminScope::authorize($document->dossier);

        abort_unless(\Storage::disk('local')->exists($document->file_path), 404);

        return \Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
