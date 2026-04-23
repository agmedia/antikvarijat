<?php

namespace Tests\Feature;

use App\Models\Back\Orders\Order;
use App\Services\MailchimpNewsletterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailchimpNewsletterServiceTest extends TestCase
{
    public function test_it_syncs_customer_merge_fields_from_an_order(): void
    {
        Cache::flush();

        config([
            'services.mailchimp.api_key' => 'test-key-us17',
            'services.mailchimp.server_prefix' => 'us17',
            'services.mailchimp.audience_id' => 'audience123',
            'services.mailchimp.customer_tag' => 'customer',
            'services.mailchimp.abandoned_cart_tag' => 'abandoned_cart',
        ]);

        $order = (new Order())->forceFill([
            'payment_email' => 'customer@example.com',
            'payment_fname' => 'Ana',
            'payment_lname' => 'Anić',
            'payment_address' => 'Palmotićeva 28',
            'payment_city' => 'Zagreb',
            'payment_zip' => '10000',
            'payment_state' => 'Croatia',
            'payment_phone' => '+385911112223',
            'company' => 'Biblos d.o.o.',
            'birthday_year' => '1984-07-12',
        ]);

        $payloads = $this->fakeMailchimpRequests(md5('customer@example.com'));

        $service = new MailchimpNewsletterService();
        $result = $service->syncCustomerFromOrder($order);

        $this->assertTrue($result['ok'], $result['error'] ?? 'Mailchimp sync nije uspio.');
        $this->assertNotNull($payloads->patch);
        $this->assertNotNull($payloads->tags);

        $this->assertSame('Ana', $payloads->patch['merge_fields']['FNAME']);
        $this->assertSame('Anić', $payloads->patch['merge_fields']['LNAME']);
        $this->assertSame('+385911112223', $payloads->patch['merge_fields']['PHONE']);
        $this->assertSame('Biblos d.o.o.', $payloads->patch['merge_fields']['COMPANY']);
        $this->assertSame('10000', $payloads->patch['merge_fields']['MERGE7']);
        $this->assertSame('Zagreb', $payloads->patch['merge_fields']['MERGE8']);
        $this->assertSame('1984-07-12', $payloads->patch['merge_fields']['MERGE9']);
        $this->assertSame('existing-value', $payloads->patch['merge_fields']['EXISTING']);
        $this->assertSame([
            'addr1' => 'Palmotićeva 28',
            'city' => 'Zagreb',
            'zip' => '10000',
            'country' => 'Croatia',
        ], $payloads->patch['merge_fields']['ADDRESS']);
        $this->assertSame([
            ['name' => 'customer', 'status' => 'active'],
            ['name' => 'abandoned_cart', 'status' => 'inactive'],
        ], $payloads->tags['tags']);
    }

    public function test_it_keeps_birthday_year_optional(): void
    {
        Cache::flush();

        config([
            'services.mailchimp.api_key' => 'test-key-us17',
            'services.mailchimp.server_prefix' => 'us17',
            'services.mailchimp.audience_id' => 'audience123',
            'services.mailchimp.customer_tag' => 'customer',
            'services.mailchimp.abandoned_cart_tag' => 'abandoned_cart',
        ]);

        $order = (new Order())->forceFill([
            'payment_email' => 'customer@example.com',
            'payment_fname' => 'Ana',
            'payment_lname' => 'Anić',
            'payment_address' => 'Palmotićeva 28',
            'payment_city' => 'Zagreb',
            'payment_zip' => '10000',
            'payment_state' => 'Croatia',
            'payment_phone' => '+385911112223',
            'company' => 'Biblos d.o.o.',
            'birthday_year' => null,
        ]);

        $payloads = $this->fakeMailchimpRequests(md5('customer@example.com'));

        $service = new MailchimpNewsletterService();
        $result = $service->syncCustomerFromOrder($order);

        $this->assertTrue($result['ok'], $result['error'] ?? 'Mailchimp sync nije uspio.');
        $this->assertArrayNotHasKey('MERGE9', $payloads->patch['merge_fields']);
    }

    /**
     * @return \stdClass
     */
    private function fakeMailchimpRequests(string $memberHash): \stdClass
    {
        $payloads = (object) [
            'patch' => null,
            'tags' => null,
        ];
        $baseUrl = 'https://us17.api.mailchimp.com/3.0/lists/audience123';

        Http::fake(function ($request) use ($baseUrl, $memberHash, $payloads) {
            if ($request->method() === 'GET' && str_starts_with($request->url(), $baseUrl . '/merge-fields')) {
                return Http::response([
                    'merge_fields' => [
                        ['name' => 'First Name', 'tag' => 'FNAME'],
                        ['name' => 'Last Name', 'tag' => 'LNAME'],
                        ['name' => 'Address', 'tag' => 'ADDRESS'],
                        ['name' => 'Phone Number', 'tag' => 'PHONE'],
                        ['name' => 'Company', 'tag' => 'COMPANY'],
                        ['name' => 'ZIP', 'tag' => 'MERGE7'],
                        ['name' => 'City', 'tag' => 'MERGE8'],
                        ['name' => 'BirthdayYear', 'tag' => 'MERGE9'],
                    ],
                ], 200);
            }

            if ($request->method() === 'GET' && $request->url() === $baseUrl . '/members/' . $memberHash) {
                return Http::response([
                    'merge_fields' => [
                        'EXISTING' => 'existing-value',
                    ],
                ], 200);
            }

            if ($request->method() === 'PATCH' && str_starts_with($request->url(), $baseUrl . '/members/' . $memberHash . '?skip_merge_validation=true')) {
                $payloads->patch = $request->data();

                return Http::response([], 200);
            }

            if ($request->method() === 'POST' && $request->url() === $baseUrl . '/members/' . $memberHash . '/tags') {
                $payloads->tags = $request->data();

                return Http::response([], 200);
            }

            return Http::response([], 404);
        });

        return $payloads;
    }
}
