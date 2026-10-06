<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class UseStatelessCrawlerSessions
{
    private const PUBLIC_ROUTES = [
        'index',
        'kontakt',
        'faq',
        'pretrazi',
        'tag',
        'catalog.route.page',
        'catalog.route.blog',
        'catalog.route.author',
        'catalog.route.publisher',
        'catalog.route.actions',
        'reviews.index',
        'featured.monthly-best-sellers',
        'sitemap',
        'image-sitemap',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! $this->shouldUseStatelessSession($request)) {
            return $next($request);
        }

        $originalDriver = config('session.driver');
        $manager = app('session');
        $request->attributes->set('stateless_crawler', true);

        config(['session.driver' => 'array']);
        $manager->forgetDrivers();
        app()->forgetInstance('session.store');

        try {
            $response = $next($request);

            // Laravel still adds cookies for the array driver. Crawlers should
            // remain anonymous instead of returning with a new file session.
            foreach ([config('session.cookie'), 'XSRF-TOKEN'] as $cookieName) {
                $response->headers->removeCookie(
                    $cookieName,
                    config('session.path', '/'),
                    config('session.domain')
                );
            }

            return $response;
        } finally {
            config(['session.driver' => $originalDriver]);
            $manager->forgetDrivers();
            app()->forgetInstance('session.store');
        }
    }

    private function shouldUseStatelessSession(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)
            || $request->headers->has('Authorization')
            || $request->cookies->has((string) config('session.cookie'))
            || ! $this->isRecognizedCrawler((string) $request->userAgent())) {
            return false;
        }

        // A remembered login can authenticate a request without a session
        // cookie. Preserve that state even if its user agent resembles a bot.
        foreach (array_keys($request->cookies->all()) as $cookieName) {
            if (strpos((string) $cookieName, 'remember_') === 0) {
                return false;
            }
        }

        try {
            // Matching the route does not invoke model binding or controllers.
            // Known private routes retain their normal session behavior.
            $route = app('router')->getRoutes()->match($request);
        } catch (NotFoundHttpException | MethodNotAllowedHttpException $exception) {
            return ! $this->hasSensitivePathPrefix($request);
        } catch (Throwable $exception) {
            return false;
        }

        return $this->isPublicRoute($route, $request);
    }

    private function isPublicRoute(Route $route, Request $request): bool
    {
        $name = (string) $route->getName();

        if (strpos($name, 'en.') === 0) {
            $name = substr($name, 3);
        }

        if ($name === 'catalog.route' || $route->isFallback || $route->uri() === '{path}') {
            // The catalog and extension catchalls also handle crawler scans
            // and missing URLs. Those 404s must not accumulate session files.
            return ! $this->hasSensitivePathPrefix($request);
        }

        if (in_array($name, self::PUBLIC_ROUTES, true)) {
            return true;
        }

        // These existing public routes intentionally have no route name.
        return in_array($route->uri(), [
            'sitemap.xml',
            'proizvod/{prod?}',
            'kategorija-proizvoda/{group?}/{cat?}/{subcat?}',
        ], true);
    }

    private function hasSensitivePathPrefix(Request $request): bool
    {
        $segments = array_values(array_filter(
            explode('/', strtolower(rawurldecode($request->path()))),
            fn ($segment) => $segment !== ''
        ));

        if (in_array($segments[0] ?? '', ['en', 'hr'], true)) {
            array_shift($segments);
        }

        // Only a secondary safeguard for catchalls/unmatched URLs. Named
        // login, customer, checkout and signed-link routes are not allowlisted.
        return in_array($segments[0] ?? '', [
            'admin', 'api', 'livewire', 'sanctum', 'oauth', 'auth',
            'login', 'logout', 'register', 'prijava', 'odjava', 'registracija',
            'forgot-password', 'reset-password', 'password', 'confirm-password',
            'two-factor-challenge', 'verify-email', 'email',
            'user', 'account', 'my-account', 'moj-racun', 'profile', 'profil',
            'cart', 'kosarica', 'checkout', 'naplata', 'pregled',
            'order', 'orders', 'narudzba', 'narudzbe',
            'payment', 'payments', 'placanje', 'uspjeh', 'greska',
            'wishlist', 'wishlist-obavijest', 'zahtjev-za-recenziju',
            'poklon-bon', 'gift-voucher',
            'forma-za-povrat-i-reklamacije', 'returns-and-complaints',
            'private', 'internal', 'callback', 'callbacks', 'webhook', 'webhooks',
        ], true);
    }

    private function isRecognizedCrawler(string $userAgent): bool
    {
        return preg_match('/(?:Googlebot|Google-InspectionTool|bingbot|BingPreview|DuckDuckBot|Applebot|YandexBot|Baiduspider|PetalBot|PerplexityBot|ExaSearchBot|facebookexternalhit|Facebot|meta-externalagent|meta-externalfetcher|Twitterbot|LinkedInBot|Slackbot-LinkExpanding|Discordbot|TelegramBot|Pinterestbot|GPTBot|ChatGPT-User|ClaudeBot|Claude-SearchBot|Claude-User|ReflectionBot)/i', $userAgent) === 1;
    }
}
