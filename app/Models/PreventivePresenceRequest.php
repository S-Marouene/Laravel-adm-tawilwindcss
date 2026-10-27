<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreventivePresenceRequest extends Model
{
    protected $fillable = [
        'user_id', 'current_step', 'status',
        'cin', 'nom_prenom', 'phone',
        'governorate_id', 'delegation_id', 'event_address',
        'event_title', 'event_date', 'event_end_date', 'event_time',
        'expected_attendance', 'notes',
        'fee_amount', 'receipt_number', 'paid_at',
        'service_date', 'service_team', 'service_agents', 'service_note', 'served_at',
        'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'current_step'        => 'integer',
        'governorate_id'      => 'integer',
        'delegation_id'       => 'integer',
        'event_date'          => 'date',
        'event_end_date'      => 'date',
        'event_time'          => 'datetime:H:i',
        'expected_attendance' => 'integer',
        'fee_amount'          => 'decimal:3',
        'paid_at'             => 'datetime',
        'service_date'        => 'date',
        'service_agents'      => 'integer',
        'served_at'           => 'datetime',
        'reviewed_at'         => 'datetime',
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
        return $this->hasMany(PresenceDocument::class, 'preventive_presence_request_id');
    }

    /** Full audit log — chronological history of every action on this request */
    public function events(): HasMany
    {
        return $this->hasMany(PreventivePresenceEvent::class, 'preventive_presence_request_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Record an event in the request audit log (timeline).
     * The actor is the currently authenticated user (admin or applicant).
     */
    public function logEvent(string $type, ?string $note = null, array $data = []): PreventivePresenceEvent
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

    public function isUnderReview(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation']);
    }

    /** Can this request be acted on by admin (not yet resolved) */
    public function isActionable(): bool
    {
        return in_array($this->status, ['completed', 'under_consultation', 'needs_correction']);
    }

    /** User can edit/resubmit this request */
    public function isCorrectableByUser(): bool
    {
        return $this->status === 'needs_correction';
    }

    /**
     * The procedure is paid (بمقابل) — proof of payment is a receipt (وصل)
     * recorded by the administration.
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null && $this->receipt_number !== null;
    }

    /** The service can be performed only once the request is approved and the fee receipt is recorded */
    public function canBeServed(): bool
    {
        return $this->isApproved() && $this->isPaid();
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
            'approved'           => 'Approved',
            'served'             => 'Service Performed',
            'cancelled'          => 'Cancelled',
            default              => ucfirst($this->status),
        };
    }
}
