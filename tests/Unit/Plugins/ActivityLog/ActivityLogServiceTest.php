<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use flight\net\Route;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\ActivityLog\Models\ActivityLog;
use Pubvana\Plugins\ActivityLog\Services\ActivityLogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ActivityLogService + ActivityLog model.
 */
#[CoversClass(ActivityLogService::class)]
#[CoversClass(ActivityLog::class)]
final class ActivityLogServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ActivityLogSchema::create($this->pdo);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // log()
    // -----------------------------------------------------------------

    public function testLogWritesRowWithDefaults(): void
    {
        $service = $this->service();

        $service->log([
            'action' => 'create',
            'entity_type' => 'page',
            'entity_name' => 'About',
        ]);

        $rows = $this->rows();
        self::assertCount(1, $rows);
        self::assertSame(9, (int) $rows[0]['user_id']);
        self::assertSame('ada', $rows[0]['user_name']);
        self::assertSame('create', $rows[0]['action']);
        self::assertSame('page', $rows[0]['entity_type']);
        self::assertSame('About', $rows[0]['entity_name']);
        self::assertSame('198.51.100.7', $rows[0]['ip']);
        self::assertSame('TestAgent/1.0', $rows[0]['user_agent']);
        self::assertNotEmpty($rows[0]['created_at']);
    }

    public function testLogHonorsExplicitFields(): void
    {
        $service = $this->service();

        $service->log([
            'action' => 'delete',
            'entity_type' => 'user',
            'entity_id' => 42,
            'entity_name' => 'bob',
            'details' => ['route' => '/admin/users/42/delete'],
            'user_id' => 3,
            'user_name' => 'root',
            'ip' => '10.0.0.1',
            'user_agent' => 'curl',
        ]);

        $rows = $this->rows();
        self::assertSame(3, (int) $rows[0]['user_id']);
        self::assertSame('root', $rows[0]['user_name']);
        self::assertSame(42, (int) $rows[0]['entity_id']);
        self::assertSame('{"route":"\/admin\/users\/42\/delete"}', $rows[0]['details']);
        self::assertSame('10.0.0.1', $rows[0]['ip']);
        self::assertSame('curl', $rows[0]['user_agent']);
    }

    public function testLogWithoutAppWritesNothing(): void
    {
        $service = new ActivityLogService($this->pdo);

        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'x']);

        self::assertSame([], $this->rows());
    }

    public function testLogSwallowsFailures(): void
    {
        // Throwing auth: log() must catch and never bubble.
        $app = $this->app([
            'auth' => static function (): object {
                throw new \RuntimeException('no auth');
            },
            'request' => static fn(): object => new \flight\net\Request(),
        ]);
        $service = new ActivityLogService($this->pdo);
        $service->setApp($app);

        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'x']);
        self::assertSame([], $this->rows());
    }

    public function testLogHandlesUnencodableDetails(): void
    {
        $service = $this->service();
        $bad = "\xB1\x31";
        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);

        // json_encode fails on a resource: details stored as null.
        $service->log([
            'action' => 'create',
            'entity_type' => 'page',
            'entity_name' => 'x',
            'details' => ['stream' => $resource],
        ]);
        fclose($resource);

        $rows = $this->rows();
        self::assertCount(1, $rows);
        self::assertNull($rows[0]['details']);
        unset($bad);
    }

    // -----------------------------------------------------------------
    // logFromRoute()
    // -----------------------------------------------------------------

    public function testLogFromRouteSkipsWithoutApp(): void
    {
        $service = new ActivityLogService($this->pdo);
        $service->logFromRoute($this->route('/admin/pages/store', ['id' => '1']));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteSkipsWhenTrackingDisabled(): void
    {
        $service = new ActivityLogService($this->pdo, ['track_admin_actions' => false]);
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/admin/pages/store', []));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteSkipsGetRequests(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('GET'));

        $service->logFromRoute($this->route('/admin/pages/store', []));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteSkipsNonAdminRoutes(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/page/about', []));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteSkipsExcludedRoutes(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('POST'));

        foreach (['/admin/auth/login', '/admin/assets/serve', '/admin/api/tokens'] as $pattern) {
            $service->logFromRoute($this->route($pattern, []));
        }

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteSkipsSelfReference(): void
    {
        $service = new ActivityLogService($this->pdo, ['route_prefix' => '/activity-log']);
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/admin/activity-log', []));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteLogsKnownRoute(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/admin/page/@id/update', ['id' => '12', 'title' => 'About']));

        $rows = $this->rows();
        self::assertCount(1, $rows);
        self::assertSame('update', $rows[0]['action']);
        self::assertSame('page', $rows[0]['entity_type']);
        self::assertSame(12, (int) $rows[0]['entity_id']);
        self::assertSame('About', $rows[0]['entity_name']);
        $details = json_decode((string) $rows[0]['details'], true);
        self::assertSame('/admin/page/@id/update', $details['route']);
        self::assertSame('POST', $details['method']);
    }

    public function testLogFromRouteSkipsUnknownRoute(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/admin/something-made-up/do', []));

        self::assertSame([], $this->rows());
    }

    public function testLogFromRouteMatchesSpecificActions(): void
    {
        $service = $this->service();

        // Real admin mutations are POST with the verb in the path. Each row
        // records the verb and the entity the route acts on.
        $routes = [
            '/admin/users/@id/toggle'            => ['toggle', 'user'],
            '/admin/comments/@id/reject'         => ['reject', 'comment'],
            '/admin/plugins/save'                => ['settings_change', 'plugin'],
            '/admin/backups/restore/@filename'   => ['restore', 'backup'],
            '/admin/themes/@id/activate'         => ['activate', 'theme'],
            '/admin/blog/categories/@id/delete'  => ['delete', 'blog_category'],
        ];

        foreach (array_keys($routes) as $pattern) {
            $service->logFromRoute($this->route($pattern, []));
        }

        $rows = $this->rows();
        self::assertCount(count($routes), $rows);
        foreach (array_values($routes) as $i => $expected) {
            self::assertSame($expected[0], $rows[$i]['action'], 'Action for ' . array_keys($routes)[$i]);
            self::assertSame($expected[1], $rows[$i]['entity_type'], 'Entity for ' . array_keys($routes)[$i]);
        }
    }

    public function testLogFromRouteFallsBackToEntityTypeName(): void
    {
        $service = $this->service();
        $service->setApp($this->engineApp('POST'));

        $service->logFromRoute($this->route('/admin/page/@id/delete', []));

        $rows = $this->rows();
        self::assertSame('delete', $rows[0]['action']);
        self::assertSame('page', $rows[0]['entity_name']);
        self::assertNull($rows[0]['entity_id']);
    }

    // -----------------------------------------------------------------
    // list / count / dropdowns
    // -----------------------------------------------------------------

    public function testListCountAndRecent(): void
    {
        $service = $this->service();
        for ($i = 1; $i <= 3; $i++) {
            $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => "P{$i}"]);
        }

        self::assertCount(3, $service->list());
        self::assertSame(3, $service->count());
        self::assertCount(2, $service->list([], 1, 2));
        self::assertCount(1, $service->list([], 2, 2));

        $recent = (new ActivityLog($this->pdo))->recent(2);
        self::assertCount(2, $recent);
        self::assertGreaterThan((int) $recent[1]->id, (int) $recent[0]->id);

        // limit floor: 0 becomes 1.
        self::assertCount(1, (new ActivityLog($this->pdo))->recent(0));
    }

    public function testFilteredListAndCount(): void
    {
        $service = $this->service();
        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'About Us', 'user_id' => 1]);
        $service->log(['action' => 'delete', 'entity_type' => 'user', 'entity_name' => 'bob', 'user_id' => 2]);

        self::assertCount(1, $service->list(['action' => 'delete']));
        self::assertSame(1, $service->count(['action' => 'delete']));
        self::assertCount(1, $service->list(['entity_type' => 'user']));
        self::assertCount(1, $service->list(['user_id' => 1]));
        self::assertCount(1, $service->list(['entity_name' => 'About']));
        self::assertCount(0, $service->list(['entity_name' => 'missing']));
        // A LIKE wildcard in the term is matched literally (explicit ESCAPE),
        // so it must not widen the result to every row.
        self::assertCount(0, $service->list(['entity_name' => '%']));
        self::assertCount(0, $service->list(['entity_name' => '_']));
        self::assertSame(0, $service->count(['entity_name' => '%']));

        $today = date('Y-m-d');
        self::assertCount(2, $service->list(['date_from' => $today]));
        self::assertSame(2, $service->count(['date_from' => $today]));
        self::assertCount(2, $service->list(['date_to' => $today]));
        self::assertCount(0, $service->list(['date_from' => '2999-01-01']));
    }

    public function testCountRecent24h(): void
    {
        $service = $this->service();
        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'now']);
        $this->pdo->exec("INSERT INTO activity_logs (user_name, action, entity_type, entity_name, ip, created_at) VALUES ('old', 'create', 'page', 'old', '1.1.1.1', '2000-01-01 00:00:00')");

        self::assertSame(1, $service->countRecent24h());
        self::assertSame(1, (new ActivityLog($this->pdo))->countSince(date('Y-m-d H:i:s', strtotime('-24 hours'))));
    }

    public function testDropdowns(): void
    {
        $service = $this->service();
        self::assertSame([], $service->getActions());
        self::assertSame([], $service->getEntityTypes());
        self::assertSame([], $service->getUsers());

        $service->log(['action' => 'delete', 'entity_type' => 'user', 'entity_name' => 'b', 'user_id' => 2, 'user_name' => 'bob']);
        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'a', 'user_id' => 1, 'user_name' => 'ada']);

        self::assertSame(['create', 'delete'], $service->getActions());
        self::assertSame(['page', 'user'], $service->getEntityTypes());
        $users = $service->getUsers();
        self::assertSame('ada', $users[0]['user_name']);
        self::assertSame('bob', $users[1]['user_name']);
    }

    public function testDropdownQueriesOnTheModel(): void
    {
        $model = new ActivityLog($this->pdo);

        // Empty table: every dropdown is an empty array, not null.
        self::assertSame([], $model->distinctActions());
        self::assertSame([], $model->distinctEntityTypes());
        self::assertSame([], $model->distinctUsers());

        $this->pdo->exec("INSERT INTO activity_logs (user_name, action, entity_type, entity_name, ip, created_at) VALUES ('sys', 'update', 'page', 'A', '1.1.1.1', '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_name, ip, created_at) VALUES (1, 'ada', 'create', 'page', 'B', '1.1.1.1', '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_name, ip, created_at) VALUES (1, 'ada', 'create', 'user', 'C', '1.1.1.1', '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_name, ip, created_at) VALUES (2, 'bob', 'delete', 'user', 'D', '1.1.1.1', '2026-01-01 00:00:00')");

        // Sorted and deduped: ada wrote twice, the null user_id row is gone.
        self::assertSame(['create', 'delete', 'update'], $model->distinctActions());
        self::assertSame(['page', 'user'], $model->distinctEntityTypes());
        self::assertSame([
            ['user_id' => 1, 'user_name' => 'ada'],
            ['user_id' => 2, 'user_name' => 'bob'],
        ], $model->distinctUsers());
    }

    public function testExtractEntityIdAndNameHelpers(): void
    {
        $service = new ActivityLogService($this->pdo);

        self::assertSame(5, $this->invoke($service, 'extractEntityId', [['post_id' => '5']]));
        self::assertSame(7, $this->invoke($service, 'extractEntityId', [['id' => '7']]));
        self::assertSame(8, $this->invoke($service, 'extractEntityId', [['userId' => '8']]));
        self::assertNull($this->invoke($service, 'extractEntityId', [['id' => 'abc']]));
        self::assertNull($this->invoke($service, 'extractEntityId', [[]]));

        self::assertSame('About', $this->invoke($service, 'extractEntityName', [['title' => 'About'], 'page']));
        self::assertSame('bundle.zip', $this->invoke($service, 'extractEntityName', [['filename' => 'bundle.zip'], 'backup']));
        self::assertSame('page', $this->invoke($service, 'extractEntityName', [[], 'page']));
        self::assertSame('page', $this->invoke($service, 'extractEntityName', [['title' => ''], 'page']));
    }

    /**
     * Every admin mutation is POST with the verb in the path. The map is
     * keyed on real route prefixes, not on PUT/PATCH/DELETE verbs.
     */
    public function testInferFromRouteActions(): void
    {
        $service = new ActivityLogService($this->pdo);

        $cases = [
            '/admin/users/store'                      => ['create', 'user'],
            '/admin/users/@id/update'                 => ['update', 'user'],
            '/admin/users/@id/delete'                 => ['delete', 'user'],
            '/admin/users/@id/force-reset'            => ['force_reset', 'user'],
            '/admin/blog/store'                       => ['create', 'blog_post'],
            '/admin/blog/@id/restore/@revisionId'     => ['restore', 'blog_post'],
            '/admin/blog/categories/store'            => ['create', 'blog_category'],
            '/admin/page/@id/delete'                  => ['delete', 'page'],
            '/admin/profile/@userId/update'           => ['update', 'profile'],
            '/admin/media/upload/image'               => ['create', 'media'],
            '/admin/media/@id/edit'                   => ['update', 'media'],
            '/admin/redirects/404-manager/@id/ignore' => ['ignore', 'redirect_link'],
            '/admin/forms/submissions/@id/delete'     => ['delete', 'form_submission'],
            '/admin/themes/regions/place'             => ['place', 'region'],
            '/admin/themes/@id/recheck'               => ['recheck', 'theme'],
            '/admin/plugins/save'                     => ['settings_change', 'plugin'],
            '/admin/settings/save'                    => ['settings_change', 'setting'],
            '/admin/seo'                              => ['settings_change', 'seo'],
            '/admin/search'                           => ['settings_change', 'search_setting'],
            '/admin/email/test'                       => ['test', 'email_setting'],
            '/admin/ai/manage/keys'                   => ['create', 'ai_key'],
            '/admin/ai/manage/keys/@id/grants'        => ['update', 'ai_key'],
            '/admin/updates/addon-check'              => ['addon_check', 'update'],
            '/admin/broken-links/@id/dismiss'         => ['dismiss', 'broken_link'],
            '/admin/social-links/@id/reorder'         => ['reorder', 'social_link'],
        ];

        foreach ($cases as $pattern => $expected) {
            $inferred = $this->invoke($service, 'inferFromRoute', [$pattern]);
            self::assertIsArray($inferred, "No inference for {$pattern}");
            self::assertSame($expected[0], $inferred['action'], "Action for {$pattern}");
            self::assertSame($expected[1], $inferred['entity_type'], "Entity for {$pattern}");
        }

        // Unknown admin routes are skipped, not guessed.
        self::assertNull($this->invoke($service, 'inferFromRoute', ['/admin/something-made-up/do']));
    }

    public function testShouldSkipRoute(): void
    {
        $service = new ActivityLogService($this->pdo, ['route_prefix' => '/activity-log']);

        self::assertTrue($this->invoke($service, 'shouldSkipRoute', ['/admin/auth/login']));
        self::assertTrue($this->invoke($service, 'shouldSkipRoute', ['/admin/activity-log']));
        self::assertFalse($this->invoke($service, 'shouldSkipRoute', ['/admin/pages']));
    }

    public function testGetUserAgentFallback(): void
    {
        $service = new ActivityLogService($this->pdo);

        $request = new \flight\net\Request();
        self::assertSame('unknown', $this->invoke($service, 'getUserAgent', [$request]));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function service(): ActivityLogService
    {
        // route_prefix must be set: with an empty prefix the self-reference
        // skip pattern degrades to '/admin' and skips every admin route.
        $service = new ActivityLogService($this->pdo, ['route_prefix' => '/activity-log']);
        $service->setApp($this->engineApp('POST'));

        return $service;
    }

    private function engineApp(string $method): \flight\Engine
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $app = $this->app([
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        public int $id = 9;
                        public string $username = 'ada';
                    };
                }
            },
            'request' => function () use ($method): object {
                return new class($method) {
                    public string $method;

                    public function __construct(string $m)
                    {
                        $this->method = $m;
                    }

                    public function getHeader(string $name): string
                    {
                        return $name === 'User-Agent' ? 'TestAgent/1.0' : '';
                    }
                };
            },
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    private function route(string $pattern, array $params): Route
    {
        $route = new Route('/admin/x', fn() => null, ['GET'], false, '');
        $route->pattern = $pattern;
        /** @var array<string, string> $params */
        $route->params = $params;

        return $route;
    }

    /** @return list<array<string, mixed>> */
    private function rows(): array
    {
        return $this->pdo->query('SELECT * FROM activity_logs ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
    }
}
