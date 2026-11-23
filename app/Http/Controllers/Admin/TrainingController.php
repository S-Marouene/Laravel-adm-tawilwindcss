<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\TrainingDocument;
use App\Models\TrainingRequest;
use App\Models\TrainingSession;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    // =========================================================================
    // Index
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);

        $query = TrainingRequest::with(['user', 'governorate', 'delegation'])
            ->whereNotIn('status', ['draft'])
            ->latest();

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

        $requests = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.training.index', compact('requests'));
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(TrainingRequest $trainingRequest): View
    {
        $this->authorize('view training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        $trainingRequest->load([
            'user', 'governorate', 'delegation',
            'reviewer', 'issuer', 'resultReviewer',
            'documents.documentType', 'events.actor', 'sessions.recorder',
            'pickupGovernorate', 'pickupDelegation',
        ]);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_TRAINING)
            ->orderBy('sort_order')->get();
        $uploadedDocs  = $trainingRequest->documents->keyBy('document_type_id');

        $user = auth()->user();
        if ($user->adminScope() === 'manager') {
            $territories = $user->territories;
            $govIds = $territories->pluck('governorate_id')->unique();
            $governorates = Governorate::whereIn('id', $govIds)
                ->with(['delegations' => function ($q) use ($territories) {
                    $specificDelIds = $territories->whereNotNull('delegation_id')->pluck('delegation_id');
                    $wholeGovIds    = $territories->whereNull('delegation_id')->pluck('governorate_id');
                    $q->where(function ($sub) use ($specificDelIds, $wholeGovIds) {
                        $sub->whereIn('governorate_id', $wholeGovIds)->orWhereIn('id', $specificDelIds);
                    });
                }])->orderBy('id')->get();
        } else {
            $governorates = Governorate::with('delegations')->orderBy('id')->get();
        }

        return view('admin.training.show', [
            'trainingRequest' => $trainingRequest,
            'documentTypes'   => $documentTypes,
            'uploadedDocs'    => $uploadedDocs,
            'governorates'    => $governorates,
            'gouvernorats'    => Governorate::forSelect(),
            'delegations'     => Governorate::delegationsMap(),
        ]);
    }

    // =========================================================================
    // Advance to Under Consultation
    // =========================================================================

    public function advance(TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if (! in_array($trainingRequest->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this request from its current status.']);
        }

        $trainingRequest->update([
            'status'      => TrainingRequest::STATUS_UNDER_CONSULTATION,
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $trainingRequest->logEvent('advanced_to_consultation');

        DossierNotifier::notify($trainingRequest, 'under_consultation', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_advanced'));
    }

    // =========================================================================
    // Request Correction
    // =========================================================================

    public function requestCorrection(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        $validated = $request->validate([
            'admin_note.fr'   => ['required', 'string', 'max:2000'],
            'admin_note.ar'   => ['required', 'string', 'max:2000'],
            'fields_to_fix'   => ['nullable', 'array'],
            'fields_to_fix.*' => ['string', 'in:cin,nom_prenom,phone,governorate_id,delegation_id,training_type,training_kind,notes'],
            'docs_to_fix'     => ['nullable', 'array'],
            'docs_to_fix.*'   => ['integer', 'exists:document_types,id'],
        ], [], ['admin_note.fr' => 'Note (Français)', 'admin_note.ar' => 'Note (العربية)']);

        if (! $trainingRequest->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this request.']);
        }

        $fieldsToFix = $validated['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $validated['docs_to_fix'] ?? []);

        $previousParams = [
            'cin'           => $trainingRequest->cin,
            'nom_prenom'    => $trainingRequest->nom_prenom,
            'phone'         => $trainingRequest->phone,
            'governorate_id'=> $trainingRequest->governorate?->name_fr ?? $trainingRequest->governorate?->name_ar,
            'delegation_id' => $trainingRequest->delegation?->name_fr ?? $trainingRequest->delegation?->name_ar,
            'training_type' => $trainingRequest->training_type,
            'training_kind' => $trainingRequest->training_kind,
            'notes'         => $trainingRequest->notes,
        ];

        $archivedDocsMeta = [];
        if (! empty($docsToFix)) {
            $docsToArchive = $trainingRequest->documents
                ->filter(fn ($d) => $d->status !== 'archived' && in_array((int) $d->document_type_id, $docsToFix));
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
                TrainingDocument::whereIn('id', $docsToArchive->pluck('id'))
                    ->update(['status' => 'archived']);
            }
        }

        $trainingRequest->update([
            'status'      => TrainingRequest::STATUS_NEEDS_CORRECTION,
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $trainingRequest->logEvent('correction_requested', $validated['admin_note'], [
            'fields_to_fix'  => $fieldsToFix,
            'docs_to_fix'    => $docsToFix,
            'previous'       => $previousParams,
            'archived_files' => $archivedDocsMeta,
        ]);
        $trainingRequest->refresh();

        DossierNotifier::notify($trainingRequest, 'needs_correction', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_correction'));
    }

    // =========================================================================
    // Approve → payment pending
    // =========================================================================

    public function approve(TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_UNDER_CONSULTATION) {
            return back()->withErrors(['status' => 'Cannot approve this request from its current status.']);
        }

        $trainingRequest->update([
            'status'      => TrainingRequest::STATUS_PAYMENT_PENDING,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $trainingRequest->logEvent('approved');

        DossierNotifier::notify($trainingRequest, 'payment_pending', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_approved'));
    }

    // =========================================================================
    // Record Payment
    // =========================================================================

    public function recordPayment(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if (! in_array($trainingRequest->status, [TrainingRequest::STATUS_PAYMENT_PENDING, TrainingRequest::STATUS_PAYMENT_CONFIRMED, TrainingRequest::STATUS_SCHEDULED])) {
            return back()->withErrors(['status' => 'Payment can only be recorded for an approved request.']);
        }

        $validated = $request->validate([
            'fee_amount'     => ['required', 'numeric', 'min:0'],
            'receipt_number' => ['required', 'string', 'max:50'],
        ]);

        $trainingRequest->update([
            'fee_amount'     => $validated['fee_amount'],
            'receipt_number' => $validated['receipt_number'],
            'paid_at'        => $trainingRequest->paid_at ?? now(),
            'payment_method' => 'office',
            'status'         => TrainingRequest::STATUS_PAYMENT_CONFIRMED,
        ]);

        $trainingRequest->logEvent('payment_recorded', null, [
            'fee_amount'     => $validated['fee_amount'],
            'receipt_number' => $validated['receipt_number'],
        ]);

        DossierNotifier::notify($trainingRequest, 'payment_confirmed', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_payment_recorded'));
    }

    // =========================================================================
    // Schedule formation (set date + duration)
    // =========================================================================

    public function schedule(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_PAYMENT_CONFIRMED) {
            return back()->withErrors(['status' => 'Formation can only be scheduled after payment confirmation.']);
        }

        $validated = $request->validate([
            'training_start_date'   => ['required', 'date', 'after_or_equal:today'],
            'training_start_time'   => ['nullable', 'date_format:H:i'],
            'training_duration_days'=> ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $trainingRequest->update([
            'status'                 => TrainingRequest::STATUS_SCHEDULED,
            'training_start_date'    => $validated['training_start_date'],
            'training_start_time'    => $validated['training_start_time'] ?? null,
            'training_duration_days' => $validated['training_duration_days'],
        ]);

        $trainingRequest->logEvent('scheduled', null, [
            'start_date'     => $validated['training_start_date'],
            'start_time'     => $validated['training_start_time'] ?? null,
            'duration_days'  => $validated['training_duration_days'],
        ]);

        DossierNotifier::notify($trainingRequest, 'scheduled', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_scheduled'));
    }

    // =========================================================================
    // Start formation
    // =========================================================================

    public function start(TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_SCHEDULED) {
            return back()->withErrors(['status' => 'Formation must be scheduled before starting.']);
        }

        $trainingRequest->update(['status' => TrainingRequest::STATUS_IN_PROGRESS]);
        $trainingRequest->logEvent('started');

        DossierNotifier::notify($trainingRequest, 'in_progress', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_started'));
    }

    // =========================================================================
    // Record daily attendance
    // =========================================================================

    public function recordAttendance(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_IN_PROGRESS) {
            return back()->withErrors(['status' => 'Attendance can only be recorded while the formation is in progress.']);
        }

        $validated = $request->validate([
            'session_date' => ['required', 'date'],
            'attendance'   => ['required', 'in:present,absent,excused'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        TrainingSession::updateOrCreate(
            [
                'training_request_id' => $trainingRequest->id,
                'session_date'        => $validated['session_date'],
            ],
            [
                'attendance'  => $validated['attendance'],
                'note'        => $validated['note'] ?? null,
                'recorded_by' => auth()->id(),
            ]
        );

        $trainingRequest->logEvent('attendance_recorded', null, [
            'session_date' => $validated['session_date'],
            'attendance'   => $validated['attendance'],
        ]);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_attendance_recorded'));
    }

    // =========================================================================
    // Record result (passed / failed)
    // =========================================================================

    public function recordResult(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_IN_PROGRESS) {
            return back()->withErrors(['status' => 'Result can only be recorded while the formation is in progress.']);
        }

        $validated = $request->validate([
            'result'          => ['required', 'in:passed,failed'],
            'result_note.fr'  => ['nullable', 'string', 'max:2000'],
            'result_note.ar'  => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['result'] === 'passed'
            ? TrainingRequest::STATUS_PASSED
            : TrainingRequest::STATUS_FAILED;

        $trainingRequest->update([
            'status'      => $newStatus,
            'result'      => $validated['result'],
            'result_note' => $validated['result_note'] ?? null,
            'result_at'   => now(),
            'result_by'   => auth()->id(),
        ]);

        $trainingRequest->logEvent('result_recorded', $validated['result_note'] ?? null, [
            'result' => $validated['result'],
        ]);

        $event = $validated['result'] === 'passed' ? 'passed' : 'failed';
        DossierNotifier::notify($trainingRequest, $event, 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_result_recorded'));
    }

    // =========================================================================
    // Issue Certificate (only for passed)
    // =========================================================================

    public function issueCertificate(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if ($trainingRequest->status !== TrainingRequest::STATUS_PASSED) {
            return back()->withErrors(['status' => 'Certificate can only be issued for a passed formation.']);
        }

        if ($trainingRequest->hasCertificate()) {
            return back()->withErrors(['status' => 'A certificate has already been issued.']);
        }

        $validated = $request->validate([
            'delivery_method'       => ['required', 'in:pickup,post'],
            'pickup_governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'pickup_delegation_id'  => ['nullable', 'integer', 'exists:delegations,id'],
            'post_recipient'        => ['required_if:delivery_method,post', 'nullable', 'string', 'max:255'],
            'post_phone'            => ['nullable', 'string', 'max:40'],
            'post_address'          => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
            'post_tracking'         => ['nullable', 'string', 'max:60'],
            'valid_until'           => ['nullable', 'date', 'after_or_equal:today'],
            'certificate_number'    => ['nullable', 'string', 'max:50'],
        ]);

        if ($validated['delivery_method'] === TrainingRequest::DELIVERY_PICKUP && empty($validated['pickup_governorate_id'])) {
            return back()->withErrors(['pickup_governorate_id' => 'A pickup governorate is required.']);
        }

        $certRef = $validated['certificate_number'] ?: TrainingRequest::makeReference($trainingRequest);

        $trainingRequest->update([
            'cert_status'           => TrainingRequest::CERT_STATUS_READY,
            'issued_by'             => auth()->id(),
            'issued_at'             => now()->toDateString(),
            'valid_until'           => $validated['valid_until'] ?? null,
            'delivery_method'       => $validated['delivery_method'],
            'certificate_number'    => $certRef,
            'pickup_governorate_id' => $validated['pickup_governorate_id'] ?? null,
            'pickup_delegation_id'  => $validated['pickup_delegation_id'] ?? null,
            'post_recipient'        => $validated['post_recipient'] ?? null,
            'post_phone'            => $validated['post_phone'] ?? null,
            'post_address'          => $validated['post_address'] ?? null,
            'post_tracking'         => $validated['post_tracking'] ?? null,
        ]);

        $trainingRequest->logEvent('certificate_issued', null, [
            'certificate_number' => $certRef,
            'method'             => $validated['delivery_method'],
        ]);

        DossierNotifier::notify($trainingRequest, 'certificate_issued', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_cert_issued'));
    }

    // =========================================================================
    // Update Certificate state
    // =========================================================================

    public function updateCertificate(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        $validated = $request->validate([
            'action'        => ['required', 'in:collected,ready,sent,delivered'],
            'post_tracking' => ['nullable', 'string', 'max:60'],
        ]);

        if (! $trainingRequest->hasCertificate()) {
            return back()->withErrors(['status' => 'No certificate has been issued yet.']);
        }

        switch ($validated['action']) {
            case 'collected':
                $trainingRequest->update([
                    'cert_status'  => TrainingRequest::CERT_STATUS_COLLECTED,
                    'delivered_at' => now(),
                    'status'       => TrainingRequest::STATUS_SERVED,
                ]);
                $trainingRequest->logEvent('certificate_collected');
                DossierNotifier::notify($trainingRequest, 'certificate_delivered', 'user', Service::SERVICE_TRAINING);
                break;
            case 'sent':
                if ($validated['post_tracking']) {
                    $trainingRequest->update(['post_tracking' => $validated['post_tracking']]);
                    $trainingRequest->refresh();
                }
                if (empty($trainingRequest->post_tracking)) {
                    return back()->withErrors(['post_tracking' => 'A tracking number is required.']);
                }
                $trainingRequest->update([
                    'cert_status'  => TrainingRequest::CERT_STATUS_POST_SENT,
                    'post_sent_at' => now(),
                ]);
                $trainingRequest->logEvent('certificate_post_sent', null, ['tracking' => $trainingRequest->post_tracking]);
                DossierNotifier::notify($trainingRequest, 'certificate_delivered', 'user', Service::SERVICE_TRAINING);
                break;
            case 'delivered':
                $trainingRequest->update([
                    'cert_status'       => TrainingRequest::CERT_STATUS_POST_DELIVERED,
                    'post_delivered_at' => now(),
                    'delivered_at'      => now(),
                    'status'            => TrainingRequest::STATUS_SERVED,
                ]);
                $trainingRequest->logEvent('certificate_post_delivered');
                DossierNotifier::notify($trainingRequest, 'certificate_delivered', 'user', Service::SERVICE_TRAINING);
                break;
            default: // ready
                $trainingRequest->update([
                    'cert_status'       => TrainingRequest::CERT_STATUS_READY,
                    'delivered_at'      => null,
                    'post_sent_at'      => null,
                    'post_delivered_at' => null,
                    'post_tracking'     => null,
                ]);
                $trainingRequest->logEvent('certificate_reset');
                break;
        }

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_cert_updated'));
    }

    // =========================================================================
    // Revoke Certificate
    // =========================================================================

    public function revokeCertificate(TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        if (! $trainingRequest->hasCertificate()) {
            return back()->withErrors(['status' => 'No certificate to revoke.']);
        }

        if (in_array($trainingRequest->cert_status, [TrainingRequest::CERT_STATUS_COLLECTED, TrainingRequest::CERT_STATUS_POST_DELIVERED])) {
            return back()->withErrors(['status' => 'A delivered certificate cannot be revoked.']);
        }

        $certNum = $trainingRequest->certificate_number;
        $trainingRequest->update([
            'cert_status'           => null,
            'issued_by'             => null,
            'issued_at'             => null,
            'valid_until'           => null,
            'delivery_method'       => null,
            'certificate_number'    => null,
            'pickup_governorate_id' => null,
            'pickup_delegation_id'  => null,
            'post_recipient'        => null,
            'post_phone'            => null,
            'post_address'          => null,
            'post_tracking'         => null,
            'post_sent_at'          => null,
            'post_delivered_at'     => null,
            'delivered_at'          => null,
        ]);

        $trainingRequest->logEvent('certificate_revoked', null, ['certificate_number' => $certNum]);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_cert_revoked'));
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorize('manage training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingRequest);

        $validated = $request->validate([
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], ['admin_note.fr' => 'Note (Français)', 'admin_note.ar' => 'Note (العربية)']);

        $cancellableStatuses = [
            TrainingRequest::STATUS_COMPLETED,
            TrainingRequest::STATUS_UNDER_CONSULTATION,
            TrainingRequest::STATUS_NEEDS_CORRECTION,
            TrainingRequest::STATUS_PAYMENT_PENDING,
            TrainingRequest::STATUS_PAYMENT_CONFIRMED,
            TrainingRequest::STATUS_SCHEDULED,
        ];

        if (! in_array($trainingRequest->status, $cancellableStatuses)) {
            return back()->withErrors(['status' => 'Cannot cancel this request from its current status.']);
        }

        $trainingRequest->update([
            'status'      => TrainingRequest::STATUS_CANCELLED,
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $trainingRequest->logEvent('cancelled', $validated['admin_note']);
        $trainingRequest->refresh();

        DossierNotifier::notify($trainingRequest, 'cancelled', 'user', Service::SERVICE_TRAINING);

        return redirect()->route('admin.training.show', $trainingRequest)
            ->with('success', __('training.admin_cancelled'));
    }

    // =========================================================================
    // Document download
    // =========================================================================

    public function downloadDocument(TrainingDocument $trainingDocument)
    {
        $this->authorize('view training');
        AdminScope::authorizeService(Service::SERVICE_TRAINING);
        AdminScope::authorize($trainingDocument->trainingRequest);

        abort_unless(\Storage::disk('local')->exists($trainingDocument->file_path), 404);

        return \Storage::disk('local')->download($trainingDocument->file_path, $trainingDocument->original_name);
    }
}
