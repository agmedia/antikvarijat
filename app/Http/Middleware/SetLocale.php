<?php

namespace App\Http\Middleware;

use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->segment(1) === config('localization.locales.en.prefix', 'en')
            ? LocaleHelper::ENGLISH_LOCALE
            : LocaleHelper::DEFAULT_LOCALE;

        app()->setLocale($locale);
        config(['app.locale' => $locale]);

        View::share('currentLocale', $locale);

        return $next($request);
    }
}
