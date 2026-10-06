<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Controllers\AiRedirectsApiController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\Redirects\Models\Redirect;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use flight\Engine;
use flight\util\Collection;

/**
 * jsonHalt() stops the request. This probe surfaces that so the assertions
 * can run against the state the request left behind.
 */
final class RedirectHalt extends \RuntimeException
{
}

/**
 * Harness over the redirects controller: fail() throws instead of halting,
 * ok() records. Everything else is the real request path.
 */
final class RedirectsHarnessController extends AiRedirectsApiController
{
    public function fail(int $status, string $message): never
    {
        throw new \RuntimeException($message, $status);
    }
}

/**
 * Partial-update semantics for the AI redirects endpoint.
 *
 * updateRedirect() takes a partial payload: an omitted field keeps its
 * stored value. The distinction that matters is omitted versus explicitly
 * null. Omitting notes means "leave them alone". Sending notes as null
 * means "clear them", and a client cannot tell the difference if the
 * controller coalesces the two, so the notes would be stuck.
 *
 * enabled has the same shape and already gets it right, which is the
 * pattern notes follows.
 */
#[CoversClass(AiRedirectsApiController::class)]
final class AiRedirectsNotesTest extends TestCase
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
    private ?string $envBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);

        $this->envBackup = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
        $_ENV['SESSION_ENCRYPTION_KEY'] = 'test-encryption-key-for-ai-redirects';
        putenv('SESSION_ENCRYPTION_KEY=test-encryption-key-for-ai-redirects');

        $created = $this->service()->createKey('Test key');
        $this->token = $created['plain'];
        $this->keyId = (int) $created['key']->id;

        $this->service()->updateGrants($this->keyId, ['redirects.update', 'redirects.create']);
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
    // notes
    // -----------------------------------------------------------------

    public function testOmittedNotesKeepsTheStoredValue(): void
    {
        $this->redirect(1, 'keep me');
        $this->update(['source_path' => '/moved']);

        self::assertSame('keep me', $this->storedNotes(1));
    }

    public function testExplicitNullNotesClearsThem(): void
    {
        $this->redirect(1, 'delete me');
        $this->update(['notes' => null]);

        self::assertNull($this->storedNotes(1));
    }

    public function testEmptyStringNotesClearsThem(): void
    {
        $this->redirect(1, 'delete me too');
        $this->update(['notes' => '   ']);

        self::assertNull($this->storedNotes(1));
    }

    public function testStringNotesReplaceThem(): void
    {
        $this->redirect(1, 'old note');
        $this->update(['notes' => 'new note']);

        self::assertSame('new note', $this->storedNotes(1));
    }

    // -----------------------------------------------------------------
    // enabled, the field that already behaved
    // -----------------------------------------------------------------

    public function testOmittedEnabledKeepsTheStoredValue(): void
    {
        $this->redirect(1, null, enabled: 1);
        $this->update(['notes' => 'still here']);

        self::assertSame(1, (int) $this->storedField(1, 'enabled'));
    }

    public function testExplicitFalseEnabledDisablesIt(): void
    {
        $this->redirect(1, null, enabled: 1);
        $this->update(['enabled' => false]);

        self::assertSame(0, (int) $this->storedField(1, 'enabled'));
    }

    // -----------------------------------------------------------------
    // create: the enabled default
    // -----------------------------------------------------------------

    public function testCreateDefaultsToEnabled(): void
    {
        $this->create(['source_path' => '/new', 'target_url' => 'https://example.com/target']);

        self::assertSame(1, (int) $this->storedField(1, 'enabled'));
    }

    public function testCreateExplicitFalseDisablesIt(): void
    {
        $this->create([
            'source_path' => '/new',
            'target_url'  => 'https://example.com/target',
            'enabled'     => false,
        ]);

        self::assertSame(0, (int) $this->storedField(1, 'enabled'));
    }

    // -----------------------------------------------------------------
    // Harness
    // -----------------------------------------------------------------

    /**
     * Run updateRedirect() end to end against the SQLite tables.
     *
     * @param array<string, mixed> $payload
     */
    private function update(array $payload): void
    {
        $token = $this->token;
        $pdo = $this->pdo;

        $app = $this->app([
            'request' => static fn(): object => new class($token, $payload) {
                public string $method = 'POST';
                public string $url = '/api/ai/redirects/1/update';
                public Collection $data;

                public function __construct(private string $token, array $payload)
                {
                    $this->data = new Collection($payload);
                }

                public function getHeader(string $n): string
                {
                    return $n === 'Authorization' ? 'Bearer ' . $this->token : '';
                }
            },
        ]);

        $app->map('ai', static fn(): AiService => new AiService($pdo, $app, self::AI_CONFIG));
        $app->map('redirects', static fn(): RedirectsService => new RedirectsService($pdo, $app));
        $app->map('jsonHalt', static function (mixed $d, int $c = 200): never {
            throw new RedirectHalt();
        });
        \Flight::setEngine($app);

        try {
            (new RedirectsHarnessController($app))->updateRedirect('1');
        } catch (RedirectHalt) {
            // ok() halts on success, which is the request finishing.
        }
    }

    private function service(): AiService
    {
        $app = $this->app([]);
        $service = new AiService($this->pdo, $app, self::AI_CONFIG);
        $app->map('ai', static fn(): AiService => $service);

        return $service;
    }

    /**
     * Run createRedirect() end to end against the SQLite table.
     *
     * @param array<string, mixed> $payload
     */
    private function create(array $payload): void
    {
        $token = $this->token;
        $pdo = $this->pdo;

        $app = $this->app([
            'request' => static fn(): object => new class($token, $payload) {
                public string $method = 'POST';
                public string $url = '/api/ai/redirects';
                public Collection $data;

                public function __construct(private string $token, array $payload)
                {
                    $this->data = new Collection($payload);
                }

                public function getHeader(string $n): string
                {
                    return $n === 'Authorization' ? 'Bearer ' . $this->token : '';
                }
            },
        ]);

        $app->map('ai', static fn(): AiService => new AiService($pdo, $app, self::AI_CONFIG));
        $app->map('redirects', static fn(): RedirectsService => new RedirectsService($pdo, $app));
        $app->map('jsonHalt', static function (mixed $d, int $c = 200): never {
            throw new RedirectHalt();
        });
        \Flight::setEngine($app);

        try {
            (new RedirectsHarnessController($app))->createRedirect();
        } catch (RedirectHalt) {
            // ok() halts on success, which is the request finishing.
        }
    }

    private function redirect(int $id, ?string $notes, int $enabled = 1): void
    {
        $row = new Redirect($this->pdo);
        $row->id = $id;
        $row->source_path = '/old-' . $id;
        $row->target_url = 'https://example.com/target';
        $row->status_code = 301;
        $row->enabled = $enabled;
        $row->notes = $notes;
        $row->hit_count = 0;
        $row->last_hit_at = null;
        $row->created_at = '2026-09-01 00:00:00';
        $row->updated_at = '2026-09-01 00:00:00';
        $row->insert();
    }

    private function storedNotes(int $id): ?string
    {
        $value = $this->storedField($id, 'notes');
        return $value === null ? null : (string) $value;
    }

    private function storedField(int $id, string $field): mixed
    {
        $statement = $this->pdo->prepare("SELECT {$field} FROM redirects WHERE id = :id");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row, "redirect {$id} not found");

        return $row[$field];
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
