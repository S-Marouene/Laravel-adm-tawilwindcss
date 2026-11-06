<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Lightweight bilingual (fr / ar) note handling.
 *
 * A "note" column stores a JSON object: {"fr": "...", "ar": "..."}.
 * Legacy plain-text values (written before this feature) are still
 * read transparently as if they were the French translation.
 *
 * Usage in a model:
 *
 *   use App\Concerns\HasTranslations;
 *
 *   public function adminNote(): Attribute   // column: admin_note
 *   {
 *       return $this->translated('admin_note');
 *   }
 */
trait HasTranslations
{
    // Supported note languages, in fallback order (fr is the primary).
    public const TRANSLATION_LOCALES = ['fr', 'ar'];

    /**
     * Bilingual attribute: get = localized string (current app locale with
     * fallback), set = accepts {fr, ar} array (or a legacy string) and
     * packs it into storable JSON.
     *
     * The parameter defaults to null on purpose: Laravel's accessor discovery
     * (HasAttributes::getAttributeMarkedMutatorMethods) calls every
     * Attribute-returning method with NO arguments during its reflection scan,
     * so a required parameter would crash model hydration/serialization.
     */
    protected function translated(?string $field = null): Attribute
    {
        return Attribute::make(
            get: fn ($value) => self::pickTranslation($value),
            set: fn ($value) => self::packTranslation($value),
        );
    }

    /**
     * Decode a stored note and return the requested (or current app) locale.
     * Fallback order: requested locale → fr → ar → raw legacy string.
     */
    public static function pickTranslation(mixed $raw, ?string $locale = null): ?string
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }

        $data = [];

        if (is_string($raw)) {
            $trimmed = trim($raw);

            if ($trimmed !== '' && str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);
                $data    = is_array($decoded) ? $decoded : [];
            } else {
                // Legacy plain-text note — treat as the primary language (fr).
                return $trimmed;
            }
        } elseif (is_array($raw)) {
            $data = $raw;
        }

        $order = $locale
            ? array_values(array_unique([$locale, app()->getLocale(), ...self::TRANSLATION_LOCALES]))
            : array_values(array_unique([app()->getLocale(), ...self::TRANSLATION_LOCALES]));

        foreach ($order as $code) {
            $value = $data[$code] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Pack a {fr, ar} array (or legacy string) into the storable JSON string.
     * Returns null when both languages are empty.
     */
    public static function packTranslation(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Already-stored JSON string — pass through unchanged.
        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            return str_starts_with($trimmed, '{')
                ? $trimmed
                : self::packTranslation(['fr' => $trimmed]);
        }

        if (! is_array($value)) {
            return null;
        }

        $value = collect($value)
            ->map(fn ($v) => is_string($v) ? trim($v) : (string) $v)
            ->filter(fn ($v) => $v !== '')
            ->only(self::TRANSLATION_LOCALES)
            ->all();

        if (empty($value)) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
