<?php

namespace Tests\Feature;

use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\User;
use App\Services\MailchimpNewsletterService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MailchimpNewsletterSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.mailchimp.api_key' => 'test-secret-us7',
            'services.mailchimp.server_prefix' => '',
            'services.mailchimp.audience_id' => 'audience-123',
            'services.mailchimp.subscribe_status' => 'subscribed',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('order_id')->default(0);
            $table->string('source')->default('unknown');
            $table->tinyInteger('gdpr')->default(1);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('mailchimp_synced_at')->nullable();
            $table->text('mailchimp_last_error')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_service_upserts_a_configured_subscriber_without_overwriting_existing_mailchimp_status(): void
    {
        Http::fake([
            'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/*' => Http::response(['id' => 'member-id'], 200),
        ]);

        $subscriber = $this->subscriber([
            'email' => ' Existing.Member@Example.test ',
            'first_name' => 'Ana',
            'last_name' => 'Anić',
        ]);

        $service = app(MailchimpNewsletterService::class);

        $this->assertTrue($service->isConfigured());
        $this->assertSame(['ok' => true, 'error' => null], $service->syncSubscriber($subscriber));

        $normalizedEmail = 'existing.member@example.test';
        Http::assertSent(function (ClientRequest $request) use ($normalizedEmail) {
            $payload = $request->data();

            return $request->method() === 'PUT'
                && $request->url() === 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/' . md5($normalizedEmail) . '?skip_merge_validation=true'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('anystring:test-secret-us7'))
                && ($payload['email_address'] ?? null) === $normalizedEmail
                && ($payload['status_if_new'] ?? null) === 'subscribed'
                && ! array_key_exists('status', $payload)
                && ($payload['merge_fields'] ?? null) === [
                    'FNAME' => 'Ana',
                    'LNAME' => 'Anić',
                ];
        });
        Http::assertSentCount(1);
    }

    public function test_service_reports_connection_failure_without_exposing_credentials(): void
    {
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'GET'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123') === 0) {
                return Http::response([
                    'title' => 'API Key Invalid',
                    'detail' => 'Your API key may be invalid.',
                ], 401);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });

        $status = app(MailchimpNewsletterService::class)->connectionStatus(true);

        $this->assertFalse($status['ok']);
        $this->assertNull($status['audience_name']);
        $this->assertStringContainsString('401', $status['error']);
        $this->assertStringNotContainsString('test-secret-us7', $status['error']);
        Http::assertSentCount(1);
    }

    public function test_unconfigured_service_does_not_attempt_a_mailchimp_request(): void
    {
        config([
            'services.mailchimp.api_key' => '',
            'services.mailchimp.server_prefix' => '',
            'services.mailchimp.audience_id' => '',
        ]);
        Http::fake();

        $service = app(MailchimpNewsletterService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->connectionStatus(true)['ok']);
        $this->assertFalse($service->syncSubscriber($this->subscriber())['ok']);
        Http::assertNothingSent();
    }

    public function test_controller_batch_records_success_and_failure_and_excludes_ineligible_subscribers(): void
    {
        Carbon::setTestNow('2026-08-28 07:15:00');

        $successful = $this->subscriber(['email' => 'success@example.test']);
        $failed = $this->subscriber(['email' => 'failure@example.test']);
        $alreadySynced = $this->subscriber([
            'email' => 'already@example.test',
            'mailchimp_synced_at' => '2026-08-27 10:00:00',
        ]);
        $withoutGdpr = $this->subscriber([
            'email' => 'no-gdpr@example.test',
            'gdpr' => 0,
        ]);
        $inactive = $this->subscriber([
            'email' => 'inactive@example.test',
            'status' => 0,
        ]);

        $this->fakeMailchimp(['failure@example.test']);

        $response = $this->actingAs($this->admin())->withoutMiddleware()->postJson(route('newsletter.mailchimp.sync'), [
            'batch' => 20,
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => 2,
                'synced' => 1,
                'failed' => 1,
                'pending' => 1,
            ])
            ->assertJsonStructure([
                'ok',
                'message',
                'finished',
                'processed',
                'synced',
                'failed',
                'last_id',
                'total',
                'pending',
            ]);

        $successful->refresh();
        $failed->refresh();
        $alreadySynced->refresh();
        $withoutGdpr->refresh();
        $inactive->refresh();

        $this->assertSame('2026-08-28 07:15:00', $successful->mailchimp_synced_at->format('Y-m-d H:i:s'));
        $this->assertNull($successful->mailchimp_last_error);
        $this->assertNull($failed->mailchimp_synced_at);
        $this->assertStringContainsString('Audience rejected this address', (string) $failed->mailchimp_last_error);
        $this->assertSame('2026-08-27 10:00:00', $alreadySynced->mailchimp_synced_at->format('Y-m-d H:i:s'));
        $this->assertNull($withoutGdpr->mailchimp_synced_at);
        $this->assertNull($inactive->mailchimp_synced_at);

        $memberRequests = $this->memberRequests();
        $this->assertCount(2, $memberRequests);
        $requestedUrls = $memberRequests->map(function ($pair) {
            return $pair[0]->url();
        })->all();
        $this->assertContains($this->memberUrl($successful->email), $requestedUrls);
        $this->assertContains($this->memberUrl($failed->email), $requestedUrls);
        $this->assertNotContains($this->memberUrl($alreadySynced->email), $requestedUrls);
        $this->assertNotContains($this->memberUrl($withoutGdpr->email), $requestedUrls);
        $this->assertNotContains($this->memberUrl($inactive->email), $requestedUrls);
    }

    public function test_controller_caps_batches_at_twenty_and_does_not_resend_synced_subscribers(): void
    {
        foreach (range(1, 21) as $index) {
            $this->subscriber([
                'email' => sprintf('batch-%02d@example.test', $index),
            ]);
        }

        $this->fakeMailchimp();

        $first = $this->actingAs($this->admin())->withoutMiddleware()->postJson(route('newsletter.mailchimp.sync'), [
            'batch' => 999,
        ]);

        $first->assertOk()->assertJson([
            'processed' => 20,
            'synced' => 20,
            'failed' => 0,
            'pending' => 1,
            'finished' => false,
        ]);
        $this->assertSame(20, $this->memberRequests()->count());

        $second = $this->withoutMiddleware()->postJson(route('newsletter.mailchimp.sync'), [
            'batch' => 20,
            'last_id' => $first->json('last_id'),
        ]);

        $second->assertOk()->assertJson([
            'processed' => 1,
            'synced' => 1,
            'failed' => 0,
            'pending' => 0,
            'finished' => true,
        ]);
        $this->assertSame(21, $this->memberRequests()->count());

        $third = $this->withoutMiddleware()->postJson(route('newsletter.mailchimp.sync'), [
            'batch' => 20,
        ]);

        $third->assertOk()->assertJson([
            'processed' => 0,
            'synced' => 0,
            'failed' => 0,
            'pending' => 0,
            'finished' => true,
        ]);
        $this->assertSame(21, $this->memberRequests()->count());
        $this->assertSame(21, NewsletterSubscriber::query()->whereNotNull('mailchimp_synced_at')->count());
    }

    public function test_controller_stops_batch_immediately_on_a_system_mailchimp_error(): void
    {
        $subscribers = collect([
            $this->subscriber(['email' => 'rate-limited-1@example.test']),
            $this->subscriber(['email' => 'rate-limited-2@example.test']),
            $this->subscriber(['email' => 'rate-limited-3@example.test']),
        ]);

        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'GET'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123') === 0) {
                return Http::response([
                    'id' => 'audience-123',
                    'name' => 'Biblos newsletter',
                ], 200);
            }

            if ($request->method() === 'PUT'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/') === 0) {
                return Http::response([
                    'title' => 'Too Many Requests',
                    'detail' => 'Rate limit exceeded.',
                ], 429);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });

        $response = $this->actingAs($this->admin())->withoutMiddleware()->postJson(route('newsletter.mailchimp.sync'), [
            'batch' => 20,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => 0,
                'total' => 3,
                'remaining' => 3,
                'pending' => 3,
                'finished' => false,
            ]);
        $this->assertStringContainsString('HTTP 429', (string) $response->json('message'));
        $this->assertCount(1, $this->memberRequests());

        $subscribers->each(function (NewsletterSubscriber $subscriber) {
            $subscriber->refresh();

            $this->assertNull($subscriber->mailchimp_synced_at);
            $this->assertNull($subscriber->mailchimp_last_error);
        });
    }

    public function test_controller_rejects_a_non_administrator_before_contacting_mailchimp(): void
    {
        Http::fake();

        $user = new class extends User {
            public function isAdministrator(): bool
            {
                return false;
            }
        };
        $user->forceFill([
            'id' => 1000,
            'name' => 'Test editor',
            'email' => 'editor@example.test',
        ]);
        $user->setRelation('details', (object) [
            'role' => 'editor',
            'status' => 1,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->postJson(route('newsletter.mailchimp.sync'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    private function subscriber(array $overrides = []): NewsletterSubscriber
    {
        static $sequence = 0;

        $sequence++;

        return NewsletterSubscriber::query()->create(array_merge([
            'email' => 'subscriber-' . $sequence . '@example.test',
            'first_name' => 'Test',
            'last_name' => 'Subscriber',
            'user_id' => 0,
            'order_id' => 0,
            'source' => 'test',
            'gdpr' => 1,
            'subscribed_at' => now(),
            'mailchimp_synced_at' => null,
            'mailchimp_last_error' => null,
            'status' => 1,
        ], $overrides));
    }

    private function admin(): User
    {
        $admin = new User();
        $admin->forceFill([
            'id' => 999,
            'name' => 'Test administrator',
            'email' => 'admin@example.test',
        ]);
        $admin->setRelation('details', (object) [
            'role' => 'admin',
            'status' => 1,
        ]);

        return $admin;
    }

    private function fakeMailchimp(array $failedEmails = []): void
    {
        $failedHashes = array_map(function ($email) {
            return md5(strtolower(trim($email)));
        }, $failedEmails);

        Http::fake(function (ClientRequest $request) use ($failedHashes) {
            if ($request->method() === 'GET'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123') === 0) {
                return Http::response([
                    'id' => 'audience-123',
                    'name' => 'Biblos newsletter',
                ], 200);
            }

            if ($request->method() === 'PUT'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/') === 0) {
                $memberHash = basename(parse_url($request->url(), PHP_URL_PATH));

                if (in_array($memberHash, $failedHashes, true)) {
                    return Http::response([
                        'title' => 'Member Rejected',
                        'detail' => 'Audience rejected this address.',
                    ], 400);
                }

                return Http::response(['id' => $memberHash], 200);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });
    }

    private function memberRequests()
    {
        return Http::recorded(function (ClientRequest $request) {
            return $request->method() === 'PUT'
                && strpos($request->url(), '/members/') !== false;
        });
    }

    private function memberUrl(string $email): string
    {
        return 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/'
            . md5(strtolower(trim($email)))
            . '?skip_merge_validation=true';
    }
}
