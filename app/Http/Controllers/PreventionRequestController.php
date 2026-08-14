<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PreventionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $queryParams = [
            'email' => $this->authenticatedEmail(),
            'page' => $request->integer('page', 1),
            'per_page' => 15,
        ];

        $requests = [
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'total' => 0,
            'from' => null,
            'to' => null,
            'links' => [],
        ];
        $loadError = null;

        if (! $this->hasPreventionApiBaseUrl()) {
            $loadError = __('prevention_request.create_config_missing');
        } else {
            try {
                $response = $this->preventionApi()
                    ->get($this->preventionApiUrl('/demande-prevention/attestations'), array_filter($queryParams, fn ($value) => $value !== null && $value !== ''));

                if ($response->successful()) {
                    $requests = array_merge($requests, $response->json('data', []));
                } else {
                    $loadError = $response->json('message', __('prevention_request.list_failed'));
                }
            } catch (ConnectionException $exception) {
                report($exception);
                $loadError = __('prevention_request.list_unavailable');
            }
        }

        return view('prevention-requests.index', [
            'requests' => $requests,
            'loadError' => $loadError,
        ]);
    }

    public function create(): View
    {
        return view('prevention-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->withAuthenticatedEmail($request->validate($this->requestRules()));

        if (! $this->hasPreventionApiBaseUrl()) {
            return back()
                ->withInput()
                ->withErrors(['api' => __('prevention_request.create_config_missing')]);
        }

        try {
            $response = $this->preventionApi()
                ->post($this->preventionApiUrl('/demande-prevention/attestations'), $validated);
        } catch (ConnectionException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['api' => __('prevention_request.create_unavailable')]);
        }

        if ($response->status() === 422) {
            return back()->withInput()->withErrors($this->responseErrors($response, __('prevention_request.create_failed')));
        }

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors(['api' => $response->json('message', __('prevention_request.create_failed'))]);
        }

        return redirect()
            ->route('prevention-requests.index')
            ->with('success', __('prevention_request.create_success'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        try {
            $response = $this->preventionApi()
                ->get($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), [
                    'email' => $this->authenticatedEmail(),
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return redirect()
                ->route('prevention-requests.index')
                ->withErrors(['api' => __('prevention_request.show_unavailable')]);
        }

        if (! $response->successful()) {
            return redirect()
                ->route('prevention-requests.index')
                ->withErrors(['api' => $response->json('message', __('prevention_request.show_failed'))]);
        }

        return view('prevention-requests.edit', [
            'requestItem' => $response->json('data', []),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $this->withAuthenticatedEmail($request->validate($this->requestRules()));

        try {
            $response = $this->preventionApi()
                ->put($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), $validated);
        } catch (ConnectionException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['api' => __('prevention_request.update_unavailable')]);
        }

        if ($response->status() === 422) {
            return back()->withInput()->withErrors($this->responseErrors($response, __('prevention_request.update_failed')));
        }

        if (! $response->successful()) {
            return back()
                ->withInput()
                ->withErrors(['api' => $response->json('message', __('prevention_request.update_failed'))]);
        }

        return redirect()
            ->route('prevention-requests.index')
            ->with('success', __('prevention_request.update_success'));
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $response = $this->preventionApi()
                ->delete($this->preventionApiUrl("/demande-prevention/attestations/{$id}"), [
                    'email' => $this->authenticatedEmail(),
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return back()->withErrors(['api' => __('prevention_request.delete_unavailable')]);
        }

        if (! $response->successful()) {
            return back()->withErrors(['api' => $response->json('message', __('prevention_request.delete_failed'))]);
        }

        return redirect()
            ->route('prevention-requests.index')
            ->with('success', __('prevention_request.delete_success'));
    }

    public function structures(): JsonResponse
    {
        if (! $this->hasPreventionApiBaseUrl()) {
            return response()->json([
                'success' => false,
                'message' => __('prevention_request.structures_config_missing'),
                'data' => [],
            ], 500);
        }

        try {
            $response = $this->preventionApi()
                ->get($this->preventionApiUrl('/demande-prevention/structures'));
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

    private function requestRules(): array
    {
        return [
            'cin' => ['required', 'string', 'max:20'],
            'code_structure' => ['required', 'numeric'],
            'num_demande' => ['required', 'numeric'],
            'nom_prenom' => ['required', 'string', 'max:255'],
        ];
    }

    private function preventionApi(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.prevention_api.timeout', 10));

        $apiKey = (string) config('services.prevention_api.key');

        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'X-Demande-Prevention-Api-Key' => $apiKey,
            ]);
        }

        return $request;
    }

    private function preventionApiUrl(string $path): string
    {
        return rtrim((string) config('services.prevention_api.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function hasPreventionApiBaseUrl(): bool
    {
        return rtrim((string) config('services.prevention_api.base_url'), '/') !== '';
    }

    private function responseErrors(Response $response, string $fallback): array
    {
        return $response->json('errors', ['api' => $fallback]);
    }

    private function authenticatedEmail(): string
    {
        return (string) auth()->user()?->email;
    }

    private function withAuthenticatedEmail(array $payload): array
    {
        $payload['email'] = $this->authenticatedEmail();

        return $payload;
    }
}
