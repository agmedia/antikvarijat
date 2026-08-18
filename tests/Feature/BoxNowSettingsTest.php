<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Shipping\BoxNowSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BoxNowSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_boxnow_admin_settings_encrypt_client_secret(): void
    {
        $settings = app(BoxNowSettingsService::class);

        $this->assertTrue($settings->save($this->payload([
            'client_secret' => 'very-secret-boxnow-value',
        ])));

        $storedValue = (string) DB::table('settings')
            ->where('code', 'shipping')
            ->where('key', 'boxnow_api')
            ->value('value');

        $this->assertStringNotContainsString('very-secret-boxnow-value', $storedValue);

        $resolved = (new BoxNowSettingsService())->get();
        $this->assertSame('biblos-client-id', $resolved['client_id']);
        $this->assertSame('very-secret-boxnow-value', $resolved['client_secret']);
        $this->assertSame('BIBLOS-WAREHOUSE', $resolved['warehouse_location_id']);
    }

    public function test_empty_secret_keeps_previously_saved_secret(): void
    {
        $settings = app(BoxNowSettingsService::class);
        $settings->save($this->payload(['client_secret' => 'existing-secret']));
        $settings->save($this->payload([
            'client_secret' => '',
            'origin_phone' => '+385 91 999 9999',
        ]));

        $resolved = (new BoxNowSettingsService())->get();
        $this->assertSame('existing-secret', $resolved['client_secret']);
        $this->assertSame('+385 91 999 9999', $resolved['origin_phone']);
    }

    public function test_administrator_can_save_boxnow_settings_from_shipping_admin(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->patch(route('boxnow-settings.update'), $this->payload([
                'client_secret' => 'admin-form-secret',
            ]));

        $response->assertRedirect(route('shippings'));
        $response->assertSessionHas('success');
        $this->assertSame('admin-form-secret', (new BoxNowSettingsService())->get()['client_secret']);
    }

    public function test_shipping_admin_never_renders_saved_client_secret(): void
    {
        app(BoxNowSettingsService::class)->save($this->payload([
            'client_secret' => 'must-never-be-rendered',
        ]));

        $response = $this->actingAs(User::factory()->create())->get(route('shippings'));

        $response->assertOk()
            ->assertSee('Box Now API postavke')
            ->assertSee('Client Secret je spremljen šifrirano.')
            ->assertDontSee('must-never-be-rendered');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 'biblos-client-id',
            'client_secret' => 'boxnow-secret',
            'api_partner_id' => '',
            'widget_partner_id' => 123,
            'warehouse_location_id' => 'BIBLOS-WAREHOUSE',
            'origin_name' => 'Antikvarijat Biblos',
            'origin_email' => 'info@antikvarijat-biblos.hr',
            'origin_phone' => '+385 91 234 5678',
            'allow_return' => true,
        ], $overrides);
    }
}
