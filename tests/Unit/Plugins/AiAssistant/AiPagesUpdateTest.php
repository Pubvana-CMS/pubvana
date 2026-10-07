<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Controllers\AiPagesApiController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use flight\util\Collection;

/**
 * jsonHalt() stops the request. This probe surfaces that so the assertions
 * can run against the array the controller built.
 */
final class PagesUpdateHalt extends \RuntimeException
{
}

/**
 * Harness over the pages controller: fail() throws instead of halting.
 */
final class PagesUpdateHarnessController extends AiPagesApiController
{
    public function fail(int $status, string $message): never
    {
        throw new \RuntimeException($message, $status);
    }
}

/**
 * Stub pages service: findPage() returns a fixed row, updatePage() records
 * the update array the controller built.
 */
final class PagesServiceStub
{
    /** @var list<array<string, mixed>> */
    public array $updates = [];

    public function __construct(public object $page)
    {
    }

    public function findPage(int $id): object
    {
        return $this->page;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePage(int $id, array $data): object
    {
        $this->updates[] = $data;

        return $this->page;
    }
}

/**
 * Partial page updates must not touch fields the caller did not send.
 *
 * The controller passes allow_comments through only when the payload carries
 * it. PagesService::updatePage() leaves the column alone when the key is
 * absent, so a title-only update cannot switch comments off.
 */
#[CoversClass(AiPagesApiController::class)]
final class AiPagesUpdateTest extends TestCase
{
    private PDO $pdo;
    private string $token;
    private int $keyId;
    private ?string $envBackup;
    private PagesServiceStub $pages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);

        $this->envBackup = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
        $_ENV['SESSION_ENCRYPTION_KEY'] = 'test-encryption-key-for-ai-pages';
        putenv('SESSION_ENCRYPTION_KEY=test-encryption-key-for-ai-pages');

        $created = $this->service()->createKey('Test key');
        $this->token = $created['plain'];
        $this->keyId = (int) $created['key']->id;
        $this->service()->updateGrants($this->keyId, ['pages.update']);

        $this->pages = new PagesServiceStub((object) [
            'id'             => 1,
            'title'          => 'About',
            'slug'           => 'about',
            'content'        => '<p>About</p>',
            'status'         => 'published',
            'allow_comments' => 1,
            'ai_generated'   => 0,
            'created_by'     => 1,
            'created_at'     => '2026-08-01 00:00:00',
            'updated_at'     => '2026-09-01 00:00:00',
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

    public function testOmittedAllowCommentsIsNotSent(): void
    {
        $this->update(['title' => 'New title']);

        // Absent is what makes PagesService::updatePage() leave the column
        // alone. Sending the page's current value would also work today, but it
        // makes the controller the authority on a field it was not asked about.
        self::assertArrayNotHasKey('allow_comments', $this->pages->updates[0]);
    }

    public function testExplicitFalseTurnsCommentsOff(): void
    {
        $this->update(['allow_comments' => false]);

        self::assertSame(0, $this->pages->updates[0]['allow_comments']);
    }

    public function testExplicitTrueTurnsCommentsOn(): void
    {
        $this->pages->page->allow_comments = 0;
        $this->update(['allow_comments' => true]);

        self::assertSame(1, $this->pages->updates[0]['allow_comments']);
    }

    /**
     * Run updatePage() end to end against the stub pages service.
     *
     * @param array<string, mixed> $payload
     */
    private function update(array $payload): void
    {
        $token = $this->token;
        $pages = $this->pages;
        $pdo = $this->pdo;

        $app = $this->app([
            'request' => static fn(): object => new class($token, $payload) {
                public string $method = 'POST';
                public string $url = '/api/ai/pages/1/update';
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

        $app->map('ai', static fn(): AiService => new AiService($pdo, $app, ['route_prefix' => '/api/ai']));
        $app->map('aiMarkdown', static fn(): MarkdownService => new MarkdownService());
        $app->map('pages', static fn(): PagesServiceStub => $pages);
        $app->map('jsonHalt', static function (mixed $d, int $c = 200): never {
            throw new PagesUpdateHalt();
        });
        \Flight::setEngine($app);

        try {
            (new PagesUpdateHarnessController($app))->updatePage('1');
        } catch (PagesUpdateHalt) {
            // ok() halts on success, which is the request finishing.
        }
    }

    private function service(): AiService
    {
        $app = $this->app([]);
        $service = new AiService($this->pdo, $app, ['route_prefix' => '/api/ai']);
        $app->map('ai', static fn(): AiService => $service);

        return $service;
    }

    private function createTables(PDO $pdo): void
    {
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
                permission TEXT NOT NULL,
                UNIQUE(key_id, permission)
            )'
        );
    }
}
