<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorksDocument extends Model
{
    protected $fillable = [
        'works_notification_id', 'document_type_id', 'file_path',
        'original_name', 'mime_type', 'size', 'status',
    ];

    // -----------------------------------------------------------------------

    public function notification(): BelongsTo
    {
        return $this->belongsTo(WorksNotification::class, 'works_notification_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
