<?php

namespace Tests\Feature;

use App\Models\Back\Settings\Settings;
use App\Models\User;
use App\Services\Shipping\WoltDriveSettingsService;
use Bouncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\Role;
use Tests\TestCase;

class WoltDriveSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wolt_secrets_are_encrypted_and_never_exposed_to_the_admin_view_model(): void
    {
        $settings = app(WoltDriveSettingsService::class);

        $this->assertTrue($settings->save($this->payload()));

        $stored = (string) DB::table('settings')
            ->where('code', 'shipping')
            ->where('key', 'wolt_drive_api')
            ->value('value');

        $this->assertStringNotContainsString('wolt-api-secret', $stored);
        $this->assertStringNotContainsString('wolt-webhook-secret', $stored);

        $resolved = (new WoltDriveSettingsService())->get();
        $this->assertSame('wolt-api-secret', $resolved['api_key']);
        $this->assertSame('wolt-webhook-secret', $resolved['webhook_secret']);

        $adminValues = (new WoltDriveSettingsService())->adminValues();
        $this->assertTrue($adminValues['has_api_key']);
        $this->assertTrue($adminValues['has_webhook_secret']);
        $this->assertArrayNotHasKey('api_key', $adminValues);
        $this->assertArrayNotHasKey('webhook_secret', $adminValues);
    }

    public function test_blank_secret_inputs_keep_the_previously_saved_values(): void
    {
        $settings = app(WoltDriveSettingsService::class);
        $settings->save($this->payload());

        $settings->save($this->payload([
            'api_key' => '',
            'webhook_secret' => '',
            'venue_id' => 'VENUE-UPDATED',
        ]));

        $resolved = (new WoltDriveSettingsService())->get();
        $this->assertSame('wolt-api-secret', $resolved['api_key']);
        $this->assertSame('wolt-webhook-secret', $resolved['webhook_secret']);
        $this->assertSame('VENUE-UPDATED', $resolved['venue_id']);
    }

    public function test_broken_stored_ciphertext_fails_closed_instead_of_using_environment_secret(): void
    {
        config([
            'services.wolt.api_key' => 'unsafe-environment-fallback',
            'services.wolt.module_enabled' => true,
        ]);
        app(WoltDriveSettingsService::class)->save($this->payload());

        $stored = json_decode((string) DB::table('settings')
            ->where('code', 'shipping')
            ->where('key', 'wolt_drive_api')
            ->value('value'), true);
        $stored['api_key_encrypted'] = 'not-valid-ciphertext';
        DB::table('settings')
            ->where('code', 'shipping')
            ->where('key', 'wolt_drive_api')
            ->update(['value' => json_encode($stored)]);

        $settings = new WoltDriveSettingsService();
        $this->assertSame('', $settings->get()['api_key']);
        $this->assertFalse($settings->isReady());
    }

    public function test_environment_selects_only_an_allowlisted_wolt_base_url(): void
    {
        $settings = app(WoltDriveSettingsService::class);
        $settings->save($this->payload([
            'environment' => 'production',
            'base_url' => 'https://attacker.example.test',
        ]));
        $this->assertSame('https://daas-public-api.wolt.com', $settings->baseUrl());

        $settings->save($this->payload([
            'environment' => 'unexpected-value',
            'base_url' => 'https://attacker.example.test',
        ]));
        $this->assertSame(
            'https://daas-public-api.development.dev.woltapi.com',
            $settings->baseUrl()
        );
    }

    public function test_administrator_can_save_the_complete_public_form_contract(): void
    {
        $response = $this->actingAs($this->administrator())
            ->patch(route('wolt-settings.update'), $this->payload([
                'preparation_time_minutes' => 17,
                'request_timeout_seconds' => 11,
                'fallback_weight_grams' => 850,
            ]));

        $response->assertRedirect(route('shippings'));
        $response->assertSessionHas('success');

        $resolved = (new WoltDriveSettingsService())->get();
        $this->assertSame(17, $resolved['preparation_time_minutes']);
        $this->assertSame(11, $resolved['request_timeout_seconds']);
        $this->assertSame(850, $resolved['fallback_weight_grams']);
        $this->assertSame('quote', $resolved['pricing_mode']);
        $this->assertSame(15.5, $resolved['quote_markup_percent']);
        $this->assertSame(18.0, $resolved['max_quote_price']);
        $this->assertTrue($resolved['cod_enabled']);
    }

    public function test_enabled_module_requires_venue_api_key_webhook_and_support_contact(): void
    {
        config([
            'services.wolt.api_key' => '',
            'services.wolt.webhook_secret' => '',
        ]);
        Settings::insert('test', 'wolt-settings-cache-reset', '1', false);

        $response = $this->actingAs($this->administrator())
            ->from(route('shippings'))
            ->patch(route('wolt-settings.update'), $this->payload([
                'venue_id' => '',
                'api_key' => '',
                'webhook_secret' => '',
                'support_email' => '',
                'support_phone' => '',
            ]));

        $response->assertRedirect(route('shippings'));
        $errors = session('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->getBag('wolt')->has('venue_id'));
        $this->assertTrue($errors->getBag('wolt')->has('api_key'));
        $this->assertTrue($errors->getBag('wolt')->has('webhook_secret'));
        $this->assertTrue($errors->getBag('wolt')->has('support_email'));
        $this->assertDatabaseMissing('settings', [
            'code' => 'shipping',
            'key' => 'wolt_drive_api',
        ]);
    }

    public function test_guest_plain_user_customer_editor_and_bearer_only_auth_cannot_change_wolt_settings(): void
    {
        $this->patch(route('wolt-settings.update'), $this->payload())
            ->assertRedirect();

        $plainUser = User::factory()->create();
        $this->actingAs($plainUser)
            ->patch(route('wolt-settings.update'), $this->payload())
            ->assertForbidden();

        $customer = User::factory()->create();
        $customer->details()->create([
            'fname' => 'Kupac',
            'lname' => 'Test',
            'role' => 'customer',
            'status' => true,
        ]);
        $customerRole = Bouncer::role()->create([
            'name' => 'customer',
            'title' => 'Customer',
        ]);
        Bouncer::assign($customerRole)->to($customer);
        Bouncer::refresh();
        $this->actingAs($customer->fresh('details'))
            ->patch(route('wolt-settings.update'), $this->payload())
            ->assertRedirect(route('moj-racun'));

        $editor = User::factory()->create();
        $editor->details()->create([
            'fname' => 'Urednik',
            'lname' => 'Test',
            'role' => 'editor',
            'status' => true,
        ]);
        $editorRole = Bouncer::role()->create([
            'name' => 'editor',
            'title' => 'Editor',
        ]);
        Bouncer::assign($editorRole)->to($editor);
        Bouncer::refresh();
        $this->actingAs($editor->fresh('details'))
            ->patch(route('wolt-settings.update'), $this->payload())
            ->assertForbidden();

        auth()->logout();
        $tokenOnly = User::factory()->create();
        $token = $tokenOnly->createToken('wolt-settings-test')->plainTextToken;
        $this->withToken($token)
            ->patchJson(route('wolt-settings.update'), $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseMissing('settings', [
            'code' => 'shipping',
            'key' => 'wolt_drive_api',
        ]);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->details()->create([
            'fname' => 'Admin',
            'lname' => 'Test',
            'role' => 'admin',
            'status' => true,
        ]);
        $role = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['title' => 'Administrator']
        );
        Bouncer::assign($role)->to($user);
        Bouncer::refresh();

        return $user->fresh('details');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'module_enabled' => true,
            'environment' => 'development',
            'api_key' => 'wolt-api-secret',
            'webhook_secret' => 'wolt-webhook-secret',
            'venue_id' => 'VENUE-BIBLOS',
            'merchant_id' => 'MERCHANT-BIBLOS',
            'availability_cache_seconds' => 120,
            'preparation_time_minutes' => 20,
            'request_timeout_seconds' => 15,
            'fallback_weight_grams' => 600,
            'cod_enabled' => true,
            'pricing_mode' => 'quote',
            'quote_markup_percent' => 15.5,
            'max_quote_price' => 18,
            'support_url' => 'https://antikvarijat-biblos.hr/kontakt',
            'support_email' => 'podrska@example.test',
            'support_phone' => '+385 91 234 5678',
        ], $overrides);
    }
}
