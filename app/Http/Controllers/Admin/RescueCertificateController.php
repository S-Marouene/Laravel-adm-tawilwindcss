<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delegation;
use App\Models\DocumentType;
use App\Models\Governorate;
use App\Models\RescueCertificate;
use App\Models\RescueDocument;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\DossierNotifier;
use App\Support\AdminScope;
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
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);

        $query = RescueCertificate::with(['user', 'governorate', 'delegation'])
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

        $certificates = $query->paginate(20)->appends($request->only('q', 'status'));

        return view('admin.rescue-certificates.index', compact('certificates'));
    }

    // =========================================================================
    // Show — full request details
    // =========================================================================

    public function show(RescueCertificate $rescueCertificate): View
    {
        $this->authorize('view rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        $rescueCertificate->load([
            'user', 'governorate', 'delegation',
            'pickupGovernorate', 'pickupDelegation',
            'reviewer', 'issuer',
            'documents.documentType', 'events.actor',
        ]);

        $documentTypes = DocumentType::service(DocumentType::SERVICE_RESCUE_CERTIFICATE)
            ->orderBy('sort_order')
            ->get();
        $uploadedDocs  = $rescueCertificate->documents->keyBy('document_type_id');

        // Full governorate collection with delegations — used by the "issue certificate" form
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

        return view('admin.rescue-certificates.show', [
            'certificate'   => $rescueCertificate,
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

    public function advance(RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

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

        DossierNotifier::notify($rescueCertificate, 'under_consultation', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_advanced'));
    }

    // =========================================================================
    // Request Correction
    // =========================================================================

    public function requestCorrection(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        $validated = $request->validate([
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
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

        DossierNotifier::notify($rescueCertificate, 'needs_correction', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_correction'));
    }

    // =========================================================================
    // Approve
    // =========================================================================

    public function approve(RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        if ($rescueCertificate->status !== 'under_consultation') {
            return back()->withErrors(['status' => 'Cannot approve this request from its current status.']);
        }

        $rescueCertificate->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $rescueCertificate->logEvent('approved');

        DossierNotifier::notify($rescueCertificate, 'approved', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_approved'));
    }

    // =========================================================================
    // Cancel
    // =========================================================================

    public function cancel(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        $validated = $request->validate([
            'admin_note.fr' => ['required', 'string', 'max:2000'],
            'admin_note.ar' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note.fr' => 'Note (Français)',
            'admin_note.ar' => 'Note (العربية)',
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

        DossierNotifier::notify($rescueCertificate, 'cancelled', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_cancelled'));
    }

    // =========================================================================
    // Issue Certificate — prevention-style (sets cert_status = 'ready')
    // =========================================================================

    public function issueCertificate(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        if ($rescueCertificate->isCancelled()) {
            return back()->withErrors(['status' => 'Cannot issue a certificate for a cancelled request.']);
        }

        if (! in_array($rescueCertificate->status, ['approved', 'under_consultation'])) {
            return back()->withErrors(['status' => 'A certificate can only be issued for an approved or verified request.']);
        }

        if ($rescueCertificate->hasCertificate()) {
            return back()->withErrors(['status' => 'This request already has an issued certificate.']);
        }

        $validated = $request->validate([
            'delivery_method'    => ['required', 'in:' . RescueCertificate::DELIVERY_PICKUP . ',' . RescueCertificate::DELIVERY_POST],
            'pickup_governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'pickup_delegation_id'  => ['nullable', 'integer', 'exists:delegations,id'],
            'post_recipient'     => ['required_if:delivery_method,post', 'nullable', 'string', 'max:255'],
            'post_phone'         => ['nullable', 'string', 'max:40'],
            'post_address'       => ['required_if:delivery_method,post', 'nullable', 'string', 'max:500'],
            'post_tracking'      => ['nullable', 'string', 'max:60'],
            'valid_until'        => ['nullable', 'date', 'after_or_equal:today'],
            'certificate_number' => ['nullable', 'string', 'max:50'],
            'note.fr'            => ['nullable', 'string', 'max:2000'],
            'note.ar'            => ['nullable', 'string', 'max:2000'],
        ]);

        $deliveryMethod = $validated['delivery_method'];

        if ($deliveryMethod === RescueCertificate::DELIVERY_POST) {
            // Pre-fill from applicant data if not provided
            $validated['post_recipient'] ??= $rescueCertificate->nom_prenom;
            $validated['post_phone']     ??= $rescueCertificate->phone;
            $validated['post_address']   ??= $rescueCertificate->post_address_citizen;

            if (empty($validated['post_address'])) {
                return back()->withErrors(['post_address' => 'A postal address is required for the Poste Tunisienne (B3) delivery.']);
            }
        } else {
            if (empty($validated['pickup_governorate_id'])) {
                return back()->withErrors(['pickup_governorate_id' => 'A pickup governorate is required.']);
            }

            // Scoped managers can only assign a pickup location inside their territory
            $user = auth()->user();
            if ($user->adminScope() === 'manager') {
                $ok = AdminScope::canAccess((object) [
                    'governorate_id' => $validated['pickup_governorate_id'],
                    'delegation_id'  => $validated['pickup_delegation_id'] ?? null,
                ], $user);
                if (! $ok) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pickup_governorate_id' => 'The pickup location must be inside your assigned territory.',
                    ]);
                }
            }

            $govForLog = Governorate::find($validated['pickup_governorate_id']);
            $delForLog = !empty($validated['pickup_delegation_id']) ? Delegation::find($validated['pickup_delegation_id']) : null;

            if ($delForLog && (int) $delForLog->governorate_id !== (int) $govForLog->id) {
                return back()->withErrors(['pickup_delegation_id' => 'The selected delegation does not belong to the selected governorate.']);
            }
        }

        $certRef = RescueCertificate::makeCertificateReference($rescueCertificate);

        // Auto-approve if still under_consultation (streamlined workflow)
        if ($rescueCertificate->status === 'under_consultation') {
            $rescueCertificate->update([
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            $rescueCertificate->logEvent('approved');
        }

        $rescueCertificate->update([
            'cert_status'            => RescueCertificate::CERT_STATUS_READY,
            'issued_by'              => auth()->id(),
            'issued_at'              => now()->toDateString(),
            'valid_until'            => $validated['valid_until'] ?? null,
            'delivery_method'        => $deliveryMethod,
            'certificate_number'     => $validated['certificate_number'] ?? $certRef,
            // Pickup fields
            'pickup_governorate_id'  => $validated['pickup_governorate_id'] ?? null,
            'pickup_delegation_id'   => $validated['pickup_delegation_id'] ?? null,
            // Post fields
            'post_recipient'         => $validated['post_recipient'] ?? null,
            'post_phone'             => $validated['post_phone'] ?? null,
            'post_address'           => $validated['post_address'] ?? null,
            'post_tracking'          => $validated['post_tracking'] ?? null,
            'delivery_note'          => $validated['note'] ?? null,
        ]);

        $rescueCertificate->logEvent('certificate_issued', $validated['note'] ?? null, [
            'certificate_number' => $rescueCertificate->certificate_number,
            'method'             => $deliveryMethod,
            'governorate'        => ($govForLog ?? null)?->name_fr ?? ($govForLog ?? null)?->name_ar,
            'delegation'         => ($delForLog ?? null)?->name_fr ?? ($delForLog ?? null)?->name_ar,
        ]);

        DossierNotifier::notify($rescueCertificate, 'certificate_issued', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_cert_issued'));
    }

    // =========================================================================
    // Update Certificate state (pickup: ready ↔ collected, post: ready→sent→delivered)
    // =========================================================================

    public function updateCertificate(Request $request, RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        $validated = $request->validate([
            'action'        => ['required', 'in:collected,ready,sent,delivered'],
            'post_tracking' => ['nullable', 'string', 'max:60'],
        ]);

        if (! $rescueCertificate->hasCertificate()) {
            return back()->withErrors(['status' => 'This request has no issued certificate to update.']);
        }

        // Illegal state moves
        if ($rescueCertificate->isPost() && $validated['action'] === 'collected') {
            return back()->withErrors(['action' => 'This certificate is sent by post — use sent/delivered actions.']);
        }
        if ($rescueCertificate->isPickup() && in_array($validated['action'], ['sent', 'delivered'])) {
            return back()->withErrors(['action' => 'This certificate is collected at the office — use the collected action.']);
        }

        switch ($validated['action']) {
            case 'collected':
                $rescueCertificate->update([
                    'cert_status'  => RescueCertificate::CERT_STATUS_COLLECTED,
                    'delivered_at' => now(),
                    'status'       => 'served',
                ]);
                $rescueCertificate->logEvent('certificate_collected', null, [
                    'certificate_number' => $rescueCertificate->certificate_number,
                    'governorate'        => $rescueCertificate->pickupGovernorate?->name_fr ?? $rescueCertificate->pickupGovernorate?->name_ar,
                    'delegation'         => $rescueCertificate->pickupDelegation?->name_fr ?? $rescueCertificate->pickupDelegation?->name_ar,
                ]);
                DossierNotifier::notify($rescueCertificate, 'certificate_delivered', 'user', Service::SERVICE_RESCUE_CERTIFICATE);
                break;

            case 'sent':
                $tracking = $validated['post_tracking'] ?? null;
                if ($tracking) {
                    $rescueCertificate->update(['post_tracking' => $tracking]);
                    $rescueCertificate->refresh();
                }
                if (empty($rescueCertificate->post_tracking)) {
                    return back()->withErrors(['post_tracking' => 'A tracking number is required to mark the certificate as sent.']);
                }
                $rescueCertificate->update([
                    'cert_status'  => RescueCertificate::CERT_STATUS_POST_SENT,
                    'post_sent_at' => now(),
                ]);
                $rescueCertificate->logEvent('certificate_post_sent', null, [
                    'certificate_number' => $rescueCertificate->certificate_number,
                    'tracking'           => $rescueCertificate->post_tracking,
                ]);
                DossierNotifier::notify($rescueCertificate, 'certificate_post_sent', 'user', Service::SERVICE_RESCUE_CERTIFICATE);
                break;

            case 'delivered':
                $rescueCertificate->update([
                    'cert_status'       => RescueCertificate::CERT_STATUS_POST_DELIVERED,
                    'post_delivered_at' => now(),
                    'delivered_at'      => now(),
                    'status'            => 'served',
                ]);
                $rescueCertificate->logEvent('certificate_post_delivered', null, [
                    'certificate_number' => $rescueCertificate->certificate_number,
                    'tracking'           => $rescueCertificate->post_tracking,
                ]);
                DossierNotifier::notify($rescueCertificate, 'certificate_delivered', 'user', Service::SERVICE_RESCUE_CERTIFICATE);
                break;

            case 'ready':
            default:
                $rescueCertificate->update([
                    'cert_status'       => RescueCertificate::CERT_STATUS_READY,
                    'delivered_at'      => null,
                    'post_sent_at'      => null,
                    'post_delivered_at' => null,
                    'post_tracking'     => null,
                    // Revert served back to approved if resetting
                    'status'            => 'approved',
                ]);
                $rescueCertificate->logEvent('certificate_reset', null, [
                    'certificate_number' => $rescueCertificate->certificate_number,
                ]);
                break;
        }

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_cert_updated'));
    }

    // =========================================================================
    // Revoke Certificate — not possible once collected/post_delivered
    // =========================================================================

    public function revokeCertificate(RescueCertificate $rescueCertificate): RedirectResponse
    {
        $this->authorize('manage rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueCertificate);

        if (! $rescueCertificate->hasCertificate()) {
            return back()->withErrors(['status' => 'This request has no certificate to revoke.']);
        }

        if ($rescueCertificate->certIsCollected() || $rescueCertificate->certIsPostDelivered()) {
            return back()->withErrors(['status' => 'A delivered certificate cannot be revoked.']);
        }

        $certNum = $rescueCertificate->certificate_number;

        $rescueCertificate->update([
            'cert_status'           => null,
            'issued_by'             => null,
            'issued_at'             => null,
            'valid_until'           => null,
            'delivery_method'       => null,
            'certificate_number'    => null,
            'pickup_governorate_id' => null,
            'pickup_delegation_id'  => null,
            'post_recipient'        => null,
            'post_phone'            => null,
            'post_address'          => null,
            'post_tracking'         => null,
            'post_sent_at'          => null,
            'post_delivered_at'     => null,
            'delivered_at'          => null,
            'delivery_note'         => null,
            'status'                => 'approved',  // revert to approved for re-issue
        ]);

        $rescueCertificate->logEvent('certificate_deleted', null, [
            'certificate_number' => $certNum,
        ]);

        DossierNotifier::notify($rescueCertificate, 'certificate_revoked', 'user', Service::SERVICE_RESCUE_CERTIFICATE);

        return redirect()->route('admin.rescue-certificates.show', $rescueCertificate)
            ->with('success', __('rescue_certificate.admin_cert_revoked'));
    }

    // =========================================================================
    // Download an uploaded document
    // =========================================================================

    public function downloadDocument(RescueDocument $rescueDocument)
    {
        $this->authorize('view rescue-certificates');
        AdminScope::authorizeService(Service::SERVICE_RESCUE_CERTIFICATE);
        AdminScope::authorize($rescueDocument->certificate);

        abort_unless(\Storage::disk('local')->exists($rescueDocument->file_path), 404);

        return \Storage::disk('local')->download($rescueDocument->file_path, $rescueDocument->original_name);
    }
}
