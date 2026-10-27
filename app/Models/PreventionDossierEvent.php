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
            default                      => 'bg-gray-400',
        };
    }

    /** Formatted RDV details (visit date / time + building address) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data) || empty($data['visit_date'])) {
            return null;
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
