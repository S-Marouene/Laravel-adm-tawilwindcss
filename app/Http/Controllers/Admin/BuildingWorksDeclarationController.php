<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\BuildingWorksDeclaration;
use App\Models\BuildingWorksDocument;
use App\Models\Service;
use App\Services\DossierNotifier;
use App\Support\AdminScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingWorksDeclarationController extends Controller
{
    // =========================================================================
    // Index — list all non-draft notifications
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);

        $query = BuildingWorksDeclaration::with(['user', 'governorate', 'delegation'])
            ->whereNotIn('status', ['draft'])
            ->latest();

        AdminScope::apply($query);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('owner_cin', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('property_address', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $notifications = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.building-works-declarations.index', compact('notifications'));
    }

    // =========================================================================
    // Show — full notification details
    // =========================================================================

    public function show(BuildingWorksDeclaration $buildingWorksDeclaration): View
    {
        $this->authorize('view building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDeclaration);

        $buildingWorksDeclaration->load(['user', 'governorate', 'delegation', 'reviewer', 'documents.documentType', 'events.actor']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->get();

        $activeDocuments = $buildingWorksDeclaration->documents->filter(fn ($d) => $d->status !== 'archived');
        $documentsByType = $activeDocuments->groupBy('document_type_id');
        $uploadedDocs    = $buildingWorksDeclaration->documents->keyBy('document_type_id');
        $archivedDocs    = $buildingWorksDeclaration->documents->filter(fn ($d) => $d->status === 'archived');

        return view('admin.building-works-declarations.show', [
            'notification'    => $buildingWorksDeclaration,
            'documentTypes'   => $documentTypes,
            'uploadedDocs'    => $uploadedDocs,
            'documentsByType' => $documentsByType,
            'archivedDocs'    => $archivedDocs,
        ]);
    }

    // =========================================================================
    // Advance to Processing (قيد الدراسة)
    // =========================================================================

    public function advance(BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        $this->authorize('manage building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDeclaration);

        if (! in_array($buildingWorksDeclaration->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this notification from its current status.']);
        }

        $buildingWorksDeclaration->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $buildingWorksDeclaration->logEvent('advanced_to_consultation');

        DossierNotifier::notify($buildingWorksDeclaration, 'under_consultation', 'user', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()->route('admin.building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', 'Notification moved to consultation. The applicant has been notified.');
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        $this->authorize('manage building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDeclaration);

        $validated = $request->validate([
            // Bilingual note — both languages are required (auto-translated
            // in real time while the admin types).
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
            'fields_to_fix' => ['nullable', 'array'],
            'fields_to_fix.*' => ['string', 'in:owner_cin,owner_name,company_name,jort_reference,phone,governorate_id,delegation_id,property_address,works_type,works_start_date,works_description,notes'],
            'docs_to_fix'   => ['nullable', 'array'],
            'docs_to_fix.*' => ['integer', 'exists:document_types,id'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (! $buildingWorksDeclaration->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this notification.']);
        }

        $fieldsToFix = $validated['fields_to_fix'] ?? [];
        $docsToFix   = array_map('intval', $validated['docs_to_fix'] ?? []);

        // ── Snapshot of the values the applicant will have to update ──
        $previousParams = [
            'owner_cin'         => $buildingWorksDeclaration->owner_cin,
            'owner_name'        => $buildingWorksDeclaration->isMoral() ? ($buildingWorksDeclaration->company_name ?? null) : ($buildingWorksDeclaration->owner_name ?? null),
            'company_name'      => $buildingWorksDeclaration->company_name,
            'jort_reference'    => $buildingWorksDeclaration->jort_reference,
            'phone'             => $buildingWorksDeclaration->phone,
            'governorate_id'    => $buildingWorksDeclaration->governorate?->name_fr ?? $buildingWorksDeclaration->governorate?->name_ar,
            'delegation_id'     => $buildingWorksDeclaration->delegation?->name_fr ?? $buildingWorksDeclaration->delegation?->name_ar,
            'property_address'  => $buildingWorksDeclaration->property_address,
            'works_type'        => $buildingWorksDeclaration->works_type,
            'works_start_date'  => $buildingWorksDeclaration->works_start_date?->format('d/m/Y'),
            'works_description' => $buildingWorksDeclaration->works_description,
            'notes'             => $buildingWorksDeclaration->notes,
        ];

        // ── Archive ONLY the documents whose type was flagged for correction ──
        $archivedDocsMeta = [];
        if (! empty($docsToFix)) {
            $docsToArchive = $buildingWorksDeclaration->documents
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
                BuildingWorksDocument::whereIn('id', $docsToArchive->pluck('id'))
                    ->update(['status' => 'archived']);
            }
        }

        $buildingWorksDeclaration->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $buildingWorksDeclaration->logEvent('correction_requested', $validated['admin_note'], [
            'fields_to_fix'  => $fieldsToFix,
            'docs_to_fix'    => $docsToFix,
            'previous'       => $previousParams,
            'archived_files' => $archivedDocsMeta,
        ]);
        $buildingWorksDeclaration->refresh();

        DossierNotifier::notify($buildingWorksDeclaration, 'needs_correction', 'user', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()->route('admin.building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', 'Correction requested. The applicant will see your note and can fix and resubmit their notification.');
    }

    // =========================================================================
    // Approve — final stage: the Civil Protection is informed of the works
    // =========================================================================

    public function approve(BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        $this->authorize('manage building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDeclaration);

        if (! $buildingWorksDeclaration->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this notification from its current status.']);
        }

        $buildingWorksDeclaration->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $buildingWorksDeclaration->logEvent('approved');

        DossierNotifier::notify($buildingWorksDeclaration, 'approved', 'user', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()->route('admin.building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', 'Notification approved. The Civil Protection is informed of the works — the applicant will be notified by phone.');
    }

    // =========================================================================
    // Cancel — rejected notifications
    // =========================================================================

    public function cancel(Request $request, BuildingWorksDeclaration $buildingWorksDeclaration): RedirectResponse
    {
        $this->authorize('manage building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDeclaration);

        $validated = $request->validate([
            // Bilingual cancellation reason — both languages are required.
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
        ]);

        if (! $buildingWorksDeclaration->isActionable()) {
            return back()->withErrors(['status' => 'Cannot cancel this notification from its current status.']);
        }

        $buildingWorksDeclaration->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $buildingWorksDeclaration->logEvent('cancelled', $validated['admin_note']);
        $buildingWorksDeclaration->refresh();

        DossierNotifier::notify($buildingWorksDeclaration, 'cancelled', 'user', Service::SERVICE_WORKS_NOTIFICATION);

        return redirect()->route('admin.building-works-declarations.show', $buildingWorksDeclaration)
            ->with('success', 'Notification cancelled. The applicant has been notified.');
    }

    // =========================================================================
    // Download an uploaded document
    // =========================================================================

    public function downloadDocument(BuildingWorksDocument $buildingWorksDocument)
    {
        $this->authorize('view building-works-declarations');
        AdminScope::authorizeService(Service::SERVICE_WORKS_NOTIFICATION);
        AdminScope::authorize($buildingWorksDocument->notification);

        abort_unless(\Storage::disk('local')->exists($buildingWorksDocument->file_path), 404);

        return \Storage::disk('local')->download($buildingWorksDocument->file_path, $buildingWorksDocument->original_name);
    }
}
