<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\SpecialIntervention;
use App\Models\SpecialInterventionDocument;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialInterventionController extends Controller
{
    // =========================================================================
    // Index — list all non-draft requests
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);

        $query = SpecialIntervention::with(['user', 'governorate', 'delegation'])
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

        $interventions = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.special-interventions.index', compact('interventions'));
    }

    // =========================================================================
    // Show — full request details
    // =========================================================================

    public function show(SpecialIntervention $specialIntervention): View
    {
        $this->authorize('view special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        $specialIntervention->load([
            'user', 'governorate', 'delegation',
            'reviewer',
            'documents.documentType', 'events.actor',
        ]);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_SPECIAL_INTERVENTIONS)
            ->orderBy('sort_order')
            ->get();
        $uploadedDocs  = $specialIntervention->documents->keyBy('document_type_id');

        $user = auth()->user();
        if ($user->adminScope() === 'manager') {
            $territories = $user->territories;
            $govIds = $territories->pluck('governorate_id')->unique();
            $governorates = \App\Models\Governorate::whereIn('id', $govIds)
                ->with(['delegations' => function ($q) use ($territories) {
                    $specificDelIds = $territories->whereNotNull('delegation_id')->pluck('delegation_id');
                    $wholeGovIds    = $territories->whereNull('delegation_id')->pluck('governorate_id');
                    $q->where(function ($sub) use ($specificDelIds, $wholeGovIds) {
                        $sub->whereIn('governorate_id', $wholeGovIds)->orWhereIn('id', $specificDelIds);
                    });
                }])
                ->orderBy('id')->get();
        } else {
            $governorates = \App\Models\Governorate::with('delegations')->orderBy('id')->get();
        }

        $gouvernorats = Governorate::forSelect();
        $delegations  = Governorate::delegationsMap();

        return view('admin.special-interventions.show', [
            'intervention'   => $specialIntervention,
            'documentTypes' => $documentTypes,
            'uploadedDocs'  => $uploadedDocs,
            'governorates'  => $governorates,
            'gouvernorats'  => $gouvernorats,
            'delegations'   => $delegations,
        ]);
    }

    // =========================================================================
    // Advance to Verification
    // =========================================================================

    public function advance(SpecialIntervention $specialIntervention): RedirectResponse
    {
        $this->authorize('manage special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        if (! in_array($specialIntervention->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this request from its current status.']);
        }

        $specialIntervention->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $specialIntervention->logEvent('advanced_to_consultation');

        DossierNotifier::notify($specialIntervention, 'under_consultation', 'user', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('admin.special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.admin_advanced'));
    }

    // =========================================================================
    // Request Correction
    // =========================================================================

    public function requestCorrection(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        $this->authorize('manage special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        $validated = $request->validate([
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
            'fields_to_fix' => ['nullable', 'array'],
            'fields_to_fix.*' => ['string', 'in:cin,nom_prenom,phone,governorate_id,delegation_id,intervention_date,intervention_time,notes,interventions'],
            'docs_to_fix'   => ['nullable', 'array'],
            'docs_to_fix.*' => ['integer', 'exists:document_types,id'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (! $specialIntervention->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this request.']);
        }

        $fieldsToFix = $validated['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $validated['docs_to_fix'] ?? []);

        // Snapshot of the values the applicant will have to update
        $previousParams = [
            'cin'                  => $specialIntervention->cin,
            'nom_prenom'           => $specialIntervention->nom_prenom,
            'phone'                => $specialIntervention->phone,
            'governorate_id'       => $specialIntervention->governorate?->name_fr ?? $specialIntervention->governorate?->name_ar,
            'delegation_id'        => $specialIntervention->delegation?->name_fr ?? $specialIntervention->delegation?->name_ar,
            'intervention_date'    => $specialIntervention->intervention_date?->format('d/m/Y'),
            'intervention_time'    => $specialIntervention->intervention_time?->format('H:i'),
            'notes'                => $specialIntervention->notes,
            'interventions'        => $specialIntervention->interventions,
            'total_amount'         => $specialIntervention->total_amount,
        ];

        // Archive ONLY the documents whose type was flagged for correction
        $archivedDocsMeta = [];
        if (! empty($docsToFix)) {
            $docsToArchive = $specialIntervention->documents
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
                SpecialInterventionDocument::whereIn('id', $docsToArchive->pluck('id'))
                    ->update(['status' => 'archived']);
            }
        }

        $specialIntervention->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $specialIntervention->logEvent('correction_requested', $validated['admin_note'], [
            'fields_to_fix'  => $fieldsToFix,
            'docs_to_fix'    => $docsToFix,
            'previous'       => $previousParams,
            'archived_files' => $archivedDocsMeta,
        ]);
        $specialIntervention->refresh();

        DossierNotifier::notify($specialIntervention, 'needs_correction', 'user', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('admin.special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.admin_correction'));
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(SpecialIntervention $specialIntervention): RedirectResponse
    {
        $this->authorize('manage special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        if ($specialIntervention->status !== 'under_consultation') {
            return back()->withErrors(['status' => 'Cannot approve this request from its current status.']);
        }

        $specialIntervention->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $specialIntervention->logEvent('approved');

        DossierNotifier::notify($specialIntervention, 'approved', 'user', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('admin.special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.admin_approved'));
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, SpecialIntervention $specialIntervention): RedirectResponse
    {
        $this->authorize('manage special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        $validated = $request->validate([
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (! $specialIntervention->isActionable()) {
            return back()->withErrors(['status' => 'Cannot cancel this request from its current status.']);
        }

        $specialIntervention->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $specialIntervention->logEvent('cancelled', $validated['admin_note']);
        $specialIntervention->refresh();

        DossierNotifier::notify($specialIntervention, 'cancelled', 'user', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('admin.special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.admin_cancelled'));
    }

    // =========================================================================
    // Serve — mark the service as finished (performed)
    // =========================================================================

    public function serve(SpecialIntervention $specialIntervention): RedirectResponse
    {
        $this->authorize('manage special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($specialIntervention);

        if ($specialIntervention->status !== 'approved') {
            return back()->withErrors(['status' => 'Cannot mark as served from its current status.']);
        }

        $specialIntervention->update([
            'status'      => 'served',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $specialIntervention->logEvent('served');

        DossierNotifier::notify($specialIntervention, 'served', 'user', Service::SERVICE_SPECIAL_INTERVENTIONS);

        return redirect()->route('admin.special-interventions.show', $specialIntervention)
            ->with('success', __('special_intervention.admin_served'));
    }

    // =========================================================================
    // Download an uploaded document
    // =========================================================================

    public function downloadDocument(SpecialInterventionDocument $document)
    {
        $this->authorize('view special-interventions');
        AdminScope::authorizeService(Service::SERVICE_SPECIAL_INTERVENTIONS);
        AdminScope::authorize($document->intervention);

        abort_unless(\Storage::disk('local')->exists($document->file_path), 404);

        return \Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
