<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorksEvent extends Model
{
    protected $fillable = [
        'works_notification_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function notification(): BelongsTo
    {
        return $this->belongsTo(WorksNotification::class, 'works_notification_id');
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
            'resubmitted'               => 'Notification resubmitted after correction',
            'advanced_to_consultation'  => 'Advanced to consultation',
            'correction_requested'      => 'Correction requested',
            'approved'                  => 'Notification approved — Civil Protection informed of the works',
            'cancelled'                 => 'Notification cancelled',
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
            default                     => 'bg-gray-400',
        };
    }
}
