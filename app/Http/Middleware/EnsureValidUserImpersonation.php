<?php

namespace App\Http\Middleware;

use App\Helpers\LocaleHelper;
use App\Services\UserImpersonationService;
use Closure;
use Illuminate\Http\Request;

class EnsureValidUserImpersonation
{
    private $impersonation;

    public function __construct(UserImpersonationService $impersonation)
    {
        $this->impersonation = $impersonation;
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->impersonation->hasState($request)) {
            return $next($request);
        }

        $reason = $this->impersonation->invalidReason($request);

        if ($reason !== null) {
            $this->impersonation->terminate($request, $reason);

            if ($request->expectsJson()) {
                return response()->json(['message' => __('front.impersonation.expired')], 401);
            }

            return redirect()
                ->route('login')
                ->with('error', __('front.impersonation.expired'));
        }

        // Impersonated customers must never enter the back office, even if a
        // legacy account is missing its Bouncer customer assignment.
        if ($request->is('admin') || $request->is('admin/*')) {
            return redirect()->to(LocaleHelper::route('moj-racun'));
        }

        // A normal logout ends the complete support session; it must never
        // silently restore administrator privileges.
        if ($request->routeIs('logout')) {
            $this->impersonation->terminate($request, 'logout');

            if ($request->expectsJson()) {
                return response()->noContent();
            }

            return redirect()->to(LocaleHelper::route('index'));
        }

        return $next($request);
    }
}
