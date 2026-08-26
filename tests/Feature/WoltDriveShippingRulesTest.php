<?php

namespace Tests\Feature;

use App\Services\Shipping\ShippingRuleService;
use Carbon\Carbon;
use Tests\TestCase;

class WoltDriveShippingRulesTest extends TestCase
{
    public function test_amount_and_item_boundaries_are_inclusive(): void
    {
        $method = $this->method([
            'min_subtotal' => 20,
            'max_subtotal' => 100,
            'max_items' => 3,
        ]);
        $rules = app(ShippingRuleService::class);

        $this->assertTrue($rules->evaluate($method, [], [
            'subtotal' => 20,
            'count' => 3,
        ])['available']);
        $this->assertTrue($rules->evaluate($method, [], [
            'subtotal' => 100,
            'count' => 3,
        ])['available']);
        $this->assertSame('min_subtotal', $rules->evaluate($method, [], [
            'subtotal' => 19.99,
            'count' => 1,
        ])['code']);
        $this->assertSame('max_subtotal', $rules->evaluate($method, [], [
            'subtotal' => 100.01,
            'count' => 1,
        ])['code']);
        $this->assertSame('max_items', $rules->evaluate($method, [], [
            'subtotal' => 50,
            'count' => 4,
        ])['code']);
    }

    public function test_postal_allowlist_supports_prefixes_and_exclusion_wins(): void
    {
        $method = $this->method([
            'allowed_postal_codes' => "10000, 10*\n51000",
            'excluded_postal_codes' => ['10020'],
        ]);
        $rules = app(ShippingRuleService::class);

        $this->assertTrue($rules->evaluate($method, ['zip' => '10 010'], [])['available']);
        $this->assertSame(
            'postal_code_excluded',
            $rules->evaluate($method, ['zip' => '10020'], [])['code']
        );
        $this->assertSame(
            'postal_code_not_allowed',
            $rules->evaluate($method, ['zip' => '31000'], [])['code']
        );
        $this->assertSame(
            'postal_code_not_allowed',
            $rules->evaluate($method, [], [])['code']
        );
    }

    public function test_city_matching_is_case_and_whitespace_insensitive(): void
    {
        $method = $this->method([
            'allowed_cities' => "Zagreb\nVelika Gorica",
        ]);
        $rules = app(ShippingRuleService::class);

        $this->assertTrue($rules->evaluate($method, [
            'city' => '  VELIKA   GORICA ',
        ], [])['available']);
        $this->assertSame(
            'city_not_allowed',
            $rules->evaluate($method, ['city' => 'Rijeka'], [])['code']
        );
    }

    public function test_weekday_and_normal_time_window_are_enforced_at_boundaries(): void
    {
        $method = $this->method([
            'weekdays' => [1, 2, 3, 4, 5],
            'time_from' => '09:00',
            'time_to' => '17:00',
        ]);
        $rules = app(ShippingRuleService::class);

        $this->assertTrue($rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-24 09:00:00', 'Europe/Zagreb')
        )['available']);
        $this->assertTrue($rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-24 17:00:00', 'Europe/Zagreb')
        )['available']);
        $this->assertSame('time_window', $rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-24 17:01:00', 'Europe/Zagreb')
        )['code']);
        $this->assertSame('weekday', $rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-23 12:00:00', 'Europe/Zagreb')
        )['code']);
    }

    public function test_overnight_time_window_is_supported(): void
    {
        $method = $this->method([
            'time_from' => '20:00',
            'time_to' => '02:00',
        ]);
        $rules = app(ShippingRuleService::class);

        $this->assertTrue($rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-24 23:30:00', 'Europe/Zagreb')
        )['available']);
        $this->assertTrue($rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-25 01:30:00', 'Europe/Zagreb')
        )['available']);
        $this->assertSame('time_window', $rules->evaluate(
            $method,
            [],
            [],
            Carbon::parse('2026-08-25 12:00:00', 'Europe/Zagreb')
        )['code']);
    }

    public function test_free_shipping_modes_keep_their_distinct_threshold_contracts(): void
    {
        config(['settings.free_shipping' => 70]);
        $rules = app(ShippingRuleService::class);

        $global = $this->method(['free_shipping_mode' => 'global']);
        $this->assertSame(5.50, $rules->priceFor($global, 70));
        $this->assertSame(0.0, $rules->priceFor($global, 70.01));

        $never = $this->method(['free_shipping_mode' => 'never']);
        $this->assertSame(5.50, $rules->priceFor($never, 999));

        $custom = $this->method([
            'free_shipping_mode' => 'custom',
            'free_shipping_threshold' => 50,
        ]);
        $this->assertSame(5.50, $rules->priceFor($custom, 49.99));
        $this->assertSame(0.0, $rules->priceFor($custom, 50));
    }

    private function method(array $rules): object
    {
        return json_decode(json_encode([
            'code' => 'wolt_drive',
            'data' => [
                'price' => 5.50,
                'rules' => $rules,
            ],
        ]));
    }
}
