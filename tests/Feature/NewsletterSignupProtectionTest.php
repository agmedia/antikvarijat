<?php

namespace Tests\Feature;

use App\Helpers\Recaptcha;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\NewsletterSignupGuard;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionObject;
use Tests\TestCase;

class NewsletterSignupProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('n', 32)),
            'app.url' => 'https://www.antikvarijat-biblos.hr',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.recaptcha.sitekey' => '',
            'services.recaptcha.secret' => '',
            'services.recaptcha.bypass_local' => true,
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

        Carbon::setTestNow('2026-09-04 12:00:00');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_croatian_ajax_signup_creates_a_normalized_footer_subscriber(): void
    {
        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('newsletter.subscribe'), $this->validPayload(' Reader@Example.test '));

        $response->assertOk()->assertJson([
            'status' => 'success',
            'message' => __('front.newsletter.success'),
        ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'reader@example.test',
            'user_id' => 0,
            'order_id' => 0,
            'source' => 'footer',
            'gdpr' => 1,
            'status' => 1,
        ]);
    }

    public function test_english_route_uses_the_same_protected_signup_flow(): void
    {
        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('en.newsletter.subscribe'), $this->validPayload('english@example.test'));

        $response->assertOk()->assertJson([
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'english@example.test',
            'source' => 'footer',
        ]);
    }

    public function test_normal_form_submission_redirects_with_a_success_message(): void
    {
        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->from('/')
            ->post(route('newsletter.subscribe'), $this->validPayload());

        $response->assertRedirect('/')
            ->assertSessionHas('newsletter_success', __('front.newsletter.success'));
    }

    public function test_filled_honeypot_is_silently_accepted_without_an_insert(): void
    {
        $response = $this->withoutMiddleware(ThrottleRequests::class)->postJson(route('newsletter.subscribe'), [
            'website' => 'https://spam.example',
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_too_fast_submission_is_rejected_without_resetting_a_real_users_form(): void
    {
        $payload = [
            'email' => 'fast@example.test',
            'gdpr' => '1',
            'newsletter_started_at' => app(NewsletterSignupGuard::class)->issueToken(),
        ];

        $response = $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('newsletter.subscribe'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('newsletter_started_at')
            ->assertJsonPath('errors.newsletter_started_at.0', __('front.newsletter.too_fast'));
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_invalid_or_expired_form_token_is_rejected_without_an_insert(): void
    {
        $invalid = $this->withoutMiddleware(ThrottleRequests::class)->postJson(route('newsletter.subscribe'), [
            'email' => 'invalid-token@example.test',
            'gdpr' => '1',
            'newsletter_started_at' => 'not-an-encrypted-token',
        ]);

        $invalid->assertStatus(422)->assertJsonValidationErrors('newsletter_started_at');

        $token = app(NewsletterSignupGuard::class)->issueToken();
        Carbon::setTestNow(Carbon::now()->copy()->addSeconds(7201));

        $expired = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'expired-token@example.test',
            'gdpr' => '1',
            'newsletter_started_at' => $token,
        ]);

        $expired->assertStatus(422)->assertJsonValidationErrors('newsletter_started_at');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_duplicate_footer_signup_does_not_reactivate_or_modify_existing_data(): void
    {
        DB::table('newsletter_subscribers')->insert([
            'email' => 'existing@example.test',
            'first_name' => 'Existing',
            'last_name' => 'Customer',
            'user_id' => 91,
            'order_id' => 1234,
            'source' => 'checkout',
            'gdpr' => 0,
            'subscribed_at' => '2026-08-01 09:30:00',
            'mailchimp_synced_at' => '2026-08-01 09:35:00',
            'mailchimp_last_error' => 'preserve this value',
            'status' => 0,
            'created_at' => '2026-08-01 09:30:00',
            'updated_at' => '2026-08-02 10:00:00',
        ]);

        $before = (array) DB::table('newsletter_subscribers')
            ->where('email', 'existing@example.test')
            ->first();

        $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('newsletter.subscribe'), $this->validPayload(' EXISTING@example.test '))
            ->assertOk();

        $after = (array) DB::table('newsletter_subscribers')
            ->where('email', 'existing@example.test')
            ->first();

        $this->assertSame($before, $after);
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_recaptcha_is_not_required_when_only_one_key_is_configured(): void
    {
        config([
            'services.recaptcha.sitekey' => 'site-key',
            'services.recaptcha.secret' => '',
        ]);

        $recaptcha = Mockery::mock(Recaptcha::class);
        $recaptcha->shouldNotReceive('check');
        $this->app->instance(Recaptcha::class, $recaptcha);

        $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('newsletter.subscribe'), $this->validPayload('optional-captcha@example.test'))
            ->assertOk();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'optional-captcha@example.test',
        ]);
    }

    public function test_configured_recaptcha_requires_expected_action_and_app_hostname(): void
    {
        config([
            'services.recaptcha.sitekey' => 'site-key',
            'services.recaptcha.secret' => 'secret-key',
            'services.recaptcha.bypass_local' => false,
        ]);

        $recaptcha = Mockery::mock(Recaptcha::class);
        $recaptcha->shouldReceive('check')
            ->once()
            ->with(['recaptcha' => 'browser-token'])
            ->andReturnSelf();
        $recaptcha->shouldReceive('ok')
            ->once()
            ->with('newsletter_subscribe', 'www.antikvarijat-biblos.hr')
            ->andReturnFalse();
        $this->app->instance(Recaptcha::class, $recaptcha);

        $payload = $this->validPayload('captcha@example.test');
        $payload['recaptcha'] = 'browser-token';

        $this->withoutMiddleware(ThrottleRequests::class)
            ->postJson(route('newsletter.subscribe'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('recaptcha');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_ip_limiter_allows_six_attempts_in_ten_minutes_and_rejects_the_seventh(): void
    {
        $payload = $this->validPayload();

        foreach (range(1, 6) as $index) {
            $payload['email'] = 'ip-limit-' . $index . '@example.test';
            $this->postJson(route('newsletter.subscribe'), $payload)->assertOk();
        }

        $payload['email'] = 'ip-limit-7@example.test';
        $this->postJson(route('newsletter.subscribe'), $payload)
            ->assertStatus(429)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('newsletter_subscribers', 6);
    }

    public function test_daily_email_limit_is_scoped_to_the_email_and_ip_pair(): void
    {
        $payload = $this->validPayload('shared@example.test');

        foreach (range(1, 3) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->postJson(route('newsletter.subscribe'), $payload)
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson(route('newsletter.subscribe'), $payload)
            ->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->postJson(route('newsletter.subscribe'), $payload)
            ->assertOk();
    }

    public function test_both_routes_use_the_named_limiter_and_newsletter_is_not_csrf_exempt(): void
    {
        foreach (['newsletter.subscribe', 'en.newsletter.subscribe'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains('throttle:newsletter', $route->middleware());
        }

        $middleware = app(VerifyCsrfToken::class);
        $reflection = new ReflectionObject($middleware);
        $except = $reflection->getProperty('except');
        $except->setAccessible(true);

        $this->assertNotContains('/newsletter/prijava', $except->getValue($middleware));
    }

    private function validPayload(string $email = 'reader@example.test'): array
    {
        $token = app(NewsletterSignupGuard::class)->issueToken();
        Carbon::setTestNow(Carbon::now()->copy()->addSeconds(3));

        return [
            'email' => $email,
            'gdpr' => '1',
            'newsletter_started_at' => $token,
        ];
    }
}
