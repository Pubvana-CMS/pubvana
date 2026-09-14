<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PluginView;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * PluginView's 3-tier template resolution, native/Vision mode split,
 * and the built-in Vision tags.
 *
 * Layout follows the loader convention: addPluginPath() receives the
 * plugin's Views/ root, so the plugin tier resolves 'pubvana/blog/x'
 * to {pluginViewsRoot}/x.php (the first two segments are the plugin
 * ID, not directories). The app/theme override tiers keep the fully
 * prefixed path. The default View extension is '.php'; fetch()/render()
 * switch it to '.tpl' for non-native routes.
 *
 * isNativeRender() reads $_SERVER and Flight::app(), so the global
 * engine is set and REQUEST_URI is restored after every test.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginView::class)]
final class PluginViewTest extends TestCase
{
    private string $tmpRoot;

    private ?string $requestUriBackup = null;

    /** @var Engine<object>|null Memoized per-test global engine */
    private ?Engine $engine = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-pluginview-' . uniqid();
        mkdir($this->tmpRoot . '/app-views', 0777, true);
        mkdir($this->tmpRoot . '/theme-views', 0777, true);
        mkdir($this->tmpRoot . '/plugin-views', 0777, true);
        $this->requestUriBackup = $_SERVER['REQUEST_URI'] ?? null;
        unset($_SERVER['REQUEST_URI']);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        if ($this->requestUriBackup === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->requestUriBackup;
        }
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Registration and getters
    // -----------------------------------------------------------------

    public function testPluginPathRegistrationTrimsTrailingSeparators(): void
    {
        $view = $this->view();

        $view->addPluginPath('pubvana/blog', $this->tmpRoot . '/plugin-views/');

        self::assertSame(
            $this->tmpRoot . '/plugin-views',
            $view->getPluginPath('pubvana/blog')
        );
        self::assertNull($view->getPluginPath('pubvana/unknown'));
    }

    public function testThemePathTrimsTrailingSeparatorAndAcceptsNull(): void
    {
        $view = $this->view();

        $view->setThemePath($this->tmpRoot . '/theme-views/');
        self::assertSame($this->tmpRoot . '/theme-views', $view->getThemePath());

        $view->setThemePath(null);
        self::assertNull($view->getThemePath());
    }

    public function testVisionIsLazilyBuiltAndMemoized(): void
    {
        $view = $this->view();

        $vision = $view->vision();

        self::assertInstanceOf(\Enlivenapp\Vision\Engine::class, $vision);
        self::assertSame($vision, $view->vision(), 'memoized, one instance per view');
    }

    // -----------------------------------------------------------------
    // getTemplate(): three-tier resolution
    // -----------------------------------------------------------------

    public function testGetTemplateResolvesTheAppOverrideTierFirst(): void
    {
        $view = $this->view();
        $this->writeTemplate('app-views/pubvana/blog/card.php', 'app');
        $this->writeTemplate('theme-views/pubvana/blog/card.php', 'theme');
        $this->writeTemplate('plugin-views/card.php', 'plugin');

        self::assertSame(
            $this->tmpRoot . '/app-views/pubvana/blog/card.php',
            $view->getTemplate('pubvana/blog/card')
        );
    }

    public function testGetTemplateResolvesTheThemeTierNext(): void
    {
        $view = $this->view();
        $view->setThemePath($this->tmpRoot . '/theme-views');
        $this->writeTemplate('theme-views/pubvana/blog/card.php', 'theme');
        $this->writeTemplate('plugin-views/card.php', 'plugin');

        self::assertSame(
            $this->tmpRoot . '/theme-views/pubvana/blog/card.php',
            $view->getTemplate('pubvana/blog/card')
        );
    }

    public function testGetTemplateFallsBackToThePluginDefault(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/card.php', 'plugin');

        self::assertSame(
            $this->tmpRoot . '/plugin-views/card.php',
            $view->getTemplate('pubvana/blog/card')
        );
    }

    public function testGetTemplateUsesTheActivePluginContextForUnprefixedNames(): void
    {
        $view = $this->view();
        $view->setCurrentPlugin('pubvana/blog');
        $this->writeTemplate('plugin-views/card.php', 'plugin');

        self::assertSame(
            $this->tmpRoot . '/plugin-views/card.php',
            $view->getTemplate('card')
        );
    }

    public function testGetTemplateIgnoresAnUnknownPrefixAndUnregisteredContext(): void
    {
        $view = $this->view();
        // 'pubvana/nope' is not a registered plugin: no prefix match,
        // standard Flight resolution under the app path.
        self::assertSame(
            $this->tmpRoot . '/app-views/pubvana/nope/card.php',
            $view->getTemplate('pubvana/nope/card')
        );

        // Context plugin without a registered path: standard resolution.
        $view->setCurrentPlugin('pubvana/nope');
        self::assertSame(
            $this->tmpRoot . '/app-views/card.php',
            $view->getTemplate('card')
        );
    }

