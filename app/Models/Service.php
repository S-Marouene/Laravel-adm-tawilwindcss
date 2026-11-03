<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The back-office "service" registry (one row per citizen-facing procedure).
 * Administrators are assigned services explicitly, so a delegation user can
 * handle e.g. prevention only, while a centrale user handles rescue only.
 */
class Service extends Model
{
    // Canonical service keys — identical to DocumentType::SERVICE_* keys.
    public const SERVICE_PREVENTION          = 'prevention';
    public const SERVICE_PREVENTIVE_PRESENCE = 'preventive-presence';
    public const SERVICE_RESCUE_CERTIFICATE  = 'rescue-certificate';
    public const SERVICE_WORKS_NOTIFICATION  = 'works-notification';

    public const KEYS = [
        self::SERVICE_PREVENTION,
        self::SERVICE_PREVENTIVE_PRESENCE,
        self::SERVICE_RESCUE_CERTIFICATE,
        self::SERVICE_WORKS_NOTIFICATION,
    ];

    protected $fillable = ['key', 'name_ar', 'name_fr', 'name_en', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    // -------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'fr'    => $this->name_fr,
            'en'    => $this->name_en,
            default => $this->name_ar,
        };
    }

    /**
     * Index route of the service's admin panel, used to redirect
     * scoped administrators to their first handled service.
     */
    public function adminIndexRoute(): string
    {
        return match ($this->key) {
            self::SERVICE_PREVENTIVE_PRESENCE => 'admin.preventive-presence.index',
            self::SERVICE_RESCUE_CERTIFICATE  => 'admin.rescue-certificates.index',
            self::SERVICE_WORKS_NOTIFICATION  => 'admin.works-notifications.index',
            default                           => 'admin.prevention-dossiers.index',
        };
    }

    /**
     * The permission prefix used by Spatie gates: 'view {prefix}', 'manage {prefix}'.
     */
    public function permissionPrefix(): string
    {
        return match ($this->key) {
            self::SERVICE_PREVENTIVE_PRESENCE => 'preventive-presence',
            self::SERVICE_RESCUE_CERTIFICATE  => 'rescue-certificates',
            self::SERVICE_WORKS_NOTIFICATION  => 'works-notifications',
            default                           => 'prevention-dossiers',
        };
    }
}
