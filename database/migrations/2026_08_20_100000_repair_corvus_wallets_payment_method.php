<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RepairCorvusWalletsPaymentMethod extends Migration
{
    public function up()
    {
        $corvusRow = DB::table('settings')
            ->where('code', 'payment')
            ->where('key', 'list.corvus')
            ->first();
        $corvus = $this->firstSettingItem($corvusRow ? $corvusRow->value : null);
        $corvusData = isset($corvus['data']) && is_array($corvus['data']) ? $corvus['data'] : [];
        $hasCredentials = ! empty($corvusData['shop_id']) && ! empty($corvusData['secret_key']);

        $defaults = [
            'title' => 'Apple Pay / Google Pay',
            'title_en' => 'Apple Pay / Google Pay',
            'code' => 'corvus_wallets',
            'min' => $corvus['min'] ?? null,
            'data' => [
                'price' => $corvusData['price'] ?? 0,
                'short_description' => 'Brzo i sigurno plaćanje putem Apple Paya ili Google Paya',
                'short_description_en' => 'Fast and secure payment with Apple Pay or Google Pay',
                'description' => 'Plaćanje putem Apple Paya ili Google Paya na sigurnoj CorvusPay stranici.',
                'description_en' => 'Pay with Apple Pay or Google Pay on the secure CorvusPay page.',
                'credential_source' => 'corvus',
            ],
            'geo_zone' => $corvus['geo_zone'] ?? null,
            'status' => $hasCredentials,
            'sort_order' => ((int) ($corvus['sort_order'] ?? 0)) + 1,
        ];

        $walletRow = DB::table('settings')
            ->where('code', 'payment')
            ->where('key', 'list.corvus_wallets')
            ->first();

        if (! $walletRow) {
            DB::table('settings')->insert([
                'code' => 'payment',
                'key' => 'list.corvus_wallets',
                'value' => $this->encodeSetting($defaults),
                'json' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $current = $this->firstSettingItem($walletRow->value);
        $isStub = empty($current['title']) || $current['title'] === 'corvus_wallets';
        $wallet = array_replace_recursive($defaults, $current);

        if ($isStub) {
            $wallet['title'] = $defaults['title'];

            if (empty($wallet['sort_order'])) {
                $wallet['sort_order'] = $defaults['sort_order'];
            }
        }

        if (empty($wallet['title_en'])) {
            $wallet['title_en'] = $defaults['title_en'];
        }

        foreach (['short_description', 'short_description_en', 'description', 'description_en'] as $field) {
            if (empty($wallet['data'][$field])) {
                $wallet['data'][$field] = $defaults['data'][$field];
            }
        }

        $wallet['code'] = 'corvus_wallets';
        $wallet['status'] = $hasCredentials;
        $wallet['data']['credential_source'] = 'corvus';

        DB::table('settings')->where('id', $walletRow->id)->update([
            'value' => $this->encodeSetting($wallet),
            'json' => 1,
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        // This migration only repairs defaults and intentionally has no destructive rollback.
    }

    private function firstSettingItem(?string $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])
            ? $decoded[0]
            : [];
    }

    private function encodeSetting(array $item): string
    {
        return json_encode([$item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
