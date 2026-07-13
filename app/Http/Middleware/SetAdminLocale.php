<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filament admin panel locale — independent from the storefront's URL-prefixed
 * locale (SetLocale). Admin has no /vi|/en path segment, so the choice is
 * remembered in session instead, set via the topbar switcher (see routes()
 * in AdminPanelProvider).
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('admin_locale', config('app.locale'));

        if (! in_array($locale, config('app.supported_locales'), true)) {
            $locale = config('app.fallback_locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
