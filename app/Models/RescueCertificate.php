<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RescueCertificate extends Model
{
    use HasTranslations;

    // -----------------------------------------------------------------------
    // Delivery method constants (mirrors PreventionCertificate)
    // -----------------------------------------------------------------------
    public const DELIVERY_PICKUP = 'pickup';
    public const DELIVERY_POST   = 'post';

    // -----------------------------------------------------------------------
    // Certificate sub-status constants
    // ready          — waiting at the office (pickup) or being prepared (post)
    // collected      — picked up at the office
    // post_sent      — handed to Poste Tunisienne (B3), tracking available
    // post_delivered — received by the applicant
    // -----------------------------------------------------------------------
    public const CERT_STATUS_READY          = 'ready';
    public const CERT_STATUS_COLLECTED      = 'collected';
    public const CERT_STATUS_POST_SENT      = 'post_sent';
    public const CERT_STATUS_POST_DELIVERED = 'post_delivered';

    protected $fillable = [
        'user_id', 'current_step', 'status',
        'cin', 'nom_prenom', 'phone',
        'victim_is_minor', 'parent_name', 'parent_cin', 'spouse_case',
        'governorate_id', 'delegation_id', 'incident_address',
        'incident_type', 'incident_date', 'incident_time', 'incident_description', 'notes',
        // Legacy flat delivery (kept for backward-compat)
        'certificate_number', 'delivery_date', 'delivery_note', 'delivered_at',
        'reference',
        'admin_note', 'reviewed_by', 'reviewed_at',
        // ── Delivery pipeline (prevention-style) ──
        'delivery_method', 'delivery_choice_at',
        'post_recipient', 'post_phone', 'post_address', 'post_tracking',
        'post_sent_at', 'post_delivered_at',
        'pickup_governorate_id', 'pickup_delegation_id',
        'cert_status', 'issued_by', 'issued_at', 'valid_until',
        'post_address_citizen',
    ];

    protected $casts = [
        'current_step'       => 'integer',
        'victim_is_minor'    => 'boolean',
        'spouse_case'        => 'boolean',
        'incident_date'      => 'date',
        'incident_time'      => 'datetime:H:i',
        'delivery_date'      => 'date',
        'delivered_at'       => 'datetime',
        'reviewed_at'        => 'datetime',
        'issued_at'          => 'date',
        'valid_until'        => 'date',
        'delivery_choice_at' => 'datetime',
        'post_sent_at'       => 'datetime',
        'post_delivered_at'  => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Bilingual fields
    // -----------------------------------------------------------------------

    protected function adminNote(): Attribute
    {
        return $this->translated('admin_note');
    }

    protected function deliveryNote(): Attribute
    {
        return $this->translated('delivery_note');
    }

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'delegation_id');
    }

    /** Pickup office governorate (may differ from incident governorate) */
    public function pickupGovernorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'pickup_governorate_id');
    }

    /** Pickup office delegation */
    public function pickupDelegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'pickup_delegation_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RescueDocument::class, 'rescue_certificate_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RescueEvent::class, 'rescue_certificate_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function logEvent(string $type, string|array|null $note = null, array $data = []): RescueEvent
    {
        return $this->events()->create([
            'user_id' => auth()->id(),
            'type'    => $type,
            'note'    => $note,
            'data'    => $data ?: null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Dossier-level status helpers
    // -----------------------------------------------------------------------

    public function isDraft(): bool             { return $this->status === 'draft'; }
    public function isCompleted(): bool         { return $this->status === 'completed'; }
    public function isUnderConsultation(): bool { return $this->status === 'under_consultation'; }
    public function isNeedsCorrection(): bool   { return $this->status === 'needs_correction'; }
    public function isApproved(): bool          { return $this->status === 'approved'; }
    public function isServed(): bool            { return $this->status === 'served'; }
    public function isCancelled(): bool         { return $this->status === 'cancelled'; }

    /** Approved but certificate not yet issued — citizen waits for certificate */
    public function isWaitingDelivery(): bool
    {
        return $this->status === 'approved' && $this->cert_status === null;
    }

    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation', 'needs_correction']);
    }

    public function isCorrectableByUser(): bool
    {
        return $this->status === 'needs_correction';
    }

    // -----------------------------------------------------------------------
    // Correction rounds (mirrors PreventionDossier)
    // -----------------------------------------------------------------------

    /** Event types that hold a full snapshot of a previous (old) version */
    public const SNAPSHOT_EVENT_TYPES = ['correction_requested', 'history_snapshot'];

    public function lastCorrectionEvent(): ?RescueEvent
    {
        return $this->events->last(
            fn ($e) => in_array($e->type, self::SNAPSHOT_EVENT_TYPES)
                || ($e->type === 'resubmitted' && is_array($e->data) && ! empty($e->data['previous']))
        );
    }

    public function correctionRoundsCount(): int
    {
        return $this->events->filter(
            fn ($e) => in_array($e->type, self::SNAPSHOT_EVENT_TYPES)
                || ($e->type === 'resubmitted' && is_array($e->data) && ! empty($e->data['previous']))
        )->count();
    }

    /** Data of the last correction round — note, flagged fields, flagged docs, retro values */
    public function lastCorrectionData(): ?array
    {
        $event = $this->lastCorrectionEvent();

        if (! $event || ! is_array($event->data)) {
            return null;
        }

        return [
            'round'          => $this->correctionRoundsCount(),
            'note'           => $event->note,
            'fields_to_fix'  => $event->data['fields_to_fix'] ?? [],
            'docs_to_fix'    => $event->data['docs_to_fix'] ?? [],
            'previous'       => $event->data['previous'] ?? null,
            'archived_files' => $event->data['archived_files'] ?? null,
        ];
    }

    /** Every correction round snapshot, oldest first */
    public function allCorrectionRounds(): array
    {
        $snapshotEvents = $this->events->filter(
            fn ($e) => in_array($e->type, self::SNAPSHOT_EVENT_TYPES)
                || ($e->type === 'resubmitted' && is_array($e->data) && ! empty($e->data['previous']))
        )->values();

        $round = 0;

        return $snapshotEvents->map(function ($event) use (&$round) {
            $round++;

            return [
                'round'          => $round,
                'date'           => $event->created_at,
                'note'           => $event->note,
                'previous'       => is_array($event->data) ? ($event->data['previous'] ?? null) : null,
                'archived_files' => is_array($event->data) ? ($event->data['archived_files'] ?? null) : null,
            ];
        })->all();
    }

    public function isDelivered(): bool
    {
        return $this->isServed() && $this->delivered_at !== null;
    }

    // -----------------------------------------------------------------------
    // Certificate sub-status helpers (mirrors PreventionCertificate)
    // -----------------------------------------------------------------------

    public function certIsReady(): bool         { return $this->cert_status === self::CERT_STATUS_READY; }
    public function certIsCollected(): bool     { return $this->cert_status === self::CERT_STATUS_COLLECTED; }
    public function certIsPostSent(): bool      { return $this->cert_status === self::CERT_STATUS_POST_SENT; }
    public function certIsPostDelivered(): bool { return $this->cert_status === self::CERT_STATUS_POST_DELIVERED; }

    public function isPickup(): bool { return $this->delivery_method === self::DELIVERY_PICKUP; }
    public function isPost(): bool   { return $this->delivery_method === self::DELIVERY_POST; }

    /** Return the pickup office (delegation or governorate fallback) */
    public function pickupOffice(): ?Model
    {
        return $this->pickupDelegation ?? $this->pickupGovernorate;
    }

    /** Has the certificate been issued (cert_status is set) */
    public function hasCertificate(): bool
    {
        return $this->cert_status !== null;
    }

    // -----------------------------------------------------------------------
    // Incident type helpers
    // -----------------------------------------------------------------------

    public const TYPES = [
        'person_rescue',
        'fire',
        'property_protection',
        'other',
    ];

    public function incidentTypeLabel(): string
    {
        $key = 'rescue_certificate.incident_type_' . $this->incident_type;

        return trans($key) === $key
            ? ucfirst(str_replace('_', ' ', (string) $this->incident_type))
            : trans($key);
    }

    // -----------------------------------------------------------------------
    // Status label helper
    // -----------------------------------------------------------------------

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'              => __('rescue_certificate.status_draft'),
            'completed'          => __('rescue_certificate.status_completed'),
            'under_consultation' => __('rescue_certificate.status_under_consultation'),
            'needs_correction'   => __('rescue_certificate.status_needs_correction'),
            'approved'           => __('rescue_certificate.status_approved'),
            'served'             => __('rescue_certificate.status_served'),
            'cancelled'          => __('rescue_certificate.status_cancelled'),
            default              => ucfirst($this->status),
        };
    }

    /** Build a unique certificate reference, e.g. SEC-2026-000123. */
    public static function makeCertificateReference(self $certificate): string
    {
        return 'SEC-' . date('Y') . '-' . str_pad((string) $certificate->id, 6, '0', STR_PAD_LEFT);
    }
}
