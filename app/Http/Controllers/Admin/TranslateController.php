<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Real-time translation endpoint for the bilingual admin notes.
 * Thin server-side proxy — no API keys, results cached, rate limited.
 */
class TranslateController extends Controller
{
    /**
     * POST /admin/translate  { text, from, to } → { translation }
     */
    public function __invoke(Request $request, TranslationService $translator): JsonResponse
    {
        $validated = $request->validate([
            // A single sentence / paragraph fragment while the admin types.
            'text' => ['required', 'string', 'max:3000'],
            'from' => ['required', 'string', 'max:5'],
            'to'   => ['required', 'string', 'max:5'],
        ]);

        $translation = $translator->translate($validated['text'], $validated['from'], $validated['to']);

        if ($translation === null) {
            return response()->json([
                'message' => 'Translation is temporarily unavailable.',
            ], 502);
        }

        return response()->json([
            'text'        => $validated['text'],
            'from'        => $validated['from'],
            'to'          => $validated['to'],
            'translation' => $translation,
        ]);
    }
}
