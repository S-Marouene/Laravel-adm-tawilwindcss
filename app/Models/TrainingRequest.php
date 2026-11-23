<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingRequest extends Model
{
    use HasTranslations;

    // -----------------------------------------------------------------------
    // Training type catalogue  (جدول التعريفات)
    // key => ['price_new' => TND, 'price_recycling' => TND*0.75, 'duration_weeks' => int]
    // Recycling fee = -25 %
    // -----------------------------------------------------------------------
    public const TYPES = [
        'taxi_drivers'           => ['price' => 100,  'weeks' => 1],
        'safety_teams'           => ['price' => 300,  'weeks' => 1],
        'first_aid'              => ['price' => 240,  'weeks' => 1],
        'lifeguards'             => ['price' => 400,  'weeks' => 1],
        'prevention_level1'      => ['price' => 600,  'weeks' => 2],
        'prevention_level2'      => ['price' => 600,  'weeks' => 3],
        'ship_fires'             => ['price' => 800,  'weeks' => 1],
        'fuel_fires'             => ['price' => 1200, 'weeks' => 1],
        'first_aid_instructors'  => ['price' => 600,  'weeks' => 1],
    ];

    public const KIND_NEW       = 'new';
    public const KIND_RECYCLING = 'recycling';

    // -----------------------------------------------------------------------
    // Status constants
    // -----------------------------------------------------------------------
    public const STATUS_DRAFT              = 'draft';
    public const STATUS_COMPLETED          = 'completed';          // submitted by citizen
    public const STATUS_UNDER_CONSULTATION = 'under_consultation'; // admin reviewing
    public const STATUS_NEEDS_CORRECTION   = 'needs_correction';
    public const STATUS_PAYMENT_PENDING    = 'payment_pending';    // approved, awaiting fee
    public const STATUS_PAYMENT_CONFIRMED  = 'payment_confirmed';  // fee receipt recorded
    public const STATUS_SCHEDULED          = 'scheduled';          // date/time set
    public const STATUS_IN_PROGRESS        = 'in_progress';        // formation started
    public const STATUS_PASSED             = 'passed';             // exam passed
    public const STATUS_FAILED             = 'failed';             // exam failed
    public const STATUS_SERVED             = 'served';             // certificate delivered
    public const STATUS_CANCELLED          = 'cancelled';

    // Certificate sub-status (mirrors RescueCertificate)
    public const CERT_STATUS_READY          = 'ready';
    public const CERT_STATUS_COLLECTED      = 'collected';
    public const CERT_STATUS_POST_SENT      = 'post_sent';
    public const CERT_STATUS_POST_DELIVERED = 'post_delivered';

    // Delivery method
    public const DELIVERY_PICKUP = 'pickup';
    public const DELIVERY_POST   = 'post';

    // -----------------------------------------------------------------------
    // Fillable / casts
    // -----------------------------------------------------------------------

    protected $fillable = [
        'user_id', 'current_step', 'status', 'reference',
        'cin', 'nom_prenom', 'phone',
        'governorate_id', 'delegation_id',
        'training_type', 'training_kind', 'notes',
        // Payment
        'fee_amount', 'receipt_number', 'paid_at',
        'payment_method', 'payment_online_ref',
        // Schedule
        'training_start_date', 'training_start_time', 'training_duration_days',
        // Result
        'result', 'result_note', 'result_at', 'result_by',
        // Certificate delivery
        'certificate_number', 'delivery_method', 'delivery_choice_at',
        'post_recipient', 'post_phone', 'post_address',
        'post_tracking', 'post_sent_at', 'post_delivered_at',
        'pickup_governorate_id', 'pickup_delegation_id',
        'cert_status', 'issued_by', 'issued_at', 'valid_until', 'delivered_at',
        // Admin workflow
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'current_step'           => 'integer',
        'training_duration_days' => 'integer',
        'fee_amount'             => 'decimal:3',
        'paid_at'                => 'datetime',
        'training_start_date'    => 'date',
        'training_start_time'    => 'datetime:H:i',
        'result_at'              => 'datetime',
        'delivery_choice_at'     => 'datetime',
        'post_sent_at'           => 'datetime',
        'post_delivered_at'      => 'datetime',
        'issued_at'              => 'date',
        'valid_until'            => 'date',
        'delivered_at'           => 'datetime',
        'reviewed_at'            => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Bilingual fields
    // -----------------------------------------------------------------------

    protected function adminNote(): Attribute  { return $this->translated('admin_note'); }
    protected function resultNote(): Attribute { return $this->translated('result_note'); }

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

    public function resultReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_by');
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

    public function pickupGovernorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'pickup_governorate_id');
    }

    public function pickupDelegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'pickup_delegation_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TrainingDocument::class, 'training_request_id')
            ->orderBy('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrainingEvent::class, 'training_request_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'training_request_id')
            ->orderBy('session_date');
    }

    // -----------------------------------------------------------------------
    // Event log helper
    // -----------------------------------------------------------------------

    public function logEvent(string $type, string|array|null $note = null, array $data = []): TrainingEvent
    {
        return $this->events()->create([
            'user_id' => auth()->id(),
            'type'    => $type,
            'note'    => $note,
            'data'    => $data ?: null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------------

    public function isDraft(): bool              { return $this->status === self::STATUS_DRAFT; }
    public function isCompleted(): bool          { return $this->status === self::STATUS_COMPLETED; }
    public function isUnderConsultation(): bool  { return $this->status === self::STATUS_UNDER_CONSULTATION; }
    public function isNeedsCorrection(): bool    { return $this->status === self::STATUS_NEEDS_CORRECTION; }
    public function isPaymentPending(): bool     { return $this->status === self::STATUS_PAYMENT_PENDING; }
    public function isPaymentConfirmed(): bool   { return $this->status === self::STATUS_PAYMENT_CONFIRMED; }
    public function isScheduled(): bool          { return $this->status === self::STATUS_SCHEDULED; }
    public function isInProgress(): bool         { return $this->status === self::STATUS_IN_PROGRESS; }
    public function isPassed(): bool             { return $this->status === self::STATUS_PASSED; }
    public function isFailed(): bool             { return $this->status === self::STATUS_FAILED; }
    public function isServed(): bool             { return $this->status === self::STATUS_SERVED; }
    public function isCancelled(): bool          { return $this->status === self::STATUS_CANCELLED; }

    public function isActionable(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_UNDER_CONSULTATION,
            self::STATUS_NEEDS_CORRECTION,
        ]);
    }

    public function isCorrectableByUser(): bool
    {
        return $this->status === self::STATUS_NEEDS_CORRECTION;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null && $this->receipt_number !== null;
    }

    public function hasCertificate(): bool
    {
        return $this->cert_status !== null;
    }

    public function isPickup(): bool { return $this->delivery_method === self::DELIVERY_PICKUP; }
    public function isPost(): bool   { return $this->delivery_method === self::DELIVERY_POST; }

    // -----------------------------------------------------------------------
    // Pricing helpers
    // -----------------------------------------------------------------------

    public static function basePrice(string $type): int
    {
        return self::TYPES[$type]['price'] ?? 0;
    }

    public function computedFee(): float
    {
        $base = self::basePrice($this->training_type ?? '');
        if ($this->training_kind === self::KIND_RECYCLING) {
            return round($base * 0.75, 3);
        }
        return (float) $base;
    }

    public static function durationWeeks(string $type): int
    {
        return self::TYPES[$type]['weeks'] ?? 1;
    }

    // -----------------------------------------------------------------------
    // Status label
    // -----------------------------------------------------------------------

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => __('training.status_draft'),
            self::STATUS_COMPLETED          => __('training.status_completed'),
            self::STATUS_UNDER_CONSULTATION => __('training.status_under_consultation'),
            self::STATUS_NEEDS_CORRECTION   => __('training.status_needs_correction'),
            self::STATUS_PAYMENT_PENDING    => __('training.status_payment_pending'),
            self::STATUS_PAYMENT_CONFIRMED  => __('training.status_payment_confirmed'),
            self::STATUS_SCHEDULED          => __('training.status_scheduled'),
            self::STATUS_IN_PROGRESS        => __('training.status_in_progress'),
            self::STATUS_PASSED             => __('training.status_passed'),
            self::STATUS_FAILED             => __('training.status_failed'),
            self::STATUS_SERVED             => __('training.status_served'),
            self::STATUS_CANCELLED          => __('training.status_cancelled'),
            default                         => ucfirst($this->status),
        };
    }

    // -----------------------------------------------------------------------
    // Correction rounds (same pattern as RescueCertificate)
    // -----------------------------------------------------------------------

    public const SNAPSHOT_EVENT_TYPES = ['correction_requested', 'history_snapshot'];

    public function lastCorrectionEvent(): ?TrainingEvent
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

    public function lastCorrectionData(): ?array
    {
        $event = $this->lastCorrectionEvent();
        if (! $event || ! is_array($event->data)) return null;

        return [
            'round'          => $this->correctionRoundsCount(),
            'note'           => $event->note,
            'fields_to_fix'  => $event->data['fields_to_fix'] ?? [],
            'docs_to_fix'    => $event->data['docs_to_fix'] ?? [],
            'previous'       => $event->data['previous'] ?? null,
            'archived_files' => $event->data['archived_files'] ?? null,
        ];
    }

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

    // -----------------------------------------------------------------------
    // Reference builder
    // -----------------------------------------------------------------------

    public static function makeReference(self $r): string
    {
        return 'TRN-' . date('Y') . '-' . str_pad((string) $r->id, 6, '0', STR_PAD_LEFT);
    }
}
