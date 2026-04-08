<?php

namespace App\Providers;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Page;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        $this->registerFrontendViewComposers();
    }

    protected function registerFrontendViewComposers(): void
    {
        View::composer(['front.layouts.partials.footer', 'front.checkout.view'], function ($view) {
            $view->with('uvjeti_kupnje', $this->rememberFrontendViewData(
                'front.shared.uvjeti_kupnje',
                now()->addSeconds((int) config('cache.page_life', 86400)),
                function () {
                    return Page::query()
                        ->select('id', 'title', 'slug', 'description')
                        ->where('subgroup', 'Uvjeti kupnje')
                        ->orderBy('title')
                        ->get();
                },
                collect()
            ));
        });

        View::composer('front.layouts.partials.footer', function ($view) {
            $view->with([
                'products' => $this->rememberFrontendViewData(
                    'front.shared.products_count',
                    now()->addMinutes(15),
                    function () {
                        return Product::query()->active()->hasStock()->count();
                    },
                    0
                ),
                'users' => $this->rememberFrontendViewData(
                    'front.shared.users_count',
                    now()->addMinutes(15),
                    function () {
                        return User::query()->count();
                    },
                    0
                ),
            ]);
        });

        View::composer('front.layouts.partials.header', function ($view) {
            $view->with([
                'knjige' => $this->rememberFrontendViewData(
                    'front.shared.knjige',
                    now()->addSeconds((int) config('cache.page_life', 86400)),
                    function () {
                        return Category::query()
                            ->active()
                            ->topList('Knjige')
                            ->sortByName()
                            ->select('id', 'title', 'group', 'slug')
                            ->get();
                    },
                    collect()
                ),
                'zemljovidi_vedute' => $this->rememberFrontendViewData(
                    'front.shared.zemljovidi_vedute',
                    now()->addSeconds((int) config('cache.page_life', 86400)),
                    function () {
                        return Category::query()
                            ->active()
                            ->topList('Zemljovidi i vedute')
                            ->sortByName()
                            ->select('id', 'title', 'group', 'slug')
                            ->get();
                    },
                    collect()
                ),
            ]);
        });
    }

    protected function rememberFrontendViewData(string $key, $ttl, callable $resolver, $default)
    {
        static $resolved = [];

        if (array_key_exists($key, $resolved)) {
            return $resolved[$key];
        }

        // Keep shared frontend data off MySQL so header/footer traffic does not amplify connection pressure.
        $cache = Cache::store('file');

        if ($cache->has($key)) {
            return $resolved[$key] = $cache->get($key, $default);
        }

        try {
            $value = $resolver();

            $cache->put($key, $value, $ttl);

            return $resolved[$key] = $value;
        } catch (\Throwable $e) {
            return $resolved[$key] = $default;
        }
    }
}
