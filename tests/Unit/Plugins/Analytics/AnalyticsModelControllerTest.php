<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Analytics;

use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Analytics\Controllers\AnalyticsAdminController;
use Pubvana\Plugins\Analytics\Models\PageView;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PageView model plus AnalyticsAdminController private helpers.
 */
#[CoversClass(PageView::class)]
#[CoversClass(AnalyticsAdminController::class)]
final class AnalyticsModelControllerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->pdo->exec(
            'CREATE TABLE analytics_page_views (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                page_path       TEXT NOT NULL,
                page_group      TEXT NOT NULL,
                referrer_domain TEXT,
                viewed_at       TEXT NOT NULL
            )'
        );
    }

    public function testFindById(): void
    {
        self::assertNull((new PageView($this->pdo))->findById(99999));

        $view = new PageView($this->pdo);
        $view->page_path = '/blog/hi';
        $view->page_group = 'blog';
        $view->referrer_domain = null;
        $view->viewed_at = '2026-01-01 00:00:00';
        $view->insert();

        $found = (new PageView($this->pdo))->findById((int) $view->id);
        self::assertNotNull($found);
        self::assertSame('/blog/hi', (string) $found->page_path);
    }

    public function testAdminBase(): void
    {
        $app = $this->app([
            'pluginLoader' => static fn (): object => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/analytics' ? '/analytics' : '/other';
                }
            },
        ]);

        $controller = new AnalyticsAdminControllerHarness($app);

        self::assertSame('/admin/analytics', $this->invoke($controller, 'adminBase'));
    }

    public function testValidRange(): void
    {
        $controller = new AnalyticsAdminControllerHarness($this->controllerApp('30'));

        self::assertSame('7', $this->invoke($controller, 'validRange', ['7']));
        self::assertSame('all', $this->invoke($controller, 'validRange', ['all']));
        self::assertSame('30', $this->invoke($controller, 'validRange', ['bogus']));
        self::assertSame('30', $this->invoke($controller, 'validRange', [null]));
    }

    public function testIndexRendersReport(): void
    {
        $controller = new AnalyticsAdminControllerHarness($this->controllerApp('7', true, ['range' => '7']));

        $controller->index();

        self::assertSame('pubvana/analytics/admin/index', $controller->renderedView);
        self::assertSame('7', $controller->renderedData['range']);
        self::assertSame('Analytics', $controller->renderedData['pageTitle']);
        self::assertTrue($controller->renderedData['trackingEnabled']);
        self::assertSame('/admin/analytics', $controller->renderedData['adminBase']);
    }

    public function testDataOutputsJson(): void
    {
        $controller = new AnalyticsAdminControllerHarness($this->controllerApp('all', false, ['range' => 'x']));

        $controller->data();

        self::assertSame(['range' => '30'], $this->jsonPayload);
    }

    public function testToggleTracking(): void
    {
        $controller = new AnalyticsAdminControllerHarness($this->controllerApp('30', false, [], ['tracking_enabled' => '1']));

        $controller->toggleTracking();

        self::assertSame(['Analytics.tracking_enabled' => true], $this->savedSettings);
        self::assertSame(['success' => 'Page tracking enabled.'], $this->flashes);
        self::assertSame('/admin/analytics', $this->redirectTarget);

        $this->savedSettings = [];
        $this->flashes = [];
        $this->redirectTarget = null;
        $controller = new AnalyticsAdminControllerHarness($this->controllerApp('30', false, [], []));

        $controller->toggleTracking();

        self::assertSame(['Analytics.tracking_enabled' => false], $this->savedSettings);
        self::assertSame(['success' => 'Page tracking disabled.'], $this->flashes);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $data
     */
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $data
     * @return \flight\Engine<object>
     */
    private function controllerApp(
        string $range,
        bool $tracking = false,
        array $query = [],
        array $data = []
    ): \flight\Engine {
        $test = $this;
        $app = $this->app([
            'request' => static fn (): object => new class ($query, $data) {
                public Collection $query;
                public Collection $data;

                /**
                 * @param array<string, mixed> $query
                 * @param array<string, mixed> $data
                 */
                public function __construct(array $query, array $data)
                {
                    $this->query = new Collection($query);
                    $this->data = new Collection($data);
                }
            },
            'analytics' => static fn (): object => new class ($tracking) {
                public function __construct(private bool $tracking)
                {
                }

                public function normalizeRange(string $raw): string
                {
                    $raw = strtolower(trim($raw));
                    if ($raw === 'all') {
                        return 'all';
                    }
                    return in_array((int) $raw, [7, 30, 90, 180, 365], true) ? (string) (int) $raw : '30';
                }

                /** @return array<string, mixed> */
                public function dashboard(string $range): array
                {
                    return ['range' => $range];
                }

                public function isTrackingEnabled(): bool
                {
                    return $this->tracking;
                }
            },
            'settings' => static fn (): object => new class ($test) {
                public function __construct(private AnalyticsModelControllerTest $test)
                {
                }

                public function set(string $key, mixed $value): void
                {
                    $this->test->savedSettings[$key] = $value;
                }
            },
            'session' => static fn (): object => new class ($test) {
                public function __construct(private AnalyticsModelControllerTest $test)
                {
                }

                public function flash(string $key, string $message): void
                {
                    $this->test->flashes[$key] = $message;
                }
            },
            'pluginLoader' => static fn (): object => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/analytics';
                }
            },
        ]);

        $app->map('redirect', function (string $target) use ($test): void {
            $test->redirectTarget = $target;
        });
        $app->map('json', function (mixed $payload) use ($test): void {
            $test->jsonPayload = $payload;
        });

        return $app;
    }

    /** @var array<string, mixed> */
    public array $savedSettings = [];

    /** @var array<string, string> */
    public array $flashes = [];

    public ?string $redirectTarget = null;

    public mixed $jsonPayload = null;
}

/**
 * AnalyticsAdminController with render() captured instead of Vision.
 */
final class AnalyticsAdminControllerHarness extends AnalyticsAdminController
{
    public string $renderedView = '';

    /** @var array<string, mixed> */
    public array $renderedData = [];

    protected function render(string $view, array $data = [], bool $layout = true): void
    {
        $this->renderedView = $view;
        $this->renderedData = $data;
    }
}
