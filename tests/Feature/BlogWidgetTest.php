<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Models\Back\Marketing\Blog as AdminBlog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class BlogWidgetTest extends TestCase
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

        Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('group')->default('blog');
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('slug');
            $table->string('keywords')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->unsignedInteger('viewed')->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('hide_from_home_widget')->default(false);
            $table->boolean('status')->default(false);
            $table->string('title_en')->nullable();
            $table->text('short_description_en')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_en')->nullable();
            $table->string('slug_en')->nullable();
            $table->string('keywords_en')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function testNewBlogItemsUseTheLatestNineWithoutTheManualListFilter(): void
    {
        $query = $this->blogWidgetQuery([
            'target' => 'blog',
            'new' => 'on',
            'list' => [10, 20, 30],
        ]);

        $this->assertSame(9, $query->getQuery()->limit);
        $this->assertSame([
            ['created_at', 'desc'],
            ['updated_at', 'desc'],
        ], $this->orders($query->getQuery()));
        $this->assertFalse($this->hasIdListFilter($query->getQuery()));
        $this->assertTrue($this->hasHomeWidgetVisibilityFilter($query->getQuery()));
    }

    public function testManualBlogItemsRemainFilteredWhenNewItemsAreDisabled(): void
    {
        $query = $this->blogWidgetQuery([
            'target' => 'blog',
            'list' => [10, 20, 30],
        ]);

        $this->assertNull($query->getQuery()->limit);
        $this->assertTrue($this->hasIdListFilter($query->getQuery()));
        $this->assertFalse($this->hasHomeWidgetVisibilityFilter($query->getQuery()));
    }

    public function testHiddenBlogIsReplacedByTheNextNewestAutomaticItem(): void
    {
        foreach (range(1, 10) as $id) {
            DB::table('pages')->insert([
                'id' => $id,
                'group' => 'blog',
                'title' => 'Blog ' . $id,
                'slug' => 'blog-' . $id,
                'hide_from_home_widget' => $id === 10,
                'status' => true,
                'created_at' => sprintf('2026-08-%02d 09:00:00', $id),
                'updated_at' => sprintf('2026-08-%02d 09:00:00', $id),
            ]);
        }

        $ids = $this->blogWidgetQuery([
            'target' => 'blog',
            'new' => 'on',
        ])->get()->pluck('id')->all();

        $this->assertCount(9, $ids);
        $this->assertNotContains(10, $ids);
        $this->assertSame([9, 8, 7, 6, 5, 4, 3, 2, 1], $ids);
    }

    public function testAdminSwitchIsStoredAndCanBeTurnedOff(): void
    {
        $blog = (new AdminBlog())->validateRequest(new Request([
            'title' => 'Skriveni blog',
            'hide_from_home_widget' => 'on',
            'status' => 'on',
        ]))->create();

        $this->assertTrue($blog->hide_from_home_widget);

        $updated = $blog->validateRequest(new Request([
            'title' => 'Skriveni blog',
            'status' => 'on',
        ]))->edit();

        $this->assertFalse($updated->hide_from_home_widget);
    }

    public function testBlogEditBackButtonAlwaysPointsToTheBlogList(): void
    {
        $source = file_get_contents(resource_path('views/back/marketing/blog/edit.blade.php'));

        $this->assertStringContainsString("href=\"{{ route('blogs') }}\"", $source);
        $this->assertStringNotContainsString("back()->getTargetUrl()", $source);
    }

    private function blogWidgetQuery(array $data)
    {
        $reflection = new ReflectionClass(Helper::class);
        $method = $reflection->getMethod('blogs');
        $method->setAccessible(true);

        return $method->invoke(null, $data);
    }

    private function orders(Builder $query): array
    {
        return array_map(static function (array $order): array {
            return [$order['column'], $order['direction']];
        }, $query->orders);
    }

    private function hasIdListFilter(Builder $query): bool
    {
        foreach ($query->wheres as $where) {
            if (($where['type'] ?? null) === 'In' && ($where['column'] ?? null) === 'id') {
                return true;
            }
        }

        return false;
    }

    private function hasHomeWidgetVisibilityFilter(Builder $query): bool
    {
        foreach ($query->wheres as $where) {
            if (($where['type'] ?? null) === 'Basic'
                && ($where['column'] ?? null) === 'hide_from_home_widget'
                && ! (bool) ($where['value'] ?? true)) {
                return true;
            }
        }

        return false;
    }
}
