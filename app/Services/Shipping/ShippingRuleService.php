<?php

namespace App\Services\Shipping;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ShippingRuleService
{
    /**
     * Apply the optional, reusable rules stored on a shipping method.
     * Geo-zone and the method's active status are resolved by ShippingMethod.
     */
    public function filter(Collection $methods, array $address = [], array $cart = [], ?Carbon $at = null): Collection
    {
        return $methods
            ->filter(fn ($method) => $this->evaluate($method, $address, $cart, $at)['available'])
            ->values();
    }

    public function evaluate($method, array $address = [], array $cart = [], ?Carbon $at = null): array
    {
        $rules = $this->rules($method);
        $subtotal = (float) ($cart['subtotal'] ?? $cart['total'] ?? 0);
        $itemCount = (int) ($cart['count'] ?? 0);

        if ($this->hasNumber($rules, 'min_subtotal') && $subtotal < (float) $rules['min_subtotal']) {
            return $this->unavailable('min_subtotal', 'Minimalni iznos košarice za ovaj način dostave nije dosegnut.');
        }

        if ($this->hasNumber($rules, 'max_subtotal') && $subtotal > (float) $rules['max_subtotal']) {
            return $this->unavailable('max_subtotal', 'Iznos košarice prelazi dopušteni iznos za ovaj način dostave.');
        }

        if ($this->hasNumber($rules, 'max_items') && $itemCount > (int) $rules['max_items']) {
            return $this->unavailable('max_items', 'Košarica ima previše artikala za ovaj način dostave.');
        }

        $postalCode = $this->normalizePostalCode((string) ($address['zip'] ?? $address['post_code'] ?? ''));
        $allowedPostalCodes = $this->postalPatterns($rules['allowed_postal_codes'] ?? null);
        $excludedPostalCodes = $this->postalPatterns($rules['excluded_postal_codes'] ?? null);

        if ($allowedPostalCodes && ($postalCode === '' || ! $this->matchesPostalCode($postalCode, $allowedPostalCodes))) {
            return $this->unavailable('postal_code_not_allowed', 'Ovaj način dostave nije dostupan za uneseni poštanski broj.');
        }

        if ($postalCode !== '' && $this->matchesPostalCode($postalCode, $excludedPostalCodes)) {
            return $this->unavailable('postal_code_excluded', 'Ovaj način dostave nije dostupan za uneseni poštanski broj.');
        }

        $city = $this->normalizeText((string) ($address['city'] ?? ''));
        $allowedCities = $this->textList($rules['allowed_cities'] ?? null);

        if ($allowedCities && ($city === '' || ! in_array($city, $allowedCities, true))) {
            return $this->unavailable('city_not_allowed', 'Ovaj način dostave nije dostupan za uneseni grad.');
        }

        $now = $at ?: now();
        $weekdays = collect((array) ($rules['weekdays'] ?? []))
            ->filter(fn ($day) => is_numeric($day) && (int) $day >= 1 && (int) $day <= 7)
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->values()
            ->all();

        if ($weekdays && ! in_array((int) $now->isoWeekday(), $weekdays, true)) {
            return $this->unavailable('weekday', 'Ovaj način dostave danas nije dostupan.');
        }

        if (! $this->withinTimeWindow($now, $rules['time_from'] ?? null, $rules['time_to'] ?? null)) {
            return $this->unavailable('time_window', 'Ovaj način dostave trenutačno nije dostupan.');
        }

        return [
            'available' => true,
            'code' => null,
            'message' => null,
        ];
    }

    /**
     * Resolve the customer-facing shipping charge. Wolt's quoted fee can be
     * supplied separately while all other methods use their fixed price.
     */
    public function priceFor($method, float $subtotal, ?float $woltQuotePrice = null): float
    {
        $price = max(0, (float) data_get($method, 'data.price', 0));

        if ((string) data_get($method, 'code') === WoltDriveService::CARRIER && $woltQuotePrice !== null) {
            try {
                $settings = app(WoltDriveSettingsService::class)->get();

                if (($settings['pricing_mode'] ?? 'fixed') === 'quote') {
                    $markup = max(0, (float) ($settings['quote_markup_percent'] ?? 0));
                    $price = max(0, $woltQuotePrice * (1 + ($markup / 100)));
                }
            } catch (\Throwable $exception) {
                // The fixed method price remains the safe fallback while the
                // module is being installed or its settings table is absent.
            }
        }

        $rules = $this->rules($method);
        $defaultFreeMode = (string) data_get($method, 'code') === WoltDriveService::CARRIER
            ? 'never'
            : 'global';
        $freeMode = (string) ($rules['free_shipping_mode'] ?? $defaultFreeMode);

        if ($freeMode === 'custom') {
            $threshold = $rules['free_shipping_threshold'] ?? null;

            if ($threshold !== null && $threshold !== '' && is_numeric($threshold) && $subtotal >= (float) $threshold) {
                return 0.0;
            }
        } elseif ($freeMode !== 'never') {
            $threshold = (float) config('settings.free_shipping', 0);

            // Keep the existing storefront boundary contract: the total must
            // be strictly greater than the global threshold.
            if ($threshold > 0 && $subtotal > $threshold) {
                return 0.0;
            }
        }

        return round($price, 2);
    }

    public function rules($method): array
    {
        $rules = data_get($method, 'data.rules', []);

        if (is_object($rules)) {
            $rules = json_decode(json_encode($rules), true) ?: [];
        }

        return is_array($rules) ? $rules : [];
    }

    private function hasNumber(array $rules, string $key): bool
    {
        return array_key_exists($key, $rules)
            && $rules[$key] !== null
            && $rules[$key] !== ''
            && is_numeric($rules[$key]);
    }

    private function postalPatterns($value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\s,;]+/u', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect($parts)
            ->map(fn ($part) => $this->normalizePostalPattern((string) $part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function textList($value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\r\n,;]+/u', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect($parts)
            ->map(fn ($part) => $this->normalizeText((string) $part))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function matchesPostalCode(string $postalCode, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::endsWith($pattern, '*')) {
                if (Str::startsWith($postalCode, rtrim($pattern, '*'))) {
                    return true;
                }

                continue;
            }

            if ($postalCode === $pattern) {
                return true;
            }
        }

        return false;
    }

    private function withinTimeWindow(Carbon $now, $from, $to): bool
    {
        $from = $this->normalizeTime($from);
        $to = $this->normalizeTime($to);

        if ($from === null || $to === null) {
            return true;
        }

        $time = $now->format('H:i');

        if ($from <= $to) {
            return $time >= $from && $time <= $to;
        }

        // Overnight windows such as 20:00-02:00 are supported.
        return $time >= $from || $time <= $to;
    }

    private function normalizeTime($value): ?string
    {
        $value = trim((string) $value);

        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizePostalCode(string $value): string
    {
        return Str::upper((string) preg_replace('/\s+/u', '', trim($value)));
    }

    private function normalizePostalPattern(string $value): string
    {
        $wildcard = Str::endsWith(trim($value), '*');
        $normalized = $this->normalizePostalCode(rtrim(trim($value), '*'));

        return $normalized === '' ? '' : $normalized . ($wildcard ? '*' : '');
    }

    private function normalizeText(string $value): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/u', ' ', $value)));
    }

    private function unavailable(string $code, string $message): array
    {
        return [
            'available' => false,
            'code' => $code,
            'message' => $message,
        ];
    }
}
