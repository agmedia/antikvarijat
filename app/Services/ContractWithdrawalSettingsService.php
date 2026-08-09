<?php

namespace App\Services;

use App\Models\Back\Settings\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ContractWithdrawalSettingsService
{
    private const CODE = 'store';
    private const KEY = 'contract_withdrawal';
    private const CACHE_KEY = 'settings.contract_withdrawal';

    public function get(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(6),
            fn () => $this->normalize($this->stored())
        );
    }

    public function save(array $data): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        $payload = $this->normalize($data);
        $value = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $setting = Settings::query()
            ->where('code', self::CODE)
            ->where('key', self::KEY)
            ->first();

        $saved = $setting
            ? Settings::edit($setting->id, self::CODE, self::KEY, $value, true)
            : Settings::insert(self::CODE, self::KEY, $value, true);

        if ($saved) {
            Cache::forget(self::CACHE_KEY);
        }

        return (bool) $saved;
    }

    public function defaults(): array
    {
        return [
            'admin_email' => (string) config('mail.admin', 'info@antikvarijat-biblos.hr'),
            'return_address' => 'Antikvarijat Biblos, Palmotićeva 28, 10000 Zagreb',
            'return_address_en' => 'Antikvarijat Biblos, Palmotićeva 28, 10000 Zagreb, Croatia',
            'return_cost_policy' => 'consumer',
            'instructions' => (string) trans('contract_withdrawal.default_instructions', [], 'hr'),
            'instructions_en' => (string) trans('contract_withdrawal.default_instructions', [], 'en'),
        ];
    }

    public function forLocale(?string $locale = null): array
    {
        $settings = $this->get();
        $locale = $locale ?: app()->getLocale();
        $locale = $locale === 'en' ? 'en' : 'hr';

        if ($locale === 'en') {
            $settings['return_address'] = trim((string) ($settings['return_address_en'] ?? ''))
                ?: $settings['return_address'];
            $settings['instructions'] = trim((string) ($settings['instructions_en'] ?? ''))
                ?: $settings['instructions'];
        }

        return $settings;
    }

    public function returnCostText(?array $settings = null, ?string $locale = null): string
    {
        $settings = $settings ?: $this->get();
        $locale = $locale === 'en' || ($locale === null && app()->getLocale() === 'en') ? 'en' : 'hr';

        return ($settings['return_cost_policy'] ?? 'consumer') === 'merchant'
            ? (string) trans('contract_withdrawal.return_cost_merchant', [], $locale)
            : (string) trans('contract_withdrawal.return_cost_consumer', [], $locale);
    }

    private function stored(): array
    {
        $setting = Settings::get(self::CODE, self::KEY);

        if ($setting instanceof Collection) {
            return $setting->toArray();
        }

        if (is_array($setting)) {
            return $setting;
        }

        if (is_object($setting)) {
            return json_decode(json_encode($setting), true) ?: [];
        }

        if (is_string($setting)) {
            return json_decode($setting, true) ?: [];
        }

        return [];
    }

    private function normalize(array $data): array
    {
        $data = array_merge($this->defaults(), $data);
        $returnCostPolicy = ($data['return_cost_policy'] ?? '') === 'merchant'
            ? 'merchant'
            : 'consumer';

        return [
            'admin_email' => strtolower(trim((string) ($data['admin_email'] ?? ''))),
            'return_address' => trim((string) ($data['return_address'] ?? '')),
            'return_address_en' => trim((string) ($data['return_address_en'] ?? '')),
            'return_cost_policy' => $returnCostPolicy,
            'instructions' => trim((string) ($data['instructions'] ?? '')),
            'instructions_en' => trim((string) ($data['instructions_en'] ?? '')),
        ];
    }
}
