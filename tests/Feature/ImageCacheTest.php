<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImageCacheTest extends TestCase
{
    public function testMissingThumbnailSourceReturnsAPlaceholderImage(): void
    {
        $response = $this->get('/cache/thumb?src=media/img/does-not-exist.jpg&size=80x100&mode=fit');

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('content-type'));
        $this->assertNotEmpty($response->getContent());
    }

    public function testThumbnailEndpointDoesNotReadExternalOrParentPaths(): void
    {
        foreach (['https://example.com/image.jpg', '../.env'] as $source) {
            $response = $this->get('/cache/thumb?size=80&src=' . urlencode($source));

            $response->assertOk();
            $this->assertStringStartsWith('image/', (string) $response->headers->get('content-type'));
            $this->assertNotEmpty($response->getContent());
        }
    }
}
