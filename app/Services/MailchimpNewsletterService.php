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

        $payload = [
            'email_address' => $email,
            'status_if_new' => 'subscribed',
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => (string) ($subscriber->first_name ?? ''),
                'LNAME' => (string) ($subscriber->last_name ?? ''),
            ],
        ];

        $response = Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(20)
            ->put("https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$audienceId}/members/{$memberHash}", $payload);

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        $error = (string) ($response->json('detail') ?? $response->body() ?? 'Nepoznata Mailchimp greška.');

        return ['ok' => false, 'error' => $error];
    }
}
