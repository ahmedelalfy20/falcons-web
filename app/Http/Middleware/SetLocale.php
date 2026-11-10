<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Language resolution, in order:
 *   1. ?lang=ar|en in the URL — gives every page a crawlable URL per language
 *      (used by hreflang, canonical and the sitemap) and is remembered.
 *   2. The visitor's saved choice (session).
 *   3. The browser's Accept-Language header, then the app default.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next)
    {
        $query = $request->query('lang');
        if (is_string($query) && in_array($query, self::SUPPORTED, true)) {
            $locale = $query;
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale);
            }
        } else {
            $locale = $request->hasSession() ? $request->session()->get('locale') : null;
            if (! in_array($locale, self::SUPPORTED, true)) {
                $locale = str_starts_with((string) $request->getPreferredLanguage(['en', 'ar']), 'ar') ? 'ar' : config('app.locale', 'en');
            }
        }
        app()->setLocale($locale);

        $response = $next($request);
        // Content depends on the chosen language when no ?lang is given.
        if (! $query && method_exists($response, 'setVary')) {
            $response->setVary('Accept-Language', false);
        }

        return $response;
    }
}
