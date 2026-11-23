<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEvent extends Model
{
    use HasTranslations;

    protected $fillable = [
        'training_request_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = ['data' => 'array'];

    protected function note(): Attribute { return $this->translated('note'); }

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class, 'training_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
