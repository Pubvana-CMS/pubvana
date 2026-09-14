<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Config;

use Enlivenapp\FlightCsrf\Middlewares\CsrfMiddleware;
use Enlivenapp\FlightShield\Middlewares\ForcePasswordResetMiddleware;
use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Enlivenapp\FlightShield\Middlewares\RateLimitMiddleware;
use flight\Engine;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * routes.php coverage.
 *
 * Includes the file against a fresh Engine and asserts the six core routes
 * exist with the expected middleware. header() output from the security
 * middleware is suppressed (CLI would warn); the middleware itself is
 * covered by its own test.
 */
#[CoversNothing]
final class RoutesConfigTest extends TestCase
{
    public function testCoreRoutesRegistered(): void
    {
        $app = $this->freshEngine();
        $this->includeRoutes($app);

        // Flight splits "METHOD /url" into methods[] + pattern.
        $sigs = [];
        foreach ($app->router()->getRoutes() as $route) {
            foreach ($route->methods as $method) {
                $sigs[] = $method . ' ' . $route->pattern;
            }
        }

        foreach ([
            'GET /assets/@type/@name/@path:.+',
            'GET /admin',
            'GET /auth/forgot',
            'POST /auth/forgot/send',
            'GET /auth/reset-password',
            'POST /auth/reset-password/process',
            'GET /',
        ] as $expected) {
            self::assertContains($expected, $sigs, "route {$expected} missing");
        }
    }

    public function testAdminRouteHasGuards(): void
    {
        $app = $this->freshEngine();
        $this->includeRoutes($app);

        $route = $this->findRoute($app, 'GET /admin');
        self::assertNotNull($route);
        $classes = array_map(static fn($m): string => is_object($m) ? $m::class : (string) $m, $route->middleware);
        self::assertContains(ForcePasswordResetMiddleware::class, $classes);
        self::assertContains(PermissionMiddleware::class, $classes);
    }

    public function testAuthPostsHaveCsrfAndRateLimit(): void
    {
        $app = $this->freshEngine();
        $this->includeRoutes($app);

        foreach (['POST /auth/forgot/send', 'POST /auth/reset-password/process'] as $pattern) {
            $route = $this->findRoute($app, $pattern);
            self::assertNotNull($route, $pattern);
            $classes = array_map(static fn($m): string => is_object($m) ? $m::class : (string) $m, $route->middleware);
            self::assertContains(CsrfMiddleware::class, $classes, $pattern);
            self::assertContains(RateLimitMiddleware::class, $classes, $pattern);
        }
    }

    public function testAuthGetsHaveNoMiddleware(): void
    {
        $app = $this->freshEngine();
        $this->includeRoutes($app);

        foreach (['GET /auth/forgot', 'GET /auth/reset-password'] as $pattern) {
            $route = $this->findRoute($app, $pattern);
            self::assertNotNull($route, $pattern);
            self::assertSame([], $route->middleware, $pattern);
        }
    }

    private function freshEngine(): Engine
    {
        $app = new Engine();
        $app->init();
        \Flight::setEngine($app);

        return $app;
    }

    private function includeRoutes(Engine $app): void
    {
        // routes.php calls header() via the security middleware; suppress CLI warnings.
        set_error_handler(static fn(): bool => true);
        try {
            require PROJECT_ROOT . '/app/config/routes.php';
        } finally {
            restore_error_handler();
        }
    }

    private function findRoute(Engine $app, string $pattern): ?object
    {
        [$method, $url] = explode(' ', $pattern, 2);
        foreach ($app->router()->getRoutes() as $route) {
            if ($route->pattern === $url && in_array($method, $route->methods, true)) {
                return $route;
            }
        }

        return null;
    }
}
