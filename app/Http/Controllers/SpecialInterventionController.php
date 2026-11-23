<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\SpecialIntervention;
use App\Models\SpecialInterventionDocument;
use App\Models\Service;
use App\Services\DossierNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialInterventionController extends Controller
{
    // =========================================================================
    // Index
    // =========================================================================

    public function index(): View
    {
        $drafts = SpecialIntervention::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        $submitted = SpecialIntervention::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'under_consultation', 'needs_correction', 'approved', 'served', 'cancelled'])
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        return view('special-interventions.index', compact('drafts', 'submitted'));
    }

    // =========================================================================
    // Show — full lifecycle page
    // =========================================================================

    public function show(SpecialIntervention $specialIntervention): View
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        $specialIntervention->load(['user', 'governorate', 'delegation', 'documents.documentType', 'events.actor']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_SPECIAL_INTERVENTIONS)
            ->orderBy('sort_order')->get();

        return view('special-interventions.show', [
            'specialIntervention' => $specialIntervention,
            'documentTypes'       => $documentTypes,
            'archivedDocuments'   => $specialIntervention->documents->filter(fn ($d) => $d->status === 'archived')->sortBy('created_at'),
            'allCorrectionRounds' => $specialIntervention->allCorrectionRounds(),
        ]);
    }

    // =========================================================================
    // Step 1 — Create form
    // =========================================================================

    public function create(): View
    {
        $drafts = SpecialIntervention::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()->get();

        return view('special-interventions.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
            'drafts'       => $drafts,
        ]);
    }

    // =========================================================================
    // Step 1 — Edit existing draft
    // =========================================================================

    public function editStep1(SpecialIntervention $specialIntervention): View
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        return view('special-interventions.create', [
            'gouvernorats'        => $this->gouvernorats(),
            'delegations'         => $this->delegations(),
            'specialIntervention' => $specialIntervention,
        ]);
    }

    // =========================================================================
    // Step 1 — Store new draft
    // =========================================================================

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $specialIntervention = SpecialIntervention::create(array_merge(
            $this->step1Data($validated),
            ['user_id' => auth()->id(), 'status' => 'draft', 'current_step' => 1]
        ));

        return redirect()->route('special-interventions.step2.show', $specialIntervention);
    }

    // =========================================================================
    // Step 1 — Update draft
    // =========================================================================

    public function updateStep1(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        $validated = $request->validate($this->step1Rules());
        $specialIntervention->update($this->step1Data($validated));

        return redirect()->route('special-interventions.step2.show', $specialIntervention);
    }

    // =========================================================================
    // Step 2 — Show intervention selection
    // =========================================================================

    public function showStep2(SpecialIntervention $specialIntervention): View|RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if ($specialIntervention->status !== 'draft') {
            return redirect()->route('special-interventions.index')
                ->withErrors(['status' => __('special_intervention.not_editable')]);
        }

        $specialIntervention->load(['governorate', 'delegation']);

        return view('special-interventions.create-step2', [
            'specialIntervention' => $specialIntervention,
            'interventions'       => SpecialIntervention::interventionList(),
        ]);
    }

    // =========================================================================
    // Step 2 — Save intervention selection
    // =========================================================================

    public function storeStep2(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if ($specialIntervention->status !== 'draft') {
            return redirect()->route('special-interventions.index')
                ->withErrors(['status' => __('special_intervention.not_editable')]);
        }

        $request->validate([
            'interventions'         => ['required', 'array', 'min:1'],
            'interventions.*.key'   => ['required', 'string', 'in:' . implode(',', array_keys(SpecialIntervention::INTERVENTIONS))],
            'interventions.*.hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        $selected = [];
        foreach ($request->input('interventions', []) as $item) {
            $key   = $item['key'];
            $hours = (int) ($item['hours'] ?? 1);
            $price = SpecialIntervention::interventionPrice($key) ?? 0;
            $selected[] = [
                'key'       => $key,
                'name_ar'   => trans('special_intervention.intervention_' . $key, [], 'ar'),
                'name_fr'   => trans('special_intervention.intervention_' . $key, [], 'fr'),
                'price_tnd' => $price,
                'hours'     => $hours,
                'amount'    => $price * $hours,
            ];
        }

        $specialIntervention->update([
            'current_step'  => 2,
            'interventions' => $selected,
            'total_amount'  => array_reduce($selected, fn ($c, $i) => $c + $i['amount'], 0),
        ]);

        return redirect()->route('special-interventions.step3.show', $specialIntervention);
    }

    // =========================================================================
    // Step 3 — Show document upload
    // =========================================================================

    public function showStep3(SpecialIntervention $specialIntervention): View|RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if ($specialIntervention->status !== 'draft') {
            return redirect()->route('special-interventions.index')
                ->withErrors(['status' => __('special_intervention.not_editable')]);
        }

        if (empty($specialIntervention->interventions)) {
            return redirect()->route('special-interventions.step2.show', $specialIntervention);
        }

        $specialIntervention->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_SPECIAL_INTERVENTIONS)
            ->orderBy('sort_order')->get();

        $uploadedDocs = SpecialInterventionDocument::where('special_intervention_id', $specialIntervention->id)
            ->where('status', '!=', 'archived')
            ->get()->keyBy('document_type_id');

        return view('special-interventions.create-step3', [
            'specialIntervention' => $specialIntervention,
            'documentTypes'       => $documentTypes,
            'uploadedDocs'        => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 3 — Upload documents + submit
    // =========================================================================

    public function storeStep3(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if ($specialIntervention->status !== 'draft') {
            return redirect()->route('special-interventions.index')
                ->withErrors(['status' => __('special_intervention.not_editable')]);
        }

        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_SPECIAL_INTERVENTIONS)
            ->orderBy('sort_order')->pluck('id');

        $rules = [];
        foreach ($documentTypeIds as $id) {
            $rules['documents.' . $id]        = ['nullable', 'array'];
            $rules['documents.' . $id . '.*'] = ['nullable', 'file', 'max:10240'];
        }
        $request->validate($rules);

        $uploadDir = "special-intervention-documents/{$specialIntervention->id}";

        foreach ($documentTypeIds as $id) {
            $files = $request->file("documents.{$id}");
            if (! $files) continue;
            if (! is_array($files)) $files = [$files];
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');
                SpecialInterventionDocument::create([
                    'special_intervention_id' => $specialIntervention->id,
                    'document_type_id'        => $id,
                    'file_path'               => $path,
                    'original_name'           => $file->getClientOriginalName(),
                    'mime_type'               => $file->getMimeType(),
                    'size'                    => $file->getSize(),
                    'status'                  => 'uploaded',
                ]);
            }
        }

        $isResubmission = $specialIntervention->events()->where('type', 'correction_requested')->exists();

        $specialIntervention->update([
            'current_step' => 3,
            'status'       => 'completed',
            'reference'    => $specialIntervention->reference
                ?: \App\Support\Reference::make('special-intervention', $specialIntervention),
        ]);

        $specialIntervention->logEvent($isResubmission ? 'resubmitted' : 'submitted', null, [
            'total_amount' => $specialIntervention->total_amount,
        ]);

        DossierNotifier::notify($specialIntervention, $isResubmission ? 'resubmitted' : 'submitted', 'admin', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect('/mes-demandes')->with('success', __('special_intervention.submit_success'));
    }

    // =========================================================================
    // Correction — show form
    // =========================================================================

    public function showCorrectForm(SpecialIntervention $specialIntervention): View|RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if (! $specialIntervention->isNeedsCorrection()) {
            return redirect()->route('special-interventions.show', $specialIntervention)
                ->withErrors(['status' => __('special_intervention.not_needs_correction')]);
        }

        $specialIntervention->load(['governorate', 'delegation', 'documents.documentType', 'events']);

        $correction   = $specialIntervention->lastCorrectionData();
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id', 'intervention_date', 'intervention_time', 'notes', 'interventions'];
        $fieldsToFix  = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix    = $correction['docs_to_fix'] ?? [];

        $documentTypes = collect();
        if (! empty($docsToFix)) {
            $documentTypes = DocumentType::service(DocumentType::SERVICE_SPECIAL_INTERVENTIONS)
                ->orderBy('sort_order')->get()
                ->filter(fn ($t) => in_array($t->id, $docsToFix))->values();
        }

        $archivedDocs = SpecialInterventionDocument::where('special_intervention_id', $specialIntervention->id)
            ->where('status', 'archived')->orderBy('created_at', 'desc')->get();

        return view('special-interventions.correct', [
            'specialIntervention' => $specialIntervention,
            'correction'          => $correction,
            'fieldsToFix'         => $fieldsToFix,
            'docsToFix'           => $docsToFix,
            'documentTypes'       => $documentTypes,
            'archivedDocs'        => $archivedDocs,
            'gouvernorats'        => Governorate::with('delegations')->orderBy('id')->get(),
            'interventions'       => SpecialIntervention::interventionList(),
        ]);
    }

    // =========================================================================
    // Correction — submit fixes
    // =========================================================================

    public function submitCorrection(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if (! $specialIntervention->isNeedsCorrection()) {
            return redirect()->route('special-interventions.show', $specialIntervention)
                ->withErrors(['status' => __('special_intervention.not_needs_correction')]);
        }

        $correction   = $specialIntervention->lastCorrectionData();
        $allFieldKeys = ['cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id', 'intervention_date', 'intervention_time', 'notes', 'interventions'];
        $fieldsToFix  = $correction !== null ? ($correction['fields_to_fix'] ?? $allFieldKeys) : $allFieldKeys;
        $docsToFix    = array_map('intval', $correction['docs_to_fix'] ?? []);

        $rules = [];
        if (in_array('cin', $fieldsToFix))               $rules['cin']               = ['required', 'string', 'max:20'];
        if (in_array('nom_prenom', $fieldsToFix))        $rules['nom_prenom']        = ['required', 'string', 'max:255'];
        if (in_array('phone', $fieldsToFix))             $rules['phone']             = ['required', 'string', 'max:20'];
        if (in_array('governorate_id', $fieldsToFix))    $rules['governorate_id']    = ['required', 'integer', 'exists:governorates,id'];
        if (in_array('delegation_id', $fieldsToFix))     $rules['delegation_id']     = ['required', 'integer', 'exists:delegations,id'];
        if (in_array('intervention_date', $fieldsToFix)) $rules['intervention_date'] = ['required', 'date'];
        if (in_array('intervention_time', $fieldsToFix)) $rules['intervention_time'] = ['nullable', 'date_format:H:i'];
        if (in_array('notes', $fieldsToFix))             $rules['notes']             = ['nullable', 'string', 'max:3000'];
        if (in_array('interventions', $fieldsToFix)) {
            $rules['interventions']         = ['required', 'array', 'min:1'];
            $rules['interventions.*.key']   = ['required', 'string', 'in:' . implode(',', array_keys(SpecialIntervention::INTERVENTIONS))];
            $rules['interventions.*.hours'] = ['required', 'integer', 'min:1', 'max:168'];
        }
        foreach ($docsToFix as $docTypeId) {
            $rules["documents.{$docTypeId}"]   = ['nullable', 'array', 'max:10'];
            $rules["documents.{$docTypeId}.*"] = ['file', 'max:10240'];
        }

        $validated = $request->validate($rules);
        $update    = [];

        if (in_array('cin', $fieldsToFix))               $update['cin']               = $validated['cin'];
        if (in_array('nom_prenom', $fieldsToFix))        $update['nom_prenom']        = $validated['nom_prenom'];
        if (in_array('phone', $fieldsToFix))             $update['phone']             = $validated['phone'];
        if (in_array('governorate_id', $fieldsToFix))    $update['governorate_id']    = $validated['governorate_id'];
        if (in_array('delegation_id', $fieldsToFix))     $update['delegation_id']     = $validated['delegation_id'];
        if (in_array('intervention_date', $fieldsToFix)) $update['intervention_date'] = $validated['intervention_date'];
        if (in_array('intervention_time', $fieldsToFix)) $update['intervention_time'] = $validated['intervention_time'] ?? null;
        if (in_array('notes', $fieldsToFix))             $update['notes']             = $validated['notes'] ?? null;

        if (in_array('interventions', $fieldsToFix)) {
            $selected = [];
            foreach ($validated['interventions'] as $item) {
                $key   = $item['key'];
                $hours = (int) ($item['hours'] ?? 1);
                $price = SpecialIntervention::interventionPrice($key) ?? 0;
                $selected[] = [
                    'key' => $key,
                    'name_ar'   => trans('special_intervention.intervention_' . $key, [], 'ar'),
                    'name_fr'   => trans('special_intervention.intervention_' . $key, [], 'fr'),
                    'price_tnd' => $price, 'hours' => $hours, 'amount' => $price * $hours,
                ];
            }
            $update['interventions'] = $selected;
            $update['total_amount']  = array_reduce($selected, fn ($c, $i) => $c + $i['amount'], 0);
        }

        if (! empty($update)) $specialIntervention->update($update);

        $uploadDir = "special-intervention-documents/{$specialIntervention->id}";
        foreach ($docsToFix as $docTypeId) {
            $files = $request->file("documents.{$docTypeId}");
            if (! $files) continue;
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) continue;
                $path = $file->store($uploadDir, 'local');
                SpecialInterventionDocument::create([
                    'special_intervention_id' => $specialIntervention->id,
                    'document_type_id' => $docTypeId, 'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'status' => 'uploaded',
                ]);
            }
        }

        $specialIntervention->update([
            'status' => 'completed', 'current_step' => 3,
            'reference' => $specialIntervention->reference
                ?: \App\Support\Reference::make('special-intervention', $specialIntervention),
        ]);

        $specialIntervention->logEvent('resubmitted', null, [
            'updated' => $update, 'fields_fixed' => $fieldsToFix, 'docs_fixed' => $docsToFix,
        ]);

        DossierNotifier::notify($specialIntervention, 'resubmitted', 'admin', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.correction_submitted_success'));
    }

    // =========================================================================
    // Resubmit bounce
    // =========================================================================

    public function resubmit(SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if (! $specialIntervention->isNeedsCorrection()) {
            return redirect()->route('special-interventions.show', $specialIntervention)
                ->withErrors(['status' => __('special_intervention.resubmit_forbidden')]);
        }

        return redirect()->route('special-interventions.correct', $specialIntervention);
    }

    // =========================================================================
    // Document / template downloads
    // =========================================================================

    public function downloadDocument(SpecialInterventionDocument $specialInterventionDocument)
    {
        abort_unless($specialInterventionDocument->intervention->user_id === auth()->id(), 403);

        return \Storage::disk('local')->download(
            $specialInterventionDocument->file_path,
            $specialInterventionDocument->original_name
        );
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

    public function destroy(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        if ($specialIntervention->user_id !== auth()->id()) abort(403);

        if (! $specialIntervention->isDraft()) {
            return redirect()->route('special-interventions.index')
                ->withErrors(['status' => __('special_intervention.delete_forbidden')]);
        }

        $specialIntervention->delete();

        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)->with('success', __('special_intervention.delete_success'));
        }

        return redirect()->route('special-interventions.index')
            ->with('success', __('special_intervention.delete_success'));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function gouvernorats(): array { return Governorate::forSelect(); }
    private function delegations(): array  { return Governorate::delegationsMap(); }

    private function step1Rules(): array
    {
        return [
            'cin'               => ['required', 'string', 'max:20'],
            'nom_prenom'        => ['required', 'string', 'max:255'],
            'phone'             => ['required', 'string', 'max:20'],
            'governorate_id'    => ['required', 'integer', 'exists:governorates,id'],
            'delegation_id'     => ['required', 'integer', 'exists:delegations,id'],
            'intervention_date' => ['required', 'date', 'after_or_equal:today'],
            'intervention_time' => ['nullable', 'date_format:H:i'],
            'notes'             => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function step1Data(array $validated): array
    {
        return [
            'cin'               => $validated['cin'],
            'nom_prenom'        => $validated['nom_prenom'],
            'phone'             => $validated['phone'],
            'governorate_id'    => $validated['governorate_id'],
            'delegation_id'     => $validated['delegation_id'],
            'intervention_date' => $validated['intervention_date'],
            'intervention_time' => $validated['intervention_time'] ?? null,
            'notes'             => $validated['notes'] ?? null,
        ];
    }
}
