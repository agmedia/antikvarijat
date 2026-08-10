<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\CheckoutLoginRedirect;
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
            CheckoutLoginRedirect::forget($request);

            return redirect()->route('dashboard');
        }

        if ($redirect = CheckoutLoginRedirect::pull($request)) {
            $request->session()->forget('url.intended');

            return redirect()->to($redirect);
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
