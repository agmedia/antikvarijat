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

    public function testTranslatorConstraintIsPropagatedThroughCatalogueRequestsAndRoutes(): void
    {
        $filter = file_get_contents(resource_path('js/front/filter/components/Filter/Filter.vue'));
        $products = file_get_contents(resource_path('js/front/filter/components/ProductsList/ProductsList.vue'));
        $controller = file_get_contents(app_path('Http/Controllers/Api/v2/FilterController.php'));
        $cartBundle = file_get_contents(public_path('js/cart.js'));

        $this->assertGreaterThanOrEqual(2, substr_count($filter, 'prevoditelj: this.prevoditelj'));
        $this->assertStringContainsString('params.query.prevoditelj', $filter);
        $this->assertGreaterThanOrEqual(2, substr_count($products, 'prevoditelj: this.prevoditelj'));
        $this->assertStringContainsString('params.query.prevoditelj', $products);
        $this->assertStringContainsString("\$request_data['prevoditelj'] = \$translatorIds->all();", $controller);
        $this->assertStringContainsString("'nakladnik', 'prevoditelj', 'start'", $controller);
        $this->assertStringContainsString('prevoditelj', $cartBundle);
    }
}
