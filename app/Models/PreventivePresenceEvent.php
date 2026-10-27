<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventivePresenceEvent extends Model
{
    protected $fillable = [
        'preventive_presence_request_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled | payment_recorded | service_performed

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function request(): BelongsTo
    {
        return $this->belongsTo(PreventivePresenceRequest::class, 'preventive_presence_request_id');
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
            'resubmitted'               => 'Request resubmitted after correction',
            'advanced_to_consultation'  => 'Advanced to processing',
            'correction_requested'      => 'Correction requested',
            'approved'                  => 'Request approved',
            'cancelled'                 => 'Request cancelled',
            'payment_recorded'          => 'Fee receipt recorded',
            'service_performed'         => 'Service performed',
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
            'payment_recorded'          => 'bg-yellow-500',
            'service_performed'         => 'bg-teal-500',
            default                     => 'bg-gray-400',
        };
    }

    /** Extra details line (payment info, actor role...) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data) || empty($data)) {
            return null;
        }

        if ($this->type === 'payment_recorded') {
            $text = 'Receipt: ' . ($data['receipt_number'] ?? '—');
            if (isset($data['fee_amount'])) {
                $text .= ' · Amount: ' . number_format((float) $data['fee_amount'], 3) . ' DT';
            }

            return $text;
        }

        if ($this->type === 'service_performed') {
            $parts = [];
            if (! empty($data['service_date'])) {
                $parts[] = 'Date: ' . \Carbon\Carbon::parse($data['service_date'])->format('d M Y');
            }
            if (! empty($data['service_team'])) {
                $parts[] = 'Team: ' . $data['service_team'];
            }
            if (! empty($data['service_agents'])) {
                $parts[] = 'Agents: ' . $data['service_agents'];
            }

            return $parts ? implode(' · ', $parts) : null;
        }

        return null;
    }
}
