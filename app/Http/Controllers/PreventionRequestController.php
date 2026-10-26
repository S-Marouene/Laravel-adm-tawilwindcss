<?php

namespace App\Http\Controllers;

use App\Models\BuildingType;
use App\Models\DocumentType;
use App\Models\DossierDocument;
use App\Models\Governorate;
use App\Models\PreventionDossier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PreventionRequestController extends Controller
{
    // =========================================================================
    // Index — list all local dossiers for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        // Draft dossiers (incomplete)
        $drafts = PreventionDossier::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        // Submitted / completed / under_consultation / needs_correction / approved / cancelled dossiers
        $submitted = PreventionDossier::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'submitted', 'under_consultation', 'needs_correction', 'approved', 'cancelled'])
            ->with(['governorate', 'delegation', 'buildingType', 'appointments'])
            ->latest()
            ->get();

        return view('prevention-requests.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
        ]);
    }

    // =========================================================================
    // Step 1 — Show form
    // =========================================================================

    public function create(): View
    {
        return view('prevention-requests.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft (go back from Step 2)
    // =========================================================================

    public function editStep1(PreventionDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        return view('prevention-requests.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'dossier'      => $dossier,   // pre-fills the form & unlocks "editing" mode
        ]);
    }

    // =========================================================================
    // Step 1 — Update existing draft & redirect back to Step 2
    // =========================================================================

    public function updateStep1(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'cin'            => ['required', 'string', 'max:20'],
            'nom_prenom'     => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'code_structure' => ['required', 'integer', 'between:1,24'],
            'delegation_id'  => ['required', 'integer', 'exists:delegations,id'],
        ]);

        $dossier->update([
            'cin'            => $validated['cin'],
            'nom_prenom'     => $validated['nom_prenom'],
            'phone'          => $validated['phone'],
            'governorate_id' => $validated['code_structure'],
            'delegation_id'  => $validated['delegation_id'],
        ]);

        return redirect()->route('prevention-requests.step2.show', $dossier);
    }

    // =========================================================================
    // Step 1 — Save draft & redirect to Step 2
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cin'            => ['required', 'string', 'max:20'],
            'nom_prenom'     => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'code_structure' => ['required', 'integer', 'between:1,24'],
            'delegation_id'  => ['required', 'integer', 'exists:delegations,id'],
        ]);

        $dossier = PreventionDossier::create([
            'user_id'        => auth()->id(),
            'status'         => 'draft',
            'current_step'   => 1,
            'cin'            => $validated['cin'],
            'nom_prenom'     => $validated['nom_prenom'],
            'phone'          => $validated['phone'],
            'governorate_id' => $validated['code_structure'],
            'delegation_id'  => $validated['delegation_id'],
        ]);

        return redirect()->route('prevention-requests.step2.show', $dossier);
    }

    // =========================================================================
    // Step 2 — Show form
    // =========================================================================

    public function showStep2(PreventionDossier $dossier): View|RedirectResponse
    {
        // Make sure this dossier belongs to the authenticated user
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load(['governorate', 'delegation', 'buildingType', 'buildingActivity']);

        return view('prevention-requests.create-step2', [
            'dossier'       => $dossier,
            'buildingTypes' => BuildingType::forSelect(),
            'activitiesMap' => BuildingType::activitiesMap(),
        ]);
    }

    // =========================================================================
    // Step 2 — Save & submit to external API (or mark completed)
    // =========================================================================

    public function storeStep2(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'building_type_id'    => ['required', 'integer', 'exists:building_types,id'],
            'building_activity_id'=> ['nullable', 'integer', 'exists:building_activities,id'],
            'activity_other'      => ['nullable', 'string', 'max:255'],
        ]);

        // Update dossier with step-2 data
        $dossier->update([
            'building_type_id'     => $validated['building_type_id'],
            'building_activity_id' => $validated['building_activity_id'] ?? null,
            'activity_other'       => $validated['activity_other'] ?? null,
            'current_step'         => 2,
        ]);

        return redirect()->route('prevention-requests.step3.show', $dossier);
    }

    // =========================================================================
    // Step 3 — Show document upload form
    // =========================================================================

    public function showStep3(PreventionDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load(['governorate', 'delegation', 'buildingType', 'buildingActivity']);

        $documentTypes = DocumentType::orderBy('sort_order')->get();

        // Load any already-uploaded documents for this dossier
        $uploadedDocs = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->get()
            ->keyBy('document_type_id');

        return view('prevention-requests.create-step3', [
            'dossier'       => $dossier,
            'documentTypes'  => $documentTypes,
            'uploadedDocs'   => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 3 — Save uploaded documents & submit
    // =========================================================================

    public function storeStep3(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $documentTypes = DocumentType::orderBy('sort_order')->pluck('id');
        $rules = [];
        foreach ($documentTypes as $docTypeId) {
            $rules['documents.' . $docTypeId] = ['nullable', 'file', 'max:10240']; // 10MB max
        }

        $validated = $request->validate($rules);

        $uploadDir = "prevention-documents/{$dossier->id}";

        foreach ($documentTypes as $docTypeId) {
            $file = $request->file("documents.{$docTypeId}");
            if ($file && $file->isValid()) {
                $path = $file->store($uploadDir, 'local');

                DossierDocument::updateOrCreate(
                    [
                        'prevention_dossier_id' => $dossier->id,
                        'document_type_id'       => $docTypeId,
                    ],
                    [
                        'file_path'    => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'    => $file->getMimeType(),
                        'size'         => $file->getSize(),
                        'status'       => 'uploaded',
                    ]
                );
            }
        }

        // Mark dossier as completed
        $dossier->update([
            'current_step' => 3,
            'status'       => 'completed',
        ]);

        // Attempt to push to external API if configured
        if ($this->hasPreventionApiBaseUrl()) {
            $payload = $this->withAuthenticatedEmail([
                'cin'            => $dossier->cin,
                'nom_prenom'     => $dossier->nom_prenom,
                'code_structure' => $dossier->governorate_id,
                'delegation'     => $dossier->delegation?->name_ar,
                'num_demande'    => $dossier->num_demande,
            ]);

            try {
                $response = $this->preventionApi()
                    ->post($this->preventionApiUrl('/demande-prevention/attestations'), $payload);

                if ($response->successful()) {
                    $apiRef = $response->json('data.id') ?? $response->json('data.reference');
                    if ($apiRef) {
                        $dossier->update(['api_reference' => (string) $apiRef, 'status' => 'submitted']);
                    }
                }
            } catch (ConnectionException $e) {
                report($e);
            }
        }

        return redirect()
            ->route('prevention-requests.index')
            ->with('success', __('prevention_request.submit_success'));
    }

    // =========================================================================
    // Legacy store (backward compat — redirects to step1 flow)
    // =========================================================================

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('prevention-requests.create');
    }

    // =========================================================================
    // Resubmit — user fixes a needs_correction dossier and re-submits step 3
    // The dossier status goes back to completed so admin can review again.
    // =========================================================================

    public function resubmit(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isNeedsCorrection()) {
            return redirect()->route('prevention-requests.index')
                ->withErrors(['status' => 'This dossier cannot be resubmitted right now.']);
        }

        // Re-open to step 3 so user can upload corrected documents
        $dossier->update([
            'status'       => 'draft',
            'current_step' => 2,   // bring them back to step 3 (step 2 completed, step 3 open)
            'admin_note'   => null,
        ]);

        return redirect()
            ->route('prevention-requests.step3.show', $dossier)
            ->with('info', __('prevention_request.resubmit_info'));
    }

    // =========================================================================
    // Edit / Update / Delete (external API records)
    // =========================================================================

    public function edit(int $id): View|RedirectResponse
    {
        try {
            $response = $this->preventionApi()
                ->get($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), [
                    'email' => $this->authenticatedEmail(),
                ]);
        } catch (ConnectionException $e) {
            report($e);
            return redirect()->route('prevention-requests.index')
                ->withErrors(['api' => __('prevention_request.show_unavailable')]);
        }

        if (! $response->successful()) {
            return redirect()->route('prevention-requests.index')
                ->withErrors(['api' => $response->json('message', __('prevention_request.show_failed'))]);
        }

        return view('prevention-requests.edit', [
            'requestItem'   => $response->json('data', []),
            'gouvernorats'  => $this->gouvernorats(),
            'delegations'   => $this->delegations(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $this->withAuthenticatedEmail($request->validate([
            'cin'            => ['required', 'string', 'max:20'],
            'nom_prenom'     => ['required', 'string', 'max:255'],
            'code_structure' => ['required', 'integer', 'between:1,24'],
            'delegation'     => ['required', 'string', 'max:100'],
            'num_demande'    => ['required', 'numeric'],
        ]));

        try {
            $response = $this->preventionApi()
                ->put($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), $validated);
        } catch (ConnectionException $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['api' => __('prevention_request.update_unavailable')]);
        }

        if ($response->status() === 422) {
            return back()->withInput()
                ->withErrors($this->responseErrors($response, __('prevention_request.update_failed')));
        }

        if (! $response->successful()) {
            return back()->withInput()
                ->withErrors(['api' => $response->json('message', __('prevention_request.update_failed'))]);
        }

        return redirect()->route('prevention-requests.index')
            ->with('success', __('prevention_request.update_success'));
    }

    public function destroy(int $id): RedirectResponse
    {
        // Allow deleting local drafts
        if (is_numeric($id) && $draft = PreventionDossier::where('id', $id)->where('user_id', auth()->id())->first()) {
            $draft->delete();
            return redirect()->route('prevention-requests.index')
                ->with('success', __('prevention_request.delete_success'));
        }

        try {
            $response = $this->preventionApi()
                ->delete($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), [
                    'email' => $this->authenticatedEmail(),
                ]);
        } catch (ConnectionException $e) {
            report($e);
            return back()->withErrors(['api' => __('prevention_request.delete_unavailable')]);
        }

        if (! $response->successful()) {
            return back()->withErrors(['api' => $response->json('message', __('prevention_request.delete_failed'))]);
        }

        return redirect()->route('prevention-requests.index')
            ->with('success', __('prevention_request.delete_success'));
    }

    // =========================================================================
    // Structures JSON (kept for edit form compat)
    // =========================================================================

    public function structures(): JsonResponse
    {
        if (! $this->hasPreventionApiBaseUrl()) {
            return response()->json(['success' => false, 'message' => __('prevention_request.structures_config_missing'), 'data' => []], 500);
        }

        try {
            $response = $this->preventionApi()->get($this->preventionApiUrl('/demande-prevention/structures'));
        } catch (ConnectionException $e) {
            report($e);
            return response()->json(['success' => false, 'message' => __('prevention_request.structures_unavailable'), 'data' => []], 503);
        }

        if (! $response->successful()) {
            return response()->json(['success' => false, 'message' => __('prevention_request.structures_unavailable'), 'data' => []], 502);
        }

        return response()->json(['success' => true, 'data' => $response->json('data', [])]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function preventionApi(): PendingRequest
    {
        $client = Http::acceptJson()->asJson()
            ->timeout((int) config('services.prevention_api.timeout', 10));

        $apiKey = (string) config('services.prevention_api.key');
        if ($apiKey !== '') {
            $client = $client->withHeaders(['X-Demande-Prevention-Api-Key' => $apiKey]);
        }

        return $client;
    }

    private function preventionApiUrl(string $path): string
    {
        return rtrim((string) config('services.prevention_api.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function hasPreventionApiBaseUrl(): bool
    {
        return rtrim((string) config('services.prevention_api.base_url'), '/') !== '';
    }

    private function responseErrors(Response $response, string $fallback): array
    {
        return $response->json('errors', ['api' => $fallback]);
    }

    private function authenticatedEmail(): string
    {
        return (string) auth()->user()?->email;
    }

    private function withAuthenticatedEmail(array $payload): array
    {
        $payload['email'] = $this->authenticatedEmail();
        return $payload;
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
