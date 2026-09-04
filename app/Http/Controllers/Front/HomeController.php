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
use App\Services\NewsletterSignupGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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

        $renderVersion = sha1((string) $page->description . '|' . (string) $page->updated_at);
        $renderedDescription = Cache::remember(
            'page.homepage.rendered.v2.' . app()->getLocale() . '.' . $renderVersion,
            now()->addMinutes(5),
            fn () => Helper::setDescription((string) $page->description)
        );
        $page->setAttribute('rendered_description', $renderedDescription);

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
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function newsletter(Request $request, NewsletterSignupGuard $signupGuard, Recaptcha $recaptcha)
    {
        // Honeypot submissions receive the same answer as a real signup so a
        // bot cannot use the response to tune its payload.
        if ($signupGuard->honeypotIsFilled($request->input('website'))) {
            return $this->newsletterSuccessResponse($request);
        }

        $timingResult = $signupGuard->timingResult($request->input('newsletter_started_at'));

        if ($timingResult === NewsletterSignupGuard::TIMING_TOO_FAST) {
            return $this->newsletterValidationError(
                $request,
                'newsletter_started_at',
                __('front.newsletter.too_fast')
            );
        }

        if ($timingResult !== NewsletterSignupGuard::TIMING_ALLOWED) {
            return $this->newsletterValidationError(
                $request,
                'newsletter_started_at',
                __('front.newsletter.form_expired')
            );
        }

        $validator = Validator::make($request->only(['email', 'gdpr']), [
            'email' => 'required|string|email:rfc|max:191',
            'gdpr' => 'required|accepted',
        ], [
            'email.required' => __('front.newsletter.email_required'),
            'email.email' => __('front.newsletter.email_invalid'),
            'email.max' => __('front.newsletter.email_invalid'),
            'gdpr.required' => __('front.newsletter.gdpr_required'),
            'gdpr.accepted' => __('front.newsletter.gdpr_required'),
        ]);

        if ($validator->fails()) {
            return $this->newsletterValidationErrors($request, $validator->errors()->toArray());
        }

        $captchaEnabled = trim((string) config('services.recaptcha.sitekey')) !== ''
            && trim((string) config('services.recaptcha.secret')) !== '';

        if ($captchaEnabled) {
            $token = $request->input('recaptcha');
            $appHostname = parse_url((string) config('app.url'), PHP_URL_HOST);
            $expectedHostname = is_string($appHostname) && $appHostname !== '' ? $appHostname : null;

            if (! is_string($token)
                || strlen($token) > 4096
                || ! $recaptcha->check(['recaptcha' => $token])->ok('newsletter_subscribe', $expectedHostname)) {
                return $this->newsletterValidationError(
                    $request,
                    'recaptcha',
                    __('front.newsletter.captcha_failed')
                );
            }
        }

        NewsletterSubscriber::subscribeFromFooter(
            Str::lower(trim((string) $request->input('email'))),
            (int) (auth()->id() ?: 0)
        );

        return $this->newsletterSuccessResponse($request);
    }

    private function newsletterSuccessResponse(Request $request)
    {
        $message = __('front.newsletter.success');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return back()->with('newsletter_success', $message);
    }

    private function newsletterValidationError(Request $request, string $field, string $message)
    {
        return $this->newsletterValidationErrors($request, [
            $field => [$message],
        ]);
    }

    private function newsletterValidationErrors(Request $request, array $errors)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => collect($errors)->flatten()->first(),
                'errors' => $errors,
            ], 422);
        }

        return back()
            ->withErrors($errors)
            ->withInput($request->only(['newsletter_form', 'email', 'gdpr']));
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
        $fallback = $this->fallbackImagePath();
        $src = $this->resolvePublicImagePath($request->input('src'), $fallback);

        try {
            $cacheimage = Image::cache(function($image) use ($src) {
                $image->make($src);
            }, config('imagecache.lifetime'));
        } catch (\Throwable $exception) {
            $cacheimage = Image::cache(function($image) use ($fallback) {
                $image->make($fallback);
            }, config('imagecache.lifetime'));
        }

        return Image::make($cacheimage)->response();
    }


    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function thumbCache(Request $request)
    {
        $fallback = $this->fallbackImagePath();
        $src = $this->resolvePublicImagePath($request->input('src'), $fallback);
        [$width, $height] = $this->thumbnailDimensions($request->input('size'));
        $mode = $request->input('mode') === 'fit' ? 'fit' : 'resize';

        try {
            $cacheimage = $this->cachedThumbnail($src, $width, $height, $mode);
        } catch (\Throwable $exception) {
            $cacheimage = $this->cachedThumbnail($fallback, $width, $height, $mode);
        }

        return Image::make($cacheimage)->response();
    }

    private function cachedThumbnail(string $src, int $width, ?int $height, string $mode)
    {
        return Image::cache(function($image) use ($src, $width, $height, $mode) {
            $image = $image->make($src);

            if ($mode === 'fit' && $height) {
                $image->fit($width, $height, function ($constraint) {
                    $constraint->upsize();
                });

                return;
            }

            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }, config('imagecache.lifetime'));
    }

    /**
     * Resolve only existing files below public/. Invalid, external and missing
     * sources deliberately use the storefront placeholder instead of logging a 500.
     */
    private function resolvePublicImagePath(?string $source, string $fallback): string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return $fallback;
        }

        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($source, PHP_URL_SCHEME));
            $host = strtolower((string) parse_url($source, PHP_URL_HOST));
            $allowedHosts = array_values(array_unique(array_filter(array_map(
                fn ($url) => strtolower((string) parse_url((string) $url, PHP_URL_HOST)),
                [config('app.url'), config('settings.images_domain')]
            ))));

            if (! in_array($scheme, ['http', 'https'], true) || ! in_array($host, $allowedHosts, true)) {
                return $fallback;
            }

            $source = (string) parse_url($source, PHP_URL_PATH);
        }

        $relativePath = ltrim(rawurldecode($source), '/');
        $candidate = realpath(public_path($relativePath));
        $publicRoot = realpath(public_path());

        if (! $candidate || ! $publicRoot || ! is_file($candidate)) {
            return $fallback;
        }

        if ($candidate !== $publicRoot && ! str_starts_with($candidate, $publicRoot . DIRECTORY_SEPARATOR)) {
            return $fallback;
        }

        return $candidate;
    }

    /**
     * Keep generated thumbnails within a predictable memory/CPU budget.
     * A single value (for example 600) means proportional width resize.
     */
    private function thumbnailDimensions(?string $size): array
    {
        $size = trim((string) $size);

        if ($size === '' || ! preg_match('/^(\d{1,4})(?:x(\d{1,4}))?$/', $size, $matches)) {
            return [250, 300];
        }

        $width = min(max((int) $matches[1], 1), 2000);
        $height = isset($matches[2]) ? min(max((int) $matches[2], 1), 2000) : null;

        return [$width, $height];
    }

    private function fallbackImagePath(): string
    {
        return public_path('media/img/knjiga-detalj.jpg');
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
        $cacheKey = 'public.sitemap.v4.' . sha1(config('app.url') . '|' . $key);
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
