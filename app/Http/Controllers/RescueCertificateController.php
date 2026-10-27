<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\RescueCertificate;
use App\Models\RescueDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RescueCertificateController extends Controller
{
    // =========================================================================
    // Index — list all local requests for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        // Draft requests (incomplete)
        $drafts = RescueCertificate::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        // Submitted / processed requests
        $submitted = RescueCertificate::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'under_consultation', 'needs_correction', 'approved', 'served', 'cancelled'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('rescue-certificates.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
        ]);
    }

    // =========================================================================
    // Step 1 — Show form (victim identity + rescue operation details)
    // =========================================================================

    public function create(): View
    {
        return view('rescue-certificates.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft (go back from Step 2)
    // =========================================================================

    public function editStep1(RescueCertificate $rescueCertificate): View|RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        return view('rescue-certificates.create', [
            'gouvernorats'   => $this->gouvernorats(),
            'delegations'    => $this->delegations(),
            'rescueCertificate' => $rescueCertificate, // pre-fills the form & unlocks "editing" mode
        ]);
    }

    // =========================================================================
    // Step 1 — Update existing draft & redirect back to Step 2
    // =========================================================================

    public function updateStep1(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate($this->step1Rules());

        $rescueCertificate->update($this->step1Data($validated));

        return redirect()->route('rescue-certificates.step2.show', $rescueCertificate);
    }

    // =========================================================================
    // Step 1 — Save draft & redirect to Step 2
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $rescueCertificate = RescueCertificate::create(array_merge(
            $this->step1Data($validated),
            [
                'user_id'      => auth()->id(),
                'status'       => 'draft',
                'current_step' => 1,
            ]
        ));

        return redirect()->route('rescue-certificates.step2.show', $rescueCertificate);
    }

    // =========================================================================
    // Step 2 — Show document upload form
    // =========================================================================

    public function showStep2(RescueCertificate $rescueCertificate): View|RedirectResponse
    {
        // Make sure this certificate belongs to the authenticated user
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if ($rescueCertificate->status !== 'draft') {
            return redirect()->route('rescue-certificates.index')
                ->withErrors(['status' => __('rescue_certificate.not_editable')]);
        }

        $rescueCertificate->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
            ->orderBy('sort_order')
            ->get();

        // Load any already-uploaded documents for this certificate
        $uploadedDocs = RescueDocument::where('rescue_certificate_id', $rescueCertificate->id)
            ->get()
            ->keyBy('document_type_id');

        return view('rescue-certificates.create-step2', [
            'rescueCertificate' => $rescueCertificate,
            'documentTypes'     => $documentTypes,
            'uploadedDocs'      => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 2 — Save uploaded documents & submit the request
    // =========================================================================

    public function storeStep2(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if ($rescueCertificate->status !== 'draft') {
            return redirect()->route('rescue-certificates.index')
                ->withErrors(['status' => __('rescue_certificate.not_editable')]);
        }

        $rules = [];
        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
            ->orderBy('sort_order')
            ->pluck('id');

        foreach ($documentTypeIds as $docTypeId) {
            $rules['documents.' . $docTypeId] = ['nullable', 'file', 'max:10240']; // 10MB max
        }

        $request->validate($rules);

        $uploadDir = "rescue-documents/{$rescueCertificate->id}";

        foreach ($documentTypeIds as $docTypeId) {
            $file = $request->file("documents.{$docTypeId}");
            if ($file && $file->isValid()) {
                $path = $file->store($uploadDir, 'local');

                RescueDocument::updateOrCreate(
                    [
                        'rescue_certificate_id' => $rescueCertificate->id,
                        'document_type_id'      => $docTypeId,
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
        $rescueCertificate->update([
            'current_step' => 2,
            'status'       => 'completed',
        ]);

        $rescueCertificate->logEvent('submitted');

        return redirect()
            ->route('rescue-certificates.index')
            ->with('success', __('rescue_certificate.submit_success'));
    }

    // =========================================================================
    // Resubmit — user fixes a needs_correction request and re-submits step 2
    // The request status goes back to drafted so admin can review again.
    // =========================================================================

    public function resubmit(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $rescueCertificate->isNeedsCorrection()) {
            return redirect()->route('rescue-certificates.index')
                ->withErrors(['status' => __('rescue_certificate.resubmit_forbidden')]);
        }

        // Re-open to step 2 so user can upload corrected documents
        $previousNote = $rescueCertificate->admin_note;

        $rescueCertificate->update([
            'status'       => 'draft',
            'current_step' => 2,
            'admin_note'   => null,
        ]);

        $rescueCertificate->logEvent('resubmitted', $previousNote ? 'In response to: ' . $previousNote : null);

        return redirect()
            ->route('rescue-certificates.step2.show', $rescueCertificate)
            ->with('info', __('rescue_certificate.resubmit_info'));
    }

    // =========================================================================
    // Download one of my uploaded documents
    // =========================================================================

    public function downloadDocument(RescueDocument $rescueDocument)
    {
        abort_unless($rescueDocument->certificate->user_id === auth()->id(), 403);

        return \Storage::disk('local')->download($rescueDocument->file_path, $rescueDocument->original_name);
    }

    // =========================================================================
    // Delete a local draft
    // =========================================================================

    public function destroy(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $rescueCertificate->isDraft()) {
            return redirect()->route('rescue-certificates.index')
                ->withErrors(['status' => __('rescue_certificate.delete_forbidden')]);
        }

        $rescueCertificate->delete();

        return redirect()->route('rescue-certificates.index')
            ->with('success', __('rescue_certificate.delete_success'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function gouvernorats(): array
    {
        return Governorate::forSelect();
    }

    private function delegations(): array
    {
        return Governorate::delegationsMap();
    }

    private function step1Rules(): array
    {
        return [
            'victim_is_minor'   => ['nullable', 'boolean'],
            'parent_name'       => ['required_if:victim_is_minor,1', 'nullable', 'string', 'max:255'],
            'parent_cin'        => ['required_if:victim_is_minor,1', 'nullable', 'string', 'max:20'],
            'spouse_case'       => ['nullable', 'boolean'],
            'cin'               => ['required', 'string', 'max:20'],
            'nom_prenom'        => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'max:20'],
            'governorate_id'    => ['required', 'integer', 'between:1,24', 'exists:governorates,id'],
            'delegation_id'     => ['required', 'integer', 'exists:delegations,id'],
            'incident_address'  => ['required', 'string', 'max:500'],
            'incident_type'     => ['required', 'string', 'in:' . implode(',', RescueCertificate::TYPES)],
            'incident_date'     => ['required', 'date'],
            'incident_time'     => ['nullable', 'date_format:H:i'],
            'incident_description' => ['required', 'string', 'max:5000'],
            'notes'             => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function step1Data(array $validated): array
    {
        return [
            'victim_is_minor'      => (bool) ($validated['victim_is_minor'] ?? false),
            'parent_name'          => $validated['parent_name'] ?? null,
            'parent_cin'           => $validated['parent_cin'] ?? null,
            'spouse_case'          => $validated['spouse_case'] ?? false,
            'cin'                  => $validated['cin'],
            'nom_prenom'           => $validated['nom_prenom'],
            'phone'                => $validated['phone'],
            'governorate_id'       => $validated['governorate_id'],
            'delegation_id'        => $validated['delegation_id'],
            'incident_address'     => $validated['incident_address'],
            'incident_type'        => $validated['incident_type'],
            'incident_date'        => $validated['incident_date'],
            'incident_time'        => $validated['incident_time'] ?? null,
            'incident_description' => $validated['incident_description'],
            'notes'                => $validated['notes'] ?? null,
        ];
    }
}
