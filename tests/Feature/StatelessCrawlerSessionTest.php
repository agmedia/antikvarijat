<?php

namespace Tests\Feature;

use App\Http\Middleware\UseStatelessCrawlerSessions;
use App\Session\LockedFileSessionHandler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StatelessCrawlerSessionTest extends TestCase
{
    private string $sessionDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionDirectory = sys_get_temp_dir().'/biblos-crawler-session-'.bin2hex(random_bytes(8));
        (new Filesystem())->makeDirectory($this->sessionDirectory);
        config([
            'session.driver' => 'file',
            'session.files' => $this->sessionDirectory,
            'session.cookie' => 'biblos_test_session',
            'session.lottery' => [0, 100],
            'session.block' => false,
        ]);
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->sessionDirectory);

        parent::tearDown();
    }

    /** @dataProvider crawlerUserAgents */
    public function test_anonymous_crawler_uses_array_handler_without_files_or_session_cookies(string $userAgent): void
    {
        // Even a previously resolved file store must not be reused by the bot.
        $this->assertInstanceOf(FileSessionHandler::class, app('session.store')->getHandler());

        $request = $this->request('/knjige/povijest/proizvod', $userAgent);
        $response = $this->runSession($request, function (Request $request) {
            $this->assertTrue($request->attributes->get('stateless_crawler'));
            $this->assertSame('array', config('session.driver'));
            $this->assertInstanceOf(ArraySessionHandler::class, $request->session()->getHandler());
            $this->assertInstanceOf(ArraySessionHandler::class, app('session.store')->getHandler());
            session(['recent_products' => [123]]);

            return response('public product');
        });

        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));
        $this->assertSame([], $response->headers->getCookies());
        $this->assertSame('file', config('session.driver'));
        $this->assertInstanceOf(FileSessionHandler::class, app('session.store')->getHandler());
    }

    public function crawlerUserAgents(): array
    {
        return [
            ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            ['PerplexityBot/1.0'],
            ['ExaSearchBot/1.0'],
            ['facebookexternalhit/1.1'],
            ['meta-externalagent/1.1'],
        ];
    }

    /** @dataProvider publicPaths */
    public function test_public_catalog_and_static_head_requests_are_stateless(string $path): void
    {
        $request = $this->request($path, 'Googlebot/2.1', 'HEAD');
        $this->runSession($request, function (Request $request) {
            $this->assertInstanceOf(ArraySessionHandler::class, $request->session()->getHandler());

            return response('public page');
        });

        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));
    }

    public function publicPaths(): array
    {
        return [
            ['/'],
            ['/pretrazi?pojam=knjiga'],
            ['/autor/test-autor'],
            ['/nakladnik/test-nakladnik'],
            ['/blog/test-clanak'],
            ['/info/uvjeti'],
            ['/en/books/history/product'],
            ['/en/search?pojam=book'],
        ];
    }

    public function test_normal_visitor_and_existing_crawler_session_keep_persisted_state(): void
    {
        $human = $this->request('/knjige/povijest/proizvod', 'Mozilla/5.0');
        $this->runSession($human, function (Request $request) {
            $this->assertFalse($request->attributes->get('stateless_crawler', false));
            $this->assertInstanceOf(FileSessionHandler::class, $request->session()->getHandler());
            $request->session()->put('cart.product_id', 123);

            return response('human page');
        });

        $sessionId = $human->session()->getId();
        $crawlerWithCookie = $this->request('/knjige/povijest/proizvod', 'Googlebot/2.1', 'GET', [
            config('session.cookie') => $sessionId,
        ]);
        $response = $this->runSession($crawlerWithCookie, function (Request $request) {
            $this->assertFalse($request->attributes->get('stateless_crawler', false));
            $this->assertInstanceOf(FileSessionHandler::class, $request->session()->getHandler());
            $this->assertSame(123, $request->session()->get('cart.product_id'));

            return response('existing state');
        });

        $this->assertCount(1, (new Filesystem())->files($this->sessionDirectory));
        $this->assertSame($sessionId, $response->headers->getCookies()[0]->getValue());
    }

    /** @dataProvider statefulPaths */
    public function test_auth_checkout_and_private_paths_keep_file_sessions(string $path, string $method = 'GET'): void
    {
        $request = $this->request($path, 'Googlebot/2.1', $method);
        $this->runSession($request, function (Request $request) {
            $this->assertFalse($request->attributes->get('stateless_crawler', false));
            $this->assertInstanceOf(FileSessionHandler::class, $request->session()->getHandler());

            return response('stateful page');
        });

        $this->assertCount(1, (new Filesystem())->files($this->sessionDirectory));
    }

    public function statefulPaths(): array
    {
        return [
            ['/prijava'],
            ['/login'],
            ['/register'],
            ['/forgot-password'],
            ['/reset-password/token'],
            ['/admin/dashboard'],
            ['/user/profile'],
            ['/kosarica'],
            ['/naplata'],
            ['/pregled'],
            ['/narudzba'],
            ['/uspjeh'],
            ['/en/cart'],
            ['/en/checkout'],
            ['/en/checkout/review'],
            ['/wishlist-obavijest/123'],
            ['/zahtjev-za-recenziju/token'],
            ['/poklon-bon'],
            ['/private/reports'],
            ['/account/missing-page'],
            ['/en/my-account/missing-page'],
            ['/admin/missing-page'],
            ['/api/private/missing-page'],
            ['/kosarica/missing-page'],
            ['/en/checkout/missing-page'],
            ['/knjige/povijest/proizvod', 'POST'],
        ];
    }

    /** @dataProvider crawlerMissingPaths */
    public function test_crawler_scans_and_catalog_catchall_404s_do_not_create_session_files(string $path): void
    {
        $request = $this->request($path, 'Googlebot/2.1');
        $response = $this->runSession($request, function (Request $request) {
            $this->assertTrue($request->attributes->get('stateless_crawler'));
            $this->assertInstanceOf(ArraySessionHandler::class, $request->session()->getHandler());
            $request->session()->put('previous_path', 'not persisted');

            return response('missing page', 404);
        });

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([], $response->headers->getCookies());
        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));
    }

    public function crawlerMissingPaths(): array
    {
        return [
            ['/unknown-private-page'],
            ['/unrecognized-catalog-group/category/product'],
            ['/wp-login.php'],
            ['/.env'],
            ['/wp-json/wp/v2/users'],
            ['/more/segments/than/catalog/can/match/here'],
        ];
    }

    public function test_route_miss_uses_array_except_for_sensitive_unmatched_paths(): void
    {
        Route::setRoutes(new RouteCollection());

        $this->runSession($this->request('/scanner-missing.php', 'Googlebot/2.1'), function (Request $request) {
            $this->assertTrue($request->attributes->get('stateless_crawler'));
            $this->assertInstanceOf(ArraySessionHandler::class, $request->session()->getHandler());

            return response('missing', 404);
        });
        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));

        $this->runSession($this->request('/en/checkout/missing-page', 'Googlebot/2.1'), function (Request $request) {
            $this->assertFalse($request->attributes->get('stateless_crawler', false));
            $this->assertInstanceOf(FileSessionHandler::class, $request->session()->getHandler());

            return response('missing private page', 404);
        });
        $this->assertCount(1, (new Filesystem())->files($this->sessionDirectory));
    }

    public function test_authorization_or_remember_login_cookie_keeps_normal_session(): void
    {
        foreach ([
            $this->request('/knjige', 'Googlebot/2.1', 'GET', [], ['HTTP_AUTHORIZATION' => 'Bearer example']),
            $this->request('/knjige', 'Googlebot/2.1', 'GET', ['remember_web_example' => 'example']),
        ] as $request) {
            $this->runSession($request, function (Request $request) {
                $this->assertFalse($request->attributes->get('stateless_crawler', false));
                $this->assertInstanceOf(FileSessionHandler::class, $request->session()->getHandler());

                return response('authenticated state');
            });
        }
    }

    public function test_driver_is_restored_when_downstream_throws(): void
    {
        try {
            app(UseStatelessCrawlerSessions::class)->handle(
                $this->request('/knjige', 'Googlebot/2.1'),
                function () {
                    throw new RuntimeException('test failure');
                }
            );
            $this->fail('Expected the downstream exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('test failure', $exception->getMessage());
        }

        $this->assertSame('file', config('session.driver'));
        $this->assertInstanceOf(FileSessionHandler::class, app('session.store')->getHandler());
        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));
    }

    public function test_http_middleware_stack_does_not_emit_crawler_session_or_csrf_cookies(): void
    {
        Route::setRoutes(new RouteCollection());
        Route::middleware('web')->get('/public-probe', function (Request $request) {
            $request->session()->put('recent_products', [123]);

            return response()->json([
                'stateless' => $request->attributes->get('stateless_crawler', false),
                'handler' => get_class($request->session()->getHandler()),
            ]);
        })->name('catalog.route.page');

        $crawler = $this->get('/public-probe', ['User-Agent' => 'Googlebot/2.1']);
        $crawler->assertOk()->assertExactJson([
            'stateless' => true,
            'handler' => ArraySessionHandler::class,
        ]);
        $this->assertSame([], $crawler->headers->getCookies());
        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));

        $human = $this->get('/public-probe', ['User-Agent' => 'Mozilla/5.0']);
        $human->assertOk()->assertExactJson([
            'stateless' => false,
            'handler' => LockedFileSessionHandler::class,
        ]);
        $human->assertCookie(config('session.cookie'));
        $this->assertCount(1, (new Filesystem())->files($this->sessionDirectory));
    }

    public function test_http_fallback_404_keeps_crawler_stateless_and_private_prefix_stateful(): void
    {
        Route::setRoutes(new RouteCollection());
        Route::fallback(function (Request $request) {
            $request->session()->put('previous_path', 'not persisted for crawlers');

            return response()->json([
                'stateless' => $request->attributes->get('stateless_crawler', false),
                'handler' => get_class($request->session()->getHandler()),
            ], 404);
        })->middleware('web');

        $crawler = $this->get('/wp-login.php', ['User-Agent' => 'Googlebot/2.1']);
        $crawler->assertNotFound()->assertExactJson([
            'stateless' => true,
            'handler' => ArraySessionHandler::class,
        ]);
        $this->assertSame([], $crawler->headers->getCookies());
        $this->assertSame([], (new Filesystem())->files($this->sessionDirectory));

        $private = $this->get('/account/missing-page', ['User-Agent' => 'Googlebot/2.1']);
        $private->assertNotFound()->assertExactJson([
            'stateless' => false,
            'handler' => LockedFileSessionHandler::class,
        ]);
        $private->assertCookie(config('session.cookie'));
        $this->assertCount(1, (new Filesystem())->files($this->sessionDirectory));
    }

    private function runSession(Request $request, callable $next): Response
    {
        return app(UseStatelessCrawlerSessions::class)->handle($request, function (Request $request) use ($next) {
            return app(StartSession::class)->handle($request, $next);
        });
    }

    private function request(string $path, string $userAgent, string $method = 'GET', array $cookies = [], array $headers = []): Request
    {
        return Request::create($path, $method, [], $cookies, [], array_merge([
            'HTTP_HOST' => 'www.antikvarijat-biblos.hr',
            'HTTP_USER_AGENT' => $userAgent,
        ], $headers));
    }
}
