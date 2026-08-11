<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Http\Middleware\RedirectCustomer;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ContractWithdrawalSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductReviewFeaturedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('hr');
        config([
            'app.locale' => 'hr',
            'cache.default' => 'array',
        ]);
        $this->mock(ContractWithdrawalSettingsService::class, function ($mock) {
            $mock->shouldReceive('get')->andReturn(['return_cost_policy' => 'consumer']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('title_en')->nullable();
            $table->string('slug')->nullable();
            $table->string('slug_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('subgroup')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('url')->nullable();
            $table->string('url_en')->nullable();
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('invitation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reviewer_name');
            $table->string('reviewer_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('locale', 5)->default('hr');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        DB::table('products')->insert([
            'id' => 10,
            'name' => 'Testna knjiga',
            'name_en' => 'Test book',
            'url' => 'knjige/testna-knjiga',
            'url_en' => 'en/books/test-book',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_feature_only_an_approved_review(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $review = $this->createReview('pending', false, 'Recenzija za moderaciju');

        $this->withoutMiddleware(RedirectCustomer::class);
        $this->actingAs($admin)
            ->from(route('product-reviews.index'))
            ->patch(route('product-reviews.update', $review), [
                'status' => ProductReview::STATUS_APPROVED,
                'is_featured' => '1',
            ])
            ->assertRedirect(route('product-reviews.index'));

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => ProductReview::STATUS_APPROVED,
            'is_featured' => 1,
        ]);

        $this->actingAs($admin)
            ->from(route('product-reviews.index'))
            ->patch(route('product-reviews.update', $review), [
                'status' => ProductReview::STATUS_REJECTED,
                'is_featured' => '1',
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => ProductReview::STATUS_REJECTED,
            'is_featured' => 0,
        ]);
    }

    public function test_home_widget_uses_twelve_latest_approved_featured_reviews(): void
    {
        foreach (range(1, 14) as $index) {
            $review = $this->createReview(
                ProductReview::STATUS_APPROVED,
                true,
                'Istaknuta recenzija ' . $index
            );
            $review->forceFill(['approved_at' => now()->subMinutes(14 - $index)])->save();
        }

        $this->createReview(ProductReview::STATUS_APPROVED, false, 'Nije istaknuta');
        $this->createReview(ProductReview::STATUS_REJECTED, true, 'Nije odobrena');

        $reviews = Helper::featuredReviews();

        $this->assertCount(12, $reviews);
        $this->assertSame('Istaknuta recenzija 14', $reviews->first()->body);
        $this->assertFalse($reviews->contains('body', 'Istaknuta recenzija 1'));
        $this->assertFalse($reviews->contains('body', 'Istaknuta recenzija 2'));
        $this->assertTrue($reviews->every(fn (ProductReview $review) => $review->is_featured && $review->status === ProductReview::STATUS_APPROVED));

        $html = view('front.layouts.widget.widget_page_carousel', [
            'data' => [
                'tablename' => 'reviews',
                'title' => 'Recenzije kupaca',
                'items' => $reviews,
            ],
        ])->render();

        $this->assertStringContainsString(route('reviews.index'), $html);
        $this->assertStringContainsString('Sve recenzije', $html);
        $this->assertStringContainsString('Istaknuta recenzija 14', $html);
    }

    public function test_public_page_lists_all_and_only_approved_reviews_without_emails(): void
    {
        $approved = $this->createReview(ProductReview::STATUS_APPROVED, true, 'Javno iskustvo kupca');
        $approved->forceFill(['approved_at' => now()])->save();
        $this->createReview(ProductReview::STATUS_PENDING, true, 'Skriveno iskustvo kupca');

        $response = $this->get(route('reviews.index'));

        $response->assertOk()
            ->assertSee('Recenzije kupaca')
            ->assertSee('Javno iskustvo kupca')
            ->assertSee('Testna knjiga')
            ->assertDontSee('Skriveno iskustvo kupca')
            ->assertDontSee('kupac@example.test');

        $english = $this->get(route('en.reviews.index'));
        $english->assertOk()
            ->assertSee('Customer reviews')
            ->assertSee('Test book');
    }

    private function createReview(string $status, bool $featured, string $body): ProductReview
    {
        return ProductReview::query()->create([
            'product_id' => 10,
            'reviewer_name' => 'Kupac Test',
            'reviewer_email' => 'kupac@example.test',
            'rating' => 5,
            'body' => $body,
            'locale' => 'hr',
            'status' => $status,
            'is_verified_purchase' => true,
            'is_featured' => $featured,
            'approved_at' => $status === ProductReview::STATUS_APPROVED ? now() : null,
        ]);
    }
}
