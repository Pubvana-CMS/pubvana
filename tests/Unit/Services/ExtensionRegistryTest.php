<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\net\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * ExtensionRegistry is a pure in-memory registry with no constructor
 * dependencies, so the full public surface is unit-testable in isolation.
 */
#[CoversClass(ExtensionRegistry::class)]
final class ExtensionRegistryTest extends TestCase
{
    public function testRegisterSingleItemThenGetSortedByPriority(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('admin.menu', 'content', 'pubvana.blog', [
            'label'    => 'Blog',
            'icon'     => 'ti-pencil',
            'url'      => '/blog',
            'priority' => 20,
        ]);
        $registry->register('admin.menu', 'content', 'pubvana.pages', [
            'label'    => 'Pages',
            'url'      => '/pages',
            'priority' => 10,
        ]);

        $items = $registry->get('admin.menu', 'content');

        self::assertCount(2, $items);
        self::assertSame(['pubvana.pages', 'pubvana.blog'], array_keys($items));
        self::assertSame('Pages', $items['pubvana.pages']['label']);
    }

    public function testRegisterBatchMode(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('admin.menu', 'content', [
            'pubvana.one' => ['label' => 'One', 'url' => '/one'],
            'pubvana.two' => ['label' => 'Two', 'url' => '/two'],
        ]);

        self::assertCount(2, $registry->get('admin.menu', 'content'));
        self::assertTrue($registry->has('admin.menu', 'content'));
    }

    public function testAdminUrlsGetPrefixedWithAdmin(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('admin.menu', 'content', 'pubvana.users', [
            'label' => 'Users',
            'url'   => '/users',
        ]);

        $items = $registry->get('admin.menu', 'content');
        self::assertSame('/admin/users', $items['pubvana.users']['url']);
    }

    public function testAdminSubmenuUrlsGetPrefixed(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('admin.menu', 'content', 'pubvana.seo', [
            'label'   => 'Seo',
            'url'     => '/seo',
            'submenu' => [
                'sitemaps' => ['label' => 'Sitemaps', 'url' => '/seo/sitemaps'],
            ],
        ]);

        $items = $registry->get('admin.menu', 'content');
        self::assertSame('/admin/seo/sitemaps', $items['pubvana.seo']['submenu']['sitemaps']['url']);
    }

    public function testPublicUrlsAreNotPrefixed(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('public.nav', 'main', 'pubvana.home', [
            'label' => 'Home',
            'url'   => '/',
        ]);

        $items = $registry->get('public.nav', 'main');
        self::assertSame('/', $items['pubvana.home']['url']);
    }

    public function testUnknownTypeIsRejected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('nope.menu', 'content', 'pubvana.x', ['label' => 'X', 'url' => '/x']);

