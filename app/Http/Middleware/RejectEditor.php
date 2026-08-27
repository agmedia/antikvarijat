<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class RejectEditor
{
    /**
     * Editors may manage content, but must not access business sales data.
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof User && $user->isEditor()) {
            abort(403);
        }

        return $next($request);
    }
}
