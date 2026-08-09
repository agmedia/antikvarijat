<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Metatags
{
    private const DEFAULT_ROBOTS = 'index,follow,max-image-preview:large';

    private const NOINDEX_ROBOTS = 'noindex,follow';

    private const PRIVATE_ROBOTS = 'noindex,follow,noarchive';

    private const PRIVATE_ROUTES = [
        'kosarica',
        'naplata',
        'pregled',
        'checkout',
        'checkout.success',
        'checkout.error',
        'moj-racun',
        'moje-narudzbe',
        'login',
        'register',
        'verification.notice',
        'password.request',
        'password.reset',
        'forget.password.get',
        'reset.password.get',
        'product-review-invitations.show',
        'product-review-invitations.store',
    ];

    private const FILTERED_QUERY_PARAMETERS = [
        'start',
        'end',
        'autor',
        'nakladnik',
        'sort',
        'letter',
    ];

    private const FILTERABLE_ROUTES = [
        'catalog.route',
        'catalog.route.author',
        'catalog.route.publisher',
        'catalog.route.actions',
    ];

    private const PAGINATED_ROUTES = [
        'catalog.route',
        'catalog.route.author',
        'catalog.route.publisher',
        'catalog.route.actions',
        'catalog.route.blog',
    ];

    private const SEARCH_ROUTES = [
        'pretrazi',
        'tag',
    ];

    public static function resolve(Request $request): array
    {
        return [
            'robots' => static::robots($request),
            'canonical' => static::canonical($request),
        ];
    }

    public static function robots(Request $request): string
    {
        $routeName = static::baseRouteName($request);

        if (in_array($routeName, self::PRIVATE_ROUTES, true)) {
            return self::PRIVATE_ROBOTS;
        }

        if (in_array($routeName, self::SEARCH_ROUTES, true)
            || static::hasFilteredQuery($request, $routeName)
            || static::hasPathFilter($request, $routeName)) {
            return self::NOINDEX_ROBOTS;
        }

        return self::DEFAULT_ROBOTS;
    }

    public static function canonical(Request $request): string
    {
        $routeName = static::baseRouteName($request);

        if (static::hasPathFilter($request, $routeName)) {
            return static::parentEntityUrl($request, $routeName);
        }

        $url = $request->url();

        if (in_array($routeName, self::SEARCH_ROUTES, true)
            || static::hasFilteredQuery($request, $routeName)) {
            return $url;
        }

        $canonicalQuery = static::canonicalQuery($request);

        if ($canonicalQuery) {
            return $url . '?' . http_build_query($canonicalQuery);
        }

        return $url;
    }

    public static function canonicalQuery(Request $request): array
    {
        $routeName = static::baseRouteName($request);

        if (in_array($routeName, self::SEARCH_ROUTES, true)
            || static::hasFilteredQuery($request, $routeName)
            || static::hasPathFilter($request, $routeName)) {
            return [];
        }

        $page = filter_var($request->query('page'), FILTER_VALIDATE_INT);

        return in_array($routeName, self::PAGINATED_ROUTES, true) && $page && $page > 1
            ? ['page' => $page]
            : [];
    }

    private static function baseRouteName(Request $request): string
    {
        $route = $request->route();
        $name = $route ? (string) $route->getName() : '';

        return Str::startsWith($name, 'en.') ? Str::after($name, 'en.') : $name;
    }

    private static function hasFilteredQuery(Request $request, string $routeName): bool
    {
        if (! in_array($routeName, self::FILTERABLE_ROUTES, true)) {
            return false;
        }

        return collect(self::FILTERED_QUERY_PARAMETERS)
            ->contains(fn (string $parameter) => array_key_exists($parameter, $request->query()));
    }

    private static function hasPathFilter(Request $request, string $routeName): bool
    {
        if (! in_array($routeName, ['catalog.route.author', 'catalog.route.publisher'], true)) {
            return false;
        }

        $route = $request->route();

        return $route && ($route->parameter('cat') || $route->parameter('subcat'));
    }

    private static function parentEntityUrl(Request $request, string $routeName): string
    {
        $route = $request->route();
        $parameter = $routeName === 'catalog.route.author' ? 'author' : 'publisher';
        $value = $route ? $route->parameter($parameter) : null;

        return LocaleHelper::route(
            $routeName,
            $value ? [$parameter => $value] : [],
            true,
            static::routeLocale($request)
        );
    }

    private static function routeLocale(Request $request): string
    {
        $route = $request->route();
        $name = $route ? (string) $route->getName() : '';

        return Str::startsWith($name, 'en.')
            ? LocaleHelper::ENGLISH_LOCALE
            : LocaleHelper::DEFAULT_LOCALE;
    }
}
