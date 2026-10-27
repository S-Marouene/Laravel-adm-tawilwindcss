<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventionDossierEvent extends Model
{
    protected $fillable = [
        'prevention_dossier_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled | appointment_scheduled | appointment_passed
    // appointment_failed | appointment_deleted
    // certificate_issued | certificate_collected | certificate_deleted

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -----------------------------------------------------------------------
    // Display helpers
    // -----------------------------------------------------------------------

    /** Icon (SVG path) shown in the timeline badge for this event type */
    public function iconPath(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'  => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            'advanced_to_consultation'  => 'M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z',
            'correction_requested'      => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            'approved', 'certificate_issued' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'cancelled', 'certificate_deleted', 'appointment_deleted' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'appointment_scheduled'     => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'appointment_passed', 'certificate_collected' => 'M5 13l4 4L19 7',
            'appointment_failed'        => 'M6 18L18 6M6 6l12 12',
            default                     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /** Human-readable label for the timeline */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'submitted'                 => 'Submitted by applicant',
            'resubmitted'               => 'Dossier resubmitted after correction',
            'advanced_to_consultation'  => 'Dossier approved — awaiting RDV',
            'correction_requested'      => 'Correction requested',
            'approved'                  => 'Dossier approved',
            'cancelled'                 => 'Dossier cancelled',
            'appointment_scheduled'     => 'RDV (visit) scheduled',
            'appointment_passed'        => 'Visit marked as passed',
            'appointment_failed'        => 'Visit marked as failed',
            'appointment_deleted'       => 'RDV removed',
            'certificate_issued'        => 'Prevention certificate issued',
            'certificate_collected'     => 'Certificate collected by applicant',
            'certificate_deleted'       => 'Prevention certificate deleted',
            default                     => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /** Tailwind dot class used by the timeline */
    public function typeDot(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'   => 'bg-teal-500',
            'advanced_to_consultation'   => 'bg-purple-500',
            'correction_requested'       => 'bg-amber-500',
            'approved'                   => 'bg-emerald-500',
            'cancelled'                  => 'bg-red-400',
            'appointment_scheduled'      => 'bg-blue-500',
            'appointment_passed'         => 'bg-emerald-500',
            'appointment_failed'         => 'bg-red-500',
            'appointment_deleted'        => 'bg-gray-400',
            'certificate_issued'         => 'bg-emerald-500',
            'certificate_collected'      => 'bg-teal-500',
            'certificate_deleted'        => 'bg-gray-400',
            default                      => 'bg-gray-400',
        };
    }

    /** Formatted RDV details (visit date / time + building address) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data) || (empty($data['visit_date']) && empty($data['reference']))) {
            return null;
        }

        // Certificate events: show reference + pickup governorate / delegation
        if (! empty($data['reference'])) {
            $text = 'Certificate: ' . $data['reference'];

            if (! empty($data['delegation'])) {
                $text .= ' · Delegation: ' . $data['delegation'];
            } elseif (! empty($data['governorate'])) {
                $text .= ' · Governorate: ' . $data['governorate'];
            }

            return $text;
        }

        $text = 'Visit: ' . Carbon::parse($data['visit_date'])->format('d M Y')
            . ' at ' . substr((string) ($data['visit_time'] ?? ''), 0, 5);

        // The visit takes place at the building — location comes from the dossier
        $address = $this->dossier?->building_address;

        if ($address) {
            $text .= ' · Location: ' . $address;
        }

        return $text;
    }
}
