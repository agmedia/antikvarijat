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
        $apiKey = $this->getApiKey();
        $audienceId = $this->getAudienceId();
        $serverPrefix = $this->getServerPrefix($apiKey);

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

    /**
     * Update Mailchimp tags for existing audience member.
     *
     * @param string $email
     * @param array<int,string> $activeTags
     * @param array<int,string> $inactiveTags
     *
     * @return array{ok:bool,error:?string}
     */
    public function updateMemberTags(string $email, array $activeTags = [], array $inactiveTags = []): array
    {
        $apiKey = $this->getApiKey();
        $audienceId = $this->getAudienceId();
        $serverPrefix = $this->getServerPrefix($apiKey);

        if ($apiKey === '' || $audienceId === '' || $serverPrefix === '') {
            return ['ok' => false, 'error' => 'Mailchimp nije konfiguriran (api key/audience/prefix).'];
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return ['ok' => false, 'error' => 'Email je prazan.'];
        }

        $memberHash = md5($email);
        $memberPath = "https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$audienceId}/members/{$memberHash}";

        $member = Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(20)
            ->get($memberPath);

        if ($member->status() === 404) {
            return ['ok' => false, 'error' => 'Kontakt ne postoji u Mailchimp audience listi.'];
        }

        if (! $member->successful()) {
            $error = (string) ($member->json('detail') ?? $member->body() ?? 'Greška pri dohvaćanju Mailchimp kontakta.');
            return ['ok' => false, 'error' => $error];
        }

        $tags = [];
        foreach ($activeTags as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $tags[] = ['name' => $tag, 'status' => 'active'];
            }
        }

        foreach ($inactiveTags as $tag) {
            $tag = trim((string) $tag);
            if ($tag !== '') {
                $tags[] = ['name' => $tag, 'status' => 'inactive'];
            }
        }

        if (empty($tags)) {
            return ['ok' => true, 'error' => null];
        }

        $tagResponse = Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post($memberPath . '/tags', ['tags' => $tags]);

        if ($tagResponse->successful()) {
            return ['ok' => true, 'error' => null];
        }

        $error = (string) ($tagResponse->json('detail') ?? $tagResponse->body() ?? 'Greška kod ažuriranja Mailchimp tagova.');

        return ['ok' => false, 'error' => $error];
    }

    /**
     * Mark contact as customer and clear abandoned cart tag.
     *
     * @param string $email
     *
     * @return array{ok:bool,error:?string}
     */
    public function markAsCustomer(string $email): array
    {
        return $this->updateMemberTags(
            $email,
            [(string) config('services.mailchimp.customer_tag', 'customer')],
            [(string) config('services.mailchimp.abandoned_cart_tag', 'abandoned_cart')]
        );
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

    private function getApiKey(): string
    {
        return (string) config('services.mailchimp.api_key');
    }

    private function getAudienceId(): string
    {
        return (string) config('services.mailchimp.audience_id');
    }

    private function getServerPrefix(string $apiKey): string
    {
        $serverPrefix = (string) config('services.mailchimp.server_prefix');

        if ($serverPrefix === '' && strpos($apiKey, '-') !== false) {
            $parts = explode('-', $apiKey);
            $serverPrefix = end($parts) ?: '';
        }

        return $serverPrefix;
    }
}
