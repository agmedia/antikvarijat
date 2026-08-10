<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create the response after a successful two-factor challenge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator()) {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
