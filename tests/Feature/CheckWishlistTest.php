<?php

namespace Tests\Feature;

use App\Http\Controllers\Back\Marketing\WishlistController;
use App\Mail\WishlistArrived;
use App\Services\WishlistAttributionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use App\Models\Back\Marketing\Wishlist;
use Tests\TestCase;

class CheckWishlistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['wishlist.emails_enabled' => true]);

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('slug');
            $table->string('slug_en')->nullable();
            $table->string('url');
            $table->string('url_en')->nullable();
            $table->string('image')->nullable();
            $table->string('sku')->default('0');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('special', 10, 2)->default(0);
            $table->unsignedBigInteger('author_id')->default(0);
            $table->integer('quantity')->default(0);
            $table->boolean('status')->default(true);
        });

        Schema::create('wishlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('email');
            $table->unsignedBigInteger('product_id');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        DB::table('products')->insert([
            $this->product(1, 1, 1),
            $this->product(2, 0, 1),
            $this->product(3, 1, 0),
        ]);

        DB::table('wishlist')->insert([
            $this->wish(1, 1, 'Kupac@example.test'),
            $this->wish(2, 1, 'kupac@example.test'),
            $this->wish(3, 2, 'nema@example.test'),
            $this->wish(4, 3, 'skriven@example.test'),
            $this->wish(5, 1, 'nije-email'),
            array_merge($this->wish(6, 1, 'neaktivan@example.test'), ['status' => 0]),
            array_merge($this->wish(7, 1, 'poslan@example.test'), ['sent' => 1]),
        ]);
    }

    public function test_dry_run_does_not_change_rows_or_send_mail(): void
    {
        Mail::fake();

        $this->artisan('check:wishlist --dry-run')->assertExitCode(0);

        $this->assertSame(7, DB::table('wishlist')->count());
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 5)->value('status'));
        Mail::assertNothingSent();
    }

    public function test_it_sends_once_per_product_and_normalized_email_then_removes_only_sent_rows(): void
    {
        Mail::fake();

        $this->artisan('check:wishlist')->assertExitCode(0);

        Mail::assertSent(WishlistArrived::class, 1);
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 1)->value('sent'));
        $this->assertNotNull(DB::table('wishlist')->where('id', 1)->value('sent_at'));
        $this->assertSame(0, (int) DB::table('wishlist')->where('id', 5)->value('status'));
        $this->assertSame(7, DB::table('wishlist')->count());
    }

    public function test_live_run_respects_notification_batch_limit(): void
    {
        Mail::fake();
        DB::table('wishlist')->insert($this->wish(8, 1, 'drugi@example.test'));

        $this->artisan('check:wishlist --limit=1')->assertExitCode(0);

        Mail::assertSent(WishlistArrived::class, 1);
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 1)->value('sent'));
        $this->assertSame(0, (int) DB::table('wishlist')->where('id', 8)->value('sent'));
    }

    public function test_admin_can_send_one_ready_notification_without_deleting_history(): void
    {
        Mail::fake();

        $result = Wishlist::query()->findOrFail(1)->sendNow();

        $this->assertTrue($result['sent']);
        Mail::assertSent(WishlistArrived::class, 1);
        $this->assertSame(7, DB::table('wishlist')->count());
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 1)->value('sent'));
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 2)->value('sent'));
        $this->assertNotNull(DB::table('wishlist')->where('id', 1)->value('sent_at'));
    }

    public function test_sent_mail_uses_signed_tracking_link_and_records_unique_click(): void
    {
        Mail::fake();

        Wishlist::query()->findOrFail(1)->sendNow();

        Mail::assertSent(WishlistArrived::class, function (WishlistArrived $mail) {
            $mail->build();
            $trackingUrl = (string) ($mail->viewData['trackingUrl'] ?? '');

            $this->assertStringContainsString('/wishlist-obavijest/1', $trackingUrl);
            $this->assertStringContainsString('signature=', $trackingUrl);

            return true;
        });

        $url = URL::signedRoute('wishlist.track', ['wishlist' => 1, 'locale' => 'hr']);
        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('utm_source=wishlist', (string) $response->headers->get('Location'));
        $this->assertNotNull(DB::table('wishlist')->where('id', 1)->value('clicked_at'));
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 1)->value('click_count'));
    }

    public function test_admin_can_send_multiple_selected_ready_notifications(): void
    {
        Mail::fake();
        $this->withoutMiddleware();
        DB::table('wishlist')->insert($this->wish(8, 1, 'drugi@example.test'));

        $response = $this->from('/admin/wishlists')->post(route('wishlists.send-selected'), [
            'wishlist_ids' => [1, 8],
        ]);

        $response->assertRedirect('/admin/wishlists');
        $response->assertSessionHas('success');
        Mail::assertSent(WishlistArrived::class, 2);
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 1)->value('sent'));
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 2)->value('sent'));
        $this->assertSame(1, (int) DB::table('wishlist')->where('id', 8)->value('sent'));
        $this->assertSame(8, DB::table('wishlist')->count());
    }

    public function test_bulk_send_skips_selected_notification_without_stock(): void
    {
        Mail::fake();
        $this->withoutMiddleware();

        $response = $this->from('/admin/wishlists')->post(route('wishlists.send-selected'), [
            'wishlist_ids' => [3],
        ]);

        $response->assertRedirect('/admin/wishlists');
        $response->assertSessionHas('success', 'Poslano obavijesti: 0; obrađeno wishlist zapisa: 0. Preskočeno: 1.');
        Mail::assertNothingSent();
        $this->assertSame(0, (int) DB::table('wishlist')->where('id', 3)->value('sent'));
    }

    public function test_top_products_include_prices_row_values_and_filtered_summary(): void
    {
        DB::table('products')->where('id', 1)->update(['price' => 12.50]);
        DB::table('products')->where('id', 2)->update(['price' => 7]);

        $view = app(WishlistController::class)->index(
            Request::create('/admin/wishlists', 'GET', ['tab' => 'top-products']),
            app(WishlistAttributionService::class)
        );
        $data = $view->getData();
        $firstProduct = $data['topProducts']->first();

        $this->assertSame(7, (int) $data['topProductsSummary']->requested_total);
        $this->assertSame(79.5, (float) $data['topProductsSummary']->value_total);
        $this->assertSame(5, (int) $firstProduct->total);
        $this->assertSame(12.5, (float) $firstProduct->product->price);
        $this->assertSame(62.5, (float) $firstProduct->product->price * (int) $firstProduct->total);

        $filteredView = app(WishlistController::class)->index(
            Request::create('/admin/wishlists', 'GET', ['tab' => 'top-products', 'search' => 'Artikl 2']),
            app(WishlistAttributionService::class)
        );
        $filteredData = $filteredView->getData();

        $this->assertSame(1, (int) $filteredData['topProductsSummary']->requested_total);
        $this->assertSame(7.0, (float) $filteredData['topProductsSummary']->value_total);
    }

    private function product(int $id, int $quantity, int $status): array
    {
        return [
            'id' => $id,
            'name' => 'Artikl ' . $id,
            'slug' => 'artikl-' . $id,
            'url' => '/knjige/artikl-' . $id,
            'price' => 10,
            'special' => 0,
            'author_id' => 0,
            'quantity' => $quantity,
            'status' => $status,
        ];
    }

    private function wish(int $id, int $productId, string $email): array
    {
        return [
            'id' => $id,
            'user_id' => 0,
            'email' => $email,
            'product_id' => $productId,
            'sent' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
