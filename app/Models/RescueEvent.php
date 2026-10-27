<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescueEvent extends Model
{
    protected $fillable = [
        'rescue_certificate_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled | certificate_delivered

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(RescueCertificate::class, 'rescue_certificate_id');
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
            'resubmitted'               => 'Certificate request resubmitted after correction',
            'advanced_to_consultation'  => 'Verification of rescue operation data',
            'correction_requested'      => 'Correction requested',
            'approved'                  => 'Certificate request approved',
            'cancelled'                 => 'Certificate request cancelled',
            'certificate_delivered'     => 'Certificate delivered',
            default                     => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /** Tailwind dot class used by the timeline */
    public function typeDot(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'  => 'bg-teal-500',
            'advanced_to_consultation'  => 'bg-purple-500',
            'correction_requested'      => 'bg-amber-500',
            'approved'                  => 'bg-emerald-500',
            'cancelled'                 => 'bg-red-400',
            'certificate_delivered'     => 'bg-teal-500',
            default                     => 'bg-gray-400',
        };
    }

    /** Extra details line (delivery info) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data) || $this->type !== 'certificate_delivered') {
            return null;
        }

        $parts = [];
        if (! empty($data['delivery_date'])) {
            $parts[] = 'Delivered on: ' . Carbon::parse($data['delivery_date'])->format('d M Y');
        }
        if (! empty($data['certificate_number'])) {
            $parts[] = 'Certificate No.: ' . $data['certificate_number'];
        }

        return $parts ? implode(' · ', $parts) : null;
    }
}
