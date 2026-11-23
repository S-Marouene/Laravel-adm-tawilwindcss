<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\TrainingDocument;
use App\Models\TrainingRequest;
use App\Models\Service;
use App\Services\DossierNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    // =========================================================================
    // Index
    // =========================================================================

    public function index(): View
    {
        $drafts = TrainingRequest::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        $submitted = TrainingRequest::where('user_id', auth()->id())
            ->whereNotIn('status', ['draft'])
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        return view('training.index', compact('drafts', 'submitted'));
    }

    // =========================================================================
    // Show
    // =========================================================================

    public function show(TrainingRequest $trainingRequest): View
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        $trainingRequest->load(['user', 'governorate', 'delegation', 'documents.documentType', 'events.actor', 'sessions']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_TRAINING)
            ->orderBy('sort_order')->get();

        return view('training.show', [
            'trainingRequest'     => $trainingRequest,
            'documentTypes'       => $documentTypes,
            'archivedDocuments'   => $trainingRequest->documents->filter(fn ($d) => $d->status === 'archived')->sortBy('created_at'),
            'allCorrectionRounds' => $trainingRequest->allCorrectionRounds(),
        ]);
    }

    // =========================================================================
    // Step 1 — Create
    // =========================================================================

    public function create(): View
    {
        $drafts = TrainingRequest::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        return view('training.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
        ]);
    }

    // =========================================================================
    // Step 1 — Edit
    // =========================================================================

    public function editStep1(TrainingRequest $trainingRequest): View
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        return view('training.create', [
            'gouvernorats'   => $this->gouvernorats(),
            'delegations'    => $this->delegations(),
            'trainingRequest' => $trainingRequest,
        ]);
    }

    // =========================================================================
    // Step 1 — Store
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $trainingRequest = TrainingRequest::create(array_merge(
            $this->step1Data($validated),
            ['user_id' => auth()->id(), 'status' => 'draft', 'current_step' => 1]
        ));

        return redirect()->route('training.step2.show', $trainingRequest);
    }

    // =========================================================================
    // Step 1 — Update
    // =========================================================================

    public function updateStep1(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        $validated = $request->validate($this->step1Rules());
        $trainingRequest->update($this->step1Data($validated));

        return redirect()->route('training.step2.show', $trainingRequest);
    }

    // =========================================================================
    // Step 2 — Show document upload
    // =========================================================================

    public function showStep2(TrainingRequest $trainingRequest): View|RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if ($trainingRequest->status !== 'draft') {
            return redirect()->route('training.index')
                ->withErrors(['status' => __('training.not_editable')]);
        }

        $trainingRequest->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_TRAINING)
            ->orderBy('sort_order')->get();

        $uploadedDocs = TrainingDocument::where('training_request_id', $trainingRequest->id)
            ->where('status', '!=', 'archived')
            ->get()->keyBy('document_type_id');

        return view('training.create-step2', [
            'trainingRequest' => $trainingRequest,
            'documentTypes'   => $documentTypes,
            'uploadedDocs'    => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 2 — Store documents + submit
    // =========================================================================

    public function storeStep2(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if ($trainingRequest->status !== 'draft') {
            return redirect()->route('training.index')
                ->withErrors(['status' => __('training.not_editable')]);
        }

        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_TRAINING)
            ->orderBy('sort_order')->pluck('id');

        $rules = [];
        foreach ($documentTypeIds as $id) {
            $rules['documents.' . $id]        = ['nullable', 'array'];
            $rules['documents.' . $id . '.*'] = ['nullable', 'file', 'max:10240'];
        }
        $request->validate($rules);

        $uploadDir = "training-documents/{$trainingRequest->id}";
        foreach ($documentTypeIds as $id) {
            $files = $request->file("documents.{$id}");
            if (! $files) continue;
            if (! is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');
                TrainingDocument::create([
                    'training_request_id' => $trainingRequest->id,
                    'document_type_id'    => $id,
                    'file_path'           => $path,
                    'original_name'       => $file->getClientOriginalName(),
                    'mime_type'           => $file->getMimeType(),
                    'size'                => $file->getSize(),
                    'status'              => 'uploaded',
                ]);
            }
        }

        $isResubmission = $trainingRequest->events()->where('type', 'correction_requested')->exists();

        $trainingRequest->update([
            'current_step' => 2,
            'status'       => 'completed',
            'reference'    => $trainingRequest->reference
                ?: \App\Support\Reference::make('training', $trainingRequest),
        ]);

        $trainingRequest->logEvent($isResubmission ? 'resubmitted' : 'submitted');

        DossierNotifier::notify($trainingRequest, $isResubmission ? 'resubmitted' : 'submitted', 'admin', Service::SERVICE_TRAINING);

        return redirect('/mes-demandes')->with('success', __('training.submit_success'));
    }

    // =========================================================================
    // Targeted correction — show
    // =========================================================================

    public function showCorrectForm(TrainingRequest $trainingRequest): View|RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if (! $trainingRequest->isNeedsCorrection()) {
            return redirect()->route('training.show', $trainingRequest)
                ->withErrors(['status' => __('training.not_needs_correction')]);
        }

        $trainingRequest->load(['governorate', 'delegation', 'documents.documentType', 'events']);

        $correction   = $trainingRequest->lastCorrectionData();
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id', 'training_type', 'training_kind', 'notes'];
        $fieldsToFix  = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix    = $correction['docs_to_fix'] ?? [];

        $documentTypes = collect();
        if (! empty($docsToFix)) {
            $documentTypes = DocumentType::service(DocumentType::SERVICE_TRAINING)
                ->orderBy('sort_order')->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))->values();
        }

        $archivedDocs = TrainingDocument::where('training_request_id', $trainingRequest->id)
            ->where('status', 'archived')->orderBy('created_at', 'desc')->get();

        return view('training.correct', [
            'trainingRequest' => $trainingRequest,
            'correction'      => $correction,
            'fieldsToFix'     => $fieldsToFix,
            'docsToFix'       => $docsToFix,
            'documentTypes'   => $documentTypes,
            'archivedDocs'    => $archivedDocs,
            'gouvernorats'    => Governorate::with('delegations')->orderBy('id')->get(),
        ]);
    }

    // =========================================================================
    // Targeted correction — submit
    // =========================================================================

    public function submitCorrection(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if (! $trainingRequest->isNeedsCorrection()) {
            return redirect()->route('training.show', $trainingRequest)
                ->withErrors(['status' => __('training.not_needs_correction')]);
        }

        $correction   = $trainingRequest->lastCorrectionData();
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id', 'training_type', 'training_kind', 'notes'];
        $fieldsToFix  = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix    = array_map('intval', $correction['docs_to_fix'] ?? []);

        $rules = [];
        if (in_array('cin', $fieldsToFix))            $rules['cin']           = ['required', 'string', 'max:20'];
        if (in_array('nom_prenom', $fieldsToFix))     $rules['nom_prenom']    = ['required', 'string', 'max:255'];
        if (in_array('phone', $fieldsToFix))          $rules['phone']         = ['required', 'string', 'max:20'];
        if (in_array('governorate_id', $fieldsToFix)) $rules['governorate_id']= ['required', 'integer', 'exists:governorates,id'];
        if (in_array('delegation_id', $fieldsToFix))  $rules['delegation_id'] = ['required', 'integer', 'exists:delegations,id'];
        if (in_array('training_type', $fieldsToFix))  $rules['training_type'] = ['required', 'string', 'in:' . implode(',', array_keys(TrainingRequest::TYPES))];
        if (in_array('training_kind', $fieldsToFix))  $rules['training_kind'] = ['required', 'in:new,recycling'];
        if (in_array('notes', $fieldsToFix))          $rules['notes']         = ['nullable', 'string', 'max:3000'];
        foreach ($docsToFix as $docTypeId) {
            $rules["documents.{$docTypeId}"]   = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"] = ['file', 'max:10240'];
        }

        $validated = $request->validate($rules);
        $update    = [];

        if (in_array('cin', $fieldsToFix))            $update['cin']           = $validated['cin'];
        if (in_array('nom_prenom', $fieldsToFix))     $update['nom_prenom']    = $validated['nom_prenom'];
        if (in_array('phone', $fieldsToFix))          $update['phone']         = $validated['phone'];
        if (in_array('governorate_id', $fieldsToFix)) $update['governorate_id']= $validated['governorate_id'];
        if (in_array('delegation_id', $fieldsToFix))  $update['delegation_id'] = $validated['delegation_id'];
        if (in_array('training_type', $fieldsToFix))  $update['training_type'] = $validated['training_type'];
        if (in_array('training_kind', $fieldsToFix))  $update['training_kind'] = $validated['training_kind'];
        if (in_array('notes', $fieldsToFix))          $update['notes']         = $validated['notes'] ?? null;

        if (! empty($update)) $trainingRequest->update($update);

        $uploadDir = "training-documents/{$trainingRequest->id}";
        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');
                TrainingDocument::create([
                    'training_request_id' => $trainingRequest->id,
                    'document_type_id'    => $docTypeId,
                    'file_path'           => $path,
                    'original_name'       => $file->getClientOriginalName(),
                    'mime_type'           => $file->getMimeType(),
                    'size'                => $file->getSize(),
                    'status'              => 'uploaded',
                ]);
            }
        }

        $trainingRequest->update([
            'status'       => 'completed',
            'current_step' => 2,
            'reference'    => $trainingRequest->reference ?: \App\Support\Reference::make('training', $trainingRequest),
        ]);

        $trainingRequest->logEvent('resubmitted', null, [
            'updated' => $update, 'fields_fixed' => $fieldsToFix, 'docs_fixed' => $docsToFix,
        ]);

        DossierNotifier::notify($trainingRequest, 'resubmitted', 'admin', Service::SERVICE_TRAINING);

        return redirect()->route('training.show', $trainingRequest)
            ->with('success', __('training.correction_submitted_success'));
    }

    // =========================================================================
    // Resubmit bounce
    // =========================================================================

    public function resubmit(TrainingRequest $trainingRequest): RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if (! $trainingRequest->isNeedsCorrection()) {
            return redirect()->route('training.show', $trainingRequest)
                ->withErrors(['status' => __('training.resubmit_forbidden')]);
        }

        return redirect()->route('training.correct', $trainingRequest);
    }

    // =========================================================================
    // Document / template downloads
    // =========================================================================

    public function downloadDocument(TrainingDocument $trainingDocument)
    {
        abort_unless($trainingDocument->trainingRequest->user_id === auth()->id(), 403);

        return \Storage::disk('local')->download($trainingDocument->file_path, $trainingDocument->original_name);
    }

    public function downloadTemplate(\App\Models\DocumentType $documentType)
    {
        if (! $documentType->template_path) abort(404);

        return \Storage::disk('local')->download(
            $documentType->template_path,
            $documentType->template_name ?: ($documentType->name . '.' . $documentType->templateExtension())
        );
    }

    // =========================================================================
    // Delete draft
    // =========================================================================

    public function destroy(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        if ($trainingRequest->user_id !== auth()->id()) abort(403);

        if (! $trainingRequest->isDraft()) {
            return redirect()->route('training.index')
                ->withErrors(['status' => __('training.delete_forbidden')]);
        }

        $trainingRequest->delete();

        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)->with('success', __('training.delete_success'));
        }

        return redirect()->route('training.index')->with('success', __('training.delete_success'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function gouvernorats(): array { return Governorate::forSelect(); }
    private function delegations(): array  { return Governorate::delegationsMap(); }

    private function step1Rules(): array
    {
        return [
            'cin'            => ['required', 'string', 'max:20'],
            'nom_prenom'     => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'delegation_id'  => ['required', 'integer', 'exists:delegations,id'],
            'training_type'  => ['required', 'string', 'in:' . implode(',', array_keys(TrainingRequest::TYPES))],
            'training_kind'  => ['required', 'in:new,recycling'],
            'notes'          => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function step1Data(array $validated): array
    {
        return [
            'cin'            => $validated['cin'],
            'nom_prenom'     => $validated['nom_prenom'],
            'phone'          => $validated['phone'],
            'governorate_id' => $validated['governorate_id'],
            'delegation_id'  => $validated['delegation_id'],
            'training_type'  => $validated['training_type'],
            'training_kind'  => $validated['training_kind'],
            'notes'          => $validated['notes'] ?? null,
        ];
    }
}
