<?php

use App\Actions\Fortify\ForgotPasswordController;
use App\Http\Controllers\Api\v2\CartController;
use App\Http\Controllers\Api\v2\FilterController;
use App\Http\Controllers\Back\Catalog\AuthorController;
use App\Http\Controllers\Back\Catalog\CategoryController;
use App\Http\Controllers\Back\Catalog\ProductController;
use App\Http\Controllers\Back\Catalog\PublisherController;
use App\Http\Controllers\Back\ContractWithdrawalController as AdminContractWithdrawalController;
use App\Http\Controllers\Back\ProductReviewController as AdminProductReviewController;
use App\Http\Controllers\Back\ProductReviewBackfillController as AdminProductReviewBackfillController;
use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\OrderController;
use App\Http\Controllers\Back\StatisticsController;
use App\Http\Controllers\Back\Marketing\ActionController;
use App\Http\Controllers\Back\Marketing\BlogController;
use App\Http\Controllers\Back\Marketing\BookPurchaseController;
use App\Http\Controllers\Back\Marketing\NewsletterSubscriberController;
use App\Http\Controllers\Back\Marketing\VialibriController;
use App\Http\Controllers\Back\Settings\ApiController;
use App\Http\Controllers\Back\Settings\App\CurrencyController;
use App\Http\Controllers\Back\Settings\App\GeoZoneController;
use App\Http\Controllers\Back\Settings\App\OrderStatusController;
use App\Http\Controllers\Back\Settings\App\PaymentController;
use App\Http\Controllers\Back\Settings\App\ShippingController;
use App\Http\Controllers\Back\Settings\App\TaxController;
use App\Http\Controllers\Back\Settings\FaqController;
use App\Http\Controllers\Back\Settings\GoogleApiController;
use App\Http\Controllers\Back\Settings\GoogleLoginSettingsController;
use App\Http\Controllers\Back\Settings\HistoryController;
use App\Http\Controllers\Back\Settings\PageController;
use App\Http\Controllers\Back\Settings\QuickMenuController;
use App\Http\Controllers\Back\Settings\SettingsController;
use App\Http\Controllers\Back\Settings\ContractWithdrawalSettingsController;
use App\Http\Controllers\Back\UserController;
use App\Http\Controllers\Back\Widget\WidgetController;
use App\Http\Controllers\Back\Widget\WidgetGroupController;
use App\Http\Controllers\Front\CatalogRouteController;
use App\Http\Controllers\Front\AbandonedCartRecoveryController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\ContractWithdrawalController;
use App\Http\Controllers\Front\CustomerController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\GoogleLoginController;
use App\Http\Controllers\Front\ProductReviewController;
use App\Http\Controllers\Front\VialibriFeedController;
use App\Http\Controllers\Front\WishlistTrackingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Back\Marketing\WishlistController;

Route::get('/prijava/google', [GoogleLoginController::class, 'redirect'])
    ->middleware('throttle:10,1')
    ->name('google.login.redirect');
