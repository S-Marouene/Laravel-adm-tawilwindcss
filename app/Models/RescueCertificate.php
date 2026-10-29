<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RescueCertificate extends Model
{
    protected $fillable = [
        'user_id', 'current_step', 'status',
        'cin', 'nom_prenom', 'phone',
        'victim_is_minor', 'parent_name', 'parent_cin', 'spouse_case',
        'governorate_id', 'delegation_id', 'incident_address',
        'incident_type', 'incident_date', 'incident_time', 'incident_description', 'notes',
        'certificate_number', 'delivery_date', 'delivery_note', 'delivered_at',
        'reference',
        'admin_note', 'reviewed_by', 'reviewed_at',
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
    ];

    // All possible status values — useful for reference
    // draft | completed | under_consultation | needs_correction | approved | served | cancelled

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
        return $this->hasMany(RescueDocument::class, 'rescue_certificate_id');
    }

    /** Full audit log — chronological history of every action on this certificate */
    public function events(): HasMany
    {
        return $this->hasMany(RescueEvent::class, 'rescue_certificate_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Record an event in the certificate audit log (timeline).
     * The actor is the currently authenticated user (admin or applicant).
     */
    public function logEvent(string $type, ?string $note = null, array $data = []): RescueEvent
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

    public function isServed(): bool
    {
        return $this->status === 'served';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /** Can this certificate be acted on by admin (not yet resolved) */
    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation', 'needs_correction']);
    }

    /** User can edit/resubmit this certificate */
    public function isCorrectableByUser(): bool
    {
        return $this->status === 'needs_correction';
    }

    public function isDelivered(): bool
    {
        return $this->isServed() && $this->delivered_at !== null;
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
        return match($this->status) {
            'draft'              => 'Draft',
            'completed'          => 'Under Review',
            'under_consultation' => 'Verifying Rescue Operation',
            'needs_correction'   => 'Needs Correction',
            'approved'           => 'Approved',
            'served'             => 'Certificate Delivered',
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }
}