    public function testGetTemplateKeepsAnExplicitExtensionAndAbsolutePaths(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/card.php', 'plugin');

        // Explicit .php is not doubled.
        self::assertSame(
            $this->tmpRoot . '/plugin-views/card.php',
            $view->getTemplate('pubvana/blog/card.php')
        );

        // Absolute paths pass through, but the extension is appended
        // BEFORE the passthrough check, so it must already carry one.
        self::assertSame('/etc/hosts.php', $view->getTemplate('/etc/hosts.php'));
    }

    public function testGetTemplateHonorsTheTplExtensionAfterFetchSwitchesModes(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/card.tpl', '{{ name }}');

        // Simulate what fetch()/render() do for public routes.
        $view->extension = '.tpl';

        self::assertSame(
            $this->tmpRoot . '/plugin-views/card.tpl',
            $view->getTemplate('pubvana/blog/card')
        );
    }

    // -----------------------------------------------------------------
    // isNativeRender()
    // -----------------------------------------------------------------

    public function testIsNativeRenderMatchesTheDefaultAdminPrefix(): void
    {
        $view = $this->view();

        $_SERVER['REQUEST_URI'] = '/admin/users';
        self::assertTrue($this->invoke($view, 'isNativeRender'));

        $_SERVER['REQUEST_URI'] = '/blog/post';
        self::assertFalse($this->invoke($view, 'isNativeRender'));
    }

    public function testIsNativeRenderStripsTheFrontControllerBase(): void
    {
        $app = $this->engine();
        $app->map('request', fn(): object => $this->requestStub('/public'));

        $view = $this->view();

        $_SERVER['REQUEST_URI'] = '/public/admin/users';
        self::assertTrue($this->invoke($view, 'isNativeRender'));

        $_SERVER['REQUEST_URI'] = '/public/blog/post';
        self::assertFalse($this->invoke($view, 'isNativeRender'));
    }

    public function testIsNativeRenderStripsTheSiteUrlPath(): void
    {
        $app = $this->engine();
        $app->map('request', fn(): object => $this->requestStub(''));
        $app->set('CMS.siteUrl', 'http://localhost/public');

        $view = $this->view();

        $_SERVER['REQUEST_URI'] = '/public/admin/users';
        self::assertTrue($this->invoke($view, 'isNativeRender'));

        $_SERVER['REQUEST_URI'] = '/public/blog/post';
        self::assertFalse($this->invoke($view, 'isNativeRender'));
    }

    public function testIsNativeRenderHonorsAddedPrefixesAndMissingUri(): void
    {
        $app = $this->engine();
        $app->map('request', fn(): object => $this->requestStub(''));

        $view = $this->view();
        $view->addNativeRenderPrefix('/api');

        $_SERVER['REQUEST_URI'] = '/api/health';
        self::assertTrue($this->invoke($view, 'isNativeRender'));

        unset($_SERVER['REQUEST_URI']);
        self::assertFalse($this->invoke($view, 'isNativeRender'));
    }

    // -----------------------------------------------------------------
    // fetch()
    // -----------------------------------------------------------------

    public function testFetchSwitchesToTplExtensionAndRoutesThroughVision(): void
    {
        $view = $this->view();
        $this->writeTemplate('plugin-views/plain.tpl', 'plain|{{ name }}');

        $content = $view->fetch('pubvana/blog/plain', ['name' => 'World']);

        // fetch() delegates to render(), which routes public names
        // through Vision; the .tpl file is never included as PHP.
        self::assertSame('plain|World', $content);
        self::assertSame('.tpl', $view->extension);
    }

    public function testFetchKeepsThePhpExtensionOnNativeRoutes(): void
    {
        $app = $this->engine();
        $app->map('request', fn(): object => $this->requestStub(''));

        $view = $this->view();
        $this->writeTemplate('plugin-views/card.php', 'Hello <?php echo $name; ?>');

        $_SERVER['REQUEST_URI'] = '/admin/anything';

        self::assertSame('Hello Admin', $view->fetch('pubvana/blog/card', ['name' => 'Admin']));
        self::assertSame('.php', $view->extension);
    }

    // -----------------------------------------------------------------
    // render()
    // -----------------------------------------------------------------

    public function testRenderRunsVisionTemplatesOnPublicRoutes(): void
    {
        $view = $this->view();
        $view->setThemePath($this->tmpRoot . '/theme-views');
        $this->writeTemplate('plugin-views/card.tpl', 'Hello {{ name }}');

        $output = $this->obRender(fn() => $view->render('pubvana/blog/card', ['name' => 'Vision']));

        self::assertSame('Hello Vision', $output);
        self::assertSame('Vision', $view->get('name'), 'preserveVars merges template data into vars');
    }

