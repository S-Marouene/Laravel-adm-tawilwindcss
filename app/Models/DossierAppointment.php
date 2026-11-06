<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierAppointment extends Model
{
    use HasTranslations;

    protected $fillable = [
        'prevention_dossier_id',
        'scheduled_by',
        'visit_date',
        'visit_time',
        'status',
        'note',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    /**
     * Note left by the administration (visit instructions, inspection
     * result, cancellation reason…) — bilingual {"fr": …, "ar": …}.
     */
    protected function note(): Attribute
    {
        return $this->translated('note');
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    // -----------------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------------

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isPassed(): bool     { return $this->status === 'passed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'passed'    => 'Passed',
            'failed'    => 'Failed',
            'cancelled' => 'Cancelled',
            default     => 'Pending',
        };
    }

    /** Human-readable date + time */
    public function formattedDateTime(): string
    {
        return $this->visit_date->format('d M Y') . ' at ' . substr($this->visit_time, 0, 5);
    }
}
