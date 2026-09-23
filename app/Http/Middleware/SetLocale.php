<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale');
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = str_starts_with((string) $request->getPreferredLanguage(['en', 'ar']), 'ar') ? 'ar' : config('app.locale', 'en');
        }
        app()->setLocale($locale);

        return $next($request);
    }
}
