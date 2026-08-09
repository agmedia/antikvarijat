<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use Illuminate\Database\Query\Builder;
use ReflectionClass;
use Tests\TestCase;

class BlogWidgetTest extends TestCase
{
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
    }

    public function testManualBlogItemsRemainFilteredWhenNewItemsAreDisabled(): void
    {
        $query = $this->blogWidgetQuery([
            'target' => 'blog',
            'list' => [10, 20, 30],
        ]);

        $this->assertNull($query->getQuery()->limit);
        $this->assertTrue($this->hasIdListFilter($query->getQuery()));
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
}
