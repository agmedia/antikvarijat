<?php

namespace App\Services;

use App\Models\Back\Settings\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BookPurchaseContentService
{
    private const CODE = 'store';
    private const KEY = 'book_purchase_content';
    private const LOCALES = ['hr', 'en'];
    private const FIELDS = [
        'title',
        'meta_description',
        'section_title',
        'intro_1',
        'intro_2',
        'form_title',
    ];

    public function get(): array
    {
        if (! Schema::hasTable('settings')) {
            return $this->defaults();
        }

        return $this->normalize($this->stored());
    }

    public function forLocale(?string $locale = null): array
    {
        $locale = in_array($locale, self::LOCALES, true) ? $locale : app()->getLocale();
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'hr';

        return $this->get()[$locale];
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

        return (bool) $saved;
    }

    public function defaults(): array
    {
        $defaults = [];

        foreach (self::LOCALES as $locale) {
            foreach (self::FIELDS as $field) {
                $defaults[$locale][$field] = (string) trans(
                    'front.book_purchase.' . $field,
                    [],
                    $locale
                );
            }
        }

        return $defaults;
    }

    private function stored(): array
    {
        $setting = Settings::get(self::CODE, self::KEY);

        if ($setting instanceof Collection) {
            return json_decode(json_encode($setting->all()), true) ?: [];
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
        $defaults = $this->defaults();
        $normalized = [];

        foreach (self::LOCALES as $locale) {
            $localized = isset($data[$locale]) && is_array($data[$locale])
                ? $data[$locale]
                : [];

            foreach (self::FIELDS as $field) {
                $value = trim((string) ($localized[$field] ?? ''));
                $normalized[$locale][$field] = $value !== ''
                    ? $value
                    : $defaults[$locale][$field];
            }
        }

        return $normalized;
    }
}
