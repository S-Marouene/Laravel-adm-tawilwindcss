<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSession extends Model
{
    protected $fillable = [
        'training_request_id', 'session_date', 'attendance', 'note', 'recorded_by',
    ];

    protected $casts = ['session_date' => 'date'];

    public const PRESENT = 'present';
    public const ABSENT  = 'absent';
    public const EXCUSED = 'excused';

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class, 'training_request_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isPresent(): bool { return $this->attendance === self::PRESENT; }
    public function isAbsent(): bool  { return $this->attendance === self::ABSENT; }
    public function isExcused(): bool { return $this->attendance === self::EXCUSED; }
}
