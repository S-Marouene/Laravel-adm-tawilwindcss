<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\PreventivePresenceRequest;
use App\Models\PresenceDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreventivePresenceController extends Controller
{
    // =========================================================================
    // Index — list all non-draft requests
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view preventive-presence');

        $query = PreventivePresenceRequest::with(['user', 'governorate', 'delegation'])
            ->whereNotIn('status', ['draft'])
            ->latest();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_prenom', 'like', "%{$search}%")
                  ->orWhere('cin', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('event_title', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.preventive-presence.index', compact('requests'));
    }

    // =========================================================================
    // Show — full request details
    // =========================================================================

    public function show(PreventivePresenceRequest $preventivePresence): View
    {
        $this->authorize('view preventive-presence');

        $preventivePresence->load(['user', 'governorate', 'delegation', 'reviewer', 'documents.documentType']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTIVE_PRESENCE)
            ->orderBy('sort_order')
            ->get();
        $uploadedDocs  = $preventivePresence->documents->keyBy('document_type_id');

        return view('admin.preventive-presence.show', [
            'requestItem'   => $preventivePresence,
            'documentTypes' => $documentTypes,
            'uploadedDocs'  => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Advance to Processing (قيد الدراسة)
    // =========================================================================

    public function advance(PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        if (! in_array($preventivePresence->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this request from its current status.']);
        }

        $preventivePresence->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', 'Request moved to processing. The applicant has been notified.');
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $preventivePresence->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this request.']);
        }

        $preventivePresence->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', 'Correction requested. The applicant will see your note and can fix and resubmit their request.');
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        if (! $preventivePresence->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this request from its current status.']);
        }

        $preventivePresence->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', 'Request approved. Record the fee receipt, then mark the service as performed.');
    }

    // =========================================================================
    // Cancel — rejected requests (not approved/served yet)
    // =========================================================================

    public function cancel(Request $request, PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $preventivePresence->isActionable()) {
            return back()->withErrors(['status' => 'Cannot cancel this request from its current status.']);
        }

        $preventivePresence->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', 'Request cancelled. The applicant has been notified.');
    }

    // =========================================================================
    // Payment — record fee + receipt (وصل) — the procedure is "بمقابل"
    // =========================================================================

    public function recordPayment(Request $request, PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        $validated = $request->validate([
            'fee_amount'     => ['required', 'numeric', 'min:0'],
            'receipt_number' => ['required', 'string', 'max:50'],
        ]);

        if (! in_array($preventivePresence->status, ['approved', 'served'])) {
            return back()->withErrors(['status' => 'Payment can only be recorded for an approved request.']);
        }

        $preventivePresence->update([
            'fee_amount'     => $validated['fee_amount'],
            'receipt_number' => $validated['receipt_number'],
            'paid_at'        => $preventivePresence->paid_at ?? now(),
        ]);

        $msg = $preventivePresence->isServed()
            ? 'Payment details updated.'
            : 'Fee receipt recorded. You can now mark the service as performed.';

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', $msg);
    }

    // =========================================================================
    // Serve the service (إسداء الخدمة) — final stage of the procedure
    // =========================================================================

    public function markServed(Request $request, PreventivePresenceRequest $preventivePresence): RedirectResponse
    {
        $this->authorize('manage preventive-presence');

        $validated = $request->validate([
            'service_date'   => ['required', 'date'],
            'service_team'   => ['nullable', 'string', 'max:255'],
            'service_agents' => ['nullable', 'integer', 'min:1', 'max:999'],
            'service_note'   => ['nullable', 'string', 'max:2000'],
        ]);

        if ($preventivePresence->status !== 'approved') {
            return back()->withErrors(['status' => 'The service can only be recorded for an approved request.']);
        }

        if (! $preventivePresence->isPaid()) {
            return back()->withErrors(['status' => 'Record the fee receipt before marking the service as performed.']);
        }

        $preventivePresence->update([
            'status'         => 'served',
            'service_date'   => $validated['service_date'],
            'service_team'   => $validated['service_team'] ?? null,
            'service_agents' => $validated['service_agents'] ?? null,
            'service_note'   => $validated['service_note'] ?? null,
            'served_at'      => now(),
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
        ]);

        return redirect()->route('admin.preventive-presence.show', $preventivePresence)
            ->with('success', 'Service recorded as performed. The applicant can now view the details.');
    }
}
