<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Controllers\Ai404sApiController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * jsonHalt() stops the request. This probe surfaces that so the assertions
 * can run against the state the request left behind.
 */
final class RedirectLinkHalt extends \RuntimeException
{
}

/**
 * Collects the JSON body ok() built, unwrapped from its envelope.
 */
final class JsonRecorder
{
    /** @var array<mixed> */
    public array $body = [];

    public function record(mixed $envelope): void
    {
        if (!is_array($envelope)) {
            return;
        }

        $data = $envelope['data'] ?? null;
        $this->body = is_array($data) ? $data : [];
    }
}

/**
 * Harness over the 404s controller: fail() throws instead of halting, so a
 * refused grant or a missing entry can be asserted. Everything else is the
 * real request path.
 */
final class RedirectLinkHarnessController extends Ai404sApiController
{
    public function fail(int $status, string $message): never
    {
        throw new \RuntimeException($message, $status);
    }
}

/**
 * The /api/ai/404s/* endpoints over the real Redirects services.
 */
#[CoversClass(Ai404sApiController::class)]
final class Ai404sApiControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    private const AI_CONFIG = [
        'route_prefix' => '/api/ai',
        'key_prefix' => 'pvai1_',
        'max_failed_attempts' => 3,
        'block_minutes' => 30,
        'log_limit' => 200,
    ];

    private PDO $pdo;
    private string $token;
    private int $keyId;
    private JsonRecorder $recorder;
    private ?string $envBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
        $this->recorder = new JsonRecorder();

        $this->envBackup = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
        $_ENV['SESSION_ENCRYPTION_KEY'] = 'test-encryption-key-for-ai-404s';
        putenv('SESSION_ENCRYPTION_KEY=test-encryption-key-for-ai-404s');

        $created = $this->service()->createKey('404 key');
        $this->token = $created['plain'];
        $this->keyId = (int) $created['key']->id;

        $this->service()->updateGrants($this->keyId, [
            '404s.read',
            '404s.ignore',
            '404s.delete',
            '404s.resolve',
            'redirects.create',
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->envBackup === null) {
            unset($_ENV['SESSION_ENCRYPTION_KEY']);
            putenv('SESSION_ENCRYPTION_KEY');
        } else {
            $_ENV['SESSION_ENCRYPTION_KEY'] = $this->envBackup;
            putenv('SESSION_ENCRYPTION_KEY=' . $this->envBackup);
        }
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // listing
    // -----------------------------------------------------------------

    public function testListFiltersByStatusAndCountsEveryStatus(): void
    {
        $this->entry('/open-one');
        $this->entry('/open-two');
        $this->entry('/ignored', ignored: 1);
        $this->entry('/resolved', resolved: 7);

        $data = $this->call('entries', query: ['status' => 'active']);
        $counts = $this->arrayField($data, 'counts');

        self::assertSame('active', $data['status']);
        self::assertSame(2, $data['total']);
        self::assertCount(2, $this->arrayField($data, 'items'));
        self::assertSame(2, $counts['active']);
        self::assertSame(1, $counts['ignored']);
        self::assertSame(1, $counts['resolved']);
    }

    public function testSerializerShape(): void
    {
        $id = (int) $this->entry('/gone', hitCount: 5);
        $entry = $this->linkService()->find($id);
        self::assertNotNull($entry);

        $data = $this->service()->serializeRedirectLink($entry);

        self::assertSame($id, $data['id']);
        self::assertSame('/gone', $data['source_path']);
        self::assertSame(5, $data['hit_count']);
        self::assertFalse($data['ignored']);
        self::assertNull($data['resolved_redirect_id']);
        self::assertSame('2026-10-01 00:00:00', $data['first_seen_at']);
        self::assertSame('2026-10-01 00:00:00', $data['last_seen_at']);
    }

    public function testListAcceptsEveryKnownStatus(): void
    {
        $this->entry('/open-one');
        $this->entry('/ignored', ignored: 1);
        $this->entry('/resolved', resolved: 7);

        self::assertSame('ignored', $this->call('entries', query: ['status' => 'ignored'])['status']);
        self::assertSame('resolved', $this->call('entries', query: ['status' => 'resolved'])['status']);
        self::assertSame(3, $this->call('entries', query: ['status' => 'all'])['total']);
    }

    public function testListFallsBackToActiveOnAnUnknownStatus(): void
    {
        $this->entry('/open-one');

        $data = $this->call('entries', query: ['status' => 'bogus']);

        self::assertSame('active', $data['status']);
        self::assertSame(1, $data['total']);
    }

    public function testListClampsThePagingParams(): void
    {
        // A zero page would reach MySQL as a negative OFFSET.
        $data = $this->call('entries', query: ['page' => '0', 'per_page' => '500']);

        self::assertSame(1, $data['page']);
        self::assertSame(100, $data['per_page']);
    }

    // -----------------------------------------------------------------
    // triage
    // -----------------------------------------------------------------

    public function testIgnoreAndUnignore(): void
    {
        $id = $this->entry('/gone');

        self::assertTrue($this->call('ignoreEntry', [$id])['ignored']);
        self::assertSame(0, $this->linkService()->count('active'));
        self::assertSame(1, $this->linkService()->count('ignored'));

        self::assertFalse($this->call('unignoreEntry', [$id])['ignored']);
        self::assertSame(1, $this->linkService()->count('active'));
    }

    public function testDeleteRemovesTheEntry(): void
    {
        $id = $this->entry('/gone');

        $data = $this->call('deleteEntry', [$id]);

        self::assertTrue($data['deleted']);
        self::assertSame(0, $this->linkService()->count('all'));
    }

    public function testUnknownIdIsAnErrorForEveryAction(): void
    {
        foreach (['ignoreEntry', 'unignoreEntry', 'deleteEntry', 'createRedirect'] as $method) {
            try {
                $this->call($method, ['9999'], payload: ['target_url' => '/new-path']);
                self::fail("{$method} must fail on an unknown entry.");
            } catch (\RuntimeException $e) {
                self::assertSame(404, $e->getCode(), $method);
            }
        }
    }

    // -----------------------------------------------------------------
    // redirect from a 404
    // -----------------------------------------------------------------

    public function testCreateRedirectResolvesTheEntry(): void
    {
        $id = $this->entry('/old-path', hitCount: 2);

        $data = $this->call('createRedirect', [$id], payload: ['target_url' => '/new-path']);
        $redirect = $this->arrayField($data, 'redirect');
        $entry = $this->arrayField($data, 'entry');

        self::assertSame('/old-path', $redirect['source_path']);
        self::assertSame('/new-path', $redirect['target_url']);
        self::assertSame(301, $redirect['status_code']);
        self::assertTrue($redirect['enabled']);
        self::assertSame(1, $entry['resolved_redirect_id']);
        self::assertSame(0, $this->linkService()->count('active'));
        self::assertSame(1, $this->linkService()->count('resolved'));
    }

    public function testCreateRedirectTakesTheStatusAndNotes(): void
    {
        $id = $this->entry('/old-path');

        $data = $this->call('createRedirect', [$id], payload: [
            'target_url'  => '/new-path',
            'status_code' => 302,
            'notes'       => '  moved by the assistant  ',
        ]);

        $redirect = $this->arrayField($data, 'redirect');

        self::assertSame(302, $redirect['status_code']);
        self::assertSame('moved by the assistant', $redirect['notes']);
    }

    public function testCreateRedirectCanDisableTheRule(): void
    {
        $id = $this->entry('/old-path');

        $data = $this->call('createRedirect', [$id], payload: [
            'target_url' => '/new-path',
            'enabled'    => false,
        ]);

        self::assertFalse($this->arrayField($data, 'redirect')['enabled']);
    }

    public function testCreateRedirectNeedsATarget(): void
    {
        $id = $this->entry('/old-path');

        try {
            $this->call('createRedirect', [$id]);
            self::fail('target_url is required.');
        } catch (\RuntimeException $e) {
            self::assertSame(422, $e->getCode());
        }

        self::assertSame(1, $this->linkService()->count('active'));
        self::assertSame(0, $this->redirectService()->countAll());
    }

    public function testCreateRedirectReportsADuplicateSourcePath(): void
    {
        $id = $this->entry('/old-path');
        $this->redirectService()->create(['source_path' => '/old-path', 'target_url' => '/already-there']);

        try {
            $this->call('createRedirect', [$id], payload: ['target_url' => '/new-path']);
            self::fail('A duplicate source path must be refused.');
        } catch (\RuntimeException $e) {
            self::assertSame(422, $e->getCode());
            self::assertStringContainsString('already exists', $e->getMessage());
        }

        self::assertSame(1, $this->linkService()->count('active'));
    }

    // -----------------------------------------------------------------
    // grants
    // -----------------------------------------------------------------

    public function testEveryActionNeedsItsOwnGrant(): void
    {
        $id = $this->entry('/old-path');
        $this->service()->updateGrants($this->keyId, []);

        $calls = [
            'entries' => [],
            'ignoreEntry' => [$id],
            'unignoreEntry' => [$id],
            'deleteEntry' => [$id],
            'createRedirect' => [$id],
        ];

        foreach ($calls as $method => $args) {
            try {
                $this->call($method, $args, payload: ['target_url' => '/new-path']);
                self::fail("{$method} must need a grant.");
            } catch (\RuntimeException $e) {
                self::assertSame(403, $e->getCode(), $method);
            }
        }
    }

    public function testCreateRedirectAlsoNeedsTheRedirectsCreateGrant(): void
    {
        $id = $this->entry('/old-path');
        $this->service()->updateGrants($this->keyId, ['404s.resolve']);

        try {
            $this->call('createRedirect', [$id], payload: ['target_url' => '/new-path']);
            self::fail('The redirects.create grant is required as well.');
        } catch (\RuntimeException $e) {
            self::assertSame(403, $e->getCode());
            self::assertStringContainsString('redirects.create', $e->getMessage());
        }

        self::assertSame(0, $this->redirectService()->countAll());
    }

    // -----------------------------------------------------------------
    // harness
    // -----------------------------------------------------------------

    /**
     * Run one controller action end to end against the SQLite tables and
     * return the JSON body ok() produced.
     *
     * @param string               $method  Controller action
     * @param list<string>         $args    Positional route parameters
     * @param array<string, mixed> $query   Query string values
     * @param array<string, mixed> $payload JSON body
     * @return array<string, mixed>
     */
    private function call(string $method, array $args = [], array $query = [], array $payload = []): array
    {
        $test = $this;
        $token = $this->token;
        $pdo = $this->pdo;

        $app = $this->app([
            'request' => static fn(): object => new class($token, $query, $payload) {
                public string $method = 'POST';
                public string $url = '/api/ai/404s';
                public Collection $query;
                public Collection $data;

                /**
                 * @param array<string, mixed> $q
                 * @param array<string, mixed> $d
                 */
                public function __construct(private string $token, array $q, array $d)
                {
                    $this->query = new Collection($q);
                    $this->data = new Collection($d);
                }

                public function getHeader(string $name): string
                {
                    return $name === 'Authorization' ? 'Bearer ' . $this->token : '';
                }
            },
        ]);

        $app->map('ai', static fn(): AiService => new AiService($pdo, $app, self::AI_CONFIG));
        $app->map('redirects', static fn(): RedirectsService => new RedirectsService($pdo, $app));
        $app->map('redirectLinks', static fn(): RedirectLinksService => new RedirectLinksService($pdo, $app));
        $app->map('jsonHalt', static function (mixed $envelope, int $status = 200) use ($test): never {
            $test->recorder->record($envelope);
            throw new RedirectLinkHalt();
        });
        \Flight::setEngine($app);

        try {
            (new RedirectLinkHarnessController($app))->{$method}(...$args);
        } catch (RedirectLinkHalt) {
            // ok() halts on success, which is the request finishing.
        }

        return $this->recorder->body;
    }

    /**
     * Read a field that is expected to hold an array.
     *
     * @param array<string, mixed> $data
     * @return array<mixed>
     */
    private function arrayField(array $data, string $field): array
    {
        $value = $data[$field] ?? null;
        self::assertIsArray($value, "Field {$field} is not an array.");

        return $value;
    }

    private function service(): AiService
    {
        $app = $this->app([]);
        $service = new AiService($this->pdo, $app, self::AI_CONFIG);
        $app->map('ai', static fn(): AiService => $service);

        return $service;
    }

    private function linkService(): RedirectLinksService
    {
        return new RedirectLinksService($this->pdo, $this->app([]));
    }

    private function redirectService(): RedirectsService
    {
        return new RedirectsService($this->pdo, $this->app([]));
    }

    /**
     * Insert a 404 entry and return its id as a route parameter.
     */
    private function entry(string $path, int $ignored = 0, ?int $resolved = null, int $hitCount = 1): string
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO redirects_links (source_path, hit_count, ignored, resolved_redirect_id, first_seen_at, last_seen_at)
             VALUES (:s, :h, :i, :r, :seen, :seen)'
        );
        $statement->execute([
            's' => $path,
            'h' => $hitCount,
            'i' => $ignored,
            'r' => $resolved,
            'seen' => '2026-10-01 00:00:00',
        ]);

        return (string) $this->pdo->lastInsertId();
    }

    private function createTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE redirects (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                source_path  TEXT NOT NULL,
                target_url   TEXT NOT NULL,
                status_code  INTEGER NOT NULL DEFAULT 301,
                enabled      INTEGER NOT NULL DEFAULT 1,
                notes        TEXT,
                hit_count    INTEGER NOT NULL DEFAULT 0,
                last_hit_at  TEXT,
                created_at   TEXT,
                updated_at   TEXT
            )'
        );
        $pdo->exec('CREATE UNIQUE INDEX redirects_source_path ON redirects (source_path)');
        $pdo->exec(
            'CREATE TABLE redirects_links (
                id                   INTEGER PRIMARY KEY AUTOINCREMENT,
                source_path          TEXT NOT NULL,
                hit_count            INTEGER NOT NULL DEFAULT 0,
                last_query_string    TEXT,
                last_referrer        TEXT,
                last_user_agent      TEXT,
                ignored              INTEGER NOT NULL DEFAULT 0,
                resolved_redirect_id INTEGER,
                resolved_at          TEXT,
                first_seen_at        TEXT,
                last_seen_at         TEXT
            )'
        );
        $pdo->exec('CREATE UNIQUE INDEX redirects_links_source_path ON redirects_links (source_path)');
        $pdo->exec(
            'CREATE TABLE ai_keys (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                name            TEXT NOT NULL,
                key_hash        TEXT NOT NULL UNIQUE,
                key_prefix      TEXT NOT NULL DEFAULT \'\',
                enabled         INTEGER NOT NULL DEFAULT 1,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                blocked_until   TEXT,
                last_used_at    TEXT,
                created_at      TEXT,
                updated_at      TEXT
            )'
        );
        $pdo->exec(
            'CREATE TABLE ai_key_grants (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                key_id     INTEGER NOT NULL REFERENCES ai_keys(id) ON DELETE CASCADE,
                permission TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE ai_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                key_id      INTEGER,
                key_name    TEXT,
                method      TEXT NOT NULL,
                endpoint    TEXT NOT NULL,
                entity_type TEXT,
                entity_id   INTEGER,
                outcome     TEXT NOT NULL,
                detail      TEXT,
                ip          TEXT,
                created_at  TEXT
            )'
        );
    }
}
