<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Public\PublicController;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginView;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

/**
 * PublicController: the layout assembly surface plus the full render
 * pipeline through a fixture theme.
 *
 * Method-level tests use an anonymous subclass and invoke() the
 * protected methods against a hand-mapped Engine (the pattern the
 * existing PublicControllerFlashTest established). The pipeline tests
 * run render() end to end through a real PluginView, a fixture theme
 * under themes/{fixture} (cleaned up), and Vision templates.
 *
 * @package Pubvana\Tests\Unit
 */
#[CoversClass(PublicController::class)]
final class PublicControllerTest extends TestCase
{
    private string $tmpRoot;

    public const FIXTURE_THEME = "_fxpublic";

    /** @var string|null REQUEST_URI backup for restoration */
    private ?string $uriBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-public-' . uniqid();
        mkdir($this->tmpRoot . '/app-views/pubvana/test', 0777, true);
        mkdir($this->tmpRoot . '/plugin-views/pubvana/test', 0777, true);
        $this->uriBackup = $_SERVER['REQUEST_URI'] ?? null;
        unset($_SERVER['REQUEST_URI']);
        $GLOBALS['public_controller_calls'] = [];
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        $fixtureDir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        if (is_dir($fixtureDir)) {
            $this->deleteDir($fixtureDir);
        }
        if ($this->uriBackup === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->uriBackup;
        }
        unset($GLOBALS['public_controller_calls']);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Plugin id and route prefix
    // -----------------------------------------------------------------

    public function testGetPluginIdAndRoutePrepend(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);

        $core = new class($app) extends PublicController {};
        self::assertSame('', $this->invoke($core, 'getPluginId'));
        self::assertSame('', $this->invoke($core, 'getRoutePrepend'));

