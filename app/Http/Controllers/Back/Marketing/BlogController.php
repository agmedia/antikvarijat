<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Author;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Marketing\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Blog::where('group', 'blog')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $blogs = $query->paginate(12);

        return view('back.marketing.blog.index', compact('blogs'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.marketing.blog.edit', $this->recommendationSelection());
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $blog = new Blog();

        $stored = $blog->validateRequest($request)->create();

        if ($stored) {
            $blog->resolveImage($stored);

            return redirect()->route('blogs.edit', ['blog' => $stored])->with(['success' => 'Blog was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the blog.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Blog $blog)
    {
        return view('back.marketing.blog.edit', array_merge(
            compact('blog'),
            $this->recommendationSelection($blog)
        ));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Author                   $author
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Blog $blog)
    {
        $updated = $blog->validateRequest($request)->edit();

        if ($updated) {
            $blog->resolveImage($updated);

            return redirect()->route('blogs.edit', ['blog' => $updated])->with(['success' => 'Blog was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the blog.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Blog $blog)
    {
        $destroyed = Blog::destroy($blog->id);

        if ($destroyed) {
            return redirect()->route('blogs')->with(['success' => 'Blog was succesfully deleted!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error deleting the blog.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApi(Request $request)
    {
        if ($request->has('id')) {
            $destroyed = Blog::destroy($request->input('id'));

            if ($destroyed) {
                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 300]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadBlogImage(Request $request)
    {
        if ( ! $request->hasFile('upload')) {
            return response()->json(['uploaded' => false]);
        }

        $blog_id = $request->input('blog_id');
        $img = $request->file('upload');
        $name = Str::random(9) . '_' . $img->getClientOriginalName();

        $path = '';

        if ($blog_id) {
            $path = $blog_id . '/';
        }

        Storage::disk('blog')->putFileAs($path, $img, $name);

        return response()->json(['fileName' => $name, 'uploaded' => true, 'url' => url(config('filesystems.disks.blog.url') . $path . $name)]);
    }

    public function recommendationOptions(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:author,product',
            'q' => 'nullable|string|max:120',
        ]);
        $term = trim((string) ($validated['q'] ?? ''));

        if ($validated['type'] === 'author') {
            $authors = Author::query()
                ->select(['id', 'title'])
                ->whereHas('products', function ($products) {
                    $products->where('status', 1)
                        ->where('quantity', '>', 0)
                        ->where('price', '!=', 0);
                })
                ->when($term !== '', fn ($query) => $query->where('title', 'like', '%' . $term . '%'))
                ->orderBy('title')
                ->limit(20)
                ->get()
                ->map(fn (Author $author) => [
                    'id' => $author->id,
                    'text' => $author->title,
                ]);

            return response()->json(['results' => $authors]);
        }

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'author_id'])
            ->with('author:id,title')
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('price', '!=', 0)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', '%' . $term . '%')
                        ->orWhere('sku', 'like', '%' . $term . '%')
                        ->orWhereHas('author', fn ($author) => $author->where('title', 'like', '%' . $term . '%'));
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'text' => $this->productOptionText($product),
            ]);

        return response()->json(['results' => $products]);
    }

    private function recommendationSelection(?Blog $blog = null): array
    {
        $authorId = old('recommendation_author_id', optional($blog)->recommendation_author_id);
        $productIds = collect(old(
            'recommendation_product_ids',
            optional($blog)->recommendation_product_ids ?: []
        ))
            ->filter(fn ($id) => ctype_digit((string) $id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selectedRecommendationAuthor = $authorId
            ? Author::query()->select(['id', 'title'])->find($authorId)
            : null;
        $selectedRecommendationProducts = $productIds->isEmpty()
            ? collect()
            : Product::query()
                ->select(['id', 'name', 'sku', 'author_id'])
                ->with('author:id,title')
                ->whereIn('id', $productIds)
                ->get()
                ->sortBy(fn (Product $product) => $productIds->search((int) $product->id))
                ->values();

        return compact('selectedRecommendationAuthor', 'selectedRecommendationProducts');
    }

    private function productOptionText(Product $product): string
    {
        return collect([
            $product->name,
            $product->sku ? 'Šifra: ' . $product->sku : null,
            optional($product->author)->title,
        ])->filter()->implode(' — ');
    }
}
