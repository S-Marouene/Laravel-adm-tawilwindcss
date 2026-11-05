<?php

namespace App\Http\Controllers;

use App\Models\BuildingType;
use App\Models\DocumentType;
use App\Models\DossierDocument;
use App\Models\Governorate;
use App\Models\PreventionDossier;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        // Submitted dossiers in the review/Delivery pipeline
        $submitted = PreventionDossier::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'submitted', 'under_consultation', 'needs_correction', 'approved', 'waiting_delivery', 'cancelled'])
            ->with(['governorate', 'delegation', 'buildingType', 'appointments', 'events', 'certificate.governorate', 'certificate.delegation'])
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
        // Incomplete drafts of the current user — shown as a "continue" banner
        $drafts = PreventionDossier::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        return view('prevention-requests.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
            'hasDocuments' => false,
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft (go back from Step 2)
    // =========================================================================

    public function editStep1(Request $request, PreventionDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        // Drafts banner (same data as create()) — exclude the dossier being edited
        $drafts = PreventionDossier::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->where('id', '!=', $dossier->id)
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        $dossier->loadCount('documents');

        // Old version (correction round): previous values + archived files,
        // shown next to the form so the applicant knows exactly what changed.
        $correction = $dossier->lastCorrectionData();
        $archivedDocs = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full correction history — ALL rounds, so the user can consult any
        // previous version of the dossier even after several correction cycles.
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('prevention-requests.create', [
            'gouvernorats'       => $this->gouvernorats(),
            'delegations'        => $this->delegations(),
            'drafts'             => $drafts,
            'dossier'            => $dossier,   // pre-fills the form & unlocks "editing" mode
            'returnTo'           => $request->query('return'), // step the user came from
            'hasDocuments'       => $dossier->documents_count > 0,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
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
            'cin'              => ['required', 'string', 'max:20'],
            'nom_prenom'       => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'code_structure'   => ['required', 'integer', 'between:1,24'],
            'delegation_id'    => ['required', 'integer', 'exists:delegations,id'],
            'building_address' => ['required', 'string', 'max:500'],
            'latitude'         => ['nullable', 'numeric', 'between:29.9,37.8'],
            'longitude'        => ['nullable', 'numeric', 'between:7.2,11.9'],
        ]);

        // The building address is where the future RDV (site visit) takes place
        $dossier->update([
            'cin'              => $validated['cin'],
            'nom_prenom'       => $validated['nom_prenom'],
            'phone'            => $validated['phone'],
            'governorate_id'   => $validated['code_structure'],
            'delegation_id'    => $validated['delegation_id'],
            'building_address' => $validated['building_address'],
            'latitude'         => $validated['latitude'] ?? null,
            'longitude'        => $validated['longitude'] ?? null,
        ]);

        ActivityLogger::log('prevention_update', "Prevention — Step 1 (identity) updated for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'cin'        => $dossier->cin,
            'nom_prenom' => $dossier->nom_prenom,
            'phone'      => $dossier->phone,
            'governorate'=> $dossier->governorate?->name,
            'delegation' => $dossier->delegation?->name,
        ]);

        // Return the user to the step they came from (default: step 2 in editing mode)
        if ($request->input('return_to') === 'step3') {
            return redirect()->route('prevention-requests.step3.show', $dossier);
        }

        return redirect()->route('prevention-requests.step2.show', ['dossier' => $dossier, 'editing' => 1]);
    }

    // =========================================================================
    // Step 1 — Save draft & redirect to Step 2
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cin'              => ['required', 'string', 'max:20'],
            'nom_prenom'       => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:20'],
            'code_structure'   => ['required', 'integer', 'between:1,24'],
            'delegation_id'    => ['required', 'integer', 'exists:delegations,id'],
            'building_address' => ['required', 'string', 'max:500'],
            'latitude'         => ['nullable', 'numeric', 'between:29.9,37.8'],
            'longitude'        => ['nullable', 'numeric', 'between:7.2,11.9'],
        ]);

        $dossier = PreventionDossier::create([
            'user_id'          => auth()->id(),
            'status'           => 'draft',
            'current_step'     => 1,
            'cin'              => $validated['cin'],
            'nom_prenom'       => $validated['nom_prenom'],
            'phone'            => $validated['phone'],
            'governorate_id'   => $validated['code_structure'],
            'delegation_id'    => $validated['delegation_id'],
            'building_address' => $validated['building_address'],
            'latitude'         => $validated['latitude'] ?? null,
            'longitude'        => $validated['longitude'] ?? null,
        ]);

        ActivityLogger::log('prevention_create', "Prevention — Draft created by {$dossier->nom_prenom} (CIN: {$dossier->cin}, Gov: {$dossier->governorate?->name})", $dossier, [
            'applicant'  => $dossier->nom_prenom,
            'cin'        => $dossier->cin,
            'phone'      => $dossier->phone,
            'governorate'=> $dossier->governorate?->name,
            'delegation' => $dossier->delegation?->name,
            'address'    => $dossier->building_address,
        ]);

        return redirect()->route('prevention-requests.step2.show', $dossier);
    }

    // =========================================================================
    // Step 2 — Show form
    // =========================================================================

    public function showStep2(Request $request, PreventionDossier $dossier): View|RedirectResponse
    {
        // Make sure this dossier belongs to the authenticated user
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load(['governorate', 'delegation', 'buildingType', 'buildingActivity']);
        $dossier->loadCount('documents');

        // « قيد التعديل » : révision explicite (?editing=1 après « Modifier l'étape 1 »)
        // ou dossier déjà traité (statut au-delà du draft / étape 3 atteinte)
        $isEditing = $request->query('editing') === '1'
            || $dossier->status !== 'draft'
            || $dossier->current_step > 2;

        // Old version of the demande (correction round) — shown next to the form
        $correction = $dossier->lastCorrectionData();
        $archivedDocs = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full history of all correction rounds — user can consult any prior version
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('prevention-requests.create-step2', [
            'dossier'            => $dossier,
            'buildingTypes'      => BuildingType::forSelect(),
            'activitiesMap'      => BuildingType::activitiesMap(),
            'isEditing'          => $isEditing,
            'hasDocuments'       => $dossier->documents_count > 0,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
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

        $dossier->load('buildingType', 'buildingActivity');

        ActivityLogger::log('prevention_update', "Prevention — Step 2 (building type) saved for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'building_type'     => $dossier->buildingType?->name,
            'building_activity' => $dossier->buildingActivity?->name ?? $dossier->activity_other,
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

        // ── Verrou de flux : impossible d'atteindre l'étape 3 sans les données de l'étape 2 ──
        if (! $dossier->building_type_id) {
            return redirect()
                ->route('prevention-requests.step2.show', $dossier)
                ->withErrors(['complete_step2' => __('prevention_request.complete_step2_first')]);
        }

        // Checklist du service, filtrée selon le type de bâtiment du dossier :
        // chaque formulaire peut ne concerner que certaines catégories de bâtiment.
        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)
            ->with('buildingTypes')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (DocumentType $t) => $t->appliesToBuildingType($dossier->building_type_id))
            ->values();

        // Load any already-uploaded documents for this dossier, grouped by document type
        // (several files may be attached to the same type — archived versions excluded)
        $uploadedDocs = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->active()
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        // Correction round context: previous values (from the snapshot of the
        // round) + the OLD (archived) uploads of earlier versions, kept and
        // still downloadable in this dossier's history.
        $correction    = $dossier->lastCorrectionData();
        $archivedDocs  = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full history of all correction rounds
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('prevention-requests.create-step3', [
            'dossier'            => $dossier,
            'documentTypes'      => $documentTypes,
            'uploadedDocs'       => $uploadedDocs,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
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

        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)
            ->with('buildingTypes')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (DocumentType $t) => $t->appliesToBuildingType($dossier->building_type_id))
            ->values();

        $rules = [];
        foreach ($documentTypes as $docType) {
            // One or more files per document type (input array + multiple attribute)
            $rules['documents.' . $docType->id]       = ['nullable', 'array', 'max:10'];
            $rules['documents.' . $docType->id . '.*'] = ['file', 'max:10240']; // 10MB max
        }

        $validated = $request->validate($rules);

        $uploadDir = "prevention-documents/{$dossier->id}";

        foreach ($documentTypes as $docType) {
            $files = $request->file("documents.{$docType->id}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                DossierDocument::create([
                    'prevention_dossier_id' => $dossier->id,
                    'document_type_id'      => $docType->id,
                    'file_path'             => $path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'size'                  => $file->getSize(),
                    'status'                => 'uploaded',
                ]);
            }
        }

        // Log the document upload(s) to the global activity log
        $uploadedCount = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'uploaded')
            ->count();
        if ($uploadedCount > 0) {
            ActivityLogger::log('prevention_create', "Prevention — {$uploadedCount} document(s) uploaded for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, ['count' => $uploadedCount]);
        }

        // Mark dossier as completed + assign the citizen-facing reference (PRV26-0001)
        $dossier->update([
            'current_step' => 3,
            'status'       => 'completed',
            'reference'    => $dossier->reference ?: \App\Support\Reference::make('prevention', $dossier),
        ]);

        // ── History: a re-submission after correction rounds logs
        // "resubmitted" with the NEW values of this version (data.updated),
        // so old (from the round snapshot) vs new versions can be compared.
        // A first submission simply logs "submitted".
        $isResubmission = $dossier->lastCorrectionData() !== null;

        if ($isResubmission) {
            $dossier->logEvent('resubmitted', null, [
                'updated' => [
                    'cin'              => $dossier->cin,
                    'nom_prenom'       => $dossier->nom_prenom,
                    'phone'            => $dossier->phone,
                    'governorate'      => $dossier->governorate?->name_fr ?? $dossier->governorate?->name_ar,
                    'delegation'       => $dossier->delegation?->name_fr ?? $dossier->delegation?->name_ar,
                    'building_address' => $dossier->building_address,
                    'latitude'         => $dossier->latitude,
                    'longitude'        => $dossier->longitude,
                    'building_type'    => $dossier->buildingType?->name,
                    'building_activity'=> $dossier->buildingActivity?->name ?? $dossier->activity_other,
                ],
            ]);
        } else {
            $dossier->logEvent('submitted');
        }

        // Log to the global activity log
        ActivityLogger::log('prevention_update', "Prevention — Dossier " . ($isResubmission ? 'resubmitted' : 'submitted') . " (ref: {$dossier->reference}, by: {$dossier->nom_prenom}, CIN: {$dossier->cin})", $dossier, [
            'reference'        => $dossier->reference,
            'applicant'        => $dossier->nom_prenom,
            'cin'              => $dossier->cin,
            'governorate'      => $dossier->governorate?->name,
            'delegation'       => $dossier->delegation?->name,
            'building_type'    => $dossier->buildingType?->name,
            'building_activity'=> $dossier->buildingActivity?->name ?? $dossier->activity_other,
            'resubmission'     => $isResubmission,
        ]);

        // Notify eligible admins of the new/resubmitted dossier
        DossierNotifier::notify($dossier, $isResubmission ? 'resubmitted' : 'submitted', 'admin');

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
                }            } catch (ConnectionException $e) {
                report($e);
            }
        }

        return redirect()
            ->route('mes-demandes')
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
    // Show — full dossier details for the applicant
    // =========================================================================

    public function show(PreventionDossier $dossier): View
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load([
            'governorate', 'delegation', 'buildingType', 'buildingActivity',
            'appointments', 'events.actor', 'certificate.governorate', 'certificate.delegation',
        ]);

        // Checklist du service PRÉVENTION, filtrée strictement selon le type de
        // bâtiment du dossier (comme à l'étape 3 : seuls les formulaires concernés).
        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)
            ->with('buildingTypes')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (DocumentType $t) => $t->appliesToBuildingType($dossier->building_type_id))
            ->values();

        // Documents actifs, groupés par type (plusieurs fichiers possibles).
        // Les versions archivées (corrections précédentes) sont séparées —
        // elles restent consultables dans l'historique du dossier.
        $documents = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->active()
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        $archivedDocuments = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        // All correction rounds — full history shown on the dossier detail page
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        // Flat list of archived docs for the correction-history partial
        $allArchivedDocuments = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        return view('prevention-requests.show', compact(
            'dossier', 'documentTypes', 'documents', 'archivedDocuments',
            'allCorrectionRounds', 'allArchivedDocuments',
        ));
    }

    // =========================================================================
    // Choose delivery — how the applicant wants to receive the certificate.
    // Available once the inspection (RDV) passed (status waiting_delivery):
    //   - digital : NOT ACTIVE YET (requires the on-line payment module)
    //   - pickup  : at the delegation / gouvernorat office  ← only active option
    //   - post    : sent via Poste Tunisienne (B3) (requires the payment module)
    //
    // TODO(paiement): when the credit-card gateway goes live, this flow becomes:
    //   1. user selects digital|post → the (currently mocked) card form shows;
    //   2. the PSP (Paymee / D17 / e-dinar, 3-D Secure redirect + hosted fields)
    //      charges the certificate delivery fee;
    //   3. a SIGNED PSP webhook confirms payment server-side: a payment record
    //      (amount, PSP reference, status) is attached to the dossier and a
    //      'delivery_paid' event is logged;
    //   4. only then is the choice persisted here and, for digital, the PDF
    //      certificate is generated (download button unlocks) / for post the
    //      postal address is required and the B3 request is recorded.
    //   Until then the post/digital radios stay disabled in the UI and only
    //   "pickup" reaches this method — the whitelist below is voluntarily kept
    //   permissive for the switchover.
    // =========================================================================

    public function chooseDelivery(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isWaitingDelivery()) {
            return redirect()->route('prevention-requests.show', $dossier)
                ->withErrors(['delivery_method' => __('prevention_request.delivery_not_available_yet')]);
        }

        $validated = $request->validate([
            'delivery_method' => ['required', 'in:pickup,post'],
            'post_address'    => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
        ]);

        $deliveryMethod = $validated['delivery_method'];

        $dossier->update([
            'delivery_method'   => $deliveryMethod,
            'post_address'      => $deliveryMethod === 'post' ? $validated['post_address'] : null,
            'delivery_choice_at' => now(),
        ]);

        $dossier->logEvent('delivery_chosen', null, [
            'method'  => $deliveryMethod,
            'address' => $deliveryMethod === 'post' ? $validated['post_address'] : null,
        ]);

        ActivityLogger::log('prevention_update', "Prevention — Delivery method chosen: {$deliveryMethod} for dossier ref {$dossier->reference} by {$dossier->nom_prenom}", $dossier, [
            'method'   => $deliveryMethod,
            'applicant'=> $dossier->nom_prenom,
            'reference'=> $dossier->reference,
        ]);

        // Notify eligible admins that the citizen chose a delivery method
        DossierNotifier::notify($dossier, 'delivery_chosen', 'admin');

        return redirect()->route('prevention-requests.show', $dossier)
            ->with('success', __('prevention_request.delivery_saved'));
    }

    // =========================================================================
    // Download an uploaded document — only from your own dossier
    // =========================================================================

    public function downloadDocument(PreventionDossier $dossier, DossierDocument $document)
    {
        if ($dossier->user_id !== auth()->id() || $document->prevention_dossier_id !== $dossier->id) {
            abort(403);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    // =========================================================================
    // Download the official form/template attached to a document type
    // (the applicant fills it, scans it and uploads it back)
    // =========================================================================

    public function downloadTemplate(DocumentType $documentType)
    {
        if (! $documentType->template_path) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $documentType->template_path,
            $documentType->template_name ?: ($documentType->name . '.' . $documentType->templateExtension())
        );
    }

    // =========================================================================
    // Delete an uploaded document (remove a file from the draft dossier)
    // =========================================================================

    public function deleteDocument(Request $request, PreventionDossier $dossier, DossierDocument $document): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id() || $document->prevention_dossier_id !== $dossier->id) {
            abort(403);
        }

        // Do not allow removing documents once the dossier has left the applicant's hands
        if (! in_array($dossier->status, ['draft', 'completed', 'needs_correction'])) {
            return redirect()
                ->route('prevention-requests.step3.show', $dossier)
                ->withErrors(['status' => __('prevention_request.delete_forbidden')]);
        }

        $docName = $document->original_name;
        $docType = $document->documentType?->name;

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        ActivityLogger::log('prevention_delete', "Prevention — Document '{$docName}' deleted from dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'document' => $docName,
            'type'      => $docType,
        ]);

        return redirect()
            ->route('prevention-requests.step3.show', $dossier)
            ->with('success', __('prevention_request.document_deleted'));
    }

    // =========================================================================
    // Resubmit — the user starts fixing a needs_correction dossier.
    // He re-walks EVERY step (1 → 2 → 3): old values are editable, and any
    // change becomes the new version while the old one stays conserved in
    // the history (snapshot + archived files anchored on "correction_requested").
    // =========================================================================

    // =========================================================================
    // Targeted correction form — the new flow replacing the 3-step resubmit.
    // The admin selects exactly which fields/documents need fixing; the user
    // sees a single page with only those items editable.
    // =========================================================================

    /**
     * Legacy POST /resubmit redirect — keeps old email links working.
     * Simply bounces to the new targeted correction page.
     */
    public function resubmitRedirect(PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        return redirect()->route('prevention-requests.correct', $dossier);
    }

    /**
     * GET — show the targeted correction form.
     * Only the fields/documents flagged by the admin are editable.
     */
    public function showCorrectForm(PreventionDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isNeedsCorrection()) {
            return redirect()->route('prevention-requests.show', $dossier)
                ->withErrors(['status' => __('prevention_request.not_needs_correction')]);
        }

        $dossier->load([
            'governorate', 'delegation', 'buildingType', 'buildingActivity', 'events',
        ]);

        $correction = $dossier->lastCorrectionData();

        // If no correction data (e.g. legacy dossier), fall back to all fields editable
        $fieldsToFix = $correction['fields_to_fix'] ?? [];
        $docsToFix   = $correction['docs_to_fix'] ?? [];

        // Load only the document types the admin flagged
        $documentTypes = collect();
        if (! empty($docsToFix)) {
            $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)
                ->with('buildingTypes')
                ->orderBy('sort_order')
                ->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))
                ->values();
        }

        // Archived docs for those types (the ones replaced by this correction)
        $archivedDocs = DossierDocument::where('prevention_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('prevention-requests.correct', [
            'dossier'      => $dossier,
            'correction'   => $correction,
            'fieldsToFix'  => $fieldsToFix,
            'docsToFix'    => $docsToFix,
            'documentTypes'=> $documentTypes,
            'archivedDocs' => $archivedDocs,
            'gouvernorats' => Governorate::with('delegations')->orderBy('id')->get(),
            'delegations'  => $this->delegations(),
            'buildingTypes'=> BuildingType::forSelect(),
            'activitiesMap'=> BuildingType::activitiesMap(),
        ]);
    }

    /**
     * POST — save the targeted corrections and resubmit the dossier.
     */
    public function submitCorrection(Request $request, PreventionDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isNeedsCorrection()) {
            return redirect()->route('prevention-requests.show', $dossier)
                ->withErrors(['status' => __('prevention_request.not_needs_correction')]);
        }

        $dossier->load(['governorate', 'delegation', 'buildingType', 'buildingActivity', 'events']);
        $correction  = $dossier->lastCorrectionData();
        $fieldsToFix = $correction['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $correction['docs_to_fix'] ?? []);

        // ── Build validation rules for only the flagged items ──
        $rules = [];

        if (in_array('cin', $fieldsToFix)) {
            $rules['cin'] = ['required', 'string', 'max:20'];
        }
        if (in_array('nom_prenom', $fieldsToFix)) {
            $rules['nom_prenom'] = ['required', 'string', 'max:255'];
        }
        if (in_array('phone', $fieldsToFix)) {
            $rules['phone'] = ['required', 'string', 'max:20'];
        }
        if (in_array('governorate_id', $fieldsToFix)) {
            $rules['code_structure'] = ['required', 'integer', 'between:1,24'];
        }
        if (in_array('delegation_id', $fieldsToFix)) {
            $rules['delegation_id'] = ['required', 'integer', 'exists:delegations,id'];
        }
        if (in_array('building_address', $fieldsToFix)) {
            $rules['building_address'] = ['required', 'string', 'max:500'];
            $rules['latitude']         = ['nullable', 'numeric', 'between:29.9,37.8'];
            $rules['longitude']        = ['nullable', 'numeric', 'between:7.2,11.9'];
        }
        if (in_array('building_type_id', $fieldsToFix)) {
            $rules['building_type_id'] = ['required', 'integer', 'exists:building_types,id'];
        }
        if (in_array('building_activity_id', $fieldsToFix)) {
            $rules['building_activity_id'] = ['nullable', 'integer', 'exists:building_activities,id'];
            $rules['activity_other']        = ['nullable', 'string', 'max:255'];
        }

        foreach ($docsToFix as $docTypeId) {
            $rules["documents.{$docTypeId}"]       = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"]     = ['file', 'max:10240'];
        }

        $validated = $request->validate($rules);

        // ── Apply field changes (only those flagged) ──
        $updates = [];
        if (in_array('cin', $fieldsToFix))               $updates['cin']              = $validated['cin'];
        if (in_array('nom_prenom', $fieldsToFix))         $updates['nom_prenom']       = $validated['nom_prenom'];
        if (in_array('phone', $fieldsToFix))              $updates['phone']            = $validated['phone'];
        if (in_array('governorate_id', $fieldsToFix))     $updates['governorate_id']   = $validated['code_structure'];
        if (in_array('delegation_id', $fieldsToFix))      $updates['delegation_id']    = $validated['delegation_id'];
        if (in_array('building_address', $fieldsToFix)) {
            $updates['building_address'] = $validated['building_address'];
            $updates['latitude']          = $validated['latitude'] ?? null;
            $updates['longitude']          = $validated['longitude'] ?? null;
        }
        if (in_array('building_type_id', $fieldsToFix))   $updates['building_type_id'] = $validated['building_type_id'];
        if (in_array('building_activity_id', $fieldsToFix)) {
            $updates['building_activity_id'] = $validated['building_activity_id'] ?? null;
            $updates['activity_other']       = $validated['activity_other'] ?? null;
        }

        if (! empty($updates)) {
            $dossier->update($updates);
        }

        // ── Upload new files for flagged document types ──
        $uploadDir = "prevention-documents/{$dossier->id}";

        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                DossierDocument::create([
                    'prevention_dossier_id' => $dossier->id,
                    'document_type_id'      => $docTypeId,
                    'file_path'             => $path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'size'                  => $file->getSize(),
                    'status'                => 'uploaded',
                ]);
            }
        }

        // ── Mark as re-submitted ──
        $dossier->refresh()->load(['governorate', 'delegation', 'buildingType', 'buildingActivity']);

        $dossier->update([
            'status'       => 'completed',
            'current_step' => 3,
            'reference'    => $dossier->reference ?: \App\Support\Reference::make('prevention', $dossier),
        ]);

        $dossier->logEvent('resubmitted', null, [
            'updated' => [
                'cin'              => $dossier->cin,
                'nom_prenom'       => $dossier->nom_prenom,
                'phone'            => $dossier->phone,
                'governorate'      => $dossier->governorate?->name_fr ?? $dossier->governorate?->name_ar,
                'delegation'       => $dossier->delegation?->name_fr ?? $dossier->delegation?->name_ar,
                'building_address' => $dossier->building_address,
                'latitude'         => $dossier->latitude,
                'longitude'        => $dossier->longitude,
                'building_type'    => $dossier->buildingType?->name,
                'building_activity'=> $dossier->buildingActivity?->name ?? $dossier->activity_other,
            ],
            'fields_fixed' => $fieldsToFix,
            'docs_fixed'   => $docsToFix,
        ]);

        ActivityLogger::log('prevention_update', "Prevention — Correction submitted for dossier ref {$dossier->reference} by {$dossier->nom_prenom} (fields: " . implode(', ', $fieldsToFix) . ")", $dossier, [
            'applicant'    => $dossier->nom_prenom,
            'reference'     => $dossier->reference,
            'fields_fixed'  => $fieldsToFix,
            'docs_fixed'    => $docsToFix,
        ]);

        // Notify eligible admins that the citizen resubmitted after correction
        DossierNotifier::notify($dossier, 'resubmitted', 'admin');

        return redirect()
            ->route('prevention-requests.show', $dossier)
            ->with('success', __('prevention_request.correction_submitted_success'));
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
            'num_demande'    => ['required', 'string', 'max:50'], // PRV format (e.g. PRV26-0001), not a plain number
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

    public function destroy(Request $request, int $id): RedirectResponse
    {
        // Allow deleting local drafts
        if (is_numeric($id) && $draft = PreventionDossier::where('id', $id)->where('user_id', auth()->id())->first()) {
            $draft->delete();

            // Delete triggered from the wizard's draft banner → stay in the wizard
            if ($request->input('back') === 'wizard') {
                return redirect()->route('prevention-requests.create')
                    ->with('success', __('prevention_request.delete_success'));
            }

            // Optional local override (e.g. delete from /mes-demandes)
            $redirect = $request->input('redirect');
            if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
                return redirect($redirect)
                    ->with('success', __('prevention_request.delete_success'));
            }

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
