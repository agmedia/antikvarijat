<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Models\Front\Catalog\Product;
use Illuminate\Http\Request;
use ReflectionClass;
use Tests\TestCase;

class CatalogProductSortTest extends TestCase
{
    public function testCatalogDefaultsToRecentlyUpdatedProducts(): void
    {
        $this->assertOrderBy([
            ['updated_at', 'desc'],
        ], new Request());
    }

    public function testBooksRootCanDefaultToRecentlyUpdatedProducts(): void
    {
        $this->assertOrderBy([
            ['updated_at', 'desc'],
        ], new Request(['_default_sort_latest' => true]));
    }

    public function testBooksRootTreatsInvalidSortAsRecentlyUpdatedProducts(): void
    {
        $this->assertOrderBy([
            ['updated_at', 'desc'],
        ], new Request(['sort' => 'unknown', '_default_sort_latest' => true]));
    }

    public function testCatalogUsesExplicitNewestSort(): void
    {
        $this->assertOrderBy([
            ['created_at', 'desc'],
            ['id', 'desc'],
        ], new Request(['sort' => 'novi']));
    }

    public function testPublicationYearRangeExcludesProductsWithoutAYear(): void
    {
        $startWheres = (new Product())
            ->filter(new Request(['start' => '2020']))
            ->getQuery()
            ->wheres;
        $endWheres = (new Product())
            ->filter(new Request(['end' => '2020']))
            ->getQuery()
            ->wheres;

        $this->assertQueryHasWhere($startWheres, 'year', '>=', '2020');
        $this->assertQueryHasWhere($endWheres, 'year', '<=', '2020');
        $this->assertFalse($this->queryContainsNullYearFallback($startWheres));
        $this->assertFalse($this->queryContainsNullYearFallback($endWheres));
    }

    public function testWidgetNewProductsUseRecentlyUpdatedAvailableProducts(): void
    {
        $reflection = new ReflectionClass(Helper::class);
        $method = $reflection->getMethod('products');
        $method->setAccessible(true);

        $query = $method->invoke(null, ['target' => 'product', 'new' => 'on']);

        $orders = $query->getQuery()->orders;
        $actual = array_map(static function (array $order): array {
            return [$order['column'], $order['direction']];
        }, $orders);

        $this->assertSame([['updated_at', 'desc']], $actual);
        $this->assertSame(12, $query->getQuery()->limit);
        $this->assertQueryHasWhere($query->getQuery()->wheres, 'quantity', '>', 0);
    }

    private function assertOrderBy(array $expected, Request $request): void
    {
        $orders = (new Product())
            ->filter($request)
            ->getQuery()
            ->orders;

        $actual = array_map(static function (array $order): array {
            return [$order['column'], $order['direction']];
        }, $orders);

        $this->assertSame($expected, $actual);
    }

    private function assertQueryHasWhere(array $wheres, string $column, string $operator, $value): void
    {
        $matches = array_filter($wheres, static function (array $where) use ($column, $operator, $value): bool {
            return ($where['column'] ?? null) === $column
                && ($where['operator'] ?? null) === $operator
                && ($where['value'] ?? null) === $value;
        });

        $this->assertNotEmpty($matches);
    }

    private function queryContainsNullYearFallback(array $wheres): bool
    {
        foreach ($wheres as $where) {
            if (($where['column'] ?? null) === 'year' && ($where['type'] ?? null) === 'Null') {
                return true;
            }

            if (isset($where['query']->wheres) && $this->queryContainsNullYearFallback($where['query']->wheres)) {
                return true;
            }
        }

        return false;
    }
}
