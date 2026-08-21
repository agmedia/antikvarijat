<?php

namespace Tests\Feature;

use App\Http\Controllers\Back\Marketing\BlogController;
use App\Models\Back\Marketing\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogEditorTest extends TestCase
{
    use RefreshDatabase;

    public function testBlogFormProvidesSourceImageAndVideoTools(): void
    {
        $source = file_get_contents(resource_path('views/back/marketing/blog/edit.blade.php'));

        $this->assertStringContainsString('summernote-bs4.min.js', $source);
        $this->assertStringContainsString("['insert', ['link', 'picture', 'video']]", $source);
        $this->assertStringContainsString("['view', ['fullscreen', 'codeview', 'help']]", $source);
        $this->assertStringContainsString("route('blogs.upload.image')", $source);
        $this->assertStringNotContainsString('ckeditor5-classic/build/ckeditor.js', $source);
    }

    public function testBlogEditorImageCanBeUploaded(): void
    {
        Storage::fake('blog');

        $request = Request::create('/blogs/upload/image', 'POST', [
            'blog_id' => 42,
        ], [], [
            'upload' => UploadedFile::fake()->image('editor-image.jpg', 800, 600),
        ]);

        $response = (new BlogController())->uploadBlogImage($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['uploaded']);
        $this->assertSame('editor-image.jpg', substr($payload['fileName'], 10));
        Storage::disk('blog')->assertExists('42/' . $payload['fileName']);
    }

    public function testExistingRichHtmlIsStoredWithoutRemovingStylesOrVideo(): void
    {
        $html = '<p>Stari sadržaj</p><a class="btn btn-primary" style="border-radius: 2rem" href="/glasaj">Glasaj</a><iframe src="https://www.youtube.com/embed/aqz-KE-bpKQ" allowfullscreen></iframe>';

        $blog = (new Blog())->validateRequest(new Request([
            'title' => 'Postojeći članak',
            'description' => $html,
            'status' => 'on',
        ]))->create();

        $this->assertSame($html, $blog->getRawOriginal('description'));
    }
}
