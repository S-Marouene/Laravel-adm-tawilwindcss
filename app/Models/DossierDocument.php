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

    // -----------------------------------------------------------------------

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(PreventionDossier::class, 'prevention_dossier_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
