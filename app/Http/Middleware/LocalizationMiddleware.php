<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: 1) Query param, 2) Session, 3) Cookie, 4) Default (fr)
        $locale = null;

        if ($request->has('locale')) {
            $locale = $request->query('locale');
        } elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        } elseif ($request->cookie('user_locale')) {
            $locale = $request->cookie('user_locale');
        }

        // Validate and fallback
        if (! $locale || ! in_array($locale, ['fr', 'ar'])) {
            $locale = 'fr';
        }

        // Persist
        Session::put('locale', $locale);
        App::setLocale($locale);

        $response = $next($request);

        return $response;
    }
}
