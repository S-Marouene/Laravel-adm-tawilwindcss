<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PreventionDossier extends Model
{
    use HasTranslations;


    // Delivery methods chosen by the applicant once the RDV is passed.
    // Digital download is announced but not active yet ("coming soon").
    public const DELIVERY_DIGITAL = 'digital';
    public const DELIVERY_PICKUP  = 'pickup';
    public const DELIVERY_POST    = 'post';

    protected $fillable = [
        'user_id', 'current_step', 'status',
        'cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id',
        'building_address', 'latitude', 'longitude',
        'building_type_id', 'building_activity_id', 'activity_other',
        'num_demande', 'api_reference', 'reference',
        'delivery_method', 'post_address',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'governorate_id'       => 'integer',
        'delegation_id'        => 'integer',
        'building_type_id'     => 'integer',
        'building_activity_id' => 'integer',
        'current_step'         => 'integer',
        'latitude'             => 'decimal:7',
        'longitude'            => 'decimal:7',
        'reviewed_at'          => 'datetime',
        'delivery_choice_at'   => 'datetime',
    ];

    // All possible status values — useful for reference
    // draft | completed | submitted | under_consultation | needs_correction | approved
    // waiting_delivery | cancelled

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

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'building_type_id');
    }

    public function buildingActivity(): BelongsTo
    {
        return $this->belongsTo(BuildingActivity::class, 'building_activity_id');
    }

    /** Every file attached to the dossier (step 3) */
    public function documents(): HasMany
    {
        return $this->hasMany(DossierDocument::class, 'prevention_dossier_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(DossierAppointment::class, 'prevention_dossier_id')->orderBy('visit_date')->orderBy('visit_time');
    }

    /** The prevention certificate issued after a passed RDV (one per dossier) */
    public function certificate(): HasOne
    {
        return $this->hasOne(PreventionCertificate::class, 'prevention_dossier_id');
    }

    /** True once at least one visit (RDV) was marked as passed */
    public function hasPassedAppointment(): bool
    {
        return $this->appointments->contains(fn (DossierAppointment $appt) => $appt->isPassed());
    }

    /** Full audit log — chronological history of every action on this dossier */
    public function events(): HasMany
    {
        return $this->hasMany(PreventionDossierEvent::class, 'prevention_dossier_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Record an event in the dossier audit log (timeline).
     * The actor is the currently authenticated user (admin or applicant).
     * The note (when given) may be a legacy string or a bilingual {fr, ar} array.
     */
    public function logEvent(string $type, string|array|null $note = null, array $data = []): PreventionDossierEvent
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isUnderConsultation(): bool
    {
        return $this->status === 'under_consultation';
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

    /** Inspection passed — everything is OK, waiting for the certificate delivery */
    public function isWaitingDelivery(): bool
    {
        return $this->status === 'waiting_delivery';
    }

    /** The applicant already picked how he wants to receive the certificate */
    public function hasChosenDelivery(): bool
    {
        return $this->delivery_method !== null;
    }

    /** Last RDV that was recorded (passed or failed) — used to show the fail reason */
    public function lastAppointment(): ?DossierAppointment
    {
        return $this->appointments->last();
    }

    // -----------------------------------------------------------------------
    // Correction history — every correction round keeps the old data & files
    // -----------------------------------------------------------------------

    /** Event types that hold a full snapshot of a previous (old) version */
    public const SNAPSHOT_EVENT_TYPES = ['correction_requested', 'history_snapshot'];

    /** The most recent snapshot event — anchor of the current correction round */
    public function lastCorrectionEvent(): ?PreventionDossierEvent
    {
        return $this->events->last(
            fn ($e) => in_array($e->type, self::SNAPSHOT_EVENT_TYPES)
                || ($e->type === 'resubmitted' && is_array($e->data) && ! empty($e->data['previous']))
        );
    }

    /** How many correction rounds this dossier went through (1, 2, 3…) */
    public function correctionRoundsCount(): int
    {
        $legacy = $this->events->where('type', 'resubmitted')
            ->filter(fn ($e) => is_array($e->data) && ! empty($e->data['previous']))
            ->count();

        return $this->events
            ->whereIn('type', self::SNAPSHOT_EVENT_TYPES)
            ->count() + $legacy;
    }

    /**
     * Everything the applicant needs to see about the previous version of
     * the demande: the old field values (if you change a field it becomes
     * the new version — and the old value is kept in this history), plus
     * the old upload files that were archived for that round.
     */
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

    /**
     * ALL correction rounds in chronological order — each entry has:
     *   round, date, note, previous (old field values), archived_files (metadata)
     * Used to render the full correction history on the form and show pages.
     */
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

    public function isUnderReview(): bool
    {
        return in_array($this->status, ['completed', 'submitted', 'under_consultation']);
    }

    /** Can this dossier be acted on by admin (not yet resolved) */
    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'submitted', 'under_consultation', 'needs_correction']);
    }

    /** User can edit/resubmit this dossier */
    public function isCorrectableByUser(): bool
    {
        return $this->status === 'needs_correction';
    }

    // -----------------------------------------------------------------------
    // Status label helper
    // -----------------------------------------------------------------------

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'              => 'Draft',
            'completed'          => 'Under Review',
            'submitted'          => 'Submitted',
            'under_consultation' => 'Approved — Awaiting RDV',
            'needs_correction'   => 'Needs Correction',
            'approved'           => 'Approved',
            'waiting_delivery'   => 'All OK — Awaiting Certificate Delivery',
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }
}
