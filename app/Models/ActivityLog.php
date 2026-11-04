<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity type badge color class.
     */
    public function badgeColor(): string
    {
        return match ($this->type) {
            'login', 'logout' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300',
            'create' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
            'update' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300',
            'delete' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
            'export' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300',
            'prevention_create' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
            'prevention_update' => 'bg-cyan-100 dark:bg-cyan-900/50 text-cyan-800 dark:text-cyan-300',
            'prevention_delete' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
        };
    }

    /** Human-readable label for the type badge */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            'export' => 'Export',
            'prevention_create' => 'Prevention — Create',
            'prevention_update' => 'Prevention — Update',
            'prevention_delete' => 'Prevention — Delete',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
