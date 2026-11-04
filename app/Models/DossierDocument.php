<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierDocument extends Model
{
    protected $fillable = [
        'prevention_dossier_id', 'document_type_id',
        'file_path', 'original_name', 'mime_type', 'size', 'status',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    // Document states: pending | uploaded | archived
    // "archived" = replaced by a newer version after a correction round —
    // the old file is kept on disk for history but excluded from the
    // active checklist and from completeness counting.

    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    // -----------------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------------

    public function isActive(): bool    { return $this->status !== 'archived'; }
    public function isArchived(): bool  { return $this->status === 'archived'; }

    /** Only the current (non-archived) documents — used by checklists */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'archived');
    }
}
