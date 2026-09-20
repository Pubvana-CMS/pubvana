<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Public\ErrorController;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginView;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

/**
 * ErrorController: the themed 404 guard and the rendered page.
 *
 * wantsThemedHtml() is exercised against a hand-mapped Engine whose
 * request reflects the $_SERVER keys each test sets. The rendered page
 * runs buildErrorPage() end to end through a real PluginView and a
 * fixture theme under themes/{fixture} (cleaned up).
 *
 * @package Pubvana\Tests\Unit
 */
#[CoversClass(ErrorController::class)]
final class ErrorControllerTest extends TestCase
{
    private string $tmpRoot;

    public const FIXTURE_THEME = "_fxerror";

    /** @var array<string, string|null> server-key backup for restoration */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-error-' . uniqid();
        mkdir($this->tmpRoot . '/app-views', 0777, true);

        foreach (['REQUEST_URI', 'HTTP_ACCEPT', 'CONTENT_TYPE', 'HTTP_X_REQUESTED_WITH'] as $key) {
            $this->serverBackup[$key] = $_SERVER[$key] ?? null;
            unset($_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        $fixtureDir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        if (is_dir($fixtureDir)) {
            $this->deleteDir($fixtureDir);
        }
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // wantsThemedHtml()
    // -----------------------------------------------------------------

    public function testWantsThemedHtmlTrueForPublicBrowserRequest(): void
    {
        $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
        $_SERVER['REQUEST_URI'] = '/some/missing/page';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        self::assertTrue($controller->wantsThemedHtml());
    }

    public function testWantsThemedHtmlFalseForJsonAccept(): void
    {
        $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
        $_SERVER['REQUEST_URI'] = '/some/missing/page';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        self::assertFalse($controller->wantsThemedHtml());
    }

    public function testWantsThemedHtmlFalseForJsonContentType(): void
    {
        $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        self::assertFalse($controller->wantsThemedHtml());
    }

    public function testWantsThemedHtmlFalseForAjax(): void
    {
        $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        self::assertFalse($controller->wantsThemedHtml());
    }

    public function testWantsThemedHtmlFalseForAdminAndApiPaths(): void
    {
        foreach (['/admin', '/admin/blog/edit', '/api', '/api/blog/posts'] as $path) {
            $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
            $_SERVER['REQUEST_URI'] = $path;
            self::assertFalse($controller->wantsThemedHtml(), "path {$path}");
        }

        // Assets 404s are still themed: they are neither admin nor API traffic.
        $controller = new ErrorController($this->engine(['CMS.siteName' => 'Pubvana']));
        $_SERVER['REQUEST_URI'] = '/assets/theme/default/css/style.css';
        self::assertTrue($controller->wantsThemedHtml());
    }

    // -----------------------------------------------------------------
    // The rendered page
    // -----------------------------------------------------------------

    public function testBuildErrorPageRendersThemedLayout(): void
    {
        $app = $this->engine(['CMS.siteName' => 'FromDb']);
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);

        $this->makeFixtureTheme();
        $view->setThemePath(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views');
        mkdir(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/errors', 0777, true);

        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/errors/error.tpl',
            'ERRBODY({{ status }}|{{ message }})'
        );
        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            'LAYOUT({{ content }})'
        );

        $controller = new ErrorController($app);
        $_SERVER['REQUEST_URI'] = '/some/missing/page';

        $html = $controller->buildErrorPage(404, 'Post not found');

        self::assertSame('LAYOUT(ERRBODY(404|Post not found))', $html);
    }

    public function testBuildErrorPageFallsBackToFriendlyHeadline(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $view = $app->view();
        $this->makeFixtureTheme();
        $view->setThemePath(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views');
        mkdir(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/errors', 0777, true);

        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/errors/error.tpl',
            '{{ message }}|{{ status }}'
        );
        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            'LAYOUT({{ content }})'
        );

        $controller = new ErrorController($app);

        self::assertSame('LAYOUT(Page not found|404)', $controller->buildErrorPage(404));
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * Fresh Engine mapped the way services.php wires public pages.
     *
     * @param array<string, mixed> $appValues Values set on the engine
     */
    private function engine(array $appValues = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'adext' => function (): ExtensionRegistry {
                static $registry = null;
                if ($registry === null) {
                    $registry = new ExtensionRegistry();
                }
                return $registry;
            },
            'view' => function () use ($test) {
                static $view = null;
                if ($view === null) {
                    $view = new PluginView($test->tmpRoot . '/app-views');
                }
                return $view;
            },
            'settings' => function (): object {
                return new class {
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return $default;
                    }
                };
            },
            'themes' => function (): object {
                return new class {
                    public function getActive(): ?object
                    {
                        $theme = new stdClass();
                        $theme->id = 1;
                        $theme->folder = ErrorControllerTest::FIXTURE_THEME;

                        return $theme;
                    }

                    public function getThemeOption(int $themeId, string $key, ?string $default = null): ?string
                    {
                        return $default;
                    }

                    public function getThemeOptions(int $themeId): array
                    {
                        return [];
                    }
                };
            },
            'session' => fn(): object => new class {
                public function hasFlash(string $type): bool
                {
                    return false;
                }

                public function pullFlash(string $type): mixed
                {
                    return null;
                }
            },
            'pluginLoader' => fn(): object => new class {
                public function routePrefix(string $pluginId): string
                {
                    return '/' . explode('/', $pluginId)[1];
                }
            },
        ]);

        foreach ($appValues as $key => $value) {
            $app->set($key, $value);
        }
        $app->set('flight.views.path', $this->tmpRoot . '/app-views');

        \Flight::setEngine($app);

        return $app;
    }

    private function makeFixtureTheme(): void
    {
        mkdir(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views', 0777, true);
    }

    private function deleteDir(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $filePath = (string) $file->getPathname();
            is_dir($filePath) ? rmdir($filePath) : unlink($filePath);
        }
        rmdir($path);
    }
}