        self::assertFalse($registry->has('nope.menu', 'content'));
    }

    public function testUnknownSlotIsRejected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('admin.menu', 'not-a-slot', 'pubvana.x', ['label' => 'X', 'url' => '/x']);

        self::assertFalse($registry->has('admin.menu', 'not-a-slot'));
    }

    public function testMissingRequiredKeyIsRejected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('admin.menu', 'content', 'pubvana.x', ['label' => 'X']);

        self::assertFalse($registry->has('admin.menu', 'content'));
    }

    public function testUnknownConfigKeyIsRejected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('admin.css', 'default', 'pubvana.x', ['url' => '/x.css', 'bogus' => true]);

        self::assertFalse($registry->has('admin.css', 'default'));
    }

    public function testDuplicateKeyIsRejected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('admin.menu', 'content', 'pubvana.x', ['label' => 'X', 'url' => '/x']);
        $registry->register('admin.menu', 'content', 'pubvana.x', ['label' => 'Y', 'url' => '/y']);

        $items = $registry->get('admin.menu', 'content');
        self::assertCount(1, $items);
        self::assertSame('X', $items['pubvana.x']['label']);
    }

    public function testPluginAssetUrlIsTransformed(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('public.css', 'default', 'pubvana.blog', [
            'url' => '/plugins/Blog/assets/blog.css',
        ]);

        $items = $registry->get('public.css', 'default');
        self::assertSame('/assets/plugin/Blog/blog.css', $items['pubvana.blog']['url']);
    }

    public function testGetInvokesCallableWithContextAndMergesArrayResult(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('content.edit.panel', 'default', 'pubvana.seo', [
            'label'    => 'Seo Panel',
            'callable' => fn (array $context): array => ['output' => 'ctx ' . ($context['id'] ?? 'none')],
        ]);

        $items = $registry->get('content.edit.panel', 'default', ['id' => 42]);
        self::assertSame('ctx 42', $items['pubvana.seo']['output']);
    }

    public function testGetWithContextStringCallableStoresOutput(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('content.edit.panel', 'default', 'pubvana.panel', [
            'label'    => 'Panel',
            'callable' => fn (array $context): string => '<aside>side</aside>',
        ]);

        $items = $registry->get('content.edit.panel', 'default', ['id' => 1]);
        self::assertSame('<aside>side</aside>', $items['pubvana.panel']['output']);
    }

    public function testRegisterCollectsRouteForAdminType(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register('admin.menu', 'content', 'pubvana.users', [
            'label' => 'Users',
            'url'   => '/users',
            'route' => ['GET', '/users', [\stdClass::class, 'index']],
        ]);

        $routes = $registry->getRoutes();
        self::assertCount(1, $routes);
        self::assertSame('GET', $routes[0]['method']);
        self::assertSame('/users', $routes[0]['path']);
        self::assertSame('admin', $routes[0]['scope']);
        self::assertSame('pubvana.users', $routes[0]['source']);
        self::assertFalse($routes[0]['isCore']);
    }

    public function testAddRouteCountsAdded(): void
    {
        $registry = new ExtensionRegistry();

        $count = $registry->addRoute('POST', '/submit', [\stdClass::class, 'x']);
        self::assertSame(1, $count);
        self::assertCount(1, $registry->getRoutes());
    }

    public function testAddRoutesBatchCountsAdded(): void
    {
        $registry = new ExtensionRegistry();

        $count = $registry->addRoutes('public', [
            ['GET', '/a', [\stdClass::class, 'a']],
            ['post', '/b', [\stdClass::class, 'b'], [new \stdClass()]],
        ]);

        self::assertSame(2, $count);
        $routes = $registry->getRoutes();
        self::assertSame('POST', $routes[1]['method']);
    }

    public function testFirstRegisteredRouteWinsOnConflict(): void
    {
        $registry = new ExtensionRegistry();

        $registry->addRoute('GET', '/users', [\stdClass::class, 'a'], [], 'admin', 'pubvana.one');
        $registry->addRoute('GET', '/users', [\stdClass::class, 'b'], [], 'admin', 'pubvana.two');

        $routes = $registry->getRoutes();
        self::assertCount(1, $routes);
        self::assertSame('pubvana.one', $routes[0]['source']);
    }

    public function testCoreRouteCannotBeOverriddenFromArray(): void
    {
        $registry = new ExtensionRegistry();

        $registry->addRoutes('admin', [[
            'GET', '/users', [\stdClass::class, 'a'], [], 'admin', 'pubvana.one',
        ]], 'pubvana.one', true);

        $registry->addRoute('GET', '/users', [\stdClass::class, 'b'], [], 'admin', 'pubvana.two');

        $routes = $registry->getRoutes();
        self::assertCount(1, $routes);
        self::assertTrue($routes[0]['isCore']);
        self::assertSame('pubvana.one', $routes[0]['source']);
    }

    public function testIsCoreSignatureBlockedByAlreadyAddedCoreRoute(): void
    {
        $registry = new ExtensionRegistry();

        $registry->addRoute('GET', '/drugs', [\stdClass::class, 'a'], [], 'public', 'pubvana.core', true);
        $registry->addRoute('GET', '/drugs', [\stdClass::class, 'b'], [], 'public', 'pubvana.plug');

        $routes = $registry->getRoutes();
        self::assertCount(1, $routes);
        self::assertSame('pubvana.core', $routes[0]['source']);
    }

    public function testGetWithNoContributionsReturnsEmpty(): void
    {
        $registry = new ExtensionRegistry();
        self::assertSame([], $registry->get('admin.menu', 'content'));
        self::assertFalse($registry->has('admin.menu', 'content'));
    }

    public function testRegisterRoutesMapsPublicAndAdminRoutesOnRouter(): void
    {
        $registry = new ExtensionRegistry();
        $registry->addRoute('GET', '/feed', [\stdClass::class, 'feed'], [], 'public', 'pubvana.feed');
        $registry->addRoute('GET', '/users', [\stdClass::class, 'users'], [], 'admin', 'pubvana.users');

        $app = $this->app();
        $registry->registerRoutes($app);

        $router = $app->router();

        $publicMatch = $router->route($this->makeRequest('/feed', 'GET'));
        self::assertNotFalse($publicMatch);
        self::assertSame([\stdClass::class, 'feed'], $publicMatch->callback);

        $adminMatch = $router->route($this->makeRequest('/admin/users', 'GET'));
        self::assertNotFalse($adminMatch);
        self::assertSame([\stdClass::class, 'users'], $adminMatch->callback);
    }

    public function testRegisterRoutesAppliesObjectMiddleware(): void
    {
        $middleware = new class {
        };
        $nonObject = ['a', 'b'];

        $registry = new ExtensionRegistry();
        $registry->addRoute('POST', '/submit', [\stdClass::class, 'x'], [$middleware, $nonObject], 'public', 'pubvana.x');

        $app = $this->app();
        $registry->registerRoutes($app);

        $matched = $app->router()->route($this->makeRequest('/submit', 'POST'));
        self::assertNotFalse($matched);
        self::assertContains($middleware, $matched->middleware);
        self::assertNotContains($nonObject, $matched->middleware);
    }

    /**
     * Build a minimal Request. Construct with no config so all typed
     * properties initialize, then override the routing-relevant ones.
     */
    private function makeRequest(string $url, string $method): Request
    {
        $request = new Request();
        $request->url = $url;
        $request->method = $method;
        $request->base = '';

        return $request;
    }
}
