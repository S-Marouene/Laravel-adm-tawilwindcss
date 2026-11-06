<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side proxy for real-time admin note translation (fr <-> ar).
 *
 * Primary engine: Google Translate's free public "gtx" endpoint —
 * no API key, good Arabic quality. If it fails (blocked, rate-limited…),
 * falls back to the official MyMemory free API.
 *
 * Results are cached by (from, to, text) so real-time typing never hammers
 * the external services with the same sentence twice.
 */
class TranslationService
{
    public const SUPPORTED = ['fr', 'ar', 'en'];

    /** Segment cache lifetime */
    private const CACHE_TTL = 86400; // 24h

    /** Google request timeout (seconds) — must feel instant while typing */
    private const TIMEOUT = 6;

    /**
     * Translate a text segment between two supported languages.
     * Returns null when no engine could produce a result.
     */
    public function translate(string $text, string $from, string $to): ?string
    {
        $text  = trim($text);
        $from  = in_array($from, self::SUPPORTED, true) ? $from : 'fr';
        $to    = in_array($to, self::SUPPORTED, true) ? $to : 'ar';

        if ($text === '' || $from === $to) {
            return null;
        }

        return Cache::remember(
            'auto-translate:' . $from . '>' . $to . ':' . md5($text),
            self::CACHE_TTL,
            fn () => $this->viaGoogle($text, $from, $to) ?? $this->viaMyMemory($text, $from, $to)
        );
    }

    /**
     * Google Translate free endpoint (client=gtx).
     * Response shape: [[["seg1","src1",…],["seg2","src2",…],…], …]
     */
    private function viaGoogle(string $text, string $from, string $to): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->asForm()
                ->acceptJson()
                ->post('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl'     => $from,
                    'tl'     => $to,
                    'dt'     => 't',
                    'ie'     => 'UTF-8',
                    'oe'     => 'UTF-8',
                    'q'      => $text,
                ]);

            if ($response->failed()) {
                return null;
            }

            $payload = $response->json();

            if (! is_array($payload) || ! is_array($payload[0] ?? null)) {
                return null;
            }

            // Concatenate the translated sentence segments.
            $translated = collect($payload[0])
                ->filter(fn ($seg) => is_array($seg) && isset($seg[0]) && is_string($seg[0]))
                ->pluck(0)
                ->implode('');

            return $translated !== '' ? $translated : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * MyMemory free API — official fallback.
     * GET https://api.mymemory.translated.net/get?q=…&langpair=fr|ar
     */
    private function viaMyMemory(string $text, string $from, string $to): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->acceptJson()
                ->get('https://api.mymemory.translated.net/get', [
                    'q'        => $text,
                    'langpair' => $from . '|' . $to,
                ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json('responseData.translatedText');

            if (! is_string($data) || trim($data) === '') {
                return null;
            }

            // MyMemory error payloads come as "PLEASE SELECT TWO DISTINCT..." etc.
            if (str_starts_with(trim($data), 'MYMEMORY WARNING') || str_starts_with(trim($data), 'PLEASE')) {
                return null;
            }

            return $data;
        } catch (\Throwable) {
            return null;
        }
    }
}
