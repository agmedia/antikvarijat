<?php

namespace App\Services;

use App\Models\Back\Orders\Order;
use App\Models\Back\Marketing\NewsletterSubscriber;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailchimpNewsletterService
{
    private ?array $resolvedMergeFieldTags = null;

    public function isConfigured(): bool
    {
        $apiKey = $this->getApiKey();

        return $apiKey !== ''
            && $this->getAudienceId() !== ''
            && $this->getServerPrefix($apiKey) !== '';
    }

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

        $mergeFields = $this->buildSubscriberMergeFields($subscriber);

        if (! empty($mergeFields)) {
            $payload['merge_fields'] = $mergeFields;
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
     * @param array<string,mixed> $mergeFields
     *
     * @return array{ok:bool,error:?string}
     */
    public function updateMemberTags(
        string $email,
        array $activeTags = [],
        array $inactiveTags = [],
        array $mergeFields = []
    ): array
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

        $mergeUpdateError = null;
        $mergeFields = $this->filterMergeFields($mergeFields);

        if (! empty($mergeFields)) {
            $patchResponse = Http::withBasicAuth('anystring', $apiKey)
                ->acceptJson()
                ->timeout(20)
                ->patch($memberPath . '?skip_merge_validation=true', [
                    'merge_fields' => array_merge((array) $member->json('merge_fields', []), $mergeFields),
                ]);

            if (! $patchResponse->successful()) {
                $mergeUpdateError = (string) ($patchResponse->json('detail') ?? $patchResponse->body() ?? 'Greška kod ažuriranja Mailchimp merge fieldova.');
            }
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
            return ['ok' => $mergeUpdateError === null, 'error' => $mergeUpdateError];
        }

        $tagResponse = Http::withBasicAuth('anystring', $apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post($memberPath . '/tags', ['tags' => $tags]);

        if ($tagResponse->successful()) {
            return ['ok' => $mergeUpdateError === null, 'error' => $mergeUpdateError];
        }

        $error = (string) ($tagResponse->json('detail') ?? $tagResponse->body() ?? 'Greška kod ažuriranja Mailchimp tagova.');

        if ($mergeUpdateError !== null) {
            $error = $mergeUpdateError . ' | ' . $error;
        }

        return ['ok' => false, 'error' => $error];
    }

    /**
     * Mark contact as customer and clear abandoned cart tag.
     *
     * @param string $email
     * @param array<string,mixed> $mergeFields
     *
     * @return array{ok:bool,error:?string}
     */
    public function markAsCustomer(string $email, array $mergeFields = []): array
    {
        return $this->updateMemberTags(
            $email,
            [(string) config('services.mailchimp.customer_tag', 'customer')],
            [(string) config('services.mailchimp.abandoned_cart_tag', 'abandoned_cart')],
            $mergeFields
        );
    }

    /**
     * Sync customer merge fields and tags for a paid order.
     *
     * @return array{ok:bool,error:?string}
     */
    public function syncCustomerFromOrder(Order $order): array
    {
        $email = strtolower(trim((string) $order->payment_email));

        if ($email === '') {
            return ['ok' => false, 'error' => 'Order nema payment email za customer sync.'];
        }

        return $this->markAsCustomer($email, $this->buildCustomerMergeFieldsFromOrder($order));
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

    /**
     * @return array<string,string>
     */
    private function buildSubscriberMergeFields(NewsletterSubscriber $subscriber): array
    {
        $tags = $this->resolveMergeFieldTags();
        $mergeFields = [];
        $firstName = trim((string) ($subscriber->first_name ?? ''));
        $lastName = trim((string) ($subscriber->last_name ?? ''));

        if ($firstName !== '' && ! empty($tags['first_name'])) {
            $mergeFields[$tags['first_name']] = $firstName;
        }

        if ($lastName !== '' && ! empty($tags['last_name'])) {
            $mergeFields[$tags['last_name']] = $lastName;
        }

        return $mergeFields;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildCustomerMergeFieldsFromOrder(Order $order): array
    {
        $tags = $this->resolveMergeFieldTags();
        $mergeFields = [];

        $firstName = trim((string) $order->payment_fname);
        $lastName = trim((string) $order->payment_lname);
        $phone = trim((string) $order->payment_phone);
        $company = trim((string) $order->company);
        $zip = trim((string) $order->payment_zip);
        $city = trim((string) $order->payment_city);
        $birthdayYear = $this->formatBirthdayYearForMailchimp($order->birthday_year ?? null);

        if ($firstName !== '' && ! empty($tags['first_name'])) {
            $mergeFields[$tags['first_name']] = $firstName;
        }

        if ($lastName !== '' && ! empty($tags['last_name'])) {
            $mergeFields[$tags['last_name']] = $lastName;
        }

        if (! empty($tags['address'])) {
            $address = $this->filterMergeFields([
                'addr1' => trim((string) $order->payment_address),
                'city' => $city,
                'zip' => $zip,
                'country' => trim((string) $order->payment_state),
            ]);

            if (! empty($address)) {
                $mergeFields[$tags['address']] = $address;
            }
        }

        if ($phone !== '' && ! empty($tags['phone'])) {
            $mergeFields[$tags['phone']] = $phone;
        }

        if ($company !== '' && ! empty($tags['company'])) {
            $mergeFields[$tags['company']] = $company;
        }

        if ($zip !== '' && ! empty($tags['zip'])) {
            $mergeFields[$tags['zip']] = $zip;
        }

        if ($city !== '' && ! empty($tags['city'])) {
            $mergeFields[$tags['city']] = $city;
        }

        if ($birthdayYear !== null && ! empty($tags['birthday_year'])) {
            $mergeFields[$tags['birthday_year']] = $birthdayYear;
        }

        return $mergeFields;
    }

    /**
     * @return array<string,string>
     */
    private function resolveMergeFieldTags(): array
    {
        if ($this->resolvedMergeFieldTags !== null) {
            return $this->resolvedMergeFieldTags;
        }

        $fallbacks = [
            'first_name' => 'FNAME',
            'last_name' => 'LNAME',
            'address' => 'ADDRESS',
            'phone' => 'PHONE',
            'company' => 'COMPANY',
            'zip' => 'MERGE7',
            'city' => 'MERGE8',
            'birthday_year' => 'MERGE9',
        ];

        $configuredLabels = (array) config('services.mailchimp.merge_field_labels', []);
        $availableTags = $this->fetchMergeFieldTags();
        $resolved = [];

        foreach ($fallbacks as $key => $fallbackTag) {
            $label = (string) Arr::get($configuredLabels, $key, '');
            $normalizedLabel = $this->normalizeMergeFieldLabel($label);

            $resolved[$key] = $availableTags[$normalizedLabel] ?? $fallbackTag;
        }

        return $this->resolvedMergeFieldTags = $resolved;
    }

    /**
     * @return array<string,string>
     */
    private function fetchMergeFieldTags(): array
    {
        $apiKey = $this->getApiKey();
        $audienceId = $this->getAudienceId();
        $serverPrefix = $this->getServerPrefix($apiKey);

        if ($apiKey === '' || $audienceId === '' || $serverPrefix === '') {
            return [];
        }

        return Cache::remember(
            'mailchimp_merge_fields_' . md5($serverPrefix . ':' . $audienceId),
            now()->addMinutes(30),
            function () use ($apiKey, $audienceId, $serverPrefix) {
                $response = Http::withBasicAuth('anystring', $apiKey)
                    ->acceptJson()
                    ->timeout(20)
                    ->get("https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$audienceId}/merge-fields", [
                        'count' => 100,
                    ]);

                if (! $response->successful()) {
                    Log::warning('Mailchimp merge field lookup failed', [
                        'audience_id' => $audienceId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $resolved = [];

                foreach ((array) $response->json('merge_fields', []) as $field) {
                    $label = $this->normalizeMergeFieldLabel((string) Arr::get($field, 'name', ''));
                    $tag = trim((string) Arr::get($field, 'tag', ''));

                    if ($label !== '' && $tag !== '') {
                        $resolved[$label] = $tag;
                    }
                }

                return $resolved;
            }
        );
    }

    /**
     * @param array<string,mixed> $fields
     *
     * @return array<string,mixed>
     */
    private function filterMergeFields(array $fields): array
    {
        return array_filter($fields, function ($value) {
            if (is_array($value)) {
                return ! empty($this->filterMergeFields($value));
            }

            return $value !== null && trim((string) $value) !== '';
        });
    }

    private function formatBirthdayYearForMailchimp($birthday): ?string
    {
        $birthday = trim((string) $birthday);

        if ($birthday === '') {
            return null;
        }

        try {
            return Carbon::parse($birthday)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeMergeFieldLabel(string $label): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', trim($label)));
    }
}
