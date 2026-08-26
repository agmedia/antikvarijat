<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerOrdersViewTest extends TestCase
{
    public function test_customer_orders_view_compiles_to_valid_php(): void
    {
        $source = file_get_contents(
            resource_path('views/front/customer/moje-narudzbe.blade.php')
        );

        $compiled = app('blade.compiler')->compileString($source);

        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }
}
