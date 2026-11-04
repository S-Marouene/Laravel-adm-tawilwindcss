<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventionCertificate extends Model
{
    // Delivery methods (mirrors PreventionDossier::DELIVERY_* — digital is
    // announced but not active yet).
    public const DELIVERY_DIGITAL = 'digital';
    public const DELIVERY_PICKUP  = 'pickup';
    public const DELIVERY_POST    = 'post';

    // State:
    // ready           — waiting (at the office for pickup / being prepared for post)
    // post_sent       — handed to Poste Tunisienne (B3), tracking available
    // post_delivered  — received by the applicant
    // collected       — picked up at the office by the applicant
    public const STATUS_READY         = 'ready';
    public const STATUS_POST_SENT     = 'post_sent';
    public const STATUS_POST_DELIVERED = 'post_delivered';
    public const STATUS_COLLECTED     = 'collected';

    protected $fillable = [
        'prevention_dossier_id',
        'reference',
        'issued_by',
        'issued_at',
        'valid_until',
        'governorate_id',
        'delegation_id',
        'status',
        'delivery_method',
        'post_recipient',
        'post_phone',
        'post_address',
        'post_tracking',
        'post_sent_at',
        'post_delivered_at',
        'collected_at',
        'note',
    ];

    protected $casts = [
        'issued_at'         => 'date',
        'valid_until'       => 'date',
        'collected_at'      => 'datetime',
        'post_sent_at'      => 'datetime',
        'post_delivered_at' => 'datetime',
    ];

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

    public function isReady(): bool         { return $this->status === self::STATUS_READY; }
    public function isCollected(): bool     { return $this->status === self::STATUS_COLLECTED; }
    public function isPost(): bool          { return $this->delivery_method === self::DELIVERY_POST; }
    public function isPickup(): bool        { return $this->delivery_method === self::DELIVERY_PICKUP; }
    public function isPostSent(): bool      { return $this->status === self::STATUS_POST_SENT; }
    public function isPostDelivered(): bool { return $this->status === self::STATUS_POST_DELIVERED; }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_POST_SENT     => 'Sent by post (B3)',
            self::STATUS_POST_DELIVERED => 'Delivered by post',
            self::STATUS_COLLECTED     => 'Collected',
            default                    => 'Ready',
        };
    }

    /** The office (delegation or gouvernorat bureau) where pickup happens */
    public function pickupOffice(): ?Model
    {
        return $this->delegation ?? $this->governorate;
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
