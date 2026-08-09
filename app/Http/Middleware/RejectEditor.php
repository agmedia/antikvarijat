<?php

namespace App\Http\Middleware;

use Bouncer;
use Closure;

class RejectEditor
{
    /**
     * Editors may manage content, but must not access business sales data.
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check() && Bouncer::is(auth()->user())->an('editor')) {
            abort(403);
        }

        return $next($request);
    }
}
