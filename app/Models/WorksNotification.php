<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorksNotification extends Model
{
    protected $fillable = [
        'user_id', 'current_step', 'status',
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

    public const OWNER_NATURAL = 'natural';
    public const OWNER_MORAL   = 'moral';

    public const OWNER_TYPES = [
        self::OWNER_NATURAL,
        self::OWNER_MORAL,
    ];

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
        return $this->hasMany(WorksDocument::class, 'works_notification_id');
    }

    /** Full audit log — chronological history of every action on this notification */
    public function events(): HasMany
    {
        return $this->hasMany(WorksEvent::class, 'works_notification_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Record an event in the notification audit log (timeline).
     * The actor is the currently authenticated user (admin or applicant).
     */
    public function logEvent(string $type, ?string $note = null, array $data = []): WorksEvent
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
