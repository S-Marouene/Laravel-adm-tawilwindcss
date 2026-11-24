<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingPlannedSession extends Model
{
    public const STATUS_PLANNED   = 'planned';
    public const STATUS_STARTED   = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'training_type',
        'starts_at',
        'ends_at',
        'capacity',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'  => 'datetime',
        'capacity' => 'integer',
    ];

    protected $attributes = [
        'status' => self::STATUS_PLANNED,
    ];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class, 'planned_session_id');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /** Sessions that can still be chosen by a citizen. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PLANNED)
            ->where('starts_at', '>', now());
    }

    public function scopeForCentralizedTypes(Builder $query): Builder
    {
        return $query->whereIn('training_type', TrainingRequest::CENTRALIZED_TYPES);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isPlanned(): bool   { return $this->status === self::STATUS_PLANNED; }
    public function isStarted(): bool  { return $this->status === self::STATUS_STARTED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function isSelectable(): bool
    {
        return $this->isPlanned() && $this->starts_at->isFuture() && $this->seatsLeft() !== 0;
    }

    /** Number of persons enrolled (a society counts its participants, an individual = 1). */
    public function seatsTaken(): int
    {
        $sum = 0;
        $this->requests()
            ->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED])
            ->with('participants')
            ->get()
            ->each(function (TrainingRequest $r) use (&$sum) {
                $sum += $r->isCompany() ? $r->participants->count() : 1;
            });

        return $sum;
    }

    /**
     * All persons across the linked requests, ordered by request date then by
     * the society's internal priority (the "top persons" come first).
     */
    public function personRoster(): \Illuminate\Support\Collection
    {
        $roster = collect();

        $this->requests()
            ->whereNotIn('status', [TrainingRequest::STATUS_CANCELLED])
            ->with('participants')
            ->orderBy('created_at')
            ->get()
            ->each(function (TrainingRequest $r) use (&$roster) {
                if ($r->isCompany()) {
                    foreach ($r->participants as $p) {
                        $roster->push(['request' => $r, 'participant' => $p]);
                    }
                } else {
                    // An individual has no participant row — synthesize one from the request.
                    $roster->push([
                        'request'    => $r,
                        'participant' => (object) [
                            'nom_prenom'    => $r->nom_prenom,
                            'cin'           => $r->cin,
                            'training_kind' => $r->training_kind,
                            'priority'      => 1,
                        ],
                    ]);
                }
            });

        return $roster;
    }

    /** Persons taken up to capacity (the top-ranked first). */
    public function enrolledParticipants(): \Illuminate\Support\Collection
    {
        $roster = $this->personRoster();

        return $this->capacity === null ? $roster : $roster->take($this->capacity);
    }

    /** Persons beyond the capacity (on the wait list). */
    public function waitlistedParticipants(): \Illuminate\Support\Collection
    {
        if ($this->capacity === null) return collect();

        return $this->personRoster()->slice($this->capacity);
    }

    /**
     * Remaining seats. null = unlimited, 0 = full.
     */
    public function seatsLeft(): ?int
    {
        if ($this->capacity === null) return null;

        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsLeft() === 0;
    }

    public function typeLabel(): string
    {
        return __('training.type_' . $this->training_type);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PLANNED   => __('training.planned_status_planned'),
            self::STATUS_STARTED   => __('training.planned_status_started'),
            self::STATUS_COMPLETED => __('training.planned_status_completed'),
            self::STATUS_CANCELLED => __('training.planned_status_cancelled'),
            default               => ucfirst($this->status),
        };
    }
}
