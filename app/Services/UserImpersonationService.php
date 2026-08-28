<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserImpersonationAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class UserImpersonationService
{
    public const SESSION_KEY = 'admin_impersonation';

    /**
     * Only an active, unambiguous administrator may start support access.
     */
    public function isEligibleAdministrator(User $user): bool
    {
        $user->loadMissing('details');
        $details = $user->details;

        if (! $details || $details->role !== 'admin' || ! (bool) $details->status) {
            return false;
        }

        return $user->isAdministrator() && ! $user->isEditor();
    }

    /**
     * Impersonation is restricted to active customer accounts without any
     * privileged Bouncer role.
     */
    public function isEligibleCustomer(User $user): bool
    {
        $user->loadMissing('details');
        $details = $user->details;

        if (! $details || $details->role !== 'customer' || ! (bool) $details->status) {
            return false;
        }

        return ! $user->isAn('admin')
            && ! $user->isEditor()
            && ! $user->isAn('superadmin');
    }

    public function canImpersonate(User $administrator, User $customer): bool
    {
        return $administrator->getKey() !== $customer->getKey()
            && $this->isEligibleAdministrator($administrator)
            && $this->isEligibleCustomer($customer);
    }

    public function hasState(Request $request): bool
    {
        return $request->hasSession()
            && $request->session()->has(self::SESSION_KEY);
    }

    public function state(Request $request): ?array
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! is_array($state)
            || ! isset(
                $state['audit_id'],
                $state['actor_id'],
                $state['target_id'],
                $state['actor_security_fingerprint'],
                $state['target_security_fingerprint'],
                $state['started_at'],
                $state['expires_at']
            )
            || ! is_string($state['audit_id'])
            || ! is_numeric($state['actor_id'])
            || ! is_numeric($state['target_id'])
            || ! is_string($state['actor_security_fingerprint'])
            || ! is_string($state['target_security_fingerprint'])
            || ! is_numeric($state['started_at'])
            || ! is_numeric($state['expires_at'])) {
            return null;
        }

        return $state;
    }

    /**
     * Return null when the active support session is valid, otherwise return
     * the audit reason that should terminate it.
     */
    public function invalidReason(Request $request): ?string
    {
        if (! $this->hasState($request)) {
            return null;
        }

        $state = $this->state($request);

        if (! $state) {
            return 'malformed_state';
        }

        if ((int) $state['expires_at'] <= now()->timestamp) {
            return 'expired';
        }

        $currentUser = $request->user();

        if (! $currentUser || (int) $currentUser->getKey() !== (int) $state['target_id']) {
            return 'target_mismatch';
        }

        $administrator = User::with('details')->find((int) $state['actor_id']);
        $customer = User::with('details')->find((int) $state['target_id']);

        if (! $administrator || ! $customer || ! $this->canImpersonate($administrator, $customer)) {
            return 'authorization_changed';
        }

        if (! hash_equals($state['actor_security_fingerprint'], $this->securityFingerprint($administrator))
            || ! hash_equals($state['target_security_fingerprint'], $this->securityFingerprint($customer))) {
            return 'credentials_changed';
        }

        return null;
    }

    public function start(Request $request, User $administrator, User $customer): void
    {
        if (! $this->canImpersonate($administrator, $customer)) {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        }

        if ($this->hasState($request)) {
            throw new \LogicException('An impersonation session is already active.');
        }

        $now = now();
        $ttlMinutes = max(5, min(240, (int) config('impersonation.ttl_minutes', 60)));
        $state = [
            'audit_id' => (string) Str::uuid(),
            'actor_id' => (int) $administrator->getKey(),
            'target_id' => (int) $customer->getKey(),
            'actor_security_fingerprint' => $this->securityFingerprint($administrator),
            'target_security_fingerprint' => $this->securityFingerprint($customer),
            'started_at' => $now->timestamp,
            'expires_at' => $now->copy()->addMinutes($ttlMinutes)->timestamp,
        ];

        $session = $request->session();
        $guard = Auth::guard('web');

        // Flush every identity-sensitive value (cart, checkout, intended URL,
        // two-factor state...) before changing the authenticated user.
        $session->invalidate();
        Cookie::queue(Cookie::forget($guard->getRecallerName()));
        $guard->login($customer, false);
        $session->put(self::SESSION_KEY, $state);
        $session->put('password_hash_web', $customer->getAuthPassword());
        $session->regenerateToken();

        $this->persistStart($request, $state);
        $this->logStart($request, $state);
    }

    /**
     * Restore the administrator. A null result means their access changed and
     * the impersonated session was securely terminated instead.
     */
    public function stop(Request $request): ?User
    {
        $state = $this->state($request);

        if (! $state
            || ! $request->user()
            || (int) $request->user()->getKey() !== (int) $state['target_id']) {
            return null;
        }

        $administrator = User::with('details')->find((int) $state['actor_id']);

        if (! $administrator
            || ! $this->isEligibleAdministrator($administrator)
            || ! hash_equals($state['actor_security_fingerprint'], $this->securityFingerprint($administrator))) {
            $this->terminate($request, 'actor_unavailable');

            return null;
        }

        $session = $request->session();
        $guard = Auth::guard('web');

        $session->invalidate();
        Cookie::queue(Cookie::forget($guard->getRecallerName()));
        $guard->login($administrator, false);
        $session->put('password_hash_web', $administrator->getAuthPassword());
        $session->regenerateToken();

        $this->persistEnd($state, 'stopped');
        $this->logEnd($request, $state, 'stopped');

        return $administrator;
    }

    /**
     * End impersonation without restoring privileged access.
     */
    public function terminate(Request $request, string $reason): void
    {
        $state = $this->state($request) ?: [
            'audit_id' => null,
            'actor_id' => null,
            'target_id' => $request->user() ? (int) $request->user()->getKey() : null,
        ];

        $this->persistEnd($state, $reason);
        $this->logEnd($request, $state, $reason);

        $guard = Auth::guard('web');
        Cookie::queue(Cookie::forget($guard->getRecallerName()));
        // End only this browser session without rotating the customer's
        // remember token (which would sign them out on their own devices).
        $guard->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function logDenied(Request $request, User $target): void
    {
        Log::warning('Administrator impersonation denied', $this->logContext($request, [
            'actor_id' => $request->user() ? (int) $request->user()->getKey() : null,
            'target_id' => (int) $target->getKey(),
        ]));
    }

    private function logStart(Request $request, array $state): void
    {
        Log::notice('Administrator impersonation started', $this->logContext($request, $this->auditStateContext($state)));
    }

    private function logEnd(Request $request, array $state, string $reason): void
    {
        Log::notice('Administrator impersonation ended', $this->logContext($request, array_merge($this->auditStateContext($state), [
            'reason' => $reason,
        ])));
    }

    private function logContext(Request $request, array $context): array
    {
        $userAgent = (string) $request->userAgent();

        return array_merge($context, [
            'ip' => $request->ip(),
            'user_agent_hash' => $userAgent !== '' ? hash('sha256', $userAgent) : null,
        ]);
    }

    private function auditStateContext(array $state): array
    {
        return array_intersect_key($state, array_flip([
            'audit_id',
            'actor_id',
            'target_id',
            'started_at',
            'expires_at',
        ]));
    }

    private function securityFingerprint(User $user): string
    {
        $credentialState = implode('|', [
            (string) $user->getKey(),
            (string) $user->email,
            (string) $user->getAuthPassword(),
            (string) $user->getRememberToken(),
            (string) $user->two_factor_secret,
        ]);

        return hash_hmac('sha256', $credentialState, (string) config('app.key'));
    }

    private function persistStart(Request $request, array $state): void
    {
        if (! Schema::hasTable('user_impersonation_audits')) {
            return;
        }

        try {
            $context = $this->logContext($request, $this->auditStateContext($state));

            UserImpersonationAudit::create([
                'audit_id' => $state['audit_id'],
                'actor_user_id' => $state['actor_id'],
                'target_user_id' => $state['target_id'],
                'started_at' => \Carbon\Carbon::createFromTimestamp((int) $state['started_at']),
                'expires_at' => \Carbon\Carbon::createFromTimestamp((int) $state['expires_at']),
                'ip_address' => $context['ip'],
                'user_agent_hash' => $context['user_agent_hash'],
            ]);
        } catch (Throwable $exception) {
            Log::error('Unable to persist administrator impersonation audit', [
                'audit_id' => $state['audit_id'],
                'exception' => get_class($exception),
            ]);
        }
    }

    private function persistEnd(array $state, string $reason): void
    {
        if (empty($state['audit_id']) || ! Schema::hasTable('user_impersonation_audits')) {
            return;
        }

        try {
            UserImpersonationAudit::query()
                ->where('audit_id', $state['audit_id'])
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'end_reason' => Str::limit($reason, 64, ''),
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            Log::error('Unable to close administrator impersonation audit', [
                'audit_id' => $state['audit_id'],
                'exception' => get_class($exception),
            ]);
        }
    }
}
