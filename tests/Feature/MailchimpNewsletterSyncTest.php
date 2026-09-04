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

    public function test_service_archives_a_subscriber_with_the_mailchimp_member_delete_endpoint(): void
    {
        Http::fake([
            'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/*' => Http::response(null, 204),
        ]);

        $subscriber = $this->subscriber([
            'email' => ' Archive.Me@Example.test ',
            'source' => 'footer',
        ]);

        $result = app(MailchimpNewsletterService::class)->archiveSubscriber($subscriber);

        $this->assertSame(['ok' => true, 'error' => null], $result);
        Http::assertSent(function (ClientRequest $request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/'
                    . md5('archive.me@example.test');
        });
        Http::assertSentCount(1);
    }

    public function test_service_treats_a_missing_mailchimp_member_as_already_archived(): void
    {
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'DELETE') {
                return Http::response(['title' => 'Resource Not Found'], 404);
            }

            if ($request->method() === 'GET'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123') === 0) {
                return Http::response([
                    'id' => 'audience-123',
                    'name' => 'Biblos newsletter',
                ], 200);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });

        $result = app(MailchimpNewsletterService::class)->archiveSubscriber(
            $this->subscriber(['source' => 'footer'])
        );

        $this->assertSame(['ok' => true, 'error' => null], $result);
        $this->assertCount(1, $this->archiveRequests());
        Http::assertSentCount(2);
    }

    public function test_service_does_not_accept_member_404_when_the_audience_can_no_longer_be_verified(): void
    {
        Http::fake(function (ClientRequest $request) {
            return Http::response([
                'title' => 'Resource Not Found',
                'detail' => 'The requested resource could not be found.',
            ], 404);
        });

        $result = app(MailchimpNewsletterService::class)->archiveSubscriber(
            $this->subscriber(['source' => 'footer'])
        );

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['stop']);
        $this->assertStringContainsString('nije potvrđeno', $result['error']);
        $this->assertCount(1, $this->archiveRequests());
        Http::assertSentCount(2);
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

    public function test_admin_cleanup_archives_and_deletes_only_anonymous_footer_subscribers(): void
    {
        $eligible = $this->subscriber([
            'email' => 'spam-footer@example.test',
            'source' => 'footer',
        ]);
        $linkedUser = $this->subscriber([
            'email' => 'linked-user@example.test',
            'source' => 'footer',
            'user_id' => 71,
        ]);
        $linkedOrder = $this->subscriber([
            'email' => 'linked-order@example.test',
            'source' => 'footer',
            'order_id' => 812,
        ]);
        $checkout = $this->subscriber([
            'email' => 'checkout@example.test',
            'source' => 'checkout',
        ]);

        $this->fakeMailchimpArchive();

        $response = $this->actingAs($this->admin())->withoutMiddleware()->delete(
            route('newsletter.subscribers.destroy'),
            [
                'subscriber_ids' => [
                    $eligible->id,
                    $linkedUser->id,
                    $linkedOrder->id,
                    $checkout->id,
                    999999,
                ],
            ]
        );

        $response->assertRedirect()->assertSessionHas('warning');

        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $eligible->id]);
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $linkedUser->id]);
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $linkedOrder->id]);
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $checkout->id]);

        $archiveRequests = $this->archiveRequests();
        $this->assertCount(1, $archiveRequests);
        $this->assertSame($this->archiveUrl($eligible->email), $archiveRequests->first()[0]->url());
    }

    public function test_admin_cleanup_keeps_local_row_when_mailchimp_archive_fails(): void
    {
        $subscriber = $this->subscriber([
            'email' => 'archive-failure@example.test',
            'source' => 'footer',
        ]);

        $this->fakeMailchimpArchive(400);

        $response = $this->actingAs($this->admin())->withoutMiddleware()->delete(
            route('newsletter.subscribers.destroy'),
            ['subscriber_ids' => [$subscriber->id]]
        );

        $response->assertRedirect()->assertSessionHas('warning');
        $subscriber->refresh();
        $this->assertStringContainsString('HTTP 400', (string) $subscriber->mailchimp_last_error);
        $this->assertCount(1, $this->archiveRequests());
    }

    public function test_admin_cleanup_checks_the_audience_before_treating_member_404_as_success(): void
    {
        $subscriber = $this->subscriber([
            'email' => 'wrong-audience@example.test',
            'source' => 'footer',
        ]);

        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'title' => 'Resource Not Found',
                    'detail' => 'The requested list could not be found.',
                ], 404);
            }

            throw new RuntimeException('Cleanup must not archive members when the audience check fails.');
        });

        $response = $this->actingAs($this->admin())->withoutMiddleware()->delete(
            route('newsletter.subscribers.destroy'),
            ['subscriber_ids' => [$subscriber->id]]
        );

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $subscriber->id]);
        $this->assertCount(0, $this->archiveRequests());
        Http::assertSentCount(1);
    }

    public function test_admin_cleanup_rechecks_order_and_user_links_before_local_delete(): void
    {
        $subscriber = $this->subscriber([
            'email' => 'linked-during-cleanup@example.test',
            'source' => 'footer',
        ]);

        Http::fake(function (ClientRequest $request) use ($subscriber) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'id' => 'audience-123',
                    'name' => 'Biblos newsletter',
                ], 200);
            }

            if ($request->method() === 'DELETE') {
                NewsletterSubscriber::query()
                    ->whereKey($subscriber->id)
                    ->update(['order_id' => 991]);

                return Http::response(null, 204);
            }

            throw new RuntimeException('Unexpected Mailchimp request: ' . $request->method() . ' ' . $request->url());
        });

        $response = $this->actingAs($this->admin())->withoutMiddleware()->delete(
            route('newsletter.subscribers.destroy'),
            ['subscriber_ids' => [$subscriber->id]]
        );

        $response->assertRedirect()->assertSessionHas('warning');
        $this->assertDatabaseHas('newsletter_subscribers', [
            'id' => $subscriber->id,
            'order_id' => 991,
        ]);
        $this->assertCount(1, $this->archiveRequests());
    }

    public function test_admin_cleanup_rejects_more_than_fifty_ids_without_contacting_mailchimp(): void
    {
        Http::fake();

        $response = $this->actingAs($this->admin())->withoutMiddleware()->delete(
            route('newsletter.subscribers.destroy'),
            ['subscriber_ids' => range(1, 51)]
        );

        $response->assertSessionHasErrors('subscriber_ids');
        Http::assertNothingSent();
    }

    public function test_admin_cleanup_rejects_a_non_administrator_before_contacting_mailchimp(): void
    {
        Http::fake();

        $subscriber = $this->subscriber(['source' => 'footer']);
        $user = new class extends User {
            public function isAdministrator(): bool
            {
                return false;
            }
        };
        $user->forceFill([
            'id' => 1001,
            'name' => 'Test editor',
            'email' => 'cleanup-editor@example.test',
        ]);
        $user->setRelation('details', (object) [
            'role' => 'editor',
            'status' => 1,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->delete(route('newsletter.subscribers.destroy'), [
                'subscriber_ids' => [$subscriber->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('newsletter_subscribers', ['id' => $subscriber->id]);
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

    private function fakeMailchimpArchive(int $memberStatus = 204): void
    {
        Http::fake(function (ClientRequest $request) use ($memberStatus) {
            if ($request->method() === 'GET'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123') === 0) {
                return Http::response([
                    'id' => 'audience-123',
                    'name' => 'Biblos newsletter',
                ], 200);
            }

            if ($request->method() === 'DELETE'
                && strpos($request->url(), 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/') === 0) {
                if ($memberStatus === 204) {
                    return Http::response(null, 204);
                }

                return Http::response([
                    'title' => 'Member Archive Failed',
                    'detail' => 'Mailchimp rejected the archive request.',
                ], $memberStatus);
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

    private function archiveRequests()
    {
        return Http::recorded(function (ClientRequest $request) {
            return $request->method() === 'DELETE'
                && strpos($request->url(), '/members/') !== false;
        });
    }

    private function memberUrl(string $email): string
    {
        return 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/'
            . md5(strtolower(trim($email)))
            . '?skip_merge_validation=true';
    }

    private function archiveUrl(string $email): string
    {
        return 'https://us7.api.mailchimp.com/3.0/lists/audience-123/members/'
            . md5(strtolower(trim($email)));
    }
}
