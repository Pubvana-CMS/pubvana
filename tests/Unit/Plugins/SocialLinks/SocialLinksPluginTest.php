<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SocialLinks;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\SocialLinks\Plugin;
use Pubvana\Plugins\SocialLinks\Services\SocialLinksService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SocialLinks Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class SocialLinksPluginTest extends TestCase
{
    public function testRegisterWiresServiceRoutesBlockAndCss(): void
    {
        $pdo = Sqlite::recreate();
        SocialLinksSchema::create($pdo);
        $app = new Engine();
        $app->init();
        $app->map('adext', static function () use ($app): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $app->map('db', fn(): \PDO => $pdo);
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        self::assertInstanceOf(SocialLinksService::class, $app->socialLinks());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /social-links', $handlers);
        self::assertContains('admin POST /social-links/store', $handlers);
        self::assertContains('admin POST /social-links/@id/toggle', $handlers);
        self::assertContains('admin POST /social-links/@id/delete', $handlers);
        self::assertContains('admin POST /social-links/@id/reorder', $handlers);

        $blocks = $adext->get('block', 'available');
        self::assertArrayHasKey('pubvana.social-links', $blocks);
        self::assertSame('Social Links', $blocks['pubvana.social-links']['label']);

        // Block provider resolves through the service.
        $app->socialLinks()->create(['platform' => 'github', 'url' => 'https://a.test']);
        $provided = $blocks['pubvana.social-links']['provider']([]);
        self::assertSame('Follow Us', $provided['title']);
        self::assertCount(1, $provided['links']);

        $css = $adext->get('public.css', 'default');
        self::assertArrayHasKey('pubvana.social-links.fontawesome', $css);
        self::assertArrayHasKey('pubvana.social-links.brands', $css);
        self::assertArrayHasKey('pubvana.social-links.solid', $css);
        self::assertArrayHasKey('pubvana.social-links.block', $css);

        $adminCss = $adext->get('admin.css', 'default');
        self::assertArrayHasKey('pubvana.social-links.fontawesome', $adminCss);
        self::assertArrayHasKey('pubvana.social-links.brands', $adminCss);
        self::assertArrayHasKey('pubvana.social-links.solid', $adminCss);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/SocialLinks/Config/Config.php';

        self::assertSame('_blank', $config['default_target']);
        self::assertSame('noopener noreferrer', $config['link_rel']);
        self::assertSame('Website', $config['fallback_label']);
        self::assertSame('fa-solid fa-link', $config['fallback_icon']);
        self::assertSame('Follow Us', $config['block_title']);
    }
}