    public function testRenderUsesTheThemeAsVisionBasePath(): void
    {
        $view = $this->view();
        $view->setThemePath($this->tmpRoot . '/theme-views');
        mkdir($this->tmpRoot . '/theme-views/pubvana/blog', 0777, true);
        file_put_contents(
            $this->tmpRoot . '/theme-views/pubvana/blog/child.tpl',
            "{% extends 'pubvana/blog/base' %}{% block body %}{{ word }}{% endblock %}"
        );
        file_put_contents(
            $this->tmpRoot . '/theme-views/pubvana/blog/base.tpl',
            '<p>{% block body %}default{% endblock %}</p>'
        );

        $output = $this->obRender(fn() => $view->render('pubvana/blog/child', ['word' => 'override']));

        self::assertSame('<p>override</p>', $output, 'extends resolves through the theme basePath');
    }

    public function testRenderFallsBackToNativePhpForAdminRoutes(): void
    {
        $app = $this->engine();
        $app->map('request', fn(): object => $this->requestStub(''));

        $view = $this->view();
        $this->writeTemplate('plugin-views/card.php', 'Native <?php echo $name; ?>');

        $_SERVER['REQUEST_URI'] = '/admin/plugins';

        $output = $this->obRender(fn() => $view->render('pubvana/blog/card', ['name' => 'route']));

        self::assertSame('Native route', $output);
    }

    public function testRenderThrowsForAMissingVisionTemplate(): void
    {
        $view = $this->view();

        try {
            $view->render('pubvana/blog/missing');
            self::fail('Expected exception');
        } catch (\Exception $e) {
            self::assertStringContainsString('Template not found', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Built-in Vision tags
    // -----------------------------------------------------------------

    public function testRegionAndCaptchaTagsResolveThroughTheEngine(): void
    {
        $app = $this->engine();
        $app->map('regions', fn(): object => new class {
            public function buildRegion(string $regionId): string
            {
                return "REGION({$regionId})";
            }
        });
        $app->map('captcha', fn(): object => new class {
            public function snippetFor(string $area): string
            {
                return "CAPTCHA({$area})";
            }
        });

        $view = $this->view();
        $this->writeTemplate(
            'plugin-views/tags.tpl',
            "{% region 'footer' %}|{% captcha 'comments' %}|{% csrf_field %}"
        );

        // Function-only helper files have no composer autoload; production
        // loads csrf_helper.php through the vendor plugin's register() call
        // in the PluginLoader. Unit tests don't run plugin loading, so the
        // csrf tag renders the function_exists=false branch here.
        $output = $this->obRender(fn() => $view->render('pubvana/blog/tags'));

        self::assertStringContainsString('REGION(footer)', $output);
        self::assertStringContainsString('CAPTCHA(comments)', $output);
        self::assertSame('REGION(footer)|CAPTCHA(comments)|', $output);

        // Load the vendor helper: the csrf tag renders the input.
        require_once PROJECT_ROOT . '/vendor/enlivenapp/flight-csrf/src/Helpers/csrf_helper.php';
        $output = $this->obRender(fn() => $view->render('pubvana/blog/tags'));

        self::assertStringContainsString('name="_csrf_token"', $output);
    }

    public function testRegionAndCaptchaTagsRenderEmptyWhenServicesAreUnavailable(): void
    {
        $this->engine();
        // Neither 'regions' nor 'captcha' is mapped: the tags swallow
        // the engine error and render nothing.

        $view = $this->view();
        $this->writeTemplate('plugin-views/tags2.tpl', "{% region 'footer' %}|{% captcha 'comments' %}");

        $output = $this->obRender(fn() => $view->render('pubvana/blog/tags2'));

        self::assertSame('|', $output);
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * PluginView rooted at the app override tier, with the standard
     * blog plugin path registered and the global engine wired.
     */
    private function view(): PluginView
    {
        $this->engine();
        $view = new PluginView($this->tmpRoot . '/app-views');
        $view->addPluginPath('pubvana/blog', $this->tmpRoot . '/plugin-views');

        return $view;
    }

    /**
     * Fresh global engine, memoized for the test so service mappings
     * registered later stay on the engine isNativeRender() reads.
     *
     * @return Engine<object>
     */
    private function engine(): Engine
    {
        if ($this->engine === null) {
            $this->engine = $this->app();
            \Flight::setEngine($this->engine);
        }

        return $this->engine;
    }

    /**
     * Request stand-in exposing only what isNativeRender() reads.
     */
    private function requestStub(string $base): object
    {
        return new class($base) {
            public function __construct(public readonly string $base) {}
        };
    }

    /**
     * Run a render callable under output buffering and return the output.
     */
    private function obRender(callable $render): string
    {
        ob_start();
        $render();

        return (string) ob_get_clean();
    }

    /**
     * Write a template file under the tmp root.
     */
    private function writeTemplate(string $relative, string $body): void
    {
        $dir = $this->tmpRoot . '/' . dirname($relative);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->tmpRoot . '/' . $relative, $body);
    }

    /**
     * Delete a directory tree.
     */
    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $path = (string) $file->getPathname();
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
