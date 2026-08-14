<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PreventionRequestController extends Controller
{
    public function create(): View
    {
        return view('prevention-requests.create');
    }

    public function structures(): JsonResponse
    {
        $baseUrl = rtrim((string) config('services.prevention_api.base_url'), '/');

        if ($baseUrl === '') {
            return response()->json([
                'success' => false,
                'message' => __('prevention_request.structures_config_missing'),
                'data' => [],
            ], 500);
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.prevention_api.timeout', 10))
                ->get($baseUrl . '/demande-prevention/structures');
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => __('prevention_request.structures_unavailable'),
                'data' => [],
            ], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => __('prevention_request.structures_unavailable'),
                'data' => [],
            ], $response->status() >= 400 ? $response->status() : 502);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json('data', []),
        ]);
    }
}
