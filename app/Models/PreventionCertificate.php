<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventionCertificate extends Model
{
    protected $fillable = [
        'prevention_dossier_id',
        'reference',
        'issued_by',
        'issued_at',
        'valid_until',
        'governorate_id',
        'delegation_id',
        'status',
        'collected_at',
        'note',
    ];

    protected $casts = [
        'issued_at'    => 'date',
        'valid_until'  => 'date',
        'collected_at' => 'datetime',
    ];

    // State: ready | collected

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'delegation_id');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isReady(): bool     { return $this->status === 'ready'; }
    public function isCollected(): bool { return $this->status === 'collected'; }

    public function statusLabel(): string
    {
        return $this->isCollected() ? 'Collected' : 'Ready for pickup';
    }

    /**
     * Build a unique reference for a dossier, e.g. PC-2026-000123.
     * Since only one certificate can exist per dossier, the dossier
     * number guarantees uniqueness alongside the year.
     */
    public static function makeReference(PreventionDossier $dossier): string
    {
        return 'PC-' . date('Y') . '-' . str_pad((string) $dossier->id, 6, '0', STR_PAD_LEFT);
    }
}
