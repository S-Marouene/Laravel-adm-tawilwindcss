<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\RescueCertificate;
use App\Models\RescueDocument;
use App\Models\Service;
use App\Services\DossierNotifier;
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
    // Show — full lifecycle page (hero, stepper, details, documents, timeline)
    // =========================================================================

    public function show(RescueCertificate $rescueCertificate): View
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        $rescueCertificate->load(['user', 'governorate', 'delegation', 'documents.documentType', 'events.actor', 'pickupGovernorate', 'pickupDelegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
            ->orderBy('sort_order')
            ->get();

        return view('rescue-certificates.show', [
            'certificate'         => $rescueCertificate,
            'documentTypes'       => $documentTypes,
            'archivedDocuments'   => $rescueCertificate->documents->filter(fn ($d) => $d->status === 'archived')->sortBy('created_at'),
            'allCorrectionRounds' => $rescueCertificate->allCorrectionRounds(),
        ]);
    }

    // =========================================================================
    // Step 1 — Show form (victim identity + rescue operation details)
    // =========================================================================

    public function create(): View
    {
        // Load user's rescue drafts so the service-details-tabs component can show them
        $drafts = RescueCertificate::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('rescue-certificates.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
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
            ->where('status', '!=', 'archived')
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
            // Accept multiple files per doc type (same as prevention step3)
            $rules['documents.' . $docTypeId]   = ['nullable', 'array'];
            $rules['documents.' . $docTypeId . '.*'] = ['nullable', 'file', 'max:10240'];
        }

        $request->validate($rules);

        $uploadDir = "rescue-documents/{$rescueCertificate->id}";

        foreach ($documentTypeIds as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            // Accept both single file (old input) and array (new multi-upload)
            if (! is_array($files)) $files = [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');

                // Always create a new row — multiple active files per type are
                // allowed (same model as prevention's dossier documents).
                RescueDocument::create([
                    'rescue_certificate_id' => $rescueCertificate->id,
                    'document_type_id'      => $docTypeId,
                    'file_path'             => $path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'size'                  => $file->getSize(),
                    'status'                => 'uploaded',
                ]);
            }
        }

        // Mark request as completed (submitted for review) + assign reference
        $isResubmission = $rescueCertificate->events()->where('type', 'correction_requested')->exists();

        $rescueCertificate->update([
            'current_step' => 2,
            'status'       => 'completed',
            'reference'    => $rescueCertificate->reference ?: \App\Support\Reference::make('rescue', $rescueCertificate),
        ]);

        $rescueCertificate->logEvent($isResubmission ? 'resubmitted' : 'submitted');

        DossierNotifier::notify($rescueCertificate, $isResubmission ? 'resubmitted' : 'submitted', 'admin', Service::SERVICE_RESCUE_CERTIFICATE);

        // Always redirect to mes-demandes so the user sees their full dashboard
        return redirect('/mes-demandes')
            ->with('success', __('rescue_certificate.submit_success'));
    }

    // =========================================================================
    // Choose delivery method (citizen) — pickup | post
    // Available once the request is approved (admin has issued the certificate)
    // =========================================================================

    public function chooseDelivery(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        // The delivery choice is available once approved (before cert is issued)
        if ($rescueCertificate->status !== 'approved') {
            return redirect()->route('rescue-certificates.show', $rescueCertificate)
                ->withErrors(['delivery_method' => __('rescue_certificate.delivery_not_available_yet')]);
        }

        $validated = $request->validate([
            'delivery_method' => ['required', 'in:pickup,post'],
            'post_address'    => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
        ]);

        $rescueCertificate->update([
            'delivery_method'    => $validated['delivery_method'],
            'post_address_citizen' => $validated['delivery_method'] === 'post' ? $validated['post_address'] : null,
            'delivery_choice_at' => now(),
        ]);

        $rescueCertificate->logEvent('delivery_chosen', null, [
            'method'  => $validated['delivery_method'],
            'address' => $validated['delivery_method'] === 'post' ? $validated['post_address'] : null,
        ]);

        \App\Services\DossierNotifier::notify($rescueCertificate, 'delivery_chosen', 'admin', \App\Models\Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.delivery_saved'));
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

        return \Storage::disk('local')->download(
            $documentType->template_path,
            $documentType->template_name ?: ($documentType->name . '.' . $documentType->templateExtension())
        );
    }

    // =========================================================================
    // Targeted correction — show the form (fields + documents flagged by admin)
    // =========================================================================

    public function showCorrectForm(RescueCertificate $rescueCertificate): View|RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $rescueCertificate->isNeedsCorrection()) {
            return redirect()->route('rescue-certificates.show', $rescueCertificate)
                ->withErrors(['status' => __('rescue_certificate.not_needs_correction')]);
        }

        $rescueCertificate->load(['governorate', 'delegation', 'documents.documentType', 'events']);

        $correction = $rescueCertificate->lastCorrectionData();

        // Legacy fallback: no correction snapshot stored → everything stays editable
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'victim_is_minor', 'spouse_case', 'governorate_id', 'delegation_id', 'incident_address', 'incident_type', 'incident_date', 'incident_time', 'incident_description', 'notes'];
        $fieldsToFix = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix   = $correction['docs_to_fix'] ?? [];

        // Load only the document types the admin flagged
        $documentTypes = collect();
        if (! empty($docsToFix)) {
            $documentTypes = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
                ->orderBy('sort_order')->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))->values();
        }

        // Archived docs (the ones replaced during previous correction rounds)
        $archivedDocs = RescueDocument::where('rescue_certificate_id', $rescueCertificate->id)
            ->where('status', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('rescue-certificates.correct', [
            'rescueCertificate' => $rescueCertificate,
            'correction'        => $correction,
            'fieldsToFix'       => $fieldsToFix,
            'docsToFix'         => $docsToFix,
            'documentTypes'     => $documentTypes,
            'archivedDocs'      => $archivedDocs,
            'gouvernorats'      => Governorate::with('delegations')->orderBy('id')->get(),
        ]);
    }

    // =========================================================================
    // Targeted correction — submit the fixes (only flagged items are validated)
    // =========================================================================

    public function submitCorrection(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $rescueCertificate->isNeedsCorrection()) {
            return redirect()->route('rescue-certificates.show', $rescueCertificate)
                ->withErrors(['status' => __('rescue_certificate.not_needs_correction')]);
        }

        $correction  = $rescueCertificate->lastCorrectionData();
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'victim_is_minor', 'spouse_case', 'governorate_id', 'delegation_id', 'incident_address', 'incident_type', 'incident_date', 'incident_time', 'incident_description', 'notes'];
        $fieldsToFix = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix   = array_map('intval', $correction['docs_to_fix'] ?? []);

        // ── Validation rules built ONLY for the fields the admin flagged ──
        $rules = [];
        if (in_array('cin', $fieldsToFix))     { $rules['cin']        = ['required', 'string', 'max:20']; }
        if (in_array('nom_prenom', $fieldsToFix)) { $rules['nom_prenom'] = ['required', 'string', 'max:255']; }
        if (in_array('phone', $fieldsToFix))   { $rules['phone']      = ['required', 'string', 'max:20']; }
        if (in_array('victim_is_minor', $fieldsToFix)) {
            $rules['victim_is_minor'] = ['nullable', 'boolean'];
            $rules['parent_name']     = ['required_if:victim_is_minor,1', 'nullable', 'string', 'max:255'];
            $rules['parent_cin']      = ['required_if:victim_is_minor,1', 'nullable', 'string', 'max:20'];
        }
        if (in_array('spouse_case', $fieldsToFix))    { $rules['spouse_case']          = ['nullable', 'boolean']; }
        if (in_array('governorate_id', $fieldsToFix)) { $rules['governorate_id']       = ['required', 'integer', 'exists:governorates,id']; }
        if (in_array('delegation_id', $fieldsToFix))  { $rules['delegation_id']        = ['required', 'integer', 'exists:delegations,id']; }
        if (in_array('incident_address', $fieldsToFix))     { $rules['incident_address']     = ['required', 'string', 'max:500']; }
        if (in_array('incident_type', $fieldsToFix))        { $rules['incident_type']        = ['required', 'string', 'in:' . implode(',', RescueCertificate::TYPES)]; }
        if (in_array('incident_date', $fieldsToFix))        { $rules['incident_date']        = ['required', 'date']; }
        if (in_array('incident_time', $fieldsToFix))        { $rules['incident_time']        = ['nullable', 'date_format:H:i']; }
        if (in_array('incident_description', $fieldsToFix)) { $rules['incident_description'] = ['required', 'string', 'max:5000']; }
        if (in_array('notes', $fieldsToFix))                { $rules['notes']                = ['nullable', 'string', 'max:3000']; }

        foreach ($docsToFix as $docTypeId) {
            $rules["documents.{$docTypeId}"]   = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"] = ['file', 'max:10240'];
        }

        $validated = $request->validate($rules);

        // ── Apply updates: only the flagged fields ──
        $update = [];

        if (in_array('cin', $fieldsToFix))                  { $update['cin']        = $validated['cin']; }
        if (in_array('nom_prenom', $fieldsToFix))           { $update['nom_prenom'] = $validated['nom_prenom']; }
        if (in_array('phone', $fieldsToFix))                { $update['phone']      = $validated['phone']; }
        if (in_array('victim_is_minor', $fieldsToFix)) {
            $update['victim_is_minor'] = $request->boolean('victim_is_minor');
            $update['parent_name']     = $request->boolean('victim_is_minor') ? ($validated['parent_name'] ?? null) : null;
            $update['parent_cin']      = $request->boolean('victim_is_minor') ? ($validated['parent_cin'] ?? null) : null;
        }
        if (in_array('spouse_case', $fieldsToFix))          { $update['spouse_case'] = $request->boolean('spouse_case'); }
        if (in_array('governorate_id', $fieldsToFix))       { $update['governorate_id'] = $validated['governorate_id']; }
        if (in_array('delegation_id', $fieldsToFix))        { $update['delegation_id'] = $validated['delegation_id']; }
        if (in_array('incident_address', $fieldsToFix))     { $update['incident_address'] = $validated['incident_address']; }
        if (in_array('incident_type', $fieldsToFix))        { $update['incident_type'] = $validated['incident_type']; }
        if (in_array('incident_date', $fieldsToFix))        { $update['incident_date'] = $validated['incident_date']; }
        if (in_array('incident_time', $fieldsToFix))        { $update['incident_time'] = $validated['incident_time'] ?? null; }
        if (in_array('incident_description', $fieldsToFix)) { $update['incident_description'] = $validated['incident_description']; }
        if (in_array('notes', $fieldsToFix))                { $update['notes'] = $validated['notes'] ?? null; }

        if (! empty($update)) {
            $rescueCertificate->update($update);
        }

        // ── Upload new files for flagged document types (multi-file per type) ──
        $uploadDir = "rescue-documents/{$rescueCertificate->id}";
        $uploadedCount = 0;

        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                RescueDocument::create([
                    'rescue_certificate_id' => $rescueCertificate->id,
                    'document_type_id'      => $docTypeId,
                    'file_path'             => $path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'size'                  => $file->getSize(),
                    'status'                => 'uploaded',
                ]);
                $uploadedCount++;
            }
        }

        // Mark request as completed (submitted for review) + keep the reference
        $rescueCertificate->update([
            'status'       => 'completed',
            'current_step' => 2,
            'reference'    => $rescueCertificate->reference ?: \App\Support\Reference::make('rescue', $rescueCertificate),
        ]);

        $rescueCertificate->logEvent('resubmitted', null, [
            'updated'      => $update,
            'fields_fixed' => $fieldsToFix,
            'docs_fixed'   => $docsToFix,
        ]);

        DossierNotifier::notify($rescueCertificate, 'resubmitted', 'admin', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.correction_submitted_success'));
    }

    // =========================================================================
    // Resubmit after correction — legacy POST now bounces to the correct page
    // =========================================================================

    public function resubmit(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        if ($rescueCertificate->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $rescueCertificate->isNeedsCorrection()) {
            return redirect()->route('rescue-certificates.show', $rescueCertificate)
                ->withErrors(['status' => __('rescue_certificate.resubmit_forbidden')]);
        }

        return redirect()->route('rescue-certificates.correct', $rescueCertificate);
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

        // Optional local override (e.g. delete from /mes-demandes)
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)
                ->with('success', __('rescue_certificate.delete_success'));
        }

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
