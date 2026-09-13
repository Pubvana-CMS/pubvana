<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SiteHealth;

use Pubvana\Plugins\SiteHealth\Services\CheckResult;
use Pubvana\Plugins\SiteHealth\Services\DatabaseCheck;
use Pubvana\Plugins\SiteHealth\Services\HealthService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * One crashing check must not take down the whole battery (AUDIT M17),
 * and a failed database connection must not leak the raw PDO message.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(HealthService::class)]
final class HealthResilienceTest extends TestCase
{
    public function testThrowingCheckYieldsCriticalResultInsteadOfCrashing(): void
    {
        $service = $this->makeService();
        $service->addCheck(new class implements \Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface {
            public function run(): CheckResult
            {
                throw new \LogicException('exploding check');
            }
        });

        $data = $service->runAll(true);

        $errors = array_values(array_filter(
            $data['results'],
            static fn (array $r): bool => ($r['id'] ?? '') === 'check-error'
        ));
        self::assertCount(1, $errors);
        self::assertSame('critical', $errors[0]['status']);
        self::assertStringContainsString('crashed', (string) $errors[0]['message']);
        // The battery still produced results for the other checks.
        self::assertGreaterThan(1, count($data['results']));
    }

    public function testThrowingExternalContributionIsSkipped(): void
    {
        $adext = new class {
            /** @return list<array<string, mixed>> */
            public function get(string $type, string $key): array
            {
                return [
                    ['callable' => static fn (): never => throw new \RuntimeException('external boom')],
                    ['callable' => static fn (): CheckResult => new CheckResult(
                        id: 'ext-ok',
                        name: 'External OK',
                        category: CheckResult::CAT_PLUGINS,
                        status: CheckResult::PASS,
                        message: 'fine',
                    )],
                ];
            }
        };
        $service = $this->makeService($adext);

        $data = $service->runAll(true);

        $ids = array_map(static fn (array $r): string => (string) ($r['id'] ?? ''), $data['results']);
        self::assertContains('ext-ok', $ids);
        self::assertNotContains('external boom', $data['summary']);
        self::assertSame('critical', $data['summary']['overall'] ?? '', 'built-ins run over sqlite; only assert the run completes');
    }

    public function testDatabaseFailureDoesNotLeakRawMessage(): void
    {
        $sqlite = new \PDO('sqlite::memory:');
        $broken = new class($sqlite) extends \PDO {
            public function __construct(private \PDO $real)
            {
            }

            public function getAttribute(int $attribute): mixed
            {
                throw new \RuntimeException('SQLSTATE[HY000] mysql://root:secret@db-host-internal');
            }
        };

        $result = (new DatabaseCheck($broken))->run();

        self::assertSame('Cannot connect to the database.', $result->message);
        self::assertStringNotContainsString('SQLSTATE', $result->message);
        self::assertStringNotContainsString('secret', $result->message);
        self::assertSame(CheckResult::CRITICAL, $result->status);
    }

    /**
     * Minimal Engine stand-in: adext() contributes no extra checks (or the
     * given stand-in), no migrations config.
     */
    private function makeService(object $adext = null): HealthService
    {
        $app = $this->app([
            'adext' => fn () => $adext ?? new class {
                /** @return list<array<string, mixed>> */
                public function get(string $type, string $key): array
                {
                    return [];
                }
            },
            'migrations' => [],
        ]);

        return new HealthService($app, Sqlite::recreate());
    }
}
