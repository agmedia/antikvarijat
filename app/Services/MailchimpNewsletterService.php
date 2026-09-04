<?php

namespace App\Services;

use App\Models\Back\Marketing\NewsletterSubscriber;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MailchimpNewsletterService
{
    /**
     * Check whether all values required to contact Mailchimp are present.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey() !== ''
            && $this->audienceId() !== ''
            && $this->serverPrefix() !== '';
    }

    /**
     * Verify that the configured API key can access the configured audience.
     * The result is cached so each browser-side sync batch does not add another
     * Mailchimp request.
     *
     * @return array{ok:bool,error:?string,audience_name:?string}
     */
    public function connectionStatus(bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'Mailchimp nije konfiguriran. Nedostaje API ključ, server prefix ili Audience ID.',
                'audience_name' => null,
            ];
        }

        $cacheKey = 'mailchimp_newsletter_connection_' . hash('sha256', implode('|', [
            $this->serverPrefix(),
            $this->audienceId(),
            $this->apiKey(),
        ]));

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            try {
                $response = $this->request()->get(
                    $this->baseUrl() . '/lists/' . rawurlencode($this->audienceId()),
                    ['fields' => 'id,name']
                );
            } catch (Throwable $e) {
                return [
                    'ok' => false,
                    'error' => 'Mailchimp trenutno nije dostupan. Pokušaj ponovno za nekoliko minuta.',
                    'audience_name' => null,
                ];
            }

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'error' => $this->responseError($response, true),
                    'audience_name' => null,
                ];
            }

            return [
                'ok' => true,
                'error' => null,
                'audience_name' => trim((string) $response->json('name')) ?: null,
            ];
        });
    }

    /**
     * Add or update one local subscriber in the configured Mailchimp audience.
     * Existing Mailchimp unsubscribe/cleaned states are preserved: status is
     * supplied only when a member is new.
     *
     * @return array{ok:bool,error:?string,stop?:bool}
     */
    public function syncSubscriber(NewsletterSubscriber $subscriber): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'Mailchimp nije konfiguriran. Nedostaje API ključ, server prefix ili Audience ID.',
                'stop' => true,
            ];
        }

        $email = strtolower(trim((string) $subscriber->email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'error' => 'E-mail adresa nije valjana.'];
        }

        $status = strtolower(trim((string) config('services.mailchimp.subscribe_status', 'subscribed')));
        if (! in_array($status, ['subscribed', 'pending'], true)) {
            $status = 'subscribed';
        }

        $payload = [
            'email_address' => $email,
            'status_if_new' => $status,
        ];

        $mergeFields = array_filter([
            'FNAME' => trim((string) $subscriber->first_name),
            'LNAME' => trim((string) $subscriber->last_name),
        ], static function ($value) {
            return $value !== '';
        });

        if ($mergeFields !== []) {
            $payload['merge_fields'] = $mergeFields;
        }

        try {
            $response = $this->sendMemberUpsert($email, $payload);

            // Custom audiences do not always use the standard FNAME/LNAME
            // fields. Retry the same upsert without optional merge fields.
            if (! $response->successful() && isset($payload['merge_fields']) && $this->isMergeFieldError($response)) {
                unset($payload['merge_fields']);
                $response = $this->sendMemberUpsert($email, $payload);
            }
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Mailchimp trenutno nije dostupan. Pokušaj ponovno za nekoliko minuta.',
                'stop' => true,
            ];
        }

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        return [
            'ok' => false,
            'error' => $this->responseError($response),
            'stop' => $this->shouldStopAfter($response),
        ];
    }

    /**
     * Archive one subscriber in Mailchimp before it is removed locally.
     * A missing Mailchimp member is already in the desired state, so HTTP 404
     * is intentionally treated as a successful archive.
     *
     * @return array{ok:bool,error:?string,stop?:bool}
     */
    public function archiveSubscriber(NewsletterSubscriber $subscriber): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'Mailchimp nije konfiguriran. Nedostaje API ključ, server prefix ili Audience ID.',
                'stop' => true,
            ];
        }

        $email = strtolower(trim((string) $subscriber->email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['ok' => false, 'error' => 'E-mail adresa nije valjana.'];
        }

        try {
            $response = $this->request()->delete($this->memberUrl($email));
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Mailchimp trenutno nije dostupan. Pokušaj ponovno za nekoliko minuta.',
                'stop' => true,
            ];
        }

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        if ($response->status() === 404) {
            // A missing member is safe to treat as already archived only while
            // the configured audience itself can still be verified.
            $connection = $this->connectionStatus(true);

            if ($connection['ok']) {
                return ['ok' => true, 'error' => null];
            }

            return [
                'ok' => false,
                'error' => 'Arhiviranje kontakta nije potvrđeno: ' . (string) $connection['error'],
                'stop' => true,
            ];
        }

        return [
            'ok' => false,
            'error' => $this->responseError($response),
            'stop' => $this->shouldStopAfter($response),
        ];
    }

    private function sendMemberUpsert(string $email, array $payload): Response
    {
        return $this->request()->put(
            $this->memberUrl($email) . '?skip_merge_validation=true',
            $payload
        );
    }

    private function memberUrl(string $email): string
    {
        return $this->baseUrl()
            . '/lists/' . rawurlencode($this->audienceId())
            . '/members/' . md5($email);
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth('anystring', $this->apiKey())
            ->acceptJson()
            ->withOptions(['connect_timeout' => 5])
            ->timeout(15);
    }

    private function baseUrl(): string
    {
        return 'https://' . $this->serverPrefix() . '.api.mailchimp.com/3.0';
    }

    private function apiKey(): string
    {
        return trim((string) config('services.mailchimp.api_key'));
    }

    private function audienceId(): string
    {
        return trim((string) config('services.mailchimp.audience_id'));
    }

    private function serverPrefix(): string
    {
        $prefix = trim((string) config('services.mailchimp.server_prefix'));

        if ($prefix === '') {
            $apiKey = $this->apiKey();
            if (strpos($apiKey, '-') !== false) {
                $parts = explode('-', $apiKey);
                $prefix = (string) end($parts);
            }
        }

        return preg_match('/^[a-z0-9-]+$/i', $prefix) === 1 ? strtolower($prefix) : '';
    }

    private function isMergeFieldError(Response $response): bool
    {
        $error = strtolower(trim(implode(' ', array_filter([
            (string) $response->json('title'),
            (string) $response->json('detail'),
        ]))));

        return strpos($error, 'merge field') !== false
            || strpos($error, 'fname') !== false
            || strpos($error, 'lname') !== false;
    }

    private function shouldStopAfter(Response $response): bool
    {
        return in_array($response->status(), [401, 403, 404, 429, 500, 502, 503, 504], true);
    }

    private function responseError(Response $response, bool $connectionCheck = false): string
    {
        if ($response->status() === 401) {
            return 'Mailchimp API ključ nije prihvaćen (HTTP 401). Postavi novi MAILCHIMP_API_KEY u produkcijskom .env-u pa očisti cache.';
        }

        if ($connectionCheck && $response->status() === 404) {
            return 'Mailchimp Audience ID nije pronađen (HTTP 404). Provjeri Audience ID.';
        }

        if ($response->status() === 429) {
            return 'Mailchimp je privremeno ograničio broj zahtjeva (HTTP 429). Pokušaj ponovno kasnije.';
        }

        $detail = trim(strip_tags((string) $response->json('detail')));
        $title = trim(strip_tags((string) $response->json('title')));
        $message = trim(implode(': ', array_filter([$title, $detail])));

        if ($message === '') {
            $message = 'Mailchimp je vratio neočekivanu grešku.';
        }

        return 'HTTP ' . $response->status() . ' — ' . Str::limit($message, 500, '…');
    }
}
