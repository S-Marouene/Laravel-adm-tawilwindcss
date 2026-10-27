<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\PreventivePresenceRequest;
use App\Models\PresenceDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreventivePresenceController extends Controller
{
    // =========================================================================
    // Index — list all local requests for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        // Draft requests (incomplete)
        $drafts = PreventivePresenceRequest::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        // Submitted / processed requests
        $submitted = PreventivePresenceRequest::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'under_consultation', 'needs_correction', 'approved', 'served', 'cancelled'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('preventive-presence.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
        ]);
    }

    // =========================================================================
    // Step 1 — Show form (applicant + event details)
    // =========================================================================

    public function create(): View
    {
        return view('preventive-presence.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft (go back from Step 2)
    // =========================================================================

    public function editStep1(PreventivePresenceRequest $presenceRequest): View|RedirectResponse
    {
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        return view('preventive-presence.create', [
            'gouvernorats'   => $this->gouvernorats(),
            'delegations'    => $this->delegations(),
            'presenceRequest' => $presenceRequest,   // pre-fills the form & unlocks "editing" mode
        ]);
    }

    // =========================================================================
    // Step 1 — Update existing draft & redirect back to Step 2
    // =========================================================================

    public function updateStep1(Request $request, PreventivePresenceRequest $presenceRequest): RedirectResponse
    {
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate($this->step1Rules());

        $presenceRequest->update($this->step1Data($validated));

        return redirect()->route('preventive-presence.step2.show', $presenceRequest);
    }

    // =========================================================================
    // Step 1 — Save draft & redirect to Step 2
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $presenceRequest = PreventivePresenceRequest::create(array_merge(
            $this->step1Data($validated),
            [
                'user_id'      => auth()->id(),
                'status'       => 'draft',
                'current_step' => 1,
            ]
        ));

        return redirect()->route('preventive-presence.step2.show', $presenceRequest);
    }

    // =========================================================================
    // Step 2 — Show document upload form
    // =========================================================================

    public function showStep2(PreventivePresenceRequest $presenceRequest): View|RedirectResponse
    {
        // Make sure this request belongs to the authenticated user
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($presenceRequest->status !== 'draft') {
            return redirect()->route('preventive-presence.index')
                ->withErrors(['status' => __('preventive_presence.not_editable')]);
        }

        $presenceRequest->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTIVE_PRESENCE)
            ->orderBy('sort_order')
            ->get();

        // Load any already-uploaded documents for this request
        $uploadedDocs = PresenceDocument::where('preventive_presence_request_id', $presenceRequest->id)
            ->get()
            ->keyBy('document_type_id');

        return view('preventive-presence.create-step2', [
            'presenceRequest' => $presenceRequest,
            'documentTypes'   => $documentTypes,
            'uploadedDocs'    => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 2 — Save uploaded documents & submit the request
    // =========================================================================

    public function storeStep2(Request $request, PreventivePresenceRequest $presenceRequest): RedirectResponse
    {
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($presenceRequest->status !== 'draft') {
            return redirect()->route('preventive-presence.index')
                ->withErrors(['status' => __('preventive_presence.not_editable')]);
        }

        $rules = [];
        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_PREVENTIVE_PRESENCE)
            ->orderBy('sort_order')
            ->pluck('id');

        foreach ($documentTypeIds as $docTypeId) {
            $rules['documents.' . $docTypeId] = ['nullable', 'file', 'max:10240']; // 10MB max
        }

        $request->validate($rules);

        $uploadDir = "presence-documents/{$presenceRequest->id}";

        foreach ($documentTypeIds as $docTypeId) {
            $file = $request->file("documents.{$docTypeId}");
            if ($file && $file->isValid()) {
                $path = $file->store($uploadDir, 'local');

                PresenceDocument::updateOrCreate(
                    [
                        'preventive_presence_request_id' => $presenceRequest->id,
                        'document_type_id'               => $docTypeId,
                    ],
                    [
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getMimeType(),
                        'size'          => $file->getSize(),
                        'status'        => 'uploaded',
                    ]
                );
            }
        }

        // Mark request as completed (submitted for review)
        $presenceRequest->update([
            'current_step' => 2,
            'status'       => 'completed',
        ]);

        return redirect()
            ->route('preventive-presence.index')
            ->with('success', __('preventive_presence.submit_success'));
    }

    // =========================================================================
    // Resubmit — user fixes a needs_correction request and re-submits step 2
    // The request status goes back to drafted so admin can review again.
    // =========================================================================

    public function resubmit(Request $request, PreventivePresenceRequest $presenceRequest): RedirectResponse
    {
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $presenceRequest->isNeedsCorrection()) {
            return redirect()->route('preventive-presence.index')
                ->withErrors(['status' => __('preventive_presence.resubmit_forbidden')]);
        }

        // Re-open to step 2 so user can upload corrected documents
        $presenceRequest->update([
            'status'       => 'draft',
            'current_step' => 2,
            'admin_note'   => null,
        ]);

        return redirect()
            ->route('preventive-presence.step2.show', $presenceRequest)
            ->with('info', __('preventive_presence.resubmit_info'));
    }

    // =========================================================================
    // Delete a local draft
    // =========================================================================

    public function destroy(Request $request, PreventivePresenceRequest $presenceRequest): RedirectResponse
    {
        if ($presenceRequest->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $presenceRequest->isDraft()) {
            return redirect()->route('preventive-presence.index')
                ->withErrors(['status' => __('preventive_presence.delete_forbidden')]);
        }

        $presenceRequest->delete();

        return redirect()->route('preventive-presence.index')
            ->with('success', __('preventive_presence.delete_success'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function step1Rules(): array
    {
        return [
            'cin'                 => ['required', 'string', 'max:20'],
            'nom_prenom'          => ['required', 'string', 'max:255'],
            'phone'               => ['required', 'string', 'max:20'],
            'governorate_id'      => ['required', 'integer', 'between:1,24', 'exists:governorates,id'],
            'delegation_id'       => ['required', 'integer', 'exists:delegations,id'],
            'event_title'         => ['required', 'string', 'max:300'],
            'event_address'       => ['required', 'string', 'max:500'],
            'event_date'          => ['required', 'date'],
            'event_end_date'      => ['nullable', 'date', 'after_or_equal:event_date'],
            'event_time'          => ['nullable', 'date_format:H:i'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'notes'               => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function step1Data(array $validated): array
    {
        return [
            'cin'                 => $validated['cin'],
            'nom_prenom'          => $validated['nom_prenom'],
            'phone'               => $validated['phone'],
            'governorate_id'      => $validated['governorate_id'],
            'delegation_id'       => $validated['delegation_id'],
            'event_title'         => $validated['event_title'],
            'event_address'       => $validated['event_address'],
            'event_date'          => $validated['event_date'],
            'event_end_date'      => $validated['event_end_date'] ?? null,
            'event_time'          => $validated['event_time'] ?? null,
            'expected_attendance' => $validated['expected_attendance'] ?? null,
            'notes'               => $validated['notes'] ?? null,
        ];
    }

    private function gouvernorats(): array
    {
        return Governorate::forSelect();
    }

    private function delegations(): array
    {
        return Governorate::delegationsMap();
    }
}
