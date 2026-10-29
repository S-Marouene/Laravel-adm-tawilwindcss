<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PreventionDossier extends Model
{
    protected $fillable = [
        'user_id', 'current_step', 'status',
        'cin', 'nom_prenom', 'phone', 'governorate_id', 'delegation_id',
        'building_address',
        'building_type_id', 'building_activity_id', 'activity_other',
        'num_demande', 'api_reference', 'reference',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'governorate_id'       => 'integer',
        'delegation_id'        => 'integer',
        'building_type_id'     => 'integer',
        'building_activity_id' => 'integer',
        'current_step'         => 'integer',
        'reviewed_at'          => 'datetime',
    ];

    // All possible status values — useful for reference
    // draft | completed | submitted | under_consultation | needs_correction | approved | cancelled

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
     */
    public function logEvent(string $type, ?string $note = null, array $data = []): PreventionDossierEvent
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
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }
}
