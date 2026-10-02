<?php

namespace Tests\Unit;

use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Product\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }
    }

    public function test_valid_slim_image_payload_is_accepted(): void
    {
        $payload = $this->slimPayload($this->jpegDataUri(1200, 1600));
        $request = Request::create('/', 'POST', [
            'files' => [
                ['image' => $payload],
                'default' => 'image/cover.jpg',
            ],
        ]);

        ProductImage::validateRequestImages($request);

        $this->assertTrue(true);
    }

    public function test_oversized_slim_image_is_rejected_before_product_is_saved(): void
    {
        $payload = $this->slimPayload($this->jpegDataUri(2100, 1000));
        $request = Request::create('/', 'POST', [
            'files' => [['image' => $payload]],
        ]);

        try {
            ProductImage::validateRequestImages($request);
            $this->fail('Oversized image was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('files.0.image', $exception->errors());
            $this->assertStringContainsString('preveliku rezoluciju', $exception->errors()['files.0.image'][0]);
        }
    }

    public function test_no_more_than_ten_new_images_are_accepted_per_request(): void
    {
        $payload = $this->slimPayload($this->jpegDataUri(100, 100));
        $request = Request::create('/', 'POST', [
            'files' => array_fill(0, 11, ['image' => $payload]),
        ]);

        try {
            ProductImage::validateRequestImages($request);
            $this->fail('Too many images were accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('files', $exception->errors());
        }
    }

    public function test_server_resizes_and_writes_all_product_image_variants(): void
    {
        Storage::fake('products');

        $product = new Product();
        $product->id = 321;
        $product->name = 'Velika naslovnica';

        $productImage = new ProductImage();
        $resource = new ReflectionProperty(ProductImage::class, 'resource');
        $resource->setAccessible(true);
        $resource->setValue($productImage, $product);

        $saveImage = new ReflectionMethod(ProductImage::class, 'saveImage');
        $saveImage->setAccessible(true);
        $jpgPath = $saveImage->invoke($productImage, $this->jpegDataUri(2000, 1500));

        $webpPath = preg_replace('/\.jpg$/', '.webp', $jpgPath);
        $thumbPath = preg_replace('/\.jpg$/', '-thumb.webp', $jpgPath);

        Storage::disk('products')->assertExists($jpgPath);
        Storage::disk('products')->assertExists($webpPath);
        Storage::disk('products')->assertExists($thumbPath);

        $this->assertSame([1600, 1200], $this->imageDimensions(Storage::disk('products')->get($jpgPath)));
        $this->assertSame([1600, 1200], $this->imageDimensions(Storage::disk('products')->get($webpPath)));
        $this->assertSame([250, 300], $this->imageDimensions(Storage::disk('products')->get($thumbPath)));
    }

    public function test_default_new_image_is_selected_by_field_index_not_filename(): void
    {
        $first = (object) ['id' => 10, 'image' => 'first.jpg'];
        $second = (object) ['id' => 11, 'image' => 'second.jpg'];
        $productImage = \Mockery::mock(ProductImage::class)->makePartial();

        $productImage->shouldReceive('saveNew')->twice()->andReturn($first, $second);
        $productImage->shouldReceive('switchDefault')->once()->with($second)->andReturn($second);
        $productImage->shouldReceive('where')->once()->with('product_id', 321)->andReturnSelf();
        $productImage->shouldReceive('get')->once()->andReturn(collect([$first, $second]));

        $sameOutputName = json_encode([
            'output' => [
                'name' => 'book.jpg',
                'image' => 'data:image/jpeg;base64,ignored-by-this-test',
            ],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create('/', 'POST', [
            'files' => [
                3 => ['image' => $sameOutputName, 'sort_order' => 0],
                7 => ['image' => $sameOutputName, 'sort_order' => 1],
                'default' => '7',
            ],
        ]);

        $productImage->store((object) ['id' => 321, 'name' => 'Knjiga'], $request);
    }

    private function slimPayload(string $dataUri): string
    {
        return json_encode([
            'output' => [
                'name' => 'cover.jpg',
                'type' => 'image/jpeg',
                'image' => $dataUri,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function jpegDataUri(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 238, 232, 220);
        imagefill($image, 0, 0, $background);

        ob_start();
        imagejpeg($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        return 'data:image/jpeg;base64,' . base64_encode($contents);
    }

    private function imageDimensions(string $contents): array
    {
        $size = getimagesizefromstring($contents);

        return [(int) $size[0], (int) $size[1]];
    }
}
