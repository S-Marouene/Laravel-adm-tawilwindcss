<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceDocument extends Model
{
    protected $fillable = [
        'preventive_presence_request_id', 'document_type_id',
        'file_path', 'original_name', 'mime_type', 'size', 'status',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    // -----------------------------------------------------------------------

    public function request(): BelongsTo
    {
        return $this->belongsTo(PreventivePresenceRequest::class, 'preventive_presence_request_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
