<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PluginView;
use Pubvana\Services\PluginViewContextMiddleware;
use Pubvana\Tests\Support\TestCase;
use stdClass;

/**
 * PluginViewContextMiddleware against a real PluginView.
 *
 * Covers the before() plugin-context set, the non-PluginView guard, and
 * the after() context clear. The middleware does no theme work: the view
 * resolves the theme override tier itself.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginViewContextMiddleware::class)]
final class PluginViewContextMiddlewareTest extends TestCase
{
    /** @var Engine<object> */
    private Engine $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->app();
    }

    public function testBeforeSetsTheActivePlugin(): void
    {
        $view = new PluginView();
        $this->app->map('view', fn(): PluginView => $view);

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->before();

        self::assertSame('pubvana/blog', $this->property($view, 'currentPlugin'));
    }

    public function testBeforeLeavesTheThemePathAlone(): void
    {
        $view = new PluginView();
        $view->setThemePath('/some/theme/Views');
        $this->app->map('view', fn(): PluginView => $view);

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->before();

        self::assertSame('/some/theme/Views', $view->getThemePath());
    }

    public function testBeforeSkipsNonPluginViewServices(): void
    {
        $this->app->map('view', fn(): object => new stdClass());

        $middleware = new PluginViewContextMiddleware($this->app, 'pubvana/blog');

        self::assertNull($middleware->before(), 'no error for a non-PluginView mapped view');
    }

    public function testAfterClearsTheActivePlugin(): void
    {
        $view = new PluginView();
        $view->setCurrentPlugin('pubvana/blog');
        $this->app->map('view', fn(): PluginView => $view);

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->after();

        self::assertNull($this->property($view, 'currentPlugin'));
    }

    public function testAfterSkipsNonPluginViewServices(): void
    {
        $this->app->map('view', fn(): object => new stdClass());

        self::assertNull((new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->after());
    }
}
