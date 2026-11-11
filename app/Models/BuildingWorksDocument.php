<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingWorksDocument extends Model
{
    /** DB table kept at original name — class renamed from WorksDocument. */
    protected $table = 'works_documents';
    protected $fillable = [
        'works_notification_id', 'document_type_id', 'file_path',
        'original_name', 'mime_type', 'size', 'status',
    ];

    // Document states: pending | uploaded | archived
    // "archived" = replaced by a newer version after a correction round —
    // the old file is kept on disk for history but excluded from the
    // active checklist and from completeness counting.

    public function isActive(): bool    { return $this->status !== 'archived'; }
    public function isArchived(): bool  { return $this->status === 'archived'; }
    public function scopeActive($query) { return $query->where('status', '!=', 'archived'); }

    // -----------------------------------------------------------------------

    public function notification(): BelongsTo
    {
        return $this->belongsTo(BuildingWorksDeclaration::class, 'works_notification_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
