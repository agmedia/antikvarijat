<?php

namespace App\Providers;

use App\Helpers\LocaleHelper;
use App\Models\Back\Catalog\Author as BackAuthor;
use App\Models\Back\Catalog\Publisher as BackPublisher;
use App\Models\Back\Marketing\Blog as BackBlog;
use App\Models\Back\Settings\Page as BackPage;
use App\Models\Front\Blog as FrontBlog;
use App\Models\Front\Catalog\Author as FrontAuthor;
use App\Models\Front\Catalog\Category as FrontCategory;
use App\Models\Front\Catalog\Product as FrontProduct;
use App\Models\Front\Catalog\Publisher as FrontPublisher;
use App\Models\Front\Page as FrontPage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    /// protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->removeIndexPHPFromURL();
        $this->registerLocalizedRouteBindings();
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }


    /**
     * Write code on Method
     *
     * @return response()
     */
    protected function removeIndexPHPFromURL()
    {
        if (config('app.env') == 'production') {
            if ( ! Str::contains(request()->fullUrl(), 'https://www.')) {
                $url = str_replace('https://', 'https://www.', request()->fullUrl());

                if (strlen($url) > 0) {
                    header("Location: $url", true, 301);
                    exit;
                }
            }
        }

        if (Str::contains(request()->getRequestUri(), '/index.php/')) {
            $url = str_replace('index.php/', '', request()->getRequestUri());

            if (strlen($url) > 0) {
                header("Location: $url", true, 301);
                exit;
            }
        }
    }

    protected function registerLocalizedRouteBindings(): void
    {
        Route::bind('cat', function ($value) {
            return $this->resolveFrontendSlug(FrontCategory::class, $value);
        });

        Route::bind('prod', function ($value) {
            return $this->resolveFrontendSlug(FrontProduct::class, $value);
        });

        Route::bind('subcat', function ($value) {
            if ($this->isFrontendRoute('catalog.route.author') ||
                $this->isFrontendRoute('catalog.route.publisher') ||
                $this->isFrontendRoute('catalog.route.actions')) {
                return $this->resolveFrontendSlug(FrontCategory::class, $value);
            }

            return $value;
        });

        Route::bind('page', function ($value) {
            if ($this->isFrontendRoute('catalog.route.page')) {
                return $this->resolveFrontendSlug(FrontPage::class, $value);
            }

            return BackPage::query()->whereKey($value)->firstOrFail();
        });

        Route::bind('blog', function ($value) {
            if ($this->isFrontendRoute('catalog.route.blog')) {
                return $this->resolveFrontendSlug(FrontBlog::class, $value);
            }

            return BackBlog::query()->whereKey($value)->firstOrFail();
        });

        Route::bind('author', function ($value) {
            if ($this->isFrontendRoute('catalog.route.author')) {
                return $this->resolveFrontendSlug(FrontAuthor::class, $value);
            }

            return BackAuthor::query()->whereKey($value)->firstOrFail();
        });

        Route::bind('publisher', function ($value) {
            if ($this->isFrontendRoute('catalog.route.publisher')) {
                return $this->resolveFrontendSlug(FrontPublisher::class, $value);
            }

            return BackPublisher::query()->whereKey($value)->firstOrFail();
        });
    }

    protected function resolveFrontendSlug(string $class, string $value)
    {
        $model = new $class();
        $routeKey = $model->getRouteKeyName();

        // A localized slug can legitimately be the same as another record's
        // Croatian slug. On English routes the exact EN slug must therefore
        // win before falling back to the Croatian route key.
        $resolved = LocaleHelper::isEnglish()
            ? $class::query()->where('slug_en', $value)->first()
            : null;

        $resolved = $resolved ?: $class::query()->where($routeKey, $value)->first();

        if ($resolved) {
            return $resolved;
        }

        // English product URLs were previously persisted with numeric IDs.
        // Keep those published links working while new links use slugs.
        if (ctype_digit($value)) {
            return $class::query()->whereKey((int) $value)->firstOrFail();
        }

        abort(404);
    }

    protected function isFrontendRoute(string $baseName): bool
    {
        $name = optional(request()->route())->getName();

        return $name === $baseName || $name === 'en.' . $baseName;
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60);
        });
    }
}
