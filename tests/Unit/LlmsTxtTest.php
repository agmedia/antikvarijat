<?php

namespace Tests\Unit;

use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    public function test_llms_txt_is_a_concise_bilingual_index_of_canonical_pages(): void
    {
        $contents = file_get_contents(public_path('llms.txt'));

        $this->assertIsString($contents);
        $this->assertStringStartsWith("# Antikvarijat Biblos\n\n>", $contents);
        $this->assertStringContainsString('## Primary Croatian pages', $contents);
        $this->assertStringContainsString('## Primary English pages', $contents);
        $this->assertStringContainsString('https://www.antikvarijat-biblos.hr/knjige', $contents);
        $this->assertStringContainsString('https://www.antikvarijat-biblos.hr/en/books', $contents);
        $this->assertStringContainsString('https://www.antikvarijat-biblos.hr/sitemap.xml', $contents);
        $this->assertStringNotContainsString('/admin', $contents);
        $this->assertLessThan(10000, strlen($contents));
    }

    public function test_llms_txt_contains_only_absolute_links_on_the_canonical_host(): void
    {
        $contents = file_get_contents(public_path('llms.txt'));

        preg_match_all('/\[[^]]+]\(([^)]+)\)/', (string) $contents, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $url) {
            $this->assertStringStartsWith('https://www.antikvarijat-biblos.hr', $url);
        }

        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }
}
