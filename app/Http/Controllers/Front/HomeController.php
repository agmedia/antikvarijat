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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $page = Cache::remember('page.homepage', config('cache.life'), function () {
            return Page::where('slug', 'homepage')->first();
        });

        $page->description = Helper::setDescription($page->description);

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
                        'recaptcha' => ['ReCaptcha provjera nije uspjela. Osvježite stranicu i pokušajte ponovno.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['error' => 'ReCaptcha Error! Kontaktirajte administratora!'])
                ->withInput();
        }

        if ($wish->create()) {
            return back()->with(['success' => 'Vaš Email je upisan u listu želja za ovaj artikl..!']);
        }

        return back()->with(['error' => 'Vaš Email je već upisan u listu želja za ovaj artikl!']);
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
                'message' => 'Hvala! Uspješno ste prijavljeni na newsletter.',
            ]);
        }

        return back()->with('newsletter_success', 'Hvala! Uspješno ste prijavljeni na newsletter.');
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
    public function bookPurchase()
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

        return view('front.book-purchase', compact('defaults'));
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
            return back()->withErrors(['error' => 'ReCaptcha Error! Kontaktirajte administratora!']);
        }

        $message = $request->toArray();

        dispatch(function () use ($message) {
            Mail::to(config('mail.admin'))->send(new ContactFormMessage($message));
        });

        return view('front.contact')->with(['success' => 'Vaša poruka je uspješno poslana.! Odgovoriti ćemo vam uskoro.']);
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
            'photos.max' => 'Maksimalno je dozvoljeno 20 fotografija.',
            'photos.*.max' => 'Maksimalna veličina pojedine fotografije je 4 MB.',
        ]);

        $totalUploadSize = collect($request->file('photos', []))->sum(function ($photo) {
            return $photo->getSize();
        });

        if ($totalUploadSize > 40 * 1024 * 1024) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'errors' => [
                        'photos' => ['Ukupna veličina svih fotografija može biti najviše 40 MB.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'photos' => 'Ukupna veličina svih fotografija može biti najviše 40 MB.',
            ])->withInput();
        }

        $recaptcha = (new Recaptcha())->check($request->toArray());
        if (! $recaptcha->ok()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'errors' => [
                        'recaptcha' => ['ReCaptcha provjera nije uspjela. Osvježite stranicu i pokušajte ponovno.'],
                    ],
                ], 422);
            }

            return back()->withErrors(['error' => 'ReCaptcha Error! Kontaktirajte administratora!'])
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
                    'message' => 'Došlo je do greške prilikom slanja prijave. Pokušajte ponovno.',
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Došlo je do greške prilikom slanja prijave. Pokušajte ponovno.',
            ])->withInput();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Hvala! Vaša prijava za otkup knjiga je uspješno poslana.',
            ]);
        }

        return back()->with(['success' => 'Hvala! Vaša prijava za otkup knjiga je uspješno poslana.']);
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

        $cacheimage = Image::cache(function($image) use ($request) {
            $width = 250;
            $height = 300;

            if ($request->has('size')) {
                if (strpos($request->input('size'), 'x') !== false) {
                    $size = explode('x', $request->input('size'));
                    $width = $size[0];
                    $height = $size[1];
                }
            } else {
                $width = $request->input('size');
            }

            $image->make($request->input('src'))->resize($width, $height);

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
            $items = config('settings.sitemap');

            return response()->view('front.layouts.partials.sitemap-index', [
                'items' => $items
            ])->header('Content-Type', 'text/xml');
        }

        $sm = new Sitemap($sitemap);

        return response()->view('front.layouts.partials.sitemap', [
            'items' => $sm->getSitemap()
        ])->header('Content-Type', 'text/xml');
    }


    /**
     * @return \Illuminate\Http\Response
     */
    public function sitemapImageXML()
    {
        $sm = new Sitemap('images');

        return response()->view('front.layouts.partials.sitemap-image', [
            'items' => $sm->getResponse()
        ])->header('Content-Type', 'text/xml');
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