        $plugin = new class($app, 'pubvana.blog') extends PublicController {};
        self::assertSame('pubvana/blog', $this->invoke($plugin, 'getPluginId'));
        self::assertSame('blog', $this->invoke($plugin, 'getRoutePrepend'));
    }

    public function testGetRoutePrependSurvivesAThrowingLoader(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $app->map('pluginLoader', fn(): object => new class {
            public function routePrefix(string $pluginId): never
            {
                throw new \RuntimeException('not loaded');
            }
        });

        $controller = new class($app, 'pubvana.blog') extends PublicController {};

        self::assertSame('', $this->invoke($controller, 'getRoutePrepend'));
    }

    // -----------------------------------------------------------------
    // sidebarKind()
    // -----------------------------------------------------------------

    public function testSidebarKindVariants(): void
    {
        $cases = [
            // [page_sidebar, blog_layout, is_homepage, expected]
            ['not_home', 'sidebar-right', false, 'sidebar-right'],
            ['not_home', 'sidebar-left', false, 'sidebar-left'],
            ['not_home', 'sidebar-right', true, ''],
            ['home', 'sidebar-right', true, 'sidebar-right'],
            ['home', 'sidebar-left', true, 'sidebar-left'],
            ['home', 'sidebar-right', false, ''],
            ['none', 'sidebar-left', false, ''],
            ['none', 'sidebar-left', true, ''],
        ];

        foreach ($cases as $case) {
            [$setting, $layout, $isHomepage, $expected] = $case;
            $app = $this->engine(
                ['CMS.siteName' => 'Pubvana'],
                pageSidebar: $setting,
                blogLayout: $layout
            );
            $controller = new class($app) extends PublicController {};

            self::assertSame(
                $expected,
                $this->invoke($controller, 'sidebarKind', [['is_homepage' => $isHomepage]]),
                "page_sidebar={$setting}, blog_layout={$layout}, is_homepage=" . var_export($isHomepage, true)
            );
        }

        // No active theme: defaults (not_home + sidebar-right).
        $app = $this->engine(['CMS.siteName' => 'Pubvana'], noActiveTheme: true);
        $controller = new class($app) extends PublicController {};
        self::assertSame('sidebar-right', $this->invoke($controller, 'sidebarKind', [['is_homepage' => false]]));
    }

    // -----------------------------------------------------------------
    // buildBreadcrumbs()
    // -----------------------------------------------------------------

    public function testBreadcrumbs(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        // Provided crumbs pass through untouched.
        $provided = [['label' => 'Home', 'url' => '/'], ['label' => 'Product', 'url' => null]];
        self::assertSame(
            $provided,
            $this->invoke($controller, 'buildBreadcrumbs', [['breadcrumbs' => $provided]])
        );

        // Homepage: no crumbs.
        $_SERVER['REQUEST_URI'] = '/';
        self::assertSame([], $this->invoke($controller, 'buildBreadcrumbs'));

        // Segments: dashes and underscores become spaces; the last segment
        // carries the page title and no URL.
        $_SERVER['REQUEST_URI'] = '/store/my-item/detail';
        $crumbs = $this->invoke($controller, 'buildBreadcrumbs', [['title' => 'Detail page']]);

        self::assertSame('Home', $crumbs[0]['label']);
        self::assertSame('/', $crumbs[0]['url']);
        self::assertSame('Store', $crumbs[1]['label']);
        self::assertSame('/store', $crumbs[1]['url']);
        self::assertSame('My Item', $crumbs[2]['label']);
        self::assertSame('/store/my-item', $crumbs[2]['url']);
        self::assertSame('Detail page', $crumbs[3]['label']);
        self::assertNull($crumbs[3]['url']);

        // archive_title also wins the last segment (/tag/food: two segments,
        // so the title lands on crumbs[2]).
        $_SERVER['REQUEST_URI'] = '/tag/food';
        $crumbs = $this->invoke($controller, 'buildBreadcrumbs', [['archive_title' => 'Food posts']]);
        self::assertSame('Tag', $crumbs[1]['label']);
        self::assertSame('/tag', $crumbs[1]['url']);
        self::assertSame('Food posts', $crumbs[2]['label']);
        self::assertNull($crumbs[2]['url']);
    }

    // -----------------------------------------------------------------
    // getThemeOptions()
    // -----------------------------------------------------------------

    public function testThemeOptionsDotNotationExpansion(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana'], themeOptions: [
            'hero.show'      => '1',
            'hero.text'      => 'Hello',
            'standalone'     => 'value',
            'deep.group.key' => 'flat',
        ]);
        $controller = new class($app) extends PublicController {};

        $options = $this->invoke($controller, 'getThemeOptions');

        self::assertSame('1', $options['hero']['show']);
        self::assertSame('Hello', $options['hero']['text']);
        self::assertSame('value', $options['standalone']);
        self::assertSame('flat', $options['deep.group.key'], 'keys with more than one dot stay flat');
        self::assertArrayNotHasKey('deep', $options);

        // No active theme: nothing.
        $app = $this->engine(['CMS.siteName' => 'Pubvana'], noActiveTheme: true);
        $controller = new class($app) extends PublicController {};
        self::assertSame([], $this->invoke($controller, 'getThemeOptions'));
    }

    // -----------------------------------------------------------------
    // buildCommentsHtml()
    // -----------------------------------------------------------------

    public function testCommentsHtmlGuards(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        // No hint: nothing.
        self::assertSame('', $this->invoke($controller, 'buildCommentsHtml'));

        // Hint but no comments service mapped: nothing.
        self::assertSame(
            '',
            $this->invoke($controller, 'buildCommentsHtml', [['commentable' => ['type' => 'blog', 'id' => 5]]])
        );

        // Service mapped but disabled: nothing.
        $app->map('comments', fn(): object => new class {
            public function isEnabled(): bool
            {
                return false;
            }
        });
        self::assertSame(
            '',
            $this->invoke($controller, 'buildCommentsHtml', [['commentable' => ['type' => 'blog', 'id' => 5]]])
        );

        // Enabled: render() receives type, id, and the allow flag; route
        // data beats the hint.
        $GLOBALS['public_controller_calls'] = [];
        $app->map('comments', fn(): object => new class {
            public function isEnabled(): bool
            {
                return true;
            }
            public function render(string $type, int $id, bool $allow): string
            {
                $GLOBALS['public_controller_calls'][] = ['type' => $type, 'id' => $id, 'allow' => $allow];

                return 'THREAD-HTML';
            }
        });

        $routeData = [
            'commentable'    => ['type' => 'blog', 'id' => '5', 'allow_comments' => false],
            'allow_comments' => true,
        ];
        self::assertSame('THREAD-HTML', $this->invoke($controller, 'buildCommentsHtml', [$routeData]));
        self::assertSame([['type' => 'blog', 'id' => 5, 'allow' => true]], $GLOBALS['public_controller_calls']);

        // The hint's own flag applies when the route data has none.
        $GLOBALS['public_controller_calls'] = [];
        self::assertSame(
            'THREAD-HTML',
            $this->invoke($controller, 'buildCommentsHtml', [['commentable' => ['type' => 'page', 'id' => 2, 'allow_comments' => false]]])
        );
        self::assertSame([['type' => 'page', 'id' => 2, 'allow' => false]], $GLOBALS['public_controller_calls']);

        // A throwing renderer degrades to empty.
        $app->map('comments', fn(): object => new class {
            public function isEnabled(): bool
            {
                return true;
            }
            public function render(string $type, int $id, bool $allow): string
            {
                throw new \RuntimeException('boom');
            }
        });
        self::assertSame(
            '',
            $this->invoke($controller, 'buildCommentsHtml', [['commentable' => ['type' => 'blog', 'id' => 5]]])
        );
    }

    // -----------------------------------------------------------------
    // Navigation, identity, settings
    // -----------------------------------------------------------------

    public function testNavigationDegradesToEmptyTree(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        $item = new stdClass();
        $app->map('navigation', fn(): object => new class($item) {
            public function __construct(private stdClass $item) {}
            public function getTree(string $group): array
            {
                return $group === 'footer' ? [] : [$this->item];
            }
        });

        self::assertSame([$item], $this->invoke($controller, 'getNavigation', ['primary']));
        self::assertSame([], $this->invoke($controller, 'getNavigation', ['footer']));

        // A throwing navigation service degrades to [].
        $app->map('navigation', fn(): object => new class {
            public function getTree(string $group): never
            {
                throw new \RuntimeException('no table');
            }
        });
        self::assertSame([], $this->invoke($controller, 'getNavigation'));
    }

    public function testSiteNameFallbackChain(): void
    {
        // Nothing stored anywhere: the hardcoded name.
        $controller = new class($this->engine([])) extends PublicController {};
        self::assertSame('Pubvana', $this->invoke($controller, 'getSiteName'));

        // An app value answers.
        $controller = new class($this->engine(['CMS.siteName' => 'FromConfig'])) extends PublicController {};
        self::assertSame('FromConfig', $this->invoke($controller, 'getSiteName'));

        // A stored row beats the app value.
        $controller = new class($this->engine(['CMS.siteName' => 'FromConfig'], settingsDb: ['CMS.siteName' => 'FromDb'])) extends PublicController {};
        self::assertSame('FromDb', $this->invoke($controller, 'getSiteName'));

        // A stored NULL row falls through to the app value.
        $controller = new class($this->engine(['CMS.siteName' => 'FromConfig'], settingsDb: ['CMS.siteName' => null])) extends PublicController {};
        self::assertSame('FromConfig', $this->invoke($controller, 'getSiteName'));
    }

    public function testGetActiveThemeName(): void
    {
        $controller = new class($this->engine(['CMS.siteName' => 'Pubvana'])) extends PublicController {};
        self::assertSame(self::FIXTURE_THEME, $this->invoke($controller, 'getActiveThemeName'));

        $controller = new class($this->engine(['CMS.siteName' => 'Pubvana'], noActiveTheme: true)) extends PublicController {};
        self::assertSame('default', $this->invoke($controller, 'getActiveThemeName'));
    }

    public function testGetSettingDelegatesToTheStore(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana'], settingsDb: ['CMS.copyright' => '(c) me']);
        $controller = new class($app) extends PublicController {};

        self::assertSame('(c) me', $this->invoke($controller, 'getSetting', ['CMS.copyright']));
        self::assertSame('fallback', $this->invoke($controller, 'getSetting', ['CMS.missing', 'fallback']));
    }

    // -----------------------------------------------------------------
    // Plugin-contributed assets
    // -----------------------------------------------------------------

    public function testPublicCssJsAndHeadCollectFromAdext(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $registry = $app->adext();
        $registry->register('public.css', 'default', 'pubvana.one', ['url' => '/assets/plugin/One/css/a.css']);
        $registry->register('public.css', 'default', 'pubvana.two', ['url' => '/assets/theme/two.css']);
        $registry->register('public.js', 'default', 'pubvana.script', ['url' => '/assets/plugin/One/js/x.js']);
        $registry->register('public.head', 'other', 'pubvana.meta', ['output' => '<meta og-tag>']);

        $controller = new class($app) extends PublicController {};

        self::assertSame(
            ['/assets/plugin/One/css/a.css', '/assets/theme/two.css'],
            $this->invoke($controller, 'getPublicCss')
        );
        self::assertSame(['/assets/plugin/One/js/x.js'], $this->invoke($controller, 'getPublicJs'));
        self::assertSame("<meta og-tag>\n", $this->invoke($controller, 'getPublicHead'));
        self::assertSame(
            "<script src=\"/assets/plugin/One/js/x.js\"></script>\n",
            $this->invoke($controller, 'buildFooterScripts')
        );

        // Escaping: a hostile URL cannot inject markup.
        $registry->register('public.js', 'default', 'pubvana.hostile', ['url' => '/x.js" onerror="pwn']);
        $footer = $this->invoke($controller, 'buildFooterScripts');
        self::assertStringContainsString('<script src="/x.js&quot; onerror=&quot;pwn"></script>', $footer);
    }

    public function testBuildHeadHtmlEscapesAndConcatenates(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        $html = $this->invoke($controller, 'buildHeadHtml', [[
            'title' => 'My & "Page"',
            'meta'  => [['name' => 'a<b>', 'content' => 'c"d']],
            'og'    => [['property' => 'og:t', 'content' => 'v"v']],
            'seo'   => '<structured-data>',
            'head'  => '<meta extra>',
            'css'   => ['/a.css', '/b"css'],
        ]]);

        self::assertStringContainsString('<title>My &amp; &quot;Page&quot;</title>', $html);
        self::assertStringContainsString('<meta name="a&lt;b&gt;" content="c&quot;d">', $html);
        self::assertStringContainsString('<meta property="og:t" content="v&quot;v">', $html);
        self::assertStringContainsString('<structured-data>', $html);
        self::assertStringContainsString('<meta extra>', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="/a.css">', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="/b&quot;css">', $html);
    }

    public function testSwapPluginCssTiers(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);

        $controller = new class($app) extends PublicController {};

        // Not a plugin asset URL: unchanged.
        self::assertSame(
            '/assets/theme/plain.css',
            $this->invoke($controller, 'swapPluginCss', ['/assets/theme/plain.css'])
        );

        // Path traversal guard: unchanged.
        self::assertSame(
            '/assets/plugin/One/css/../../secret.css',
            $this->invoke($controller, 'swapPluginCss', ['/assets/plugin/One/css/../../secret.css'])
        );

        // No theme path: unchanged.
        self::assertSame(
            '/assets/plugin/Blog/css/x.css',
            $this->invoke($controller, 'swapPluginCss', ['/assets/plugin/Blog/css/x.css'])
        );

        // Theme ships the replacement: swap to the theme asset URL.
        $themeDir = $this->tmpRoot . '/themes/' . self::FIXTURE_THEME;
        mkdir($themeDir . '/Views', 0777, true);
        mkdir($themeDir . '/assets/Blog/css', 0777, true);
        file_put_contents($themeDir . '/assets/Blog/css/x.css', 'body{}');
        $view->setThemePath($themeDir . '/Views');

        self::assertSame(
            '/assets/theme/' . self::FIXTURE_THEME . '/Blog/css/x.css',
            $this->invoke($controller, 'swapPluginCss', ['/assets/plugin/Blog/css/x.css'])
        );

        // Theme exists but has no replacement file: unchanged.
        self::assertSame(
            '/assets/plugin/Blog/css/missing.css',
            $this->invoke($controller, 'swapPluginCss', ['/assets/plugin/Blog/css/missing.css'])
        );
    }

    // -----------------------------------------------------------------
    // Template resolution
    // -----------------------------------------------------------------

    public function testResolveTemplateThreeTierChain(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);
        $controller = new class($app, 'pubvana.test') extends PublicController {};

        // 1. App override tier.
        file_put_contents($this->tmpRoot . '/app-views/pubvana/test/post.tpl', 'app');
        self::assertSame(
            $this->tmpRoot . '/app-views/pubvana/test/post.tpl',
            $this->invoke($controller, 'resolveTemplate', ['pubvana/test/post', $view])
        );

        // 2. Theme override tier (fixture theme under the real themes dir).
        $this->makeFixtureTheme();
        $themePost = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/pubvana/test/post.tpl';
        @mkdir(dirname($themeDir = dirname($themePost)), 0777, true);
        mkdir(dirname($themePost), 0777, true);
        file_put_contents($themePost, 'theme');
        unlink($this->tmpRoot . '/app-views/pubvana/test/post.tpl');
        self::assertSame(
            $themePost,
            $this->invoke($controller, 'resolveTemplate', ['pubvana/test/post', $view])
        );

        // 3. Plugin fallback tier (plugin path registered on the view).
        unlink($themePost);
        file_put_contents($this->tmpRoot . '/plugin-views/pubvana/test/post.tpl', 'plugin');
        self::assertSame(
            $this->tmpRoot . '/plugin-views/pubvana/test/post.tpl',
            $this->invoke($controller, 'resolveTemplate', ['pubvana/test/post', $view])
        );

        // Nothing exists: the theme candidate is returned so the missing
        // template error is reported against the theme.
        unlink($this->tmpRoot . '/plugin-views/pubvana/test/post.tpl');
        self::assertSame(
            $themePost,
            $this->invoke($controller, 'resolveTemplate', ['pubvana/test/post', $view])
        );
    }

    public function testResolveLayoutPrefersTheActiveThemeAndFallsBackToDefault(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $view = $app->view();
        $controller = new class($app) extends PublicController {};

        $this->makeFixtureTheme();
        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            'theme'
        );

        self::assertSame(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            $this->invoke($controller, 'resolveLayout', [$view])
        );

        // No layout in the fixture theme: the default theme's layout answers.
        unlink(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl');
        self::assertSame(
            PROJECT_ROOT . '/themes/default/Views/layout.tpl',
            $this->invoke($controller, 'resolveLayout', [$view])
        );
        self::assertFileExists(PROJECT_ROOT . '/themes/default/Views/layout.tpl');
    }

    public function testResolveErrorTemplatePrefersThemeThenAppFallback(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        $this->makeFixtureTheme();
        $themeError = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/errors/error.tpl';
        mkdir(dirname($themeError), 0777, true);
        file_put_contents($themeError, 'theme');

        self::assertSame($themeError, $this->invoke($controller, 'resolveErrorTemplate'));

        // No theme file: the shipped app/Views copy answers.
        unlink($themeError);
        self::assertSame(
            PROJECT_ROOT . '/app/Views/errors/error.tpl',
            $this->invoke($controller, 'resolveErrorTemplate')
        );
        self::assertFileExists(PROJECT_ROOT . '/app/Views/errors/error.tpl');
    }

    public function testFriendlyMessageDefaults(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $controller = new class($app) extends PublicController {};

        self::assertSame('Unauthorized', $this->invoke($controller, 'friendlyMessage', [401]));
        self::assertSame('Forbidden', $this->invoke($controller, 'friendlyMessage', [403]));
        self::assertSame('Page not found', $this->invoke($controller, 'friendlyMessage', [404]));
        self::assertSame('Method not allowed', $this->invoke($controller, 'friendlyMessage', [405]));
        self::assertSame('Server error', $this->invoke($controller, 'friendlyMessage', [500]));
        self::assertSame('Service unavailable', $this->invoke($controller, 'friendlyMessage', [503]));
        self::assertSame('Request failed', $this->invoke($controller, 'friendlyMessage', [999]));
    }

    // -----------------------------------------------------------------
    // The full render pipeline
    // -----------------------------------------------------------------

    public function testRenderPipelineAssemblesLayoutThroughVision(): void
    {
        $app = $this->engine(
            ['CMS.siteName' => 'FromDb'],
            themeOptions: ['layout.page_sidebar' => 'not_home', 'layout.blog_layout' => 'sidebar-left']
        );
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);

        $this->makeFixtureTheme();
        $view->setThemePath(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views');

        // Content template (plugin tier) and layout (theme tier).
        file_put_contents($this->tmpRoot . '/plugin-views/pubvana/test/post.tpl', 'CONTENT[{{ title }}|{{ content }}]');
        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            'LAYOUT({{ content }}){! comments_html !}|{{ site.name }}|{{ sidebar_kind }}|{% for crumb in breadcrumbs %}{{ crumb.label }}{% endfor %}'
        );

        $app->map('comments', fn(): object => new class {
            public function isEnabled(): bool
            {
                return true;
            }
            public function render(string $type, int $id, bool $allow): string
            {
                return 'THREAD(' . $type . ':' . $id . ')';
            }
        });

        $output = $this->publishRender($app, 'pubvana/test/post', [
            'title'       => 'Post title',
            'content'     => 'raw content',
            'is_homepage' => false,
            'breadcrumbs' => [['label' => 'Home', 'url' => '/'], ['label' => 'Deep', 'url' => null]],
            'commentable' => ['type' => 'blog', 'id' => 9],
        ]);

        self::assertStringContainsString('CONTENT[Post title|RENDERED(raw content)]', $output, 'route data reaches the content template, transformed centrally');
        self::assertStringContainsString('THREAD(blog:9)', $output, 'comments render into the layout');
        self::assertStringContainsString('FromDb', $output, 'site identity flows through');
        self::assertStringContainsString('sidebar-left', $output, 'theme options drive the sidebar');
        self::assertStringContainsString('Home', $output, 'provided breadcrumbs land in the layout');
        self::assertStringContainsString('Deep', $output, 'provided crumbs flow unfiltered');
        self::assertStringContainsString('RENDERED(raw content)', $output, 'content.render transformers ran centrally');
    }

    public function testRenderErrorPageAssemblesLayoutThroughVision(): void
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
            'ERRORLAYOUT({{ content }})|{{ site.name }}'
        );

        // No message: the friendly status name fills the body.
        $output = $this->publishErrorPage($app, 404);
        self::assertStringContainsString('ERRORLAYOUT(ERRBODY(404|Page not found))|FromDb', $output);

        // A halt message overrides the friendly headline in body and title.
        $output = $this->publishErrorPage($app, 404, 'Post not found');
        self::assertStringContainsString('ERRORLAYOUT(ERRBODY(404|Post not found))|FromDb', $output);
    }

    public function testRenderUsesSeoContextWhenThePluginIsEnabled(): void
    {
        $app = $this->engine(['CMS.siteName' => 'My Site']);
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);

        $this->makeFixtureTheme();
        $view->setThemePath(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views');

        file_put_contents($this->tmpRoot . '/plugin-views/pubvana/test/plain.tpl', 'BODY');
        file_put_contents(
            PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views/layout.tpl',
            'HEAD{! header !}|{{ content }}|{{ title }}|{! scripts_footer !}'
        );

        $app->adext()->register('public.js', 'default', 'pubvana.script', ['url' => '/assets/plugin/One/js/x.js']);

        // The SEO plugin is enabled: it owns the head when a context exists.
        $app->map('seo', fn(): object => new class {
            private array $context = [];

            public function setContext(array $context): void
            {
                $this->context = $context;
            }

            public function detectContent(): void
            {
                $this->context = [];
            }

            public function getContext(): array
            {
                return $this->context;
            }

            public function buildTitle(): string
            {
                return (string) ($this->context['title'] ?? '');
            }

            public function renderHead(): string
            {
                $html = '';
                foreach ($this->context['meta'] ?? [] as $meta) {
                    $html .= '<meta name="' . $meta['name'] . '" content="' . $meta['content'] . '">';
                }
                return $html . '<structured-data>';
            }
        });

        $output = $this->publishRender($app, 'pubvana/test/plain', [
            'title'       => 'Special title',
            'seo_context' => ['title' => 'SEO title', 'meta' => [['name' => 'x', 'content' => 'y']], 'og' => []],
        ]);

        self::assertStringContainsString('<title>SEO title</title>', $output, 'SEO owns the head when enabled');
        self::assertStringContainsString('<meta name="x" content="y">', $output);
        self::assertStringContainsString('Special title', $output, 'the page title still reaches the template');
        self::assertStringContainsString('BODY', $output, 'content flows');
        self::assertStringContainsString('<script src="/assets/plugin/One/js/x.js"></script>', $output, 'footer scripts assemble into the layout');
    }

    public function testRenderFallsBackToNativeRenderingWithoutPluginView(): void
    {
        $app = $this->engine(['CMS.siteName' => 'Pubvana']);
        $app->map('view', fn(): object => new stdClass());
        $app->map('render', function (): void {
            $args = func_get_args();
            $GLOBALS['public_controller_calls'][] = [$args[0], $args[1] ?? null];
        });

        $this->publishRender($app, 'legacy/page', ['title' => 'Legacy']);

        self::assertCount(1, $GLOBALS['public_controller_calls']);
        [$template, $data] = $GLOBALS['public_controller_calls'][0];
        self::assertSame('legacy/page', $template);
        // buildHeadHtml() has already assembled header into a string on the
        // native path: the title fallback lives inside the <title> tag.
        self::assertStringContainsString('<title>Legacy - Pubvana</title>', $data['header']);
        self::assertSame('', $data['comments_html']);
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * Run the protected render() through an anonymous concrete subclass,
     * capturing the echoed output (throw-safe).
     */
    private function publishRender(Engine $app, string $template, array $data): string
    {
        $controller = new class($app, 'pubvana.test') extends PublicController {
            public function publish(string $tpl, array $data): string
            {
                ob_start();
                try {
                    $this->render($tpl, $data);

                    return (string) ob_get_clean();
                } catch (\Throwable $e) {
                    ob_end_clean();
                    throw $e;
                }
            }
        };

        return $controller->publish($template, $data);
    }

    /**
     * Run the protected renderErrorPage() through an anonymous concrete
     * subclass, capturing the echoed output (throw-safe).
     */
    private function publishErrorPage(Engine $app, int $status, string $message = ''): string
    {
        $controller = new class($app, 'pubvana.test') extends PublicController {
            public function publishError(int $status, string $message): string
            {
                ob_start();
                try {
                    $this->renderErrorPage($status, $message);

                    return (string) ob_get_clean();
                } catch (\Throwable $e) {
                    ob_end_clean();
                    throw $e;
                }
            }
        };

        return $controller->publishError($status, $message);
    }

    /**
     * Fresh Engine mapped the way services.php wires public pages.
     *
     * @param array<string, mixed>       $appValues  Values set on the engine (CMS.siteName etc.)
     * @param string|null                $pageSidebar layout.page_sidebar option
     * @param string|null                $blogLayout layout.blog_layout option
     * @param bool                       $noActiveTheme getActive() answers null
     * @param array<string, string|null> $settingsDb Settings-store rows
     * @param array<string, string>      $themeOptions Theme option rows
     */
    private function engine(
        array $appValues = [],
        ?string $pageSidebar = null,
        ?string $blogLayout = null,
        bool $noActiveTheme = false,
        array $settingsDb = [],
        array $themeOptions = []
    ): Engine {
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
                    $view->addPluginPath('pubvana/test', $test->tmpRoot . '/plugin-views');
                }
                return $view;
            },
            // Fake settings store: rows beat app values.
            'settings' => function () use ($settingsDb) {
                return new class($settingsDb) {
                    /** @param array<string, string|null> $rows */
                    public function __construct(private array $rows) {}
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return $this->rows[$key] ?? $default;
                    }
                    public function has(string $key): bool
                    {
                        return array_key_exists($key, $this->rows);
                    }
                };
            },
            'themes' => function () use ($noActiveTheme, $pageSidebar, $blogLayout, $themeOptions) {
                return new class($noActiveTheme, $pageSidebar, $blogLayout, $themeOptions) {
                    public function __construct(
                        private bool $noActive,
                        private ?string $pageSidebar,
                        private ?string $blogLayout,
                        private array $themeOptions
                    ) {}

                    public function getActive(): ?object
                    {
                        if ($this->noActive) {
                            return null;
                        }
                        $theme = new stdClass();
                        $theme->id = 1;
                        $theme->folder = PublicControllerTest::FIXTURE_THEME;

                        return $theme;
                    }

                    public function getThemeOption(int $themeId, string $key, ?string $default = null): ?string
                    {
                        return $this->themeOptions[$key] ?? match ($key) {
                            'layout.page_sidebar' => $this->pageSidebar,
                            'layout.blog_layout'  => $this->blogLayout,
                            default               => $default,
                        };
                    }

                    public function getThemeOptions(int $themeId): array
                    {
                        return $this->themeOptions;
                    }
                };
            },
            // Content transformer: wraps whatever it is handed.
            'content' => fn(): object => new class {
                public function render(string $content): string
                {
                    return 'RENDERED(' . $content . ')';
                }
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
        // services.php keys the app override tier off this engine value.
        $app->set('flight.views.path', $this->tmpRoot . '/app-views');

        \Flight::setEngine($app);

        return $app;
    }

    /**
     * Create the fixture theme directory under the real themes tree
     * (getActiveThemeName/resolveLayout read PROJECT_ROOT paths).
     */
    private function makeFixtureTheme(): void
    {
        mkdir(PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME . '/Views', 0777, true);
    }

    /**
     * Delete a file or directory tree.
     */
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
