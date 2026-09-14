<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Theme;
use Pubvana\Services\PluginView;
use Pubvana\Services\PluginViewContextMiddleware;
use Pubvana\Services\ThemeService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use stdClass;

/**
 * PluginViewContextMiddleware against a real PluginView and the
 * in-memory themes table.
 *
 * Covers the before() context set + theme path sync (fresh, stale,
 * already-correct, no active theme), the non-PluginView and throwing
 * themes-service guards, and the after() context clear.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginViewContextMiddleware::class)]
final class PluginViewContextMiddlewareTest extends TestCase
{
    private PDO $pdo;

    private Engine $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'themes' => function (): ThemeService {
                static $service = null;
                if ($service === null) {
                    $service = new ThemeService(\Flight::app());
                }
                return $service;
            },
        ]);
        \Flight::setEngine($this->app);
    }

    public function testBeforeSetsTheActivePluginAndSyncsTheThemePath(): void
    {
        $this->seedActiveTheme('alpha');
        $view = new PluginView();
        $view->setThemePath('/stale-theme/Views');
        $this->app->map('view', fn(): PluginView => $view);

        $middleware = new PluginViewContextMiddleware($this->app, 'pubvana/blog');
        $middleware->before();

        self::assertSame('pubvana/blog', $this->property($view, 'currentPlugin'));
        self::assertSame(
            PROJECT_ROOT . '/themes/alpha/Views',
            $view->getThemePath(),
            'theme override tier follows the active theme'
        );
    }

    public function testBeforeIsIdempotentForAnAlreadyCorrectThemePath(): void
    {
        $this->seedActiveTheme('alpha');
        $view = new PluginView();
        $view->setThemePath(PROJECT_ROOT . '/themes/alpha/Views');
        $this->app->map('view', fn(): PluginView => $view);

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->before();

        self::assertSame(PROJECT_ROOT . '/themes/alpha/Views', $view->getThemePath());
    }

    public function testBeforeKeepsTheThemePathWhenNoThemeIsActive(): void
    {
        $view = new PluginView();
        $view->setThemePath('/boot-fallback/Views');
        $this->app->map('view', fn(): PluginView => $view);

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->before();

        self::assertSame('pubvana/blog', $this->property($view, 'currentPlugin'));
        self::assertSame('/boot-fallback/Views', $view->getThemePath());
    }

    public function testBeforeSkipsNonPluginViewServices(): void
    {
        $this->app->map('view', fn(): object => new stdClass());

        $middleware = new PluginViewContextMiddleware($this->app, 'pubvana/blog');

        self::assertNull($middleware->before(), 'no error for a non-PluginView mapped view');
    }

    public function testBeforeSwallowsThemesServiceThrowables(): void
    {
        $view = new PluginView();
        $this->app->map('view', fn(): PluginView => $view);
        $this->app->map('themes', fn(): object => new class {
            public function getActive(): never
            {
                throw new \RuntimeException('themes table missing');
            }
        });

        (new PluginViewContextMiddleware($this->app, 'pubvana/blog'))->before();

        self::assertSame('pubvana/blog', $this->property($view, 'currentPlugin'), 'context is still set');
        self::assertNull($view->getThemePath(), 'boot fallback theme path is untouched');
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

    /**
     * Seed an active theme row.
     */
    private function seedActiveTheme(string $folder): void
    {
        $theme = new Theme($this->pdo);
        $theme->name = $folder;
        $theme->folder = $folder;
        $theme->is_active = 1;
        $theme->insert();
    }
}
