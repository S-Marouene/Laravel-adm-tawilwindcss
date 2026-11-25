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

    // Granular Spatie permissions for the yearly plan of centralized formations.
    // Viewing is intentionally separate from writing so a regional manager can
    // follow the planning without being able to create/edit/cancel a session.
    public const PERM_VIEW   = 'view training-planned-sessions';
    public const PERM_CREATE = 'create training-planned-sessions';
    public const PERM_EDIT   = 'edit training-planned-sessions';
    public const PERM_DELETE = 'delete training-planned-sessions';

    /** Permissions an administrator may grant directly to a user. */
    public const PERMISSIONS = [
        self::PERM_VIEW,
        self::PERM_CREATE,
        self::PERM_EDIT,
        self::PERM_DELETE,
    ];

    protected $fillable = [
        'training_type',
        'governorate_id',
        'starts_at',
        'ends_at',
        'capacity',
        'status',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'capacity'      => 'integer',
        'is_active'     => 'boolean',
        'governorate_id' => 'integer',
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

    /** The governorate where this centralized session is physically held. */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class, 'planned_session_id');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /**
     * Sessions that can still be chosen by a citizen.
     *
     * Capacity is intentionally NOT part of this scope: citizens may submit
     * even when a session is full; the central service later selects the
     * participants from the roster. `is_active` is the on/off switch.
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PLANNED)
            ->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    /**
     * Sessions an administrator may assign requests to. Unlike the public
     * scope, this ignores is_active: closing submissions must not prevent the
     * centrale from finalizing the roster afterwards.
     */
    public function scopeAssignable(Builder $query): Builder
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
        return $this->is_active
            && $this->isPlanned()
            && $this->starts_at->isFuture();
    }

    /** Assignment stays available after submissions are closed. */
    public function isAssignable(): bool
    {
        return $this->isPlanned() && $this->starts_at->isFuture();
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

    /** Short localized name of the place where the session is held. */
    public function placeLabel(): string
    {
        return $this->governorate?->shortName() ?? self::defaultPlaceLabel();
    }

    /** Fallback place used before an administrator picks another governorate. */
    public static function defaultPlaceLabel(): string
    {
        return Governorate::benArous()?->shortName() ?? __('training.planned_place_default');
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

    /** @return array<string, string> Permission name => translated label. */
    public static function permissionLabels(): array
    {
        return [
            self::PERM_VIEW   => __('training.planned_perm_view'),
            self::PERM_CREATE => __('training.planned_perm_create'),
            self::PERM_EDIT   => __('training.planned_perm_edit'),
            self::PERM_DELETE => __('training.planned_perm_delete'),
        ];
    }
}
