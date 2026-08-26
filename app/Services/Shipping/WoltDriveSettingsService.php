<?php

namespace App\Services\Shipping;

use App\Models\Back\Settings\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WoltDriveSettingsService
{
    private const CODE = 'shipping';
    private const KEY = 'wolt_drive_api';

    private const DEVELOPMENT_URL = 'https://daas-public-api.development.dev.woltapi.com';
    private const PRODUCTION_URL = 'https://daas-public-api.wolt.com';

    /** @var array|null */
    private $resolved;

    public function get(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $stored = $this->stored();
        $defaults = $this->defaults();

        return $this->resolved = [
            'module_enabled' => $this->booleanValue($stored, 'module_enabled', $defaults),
            'environment' => $this->environment($this->value($stored, 'environment', $defaults)),
            'venue_id' => $this->value($stored, 'venue_id', $defaults),
            'merchant_id' => $this->value($stored, 'merchant_id', $defaults),
            'api_key' => $this->secret($stored, 'api_key_encrypted', $defaults['api_key'], 'API key'),
            'webhook_secret' => $this->secret(
                $stored,
                'webhook_secret_encrypted',
                $defaults['webhook_secret'],
                'webhook secret'
            ),
            'availability_cache_seconds' => $this->boundedInteger(
                $stored['availability_cache_seconds'] ?? $defaults['availability_cache_seconds'],
                0,
                3600
            ),
            'preparation_time_minutes' => $this->boundedInteger(
                $stored['preparation_time_minutes']
                    ?? $stored['preparation_minutes']
                    ?? $defaults['preparation_time_minutes'],
                0,
                60
            ),
            'request_timeout_seconds' => $this->boundedInteger(
                $stored['request_timeout_seconds']
                    ?? $stored['timeout_seconds']
                    ?? $defaults['request_timeout_seconds'],
                5,
                60
            ),
            'fallback_weight_grams' => $this->boundedInteger(
                $stored['fallback_weight_grams']
                    ?? $stored['default_weight_grams']
                    ?? $defaults['fallback_weight_grams'],
                1,
                50000
            ),
            'cod_enabled' => $this->booleanValue($stored, 'cod_enabled', $defaults),
            'pricing_mode' => $this->pricingMode(
                (string) ($stored['pricing_mode'] ?? $defaults['pricing_mode'])
            ),
            'quote_markup_percent' => $this->boundedFloat(
                $stored['quote_markup_percent'] ?? $defaults['quote_markup_percent'],
                0,
                1000
            ),
            'max_quote_price' => $this->boundedFloat(
                $stored['max_quote_price'] ?? $defaults['max_quote_price'],
                0,
                10000
            ),
            'support_url' => $this->supportUrl($this->value($stored, 'support_url', $defaults)),
            'support_email' => $this->supportEmail($this->value($stored, 'support_email', $defaults)),
            'support_phone' => $this->headerSafe($this->value($stored, 'support_phone', $defaults)),
        ];
    }

    public function save(array $data): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        $current = $this->get();
        $apiKey = trim((string) ($data['api_key'] ?? ''));
        $webhookSecret = trim((string) ($data['webhook_secret'] ?? ''));

        if ($apiKey === '') {
            $apiKey = $current['api_key'];
        }

        if ($webhookSecret === '') {
            $webhookSecret = $current['webhook_secret'];
        }

        $payload = [
            'module_enabled' => $this->toBoolean($data['module_enabled'] ?? false),
            'environment' => $this->environment((string) ($data['environment'] ?? 'development')),
            'venue_id' => trim((string) ($data['venue_id'] ?? '')),
            'merchant_id' => trim((string) ($data['merchant_id'] ?? '')),
            'api_key_encrypted' => $apiKey !== '' ? Crypt::encryptString($apiKey) : '',
            'webhook_secret_encrypted' => $webhookSecret !== '' ? Crypt::encryptString($webhookSecret) : '',
            'availability_cache_seconds' => $this->boundedInteger(
                $data['availability_cache_seconds'] ?? 300,
                0,
                3600
            ),
            'preparation_time_minutes' => $this->boundedInteger(
                $data['preparation_time_minutes'] ?? 30,
                0,
                60
            ),
            'request_timeout_seconds' => $this->boundedInteger(
                $data['request_timeout_seconds'] ?? 20,
                5,
                60
            ),
            'fallback_weight_grams' => $this->boundedInteger(
                $data['fallback_weight_grams'] ?? 500,
                1,
                50000
            ),
            'cod_enabled' => $this->toBoolean($data['cod_enabled'] ?? false),
            'pricing_mode' => $this->pricingMode((string) ($data['pricing_mode'] ?? 'fixed')),
            'quote_markup_percent' => $this->boundedFloat(
                $data['quote_markup_percent'] ?? 0,
                0,
                1000
            ),
            'max_quote_price' => $this->boundedFloat($data['max_quote_price'] ?? 0, 0, 10000),
            'support_url' => $this->supportUrl(trim((string) ($data['support_url'] ?? ''))),
            'support_email' => $this->supportEmail(trim((string) ($data['support_email'] ?? ''))),
            'support_phone' => $this->headerSafe(trim((string) ($data['support_phone'] ?? ''))),
        ];

        $value = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $setting = Settings::query()
            ->where('code', self::CODE)
            ->where('key', self::KEY)
            ->first();
        $saved = $setting
            ? Settings::edit($setting->id, self::CODE, self::KEY, $value, true)
            : Settings::insert(self::CODE, self::KEY, $value, true);

        if ($saved) {
            $this->resolved = null;
        }

        return (bool) $saved;
    }

    public function adminValues(): array
    {
        $values = $this->get();

        return [
            'module_enabled' => $values['module_enabled'],
            'environment' => $values['environment'],
            'venue_id' => $values['venue_id'],
            'merchant_id' => $values['merchant_id'],
            'has_api_key' => $values['api_key'] !== '',
            'has_webhook_secret' => $values['webhook_secret'] !== '',
            'availability_cache_seconds' => $values['availability_cache_seconds'],
            'preparation_time_minutes' => $values['preparation_time_minutes'],
            'request_timeout_seconds' => $values['request_timeout_seconds'],
            'fallback_weight_grams' => $values['fallback_weight_grams'],
            'cod_enabled' => $values['cod_enabled'],
            'pricing_mode' => $values['pricing_mode'],
            'quote_markup_percent' => $values['quote_markup_percent'],
            'max_quote_price' => $values['max_quote_price'],
            'support_url' => $values['support_url'],
            'support_email' => $values['support_email'],
            'support_phone' => $values['support_phone'],
            'is_ready' => $this->isReady(),
        ];
    }

    public function isReady(): bool
    {
        $values = $this->get();

        return $values['module_enabled']
            && $values['venue_id'] !== ''
            && $values['api_key'] !== ''
            && $values['webhook_secret'] !== '';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get()['module_enabled'];
    }

    public function baseUrl(): string
    {
        return $this->get()['environment'] === 'production'
            ? self::PRODUCTION_URL
            : self::DEVELOPMENT_URL;
    }

    private function defaults(): array
    {
        return [
            'module_enabled' => (bool) config('services.wolt.module_enabled', false),
            'environment' => (string) config('services.wolt.environment', 'development'),
            'venue_id' => (string) config('services.wolt.venue_id', ''),
            'merchant_id' => (string) config('services.wolt.merchant_id', ''),
            'api_key' => (string) config('services.wolt.api_key', ''),
            'webhook_secret' => (string) config('services.wolt.webhook_secret', ''),
            'availability_cache_seconds' => (int) config('services.wolt.availability_cache_seconds', 300),
            'preparation_time_minutes' => (int) config(
                'services.wolt.preparation_time_minutes',
                config('services.wolt.preparation_minutes', 30)
            ),
            'request_timeout_seconds' => (int) config(
                'services.wolt.request_timeout_seconds',
                config('services.wolt.timeout_seconds', 20)
            ),
            'fallback_weight_grams' => (int) config(
                'services.wolt.fallback_weight_grams',
                config('services.wolt.default_weight_grams', 500)
            ),
            'cod_enabled' => (bool) config('services.wolt.cod_enabled', false),
            'pricing_mode' => (string) config('services.wolt.pricing_mode', 'fixed'),
            'quote_markup_percent' => (float) config('services.wolt.quote_markup_percent', 0),
            'max_quote_price' => (float) config('services.wolt.max_quote_price', 0),
            'support_url' => (string) config('services.wolt.support_url', config('app.url', '')),
            'support_email' => (string) config('services.wolt.support_email', config('mail.from.address', '')),
            'support_phone' => (string) config('services.wolt.support_phone', ''),
        ];
    }

    private function stored(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            $setting = Settings::get(self::CODE, self::KEY);
        } catch (Throwable $exception) {
            return [];
        }

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

    private function secret(array $stored, string $encryptedKey, string $fallback, string $label): string
    {
        if (! array_key_exists($encryptedKey, $stored) || trim((string) $stored[$encryptedKey]) === '') {
            return trim($fallback);
        }

        try {
            return trim(Crypt::decryptString((string) $stored[$encryptedKey]));
        } catch (Throwable $exception) {
            Log::warning('Spremljenu Wolt Drive tajnu nije moguće dešifrirati.', [
                'secret' => $label,
                'exception' => get_class($exception),
            ]);

            // Fail closed. A broken stored secret must never silently fall back to
            // another credential from the environment.
            return '';
        }
    }

    private function value(array $stored, string $key, array $defaults): string
    {
        return trim((string) (array_key_exists($key, $stored) ? $stored[$key] : $defaults[$key]));
    }

    private function booleanValue(array $stored, string $key, array $defaults): bool
    {
        return $this->toBoolean(array_key_exists($key, $stored) ? $stored[$key] : $defaults[$key]);
    }

    private function toBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function boundedInteger($value, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, (int) $value));
    }

    private function boundedFloat($value, float $minimum, float $maximum): float
    {
        return max($minimum, min($maximum, (float) $value));
    }

    private function environment(string $environment): string
    {
        return in_array(strtolower(trim($environment)), ['production', 'prod'], true)
            ? 'production'
            : 'development';
    }

    private function pricingMode(string $mode): string
    {
        return strtolower(trim($mode)) === 'quote' ? 'quote' : 'fixed';
    }

    private function supportUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? rtrim($url, '/') : '';
    }

    private function supportEmail(string $email): string
    {
        $email = trim($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->headerSafe($email) : '';
    }

    private function headerSafe(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
