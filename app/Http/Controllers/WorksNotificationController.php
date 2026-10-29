<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\WorksNotification;
use App\Models\WorksDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorksNotificationController extends Controller
{
    // =========================================================================
    // Index — list all local notifications for the authenticated user
    // =========================================================================

    public function index(Request $request): View
    {
        $drafts = WorksNotification::where('user_id', auth()->id())
            ->where('status', 'draft')
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        $submitted = WorksNotification::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'under_consultation', 'needs_correction', 'approved', 'cancelled'])
            ->with(['governorate', 'delegation'])
            ->latest()
            ->get();

        return view('works-notifications.index', [
            'drafts'    => $drafts,
            'submitted' => $submitted,
        ]);
    }

    // =========================================================================
    // Step 1 — Show form (owner + building + works details)
    // =========================================================================

    public function create(): View
    {
        return view('works-notifications.create', [
            'gouvernorats' => $this->gouvernorats(),
            'delegations'  => $this->delegations(),
        ]);
    }

    public function editStep1(WorksNotification $worksNotification): View|RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        return view('works-notifications.create', [
            'gouvernorats'     => $this->gouvernorats(),
            'delegations'      => $this->delegations(),
            'worksNotification' => $worksNotification, // pre-fills the form & unlocks "editing" mode
        ]);
    }

    public function updateStep1(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate($this->step1Rules());

        $worksNotification->update($this->step1Data($validated));

        return redirect()->route('works-notifications.step2.show', $worksNotification);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->step1Rules());

        $worksNotification = WorksNotification::create(array_merge(
            $this->step1Data($validated),
            [
                'user_id'      => auth()->id(),
                'status'       => 'draft',
                'current_step' => 1,
            ]
        ));

        return redirect()->route('works-notifications.step2.show', $worksNotification);
    }

    // =========================================================================
    // Step 2 — Show document upload form
    // =========================================================================

    public function showStep2(WorksNotification $worksNotification): View|RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        if ($worksNotification->status !== 'draft') {
            return redirect()->route('works-notifications.index')
                ->withErrors(['status' => __('works_notification.not_editable')]);
        }

        $worksNotification->load(['governorate', 'delegation']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->get();

        $uploadedDocs = WorksDocument::where('works_notification_id', $worksNotification->id)
            ->get()
            ->keyBy('document_type_id');

        return view('works-notifications.create-step2', [
            'worksNotification' => $worksNotification,
            'documentTypes'     => $documentTypes,
            'uploadedDocs'      => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Step 2 — Save uploaded documents & submit the notification
    // =========================================================================

    public function storeStep2(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        if ($worksNotification->status !== 'draft') {
            return redirect()->route('works-notifications.index')
                ->withErrors(['status' => __('works_notification.not_editable')]);
        }

        $rules = [];
        $documentTypeIds = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->pluck('id');

        foreach ($documentTypeIds as $docTypeId) {
            $rules['documents.' . $docTypeId] = ['nullable', 'file', 'max:10240']; // 10MB max
        }

        $request->validate($rules);

        $uploadDir = "works-documents/{$worksNotification->id}";

        foreach ($documentTypeIds as $docTypeId) {
            $file = $request->file("documents.{$docTypeId}");
            if ($file && $file->isValid()) {
                $path = $file->store($uploadDir, 'local');

                WorksDocument::updateOrCreate(
                    [
                        'works_notification_id' => $worksNotification->id,
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

        $worksNotification->update([
            'current_step' => 2,
            'status'       => 'completed',
        ]);

        $worksNotification->logEvent('submitted');

        return redirect()
            ->route('works-notifications.index')
            ->with('success', __('works_notification.submit_success'));
    }

    // =========================================================================
    // Resubmit — user fixes a needs_correction notification and re-submits step 2
    // =========================================================================

    public function resubmit(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $worksNotification->isNeedsCorrection()) {
            return redirect()->route('works-notifications.index')
                ->withErrors(['status' => __('works_notification.resubmit_forbidden')]);
        }

        $previousNote = $worksNotification->admin_note;

        $worksNotification->update([
            'status'       => 'draft',
            'current_step' => 2,
            'admin_note'   => null,
        ]);

        $worksNotification->logEvent('resubmitted', $previousNote ? 'In response to: ' . $previousNote : null);

        return redirect()
            ->route('works-notifications.step2.show', $worksNotification)
            ->with('info', __('works_notification.resubmit_info'));
    }

    // =========================================================================
    // Delete a local draft
    // =========================================================================

    public function destroy(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        if ($worksNotification->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $worksNotification->isDraft()) {
            return redirect()->route('works-notifications.index')
                ->withErrors(['status' => __('works_notification.delete_forbidden')]);
        }

        $worksNotification->delete();

        // Optional local override (e.g. delete from /mes-demandes)
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect)
                ->with('success', __('works_notification.delete_success'));
        }

        return redirect()->route('works-notifications.index')
            ->with('success', __('works_notification.delete_success'));
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
            'owner_type'        => ['required', 'string', 'in:' . implode(',', WorksNotification::OWNER_TYPES)],
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
        $isMoral = ($validated['owner_type'] ?? 'natural') === WorksNotification::OWNER_MORAL;

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
