<?php

namespace Tests\Feature;

use App\Services\MailchimpEcommerceService;
use ReflectionClass;
use Tests\TestCase;

class MailchimpEcommerceServiceTest extends TestCase
{
    public function test_it_normalizes_relative_product_urls_to_the_storefront_domain(): void
    {
        config([
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $result = $this->invokeNormalizeStorefrontUrl('knjige/psihologija/praksa-psihoterapije');

        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/psihologija/praksa-psihoterapije',
            $result
        );
    }

    public function test_it_rewrites_absolute_product_urls_to_the_configured_storefront_domain(): void
    {
        config([
            'services.mailchimp.storefront_url' => 'https://www.antikvarijat-biblos.hr',
        ]);

        $result = $this->invokeNormalizeStorefrontUrl(
            'http://antlaravel.test/knjige/psihologija/praksa-psihoterapije?utm_source=mailchimp#buy'
        );

        $this->assertSame(
            'https://www.antikvarijat-biblos.hr/knjige/psihologija/praksa-psihoterapije?utm_source=mailchimp#buy',
            $result
        );
    }

    private function invokeNormalizeStorefrontUrl(string $url): string
    {
        $service = new MailchimpEcommerceService();
        $method = (new ReflectionClass($service))->getMethod('normalizeStorefrontUrl');
        $method->setAccessible(true);

        return $method->invoke($service, $url);
    }
}
