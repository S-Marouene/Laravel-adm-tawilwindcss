<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescueDocument extends Model
{
    protected $fillable = [
        'rescue_certificate_id', 'document_type_id', 'file_path',
        'original_name', 'mime_type', 'size', 'status',
    ];

    // -----------------------------------------------------------------------

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(RescueCertificate::class, 'rescue_certificate_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
