<?php

namespace Tests\Feature;

use App\Http\Controllers\Back\DashboardController;
use App\Http\Controllers\Back\StatisticsController;
use App\Helpers\Currency;
use App\Models\Back\Orders\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;
use Bouncer;

class DashboardSalesStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->registerDateFunctions();
        $this->createDashboardSchema();
        $this->seedOrderStatuses();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_new_and_paid_orders_count_as_completed_dashboard_sales(): void
    {
        Carbon::setTestNow('2026-04-15 12:00:00');

        $newOrder = $this->createOrder(1, 40.00, '2026-04-15 09:00:00');
        $paidOrder = $this->createOrder(3, 60.00, '2026-04-15 09:30:00');
        $sentOrder = $this->createOrder(4, 30.00, '2026-04-15 10:00:00');

        $this->createOrderProduct($newOrder, 2);
        $this->createOrderProduct($paidOrder, 4);
        $this->createOrderProduct($sentOrder, 1);

        $this->createOrder(2, 100.00, '2026-04-15 10:30:00');
        $this->createOrder(5, 200.00, '2026-04-15 10:40:00');
        $this->createOrder(6, 300.00, '2026-04-15 10:50:00');
        $this->createOrder(7, 400.00, '2026-04-15 11:00:00');
        $this->createOrder(8, 500.00, '2026-04-15 11:10:00');

        $view = app(DashboardController::class)->index();
        $data = $view->getData()['data'];

        $this->assertSame([1, 3, 4], Order::dashboardCompletedStatusIds());
        $this->assertSame(3, $data['finished']);
        $this->assertSame(130.00, round((float) $data['finished_total'], 2));
        $this->assertSame(3, $data['today']);
        $this->assertSame(130.00, round((float) $data['today_total'], 2));
        $this->assertSame(3, $data['this_month']);
        $this->assertSame(130.00, round((float) $data['this_month_total'], 2));
        $this->assertSame(2.33, $data['today_items_average']);
        $this->assertSame(2.33, $data['finished_items_average']);

        $this->assertSame(3, (new Order())->filter(new Request(['dashboard_group' => 'sales']))->count());
        $this->assertSame('0,00 €', Currency::main(0, true));

        View::share('errors', new ViewErrorBag());
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturn(false);
        $this->actingAs($user);
        $html = $view->render();
        $this->assertStringContainsString('Statistika prometa', $html);
        $this->assertStringContainsString('Način plaćanja', $html);
        $this->assertStringContainsString('Način dostave', $html);
        $this->assertStringNotContainsString('Zamatanje', $html);
        $this->assertStringNotContainsString('Čekaju uplatu', $html);
        $this->assertStringNotContainsString('Pravilo statistike', $html);
        $this->assertStringContainsString('href="' . route('statistics') . '"', $html);
        $this->assertStringContainsString('Detaljne statistike', $html);
        $this->assertStringNotContainsString('dashboard-date', $html);
    }

    public function test_monthly_statistics_include_items_payment_shipping_and_status_breakdowns(): void
    {
        $newOrder = $this->createOrder(1, 40.00, '2026-04-10 09:00:00', 'Kartica', 'GLS');
        $paidOrder = $this->createOrder(3, 60.00, '2026-04-10 10:00:00', 'Kartica', 'Osobno preuzimanje');
        $sentOrder = $this->createOrder(4, 50.00, '2026-04-10 11:00:00', 'Virman', 'GLS');

        $this->createOrderProduct($newOrder, 2);
        $this->createOrderProduct($paidOrder, 3);
        $this->createOrderProduct($sentOrder, 1);

        $this->createOrder(2, 500.00, '2026-04-10 12:00:00', 'Kartica', 'GLS');
        $this->createOrder(5, 500.00, '2026-04-10 13:00:00', 'Kartica', 'GLS');

        $response = app(DashboardController::class)->chartByMonth(
            Request::create('/admin/dashboard/chart/month', 'GET', ['year' => 2026, 'month' => 4])
        );
        $payload = $response->getData(true);

        $this->assertSame(3, $payload['summary']['orders']);
        $this->assertSame(150.00, round((float) $payload['summary']['total'], 2));
        $this->assertSame(6, $payload['summary']['item_quantity']);
        $this->assertSame(2, $payload['summary']['avg_items']);

        $this->assertEquals([
            ['label' => 'Kartica', 'orders' => 2, 'total' => 100.0],
            ['label' => 'Virman', 'orders' => 1, 'total' => 50.0],
        ], $payload['summary']['payment_methods']);

        $this->assertEquals([
            ['label' => 'GLS', 'orders' => 2, 'total' => 90.0],
            ['label' => 'Osobno preuzimanje', 'orders' => 1, 'total' => 60.0],
        ], $payload['summary']['shipping_methods']);

        $this->assertEqualsCanonicalizing(
            ['Novo', 'Plaćeno', 'Poslano'],
            array_column($payload['summary']['statuses'], 'label')
        );

        $this->assertCount(1, $payload['days']);
        $this->assertSame(3, (int) $payload['days'][0]['orders']);
        $this->assertSame(6, (int) $payload['days'][0]['item_quantity']);
    }

    public function test_detailed_statistics_use_sales_statuses_and_render_the_new_page(): void
    {
        Carbon::setTestNow('2026-04-30 12:00:00');

        $previousOrder = $this->createOrder(3, 25.00, '2026-03-20 09:00:00');
        DB::table('orders')->where('id', $previousOrder)->update([
            'payment_email' => 'povratni@example.com',
            'shipping_state' => 'Croatia',
            'shipping_city' => 'Zagreb 10000',
        ]);

        $newOrder = $this->createOrder(1, 40.00, '2026-04-10 09:00:00', 'Pouzeće', 'GLS');
        $paidOrder = $this->createOrder(3, 60.00, '2026-04-11 10:00:00', 'Kartica', 'Paketomat');
        $sentOrder = $this->createOrder(4, 30.00, '2026-04-12 11:00:00', 'Kartica', 'GLS');
        $waitingOrder = $this->createOrder(2, 500.00, '2026-04-13 12:00:00', 'Kartica', 'GLS');
        $cancelledOrder = $this->createOrder(5, 800.00, '2026-04-14 12:00:00', 'Kartica', 'GLS');

        DB::table('orders')->where('id', $newOrder)->update([
            'payment_email' => 'povratni@example.com',
            'shipping_state' => 'Croatia',
            'shipping_city' => 'Zagreb 10090',
            'user_id' => 12,
        ]);
        DB::table('orders')->where('id', $paidOrder)->update([
            'payment_email' => 'novi@example.com',
            'shipping_state' => 'Croatia',
            'shipping_city' => 'Split 21000',
        ]);
        DB::table('orders')->where('id', $sentOrder)->update([
            'payment_email' => 'NOVI@example.com',
            'shipping_state' => 'Germany',
            'shipping_city' => 'Berlin',
        ]);
        DB::table('orders')->whereIn('id', [$waitingOrder, $cancelledOrder])->update([
            'payment_email' => 'izvan-prometa@example.com',
            'shipping_state' => 'Croatia',
            'shipping_city' => 'Rijeka 51000',
        ]);

        $this->createOrderProduct($newOrder, 2);
        $this->createOrderProduct($paidOrder, 4);
        $this->createOrderProduct($sentOrder, 1);
        $this->createOrderProduct($waitingOrder, 50);
        $this->seedCatalogForProduct(100 + $newOrder, 'Prva knjiga');
        $this->seedCatalogForProduct(100 + $paidOrder, 'Druga knjiga');
        $this->seedCatalogForProduct(100 + $sentOrder, 'Treća knjiga');

        $response = app(StatisticsController::class)->data(Request::create(
            '/admin/statistike/podaci',
            'GET',
            ['from' => '2026-04-01', 'to' => '2026-04-30']
        ));
        $payload = $response->getData(true);

        $this->assertSame(3, $payload['summary']['orders']);
        $this->assertSame(130.0, round($payload['summary']['total'], 2));
        $this->assertSame(7, $payload['summary']['items']);
        $this->assertSame(2, $payload['summary']['customers']);
        $this->assertSame(1, $payload['customers']['new']);
        $this->assertSame(1, $payload['customers']['returning']);
        $this->assertSame(1, $payload['customers']['registered_orders']);
        $this->assertCount(30, $payload['trend']['series']);
        $this->assertNotEmpty($payload['heatmap']);
        $this->assertSame(['Croatia', 'Germany'], array_column($payload['geography']['countries'], 'name'));
        $this->assertEqualsCanonicalizing(['Split', 'Zagreb'], array_column($payload['geography']['cities'], 'name'));
        $this->assertCount(3, $payload['products']['top_products']);

        $statuses = collect($payload['operations']['statuses'])->keyBy('id');
        $this->assertTrue($statuses[1]['included_in_sales']);
        $this->assertTrue($statuses[3]['included_in_sales']);
        $this->assertTrue($statuses[4]['included_in_sales']);
        $this->assertFalse($statuses[2]['included_in_sales']);
        $this->assertFalse($statuses[5]['included_in_sales']);

        View::share('errors', new ViewErrorBag());
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturn(false);
        $this->actingAs($user);
        $html = app(StatisticsController::class)->index()->render();
        $this->assertStringContainsString('Detaljne statistike', $html);
        $this->assertStringContainsString('statistics-world-map', $html);
        $this->assertStringContainsString('statistics-europe-map', $html);
        $this->assertStringContainsString('statistics-croatia-map', $html);
        $this->assertStringContainsString('data-croatia-zoom="in"', $html);
        $this->assertStringContainsString('Kotačić za zoom', $html);
        $this->assertStringContainsString('croatia-counties.geojson', $html);
        $this->assertStringContainsString('const endpoint =', $html);
        $this->assertStringContainsString('statistike\\/podaci', $html);
        $this->assertStringNotContainsString('Zamatanje', $html);
    }

    public function test_editor_cannot_access_or_see_sales_statistics(): void
    {
        $editor = User::query()->create([
            'name' => 'Urednik',
            'email' => 'urednik@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $role = Bouncer::role()->create(['name' => 'editor', 'title' => 'Editor']);
        Bouncer::assign($role)->to($editor);
        Bouncer::refresh();

        $this->actingAs($editor);

        $this->get(route('statistics'))->assertForbidden();
        $this->get(route('statistics.data', ['from' => '2026-04-01', 'to' => '2026-04-30']))->assertForbidden();
        $this->get(route('dashboard.chart.month', ['year' => 2026, 'month' => 4]))->assertForbidden();

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('Detaljne statistike');
        $response->assertDontSee('Statistika prometa');
        $response->assertDontSee('Prodaja po razdobljima');
        $response->assertSee('Zadnje narudžbe');
        $response->assertSee('Zadnje prodani artikli');
    }

    private function registerDateFunctions(): void
    {
        $pdo = DB::connection('sqlite')->getPdo();

        $pdo->sqliteCreateFunction('YEAR', function ($value) {
            return (int) date('Y', strtotime($value));
        }, 1);
        $pdo->sqliteCreateFunction('MONTH', function ($value) {
            return (int) date('n', strtotime($value));
        }, 1);
        $pdo->sqliteCreateFunction('DAY', function ($value) {
            return (int) date('j', strtotime($value));
        }, 1);
        $pdo->sqliteCreateFunction('HOUR', function ($value) {
            return (int) date('G', strtotime($value));
        }, 1);
    }

    private function createDashboardSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('abilities', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->boolean('only_owned')->default(false);
            $table->string('options')->nullable();
            $table->integer('scope')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->unsignedInteger('level')->nullable();
            $table->integer('scope')->nullable();
            $table->timestamps();
        });

        Schema::create('assigned_roles', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('restricted_to_id')->nullable();
            $table->string('restricted_to_type')->nullable();
            $table->integer('scope')->nullable();
        });

        Schema::create('permissions', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ability_id');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->boolean('forbidden')->default(false);
            $table->integer('scope')->nullable();
        });

        Schema::create('orders', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('order_status_id');
            $table->decimal('total', 15, 4)->default(0);
            $table->string('payment_fname');
            $table->string('payment_lname');
            $table->string('payment_method');
            $table->string('shipping_method');
            $table->string('payment_email')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_city')->nullable();
            $table->unsignedInteger('user_id')->default(0);
            $table->timestamps();
        });

        Schema::create('order_products', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('org_price', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->nullable();
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('products', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('author_id')->default(0);
            $table->unsignedInteger('publisher_id')->default(0);
            $table->string('name');
            $table->integer('quantity')->default(0);
        });

        Schema::create('authors', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        Schema::create('publishers', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        Schema::create('categories', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->default(0);
            $table->string('title');
        });

        Schema::create('product_category', function ($table) {
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('category_id');
        });

        Schema::create('wishlist', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->boolean('status')->default(true);
        });

        Schema::create('settings', function ($table) {
            $table->increments('id');
            $table->string('code');
            $table->string('key');
            $table->text('value')->nullable();
            $table->boolean('json')->default(false);
            $table->timestamps();
        });
    }

    private function seedOrderStatuses(): void
    {
        DB::table('settings')->insert([
            [
                'code' => 'order',
                'key' => 'statuses',
                'value' => json_encode([
                    ['id' => 1, 'title' => 'Novo', 'color' => 'info'],
                    ['id' => 2, 'title' => 'Čeka uplatu', 'color' => 'warning'],
                    ['id' => 3, 'title' => 'Plaćeno', 'color' => 'success'],
                    ['id' => 4, 'title' => 'Poslano', 'color' => 'primary'],
                    ['id' => 5, 'title' => 'Otkazano', 'color' => 'danger'],
                    ['id' => 6, 'title' => 'Vraćeno', 'color' => 'secondary'],
                    ['id' => 7, 'title' => 'Odbijeno', 'color' => 'danger'],
                    ['id' => 8, 'title' => 'Nedovršena', 'color' => 'secondary'],
                ], JSON_UNESCAPED_UNICODE),
                'json' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'currency',
                'key' => 'list',
                'value' => json_encode([
                    [
                        'status' => true,
                        'main' => true,
                        'value' => 1,
                        'decimal_places' => 2,
                        'symbol_left' => '',
                        'symbol_right' => '€',
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'json' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function createOrder(
        int $statusId,
        float $total,
        string $createdAt,
        string $paymentMethod = 'Kartica',
        string $shippingMethod = 'GLS'
    ): int {
        return (int) DB::table('orders')->insertGetId([
            'order_status_id' => $statusId,
            'total' => $total,
            'payment_fname' => 'Test',
            'payment_lname' => 'Kupac',
            'payment_method' => $paymentMethod,
            'shipping_method' => $shippingMethod,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createOrderProduct(int $orderId, int $quantity): void
    {
        DB::table('order_products')->insert([
            'order_id' => $orderId,
            'product_id' => 100 + $orderId,
            'name' => 'Test artikl',
            'quantity' => $quantity,
            'price' => 10,
            'org_price' => 12,
            'total' => 10 * $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCatalogForProduct(int $productId, string $name): void
    {
        DB::table('authors')->insertOrIgnore(['id' => 1, 'title' => 'Test autor']);
        DB::table('publishers')->insertOrIgnore(['id' => 1, 'title' => 'Test izdavač']);
        DB::table('categories')->insertOrIgnore(['id' => 1, 'parent_id' => 0, 'title' => 'Test kategorija']);
        DB::table('products')->insert([
            'id' => $productId,
            'author_id' => 1,
            'publisher_id' => 1,
            'name' => $name,
            'quantity' => 1,
        ]);
        DB::table('product_category')->insert(['product_id' => $productId, 'category_id' => 1]);
    }
}
