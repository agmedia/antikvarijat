<?php

namespace App\Helpers;

use App\Models\Front\Blog;
use App\Models\Front\Catalog\Author;
use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Catalog\Publisher;
use App\Models\Front\Page;
use App\Models\Back\Settings\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class LocaleHelper
{
    public const DEFAULT_LOCALE = 'hr';
    public const ENGLISH_LOCALE = 'en';

    public static function current(): string
    {
        return app()->getLocale() ?: self::DEFAULT_LOCALE;
    }

    public static function isEnglish(?string $locale = null): bool
    {
        return ($locale ?: self::current()) === self::ENGLISH_LOCALE;
    }

    public static function defaultLocale(): string
    {
        return config('localization.default', self::DEFAULT_LOCALE);
    }

    public static function locales(): array
    {
        return array_keys(config('localization.locales', []));
    }

    public static function route(string $name, array $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        $locale = $locale ?: self::current();
        $routeName = self::routeName($name, $locale);

        return route($routeName, self::routeParameters($name, $parameters, $locale), $absolute);
    }

    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale = $locale ?: self::current();

        if (self::isEnglish($locale)) {
            $englishName = Str::startsWith($name, 'en.') ? $name : 'en.' . $name;

            if (Route::has($englishName)) {
                return $englishName;
            }
        }

        return Str::startsWith($name, 'en.') ? Str::after($name, 'en.') : $name;
    }

    public static function routeParameters(string $name, array $parameters, ?string $locale = null): array
    {
        $locale = $locale ?: self::current();
        $parameters = array_filter($parameters, static fn ($value) => $value !== null);

        if (array_key_exists('group', $parameters)) {
            $parameters['group'] = self::groupSlug((string) $parameters['group'], $locale);
        }

        foreach (['cat', 'subcat'] as $key) {
            if (array_key_exists($key, $parameters)) {
                $parameters[$key] = self::routeKey(
                    $parameters[$key] instanceof Model ? $parameters[$key] : self::resolveCategoryFromSlug((string) $parameters[$key]),
                    $locale
                );
            }
        }

        foreach (['prod', 'product', 'page', 'blog', 'author', 'publisher'] as $key) {
            if (array_key_exists($key, $parameters)) {
                $parameters[$key] = self::routeKey(self::resolveRouteParameterModel($key, $parameters[$key]), $locale);
            }
        }

        return $parameters;
    }

    public static function routeKey($value, ?string $locale = null): string
    {
        if ($value instanceof Model) {
            if (self::isEnglish($locale) && self::hasFilledRawAttribute($value, 'slug_en')) {
                return (string) $value->getRawOriginal('slug_en');
            }

            // Back-office models use their numeric ID as the route key, but
            // localized front-end URLs must always use the stored slug.
            if (self::hasFilledRawAttribute($value, 'slug')) {
                return (string) $value->getRawOriginal('slug');
            }

            return (string) $value->getRawOriginal($value->getRouteKeyName());
        }

        return (string) $value;
    }

    public static function localizedField($model, string $field, bool $fallback = true, ?string $locale = null)
    {
        if (! $model) {
            return null;
        }

        $locale = $locale ?: self::current();
        $raw = self::rawAttribute($model, $field);

        if (self::isEnglish($locale)) {
            $localized = self::rawAttribute($model, $field . '_en');

            if ($localized !== null && trim((string) $localized) !== '') {
                return $localized;
            }

            return $fallback ? $raw : null;
        }

        return $raw;
    }

    public static function localizedSettingField($item, string $field, bool $fallback = true, ?string $locale = null): ?string
    {
        $value = self::localizedField($item, $field, $fallback, $locale);

        return $value !== null ? (string) $value : null;
    }

    public static function localizedSettingDataField($item, string $field, bool $fallback = true, ?string $locale = null): ?string
    {
        $data = self::rawAttribute($item, 'data');
        $value = self::localizedField($data, $field, $fallback, $locale);

        return $value !== null ? (string) $value : null;
    }

    public static function paymentTitle(?string $code, ?string $fallback = null, ?string $locale = null): string
    {
        $payment = $code ? Settings::get('payment', 'list.' . $code)->first() : null;

        return (string) (self::localizedSettingField($payment, 'title', true, $locale) ?: $fallback ?: '');
    }

    public static function shippingTitle(?string $code, ?string $fallback = null, ?string $locale = null): string
    {
        $shipping = $code ? Settings::get('shipping', 'list.' . $code)->first() : null;

        return (string) (self::localizedSettingField($shipping, 'title', true, $locale) ?: $fallback ?: '');
    }

    public static function orderStatusTitle($status, ?string $fallback = null, ?string $locale = null): string
    {
        return (string) (self::localizedSettingField($status, 'title', true, $locale) ?: $fallback ?: '');
    }

    public static function localizedSlug($model, ?string $locale = null): string
    {
        return self::routeKey($model, $locale);
    }

    public static function localizedProductDescription($model, ?string $locale = null)
    {
        return self::localizedField($model, 'description', ! self::isEnglish($locale), $locale);
    }

    public static function localizedProductAttribute(string $attribute, $value, ?string $locale = null): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return $value !== null ? (string) $value : null;
        }

        $locale = $locale ?: self::current();

        if (! self::isEnglish($locale)) {
            return (string) $value;
        }

        $attribute = $attribute === 'script' ? 'letter' : $attribute;
        $normalizedValue = str_replace(['/', '|'], ' ', (string) $value);
        $translationKey = 'front.product.attribute_values.' . $attribute . '.' . Str::slug($normalizedValue, '_');

        return Lang::has($translationKey, $locale)
            ? trans($translationKey, [], $locale)
            : (string) $value;
    }

    public static function localizedUrl(?string $path, ?string $locale = null): string
    {
        if (! $path) {
            return self::isEnglish($locale) ? 'en' : '/';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = trim($path, '/');

        if (self::isEnglish($locale)) {
            return Str::startsWith($path, 'en/') ? $path : 'en/' . $path;
        }

        return Str::startsWith($path, 'en/') ? trim(Str::after($path, 'en/'), '/') : $path;
    }

    public static function productPath(Product $product, ?string $storedPath = null, ?string $locale = null): string
    {
        $locale = $locale ?: self::current();

        if (self::isEnglish($locale)) {
            [$category, $subcategory] = self::productCategoryPair($product);

            if ($category) {
                $group = self::groupSlug((string) $category->getRawOriginal('group'), $locale);
                $catSlug = self::routeKey($category, $locale);
                $productSlug = self::routeKey($product, $locale);

                if ($subcategory) {
                    return 'en/' . $group . '/' . $catSlug . '/' . self::routeKey($subcategory, $locale) . '/' . $productSlug;
                }

                return 'en/' . $group . '/' . $catSlug . '/' . $productSlug;
            }

            $urlEn = $product->getRawOriginal('url_en');

            if ($urlEn) {
                return trim($urlEn, '/');
            }
        }

        return trim((string) $storedPath, '/');
    }

    public static function categoryUrl(Category $category, ?Category $subcategory = null, ?string $locale = null): string
    {
        $locale = $locale ?: self::current();

        $params = [
            'group' => self::groupSlug((string) $category->getRawOriginal('group'), $locale),
            'cat' => self::routeKey($category, $locale),
        ];

        if ($subcategory) {
            $params['subcat'] = self::routeKey($subcategory, $locale);
        }

        return self::route('catalog.route', $params, true, $locale);
    }

    public static function categoryString(Product $product, ?string $storedValue = null, ?string $locale = null): ?string
    {
        if (! self::isEnglish($locale)) {
            return $storedValue;
        }

        [$category, $subcategory] = self::productCategoryPair($product);

        if (! $category) {
            return $storedValue;
        }

        $categoryTitle = e(self::localizedField($category, 'title'));
        $catstring = '<span class="fs-xs ms-1"><a href="' . e(self::categoryUrl($category, null, self::ENGLISH_LOCALE)) . '">' . $categoryTitle . '</a> ';

        if ($subcategory) {
            return $catstring . '</span><span class="fs-xs ms-1"><a href="' . e(self::categoryUrl($category, $subcategory, self::ENGLISH_LOCALE)) . '">' . e(self::localizedField($subcategory, 'title')) . '</a></span>';
        }

        return $catstring;
    }

    public static function groupSlug(string $group, ?string $locale = null): string
    {
        $normalized = self::normalizeGroupKey($group);
        $config = config('localization.groups.' . $normalized);

        if (! $config) {
            return Str::slug($group);
        }

        return self::isEnglish($locale) ? $config['en_slug'] : $config['hr_slug'];
    }

    public static function groupTitle(string $group, ?string $locale = null): string
    {
        $normalized = self::normalizeGroupKey($group);
        $config = config('localization.groups.' . $normalized);

        if (! $config) {
            return Str::ucfirst($group);
        }

        return self::isEnglish($locale) ? $config['en_title'] : $config['hr_title'];
    }

    public static function internalGroup(string $group): string
    {
        $normalized = self::normalizeGroupKey($group);
        $config = config('localization.groups.' . $normalized);

        return $config['hr_title'] ?? $group;
    }

    public static function currentAlternateUrls(): array
    {
        $route = Route::current();

        if (! $route) {
            return [];
        }

        $name = $route->getName();
        $baseName = Str::startsWith((string) $name, 'en.') ? Str::after((string) $name, 'en.') : (string) $name;
        $params = $route->parameters();

        if (! Route::has($baseName)) {
            return [];
        }

        $alternates = [
            'hr' => self::route($baseName, $params, true, self::DEFAULT_LOCALE),
        ];

        if (Route::has('en.' . $baseName)) {
            $alternates['en'] = self::route($baseName, $params, true, self::ENGLISH_LOCALE);
        }

        return $alternates;
    }

    public static function languageSwitcherUrls(): array
    {
        $alternates = self::currentAlternateUrls();

        return collect(config('localization.locales', []))
            ->map(function ($settings, $locale) use ($alternates) {
                return [
                    'locale' => $locale,
                    'name' => $settings['native'] ?? strtoupper($locale),
                    'url' => $alternates[$locale] ?? url($locale === self::ENGLISH_LOCALE ? 'en' : '/'),
                    'active' => self::current() === $locale,
                ];
            })
            ->values()
            ->all();
    }

    public static function rawAttribute($model, string $field)
    {
        if ($model instanceof Model) {
            return $model->getRawOriginal($field);
        }

        if (is_array($model)) {
            return $model[$field] ?? null;
        }

        if (is_object($model)) {
            return $model->{$field} ?? null;
        }

        return null;
    }

    private static function hasFilledRawAttribute(Model $model, string $field): bool
    {
        $value = $model->getRawOriginal($field);

        return $value !== null && trim((string) $value) !== '';
    }

    private static function productCategoryPair(Product $product): array
    {
        if ($product->relationLoaded('categories')) {
            $subcategory = $product->categories->first(fn ($category) => (int) $category->parent_id !== 0);
            $category = $subcategory
                ? $product->categories->firstWhere('id', (int) $subcategory->parent_id)
                : null;
            $category = $category ?: $product->categories->firstWhere('parent_id', 0);

            if ($subcategory && (! $category || (int) $subcategory->parent_id !== (int) $category->id)) {
                $subcategory = null;
            }

            return [$category, $subcategory];
        }

        $subcategory = $product->subcategory();
        $category = $subcategory ? $subcategory->parent()->first() : $product->category();

        return [$category, $subcategory];
    }

    private static function resolveRouteParameterModel(string $key, $value)
    {
        if ($value instanceof Model) {
            return $value;
        }

        $class = [
            'prod' => Product::class,
            'product' => Product::class,
            'page' => Page::class,
            'blog' => Blog::class,
            'author' => Author::class,
            'publisher' => Publisher::class,
        ][$key] ?? null;

        if (! $class) {
            return $value;
        }

        return self::resolveModelFromSlug($class, (string) $value) ?: $value;
    }

    private static function resolveCategoryFromSlug(string $value)
    {
        return self::resolveModelFromSlug(Category::class, $value) ?: $value;
    }

    private static function resolveModelFromSlug(string $class, string $value)
    {
        if ($value === '') {
            return null;
        }

        $localized = self::isEnglish()
            ? $class::query()->where('slug_en', $value)->first()
            : null;

        return $localized
            ?: $class::query()->where('slug', $value)->first()
            ?: $class::query()->where('slug_en', $value)->first();
    }

    private static function normalizeGroupKey(string $group): string
    {
        $group = trim($group);

        foreach (config('localization.groups', []) as $key => $config) {
            if (in_array($group, [$key, $config['hr_slug'], $config['en_slug'], $config['hr_title'], $config['en_title']], true)) {
                return $key;
            }
        }

        return Str::slug($group);
    }
}
