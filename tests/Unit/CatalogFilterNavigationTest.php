<?php

namespace Tests\Unit;

use Tests\TestCase;

class CatalogFilterNavigationTest extends TestCase
{
    public function testVueFilterUsesTheServerGeneratedParentUrl(): void
    {
        $component = file_get_contents(resource_path('js/front/filter/components/Filter/Filter.vue'));
        $view = file_get_contents(resource_path('views/front/catalog/category/index.blade.php'));
        $cartBundle = file_get_contents(public_path('js/cart.js'));

        $this->assertStringContainsString('parentUrl:', $component);
        $this->assertStringContainsString('if (this.parentUrl)', $component);
        $this->assertStringContainsString('window.location.href = this.parentUrl', $component);
        $this->assertStringContainsString(":parent-url='@json(\$filterParentUrl)'", $view);
        $this->assertStringContainsString("LocaleHelper::route('catalog.route.publisher'", $view);
        $this->assertStringContainsString('parentUrl', $cartBundle);
        $this->assertStringContainsString('window.location.href=this.parentUrl', $cartBundle);
    }
}
