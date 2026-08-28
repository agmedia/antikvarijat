<?php

namespace Tests\Feature;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FacebookPreviewRateLimitTest extends TestCase
{
    private const FACEBOOK_USER_AGENT = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_regular_visitors_are_not_limited(): void
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $response = $this->runLimiter('/knjige/psihologija/proizvod', 'Mozilla/5.0');

            $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
            $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
        }
    }

    public function test_facebook_preview_requests_are_limited_per_path(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = $this->runLimiter(
                '/knjige/psihologija/proizvod?share='.$attempt,
                self::FACEBOOK_USER_AGENT,
                '173.252.107.'.($attempt + 1)
            );

            $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        }

        $this->assertThrottled(function () {
            $this->runLimiter(
                '/knjige/psihologija/proizvod?share=retry',
                self::FACEBOOK_USER_AGENT,
                '173.252.107.99'
            );
        });
    }

    public function test_facebook_preview_requests_have_a_global_limit_across_paths(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $response = $this->runLimiter(
                '/knjige/proizvod-'.$attempt,
                self::FACEBOOK_USER_AGENT,
                '173.252.95.'.($attempt + 1)
            );

            $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        }

        $this->assertThrottled(function () {
            $this->runLimiter(
                '/knjige/proizvod-over-limit',
                self::FACEBOOK_USER_AGENT,
                '173.252.95.99'
            );
        });
    }

    public function test_rejected_path_retries_do_not_consume_the_global_allowance(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->runLimiter('/knjige/isti-proizvod', self::FACEBOOK_USER_AGENT);
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->assertThrottled(function () {
                $this->runLimiter('/knjige/isti-proizvod', self::FACEBOOK_USER_AGENT);
            });
        }

        for ($attempt = 0; $attempt < 15; $attempt++) {
            $response = $this->runLimiter('/knjige/drugi-proizvod-'.$attempt, self::FACEBOOK_USER_AGENT);

            $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        }

        $this->assertThrottled(function () {
            $this->runLimiter('/knjige/globalno-prekoracenje', self::FACEBOOK_USER_AGENT);
        });
    }

    public function test_web_limiter_runs_before_route_model_bindings(): void
    {
        foreach (['catalog.route', 'en.catalog.route'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $middleware = app('router')->gatherRouteMiddleware($route);
            $limiterPosition = array_search(ThrottleRequests::class.':facebook-preview', $middleware, true);
            $bindingsPosition = array_search(SubstituteBindings::class, $middleware, true);

            $this->assertIsInt($limiterPosition);
            $this->assertIsInt($bindingsPosition);
            $this->assertTrue($limiterPosition < $bindingsPosition);
        }
    }

    private function runLimiter(string $path, string $userAgent, string $ip = '127.0.0.1'): Response
    {
        $request = Request::create($path, 'GET', [], [], [], [
            'HTTP_HOST' => 'www.antikvarijat-biblos.hr',
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR' => $ip,
        ]);

        return app(ThrottleRequests::class)->handle(
            $request,
            function () {
                return response('OK');
            },
            'facebook-preview'
        );
    }

    private function assertThrottled(callable $request): void
    {
        try {
            $request();
            $this->fail('Expected the Facebook preview request to be rate limited.');
        } catch (ThrottleRequestsException $exception) {
            $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $exception->getStatusCode());
            $this->assertArrayHasKey('Retry-After', $exception->getHeaders());
        }
    }
}
