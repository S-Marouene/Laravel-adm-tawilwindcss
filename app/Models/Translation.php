<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One entry of the database translations dictionary.
 *
 * (group, key) is unique — e.g. group='prevention', key='stepper_1'.
 * The three text columns hold the French, Arabic and English copies.
 * The DatabaseTranslationLoader overlays these on top of lang/ files,
 * so an empty text always falls back to the static file value.
 */
class Translation extends Model
{
    /** Locales served by the dictionary (must match lang/{locale}/ folders). */
    public const LOCALES = ['fr', 'ar', 'en'];

    protected $fillable = ['group', 'key', 'fr', 'ar', 'en', 'updated_by'];

    /** The admin who last edited this entry. */
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** True when at least one of the three copies is empty. */
    public function isIncomplete(): bool
    {
        return in_array(null, [static::clean($this->fr), static::clean($this->ar), static::clean($this->en)], true);
    }

    /** True when the row differs from the value in the lang/ files. */
    public function isCustomized(): bool
    {
        return $this->updated_by !== null;
    }

    public static function clean(?string $value): ?string
    {
        return ($value === null || trim($value) === '') ? null : $value;
    }

    /**
     * Flush the loader cache for one group (all locales) — must be called
     * after any create / update / delete so the change is served at once.
     */
    public static function flushGroupCache(string $group): void
    {
        foreach (self::LOCALES as $locale) {
            Cache::forget("translations:$locale:$group");
        }
    }

    protected static function booted(): void
    {
        static::saved(fn (self $t) => self::flushGroupCache($t->group));
        static::deleted(fn (self $t) => self::flushGroupCache($t->group));
    }
}
