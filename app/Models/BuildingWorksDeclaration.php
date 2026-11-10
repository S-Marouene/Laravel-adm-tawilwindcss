<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingWorksDeclaration extends Model
{
    use HasTranslations;

    protected $fillable = [
        'user_id', 'current_step', 'status', 'reference',
        'owner_type', 'owner_name', 'owner_cin', 'company_name', 'jort_reference', 'phone',
        'governorate_id', 'delegation_id', 'property_address',
        'works_type', 'works_start_date', 'works_description', 'notes',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'current_step'     => 'integer',
        'works_start_date' => 'date',
        'reviewed_at'      => 'datetime',
    ];

    // All possible status values — useful for reference
    // draft | completed | under_consultation | needs_correction | approved | cancelled

    // -----------------------------------------------------------------------
    // Bilingual fields
    // -----------------------------------------------------------------------

    /**
     * Note left by the administration (correction requests, cancellation…).
     * Stored as {"fr": …, "ar": …} — reading returns the localized string.
     */
    protected function adminNote(): Attribute
    {
        return $this->translated('admin_note');
    }

    public const OWNER_NATURAL = 'natural';
    public const OWNER_MORAL   = 'moral';

    public const OWNER_TYPES = [
        self::OWNER_NATURAL,
        self::OWNER_MORAL,
    ];

    /**
     * Notifier compatibility — DossierNotifier reads `nom_prenom` on every
     * dossier-like model. For works, the applicant is the owner (natural)
     * or the legal representative of the company (moral).
     */
    public function getNomPrenomAttribute(): ?string
    {
        return $this->owner_name;
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
        return $this->hasMany(BuildingBuildingBuildingBuildingWorksDocument::class, 'works_notification_id');
    }

    /** Full audit log — chronological history of every action on this notification */
    public function events(): HasMany
    {
        return $this->hasMany(BuildingBuildingBuildingBuildingWorksEvent::class, 'works_notification_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Record an event in the notification audit log (timeline).
     * The actor is the currently authenticated user (admin or applicant).
     * The note (when given) may be a legacy string or a bilingual {fr, ar} array.
     */
    public function logEvent(string $type, string|array|null $note = null, array $data = []): BuildingBuildingBuildingBuildingWorksEvent
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isNatural(): bool
    {
        return $this->owner_type === self::OWNER_NATURAL;
    }

    public function isMoral(): bool
    {
        return $this->owner_type === self::OWNER_MORAL;
    }

    public function isNeedsCorrection(): bool
    {
        return $this->status === 'needs_correction';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isUnderConsultation(): bool
    {
        return $this->status === 'under_consultation';
    }

    public function isUnderReview(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation']);
    }

    /** Can this notification be acted on by admin (not yet resolved) */
    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation', 'needs_correction']);
    }

    // -----------------------------------------------------------------------
    // Correction rounds (mirrors RescueCertificate / PreventionDossier)
    // -----------------------------------------------------------------------

    /** Event types that hold a full snapshot of a previous (old) version */
    public const SNAPSHOT_EVENT_TYPES = ['correction_requested', 'history_snapshot'];

    public function lastCorrectionEvent(): ?BuildingBuildingBuildingBuildingWorksEvent
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

    // -----------------------------------------------------------------------
    // Status label helper
    // -----------------------------------------------------------------------

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'              => 'Draft',
            'completed'          => 'Under Review',
            'under_consultation' => 'Under Consultation',
            'needs_correction'   => 'Needs Correction',
            'approved'           => 'Approved — Civil Protection informed',
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }
}
