<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class RedirectAdministratorToBackend
{
    /**
     * Keep administrators out of the customer account area.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
