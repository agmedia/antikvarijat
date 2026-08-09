<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Helper;
use App\Helpers\Njuskalo;
use App\Helpers\Recaptcha;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Mail\BookPurchaseMessage;
use App\Mail\ContactFormMessage;
use App\Models\Back\Marketing\BookPurchaseRequest;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Marketing\Wishlist;
use App\Models\Front\Page;
use App\Models\Sitemap;
use App\Services\BookPurchaseContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = null;

        if (Schema::hasTable((new Page())->getTable())) {
            $page = Cache::remember('page.homepage', config('cache.life'), function () {
                return Page::where('slug', 'homepage')->first();
            });
        }

        // Keep the storefront usable if the CMS table or homepage record has not
        // yet been restored. The canonical content below mirrors the backup row.
        if (! $page) {
            $page = (new Page())->forceFill([
                'title' => 'Homepage',
                'title_en' => 'Homepage',
                'slug' => 'homepage',
                'slug_en' => 'homepage',
                'description' => '<p>++homepage-izdvojeno++</p><p>++homepage-novo++</p><p>++recenzije++</p><p>++knjizevnost++</p><p>++homepage-zemljovidi-i-vedute++</p><p>++banneri++</p><p>++homepage-iz-medija++</p>',
                'description_en' => '<p>++homepage-izdvojeno++</p><p>++homepage-novo++</p><p>++recenzije++</p><p>++knjizevnost++</p><p>++homepage-zemljovidi-i-vedute++</p><p>++banneri++</p><p>++homepage-iz-medija++</p>',
                'status' => true,
            ]);
        }

        $page->setAttribute('rendered_description', Helper::setDescription((string) $page->description));

        return view('front.page', compact('page'));
    }


    /**
     * @param Page $page
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function page(Page $page)
    {
        return view('front.page', compact('page'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function wishlist(Request $request)
    {
        $wish = new Wishlist();
        $wish->validateRequest($request);

        // recaptcha verifikacija – moraš imati site & secret key postavljen
        $recaptcha = (new Recaptcha())->check($request->toArray());
        if (! $recaptcha->ok()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'errors' => [
                        'recaptcha' => [__('front.messages.recaptcha_failed')],
                    ],
                ], 422);
            }

            return back()->withErrors(['error' => __('front.messages.recaptcha_admin')])
                ->withInput();
        }

        if ($wish->create()) {
            return back()->with(['success' => __('front.wishlist.success')]);
        }

        return back()->with(['error' => __('front.wishlist.exists')]);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function newsletter(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'gdpr' => 'required|accepted',
        ], [
            'email.required' => __('front.newsletter.email_required'),
            'email.email' => __('front.newsletter.email_invalid'),
            'gdpr.required' => __('front.newsletter.gdpr_required'),
            'gdpr.accepted' => __('front.newsletter.gdpr_required'),
        ]);

        NewsletterSubscriber::subscribe([
            'email' => $request->input('email'),
            'user_id' => auth()->id() ?? 0,
            'source' => 'footer',
            'gdpr' => true,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('front.newsletter.success'),
            ]);
        }

        return back()->with('newsletter_success', __('front.newsletter.success'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function contact(Request $request)
    {
        return view('front.contact');
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function bookPurchase(BookPurchaseContentService $contentService)
    {
        $defaults = [
            'full_name' => '',
            'postal_code' => '',
            'email' => '',
            'phone' => '',
        ];

        if (auth()->check()) {
            $user = auth()->user()->loadMissing('details');

            $defaults['email'] = (string) ($user->email ?? '');
            $defaults['phone'] = (string) ($user->details->phone ?? '');
            $defaults['postal_code'] = (string) ($user->details->zip ?? '');

            $firstName = trim((string) ($user->details->fname ?? ''));
            $lastName = trim((string) ($user->details->lname ?? ''));
            $detailsFullName = trim($firstName . ' ' . $lastName);

            $defaults['full_name'] = $detailsFullName !== ''
                ? $detailsFullName
                : (string) ($user->name ?? '');
        }

        $bookPurchaseContent = $contentService->forLocale();

        return view('front.book-purchase', compact('defaults', 'bookPurchaseContent'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function sendContactMessage(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
        ]);

        // Recaptcha
        $recaptcha = (new Recaptcha())->check($request->toArray());

        if ( ! $recaptcha->ok()) {
            return back()->withErrors(['error' => __('front.messages.recaptcha_admin')]);
        }

        $message = $request->toArray();

        dispatch(function () use ($message) {
            Mail::to(config('mail.admin'))->send(new ContactFormMessage($message));
        });

        return view('front.contact')->with(['success' => __('front.contact.sent')]);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendBookPurchaseMessage(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:120',
            'postal_code' => 'required|string|max:20',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:50',
            'photos' => 'required|array|min:1|max:20',
            'photos.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'photos.max' => __('front.book_purchase.max_files'),
            'photos.*.max' => __('front.book_purchase.single_photo_too_large'),
        ]);

        $totalUploadSize = collect($request->file('photos', []))->sum(function ($photo) {
            return $photo->getSize();
        });

        if ($totalUploadSize > 40 * 1024 * 1024) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'errors' => [
                        'photos' => [__('front.book_purchase.total_too_large')],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'photos' => __('front.book_purchase.total_too_large'),
            ])->withInput();
        }

        $recaptcha = (new Recaptcha())->check($request->toArray());
        if (! $recaptcha->ok()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'errors' => [
                        'recaptcha' => [__('front.messages.recaptcha_failed')],
                    ],
                ], 422);
            }

            return back()->withErrors(['error' => __('front.messages.recaptcha_admin')])
                ->withInput();
        }

        $submissionId = now()->format('Ymd_His') . '_' . Str::lower(Str::random(8));
        $relativeDirectory = 'uploads/otkup-knjiga/' . $submissionId;
        $absoluteDirectory = public_path($relativeDirectory);

        try {
            if (! File::exists($absoluteDirectory)) {
                File::makeDirectory($absoluteDirectory, 0755, true);
            }

            $photos = [];
            foreach ($request->file('photos', []) as $index => $photo) {
                $originalSize = $photo->getSize();
                $extension = strtolower($photo->getClientOriginalExtension() ?: 'jpg');
                $filename = sprintf('%02d_%s.%s', $index + 1, Str::lower(Str::random(10)), $extension);
                $photo->move($absoluteDirectory, $filename);

                $relativePath = $relativeDirectory . '/' . $filename;
                $photos[] = [
                    'name' => $photo->getClientOriginalName(),
                    'size' => $originalSize,
                    'url' => asset($relativePath),
                    'path' => $relativePath,
                ];
            }

            $payload = [
                'full_name' => $request->input('full_name'),
                'postal_code' => $request->input('postal_code'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'photos' => $photos,
                'submitted_at' => now()->format('d.m.Y H:i'),
                'submission_id' => $submissionId,
            ];

            File::put(
                $absoluteDirectory . DIRECTORY_SEPARATOR . 'submission.json',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );

            BookPurchaseRequest::query()->create([
                'submission_id' => $submissionId,
                'full_name' => $payload['full_name'],
                'postal_code' => $payload['postal_code'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'photos' => $photos,
                'storage_path' => $relativeDirectory,
                'submitted_at' => now(),
            ]);

            dispatch(function () use ($payload) {
                Mail::to(config('mail.admin'))->send(new BookPurchaseMessage($payload));
            });
        } catch (\Throwable $e) {
            Log::error('Book purchase request failed.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => __('front.book_purchase.send_error'),
                ], 500);
            }

            return back()->withErrors([
                'error' => __('front.book_purchase.send_error'),
            ])->withInput();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('front.book_purchase.sent'),
            ]);
        }

        return back()->with(['success' => __('front.book_purchase.sent')]);
    }


    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function imageCache(Request $request)
    {
        $src = $request->input('src');

        $cacheimage = Image::cache(function($image) use ($src) {
            $image->make($src);
        }, config('imagecache.lifetime'));

        return Image::make($cacheimage)->response();
    }


    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function thumbCache(Request $request)
    {
        if ( ! $request->has('src')) {
            return asset('media/img/knjiga-detalj.jpg');
        }

        $src = $request->input('src');
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $imagesHost = parse_url((string) config('settings.images_domain'), PHP_URL_HOST);

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $srcHost = parse_url($src, PHP_URL_HOST);
            $srcPath = parse_url($src, PHP_URL_PATH);

            if ($srcPath && in_array($srcHost, array_filter([$appHost, $imagesHost]), true)) {
                $src = public_path(ltrim($srcPath, '/'));
            }
        } elseif (! str_starts_with($src, DIRECTORY_SEPARATOR)) {
            $publicPath = public_path(ltrim($src, '/'));

            if (file_exists($publicPath)) {
                $src = $publicPath;
            }
        }

        $cacheimage = Image::cache(function($image) use ($request, $src) {
            $width = 250;
            $height = 300;
            $mode = $request->input('mode');

            if ($request->has('size')) {
                if (strpos($request->input('size'), 'x') !== false) {
                    $size = explode('x', $request->input('size'));
                    $width = $size[0];
                    $height = $size[1];
                }
            } else {
                $width = $request->input('size');
            }

            $image = $image->make($src);

            if ($mode === 'fit' && $width && $height) {
                $image->fit((int) $width, (int) $height, function ($constraint) {
                    $constraint->upsize();
                });

                return;
            }

            $image->resize((int) $width, $height ? (int) $height : null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

        }, config('imagecache.lifetime'));

        return Image::make($cacheimage)->response();
    }


    /**
     * @param Request $request
     * @param null    $sitemap
     *
     * @return \Illuminate\Http\Response
     */
    public function sitemapXML(Request $request, $sitemap = null)
    {
        if ( ! $sitemap) {
            return $this->cachedSitemapResponse($request, 'index', function () {
                $items = Sitemap::indexItems(config('settings.sitemap'));

                return [
                    'content' => view('front.layouts.partials.sitemap-index', compact('items'))->render(),
                    'last_modified' => $this->latestSitemapModification($items),
                ];
            });
        }

        if (in_array($sitemap, ['images', 'images.xml', 'img'], true)) {
            return redirect()->route('image-sitemap');
        }

        $descriptor = Sitemap::parseName($sitemap);

        if (! $descriptor) {
            abort(404);
        }

        $shards = Sitemap::shardCount($descriptor['type']);

        if ($descriptor['shard'] === null && $shards > 1) {
            return $this->cachedSitemapResponse($request, 'index.' . $descriptor['type'], function () use ($descriptor) {
                $items = Sitemap::indexItems([$descriptor['type']]);

                return [
                    'content' => view('front.layouts.partials.sitemap-index', compact('items'))->render(),
                    'last_modified' => $this->latestSitemapModification($items),
                ];
            });
        }

        $shard = $descriptor['shard'] ?? 1;

        if ($shard < 1 || $shard > $shards) {
            abort(404);
        }

        return $this->cachedSitemapResponse(
            $request,
            $descriptor['type'] . '.' . $shard,
            function () use ($descriptor, $shard) {
                $items = (new Sitemap($descriptor['type'], $shard))->getSitemap();

                return [
                    'content' => view('front.layouts.partials.sitemap', compact('items'))->render(),
                    'last_modified' => Sitemap::lastModifiedFor($descriptor['type'])->toAtomString(),
                ];
            }
        );
    }

    public function sitemapImageXML(Request $request, $shard = null)
    {
        $parsedShard = Sitemap::parseImageShard($shard);
        $shards = Sitemap::shardCount('images');

        if ($parsedShard === null && $shards > 1) {
            return $this->cachedSitemapResponse($request, 'images.index', function () {
                $items = Sitemap::imageIndexItems();

                return [
                    'content' => view('front.layouts.partials.sitemap-index', compact('items'))->render(),
                    'last_modified' => $this->latestSitemapModification($items),
                ];
            });
        }

        $parsedShard = $parsedShard ?? 1;

        if ($parsedShard < 1 || $parsedShard > $shards) {
            abort(404);
        }

        return $this->cachedSitemapResponse($request, 'images.' . $parsedShard, function () use ($parsedShard) {
            $items = (new Sitemap('images', $parsedShard))->getResponse();

            return [
                'content' => view('front.layouts.partials.sitemap-image', compact('items'))->render(),
                'last_modified' => Sitemap::lastModifiedFor('images')->toAtomString(),
            ];
        });
    }

    private function cachedSitemapResponse(Request $request, string $key, callable $build)
    {
        $ttl = max(60, (int) config('settings.sitemap_cache_ttl', 3600));
        $cacheKey = 'public.sitemap.v3.' . sha1(config('app.url') . '|' . $key);
        $payload = Cache::remember($cacheKey, $ttl, $build);
        $content = (string) ($payload['content'] ?? '');
        $lastModified = Carbon::parse($payload['last_modified'] ?? now());

        $response = response($content, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=' . $ttl . ', s-maxage=' . $ttl,
        ]);

        $response->setEtag(sha1($content));
        $response->setLastModified($lastModified);
        $response->isNotModified($request);

        return $response;
    }

    private function latestSitemapModification(array $items): string
    {
        $latest = collect($items)->pluck('lastmod')->filter()->max();

        return Carbon::parse($latest ?: now())->toAtomString();
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function njuskaloXML(Request $request)
    {
        $njuskalo = new Njuskalo();

        return response()->view('front.layouts.partials.njuskalo', [
            'items' => $njuskalo->getItems()
        ])->header('Content-Type', 'text/xml');
    }

}
