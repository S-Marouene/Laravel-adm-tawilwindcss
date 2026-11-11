<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\BuildingWorksDeclaration;
use App\Models\BuildingWorksDocument;
use App\Models\Service;
use App\Services\DossierNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BuildingWorksDeclarationController extends Controller
{
    // =========================================================================
    // Index — list all local notifications for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        $drafts = BuildingWorksDeclaration::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        $submitted = BuildingWorksDeclaration::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'under_consultation', 'needs_correction', 'approved', 'cancelled'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('building-works-declarations.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
        ]);
    }

    // =========================================================================
    // Step 1 — Show form (owner + building + works details)
    // =========================================================================

    public function create(): View
    {
        $drafts = BuildingWorksDeclaration::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('building-works-declarations.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
        ]);
    }

    public function editStep1(BuildingWorksDeclaration $buildingWorksDeclaration): View|RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        return view('building-works-declarations.create', [
            'gouvernorats'     => $this->gouvernorats(),
            'delegations'      => $this->delegations(),
            'buildingWorksDeclaration' => $buildingWorksDeclaration, // pre-fills the form & unlocks "editing" mode
        ]);
    }

    public function updateStep1(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate($this->step1Rules());

        $buildingWorksDeclaration->update($this->step1Data($validated));

        return redirect()->route('building-works-declarations.step2.show', $buildingWorksDeclaration);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $buildingWorksDeclaration = BuildingWorksDeclaration::create(array_merge(
            $this->step1Data($validated),
            [
                'user_id'      => auth()->id(),
                'status'       => 'draft',
                'current_step' => 1,
            ]
        ));

        return redirect()->route('building-works-declarations.step2.show', $buildingWorksDeclaration);
    }

    // =========================================================================
    // Step 2 — Show document upload form
    // =========================================================================

    public function showStep2(BuildingWorksDeclaration $buildingWorksDeclaration): View|RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if ($buildingWorksDeclaration->status !== 'draft') {
            return redirect()->route('building-works-declarations.index')
                ->withErrors(['status' => __('building-works-declarations.not_editable')]);
        }

        $buildingWorksDeclaration->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->get();

        $uploadedDocs = BuildingWorksDocument::where('works_notification_id', $buildingWorksDeclaration->id)
            ->where('status', '!=', 'archived')
            ->get()
            ->keyBy('document_type_id');

        return view('building-works-declarations.create-step2', [
            'buildingWorksDeclaration' => $buildingWorksDeclaration,
            'documentTypes'     => $documentTypes,
            'uploadedDocs'      => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 2 — Save uploaded documents & submit the notification
    // =========================================================================

    public function storeStep2(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if ($buildingWorksDeclaration->status !== 'draft') {
            return redirect()->route('building-works-declarations.index')
                ->withErrors(['status' => __('building-works-declarations.not_editable')]);
        }

        $rules = [];
        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->pluck('id');

        foreach ($documentTypeIds as $docTypeId) {
            $rules["documents.{$docTypeId}"]   = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"] = ['file', 'max:10240']; // 10MB max per file
        }

        $request->validate($rules);

        $uploadDir = "building-building-building-building-works-documents/{$buildingWorksDeclaration->id}";

        foreach ($documentTypeIds as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            // Accept both single file (old input) and array (new multi-upload)
            if (! is_array($files)) $files = [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');

                // Always create a new row — multiple active files per type are
                // allowed (same model as prevention & rescue documents).
                BuildingWorksDocument::create([
                    'works_notification_id' => $buildingWorksDeclaration->id,
                    'document_type_id'      => $docTypeId,
                    'file_path'             => $path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'size'                  => $file->getSize(),
                    'status'                => 'uploaded',
                ]);
            }
        }

        $buildingWorksDeclaration->update([
            'current_step' => 2,
            'status'       => 'completed',
            'reference'    => $buildingWorksDeclaration->reference ?: \App\Support\Reference::make('works', $buildingWorksDeclaration),
        ]);

        $buildingWorksDeclaration->logEvent('submitted');

        DossierNotifier::notify($buildingWorksDeclaration, 'submitted', 'admin', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()
            ->route('building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', __('building-works-declarations.submit_success'));
    }

    // =========================================================================
    // Resubmit after correction — legacy POST now bounces to the correct page
    // =========================================================================

    public function resubmit(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $buildingWorksDeclaration->isNeedsCorrection()) {
            return redirect()->route('building-works-declarations.index')
                ->withErrors(['status' => __('building-works-declarations.resubmit_forbidden')]);
        }

        return redirect()->route('building-works-declarations.correct', $buildingWorksDeclaration);
    }

    // =========================================================================
    // Show — full lifecycle detail page (stepper, timeline, documents)
    // =========================================================================

    public function show(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): View
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        $buildingWorksDeclaration->load(['governorate', 'delegation', 'documents.documentType', 'events.actor']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->get();

        return view('building-works-declarations.show', [
            'notification'        => $buildingWorksDeclaration,
            'documentTypes'       => $documentTypes,
            'archivedDocuments'   => $buildingWorksDeclaration->documents->filter(fn ($d) => $d->status === 'archived')->sortBy('created_at'),
            'allCorrectionRounds' => $buildingWorksDeclaration->allCorrectionRounds(),
        ]);
    }

    // =========================================================================
    // Download an uploaded document (owner only)
    // =========================================================================

    public function downloadDocument(BuildingWorksDeclaration $buildingWorksDeclaration, BuildingWorksDocument $buildingWorksDocument)
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        abort_unless($buildingWorksDocument->works_notification_id === $buildingWorksDeclaration->id, 404);
        abort_unless(Storage::disk('local')->exists($buildingWorksDocument->file_path), 404);

        return Storage::disk('local')->download($buildingWorksDocument->file_path, $buildingWorksDocument->original_name);
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
    // Targeted correction — show the form (fields + documents flagged by admin)
    // =========================================================================

    public function showCorrectForm(BuildingWorksDeclaration $buildingWorksDeclaration): View|RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $buildingWorksDeclaration->isNeedsCorrection()) {
            return redirect()->route('building-works-declarations.show', $buildingWorksDeclaration)
                ->withErrors(['status' => __('building-works-declarations.not_needs_correction')]);
        }

        $buildingWorksDeclaration->load(['governorate', 'delegation', 'documents.documentType', 'events']);

        $correction = $buildingWorksDeclaration->lastCorrectionData();

        // Legacy fallback: no correction snapshot stored → everything stays editable
        $allFieldKeys = ['owner_cin', 'owner_name', 'company_name', 'jort_reference', 'phone', 'governorate_id', 'delegation_id', 'property_address', 'works_type', 'works_start_date', 'works_description', 'notes'];
        $fieldsToFix = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix   = $correction['docs_to_fix'] ?? [];

        // Load only the document types the admin flagged
        $documentTypes = collect();
        if (! empty($docsToFix)) {
            $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
                ->orderBy('sort_order')->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))->values();
        }

        // Archived docs (the ones replaced during previous correction rounds)
        $archivedDocs = BuildingWorksDocument::where('works_notification_id', $buildingWorksDeclaration->id)
            ->where('status', 'archived')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('building-works-declarations.correct', [
            'buildingWorksDeclaration' => $buildingWorksDeclaration,
            'correction'        => $correction,
            'fieldsToFix'       => $fieldsToFix,
            'docsToFix'         => $docsToFix,
            'documentTypes'     => $documentTypes,
            'archivedDocs'      => $archivedDocs,
            'gouvernorats'      => $this->gouvernorats(),
            'delegations'       => $this->delegations(),
        ]);
    }

    // =========================================================================
    // Targeted correction — submit the fixes (only flagged items are validated)
    // =========================================================================

    public function submitCorrection(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $buildingWorksDeclaration->isNeedsCorrection()) {
            return redirect()->route('building-works-declarations.show', $buildingWorksDeclaration)
                ->withErrors(['status' => __('building-works-declarations.not_needs_correction')]);
        }

        $correction  = $buildingWorksDeclaration->lastCorrectionData();
        $allFieldKeys = ['owner_cin', 'owner_name', 'company_name', 'jort_reference', 'phone', 'governorate_id', 'delegation_id', 'property_address', 'works_type', 'works_start_date', 'works_description', 'notes'];
        $fieldsToFix = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix   = array_map('intval', $correction['docs_to_fix'] ?? []);

        // ── Validation rules built ONLY for the fields the admin flagged ──
        $rules = [];
        if (in_array('owner_cin', $fieldsToFix))       { $rules['owner_cin']       = ['required', 'string', 'max:20']; }
        if (in_array('owner_name', $fieldsToFix))      { $rules['owner_name']      = ['required_if:owner_type,natural', 'nullable', 'string', 'max:255']; }
        if (in_array('company_name', $fieldsToFix))    { $rules['company_name']    = ['required_if:owner_type,moral', 'nullable', 'string', 'max:255']; }
        if (in_array('jort_reference', $fieldsToFix))  { $rules['jort_reference']  = ['nullable', 'string', 'max:100']; }
        if (in_array('phone', $fieldsToFix))           { $rules['phone']           = ['required', 'string', 'max:20']; }
        if (in_array('governorate_id', $fieldsToFix))  { $rules['governorate_id']  = ['required', 'integer', 'between:1,24', 'exists:governorates,id']; }
        if (in_array('delegation_id', $fieldsToFix))   { $rules['delegation_id']   = ['required', 'integer', 'exists:delegations,id']; }
        if (in_array('property_address', $fieldsToFix))  { $rules['property_address']  = ['required', 'string', 'max:500']; }
        if (in_array('works_type', $fieldsToFix))        { $rules['works_type']        = ['nullable', 'string', 'max:100']; }
        if (in_array('works_start_date', $fieldsToFix))  { $rules['works_start_date']  = ['nullable', 'date']; }
        if (in_array('works_description', $fieldsToFix)) { $rules['works_description'] = ['required', 'string', 'max:5000']; }
        if (in_array('notes', $fieldsToFix))             { $rules['notes']             = ['nullable', 'string', 'max:3000']; }
        $rules['owner_type'] = ['required', 'string', 'in:' . implode(',', BuildingWorksDeclaration::OWNER_TYPES)];

        foreach ($docsToFix as $docTypeId) {
            $rules["documents.{$docTypeId}"]   = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"] = ['file', 'max:10240'];
        }

        $validated = $request->validate($rules);

        // ── Apply updates: only the flagged fields ──
        $update = [];

        if (in_array('owner_cin', $fieldsToFix))         { $update['owner_cin']       = $validated['owner_cin']; }
        if (in_array('company_name', $fieldsToFix))      { $update['company_name']    = $validated['owner_type'] === BuildingWorksDeclaration::OWNER_MORAL ? ($validated['company_name'] ?? null) : null; }
        if (in_array('owner_name', $fieldsToFix))        { $update['owner_name']      = $validated['owner_type'] === BuildingWorksDeclaration::OWNER_MORAL ? ($validated['company_name'] ?? null) : ($validated['owner_name'] ?? null); }
        if (in_array('jort_reference', $fieldsToFix))    { $update['jort_reference']  = $validated['jort_reference'] ?? null; }
        if (in_array('phone', $fieldsToFix))             { $update['phone']           = $validated['phone']; }
        if (in_array('governorate_id', $fieldsToFix))    { $update['governorate_id']  = $validated['governorate_id']; }
        if (in_array('delegation_id', $fieldsToFix))     { $update['delegation_id']   = $validated['delegation_id']; }
        if (in_array('property_address', $fieldsToFix))  { $update['property_address']  = $validated['property_address']; }
        if (in_array('works_type', $fieldsToFix))        { $update['works_type']        = $validated['works_type'] ?? null; }
        if (in_array('works_start_date', $fieldsToFix))  { $update['works_start_date']  = $validated['works_start_date'] ?? null; }
        if (in_array('works_description', $fieldsToFix)) { $update['works_description'] = $validated['works_description']; }
        if (in_array('notes', $fieldsToFix))             { $update['notes']             = $validated['notes'] ?? null; }
        $update['owner_type'] = $validated['owner_type'];

        if (! empty($update)) {
            $buildingWorksDeclaration->update($update);
        }

        // ── Upload new files for flagged document types (multi-file per type) ──
        $uploadDir = "building-building-building-building-works-documents/{$buildingWorksDeclaration->id}";
        $uploadedCount = 0;

        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;

                $path = $file->store($uploadDir, 'local');

                BuildingWorksDocument::create([
                    'works_notification_id' => $buildingWorksDeclaration->id,
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
        $buildingWorksDeclaration->update([
            'status'       => 'completed',
            'current_step' => 2,
            'reference'    => $buildingWorksDeclaration->reference ?: \App\Support\Reference::make('works', $buildingWorksDeclaration),
        ]);

        $buildingWorksDeclaration->logEvent('resubmitted', null, [
            'updated'      => $update,
            'fields_fixed' => $fieldsToFix,
            'docs_fixed'   => $docsToFix,
        ]);

        DossierNotifier::notify($buildingWorksDeclaration, 'resubmitted', 'admin', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()->route('building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', __('building-works-declarations.correction_submitted_success'));
    }

    // =========================================================================
    // Delete a local draft
    // =========================================================================

    public function destroy(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        if ($buildingWorksDeclaration->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $buildingWorksDeclaration->isDraft()) {
            return redirect()->route('building-works-declarations.index')
                ->withErrors(['status' => __('building-works-declarations.delete_forbidden')]);
        }

        $buildingWorksDeclaration->delete();

        // Optional local override (e.g. delete from /mes-demandes)
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)
                ->with('success', __('building-works-declarations.delete_success'));
        }

        return redirect()->route('building-works-declarations.index')
            ->with('success', __('building-works-declarations.delete_success'));
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
            'owner_type'        => ['required', 'string', 'in:' . implode(',', BuildingWorksDeclaration::OWNER_TYPES)],
            'owner_name'        => ['required_if:owner_type,natural', 'nullable', 'string', 'max:255'],
            'owner_cin'         => ['required', 'string', 'max:20'], // owner (natural) or legal representative (moral)
            'company_name'      => ['required_if:owner_type,moral', 'nullable', 'string', 'max:255'],
            'jort_reference'    => ['nullable', 'string', 'max:100'],
            'phone'             => ['required', 'string', 'max:20'],
            'governorate_id'    => ['required', 'integer', 'between:1,24', 'exists:governorates,id'],
            'delegation_id'     => ['required', 'integer', 'exists:delegations,id'],
            'property_address'  => ['required', 'string', 'max:500'],
            'works_type'        => ['nullable', 'string', 'max:100'],
            'works_start_date'  => ['nullable', 'date'],
            'works_description' => ['required', 'string', 'max:5000'],
            'notes'             => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function step1Data(array $validated): array
    {
        $isMoral = ($validated['owner_type'] ?? 'natural') === BuildingWorksDeclaration::OWNER_MORAL;

        return [
            'owner_type'        => $validated['owner_type'],
            'owner_name'        => $isMoral ? ($validated['company_name'] ?? null) : ($validated['owner_name'] ?? null),
            'owner_cin'         => $validated['owner_cin'],
            'company_name'      => $isMoral ? ($validated['company_name'] ?? null) : null,
            'jort_reference'    => $validated['jort_reference'] ?? null,
            'phone'             => $validated['phone'],
            'governorate_id'    => $validated['governorate_id'],
            'delegation_id'     => $validated['delegation_id'],
            'property_address'  => $validated['property_address'],
            'works_type'        => $validated['works_type'] ?? null,
            'works_start_date'  => $validated['works_start_date'] ?? null,
            'works_description' => $validated['works_description'],
            'notes'             => $validated['notes'] ?? null,
        ];
    }
}
