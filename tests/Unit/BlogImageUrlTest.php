<?php

namespace Tests\Unit;

use App\Helpers\Helper;
use App\Models\Front\Blog;
use Tests\TestCase;

class BlogImageUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://antlaravel.test',
            'settings.images_domain' => 'https://images.example.test/',
        ]);
    }

    public function testBlogThumbnailsUseTheConfiguredImageDomain(): void
    {
        $blog = new Blog();
        $blog->setRawAttributes(['image' => 'media/img/blog/example image.jpg'], true);

        $this->assertSame(
            'https://images.example.test/cache/thumb?size=600&src=media%2Fimg%2Fblog%2Fexample%20image.jpg',
            $blog->thumb
        );
        $this->assertSame(
            'https://images.example.test/cache/thumb?size=1200x1200&src=media%2Fimg%2Fblog%2Fexample%20image.jpg',
            $blog->hero
        );
    }

    public function testRichContentBlogImagesUseTheConfiguredImageDomain(): void
    {
        $html = Helper::optimizeRichContentMedia(
            '<p><img src="media/img/blog/example image.jpg" alt="Example"></p>'
        );

        $this->assertStringContainsString(
            'src="https://images.example.test/cache/thumb?size=1200x1200&amp;src=media%2Fimg%2Fblog%2Fexample%20image.jpg"',
            $html
        );
    }
}