Route::get('/prijava/google/povratak', [GoogleLoginController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('google.login.callback');


/*Route::domain('https://images.antikvarijatbibl.lin73.host25.com/')->group(function () {
    Route::get('media/img/products/{id}/{image}', function ($id, $image) {
        \Illuminate\Support\Facades\Log::info($id . ' --- ' . $image);
    });
});*/
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/**
 * BACK ROUTES
 */
Route::middleware(['auth:sanctum', 'verified', 'no.customers'])->prefix('admin')->group(function () {
    Route::match(['get', 'post'], '/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->middleware('not.editor')->group(function () {
        Route::get('/chart/month', [DashboardController::class, 'chartByMonth'])->name('chart.month');
        Route::get('/chart/year', [DashboardController::class, 'chartByYear'])->name('chart.year');
        Route::get('/chart/day', [DashboardController::class, 'chartByDay'])->name('chart.day');
    });
    Route::get('/dashboard/chart/range', [DashboardController::class, 'chartByRange'])->middleware('not.editor')->name('dashboard.chart.range');

    Route::get('statistike', [StatisticsController::class, 'index'])->middleware('not.editor')->name('statistics');
    Route::get('statistike/podaci', [StatisticsController::class, 'data'])->middleware('not.editor')->name('statistics.data');

    Route::get('setRoles', [DashboardController::class, 'setRoles'])->name('roles.set');
    Route::get('import', [DashboardController::class, 'import'])->name('import.initial');
    Route::get('mailing-test', [DashboardController::class, 'mailing'])->name('mailing.test');

    Route::get('letters', [DashboardController::class, 'letters'])->name('letters.import');
    Route::get('slugs', [DashboardController::class, 'slugs'])->name('slugs.revision');
    Route::get('statuses', [DashboardController::class, 'statuses'])->name('statuses.cron');
    Route::get('duplicate/{target?}', [DashboardController::class, 'duplicate'])->name('duplicate.revision');

    // CATALOG
    Route::prefix('catalog')->group(function () {
        // KATEGORIJE
        Route::get('categories', [CategoryController::class, 'index'])->name('categories');
        Route::get('category/create', [CategoryController::class, 'create'])->name('category.create');
        Route::post('category', [CategoryController::class, 'store'])->name('category.store');
        Route::get('category/{category}/edit', [CategoryController::class, 'edit'])->name('category.edit');
        Route::patch('category/{category}', [CategoryController::class, 'update'])->name('category.update');
        Route::delete('category/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');

        // IZDAVAČI
        Route::get('publishers', [PublisherController::class, 'index'])->name('publishers');
        Route::get('publisher/create', [PublisherController::class, 'create'])->name('publishers.create');
        Route::post('publisher', [PublisherController::class, 'store'])->name('publishers.store');
        Route::get('publisher/{publisher}/edit', [PublisherController::class, 'edit'])->name('publishers.edit');
        Route::patch('publisher/{publisher}', [PublisherController::class, 'update'])->name('publishers.update');
        Route::delete('publisher/{publisher}', [PublisherController::class, 'destroy'])->name('publishers.destroy');

        // AUTORI
        Route::get('authors', [AuthorController::class, 'index'])->name('authors');
        Route::get('author/create', [AuthorController::class, 'create'])->name('authors.create');
        Route::post('author', [AuthorController::class, 'store'])->name('authors.store');
        Route::get('author/{author}/edit', [AuthorController::class, 'edit'])->name('authors.edit');
        Route::patch('author/{author}', [AuthorController::class, 'update'])->name('authors.update');
        Route::delete('author/{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');

        // ARTIKLI
        Route::get('products', [ProductController::class, 'index'])->name('products');
        Route::get('product/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('product', [ProductController::class, 'store'])->name('products.store');
        Route::post('product/translate-description', [ProductController::class, 'translateDescription'])->name('products.translate.description');
        Route::get('product/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::get('product/{product}/photos', [ProductController::class, 'photos'])->name('products.photos');
        Route::patch('product/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('product/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });


    Route::get('/back/catalog/products/export/zero', [ProductController::class, 'exportZero'])
        ->name('products.export.zero');
    // NARUDŽBE
    Route::get('orders', [OrderController::class, 'index'])->name('orders');
    Route::get('order/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('order', [OrderController::class, 'store'])->name('orders.store');
    Route::get('order/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('order/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::patch('order/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('order/{order}/abandoned-cart-reminder', [OrderController::class, 'sendAbandonedCartReminder'])
        ->name('orders.abandoned-cart-reminder.send');

    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');

    // JEDNOSTRANI RASKIDI UGOVORA
    Route::get('contract-withdrawals', [AdminContractWithdrawalController::class, 'index'])->name('contract-withdrawals.index');
    Route::get('contract-withdrawals/{withdrawal}', [AdminContractWithdrawalController::class, 'show'])->name('contract-withdrawals.show');
    Route::patch('contract-withdrawals/{withdrawal}', [AdminContractWithdrawalController::class, 'update'])->name('contract-withdrawals.update');
    Route::post('contract-withdrawals/{withdrawal}/resend', [AdminContractWithdrawalController::class, 'resend'])->name('contract-withdrawals.resend');

    // RECENZIJE ARTIKALA
    Route::get('product-reviews', [AdminProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::patch('product-reviews/{review}', [AdminProductReviewController::class, 'update'])->name('product-reviews.update');
    Route::middleware(['not.editor', 'review.backfill.admin'])->group(function () {
        Route::get('product-review-requests', [AdminProductReviewBackfillController::class, 'index'])
            ->name('product-review-backfills.index');
        Route::post('product-review-requests', [AdminProductReviewBackfillController::class, 'store'])
            ->name('product-review-backfills.store');
        Route::post('product-review-requests/{backfill}/cancel', [AdminProductReviewBackfillController::class, 'cancel'])
            ->name('product-review-backfills.cancel');
    });

    // MARKETING
    Route::prefix('marketing')->group(function () {
        // AKCIJE
        Route::get('actions', [ActionController::class, 'index'])->name('actions');
        Route::get('action/create', [ActionController::class, 'create'])->name('actions.create');
        Route::post('action', [ActionController::class, 'store'])->name('actions.store');
        Route::get('action/{action}/edit', [ActionController::class, 'edit'])->name('actions.edit');
        Route::patch('action/{action}', [ActionController::class, 'update'])->name('actions.update');
        Route::delete('action/{action}', [ActionController::class, 'destroy'])->name('actions.destroy');

        // BLOG
        Route::get('blogs', [BlogController::class, 'index'])->name('blogs');
        Route::get('blog/create', [BlogController::class, 'create'])->name('blogs.create');
        Route::post('blog', [BlogController::class, 'store'])->name('blogs.store');
        Route::get('blog/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
        Route::patch('blog/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::delete('blog/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');

        // NEWSLETTER
        Route::get('newsletter', [NewsletterSubscriberController::class, 'index'])->name('newsletter.subscribers');
        Route::post('newsletter/clear-caches', [NewsletterSubscriberController::class, 'clearCaches'])->name('newsletter.caches.clear');
        // VIALIBRI
        Route::get('vialibri', [VialibriController::class, 'index'])->name('vialibri.index');
        Route::get('vialibri/config', [VialibriController::class, 'config'])->name('vialibri.config');
        Route::get('vialibri/autocomplete', [VialibriController::class, 'autocomplete'])->name('vialibri.autocomplete');
        Route::post('vialibri/product/{product}', [VialibriController::class, 'store'])->name('vialibri.store');
        Route::get('vialibri/{vialibriBook}/edit', [VialibriController::class, 'edit'])->name('vialibri.edit');
        Route::patch('vialibri/{vialibriBook}', [VialibriController::class, 'update'])->name('vialibri.update');
        Route::post('vialibri/{vialibriBook}/translate', [VialibriController::class, 'translate'])->name('vialibri.translate');
        Route::delete('vialibri/{vialibriBook}', [VialibriController::class, 'destroy'])->name('vialibri.destroy');
        // OTKUP KNJIGA
        Route::get('otkup-knjiga', [BookPurchaseController::class, 'index'])->name('book.purchases');
        Route::get('otkup-knjiga/tekst/uredi', [BookPurchaseController::class, 'editContent'])->name('book.purchases.content.edit');
        Route::patch('otkup-knjiga/tekst', [BookPurchaseController::class, 'updateContent'])->name('book.purchases.content.update');
        Route::get('otkup-knjiga/{purchase}', [BookPurchaseController::class, 'show'])->name('book.purchases.show');
        Route::delete('otkup-knjiga/{purchase}', [BookPurchaseController::class, 'destroy'])->name('book.purchases.destroy');
    });

    // KORISNICI
    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('user/create', [UserController::class, 'create'])->name('users.create');
    Route::post('user', [UserController::class, 'store'])->name('users.store');
    Route::get('user/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('user/{user}', [UserController::class, 'update'])->name('users.update');


    Route::get('wishlists', [WishlistController::class, 'index'])->name('wishlists');
    Route::post('wishlists/send-selected', [WishlistController::class, 'sendSelected'])->name('wishlists.send-selected');
    Route::post('wishlists/{wishlist}/send', [WishlistController::class, 'send'])->name('wishlists.send');
    Route::redirect('admin/wishlists', '/admin/wishlists');

    // WIDGETS
    Route::prefix('widgets')->group(function () {
        Route::get('/', [WidgetController::class, 'index'])->name('widgets');
        Route::get('create', [WidgetController::class, 'create'])->name('widget.create');
        Route::post('/', [WidgetController::class, 'store'])->name('widget.store');
        Route::get('{widget}/edit', [WidgetController::class, 'edit'])->name('widget.edit');
        Route::patch('{widget}', [WidgetController::class, 'update'])->name('widget.update');
        // GROUP
        Route::prefix('groups')->group(function () {
            Route::get('create', [WidgetGroupController::class, 'create'])->name('widget.group.create');
            Route::post('/', [WidgetGroupController::class, 'store'])->name('widget.group.store');
            Route::get('{widget}/edit', [WidgetGroupController::class, 'edit'])->name('widget.group.edit');
            Route::patch('{widget}', [WidgetGroupController::class, 'update'])->name('widget.group.update');
        });
    });

    // POSTAVKE
    Route::prefix('settings')->group(function () {
        // API
        Route::get('api', [ApiController::class, 'index'])->name('api.index');
        Route::get('google-api', [GoogleApiController::class, 'index'])->name('google.api.index');
        Route::post('google-api/translate/start', [GoogleApiController::class, 'start'])->name('google.api.translate.start');
        Route::get('google-api/translate/{job}', [GoogleApiController::class, 'status'])->name('google.api.translate.status');
        Route::post('google-api/translate/{job}/process', [GoogleApiController::class, 'process'])->name('google.api.translate.process');
        Route::post('google-api/translate/{job}/cancel', [GoogleApiController::class, 'cancel'])->name('google.api.translate.cancel');
        Route::get('google-login', [GoogleLoginSettingsController::class, 'edit'])->name('google-login.edit');
        Route::patch('google-login', [GoogleLoginSettingsController::class, 'update'])->name('google-login.update');
        // INFO PAGES
        Route::get('pages', [PageController::class, 'index'])->name('pages');
        Route::get('page/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('page', [PageController::class, 'store'])->name('pages.store');
        Route::get('page/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::patch('page/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('page/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

        // FAQ
        Route::get('faqs', [FaqController::class, 'index'])->name('faqs');
        Route::get('faq/create', [FaqController::class, 'create'])->name('faqs.create');
        Route::post('faq', [FaqController::class, 'store'])->name('faqs.store');
        Route::get('faq/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
        Route::patch('faq/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::delete('faq/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

        //Route::get('application', [SettingsController::class, 'index'])->name('settings');

        Route::prefix('application')->group(function () {
            // GEO ZONES
            Route::get('geo-zones', [GeoZoneController::class, 'index'])->name('geozones');
            Route::get('geo-zone/create', [GeoZoneController::class, 'create'])->name('geozones.create');
            Route::post('geo-zone', [GeoZoneController::class, 'store'])->name('geozones.store');
            Route::get('geo-zone/{geozone}/edit', [GeoZoneController::class, 'edit'])->name('geozones.edit');
            Route::patch('geo-zone/{geozone}', [GeoZoneController::class, 'store'])->name('geozones.update');
            Route::delete('geo-zone/{geozone}', [GeoZoneController::class, 'destroy'])->name('geozones.destroy');
            //
            Route::get('order-statuses', [OrderStatusController::class, 'index'])->name('order.statuses');
            //
            Route::get('shippings', [ShippingController::class, 'index'])->name('shippings');
            Route::get('payments', [PaymentController::class, 'index'])->name('payments');
            Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
            Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies');
        });

        // JEDNOSTRANI RASKID UGOVORA
        Route::get('contract-withdrawals', [ContractWithdrawalSettingsController::class, 'edit'])->name('contract-withdrawal-settings.edit');
        Route::patch('contract-withdrawals', [ContractWithdrawalSettingsController::class, 'update'])->name('contract-withdrawal-settings.update');

        // HISTORY
        Route::get('history', [HistoryController::class, 'index'])->name('history');
        Route::get('history/log/{history}', [HistoryController::class, 'show'])->name('history.show');
    });

    // SETTINGS
    Route::get('/clean/cache', [QuickMenuController::class, 'cache'])->name('cache');
    Route::get('maintenance/on', [QuickMenuController::class, 'maintenanceModeON'])->name('maintenance.on');
    Route::get('maintenance/off', [QuickMenuController::class, 'maintenanceModeOFF'])->name('maintenance.off');
});

/**
 * CUSTOMER BACK ROUTES
 */
Route::middleware(['auth:sanctum', 'verified', 'no.administrators'])->prefix('moj-racun')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('moj-racun');
    Route::patch('/snimi/{user}', [CustomerController::class, 'save'])->name('moj-racun.snimi');
    Route::get('/narudzbe', [CustomerController::class, 'orders'])->name('moje-narudzbe');
    Route::post('/narudzbe/{order}/tracking/osvjezi', [CustomerController::class, 'refreshOrderTracking'])->name('moje-narudzbe.tracking.refresh');
    Route::get('/dojmovi', [CustomerController::class, 'reviews'])->name('moji-dojmovi');
    Route::get('/preporuke', [CustomerController::class, 'recommendations'])->name('preporuke-za-vas');
});

Route::middleware(['auth:sanctum', 'verified', 'no.administrators'])->prefix('en/my-account')->as('en.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('moj-racun');
    Route::patch('/save/{user}', [CustomerController::class, 'save'])->name('moj-racun.snimi');
    Route::get('/orders', [CustomerController::class, 'orders'])->name('moje-narudzbe');
    Route::post('/orders/{order}/tracking/refresh', [CustomerController::class, 'refreshOrderTracking'])->name('moje-narudzbe.tracking.refresh');
    Route::get('/reviews', [CustomerController::class, 'reviews'])->name('moji-dojmovi');
    Route::get('/recommendations', [CustomerController::class, 'recommendations'])->name('preporuke-za-vas');
});

/**
 * API Routes
 */
Route::prefix('api/v2')->group(function () {
    // SEARCH
    Route::get('pretrazi/autocomplete', [CatalogRouteController::class, 'search'])->name('api.front.autocomplete');
    Route::get('pretrazi', [CatalogRouteController::class, 'search'])->name('api.front.search');


    // CART
    Route::prefix('cart')->group(function () {
        Route::get('/get', [CartController::class, 'get']);
        Route::post('/check', [CartController::class, 'check']);
        Route::post('/add', [CartController::class, 'add']);
        Route::post('/update/{id}', [CartController::class, 'update']);
        Route::get('/remove/{id}', [CartController::class, 'remove']);
        Route::get('/coupon/{coupon}', [CartController::class, 'coupon']);
        //
        Route::post('/provjeri-stanje-artikala', [CartController::class, 'provjeriStanje']);
    });

    Route::get('/products/autocomplete', [\App\Http\Controllers\Api\v2\ProductController::class, 'autocomplete'])->name('products.autocomplete');
    Route::post('/products/image/delete', [\App\Http\Controllers\Api\v2\ProductController::class, 'destroyImage'])->name('products.destroy.image');
    Route::post('/products/change/status', [\App\Http\Controllers\Api\v2\ProductController::class, 'changeStatus'])->name('products.change.status');
    Route::post('products/update-item/single', [\App\Http\Controllers\Api\v2\ProductController::class, 'updateItem'])->name('products.update.item');

    Route::post('/actions/destroy/api', [ActionController::class, 'destroyApi'])->name('actions.destroy.api');
    Route::post('/authors/destroy/api', [AuthorController::class, 'destroyApi'])->name('authors.destroy.api');
    Route::post('/publishers/destroy/api', [PublisherController::class, 'destroyApi'])->name('publishers.destroy.api');
    Route::post('/products/destroy/api', [ProductController::class, 'destroyApi'])->name('products.destroy.api');
    Route::post('/blogs/destroy/api', [BlogController::class, 'destroyApi'])->name('blogs.destroy.api');
    Route::post('/blogs/upload/image', [BlogController::class, 'uploadBlogImage'])->name('blogs.upload.image');

    // FILTER
    Route::prefix('filter')->group(function () {
        Route::post('/getCategories', [FilterController::class, 'categories']);
        Route::post('/getProducts', [FilterController::class, 'products']);
        Route::post('/getAuthors', [FilterController::class, 'authors']);
        Route::post('/getPublishers', [FilterController::class, 'publishers']);
    });

    // SETTINGS
    Route::prefix('settings')->group(function () {
        // FRONT SETTINGS LIST
        Route::get('/get', [SettingsController::class, 'get']);
        // WIDGET
        Route::prefix('widget')->group(function () {
            Route::post('destroy', [WidgetController::class, 'destroy'])->name('widget.destroy');
            Route::get('get-links', [WidgetController::class, 'getLinks'])->name('widget.api.get-links');
        });
        // API
        Route::prefix('api')->group(function () {
            Route::post('import', [ApiController::class, 'import'])->name('api.api.import');
            Route::post('upload/excel', [ApiController::class, 'upload'])->name('api.api.upload');
        });
        // APPLICATION SETTINGS
        Route::prefix('app')->group(function () {
            // GEO ZONE
            /*Route::prefix('geo-zone')->group(function () {
                Route::post('get-state-zones', 'Back\Settings\Store\GeoZoneController@getStateZones')->name('geo-zone.get-state-zones');
                Route::post('store', 'Back\Settings\Store\GeoZoneController@store')->name('geo-zone.store');
                Route::post('destroy', 'Back\Settings\Store\GeoZoneController@destroy')->name('geo-zone.destroy');
            });*/
            // ORDER STATUS
            Route::prefix('order-status')->group(function () {
                Route::post('store', [OrderStatusController::class, 'store'])->name('api.order.status.store');
                Route::post('destroy', [OrderStatusController::class, 'destroy'])->name('api.order.status.destroy');
                Route::post('change', [OrderController::class, 'api_status_change'])->name('api.order.status.change');
                Route::post('send/gls', [OrderController::class, 'api_send_gls'])->name('api.order.send.gls');
                Route::post('send/tracking-email', [OrderController::class, 'api_send_tracking_email'])->name('api.order.send.tracking-email');
                Route::post('tracking/refresh', [OrderController::class, 'api_refresh_tracking'])->name('api.order.tracking.refresh');
            });
            // PAYMENTS
            Route::prefix('payment')->group(function () {
                Route::post('store', [PaymentController::class, 'store'])->name('api.payment.store');
                Route::post('destroy', [PaymentController::class, 'destroy'])->name('api.payment.destroy');
            });
            // SHIPMENTS
            Route::prefix('shipping')->group(function () {
                Route::post('store', [ShippingController::class, 'store'])->name('api.shipping.store');
                Route::post('destroy', [ShippingController::class, 'destroy'])->name('api.shipping.destroy');
            });
            // TAXES
            Route::prefix('taxes')->group(function () {
                Route::post('store', [TaxController::class, 'store'])->name('api.taxes.store');
                Route::post('destroy', [TaxController::class, 'destroy'])->name('api.taxes.destroy');
            });
            // CURRENCIES
            Route::prefix('currencies')->group(function () {
                Route::post('store', [CurrencyController::class, 'store'])->name('api.currencies.store');
                Route::post('store/main', [CurrencyController::class, 'storeMain'])->name('api.currencies.store.main');
                Route::post('destroy', [CurrencyController::class, 'destroy'])->name('api.currencies.destroy');
            });
            // TOTALS
            /*Route::prefix('totals')->group(function () {
                Route::post('store', 'Back\Settings\Store\TotalController@store')->name('totals.store');
                Route::post('destroy', 'Back\Settings\Store\TotalController@destroy')->name('totals.destroy');
            });*/
        });
    });
});

/*Route::get('/phpinfo', function () {
    return phpinfo();
})->name('index');*/

/**
 * FRONT ROUTES
 */
Route::get('/', [HomeController::class, 'index'])->name('index');
Route::get('/kontakt', [HomeController::class, 'contact'])->name('kontakt');
Route::post('/kontakt/posalji', [HomeController::class, 'sendContactMessage'])->name('poruka');
Route::get('/forma-za-povrat-i-reklamacije', [ContractWithdrawalController::class, 'create'])->name('contract-withdrawal.create');
Route::post('/forma-za-povrat-i-reklamacije', [ContractWithdrawalController::class, 'review'])
    ->middleware('throttle:5,10')
    ->name('contract-withdrawal.review');
Route::post('/forma-za-povrat-i-reklamacije/potvrdi', [ContractWithdrawalController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contract-withdrawal.store');
Route::get('/otkup-knjiga', [HomeController::class, 'bookPurchase'])->name('otkup.knjiga');
Route::post('/otkup-knjiga/posalji', [HomeController::class, 'sendBookPurchaseMessage'])->name('otkup.knjiga.posalji');
Route::post('/newsletter/prijava', [HomeController::class, 'newsletter'])->name('newsletter.subscribe');
Route::get('/faq', [CatalogRouteController::class, 'faq'])->name('faq');
//
Route::post('/dodaj-u-listu-zelja', [HomeController::class, 'wishlist'])->name('wishlist');
Route::get('/wishlist-obavijest/{wishlist}', WishlistTrackingController::class)
    ->middleware(['signed', 'throttle:60,1'])
    ->name('wishlist.track');
Route::get('/recenzije', [ProductReviewController::class, 'index'])->name('reviews.index');
Route::post('/recenzije', [ProductReviewController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('product-reviews.store');
Route::get('/zahtjev-za-recenziju/{token}', [ProductReviewController::class, 'invitation'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('product-review-invitations.show');
Route::post('/zahtjev-za-recenziju/{token}', [ProductReviewController::class, 'storeInvitation'])
    ->middleware(['signed', 'throttle:10,10'])
    ->name('product-review-invitations.store');

Route::get('/kosarica', [CheckoutController::class, 'cart'])->name('kosarica');
Route::get('/kosarica/vrati/{order}', AbandonedCartRecoveryController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('abandoned-cart.restore');
Route::get('/naplata', [CheckoutController::class, 'checkout'])->name('naplata');
Route::get('/pregled', [CheckoutController::class, 'view'])->name('pregled');
Route::get('/narudzba', [CheckoutController::class, 'order'])->name('checkout');
Route::get('/uspjeh', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/greska', [CheckoutController::class, 'error'])->name('checkout.error');
//
Route::get('pretrazi', [CatalogRouteController::class, 'search'])->name('pretrazi');
Route::get('tag', [CatalogRouteController::class, 'tag'])->name('tag');
//
Route::get('info/{page}', [CatalogRouteController::class, 'page'])->name('catalog.route.page');
Route::get('blog/{blog?}', [CatalogRouteController::class, 'blog'])->name('catalog.route.blog');
//
Route::get('cache/image', [HomeController::class, 'imageCache']);
Route::get('cache/thumb', [HomeController::class, 'thumbCache']);

/**
 * EN FRONT ROUTES
 *
 * Hrvatski URL-ovi ostaju bez prefiksa i bez promjene. Engleska verzija je
 * zaseban sloj pod /en s vlastitim imenima ruta (en.*).
 */
Route::prefix('en')->as('en.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
    Route::get('/contact', [HomeController::class, 'contact'])->name('kontakt');
    Route::post('/contact/send', [HomeController::class, 'sendContactMessage'])->name('poruka');
    Route::get('/returns-and-complaints', [ContractWithdrawalController::class, 'create'])->name('contract-withdrawal.create');
    Route::post('/returns-and-complaints', [ContractWithdrawalController::class, 'review'])
        ->middleware('throttle:5,10')
        ->name('contract-withdrawal.review');
    Route::post('/returns-and-complaints/confirm', [ContractWithdrawalController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('contract-withdrawal.store');
    Route::get('/book-purchase', [HomeController::class, 'bookPurchase'])->name('otkup.knjiga');
    Route::post('/book-purchase/send', [HomeController::class, 'sendBookPurchaseMessage'])->name('otkup.knjiga.posalji');
    Route::post('/newsletter/subscribe', [HomeController::class, 'newsletter'])->name('newsletter.subscribe');
    Route::get('/faq', [CatalogRouteController::class, 'faq'])->name('faq');
    Route::post('/wishlist/add', [HomeController::class, 'wishlist'])->name('wishlist');
    Route::get('/reviews', [ProductReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [ProductReviewController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('product-reviews.store');

    Route::get('/cart', [CheckoutController::class, 'cart'])->name('kosarica');
    Route::get('/cart/restore/{order}', AbandonedCartRecoveryController::class)
        ->middleware(['signed', 'throttle:30,1'])
        ->name('abandoned-cart.restore');
    Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('naplata');
    Route::get('/checkout/review', [CheckoutController::class, 'view'])->name('pregled');
    Route::get('/checkout/order', [CheckoutController::class, 'order'])->name('checkout');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/error', [CheckoutController::class, 'error'])->name('checkout.error');

    Route::get('search', [CatalogRouteController::class, 'search'])->name('pretrazi');
    Route::get('tag', [CatalogRouteController::class, 'tag'])->name('tag');

    Route::get('info/{page}', [CatalogRouteController::class, 'page'])->name('catalog.route.page');
    Route::get('blog/{blog?}', [CatalogRouteController::class, 'blog'])->name('catalog.route.blog');

    Route::get('authors/{author?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'author'])->name('catalog.route.author');
    Route::get('publishers/{publisher?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'publisher'])->name('catalog.route.publisher');
    Route::get('sale/{cat?}/{subcat?}', [CatalogRouteController::class, 'actions'])->name('catalog.route.actions');

    Route::get('{group}/{cat?}/{subcat?}/{prod?}', [CatalogRouteController::class, 'resolve'])
        ->where('group', 'books|maps-and-views')
        ->name('catalog.route');
});
/**
 * Sitemap routes
 */
$statelessSitemapMiddleware = [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
];

Route::get('/sitemap.xml', [HomeController::class, 'sitemapXML'])
    ->withoutMiddleware($statelessSitemapMiddleware);
Route::get('sitemap/{sitemap?}', [HomeController::class, 'sitemapXML'])
    ->withoutMiddleware($statelessSitemapMiddleware)
    ->name('sitemap');
Route::get('image-sitemap/{shard?}', [HomeController::class, 'sitemapImageXML'])
    ->withoutMiddleware($statelessSitemapMiddleware)
    ->name('image-sitemap');
//
Route::get('njuskalo/biblos/xml', [HomeController::class, 'njuskaloXML'])->name('njuskalo');
Route::get('vialibri/sync.xml', [VialibriFeedController::class, 'sync'])->name('vialibri.feed.sync');
Route::get('vialibri/data.xml', [VialibriFeedController::class, 'data'])->name('vialibri.feed.data');
/**
 * Forgot password & login routes.
 */
Route::get('forgot-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.get');
Route::post('forgot-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
Route::get('reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');
Route::post('reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');
/*
 * Groups, Categories and Products routes resolver.
 * https://www.antikvarijat-biblos.hr/kategorija-proizvoda/knjige/
 */
Route::get('proizvod/{prod?}/', [CatalogRouteController::class, 'resolveOldUrl']);
Route::get('kategorija-proizvoda/{group?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'resolveOldCategoryUrl']);
//
Route::get(config('settings.author_path') . '/{author?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'author'])->name('catalog.route.author');
Route::get(config('settings.publisher_path') . '/{publisher?}/{cat?}/{subcat?}', [CatalogRouteController::class, 'publisher'])->name('catalog.route.publisher');
//
Route::get('snizenja/{cat?}/{subcat?}', [CatalogRouteController::class, 'actions'])->name('catalog.route.actions');
//
Route::get('{path}', function () {
    abort(404);
})->where('path', '.*\.[^/]+$');
//
Route::get('{group}/{cat?}/{subcat?}/{prod?}', [CatalogRouteController::class, 'resolve'])->name('catalog.route');


Route::fallback(function () {
    return response()->view('front.404', [], 404);
});
