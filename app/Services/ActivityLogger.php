<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log an activity entry.
     */
    public static function log(
        string $type,
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?int $userId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a login event.
     */
    public static function login($user): void
    {
        self::log('login', "{$user->name} logged in.", $user);
    }

    /**
     * Log a logout event.
     */
    public static function logout($user): void
    {
        self::log('logout', "{$user->name} logged out.", $user);
    }

    /**
     * Log a resource creation.
     */
    public static function created(string $label, Model $subject, ?array $properties = null): void
    {
        self::log('create', "Created {$label}", $subject, $properties);
    }

    /**
     * Log a resource update.
     */
    public static function updated(string $label, Model $subject, ?array $changes = null): void
    {
        self::log('update', "Updated {$label}", $subject, $changes);
    }

    /**
     * Log a resource deletion.
     */
    public static function deleted(string $label, Model $subject, ?array $properties = null): void
    {
        self::log('delete', "Deleted {$label}", $subject, $properties);
    }
}
