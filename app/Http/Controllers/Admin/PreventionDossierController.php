<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DossierAppointment;
use App\Models\DossierDocument;
use App\Models\DocumentType;
use App\Models\PreventionDossier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreventionDossierController extends Controller
{
    // =========================================================================
    // Index — list all non-draft dossiers
    // =========================================================================

    public function index(Request $request): View
    {
        $this->authorize('view prevention-dossiers');

        $query = PreventionDossier::with(['user', 'governorate', 'delegation', 'buildingType'])
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

        $dossiers = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.prevention-dossiers.index', compact('dossiers'));
    }

    // =========================================================================
    // Show — full dossier details
    // =========================================================================

    public function show(PreventionDossier $preventionDossier): View
    {
        $this->authorize('view prevention-dossiers');

        $preventionDossier->load(['user', 'governorate', 'delegation', 'buildingType', 'buildingActivity', 'reviewer', 'appointments.scheduler', 'events.actor', 'events.dossier:id,building_address']);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_PREVENTION)->orderBy('sort_order')->get();
        $uploadedDocs  = DossierDocument::where('prevention_dossier_id', $preventionDossier->id)
            ->get()
            ->keyBy('document_type_id');

        return view('admin.prevention-dossiers.show', compact('preventionDossier', 'documentTypes', 'uploadedDocs'));
    }

    // =========================================================================
    // Advance to Consultation
    // =========================================================================

    public function advanceToConsultation(PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        if (! in_array($preventionDossier->status, ['completed', 'submitted', 'needs_correction'])) {
            return back()->withErrors(['status' => 'Cannot advance this dossier from its current status.']);
        }

        $preventionDossier->update([
            'status'      => 'under_consultation',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('advanced_to_consultation');

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Dossier approved — awaiting RDV. You can now schedule a visit (RDV) from the sidebar.');
    }

    // =========================================================================
    // Request Correction — flag issues and send note to user
    // =========================================================================

    public function requestCorrection(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (! $preventionDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot request correction for this dossier.']);
        }

        $preventionDossier->update([
            'status'      => 'needs_correction',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('correction_requested', $validated['admin_note']);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Correction requested. The applicant will see your note and can fix and resubmit their dossier.');
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        if (! $preventionDossier->isActionable()) {
            return back()->withErrors(['status' => 'Cannot approve this dossier from its current status.']);
        }

        $preventionDossier->update([
            'status'      => 'approved',
            'admin_note'  => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('approved');

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Dossier approved successfully.');
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);

        if (in_array($preventionDossier->status, ['approved', 'cancelled'])) {
            return back()->withErrors(['status' => 'Cannot cancel a dossier that is already approved or cancelled.']);
        }

        $preventionDossier->update([
            'status'      => 'cancelled',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $preventionDossier->logEvent('cancelled', $validated['admin_note']);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Dossier cancelled. The applicant has been notified.');
    }

    // =========================================================================
    // Appointments — Schedule a new visit (RDV)
    // =========================================================================

    public function scheduleAppointment(Request $request, PreventionDossier $preventionDossier): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        $validated = $request->validate([
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'visit_time' => ['required', 'date_format:H:i'],
        ]);

        // No location here: the visit takes place at the building whose
        // address is stored on the dossier itself.
        DossierAppointment::create([
            'prevention_dossier_id' => $preventionDossier->id,
            'scheduled_by'          => auth()->id(),
            'visit_date'            => $validated['visit_date'],
            'visit_time'            => $validated['visit_time'],
            'status'                => 'pending',
        ]);

        $preventionDossier->logEvent('appointment_scheduled', null, [
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
        ]);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Rendez-vous scheduled successfully. The applicant can see the appointment.');
    }

    // =========================================================================
    // Appointments — Update RDV result (passed or failed + note)
    // =========================================================================

    public function updateAppointment(Request $request, PreventionDossier $preventionDossier, DossierAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        if ($appointment->prevention_dossier_id !== $preventionDossier->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:passed,failed'],
            'note'   => ['nullable', 'string', 'max:2000'],
        ]);

        // note is required when failed
        if ($validated['status'] === 'failed' && empty($validated['note'])) {
            return back()->withErrors(['note' => 'A note is required when marking a visit as failed.']);
        }

        $appointment->update([
            'status' => $validated['status'],
            'note'   => $validated['note'] ?? null,
        ]);

        $preventionDossier->logEvent(
            $validated['status'] === 'passed' ? 'appointment_passed' : 'appointment_failed',
            $validated['note'] ?? null,
            [
                'visit_date' => $appointment->visit_date,
                'visit_time' => $appointment->visit_time,
            ]
        );

        $msg = $validated['status'] === 'passed'
            ? 'Visit marked as passed. The applicant has been notified.'
            : 'Visit marked as failed with notes. The applicant has been notified.';

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', $msg);
    }

    // =========================================================================
    // Appointments — Delete a pending RDV
    // =========================================================================

    public function deleteAppointment(PreventionDossier $preventionDossier, DossierAppointment $appointment): RedirectResponse
    {
        $this->authorize('manage prevention-dossiers');

        if ($appointment->prevention_dossier_id !== $preventionDossier->id) {
            abort(403);
        }

        if (! $appointment->isPending()) {
            return back()->withErrors(['status' => 'Only pending appointments can be deleted.']);
        }

        // Capture details before deletion so the timeline keeps a record
        $rdvDetails = [
            'visit_date' => $appointment->visit_date,
            'visit_time' => $appointment->visit_time,
        ];

        $appointment->delete();

        $preventionDossier->logEvent('appointment_deleted', null, $rdvDetails);

        return redirect()->route('admin.prevention-dossiers.show', $preventionDossier)
            ->with('success', 'Appointment removed.');
    }
}
