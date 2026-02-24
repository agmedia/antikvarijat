<?php

namespace App\Services;

use App\Models\Back\Marketing\NewsletterSubscriber;
use Illuminate\Support\Facades\Http;

class MailchimpNewsletterService
{
    /**
     * @param NewsletterSubscriber $subscriber
     *
     * @return array{ok:bool,error:?string}
     */
    public function syncSubscriber(NewsletterSubscriber $subscriber): array
    {
        $apiKey = (string) config('services.mailchimp.api_key');
        $audienceId = (string) config('services.mailchimp.audience_id');
        $serverPrefix = (string) config('services.mailchimp.server_prefix');

        if ($apiKey === '' || $audienceId === '') {
            return ['ok' => false, 'error' => 'Mailchimp nije konfiguriran (api key ili audience id nedostaje).'];
        }

        if ($serverPrefix === '' && strpos($apiKey, '-') !== false) {
            $parts = explode('-', $apiKey);
            $serverPrefix = end($parts) ?: '';
        }

        if ($serverPrefix === '') {
            return ['ok' => false, 'error' => 'Mailchimp server prefix nedostaje.'];
        }

        $email = strtolower(trim((string) $subscriber->email));

        if ($email === '') {
            return ['ok' => false, 'error' => 'Subscriber email je prazan.'];
        }

        $memberHash = md5($email);

        $status = (string) config('services.mailchimp.subscribe_status', 'subscribed');

        if (! in_array($status, ['subscribed', 'pending'], true)) {
            $status = 'subscribed';
        }

        $payload = [
            'email_address' => $email,
            'status_if_new' => $status,
            'status' => $status,
        ];

        $firstName = trim((string) ($subscriber->first_name ?? ''));
        $lastName = trim((string) ($subscriber->last_name ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            $payload['merge_fields'] = [
                'FNAME' => $firstName,
                'LNAME' => $lastName,
            ];
        }

        $response = $this->sendMemberUpsert($apiKey, $serverPrefix, $audienceId, $memberHash, $payload);

        // Audience merge fields are often customized; retry once without merge fields.
        if (! $response->successful() && isset($payload['merge_fields'])) {
            $errorText = strtolower((string) ($response->json('detail') ?? ''));
            if (str_contains($errorText, 'merge field') || str_contains($errorText, 'invalid resource')) {
                unset($payload['merge_fields']);
                $response = $this->sendMemberUpsert($apiKey, $serverPrefix, $audienceId, $memberHash, $payload);
            }
        }

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        $detail = (string) ($response->json('detail') ?? '');
        $title = (string) ($response->json('title') ?? '');
        $statusCode = (string) ($response->json('status') ?? $response->status());
        $error = trim($statusCode . ' ' . $title . ': ' . $detail);

        if ($error === ':' || $error === '') {
            $error = (string) ($response->body() ?? 'Nepoznata Mailchimp greška.');
        }

        return ['ok' => false, 'error' => $error];
    }

    private function sendMemberUpsert(
        string $apiKey,
        string $serverPrefix,
        string $audienceId,
        string $memberHash,
        array $payload
    ) {
        return Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(20)
            ->put("https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$audienceId}/members/{$memberHash}", $payload);
    }
}
