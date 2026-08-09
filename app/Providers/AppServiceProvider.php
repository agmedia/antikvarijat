<?php

namespace App\Providers;

use App\Models\Front\Catalog\Category;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Page;
use App\Models\User;
use App\Models\ProductReview;
use App\Models\Back\Marketing\Wishlist;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;
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

        ResetPasswordNotification::createUrlUsing(function ($notifiable, string $token) {
            return route('reset.password.get', ['token' => $token]);
        });

        ResetPasswordNotification::toMailUsing(function ($notifiable, string $token) {
            return (new MailMessage)
                ->subject(__('front.email.password_heading'))
                ->view('emails.forget-password', ['token' => $token]);
        });

        $this->registerFrontendViewComposers();
        $this->registerAdminViewComposers();
    }

    protected function registerAdminViewComposers(): void
    {
        View::composer('back.layouts.partials.topbar', function ($view) {
            $counts = Cache::remember('admin.notification_counts', now()->addSeconds(30), function () {
                return [
                    'wishlist' => Schema::hasTable('wishlist') && Schema::hasTable('products')
                        ? Wishlist::query()
                            ->active()
                            ->unsent()
                            ->whereHas('product', fn ($product) => $product->active()->available())
                            ->count()
                        : 0,
                    'reviews' => Schema::hasTable('product_reviews')
                        ? ProductReview::query()->where('status', ProductReview::STATUS_PENDING)->count()
                        : 0,
                ];
            });

            $view->with('adminNotificationCounts', $counts);
        });
    }

    protected function registerFrontendViewComposers(): void
    {
        View::composer(['front.layouts.partials.footer', 'front.checkout.view'], function ($view) {
            $locale = app()->getLocale();

            $view->with('uvjeti_kupnje', $this->rememberFrontendViewData(
                'front.shared.uvjeti_kupnje.' . $locale,
                now()->addSeconds((int) config('cache.page_life', 86400)),
                function () {
                    return Page::query()
                        ->select('id', 'title', 'title_en', 'slug', 'slug_en', 'description', 'description_en')
                        ->where('subgroup', 'Uvjeti kupnje')
                        ->orderBy('title')
                        ->get();
                },
                collect()
            ));
        });

        View::composer('front.layouts.partials.footer', function ($view) {
            $locale = app()->getLocale();

            $view->with([
                'products' => $this->rememberFrontendViewData(
                    'front.shared.products_count.' . $locale,
                    now()->addMinutes(15),
                    function () {
                        return Product::query()->active()->hasStock()->count();
                    },
                    0
                ),
                'users' => $this->rememberFrontendViewData(
                    'front.shared.users_count.' . $locale,
                    now()->addMinutes(15),
                    function () {
                        return User::query()->count();
                    },
                    0
                ),
            ]);
        });

        View::composer('front.layouts.partials.header', function ($view) {
            $locale = app()->getLocale();

            $view->with([
                'knjige' => $this->rememberFrontendViewData(
                    'front.shared.knjige.' . $locale,
                    now()->addSeconds((int) config('cache.page_life', 86400)),
                    function () {
                        return Category::query()
                            ->active()
                            ->topList('Knjige')
                            ->sortByName()
                            ->select('id', 'title', 'title_en', 'group', 'slug', 'slug_en')
                            ->get();
                    },
                    collect()
                ),
                'zemljovidi_vedute' => $this->rememberFrontendViewData(
                    'front.shared.zemljovidi_vedute.' . $locale,
                    now()->addSeconds((int) config('cache.page_life', 86400)),
                    function () {
                        return Category::query()
                            ->active()
                            ->topList('Zemljovidi i vedute')
                            ->sortByName()
                            ->select('id', 'title', 'title_en', 'group', 'slug', 'slug_en')
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

        // Keep shared frontend data on the configured cache store so live can use Redis while local stays file-based.
        $cache = Cache::store(config('cache.default'));

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
