<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialInterventionEvent extends Model
{
    use HasTranslations;

    protected $fillable = [
        'special_intervention_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Bilingual note attached to this event — stored as {"fr": …, "ar": …},
     * reading returns the localized string.
     */
    protected function note(): Attribute
    {
        return $this->translated('note');
    }

    // Possible types:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled | served

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(SpecialIntervention::class, 'special_intervention_id');
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
        return __('special_intervention.tl_' . $this->type);
    }

    /** Tailwind dot class used by the timeline */
    public function typeDot(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'  => 'bg-teal-500',
            'advanced_to_consultation'  => 'bg-purple-500',
            'correction_requested'      => 'bg-amber-500',
            'approved'                  => 'bg-emerald-500',
            'served'                    => 'bg-teal-500',
            'cancelled'                 => 'bg-red-400',
            default                     => 'bg-gray-400',
        };
    }

    /** Extra details line (payment info) when present */
    public function detailsText(): ?string
    {
        $data = $this->data;

        if (! is_array($data)) {
            return null;
        }

        $parts = [];
        if (! empty($data['total_amount'])) {
            $parts[] = __('special_intervention.total_paid_label', [
                'amount' => number_format($data['total_amount'], 0),
            ]);
        }

        return $parts ? implode(' · ', $parts) : null;
    }
}
