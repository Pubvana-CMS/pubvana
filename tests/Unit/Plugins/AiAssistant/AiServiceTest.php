<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Models\AiKey;
use Pubvana\Plugins\AiAssistant\Models\AiKeyGrant;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AiService keys, auth, grants, logs, settings, catalog, serializers.
 */
#[CoversClass(AiService::class)]
final class AiServiceTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    private array $settingsData = [];

    /** @var string|null */
    private ?string $envBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
        $this->settingsData = [];
        $this->envBackup = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
        $_ENV['SESSION_ENCRYPTION_KEY'] = 'test-encryption-key-for-ai-service';
        putenv('SESSION_ENCRYPTION_KEY=test-encryption-key-for-ai-service');
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

    /**
     * @param array<string, mixed> $config
     */
    private function service(array $config = []): AiService
    {
        $test = $this;
        $app = $this->app([
            'settings' => static fn (): object => new class ($test) {
                public function __construct(private AiServiceTest $test)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->test->settingsGet($key, $default);
                }

                public function set(string $key, mixed $value): void
                {
                    $this->test->settingsSet($key, $value);
                }
            },
            'aiMarkdown' => static fn (): MarkdownService => new MarkdownService(),
        ]);

        return new AiService($this->pdo, $app, array_merge([
            'route_prefix' => '/api/ai',
            'key_prefix' => 'pvai1_',
            'max_failed_attempts' => 3,
            'block_minutes' => 30,
            'log_limit' => 200,
        ], $config));
    }

    public function settingsGet(string $key, mixed $default = null): mixed
    {
        return $this->settingsData[$key] ?? $default;
    }

    public function settingsSet(string $key, mixed $value): void
    {
        $this->settingsData[$key] = $value;
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
    }

    public function testCreateKeyRevealsPlainOnceAndHashes(): void
    {
        $service = $this->service();

        $created = $service->createKey('  My Key  ');
        self::assertSame('My Key', (string) $created['key']->name);
        self::assertStringStartsWith('pvai1_', $created['plain']);
        self::assertSame(substr($created['plain'], 0, 16), (string) $created['key']->key_prefix);
        self::assertNotSame($created['plain'], (string) $created['key']->key_hash);

        $blank = $service->createKey('   ');
        self::assertSame('Untitled key', (string) $blank['key']->name);

        $long = $service->createKey(str_repeat('n', 200));
        self::assertSame(120, mb_strlen((string) $long['key']->name));
    }

    public function testListFindToggleDelete(): void
    {
        $service = $this->service();

        self::assertNull($service->findKey(99999));
        self::assertFalse($service->toggle(99999));
        self::assertFalse($service->deleteKey(99999));
        self::assertFalse($service->updateGrants(99999, ['posts.read']));

        $created = $service->createKey('One');
        $id = (int) $created['key']->id;

        self::assertNotNull($service->findKey($id));
        $service->updateGrants($id, ['posts.read']);

        $list = $service->listKeys();
        self::assertCount(1, $list);
        self::assertSame(['posts.read'], $list[0]['grants']);
        self::assertTrue($list[0]['enabled']);

        self::assertTrue($service->toggle($id));
        self::assertFalse($service->findKey($id)->isEnabled());
        self::assertTrue($service->toggle($id));
        self::assertTrue($service->findKey($id)->isEnabled());

        self::assertTrue($service->deleteKey($id));
        self::assertNull($service->findKey($id));
        self::assertSame([], (new AiKeyGrant($this->pdo))->permissionsFor($id));
    }

    public function testUpdateGrantsFiltersUnknown(): void
    {
        $service = $this->service();
        $created = $service->createKey('G');
        $id = (int) $created['key']->id;

        self::assertTrue($service->updateGrants($id, ['posts.read', 'bogus.grant', 'pages.read']));
        self::assertSame(['pages.read', 'posts.read'], (new AiKeyGrant($this->pdo))->permissionsFor($id));
    }

    public function testAuthenticate(): void
    {
        $service = $this->service();
        $created = $service->createKey('Auth');
        $plain = $created['plain'];

        $bad = $service->authenticate('pvai1_wrong');
        self::assertNull($bad['key']);
        self::assertSame('Invalid API key.', $bad['error']);

        $ok = $service->authenticate($plain);
        self::assertNotNull($ok['key']);
        self::assertNull($ok['error']);
        $used = $service->findKey((int) $created['key']->id);
        self::assertNotNull($used);
        self::assertNotNull($used->last_used_at);
    }

    public function testAuthenticateDisabledCountsAndBlocks(): void
    {
        $service = $this->service(['max_failed_attempts' => 2, 'block_minutes' => 30]);
        $created = $service->createKey('D');
        $id = (int) $created['key']->id;
        $service->toggle($id);

        $first = $service->authenticate($created['plain']);
        self::assertNull($first['key']);
        self::assertSame('This API key is disabled.', $first['error']);
        self::assertArrayHasKey('key_known', $first);

        $second = $service->authenticate($created['plain']);
        self::assertNull($second['key']);

        $key = $service->findKey($id);
        self::assertNotNull($key);
        self::assertTrue($key->isBlocked());
    }

    public function testAuthenticateBlockedKey(): void
    {
        $service = $this->service();
        $created = $service->createKey('B');
        $id = (int) $created['key']->id;

        $this->pdo->exec("UPDATE ai_keys SET blocked_until = '2999-01-01 00:00:00' WHERE id = {$id}");

        $result = $service->authenticate($created['plain']);
        self::assertNull($result['key']);
        self::assertStringContainsString('blocked', (string) $result['error']);
    }

    public function testHasGrantUsesCache(): void
    {
        $service = $this->service();
        $created = $service->createKey('H');
        $id = (int) $created['key']->id;
        $key = $service->findKey($id);
        self::assertNotNull($key);

        self::assertFalse($service->hasGrant($key, 'posts.read'));

        // updateGrants() invalidates the per-instance cache, so the same
        // instance sees the new grant without a rebuild.
        $service->updateGrants($id, ['posts.read']);
        self::assertTrue($service->hasGrant($key, 'posts.read'));
        $fresh = $this->service()->findKey($id);
        self::assertNotNull($fresh);
        self::assertTrue($this->service()->hasGrant($fresh, 'posts.read'));
    }

    public function testLogAndRecentLogs(): void
    {
        $service = $this->service();
        $created = $service->createKey('L');
        $key = $service->findKey((int) $created['key']->id);
        self::assertNotNull($key);

        $service->log('GET', '/api/ai/posts', 'ok', $key, 'post', 5, 'fine');
        $service->log('POST', '/api/ai/nope', 'bogus-outcome');
        $service->log('GET', '/api/ai/x', 'denied', null, null, null, 'no grant');

        $logs = $service->recentLogs(10);
        self::assertCount(3, $logs);
        self::assertSame('denied', $logs[0]['outcome']);
        self::assertSame('error', $logs[1]['outcome']);
        self::assertSame('ok', $logs[2]['outcome']);
        self::assertSame(5, $logs[2]['entity_id']);
        self::assertSame('post', $logs[2]['entity_type']);
    }

    public function testDefaultAuthorId(): void
    {
        $service = $this->service();

        self::assertSame(1, $service->defaultAuthorId());

        $service->setDefaultAuthorId(7);
        self::assertSame(7, $service->defaultAuthorId());

        $service->setDefaultAuthorId(0);
        self::assertSame(1, $service->defaultAuthorId());
    }

    public function testHelpCatalogAndGroups(): void
    {
        $service = $this->service();

        $catalog = $service->helpCatalog();
        self::assertArrayHasKey('posts.read', $catalog);
        self::assertArrayHasKey('pages.publish', $catalog);
        self::assertStringContainsString('/api/ai', $catalog['posts.read']['path']);

        $groups = $service->helpGroups();
        self::assertArrayHasKey('posts', $groups);
        self::assertArrayHasKey('posts.read', $groups['posts']);
    }

    public function testSerializers(): void
    {
        $service = $this->service();

        $comment = new \Pubvana\Plugins\Comments\Models\Comment();
        $comment->id = 3;
        $comment->commentable_type = 'blog';
        $comment->commentable_id = 9;
        $comment->parent_id = null;
        $comment->user_id = null;
        $comment->guest_name = 'Ada';
        $comment->body = 'Hi';
        $comment->status = 'approved';
        $comment->created_at = '2026-01-01 00:00:00';

        $row = $service->serializeComment($comment);
        self::assertSame('guest', $row['author_type']);
        self::assertSame('Ada', $row['author_name']);

        $redirect = new \Pubvana\Plugins\Redirects\Models\Redirect();
        $redirect->id = 4;
        $redirect->source_path = '/old';
        $redirect->target_url = '/new';
        $redirect->status_code = 301;
        $redirect->enabled = 1;
        $redirect->notes = null;
        $redirect->hit_count = 2;
        $redirect->last_hit_at = null;
        $redirect->created_at = '2026-01-01 00:00:00';
        $redirect->updated_at = '2026-01-01 00:00:00';

        $rrow = $service->serializeRedirect($redirect);
        self::assertSame('/old', $rrow['source_path']);
        self::assertTrue($rrow['enabled']);

        $item = new \Pubvana\Models\NavigationItem();
        $item->id = 5;
        $item->label = 'Home';
        $item->url = '/';
        $item->parent_id = null;
        $item->sort_order = 1;
        $item->target = '_self';
        $item->nav_group = 'main';
        $item->updated_at = null;

        $nrow = $service->serializeNavigationItem($item);
        self::assertSame('Home', $nrow['label']);
    }

    public function testSerializePostAndPage(): void
    {
        $service = $this->service();

        $post = new \Pubvana\Plugins\Blog\Models\Post();
        $post->id = 1;
        $post->title = 'T';
        $post->slug = 't';
        $post->content = '<p>Hi</p>';
        $post->excerpt = null;
        $post->status = 'draft';
        $post->published_at = null;
        $post->author_id = 1;
        $post->views = 0;
        $post->is_featured = 0;
        $post->allow_comments = 1;
        $post->ai_generated = 0;
        $post->created_at = '2026-01-01 00:00:00';
        $post->updated_at = '2026-01-01 00:00:00';

        $prow = $service->serializePost($post);
        self::assertSame('T', $prow['title']);
        self::assertStringContainsString('Hi', (string) $prow['content']);
        self::assertNull($prow['seo']);

        $page = new \Pubvana\Plugins\Pages\Models\Page();
        $page->id = 2;
        $page->title = 'P';
        $page->slug = 'p';
        $page->content = '<p>Yo</p>';
        $page->status = 'published';
        $page->allow_comments = 0;
        $page->ai_generated = 0;
        $page->created_by = 1;
        $page->created_at = '2026-01-01 00:00:00';
        $page->updated_at = '2026-01-01 00:00:00';

        $grow = $service->serializePage($page);
        self::assertSame('P', $grow['title']);
        self::assertFalse($grow['allow_comments']);
    }
}
