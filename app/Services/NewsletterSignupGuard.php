<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class NewsletterSignupGuard
{
    public const TIMING_ALLOWED = 'allowed';
    public const TIMING_TOO_FAST = 'too_fast';
    public const TIMING_INVALID = 'invalid';

    private const MINIMUM_SECONDS = 2;
    private const MAXIMUM_SECONDS = 7200;

    public function issueToken(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /**
     * Treat arrays and any non-empty value as a filled trap. This keeps a
     * malformed bot request from causing a type error before validation.
     *
     * @param mixed $value
     */
    public function honeypotIsFilled($value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        return trim((string) $value) !== '';
    }

    /**
     * The encrypted timestamp prevents clients from forging a believable
     * form age. Very fast submissions are silently discarded, while an
     * invalid or expired form can be shown a useful refresh message.
     *
     * @param mixed $token
     */
    public function timingResult($token): string
    {
        if (! is_string($token) || $token === '' || strlen($token) > 1024) {
            return self::TIMING_INVALID;
        }

        try {
            $issuedAt = Crypt::decryptString($token);
        } catch (DecryptException $e) {
            return self::TIMING_INVALID;
        }

        if (! ctype_digit($issuedAt)) {
            return self::TIMING_INVALID;
        }

        $age = now()->getTimestamp() - (int) $issuedAt;

        if ($age < self::MINIMUM_SECONDS) {
            return self::TIMING_TOO_FAST;
        }

        if ($age > self::MAXIMUM_SECONDS) {
            return self::TIMING_INVALID;
        }

        return self::TIMING_ALLOWED;
    }
}
