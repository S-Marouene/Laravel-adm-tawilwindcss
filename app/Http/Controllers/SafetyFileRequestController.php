<?php

namespace App\Http\Controllers;

use App\Models\BuildingType;
use App\Models\DocumentType;
use App\Models\SafetyFileDocument;
use App\Models\Governorate;
use App\Models\SafetyFileDossier;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SafetyFileRequestController extends Controller
{
    // =========================================================================
    // Index — list all local dossiers for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        $service = SafetyFileDossier::serviceKeyForVariant($this->currentVariant());

        // Draft dossiers (incomplete) — filtered to the current service
        $drafts = SafetyFileDossier::where('user_id', auth()->id())
            ->where('service', $service)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        // Submitted dossiers in the review/Delivery pipeline — current service only
        $submitted = SafetyFileDossier::where('user_id', auth()->id())
            ->where('service', $service)
            ->whereIn('status', ['completed', 'submitted', 'under_consultation', 'needs_correction', 'approved', 'waiting_delivery', 'cancelled'])
            ->with(['governorate', 'delegation', 'buildingType', 'appointments', 'events', 'certificate.governorate', 'certificate.delegation'])
            ->latest()
            ->get();

        return view('safety-file.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
            'rp'        => $this->rp(),
        ]);
    }

    // =========================================================================
    // Step 1 — Show form
    // =========================================================================

    public function create(Request $request): View
    {
        $variant = $this->currentVariant();
        $service = SafetyFileDossier::serviceKeyForVariant($variant);

        // Incomplete drafts of the current user (this service only) — "continue" banner
        $drafts = SafetyFileDossier::where('user_id', auth()->id())
            ->where('service', $service)
            ->where('status', 'draft')
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        return view('safety-file.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
            'hasDocuments' => false,
            'variant'      => $variant,
            'service'      => $service,
            'rp'           => $this->rp(),
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft (go back from Step 2)
    // =========================================================================

    public function editStep1(Request $request, SafetyFileDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        // Drafts banner (same data as create()) — same service, exclude the dossier being edited
        $drafts = SafetyFileDossier::where('user_id', auth()->id())
            ->where('service', $dossier->service)
            ->where('status', 'draft')
            ->where('id', '!=', $dossier->id)
            ->with(['governorate', 'delegation', 'buildingType'])
            ->latest()
            ->get();

        $dossier->loadCount('documents');

        // Old version (correction round): previous values + archived files,
        // shown next to the form so the applicant knows exactly what changed.
        $correction = $dossier->lastCorrectionData();
        $archivedDocs = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full correction history — ALL rounds, so the user can consult any
        // previous version of the dossier even after several correction cycles.
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('safety-file.create', [
            'gouvernorats'       => $this->gouvernorats(),
            'delegations'        => $this->delegations(),
            'drafts'             => $drafts,
            'dossier'            => $dossier,   // pre-fills the form & unlocks "editing" mode
            'variant'            => $dossier->variant(),
            'returnTo'           => $request->query('return'), // step the user came from
            'hasDocuments'       => $dossier->documents_count > 0,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
            'rp'                 => $this->rp($dossier),
        ]);
    }

    // =========================================================================
    // Step 1 — Update existing draft & redirect back to Step 2
    // =========================================================================

    public function updateStep1(Request $request, SafetyFileDossier $dossier): RedirectResponse
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

        ActivityLogger::log('safety_update', "Safety file — Step 1 (identity) updated for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'cin'        => $dossier->cin,
            'nom_prenom' => $dossier->nom_prenom,
            'phone'      => $dossier->phone,
            'governorate'=> $dossier->governorate?->name,
            'delegation' => $dossier->delegation?->name,
        ]);

        // Return the user to the step they came from (default: step 2 in editing mode)
        if ($request->input('return_to') === 'step3') {
            return redirect()->route($this->rp($dossier) . '.step3.show', $dossier);
        }

        return redirect()->route($this->rp($dossier) . '.step2.show', ['dossier' => $dossier, 'editing' => 1]);
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

        $variant = $this->currentVariant();

        $dossier = SafetyFileDossier::create([
            'user_id'          => auth()->id(),
            'service'          => SafetyFileDossier::serviceKeyForVariant($variant),
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

        ActivityLogger::log('safety_create', "Safety file — Draft created by {$dossier->nom_prenom} (CIN: {$dossier->cin}, Gov: {$dossier->governorate?->name})", $dossier, [
            'applicant'  => $dossier->nom_prenom,
            'cin'        => $dossier->cin,
            'phone'      => $dossier->phone,
            'governorate'=> $dossier->governorate?->name,
            'delegation' => $dossier->delegation?->name,
            'address'    => $dossier->building_address,
        ]);

        return redirect()->route($this->rp($dossier) . '.step2.show', $dossier);
    }

    // =========================================================================
    // Step 2 — Show form
    // =========================================================================

    public function showStep2(Request $request, SafetyFileDossier $dossier): View|RedirectResponse
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
        $archivedDocs = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full history of all correction rounds — user can consult any prior version
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('safety-file.create-step2', [
            'dossier'            => $dossier,
            'buildingTypes'      => BuildingType::forSelect(),
            'activitiesMap'      => BuildingType::activitiesMap(),
            'isEditing'          => $isEditing,
            'hasDocuments'       => $dossier->documents_count > 0,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
            'rp'                 => $this->rp($dossier),
        ]);
    }

    // =========================================================================
    // Step 2 — Save building type & continue
    // =========================================================================

    public function storeStep2(Request $request, SafetyFileDossier $dossier): RedirectResponse
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

        ActivityLogger::log('safety_update', "Safety file — Step 2 (building type) saved for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'building_type'     => $dossier->buildingType?->name,
            'building_activity' => $dossier->buildingActivity?->name ?? $dossier->activity_other,
        ]);

        return redirect()->route($this->rp($dossier) . '.step3.show', $dossier);
    }

    // =========================================================================
    // Step 3 — Show document upload form
    // =========================================================================

    public function showStep3(SafetyFileDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load(['governorate', 'delegation', 'buildingType', 'buildingActivity']);

        // Safety file services have their own fixed checklists per variant —
        // the building type does NOT filter which documents are required.
        $documentTypes = DocumentType::service($dossier->service)
            ->orderBy('sort_order')
            ->get()
            ->values();

        // Load any already-uploaded documents for this dossier, grouped by document type
        // (several files may be attached to the same type — archived versions excluded)
        $uploadedDocs = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->active()
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        // Correction round context: previous values (from the snapshot of the
        // round) + the OLD (archived) uploads of earlier versions, kept and
        // still downloadable in this dossier's history.
        $correction    = $dossier->lastCorrectionData();
        $archivedDocs  = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        // Full history of all correction rounds
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        return view('safety-file.create-step3', [
            'dossier'            => $dossier,
            'documentTypes'      => $documentTypes,
            'uploadedDocs'       => $uploadedDocs,
            'correction'         => $correction,
            'archivedDocs'       => $archivedDocs,
            'allCorrectionRounds'=> $allCorrectionRounds,
            'rp'                 => $this->rp($dossier),
        ]);
    }

    // =========================================================================
    // Step 3 — Save uploaded documents & submit
    // =========================================================================

    public function storeStep3(Request $request, SafetyFileDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        // Safety file services have their own fixed checklists per variant —
        // the building type does NOT filter which documents are required.
        $documentTypes = DocumentType::service($dossier->service)
            ->orderBy('sort_order')
            ->get()
            ->values();

        $rules = [];
        foreach ($documentTypes as $docType) {
            // One or more files per document type (input array + multiple attribute)
            $rules['documents.' . $docType->id]       = ['nullable', 'array', 'max:10'];
            $rules['documents.' . $docType->id . '.*'] = ['file', 'max:10240']; // 10MB max
        }

        $validated = $request->validate($rules);

        $uploadDir = "safety-file-documents/{$dossier->id}";

        foreach ($documentTypes as $docType) {
            $files = $request->file("documents.{$docType->id}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                SafetyFileDocument::create([
                    'safety_file_dossier_id' => $dossier->id,
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
        $uploadedCount = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'uploaded')
            ->count();
        if ($uploadedCount > 0) {
            ActivityLogger::log('safety_create', "Safety file — {$uploadedCount} document(s) uploaded for dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, ['count' => $uploadedCount]);
        }

        // Mark dossier as completed + assign the citizen-facing reference (PRV26-0001)
        $dossier->update([
            'current_step' => 3,
            'status'       => 'completed',
            'reference'    => $dossier->reference ?: \App\Support\Reference::make($dossier->service, $dossier),
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
        ActivityLogger::log('safety_update', "Safety file — Dossier " . ($isResubmission ? 'resubmitted' : 'submitted') . " (ref: {$dossier->reference}, by: {$dossier->nom_prenom}, CIN: {$dossier->cin})", $dossier, [
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
        DossierNotifier::notify($dossier, $isResubmission ? 'resubmitted' : 'submitted', 'admin', $dossier->service);

        return redirect()
            ->route('mes-demandes')
            ->with('success', __('safety_file.submit_success'));
    }

    // =========================================================================
    // Show — full dossier details for the applicant
    // =========================================================================

    public function show(SafetyFileDossier $dossier): View
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        $dossier->load([
            'governorate', 'delegation', 'buildingType', 'buildingActivity',
            'appointments', 'events.actor', 'certificate.governorate', 'certificate.delegation',
        ]);

        // Safety file services have their own fixed checklists per variant —
        // the building type does NOT filter which documents are required.
        $documentTypes = DocumentType::service($dossier->service)
            ->orderBy('sort_order')
            ->get()
            ->values();

        // Documents actifs, groupés par type (plusieurs fichiers possibles).
        // Les versions archivées (corrections précédentes) sont séparées —
        // elles restent consultables dans l'historique du dossier.
        $documents = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->active()
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        $archivedDocuments = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get()
            ->groupBy('document_type_id');

        // All correction rounds — full history shown on the dossier detail page
        $allCorrectionRounds = $dossier->allCorrectionRounds();

        // Flat list of archived docs for the correction-history partial
        $allArchivedDocuments = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at')
            ->get();

        return view('safety-file.show', compact(
            'dossier', 'documentTypes', 'documents', 'archivedDocuments',
            'allCorrectionRounds', 'allArchivedDocuments',
        ) + ['rp' => $this->rp($dossier)]);
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

    public function chooseDelivery(Request $request, SafetyFileDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isWaitingDelivery()) {
            return redirect()->route($this->rp($dossier) . '.show', $dossier)
                ->withErrors(['delivery_method' => __('safety_file.delivery_not_available_yet')]);
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

        ActivityLogger::log('safety_update', "Safety file — Delivery method chosen: {$deliveryMethod} for dossier ref {$dossier->reference} by {$dossier->nom_prenom}", $dossier, [
            'method'   => $deliveryMethod,
            'applicant'=> $dossier->nom_prenom,
            'reference'=> $dossier->reference,
        ]);

        // Notify eligible admins that the citizen chose a delivery method
        DossierNotifier::notify($dossier, 'delivery_chosen', 'admin', $dossier->service);

        return redirect()->route($this->rp($dossier) . '.show', $dossier)
            ->with('success', __('safety_file.delivery_saved'));
    }

    // =========================================================================
    // Download an uploaded document — only from your own dossier
    // =========================================================================

    public function downloadDocument(SafetyFileDossier $dossier, SafetyFileDocument $document)
    {
        if ($dossier->user_id !== auth()->id() || $document->safety_file_dossier_id !== $dossier->id) {
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

    public function deleteDocument(Request $request, SafetyFileDossier $dossier, SafetyFileDocument $document): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id() || $document->safety_file_dossier_id !== $dossier->id) {
            abort(403);
        }

        // Do not allow removing documents once the dossier has left the applicant's hands
        if (! in_array($dossier->status, ['draft', 'completed', 'needs_correction'])) {
            return redirect()
                ->route($this->rp($dossier) . '.step3.show', $dossier)
                ->withErrors(['status' => __('safety_file.delete_forbidden')]);
        }

        $docName = $document->original_name;
        $docType = $document->documentType?->name;

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        ActivityLogger::log('safety_delete', "Safety file — Document '{$docName}' deleted from dossier #{$dossier->id} by {$dossier->nom_prenom}", $dossier, [
            'document' => $docName,
            'type'      => $docType,
        ]);

        return redirect()
            ->route($this->rp($dossier) . '.step3.show', $dossier)
            ->with('success', __('safety_file.document_deleted'));
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
    public function resubmitRedirect(SafetyFileDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        return redirect()->route($this->rp($dossier) . '.correct', $dossier);
    }

    /**
     * GET — show the targeted correction form.
     * Only the fields/documents flagged by the admin are editable.
     */
    public function showCorrectForm(SafetyFileDossier $dossier): View|RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isNeedsCorrection()) {
            return redirect()->route($this->rp($dossier) . '.show', $dossier)
                ->withErrors(['status' => __('safety_file.not_needs_correction')]);
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
            $documentTypes = DocumentType::service($dossier->service)
                ->orderBy('sort_order')
                ->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))
                ->values();
        }

        // Archived docs for those types (the ones replaced by this correction)
        $archivedDocs = SafetyFileDocument::where('safety_file_dossier_id', $dossier->id)
            ->where('status', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('safety-file.correct', [
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
            'rp'           => $this->rp($dossier),
        ]);
    }

    /**
     * POST — save the targeted corrections and resubmit the dossier.
     */
    public function submitCorrection(Request $request, SafetyFileDossier $dossier): RedirectResponse
    {
        if ($dossier->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $dossier->isNeedsCorrection()) {
            return redirect()->route($this->rp($dossier) . '.show', $dossier)
                ->withErrors(['status' => __('safety_file.not_needs_correction')]);
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
        } else {
            // Address alone carries its own optional map pin; when the map
            // position is targeted explicitly the admin flagged it on its
            // own — a pin is then expected (matches the required indicator).
            if (in_array('map_position', $fieldsToFix)) {
                $rules['latitude']  = ['required', 'numeric', 'between:29.9,37.8'];
                $rules['longitude'] = ['required', 'numeric', 'between:7.2,11.9'];
            }
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
        } elseif (in_array('map_position', $fieldsToFix)) {
            // Position only: repin the building on the map (required in this branch).
            $updates['latitude']  = $validated['latitude'];
            $updates['longitude'] = $validated['longitude'];
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
        $uploadDir = "safety-file-documents/{$dossier->id}";

        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                SafetyFileDocument::create([
                    'safety_file_dossier_id' => $dossier->id,
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
            'reference'    => $dossier->reference ?: \App\Support\Reference::make($dossier->service, $dossier),
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

        ActivityLogger::log('safety_update', "Safety file — Correction submitted for dossier ref {$dossier->reference} by {$dossier->nom_prenom} (fields: " . implode(', ', $fieldsToFix) . ")", $dossier, [
            'applicant'    => $dossier->nom_prenom,
            'reference'     => $dossier->reference,
            'fields_fixed'  => $fieldsToFix,
            'docs_fixed'    => $docsToFix,
        ]);

        // Notify eligible admins that the citizen resubmitted after correction
        DossierNotifier::notify($dossier, 'resubmitted', 'admin', $dossier->service);

        return redirect()
            ->route($this->rp($dossier) . '.show', $dossier)
            ->with('success', __('safety_file.correction_submitted_success'));
    }

    // =========================================================================
    // Delete a draft (from the wizard's draft banner or /mes-demandes)
    // =========================================================================

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $draft = SafetyFileDossier::where('id', $id)->where('user_id', auth()->id())->first();
        if (! $draft) {
            abort(404);
        }

        $draft->delete();

        // Delete triggered from the wizard's draft banner → stay in the wizard
        if ($request->input('back') === 'wizard') {
            return redirect()->route($this->rp($draft) . '.create')
                ->with('success', __('safety_file.delete_success'));
        }

        // Optional local override (e.g. delete from /mes-demandes)
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)
                ->with('success', __('safety_file.delete_success'));
        }

        return redirect()->route($this->rp() . '.index')
            ->with('success', __('safety_file.delete_success'));
    }

    // =========================================================================
    // Variant helpers — each route group maps to one variant, so the
    // controller always knows which service it's serving.
    // =========================================================================

    /** Variant inferred from the current route name (safety-file-public.* | safety-file-hazardous.*) */
    private function currentVariant(): string
    {
        $route = request()->route()?->getName() ?? '';
        return str_contains($route, 'safety-file-hazardous')
            ? SafetyFileDossier::VARIANT_HAZARDOUS
            : SafetyFileDossier::VARIANT_HABITATION;
    }

    /**
     * Route-name prefix for redirects and views.
     * If a dossier is given, use its variant; otherwise use the current route's variant.
     */
    private function rp(?SafetyFileDossier $dossier = null): string
    {
        if ($dossier) {
            return $dossier->isHazardous() ? 'safety-file-hazardous' : 'safety-file-public';
        }
        return $this->currentVariant() === SafetyFileDossier::VARIANT_HAZARDOUS
            ? 'safety-file-hazardous'
            : 'safety-file-public';
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
}
