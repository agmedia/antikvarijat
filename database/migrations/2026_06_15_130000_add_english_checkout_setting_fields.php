<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddEnglishCheckoutSettingFields extends Migration
{
    public function up()
    {
        $this->updateSettings('payment', 'list.%', function (array $item) {
            $item['title_en'] = $item['title_en'] ?? null;
            $item['data'] = isset($item['data']) && is_array($item['data']) ? $item['data'] : [];
            $item['data']['short_description_en'] = $item['data']['short_description_en'] ?? null;
            $item['data']['description_en'] = $item['data']['description_en'] ?? null;

            return $item;
        });

        $this->updateSettings('shipping', 'list.%', function (array $item) {
            $item['title_en'] = $item['title_en'] ?? null;
            $item['data'] = isset($item['data']) && is_array($item['data']) ? $item['data'] : [];
            $item['data']['time_en'] = $item['data']['time_en'] ?? null;
            $item['data']['short_description_en'] = $item['data']['short_description_en'] ?? null;
            $item['data']['description_en'] = $item['data']['description_en'] ?? null;

            return $item;
        });

        $this->updateSettings('order', 'statuses', function (array $item) {
            $item['title_en'] = $item['title_en'] ?? null;

            return $item;
        });
    }

    public function down()
    {
        $this->updateSettings('payment', 'list.%', function (array $item) {
            unset($item['title_en']);

            if (isset($item['data']) && is_array($item['data'])) {
                unset($item['data']['short_description_en'], $item['data']['description_en']);
            }

            return $item;
        });

        $this->updateSettings('shipping', 'list.%', function (array $item) {
            unset($item['title_en']);

            if (isset($item['data']) && is_array($item['data'])) {
                unset($item['data']['time_en'], $item['data']['short_description_en'], $item['data']['description_en']);
            }

            return $item;
        });

        $this->updateSettings('order', 'statuses', function (array $item) {
            unset($item['title_en']);

            return $item;
        });
    }

    private function updateSettings(string $code, string $key, callable $callback): void
    {
        $query = DB::table('settings')->where('code', $code);

        $key === 'statuses'
            ? $query->where('key', $key)
            : $query->where('key', 'like', $key);

        $query->orderBy('id')->chunk(50, function ($rows) use ($callback) {
            foreach ($rows as $row) {
                $value = json_decode((string) $row->value, true);

                if (! is_array($value)) {
                    continue;
                }

                $updated = array_map(function ($item) use ($callback) {
                    return is_array($item) ? $callback($item) : $item;
                }, $value);

                DB::table('settings')->where('id', $row->id)->update([
                    'value' => json_encode($updated, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
