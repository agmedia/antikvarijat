<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Http\Controllers\Back\Marketing\ActionController;
use App\Models\Back\Marketing\Action;
use App\Models\Back\Settings\Settings;
use App\Models\Front\AgCart;
use App\Models\Front\Catalog\Product;
use App\Models\Front\Checkout\Order;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class AllProductsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Carbon::setTestNow('2026-11-09 12:00:00');

        Schema::create('product_actions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->decimal('discount', 15, 4);
            $table->string('group');
            $table->text('links')->nullable();
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();
            $table->text('data')->nullable();
            $table->string('coupon')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('lock')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Knjiga');
            $table->unsignedInteger('publisher_id')->default(1);
            $table->decimal('price', 15, 4)->default(10);
            $table->decimal('special', 15, 4)->nullable();
            $table->unsignedInteger('action_id')->default(0);
            $table->dateTime('special_from')->nullable();
            $table->dateTime('special_to')->nullable();
            $table->boolean('special_lock')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('key');
            $table->text('value');
            $table->boolean('json')->default(false);
            $table->timestamps();
        });

        Settings::set('testing', 'all-products-actions', '1', false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_admin_offers_all_products_when_saved_group_settings_do_not_have_it(): void
    {
        Settings::insert('action', 'group_list', json_encode([
            ['id' => 'product', 'title' => 'Artikl'],
            ['id' => 'total', 'title' => 'Na ukupan iznos'],
        ]), true);

        $groups = app(ActionController::class)->create()->getData()['groups'];

        $this->assertSame(['product', 'total', 'all'], $groups->pluck('id')->all());
        $this->assertSame('Svi artikli', $groups->firstWhere('id', 'all')->title);
    }

    public function test_admin_does_not_duplicate_an_existing_all_products_option(): void
    {
        Settings::insert('action', 'group_list', json_encode([
            ['id' => 'all', 'title' => 'Svi artikli'],
            ['id' => 'product', 'title' => 'Artikl'],
        ]), true);

        $groups = app(ActionController::class)->create()->getData()['groups'];

        $this->assertCount(1, $groups->where('id', 'all'));
        $this->assertCount(2, $groups);
    }

    public function test_twenty_percent_action_applies_to_all_unlocked_products_without_an_item_list(): void
    {
        DB::table('products')->insert([
            ['id' => 1, 'price' => 10, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 2, 'price' => 25, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 3, 'price' => 30, 'special' => 15, 'action_id' => 99, 'special_lock' => 1],
        ]);

        $action = $this->createAction();

        $this->assertSame('all', $action->group);
        $this->assertSame(['all'], json_decode($action->links, true));
        foreach ([1 => 8, 2 => 20] as $id => $price) {
            $product = Product::findOrFail($id);
            $this->assertSame($price, (int) $product->special());
            $this->assertSame((int) $action->id, (int) $product->action_id);
            $this->assertSame(20, (int) Helper::calculateDiscount((float) $product->price, (float) $product->special()));
            $this->assertNull($product->special_from);
            $this->assertNull($product->special_to);
        }
        $locked = Product::findOrFail(3);
        $this->assertSame(15.0, (float) $locked->special);
        $this->assertSame(99, (int) $locked->action_id);

        $condition = new ReflectionMethod(AgCart::class, 'structureCartItemConditions');
        $condition->setAccessible(true);
        $cartDiscount = $condition->invoke(new AgCart('all-products-action-test'), Product::findOrFail(1));
        $this->assertSame(8.0, $cartDiscount->applyCondition(10));
    }

    public function test_scheduled_all_products_prices_only_appear_during_the_action_dates(): void
    {
        DB::table('products')->insert(['id' => 1, 'price' => 25]);
        $this->createAction([
            'date_start' => '2026-11-10 00:00:00',
            'date_end' => '2026-11-12 23:59:59',
        ]);

        $this->assertSame(25.0, (float) Product::findOrFail(1)->special());
        Carbon::setTestNow('2026-11-11 12:00:00');
        $this->assertSame(20.0, (float) Product::findOrFail(1)->special());
        Carbon::setTestNow('2026-11-13 12:00:00');
        $this->assertSame(25.0, (float) Product::findOrFail(1)->special());
    }

    public function test_order_products_keep_the_same_discounted_price_as_the_storefront_and_cart(): void
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('org_price', 15, 4);
            $table->decimal('discount', 15, 4);
            $table->decimal('price', 15, 4);
            $table->decimal('total', 15, 4);
            $table->timestamps();
        });
        DB::table('products')->insert(['id' => 1, 'price' => 25]);
        $this->createAction();
        $product = Product::findOrFail(1);
        $order = new Order(['cart' => ['items' => [
            (object) [
                'id' => 1,
                'name' => 'Knjiga',
                'price' => 25,
                'quantity' => 2,
                'associatedModel' => $product,
                'attributes' => [],
            ],
        ]]]);

        $updateProducts = new ReflectionMethod(Order::class, 'updateProducts');
        $updateProducts->setAccessible(true);
        $updateProducts->invoke($order, 10);

        $saved = DB::table('order_products')->where('order_id', 10)->first();
        $this->assertSame((float) $product->special(), (float) $saved->price);
        $this->assertSame(25.0, (float) $saved->org_price);
        $this->assertSame(20.0, (float) $saved->discount);
        $this->assertSame(40.0, (float) $saved->total);
    }

    public function test_a_coupon_protected_all_products_action_requires_the_matching_coupon(): void
    {
        DB::table('products')->insert(['id' => 1, 'price' => 25]);
        $this->createAction(['coupon' => 'INTERLIBER']);
        $couponSessionKey = config('session.cart') . '_coupon';

        $this->assertSame(25.0, (float) Product::findOrFail(1)->special());
        session([$couponSessionKey => 'OTHER']);
        $this->assertSame(25.0, (float) Product::findOrFail(1)->special());
        session([$couponSessionKey => 'INTERLIBER']);
        $this->assertSame(20.0, (float) Product::findOrFail(1)->special());
    }

    public function test_an_all_products_action_can_update_its_own_locked_products_and_remove_products_outside_a_new_range(): void
    {
        DB::table('products')->insert([
            ['id' => 1, 'price' => 10, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 2, 'price' => 25, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 3, 'price' => 30, 'special' => 15, 'action_id' => 99, 'special_lock' => 1],
        ]);
        $action = $this->createAction(['lock' => 'on']);

        $action->validateRequest($this->actionRequest([
            'discount' => 30,
            'min' => 15,
            'lock' => 'on',
        ]))->edit();

        $removed = Product::findOrFail(1);
        $this->assertNull($removed->special);
        $this->assertSame(0, (int) $removed->action_id);
        $this->assertSame(0, (int) $removed->special_lock);
        $remaining = Product::findOrFail(2);
        $this->assertSame(17.5, (float) $remaining->special());
        $this->assertSame(1, (int) $remaining->special_lock);
        $this->assertSame((int) $action->id, (int) $remaining->action_id);
        $this->assertSame(15.0, (float) Product::findOrFail(3)->special);
        $this->assertSame(99, (int) Product::findOrFail(3)->action_id);
    }

    public function test_deactivating_the_action_clears_its_product_prices_and_preserves_other_actions(): void
    {
        DB::table('products')->insert([
            ['id' => 1, 'price' => 10, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 2, 'price' => 30, 'special' => 15, 'action_id' => 99, 'special_lock' => 1],
        ]);
        $action = $this->createAction(['lock' => 'on']);

        $action->validateRequest($this->actionRequest(['status' => null]))->edit();

        $product = Product::findOrFail(1);
        $this->assertSame(10.0, (float) $product->special());
        $this->assertNull($product->special);
        $this->assertNull($product->special_from);
        $this->assertNull($product->special_to);
        $this->assertSame(0, (int) $product->action_id);
        $this->assertSame(0, (int) $product->special_lock);
        $this->assertSame(15.0, (float) Product::findOrFail(2)->special);
        $this->assertSame(99, (int) Product::findOrFail(2)->action_id);
    }

    public function test_fixed_all_products_discount_subtracts_the_amount_instead_of_a_percentage(): void
    {
        DB::table('products')->insert(['id' => 1, 'price' => 25]);

        $this->createAction(['type' => 'F', 'discount' => 3.5]);

        $this->assertSame(21.5, (float) Product::findOrFail(1)->special());
    }

    public function test_total_actions_keep_their_existing_scope_and_do_not_change_product_prices(): void
    {
        DB::table('products')->insert(['id' => 1, 'price' => 25]);

        $action = $this->createAction(['group' => 'total']);
        $action->validateRequest($this->actionRequest(['group' => 'total', 'discount' => 30]))->edit();

        $product = Product::findOrFail(1);
        $this->assertNull($product->special);
        $this->assertSame(0, (int) $product->action_id);
        $this->assertSame(25.0, (float) $product->special());
        $this->assertSame('total', $action->refresh()->group);
    }

    public function test_changing_an_active_all_products_action_to_total_clears_only_its_product_discounts(): void
    {
        DB::table('products')->insert([
            ['id' => 1, 'price' => 25, 'special' => null, 'action_id' => 0, 'special_lock' => 0],
            ['id' => 2, 'price' => 30, 'special' => 15, 'action_id' => 99, 'special_lock' => 1],
        ]);
        $action = $this->createAction(['lock' => 'on']);
        $this->assertSame(20.0, (float) Product::findOrFail(1)->special());

        $action->validateRequest($this->actionRequest(['group' => 'total']))->edit();

        $product = Product::findOrFail(1);
        $this->assertNull($product->special);
        $this->assertNull($product->special_from);
        $this->assertNull($product->special_to);
        $this->assertSame(0, (int) $product->action_id);
        $this->assertSame(0, (int) $product->special_lock);
        $this->assertSame(25.0, (float) $product->special());
        $this->assertSame(15.0, (float) Product::findOrFail(2)->special);
        $this->assertSame(99, (int) Product::findOrFail(2)->action_id);
        $this->assertSame(1, (int) Product::findOrFail(2)->special_lock);
        $this->assertSame('total', $action->refresh()->group);
        $this->assertSame(1, (int) $action->status);
    }

    /** @dataProvider invalidAllProductsDiscounts */
    public function test_invalid_all_products_discounts_are_rejected(string $type, $discount): void
    {
        $this->expectException(ValidationException::class);

        (new Action())->validateRequest($this->actionRequest([
            'type' => $type,
            'discount' => $discount,
        ]));
    }

    public function invalidAllProductsDiscounts(): array
    {
        return [
            'nonnumeric percentage' => ['P', 'twenty'],
            'negative percentage' => ['P', -1],
            'percentage above one hundred' => ['P', 101],
            'negative fixed amount' => ['F', -1],
        ];
    }

    private function createAction(array $overrides = []): Action
    {
        return (new Action())->validateRequest($this->actionRequest($overrides))->create();
    }

    private function actionRequest(array $overrides = []): Request
    {
        return Request::create('/admin/marketing/action', 'POST', array_replace([
            'title' => 'Interliber 20%',
            'group' => 'all',
            'type' => 'P',
            'discount' => 20,
            'status' => 'on',
        ], $overrides));
    }
}
