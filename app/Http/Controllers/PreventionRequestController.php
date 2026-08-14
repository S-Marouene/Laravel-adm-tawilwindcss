<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PreventionRequestController extends Controller
{
    public function create(): View
    {
        return view('prevention-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cin' => ['required', 'string', 'max:20'],
            'code_structure' => ['required', 'numeric'],
            'num_demande' => ['required', 'numeric'],
            'nom_prenom' => ['required', 'string', 'max:255'],
        ]);

        $baseUrl = rtrim((string) config('services.prevention_api.base_url'), '/');

        if ($baseUrl === '') {
            return back()
                ->withInput()
                ->withErrors(['api' => __('prevention_request.create_config_missing')]);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('services.prevention_api.timeout', 10))
                ->post($baseUrl . '/demande-prevention/attestations', $validated);
        } catch (ConnectionException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['api' => __('prevention_request.create_unavailable')]);
        }

        if ($response->status() === 422) {
            return back()
                ->withInput()
                ->withErrors($response->json('errors', ['api' => __('prevention_request.create_failed')]));
        }

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors(['api' => $response->json('message', __('prevention_request.create_failed'))]);
        }

        return redirect()
            ->route('prevention-requests.create')
            ->with('success', __('prevention_request.create_success'));
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
