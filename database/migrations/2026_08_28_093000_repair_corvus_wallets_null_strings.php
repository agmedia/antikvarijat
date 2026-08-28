<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairCorvusWalletsNullStrings extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $walletRow = DB::table('settings')
            ->where('code', 'payment')
            ->where('key', 'list.corvus_wallets')
            ->first();

        if ($walletRow) {
            $wallet = $this->firstSettingItem($walletRow->value);

            if ($wallet === null) {
                throw new \RuntimeException('Corvus wallets setting contains invalid JSON and was not changed.');
            }

            $wallet['data'] = isset($wallet['data']) && is_array($wallet['data']) ? $wallet['data'] : [];

            foreach ($this->defaults() as $field => $default) {
                if ($field === 'data') {
                    foreach ($default as $dataField => $dataDefault) {
                        if ($this->isBlankOrNullString($wallet['data'][$dataField] ?? null)) {
                            $wallet['data'][$dataField] = $dataDefault;
                        }
                    }

                    continue;
                }

                if ($this->isBlankOrNullString($wallet[$field] ?? null)
                    || ($field === 'title' && mb_strtolower(trim((string) ($wallet[$field] ?? ''))) === 'corvus_wallets')) {
                    $wallet[$field] = $default;
                }
            }

            $wallet['code'] = 'corvus_wallets';
            $wallet['data']['credential_source'] = 'corvus';

            DB::table('settings')->where('id', $walletRow->id)->update([
                'value' => $this->encodeSetting($wallet),
                'json' => 1,
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'payment_code')
            || ! Schema::hasColumn('orders', 'payment_method')) {
            return;
        }

        DB::table('orders')
            ->where('payment_code', 'corvus_wallets')
            ->where(function ($query) {
                $query->whereNull('payment_method')
                    ->orWhereRaw("LOWER(TRIM(payment_method)) IN ('', 'null')");
            })
            ->update(['payment_method' => 'Apple Pay / Google Pay']);
    }

    public function down()
    {
        // Data repair only; reverting would restore invalid customer-facing values.
    }

    private function defaults(): array
    {
        return [
            'title' => 'Apple Pay / Google Pay',
            'title_en' => 'Apple Pay / Google Pay',
            'data' => [
                'short_description' => 'Brzo i sigurno plaćanje putem Apple Paya ili Google Paya',
                'short_description_en' => 'Fast and secure payment with Apple Pay or Google Pay',
                'description' => 'Plaćanje putem Apple Paya ili Google Paya na sigurnoj CorvusPay stranici.',
                'description_en' => 'Pay with Apple Pay or Google Pay on the secure CorvusPay page.',
            ],
        ];
    }

    private function isBlankOrNullString($value): bool
    {
        return $value === null
            || (is_string($value) && in_array(mb_strtolower(trim($value)), ['', 'null'], true));
    }

    private function firstSettingItem(?string $value): ?array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])
            ? $decoded[0]
            : null;
    }

    private function encodeSetting(array $item): string
    {
        return json_encode([$item], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
