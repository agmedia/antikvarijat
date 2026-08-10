<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('class="auth-password-field"', false);
        $response->assertDontSee('class="password-toggle"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_administrator_login_ignores_frontend_intended_url()
    {
        $admin = $this->createAdministrator();

        $response = $this->withSession(['url.intended' => route('moj-racun')])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_administrator_google_login_entry_ignores_frontend_redirect()
    {
        $admin = $this->createAdministrator();

        $response = $this->actingAs($admin)->get(route('google.login.redirect', [
            'redirect' => route('moj-racun'),
        ]));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_administrator_cannot_open_customer_account()
    {
        $admin = $this->createAdministrator();

        $response = $this->actingAs($admin)->get(route('moj-racun'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_administrator_two_factor_login_ignores_frontend_intended_url()
    {
        $admin = $this->createAdministrator();
        $admin->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code'])),
        ])->save();

        $loginResponse = $this->withSession(['url.intended' => route('moj-racun')])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $loginResponse->assertRedirect(route('two-factor.login'));

        $response = $this->post('/two-factor-challenge', [
            'recovery_code' => 'recovery-code',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    private function createAdministrator(): User
    {
        $admin = User::factory()->create();

        $admin->details()->create([
            'fname' => 'Admin',
            'role' => 'admin',
            'status' => true,
        ]);

        return $admin;
    }
}
