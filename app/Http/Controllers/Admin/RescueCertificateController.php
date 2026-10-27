<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\RescueCertificate;
use App\Models\RescueDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RescueCertificateController extends Controller
{
    // =========================================================================
    // Index — list all non-draft requests
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view rescue-certificates');

        $query = RescueCertificate::with(['user', 'governorate', 'delegation'])
            ->whereNotIn('status', ['draft'])
            ->latest();

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

        $certificates = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.rescue-certificates.index', compact('certificates'));
    }

    // =========================================================================
    // Show — full request details
    // =========================================================================

    public function show(RescueCertificate $rescueCertificate): View
    {
        $this->authorize('view rescue-certificates');

        $rescueCertificate->load(['user', 'governorate', 'delegation', 'reviewer', 'documents.documentType', 'events.actor']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
            ->orderBy('sort_order')
            ->get();
        $uploadedDocs  = $rescueCertificate->documents->keyBy('document_type_id');

        return view('admin.rescue-certificates.show', [
            'certificate'   => $rescueCertificate,
            'documentTypes' => $documentTypes,
            'uploadedDocs'  => $uploadedDocs,
        ]);
    }

    // =========================================================================
    // Advance to Verification (التأكد من المعطيات بالرجوع لعملية النجدة والإنقاذ)
    // =========================================================================

    public function advance(RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');

        if (! in_array($rescueCertificate->status, ['completed', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this request from its current status.']);
        }

        $rescueCertificate->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $rescueCertificate->logEvent('advanced_to_consultation');

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', 'Request moved to verification. The applicant has been notified.');
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $rescueCertificate->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this request.']);
        }

        $rescueCertificate->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $rescueCertificate->logEvent('correction_requested', $validated['admin_note']);
        $rescueCertificate->refresh();

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', 'Correction requested. The applicant will see your note and can fix and resubmit their request.');
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');

        if (! $rescueCertificate->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this request from its current status.']);
        }

        $rescueCertificate->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $rescueCertificate->logEvent('approved');

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', 'Request approved. The procedure is free — deliver the certificate when the applicant collects it.');
    }

    // =========================================================================
    // Cancel — rejected requests (not approved yet)
    // =========================================================================

    public function cancel(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $rescueCertificate->isActionable()) {
            return back()->withErrors(['status' => 'Cannot cancel this request from its current status.']);
        }

        $rescueCertificate->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $rescueCertificate->logEvent('cancelled', $validated['admin_note']);
        $rescueCertificate->refresh();

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', 'Request cancelled. The applicant has been notified.');
    }

    // =========================================================================
    // Deliver the certificate (تسليم الشهادة) — final stage. Free procedure.
    // =========================================================================

    public function deliver(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');

        $validated = $request->validate([
            'certificate_number' => ['required', 'string', 'max:50'],
            'delivery_date'      => ['required', 'date'],
            'delivery_note'      => ['nullable', 'string', 'max:2000'],
        ]);

        if ($rescueCertificate->status !== 'approved') {
            return back()->withErrors(['status' => 'The certificate can only be delivered for an approved request.']);
        }

        $rescueCertificate->update([
            'status'             => 'served',
            'certificate_number' => $validated['certificate_number'],
            'delivery_date'      => $validated['delivery_date'],
            'delivery_note'      => $validated['delivery_note'] ?? null,
            'delivered_at'       => now(),
            'reviewed_by'        => auth()->id(),
            'reviewed_at'        => now(),
        ]);

        $rescueCertificate->logEvent('certificate_delivered', $validated['delivery_note'] ?? null, [
            'certificate_number' => $validated['certificate_number'],
            'delivery_date'      => $validated['delivery_date'],
        ]);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', 'Certificate delivered. The applicant can now see the details.');
    }

    // =========================================================================
    // Download an uploaded document
    // =========================================================================

    public function downloadDocument(RescueDocument $rescueDocument)
    {
        $this->authorize('view rescue-certificates');

        abort_unless(\Storage::disk('local')->exists($rescueDocument->file_path), 404);

        return \Storage::disk('local')->download($rescueDocument->file_path, $rescueDocument->original_name);
    }
}
