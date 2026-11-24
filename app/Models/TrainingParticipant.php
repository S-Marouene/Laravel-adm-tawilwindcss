<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingParticipant extends Model
{
    protected $fillable = [
        'training_request_id',
        'nom_prenom',
        'cin',
        'phone',
        'training_kind',
        'priority',
    ];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isRecycling(): bool
    {
        return $this->training_kind === TrainingRequest::KIND_RECYCLING;
    }

    /**
     * Fee for this single participant, based on the request's training type.
     */
    public function computedFee(): float
    {
        $base = TrainingRequest::basePrice($this->trainingRequest?->training_type ?? '');

        return $this->isRecycling() ? round($base * 0.75, 3) : (float) $base;
    }
}
