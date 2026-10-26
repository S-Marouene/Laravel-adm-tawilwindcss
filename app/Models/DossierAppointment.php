<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierAppointment extends Model
{
    protected $fillable = [
        'prevention_dossier_id',
        'scheduled_by',
        'visit_date',
        'visit_time',
        'location',
        'status',
        'note',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

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

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isPassed(): bool   { return $this->status === 'passed'; }
    public function isFailed(): bool   { return $this->status === 'failed'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'passed'  => 'Passed',
            'failed'  => 'Failed',
            default   => 'Pending',
        };
    }

    /** Human-readable date + time */
    public function formattedDateTime(): string
    {
        return $this->visit_date->format('d M Y') . ' at ' . substr($this->visit_time, 0, 5);
    }
}
