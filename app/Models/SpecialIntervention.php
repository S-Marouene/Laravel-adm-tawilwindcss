<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialIntervention extends Model
{
    use HasTranslations;

    // -----------------------------------------------------------------------
    // Status constants
    // -----------------------------------------------------------------------
    public const STATUS_DRAFT             = 'draft';
    public const STATUS_COMPLETED         = 'completed';
    public const STATUS_UNDER_CONSULTATION = 'under_consultation';
    public const STATUS_NEEDS_CORRECTION  = 'needs_correction';
    public const STATUS_APPROVED          = 'approved';
    public const STATUS_SERVED            = 'served';
    public const STATUS_CANCELLED         = 'cancelled';

    protected $fillable = [
        'user_id', 'current_step', 'status', 'reference',
        'cin', 'nom_prenom', 'phone',
        'governorate_id', 'delegation_id',
        'intervention_date', 'intervention_time',
        'interventions', 'total_amount',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'current_step'     => 'integer',
        'interventions'    => 'array',
        'total_amount'     => 'integer',
        'intervention_date'=> 'date',
        'intervention_time'=> 'datetime:H:i',
        'reviewed_at'      => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Bilingual fields
    // -----------------------------------------------------------------------

    protected function adminNote(): Attribute
    {
        return $this->translated('admin_note');
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

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class, 'delegation_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SpecialInterventionDocument::class, 'special_intervention_id')
            ->orderBy('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SpecialInterventionEvent::class, 'special_intervention_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function logEvent(string $type, string|array|null $note = null, array $data = []): SpecialInterventionEvent
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
    public function isApproved(): bool           { return $this->status === self::STATUS_APPROVED; }
    public function isServed(): bool             { return $this->status === self::STATUS_SERVED; }
    public function isCancelled(): bool          { return $this->status === self::STATUS_CANCELLED; }

    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation', 'needs_correction']);
    }

    public function isCorrectableByUser(): bool
    {
        return $this->status === 'needs_correction';
    }

    // -----------------------------------------------------------------------
    // Correction rounds (same snapshot pattern as RescueCertificate)
    // -----------------------------------------------------------------------

    public const SNAPSHOT_EVENT_TYPES = ['correction_requested', 'history_snapshot'];

    public function lastCorrectionEvent(): ?SpecialInterventionEvent
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
    // Interventions catalogue (the fee table from the procedure description)
    // -----------------------------------------------------------------------

    public const INTERVENTIONS = [
        'ambulance'               => ['price' => 60],
        'medical_ambulance'       => ['price' => 100],
        'fire_truck'              => ['price' => 120],
        'fire_lorry'              => ['price' => 200],
        'supply_lorry'            => ['price' => 400],
        'mechanical_ladder'       => ['price' => 900],
        'fuel_fire_truck'         => ['price' => 800],
        'tow_truck'               => ['price' => 400],
        'rescue_boat'             => ['price' => 120],
        'road_rescue_truck'       => ['price' => 300],
        'cargo_lorry'             => ['price' => 200],
        'cargo_car'               => ['price' => 60],
        'pump'                    => ['price' => 120],
        'misc_equipment'          => ['price' => 40],
        'diving_team'             => ['price' => 800],
    ];

    public static function interventionList(): array
    {
        $keys = array_keys(self::INTERVENTIONS);
        $list = [];
        foreach ($keys as $key) {
            $list[$key] = [
                'key'    => $key,
                'name_ar' => trans('special_intervention.intervention_' . $key, [], 'ar'),
                'name_fr' => trans('special_intervention.intervention_' . $key, [], 'fr'),
                'price'  => self::INTERVENTIONS[$key]['price'],
            ];
        }
        return $list;
    }

    public static function interventionPrice(string $key): ?int
    {
        return self::INTERVENTIONS[$key]['price'] ?? null;
    }

    /** Recompute the total amount from the stored interventions JSON. */
    public function recomputeTotal(): int
    {
        $items = $this->interventions ?? [];
        $total = 0;
        foreach ($items as $item) {
            if (! is_array($item)) continue;
            $price  = (int) ($item['price'] ?? 0);
            $hours  = (int) ($item['hours'] ?? 0);
            $total += $price * $hours;
        }
        return $total;
    }

    /** Human-readable total with currency. */
    public function totalLabel(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar') {
            return number_format($this->total_amount, 0) . ' دينار تونسي';
        }
        if ($locale === 'fr') {
            return number_format($this->total_amount, 0) . ' TND';
        }
        return number_format($this->total_amount, 0) . ' TND';
    }

    /** Per-hour billing note. */
    public static function billingNote(): string
    {
        return trans('special_intervention.billing_note');
    }

    // -----------------------------------------------------------------------
    // Status label
    // -----------------------------------------------------------------------

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'              => __('special_intervention.status_draft'),
            'completed'          => __('special_intervention.status_completed'),
            'under_consultation' => __('special_intervention.status_under_consultation'),
            'needs_correction'   => __('special_intervention.status_needs_correction'),
            'approved'           => __('special_intervention.status_approved'),
            'served'             => __('special_intervention.status_served'),
            'cancelled'          => __('special_intervention.status_cancelled'),
            default              => ucfirst($this->status),
        };
    }

    /** Build a unique reference, e.g. INTV-2026-000123. */
    public static function makeReference(self $intervention): string
    {
        return 'INTV-' . date('Y') . '-' . str_pad((string) $intervention->id, 6, '0', STR_PAD_LEFT);
    }
}
