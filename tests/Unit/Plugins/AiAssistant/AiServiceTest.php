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

    /** @var array<int, array{string, int, array<string, mixed>}> */
    private array $seoCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
        $this->settingsData = [];
        $this->seoCalls = [];
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
        self::assertArrayHasKey('brokenlinks.read', $catalog);
        self::assertArrayHasKey('brokenlinks.scan', $catalog);
        self::assertArrayHasKey('brokenlinks.recheck', $catalog);
        self::assertArrayHasKey('brokenlinks.dismiss', $catalog);
        self::assertArrayHasKey('analytics.read', $catalog);
        self::assertStringContainsString('/api/ai', $catalog['posts.read']['path']);

        $groups = $service->helpGroups();
        self::assertArrayHasKey('posts', $groups);
        self::assertArrayHasKey('posts.read', $groups['posts']);
        self::assertArrayHasKey('brokenlinks', $groups);
        self::assertArrayHasKey('brokenlinks.read', $groups['brokenlinks']);
        self::assertArrayHasKey('analytics', $groups);
        self::assertArrayHasKey('analytics.read', $groups['analytics']);
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

        $entry = new \Pubvana\Plugins\BrokenLinks\Models\BrokenLink();
        $entry->id = 6;
        $entry->source_type = 'post';
        $entry->source_id = 12;
        $entry->source_title = 'Hello';
        $entry->url = 'https://example.com/x';
        $entry->http_status = 404;
        $entry->error_message = null;
        $entry->dismissed = 0;
        $entry->last_checked_at = '2026-01-01 00:00:00';

        $brow = $service->serializeBrokenLink($entry);
        self::assertSame(6, $brow['id']);
        self::assertSame('post', $brow['source_type']);
        self::assertSame(12, $brow['source_id']);
        self::assertSame('Hello', $brow['source_title']);
        self::assertSame('https://example.com/x', $brow['url']);
        self::assertSame(404, $brow['http_status']);
        self::assertNull($brow['error_message']);
        self::assertFalse($brow['dismissed']);
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

    // -----------------------------------------------------------------
    // Content helpers
    // -----------------------------------------------------------------

    public function testResolveContentPrefersMarkdown(): void
    {
        $service = $this->service();

        $html = $service->resolveContent([
            'content_md' => '# Hello',
            'content'    => '<h1>Old HTML</h1>',
        ]);

        self::assertStringContainsString('<h1>', (string) $html);
        self::assertStringContainsString('Hello', (string) $html);
    }

    public function testResolveContentFallsBackToHtml(): void
    {
        $service = $this->service();

        self::assertSame('<p>Body</p>', $service->resolveContent(['content' => '<p>Body</p>']));
    }

    public function testResolveContentBlankMeansClear(): void
    {
        $service = $this->service();

        self::assertSame('', $service->resolveContent(['content_md' => '  ']));
        self::assertSame('', $service->resolveContent(['content' => '']));
    }

    public function testResolveContentMissingReturnsNull(): void
    {
        $service = $this->service();

        self::assertNull($service->resolveContent(['title' => 'T']));
        self::assertNull($service->resolveContent([]));
    }

    public function testCategoryIds(): void
    {
        $service = $this->service();

        self::assertSame([], $service->categoryIds([]));
        self::assertSame([], $service->categoryIds(null));
        self::assertSame([], $service->categoryIds('nope'));
        self::assertSame([7, 3], $service->categoryIds([7, 3, 0, -2, 'x']));
        self::assertSame([5, 4], $service->categoryIds([5, 0, 4, -1]));
    }

    public function testSearchParam(): void
    {
        $service = $this->service();

        self::assertSame('hello', $service->searchParam(new \flight\util\Collection(['search' => '  hello '])));
        self::assertNull($service->searchParam(new \flight\util\Collection(['search' => '   '])));
        self::assertNull($service->searchParam(new \flight\util\Collection()));
        self::assertNull($service->searchParam(new \flight\util\Collection(['page' => 2])));
    }

    public function testDemoteGrant(): void
    {
        $service = $this->service();

        self::assertSame('posts.publish', $service->demoteGrant('posts', 'published'));
        self::assertSame('pages.publish', $service->demoteGrant('pages', 'published'));
        self::assertSame('posts.schedule', $service->demoteGrant('posts', 'scheduled'));
        self::assertSame('pages.schedule', $service->demoteGrant('pages', 'scheduled'));
        self::assertNull($service->demoteGrant('posts', 'draft'));
        self::assertNull($service->demoteGrant('pages', 'archived'));
    }

    public function testNullableString(): void
    {
        $service = $this->service();

        self::assertNull($service->nullableString(null));
        self::assertNull($service->nullableString(''));
        self::assertNull($service->nullableString('   '));
        self::assertSame('value', $service->nullableString('  value  '));
    }

    public function testSaveSeoRobotsDirectiveWhitelist(): void
    {
        $service = $this->serviceWithSeo();

        $service->saveSeo('post', 10, ['seo' => ['robots_directive' => 'madeupvalue', 'meta_title' => 'Keep']]);
        self::assertCount(1, $this->seoCalls);
        self::assertArrayNotHasKey('robots_directive', $this->seoCalls[0][2]);
        self::assertSame('Keep', $this->seoCalls[0][2]['meta_title']);

        $service->saveSeo('post', 11, ['seo' => ['robots_directive' => 'noindex, nofollow']]);
        self::assertCount(2, $this->seoCalls);
        self::assertSame('noindex, nofollow', $this->seoCalls[1][2]['robots_directive']);

        $service->saveSeo('post', 12, ['seo' => ['robots_directive' => '']]);
        self::assertCount(3, $this->seoCalls);
        self::assertTrue(array_key_exists('robots_directive', $this->seoCalls[2][2]));
        self::assertNull($this->seoCalls[2][2]['robots_directive']);
    }

    public function testSaveSeoOnlyWritesSentFields(): void
    {
        $service = $this->serviceWithSeo();

        $service->saveSeo('page', 20, ['seo' => ['meta_title' => 'T', 'focus_keywords' => 'one, two']]);
        self::assertCount(1, $this->seoCalls);
        self::assertSame(['meta_title' => 'T', 'focus_keywords' => ['one', 'two']], $this->seoCalls[0][2]);

        $service->saveSeo('page', 21, ['seo' => []]);
        $service->saveSeo('page', 22, []);
        self::assertCount(1, $this->seoCalls);
    }

    public function testSaveSeoToleratesMissingSeoPlugin(): void
    {
        $service = $this->service();

        $service->saveSeo('post', 30, ['seo' => ['meta_title' => 'T']]);
        self::assertSame([], $this->seoCalls);
    }

    public function testFindPostForApiFindsEveryStatusExcludingDeleted(): void
    {
        $service = $this->service();
        $this->createContentTables($this->pdo);
        $this->insertPost(1, 'draft-finder', 'draft', null);
        $this->insertPost(2, 'kept-published', 'published', '2026-09-01 08:00:00');
        $this->insertPost(3, 'trashed', 'published', '2026-09-01 08:00:00', '2026-09-02 08:00:00');

        $draft = $service->findPostForApi('draft-finder');
        self::assertNotNull($draft);
        self::assertSame('draft', (string) $draft->status);

        $published = $service->findPostForApi('kept-published');
        self::assertNotNull($published);
        self::assertSame('published', (string) $published->status);

        self::assertNull($service->findPostForApi('trashed'));
        self::assertNull($service->findPostForApi('missing'));
    }

    public function testFindPageForApiFindsEveryStatusExcludingDeleted(): void
    {
        $service = $this->service();
        $this->createContentTables($this->pdo);
        $this->insertPage(1, 'draft-page', 'draft');
        $this->insertPage(2, 'live-page', 'published');
        $this->insertPage(3, 'deleted-page', 'published', '2026-09-02 08:00:00');

        $draft = $service->findPageForApi('draft-page');
        self::assertNotNull($draft);
        self::assertSame('draft', (string) $draft->status);

        self::assertNotNull($service->findPageForApi('live-page'));
        self::assertNull($service->findPageForApi('deleted-page'));
        self::assertNull($service->findPageForApi('missing'));
    }

    // -----------------------------------------------------------------
    // SEO stub
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $fields
     */
    public function recordSeoCall(string $contentType, int $contentId, array $fields): void
    {
        $this->seoCalls[] = [$contentType, $contentId, $fields];
    }

    private function serviceWithSeo(): AiService
    {
        $test = $this;
        $app = $this->app([
            'aiMarkdown' => static fn (): MarkdownService => new MarkdownService(),
            'seo'        => static fn (): object => new class ($test) {
                public function __construct(private AiServiceTest $test)
                {
                }

                /**
                 * @param array<string, mixed> $fields
                 */
                public function saveMeta(string $contentType, int $contentId, array $fields): void
                {
                    $this->test->recordSeoCall($contentType, $contentId, $fields);
                }
            },
        ]);

        return new AiService($this->pdo, $app, [
            'route_prefix'  => '/api/ai',
            'key_prefix'    => 'pvai1_',
        ]);
    }

    private function createContentTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT \'draft\',
                published_at   TEXT,
                author_id      INTEGER NOT NULL DEFAULT 1,
                views          INTEGER NOT NULL DEFAULT 0,
                is_featured    INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 1,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE pages (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT,
                slug       TEXT NOT NULL,
                content    TEXT,
                status     TEXT NOT NULL DEFAULT \'draft\',
                created_by INTEGER NOT NULL DEFAULT 1,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )'
        );
    }

    private function insertPost(int $id, string $slug, string $status, ?string $publishedAt, ?string $deletedAt = null): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO posts (id, title, slug, content, excerpt, status, author_id, views,
                is_featured, allow_comments, ai_generated, created_at, updated_at, deleted_at)
             VALUES (:id, :slug, :slug, '<p>Body</p>', null, :status, 1, 0, 0, 1, 0,
                '2026-08-01 00:00:00', '2026-09-01 08:00:00', :deleted_at)"
        );
        $statement->execute([
            'id'         => $id,
            'slug'       => $slug,
            'status'     => $status,
            'deleted_at' => $deletedAt,
        ]);
        $this->pdo->prepare('UPDATE posts SET published_at = :published_at WHERE id = :id')
            ->execute(['id' => $id, 'published_at' => $publishedAt]);
    }

    private function insertPage(int $id, string $slug, string $status, ?string $deletedAt = null): void
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO pages (id, title, slug, content, status, created_by, created_at, updated_at, deleted_at)
             VALUES (:id, :slug, :slug, '<p>About</p>', :status, 1, '2026-08-01 00:00:00',
                '2026-09-01 08:00:00', :deleted_at)"
        );
        $statement->execute([
            'id'         => $id,
            'slug'       => $slug,
            'status'     => $status,
            'deleted_at' => $deletedAt,
        ]);
    }
}
