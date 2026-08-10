<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create the response after a successful login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator()) {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
