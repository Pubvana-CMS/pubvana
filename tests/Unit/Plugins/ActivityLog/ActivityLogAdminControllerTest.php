<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use flight\util\Collection;
use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\ActivityLog\Controllers\ActivityLogAdminController;
use Pubvana\Plugins\ActivityLog\Services\ActivityLogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ActivityLogAdminController over the real service.
 */
#[CoversClass(ActivityLogAdminController::class)]
final class ActivityLogAdminControllerTest extends TestCase
{
    private PDO $pdo;
    private ActivityLogService $logs;

    /** @var array<string, mixed> */
    public array $fetches = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ActivityLogSchema::create($this->pdo);
        $this->logs = new ActivityLogService($this->pdo, ['route_prefix' => '/activity-log']);
        $this->logs->setApp($this->engineApp());
        $this->fetches = [];
    }

    public function testIndexRendersDefaults(): void
    {
        $this->logs->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'About']);

        (new ActivityLogAdminController($this->engine()))->index();

        self::assertSame('pubvana/activity-log/admin/index', $this->fetches[0]['view']);
        $data = $this->fetches[0]['data'];
        self::assertSame('Activity Log', $data['pageTitle']);
        self::assertCount(1, $data['logs']);
        self::assertSame(1, $data['total']);
        self::assertSame(1, $data['totalPages']);
        self::assertSame(1, $data['page']);
        self::assertSame(25, $data['perPage']);
        self::assertSame(['create'], $data['actions']);
        self::assertSame(['page'], $data['entityTypes']);
        self::assertSame('/admin/activity-log', $data['adminBase']);
        self::assertSame([
            'user_id' => '', 'action' => '', 'entity_type' => '',
            'entity_name' => '', 'date_from' => '', 'date_to' => '',
        ], $data['filters']);
    }

    public function testIndexAppliesFiltersAndPageFloor(): void
    {
        $this->logs->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'About']);
        $this->logs->log(['action' => 'delete', 'entity_type' => 'user', 'entity_name' => 'bob']);

        $this->fetches = [];
        (new ActivityLogAdminController($this->engine(query: ['action' => 'delete', 'page' => '0'])))->index();

        $data = $this->fetches[0]['data'];
        self::assertSame(1, $data['total']);
        self::assertSame(1, $data['page']);
        self::assertSame('delete', $data['filters']['action']);
        self::assertSame('delete', $data['logs'][0]->action);
    }

    private function engineApp(): Engine
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
            'request' => static fn(): object => new class {
                public string $method = 'GET';

                public function getHeader(string $name): string
                {
                    return '';
                }
            },
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function engine(array $query = []): Engine
    {
        $test = $this;
        $logs = $this->logs;
        $app = $this->app([
            'request' => static fn(): object => new class($query) {
                public Collection $query;
                /** @param array<string, mixed> $q */
                public function __construct(array $q)
                {
                    $this->query = new Collection($q);
                }
            },
            'activityLog' => static fn(): ActivityLogService => $logs,
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        public int $id = 9;

                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/activity-log';
                }
            },
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private ActivityLogAdminControllerTest $t)
                    {
                    }

                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->t->fetches[] = ['view' => $v, 'data' => $d ?? []];

                        return 'C:' . $v;
                    }
                };
            },
        ]);
        $app->set('admin.topNav', []);
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        \Flight::setEngine($app);

        return $app;
    }
}
