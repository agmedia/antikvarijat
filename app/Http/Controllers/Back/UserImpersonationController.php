<?php

namespace App\Http\Controllers\Back;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserImpersonationController extends Controller
{
    public function start(
        Request $request,
        User $user,
        UserImpersonationService $impersonation
    ): RedirectResponse {
        $administrator = $request->user();

        if (! $administrator || ! $impersonation->canImpersonate($administrator, $user)) {
            $impersonation->logDenied($request, $user);
            abort(403);
        }

        if ($impersonation->hasState($request)) {
            abort(409, 'Impersonation is already active.');
        }

        $impersonation->start($request, $administrator, $user);

        return redirect()
            ->to(LocaleHelper::route('moj-racun'))
            ->with('success', __('front.impersonation.started'));
    }

    public function stop(Request $request, UserImpersonationService $impersonation): RedirectResponse
    {
        $state = $impersonation->state($request);

        if (! $state
            || ! $request->user()
            || (int) $request->user()->getKey() !== (int) $state['target_id']) {
            abort(403);
        }

        $administrator = $impersonation->stop($request);

        if (! $administrator) {
            return redirect()
                ->route('login')
                ->with('error', __('front.impersonation.restore_failed'));
        }

        return redirect()
            ->route('users')
            ->with('success', __('front.impersonation.restored'));
    }
}
