<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureValidUserImpersonation;
use App\Models\User;
use App\Services\UserImpersonationService;
use Bouncer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    private int $userSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'session.driver' => 'array',
            'app.locale' => 'hr',
            'impersonation.ttl_minutes' => 60,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createSchema();
        Cache::flush();
        Bouncer::refresh();
    }

    protected function tearDown(): void
    {
        Bouncer::refresh();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_admin_can_start_and_stop_impersonation_with_rotated_clean_sessions_and_fixed_redirects(): void
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');
        $session = $this->app['session']->driver();

        $this->actingAs($administrator, 'web')->withSession([
            config('session.cart') => 'private-cart-id',
            config('session.cart') . '_coupon' => 'PRIVATE10',
            'checkout' => ['address' => ['email' => 'admin@example.test']],
            'auth.checkout_redirect' => '/naplata',
            'url.intended' => 'https://attacker.example/redirect',
            'login.id' => $administrator->id,
            'login.remember' => true,
        ]);

        $beforeStartId = $session->getId();
        $beforeStartToken = $session->token();
        $this->carryCurrentSessionCookie();

        $start = $this->post(route('users.impersonate', $customer), [
            'redirect' => 'https://attacker.example/start',
        ]);

        $start->assertRedirect(route('moj-racun'));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertNotSame($beforeStartId, $session->getId());
        $this->assertNotSame($beforeStartToken, $session->token());
        $this->assertSame($customer->getAuthPassword(), $session->get('password_hash_web'));

        foreach ([
            config('session.cart'),
            config('session.cart') . '_coupon',
            'checkout',
            'auth.checkout_redirect',
            'url.intended',
            'login.id',
            'login.remember',
        ] as $sensitiveKey) {
            $this->assertFalse($session->has($sensitiveKey), "Session key [{$sensitiveKey}] survived impersonation start.");
        }

        $state = $session->get(UserImpersonationService::SESSION_KEY);
        $this->assertIsArray($state);
        $this->assertSame($administrator->id, $state['actor_id']);
        $this->assertSame($customer->id, $state['target_id']);
        $this->assertNotEmpty($state['audit_id']);
        $this->assertGreaterThan($state['started_at'], $state['expires_at']);

        $this->assertDatabaseHas('user_impersonation_audits', [
            'audit_id' => $state['audit_id'],
            'actor_user_id' => $administrator->id,
            'target_user_id' => $customer->id,
            'expires_at' => date('Y-m-d H:i:s', $state['expires_at']),
            'ended_at' => null,
        ]);

        $this->withSession([
            config('session.cart') => 'customer-cart-id',
            'checkout' => ['payment' => 'card'],
            'url.intended' => 'https://attacker.example/restore',
        ]);
        $beforeStopId = $session->getId();
        $beforeStopToken = $session->token();
        $this->prepareNextRequest();

        $stop = $this->post(route('impersonation.stop'), [
            'redirect' => 'https://attacker.example/stop',
        ]);

        $stop->assertRedirect(route('users'));
        $this->assertAuthenticatedAs($administrator, 'web');
        $this->assertNotSame($beforeStopId, $session->getId());
        $this->assertNotSame($beforeStopToken, $session->token());
        $this->assertSame($administrator->getAuthPassword(), $session->get('password_hash_web'));
        $this->assertFalse($session->has(UserImpersonationService::SESSION_KEY));
        $this->assertFalse($session->has(config('session.cart')));
        $this->assertFalse($session->has('checkout'));
        $this->assertFalse($session->has('url.intended'));

        $audit = DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->first();

        $this->assertNotNull($audit->ended_at);
        $this->assertSame('stopped', $audit->end_reason);
    }

    public function test_editor_plain_customer_and_inactive_administrator_cannot_start_impersonation(): void
    {
        $customer = $this->createUser('customer');
        $actors = [
            $this->createUser('editor', true, ['editor']),
            $this->createUser('customer'),
            $this->createUser('admin', false),
        ];

        foreach ($actors as $actor) {
            $this->actingAs($actor, 'web');

            $this->post(route('users.impersonate', $customer))
                ->assertForbidden();

            $this->assertAuthenticatedAs($actor, 'web');
            $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        }
    }

    public function test_admin_editor_inactive_and_bouncer_privileged_targets_are_denied(): void
    {
        $administrator = $this->createUser('admin');
        $targets = [
            $this->createUser('admin'),
            $this->createUser('editor', true, ['editor']),
            $this->createUser('customer', false),
            $this->createUser('customer', true, ['admin']),
        ];

        $this->actingAs($administrator, 'web');

        foreach ($targets as $target) {
            $this->post(route('users.impersonate', $target))
                ->assertForbidden();

            $this->assertAuthenticatedAs($administrator, 'web');
            $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        }
    }

    public function test_bouncer_everything_cannot_bypass_impersonation_domain_rules(): void
    {
        $administrator = $this->createUser('admin');
        $privilegedTarget = $this->createUser('admin');

        Bouncer::allow($administrator)->everything();
        Bouncer::refresh();
        Log::spy();

        $this->actingAs($administrator, 'web')
            ->post(route('users.impersonate', $privilegedTarget))
            ->assertForbidden();

        $this->assertAuthenticatedAs($administrator, 'web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($administrator, $privilegedTarget) {
                return $message === 'Administrator impersonation denied'
                    && $context['actor_id'] === $administrator->id
                    && $context['target_id'] === $privilegedTarget->id;
            });
    }

    public function test_bearer_token_cannot_be_upgraded_into_an_impersonated_web_session(): void
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');
        $token = $administrator->createToken('support-api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post(route('users.impersonate', $customer))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
    }

    public function test_nested_impersonation_is_rejected_with_conflict(): void
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');

        $this->withoutMiddleware(EnsureValidUserImpersonation::class);
        $this->actingAs($administrator, 'web')->withSession([
            UserImpersonationService::SESSION_KEY => ['already' => 'active'],
        ]);

        $this->post(route('users.impersonate', $customer))
            ->assertStatus(409);

        $this->assertAuthenticatedAs($administrator, 'web');
        $this->assertTrue(session()->has(UserImpersonationService::SESSION_KEY));
    }

    public function test_demoted_actor_automatically_terminates_the_customer_session(): void
    {
        [$administrator, , $state] = $this->startImpersonation();
        $session = $this->app['session']->driver();
        $beforeTerminationId = $session->getId();
        $beforeTerminationToken = $session->token();

        $administrator->details()->update(['role' => 'customer']);
        $this->prepareNextRequest();

        $response = $this->get(route('poklon-bon.create'));

        $response->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertFalse($session->has(UserImpersonationService::SESSION_KEY));
        $this->assertNotSame($beforeTerminationId, $session->getId());
        $this->assertNotSame($beforeTerminationToken, $session->token());
        $this->assertSame('authorization_changed', DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->value('end_reason'));
    }

    public function test_actor_password_change_revokes_the_impersonation_session(): void
    {
        [$administrator, , $state] = $this->startImpersonation();

        $administrator->forceFill(['password' => bcrypt('rotated-password')])->save();
        $this->prepareNextRequest();

        $this->get(route('poklon-bon.create'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        $this->assertSame('credentials_changed', DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->value('end_reason'));
    }

    public function test_customer_password_change_revokes_the_impersonation_session(): void
    {
        [, $customer, $state] = $this->startImpersonation();

        $customer->forceFill(['password' => bcrypt('rotated-password')])->save();
        $this->prepareNextRequest();

        $this->get(route('poklon-bon.create'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        $this->assertSame('credentials_changed', DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->value('end_reason'));
    }

    public function test_customer_privilege_change_automatically_terminates_impersonation(): void
    {
        [, $customer, $state] = $this->startImpersonation();

        $customer->details()->update(['role' => 'admin']);
        $this->prepareNextRequest();

        $response = $this->get(route('poklon-bon.create'));

        $response->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        $this->assertSame('authorization_changed', DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->value('end_reason'));
    }

    public function test_banner_is_rendered_only_while_impersonation_is_active(): void
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');

        $this->actingAs($customer, 'web');
        $this->assertStringNotContainsString('impersonation-banner', $this->renderImpersonationBanner());

        $this->actingAs($administrator, 'web');
        $this->post(route('users.impersonate', $customer))->assertRedirect(route('moj-racun'));

        $html = $this->renderImpersonationBanner();
        $this->assertStringContainsString('impersonation-banner', $html);
        $this->assertStringContainsString(route('impersonation.stop'), $html);
        $this->assertStringContainsString(e($customer->name), $html);

        $this->prepareNextRequest();
        $this->post(route('impersonation.stop'))->assertRedirect(route('users'));

        $this->assertStringNotContainsString('impersonation-banner', $this->renderImpersonationBanner());

        $layout = file_get_contents(resource_path('views/front/layouts/app.blade.php'));
        $this->assertStringContainsString('session()->has(\\App\\Services\\UserImpersonationService::SESSION_KEY)', $layout);
        $this->assertStringContainsString("@include('front.layouts.partials.impersonation-banner')", $layout);
    }

    public function test_impersonated_customer_is_redirected_away_from_admin_urls(): void
    {
        [, $customer] = $this->startImpersonation();
        $this->prepareNextRequest();

        $this->get(route('dashboard'))
            ->assertRedirect(route('moj-racun'));

        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertTrue(session()->has(UserImpersonationService::SESSION_KEY));
    }

    public function test_normal_logout_ends_impersonation_without_restoring_the_administrator(): void
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');
        $rememberToken = 'customer-token-that-must-not-be-rotated';
        $customer->setRememberToken($rememberToken);
        $customer->save();

        $this->actingAs($administrator, 'web');
        $this->post(route('users.impersonate', $customer))
            ->assertRedirect(route('moj-racun'));

        $state = session()->get(UserImpersonationService::SESSION_KEY);
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertIsArray($state);
        $this->withSession(['url.intended' => route('users')]);
        $this->prepareNextRequest();

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('index'));
        $this->assertGuest('web');
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_KEY));
        $this->assertFalse(session()->has('url.intended'));
        $this->assertSame($rememberToken, $customer->fresh()->getRememberToken());
        $this->assertSame('logout', DB::table('user_impersonation_audits')
            ->where('audit_id', $state['audit_id'])
            ->value('end_reason'));
    }

    public function test_stop_is_forbidden_without_an_active_impersonation_state(): void
    {
        $customer = $this->createUser('customer');

        $this->actingAs($customer, 'web')
            ->post(route('impersonation.stop'))
            ->assertForbidden();

        $this->assertAuthenticatedAs($customer, 'web');
    }

    /**
     * @return array{0: User, 1: User, 2: array<string, mixed>}
     */
    private function startImpersonation(): array
    {
        $administrator = $this->createUser('admin');
        $customer = $this->createUser('customer');

        $this->actingAs($administrator, 'web');
        $this->post(route('users.impersonate', $customer))
            ->assertRedirect(route('moj-racun'));

        $state = session()->get(UserImpersonationService::SESSION_KEY);
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertIsArray($state);

        return [$administrator, $customer, $state];
    }

    private function renderImpersonationBanner(): string
    {
        Auth::shouldUse('web');

        return (string) $this->blade(<<<'BLADE'
@if (session()->has(\App\Services\UserImpersonationService::SESSION_KEY) && auth()->check())
    @include('front.layouts.partials.impersonation-banner')
@endif
BLADE
        );
    }

    private function carryCurrentSessionCookie(): void
    {
        $session = $this->app['session']->driver();

        $this->withCookie($session->getName(), $session->getId());
    }

    private function prepareNextRequest(): void
    {
        $this->carryCurrentSessionCookie();
        Auth::forgetGuards();
        Auth::shouldUse('web');
    }

    private function createUser(string $detailRole, bool $active = true, array $bouncerRoles = []): User
    {
        $this->userSequence++;

        $user = new User([
            'name' => ucfirst($detailRole) . ' ' . $this->userSequence,
            'email' => $detailRole . $this->userSequence . '@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->email_verified_at = now();
        $user->save();

        $user->details()->create([
            'fname' => ucfirst($detailRole),
            'lname' => (string) $this->userSequence,
            'role' => $detailRole,
            'status' => $active,
        ]);

        foreach ($bouncerRoles as $roleName) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName],
                ['title' => ucfirst($roleName)]
            );

            Bouncer::assign($role)->to($user);
        }

        Bouncer::refresh();

        return $user->fresh('details');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->text('profile_photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('fname');
            $table->string('lname')->nullable();
            $table->string('address')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('oib')->nullable();
            $table->string('avatar')->nullable();
            $table->longText('bio')->nullable();
            $table->string('social')->nullable();
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('abilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->boolean('only_owned')->default(false);
            $table->text('options')->nullable();
            $table->integer('scope')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->unsignedInteger('level')->nullable();
            $table->integer('scope')->nullable();
            $table->timestamps();
        });

        Schema::create('assigned_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('restricted_to_id')->nullable();
            $table->string('restricted_to_type')->nullable();
            $table->integer('scope')->nullable();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ability_id');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->boolean('forbidden')->default(false);
            $table->integer('scope')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_impersonation_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('audit_id')->unique();
            $table->unsignedBigInteger('actor_user_id')->index();
            $table->unsignedBigInteger('target_user_id')->index();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();
        });
    }
}
