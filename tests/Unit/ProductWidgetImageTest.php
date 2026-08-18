<?php

namespace Tests\Unit;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Builder;
use ReflectionMethod;
use Tests\TestCase;

class ProductWidgetImageTest extends TestCase
{
    public function testAllProductWidgetQueriesExcludeProductsWithoutImages(): void
    {
        foreach (['products', 'product_category', 'publisher'] as $methodName) {
            $method = new ReflectionMethod(Helper::class, $methodName);
            $method->setAccessible(true);

            /** @var Builder $query */
            $query = $method->invoke(null, []);
            $wheres = collect($query->getQuery()->wheres);

            $this->assertTrue($wheres->contains(
                fn (array $where) => $where['type'] === 'NotNull' && $where['column'] === 'image'
            ));
            $this->assertTrue($wheres->contains(
                fn (array $where) => $where['type'] === 'Basic'
                    && $where['column'] === 'image'
                    && $where['operator'] === '!='
                    && $where['value'] === ''
            ));
        }
    }
}
