<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Models\AiFactCheck;
use Pubvana\Plugins\AiAssistant\Models\AiKey;
use Pubvana\Plugins\AiAssistant\Models\AiKeyGrant;
use Pubvana\Plugins\AiAssistant\Models\AiLog;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AI Assistant models against in-memory SQLite.
 */
#[CoversClass(AiKey::class)]
#[CoversClass(AiKeyGrant::class)]
#[CoversClass(AiLog::class)]
#[CoversClass(AiFactCheck::class)]
final class AiModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
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
                key_id     INTEGER NOT NULL,
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
        $pdo->exec(
            'CREATE TABLE ai_fact_checks (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                content_type        TEXT NOT NULL,
                content_id          INTEGER NOT NULL,
                content_title       TEXT NOT NULL DEFAULT \'\',
                content_slug        TEXT NOT NULL DEFAULT \'\',
                content_updated_at  TEXT,
                summary             TEXT NOT NULL,
                overall_verdict     TEXT NOT NULL,
                claim_count         INTEGER NOT NULL DEFAULT 0,
                claims              TEXT NOT NULL,
                prompt_version      TEXT NOT NULL,
                prompt_interference INTEGER NOT NULL DEFAULT 0,
                interference_note   TEXT,
                key_id              INTEGER,
                key_name            TEXT,
                created_at          TEXT,
                updated_at          TEXT
            )'
        );
    }

    private function insertKey(string $hash = 'h1', int $enabled = 1, ?string $blockedUntil = null): AiKey
    {
        $key = new AiKey($this->pdo);
        $key->name = 'Test key';
        $key->key_hash = $hash;
        $key->key_prefix = 'pvai1_test';
        $key->enabled = $enabled;
        $key->failed_attempts = 0;
        $key->blocked_until = $blockedUntil;
        $key->last_used_at = null;
        $key->created_at = '2026-01-01 00:00:00';
        $key->updated_at = '2026-01-01 00:00:00';
        $key->insert();

        return $key;
    }

    public function testKeyFindAndOrdered(): void
    {
        self::assertNull((new AiKey($this->pdo))->findById(99999));
        self::assertNull((new AiKey($this->pdo))->findByHash('missing'));

        $a = $this->insertKey('ha');
        $a->created_at = '2026-01-01 00:00:00';
        $a->save();
        $b = $this->insertKey('hb');
        $b->created_at = '2026-01-02 00:00:00';
        $b->save();

        self::assertNotNull((new AiKey($this->pdo))->findById((int) $a->id));
        self::assertNotNull((new AiKey($this->pdo))->findByHash('hb'));

        $ordered = (new AiKey($this->pdo))->allOrdered();
        self::assertCount(2, $ordered);
        self::assertSame((int) $b->id, (int) $ordered[0]->id);
    }

    public function testKeyEnabledAndBlocked(): void
    {
        $on = $this->insertKey('h-on');
        self::assertTrue($on->isEnabled());
        self::assertFalse($on->isBlocked());

        $off = $this->insertKey('h-off', 0);
        self::assertFalse($off->isEnabled());

        $future = $this->insertKey('h-future', 1, date('Y-m-d H:i:s', time() + 3600));
        self::assertTrue($future->isBlocked());

        $past = $this->insertKey('h-past', 1, '2000-01-01 00:00:00');
        self::assertFalse($past->isBlocked());

        $empty = $this->insertKey('h-empty', 1, '');
        self::assertFalse($empty->isBlocked());
    }

    public function testGrantsPermissionsAndReplace(): void
    {
        $key = $this->insertKey('hg');
        $grants = new AiKeyGrant($this->pdo);

        self::assertSame([], $grants->permissionsFor((int) $key->id));

        $grants->replaceFor((int) $key->id, ['posts.read', ' posts.update ', 'posts.read', '', '  ']);
        self::assertSame(['posts.read', 'posts.update'], $grants->permissionsFor((int) $key->id));

        $grants->replaceFor((int) $key->id, []);
        self::assertSame([], $grants->permissionsFor((int) $key->id));
    }

    public function testLogRecent(): void
    {
        $logs = new AiLog($this->pdo);
        self::assertSame([], $logs->recent(5));

        foreach (['ok', 'denied', 'error'] as $i => $outcome) {
            $entry = new AiLog($this->pdo);
            $entry->key_id = null;
            $entry->key_name = null;
            $entry->method = 'GET';
            $entry->endpoint = '/api/ai/posts';
            $entry->entity_type = null;
            $entry->entity_id = null;
            $entry->outcome = $outcome;
            $entry->detail = 'd' . $i;
            $entry->ip = '127.0.0.1';
            $entry->created_at = '2026-01-0' . ($i + 1) . ' 00:00:00';
            $entry->insert();
        }

        $recent = (new AiLog($this->pdo))->recent(2);
        self::assertCount(2, $recent);
        self::assertSame('error', (string) $recent[0]->outcome);
        self::assertSame('denied', (string) $recent[1]->outcome);
        // Floor of 1: recent(0) still returns the newest row.
        self::assertCount(1, (new AiLog($this->pdo))->recent(0));
    }

    public function testFactCheckFindLatestAndClaims(): void
    {
        self::assertNull((new AiFactCheck($this->pdo))->findById(99999));
        self::assertNull((new AiFactCheck($this->pdo))->latestForContent('post', 1));

        $first = new AiFactCheck($this->pdo);
        $first->content_type = 'post';
        $first->content_id = 1;
        $first->content_title = 'T';
        $first->content_slug = 't';
        $first->content_updated_at = null;
        $first->summary = 's';
        $first->overall_verdict = 'supported';
        $first->claim_count = 1;
        $claimsJson = json_encode([['text' => 'x']]);
        self::assertNotFalse($claimsJson);
        $first->claims = $claimsJson;
        $first->prompt_version = '1.0.0';
        $first->prompt_interference = 0;
        $first->interference_note = null;
        $first->key_id = null;
        $first->key_name = null;
        $first->created_at = '2026-01-01 00:00:00';
        $first->updated_at = '2026-01-01 00:00:00';
        $first->insert();

        $second = new AiFactCheck($this->pdo);
        foreach (['content_type' => 'post', 'content_id' => 1, 'content_title' => 'T', 'content_slug' => 't', 'summary' => 's', 'overall_verdict' => 'refuted', 'prompt_version' => '1.0.0'] as $k => $v) {
            $second->$k = $v;
        }
        $second->claim_count = 0;
        $second->claims = '[]';
        $second->prompt_interference = 0;
        $second->created_at = '2026-01-02 00:00:00';
        $second->updated_at = '2026-01-02 00:00:00';
        $second->insert();

        self::assertNotNull((new AiFactCheck($this->pdo))->findById((int) $first->id));

        $latest = (new AiFactCheck($this->pdo))->latestForContent('post', 1);
        self::assertNotNull($latest);
        self::assertSame((int) $second->id, (int) $latest->id);
        self::assertSame([['text' => 'x']], $first->claimsArray());

        $bad = new AiFactCheck($this->pdo);
        $bad->claims = 'not-json';
        self::assertSame([], $bad->claimsArray());

        $mixed = new AiFactCheck($this->pdo);
        $mixedJson = json_encode([['text' => 'ok'], 'skip-me', 42]);
        self::assertNotFalse($mixedJson);
        $mixed->claims = $mixedJson;
        self::assertSame([['text' => 'ok']], $mixed->claimsArray());
    }

    public function testFindersRunOnFreshInstances(): void
    {
        $a = $this->insertKey('ha');
        $b = $this->insertKey('hb');

        $model = new AiKey($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame((int) $a->id, (int) $first->id);

        $hashA = $model->findByHash('ha');
        $hashB = $model->findByHash('hb');
        self::assertNotNull($hashA);
        self::assertNotNull($hashB);
        self::assertNotSame($hashA, $hashB);
        self::assertSame('ha', (string) $hashA->key_hash);
        self::assertNull($model->findByHash('missing'));
    }

    public function testFactCheckFindByIdRunsOnFreshInstance(): void
    {
        $a = $this->insertFactCheck('supported');
        $b = $this->insertFactCheck('refuted');

        $model = new AiFactCheck($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame('supported', (string) $first->overall_verdict);
        self::assertNull($model->findById(99999));
    }

    private function insertFactCheck(string $verdict): AiFactCheck
    {
        $record = new AiFactCheck($this->pdo);
        $record->content_type = 'post';
        $record->content_id = 1;
        $record->content_title = 'T';
        $record->content_slug = 't';
        $record->content_updated_at = null;
        $record->summary = 's';
        $record->overall_verdict = $verdict;
        $record->claim_count = 0;
        $record->claims = '[]';
        $record->prompt_version = '1.0.0';
        $record->prompt_interference = 0;
        $record->interference_note = null;
        $record->key_id = null;
        $record->key_name = null;
        $record->created_at = '2026-01-01 00:00:00';
        $record->updated_at = '2026-01-01 00:00:00';
        $record->insert();

        return $record;
    }
}
