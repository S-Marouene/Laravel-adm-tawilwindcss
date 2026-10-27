<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\WorksNotification;
use App\Models\WorksDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorksNotificationController extends Controller
{
    // =========================================================================
    // Index — list all non-draft notifications
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view works-notifications');

        $query = WorksNotification::with(['user', 'governorate', 'delegation'])
            ->whereNotIn('status', ['draft'])
            ->latest();

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

        return view('admin.works-notifications.index', compact('notifications'));
    }

    // =========================================================================
    // Show — full notification details
    // =========================================================================

    public function show(WorksNotification $worksNotification): View
    {
        $this->authorize('view works-notifications');

        $worksNotification->load(['user', 'governorate', 'delegation', 'reviewer', 'documents.documentType', 'events.actor']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_WORKS_NOTIFICATION)
            ->orderBy('sort_order')
            ->get();
        $uploadedDocs  = $worksNotification->documents->keyBy('document_type_id');

        return view('admin.works-notifications.show', [
            'notification'  => $worksNotification,
            'documentTypes' => $documentTypes,
            'uploadedDocs'  => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Advance to Processing (قيد الدراسة)
    // =========================================================================

    public function advance(WorksNotification $worksNotification): RedirectResponse
    {
        $this->authorize('manage works-notifications');

        if (! in_array($worksNotification->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this notification from its current status.']);
        }

        $worksNotification->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $worksNotification->logEvent('advanced_to_consultation');

        return redirect()->route('admin.works-notifications.show', $worksNotification)
            ->with('success', 'Notification moved to consultation. The applicant has been notified.');
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        $this->authorize('manage works-notifications');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $worksNotification->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this notification.']);
        }

        $worksNotification->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $worksNotification->logEvent('correction_requested', $validated['admin_note']);
        $worksNotification->refresh();

        return redirect()->route('admin.works-notifications.show', $worksNotification)
            ->with('success', 'Correction requested. The applicant will see your note and can fix and resubmit their notification.');
    }

    // =========================================================================
    // Approve — final stage: the Civil Protection is informed of the works
    // =========================================================================

    public function approve(WorksNotification $worksNotification): RedirectResponse
    {
        $this->authorize('manage works-notifications');

        if (! $worksNotification->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this notification from its current status.']);
        }

        $worksNotification->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $worksNotification->logEvent('approved');

        return redirect()->route('admin.works-notifications.show', $worksNotification)
            ->with('success', 'Notification approved. The Civil Protection is informed of the works — the applicant will be notified by phone.');
    }

    // =========================================================================
    // Cancel — rejected notifications
    // =========================================================================

    public function cancel(Request $request, WorksNotification $worksNotification): RedirectResponse
    {
        $this->authorize('manage works-notifications');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $worksNotification->isActionable()) {
            return back()->withErrors(['status' => 'Cannot cancel this notification from its current status.']);
        }

        $worksNotification->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $worksNotification->logEvent('cancelled', $validated['admin_note']);
        $worksNotification->refresh();

        return redirect()->route('admin.works-notifications.show', $worksNotification)
            ->with('success', 'Notification cancelled. The applicant has been notified.');
    }

    // =========================================================================
    // Download an uploaded document
    // =========================================================================

    public function downloadDocument(WorksDocument $worksDocument)
    {
        $this->authorize('view works-notifications');

        abort_unless(\Storage::disk('local')->exists($worksDocument->file_path), 404);

        return \Storage::disk('local')->download($worksDocument->file_path, $worksDocument->original_name);
    }
}
