<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Comments\Models\Comment;
use Pubvana\Plugins\Comments\Services\CommentService;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginView;
use Pubvana\Services\RateLimiter;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * CommentService coverage: settings, CRUD, trees, gating, hosts, blocks.
 *
 * Captcha is off by default (no provider configured). Rate limiting uses
 * a temp directory. Host discovery uses the real ExtensionRegistry.
 */
#[CoversClass(CommentService::class)]
final class CommentServiceCoverageTest extends TestCase
{
    private PDO $pdo;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->cacheDir = sys_get_temp_dir() . '/pv-comments-cov-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $query
     * @return Engine<object>
     */
    private function buildApp(array $query = []): Engine
    {
        $app = $this->app([
            'db' => fn (): PDO => $this->pdo,
            'settings' => $this->singleton(fn (): SettingsService => new SettingsService(\Flight::app())),
            'adext' => $this->singleton(fn (): ExtensionRegistry => new ExtensionRegistry()),
            'captcha' => $this->singleton(fn (): CaptchaService => new CaptchaService(\Flight::app())),
            'request' => static fn (): object => new class ($query) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $q */
                public function __construct(array $q)
                {
                    $this->data = new Collection([]);
                    $this->query = new Collection($q);
                }
            },
            'pluginLoader' => static fn (): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/comments';
                }
            },
            'auth' => static fn (): object => new class {
                public function id(): null
                {
                    return null;
                }
            },
            'view' => static fn (): object => new \stdClass(),
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    /** @param Engine<object> $app */
    private function service(Engine $app): CommentService
    {
        $service = new CommentService($this->pdo, $app);
        $service->setRateLimiter(new RateLimiter($this->cacheDir));

        return $service;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        static $call = 0;
        $call++;

        return array_merge([
            'commentable_type' => 'blog',
            'commentable_id' => 1,
            'body' => 'Hello world',
            'ip_address' => '10.9.0.' . $call,
            'guest_name' => 'Ada',
        ], $extra);
    }

    /** @param Engine<object> $app */
    private function registerHost(Engine $app, string $key, string $type, int $id = 1): void
    {
        $app->adext()->register('comments.host', 'content', $key, [
            'label' => $key,
            'callable' => static fn (): array => [[
                'type' => $type,
                'id' => $id,
                'title' => 'Post ' . $id,
                'url' => '/blog/post-' . $id,
                'allow_comments' => true,
            ]],
        ]);
    }

    private function setCreatedAt(int $id, string $when): void
    {
        $stmt = $this->pdo->prepare('UPDATE comments SET created_at = ? WHERE id = ?');
        self::assertNotFalse($stmt);
        $stmt->execute([$when, $id]);
    }

    private function singleton(callable $provider): callable
    {
        return static function () use ($provider): mixed {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }

            return $instance;
        };
    }

    public function testSettingsHelpers(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        self::assertTrue($service->isEnabled());
        self::assertFalse($service->allowsGuestComments());
        self::assertSame('pending', $service->defaultStatus());
        self::assertSame(3, $service->maxNestingDepth());
        self::assertSame(30, $service->rateLimitSeconds());

        $app->settings()->set('Comments.comments_enabled', '0');
        $app->settings()->set('Comments.allow_guest_comments', '1');
        $app->settings()->set('Comments.default_status', 'bogus');
        $app->settings()->set('Comments.max_nesting_depth', '0');
        $app->settings()->set('Comments.rate_limit_seconds', '-5');

        self::assertFalse($service->isEnabled());
        self::assertTrue($service->allowsGuestComments());
        self::assertSame('pending', $service->defaultStatus());
        self::assertSame(1, $service->maxNestingDepth());
        self::assertSame(0, $service->rateLimitSeconds());

        $app->settings()->set('Comments.default_status', 'approved');
        self::assertSame('approved', $service->defaultStatus());
    }

    public function testCreateUsesDefaultStatusAndStripsCaptchaToken(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $comment = $service->create($this->payload(['captcha_token' => 'tok']));

        self::assertSame('pending', (string) $comment->status);
        $stmt = $this->pdo->query('SELECT * FROM comments LIMIT 1');
        self::assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertArrayNotHasKey('captcha_token', $row);
    }

    public function testCreatePurifiesBody(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $comment = $service->create($this->payload(['body' => '<p>Hi</p><script>evil()</script>']));

        self::assertStringNotContainsString('<script>', (string) $comment->body);
        self::assertStringContainsString('Hi', (string) $comment->body);
    }

    public function testCreateRejectsBeyondNestingDepth(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Comments.max_nesting_depth', '1');
        $service = $this->service($app);

        $root = $service->create($this->payload(['status' => 'approved']));
        $child = $service->create($this->payload(['parent_id' => (int) $root->id, 'status' => 'approved']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum comment nesting depth reached.');
        $service->create($this->payload(['parent_id' => (int) $child->id]));
    }

    public function testCreateRejectsUnusableReplyTargets(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $pending = $service->create($this->payload(['body' => 'pending parent']));
        $approved = $service->create($this->payload(['body' => 'approved parent', 'status' => 'approved']));
        $rejected = $service->create($this->payload(['body' => 'rejected parent', 'status' => 'approved']));
        $service->reject((int) $rejected->id);

        // Nonexistent parent.
        $this->assertReplyRejected($service, 999999);

        // Pending and rejected parents are not visible, so they take no replies.
        $this->assertReplyRejected($service, (int) $pending->id);
        $this->assertReplyRejected($service, (int) $rejected->id);

        // A parent from another content item.
        $this->assertReplyRejected($service, (int) $approved->id, 'blog', 2);

        // An approved, same-item parent is accepted and nests.
        $reply = $service->create($this->payload([
            'body' => 'valid reply',
            'parent_id' => (int) $approved->id,
            'status' => 'approved',
        ]));
        self::assertSame((int) $approved->id, (int) $reply->parent_id);

        // Removing the parent promotes the reply to a root rather than dropping it.
        $service->delete((int) $approved->id);
        $tree = $service->findForContent('blog', 1);
        self::assertSame('valid reply', (string) $tree[0]->body);
    }

    /**
     * @param CommentService $service
     */
    private function assertReplyRejected(CommentService $service, int $parentId, string $type = 'blog', int $itemId = 1): void
    {
        try {
            $service->create($this->payload([
                'body' => 'reply',
                'parent_id' => $parentId,
                'commentable_type' => $type,
                'commentable_id' => $itemId,
            ]));
            self::fail('an unusable reply target must be rejected');
        } catch (\InvalidArgumentException $e) {
            self::assertSame('The comment you are replying to is not available.', $e->getMessage());
        }
    }

    public function testCreateRejectsAClosedOrUnknownHostItem(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog', 1);
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);

        // An id the host does not list: no such item.
        try {
            $service->create($this->payload(['commentable_id' => 2]));
            self::fail('an item the host does not list must be rejected');
        } catch (\InvalidArgumentException $e) {
            self::assertSame('Comments are closed for this item.', $e->getMessage());
        }

        // A listed item with comments turned off.
        $app->adext()->register('comments.host', 'content', 'pubvana.closed', [
            'label' => 'Closed',
            'callable' => static fn (): array => [[
                'type' => 'closed',
                'id' => 5,
                'title' => 'Closed',
                'url' => '/closed/5',
                'allow_comments' => false,
            ]],
        ]);
        $service->setHostEnabled('pubvana.closed', true);
        $service = $this->service($app);

        try {
            $service->create($this->payload(['commentable_type' => 'closed', 'commentable_id' => 5]));
            self::fail('a closed item must be rejected');
        } catch (\InvalidArgumentException $e) {
            self::assertSame('Comments are closed for this item.', $e->getMessage());
        }

        // A listed, open item still accepts.
        $comment = $service->create($this->payload(['commentable_type' => 'blog', 'commentable_id' => 1]));
        self::assertSame(1, (int) $comment->commentable_id);
    }

    public function testCreateRejectsAnUnsafeGuestWebsite(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Website must be a full URL');
        $service->create($this->payload(['guest_website' => 'javascript:alert(1)']));
    }

    public function testCreateAcceptsAndTrimsASafeGuestWebsite(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $comment = $service->create($this->payload(['guest_website' => '  https://example.com/ada  ']));

        self::assertSame('https://example.com/ada', (string) $comment->guest_website);
    }

    public function testCreateDropsAnEmptyGuestWebsiteInsteadOfStoringBlank(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $comment = $service->create($this->payload(['guest_website' => '   ']));

        self::assertSame('', (string) $comment->guest_website);
    }

    public function testCreateRecordWhitelistDropsUnknownColumns(): void
    {
        $model = new Comment($this->pdo);
        $comment = $model->createRecord([
            'commentable_type' => 'blog',
            'commentable_id' => 1,
            'body' => 'x',
            'status' => 'pending',
            'bogus_column' => 'injected',
        ]);

        // The unknown key is ignored rather than assigned: it can never
        // reach the insert as a raw identifier.
        self::assertSame('x', (string) $comment->body);
        self::assertSame('pending', (string) $comment->status);
    }

    public function testModerationLifecycle(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        self::assertNull($service->find(99999));
        self::assertNull($service->approve(99999));
        self::assertNull($service->reject(99999));
        self::assertFalse($service->delete(99999));

        $comment = $service->create($this->payload());
        $id = (int) $comment->id;

        self::assertNotNull($service->find($id));
        self::assertSame(1, $service->countByStatus('pending'));

        $approved = $service->approve($id);
        self::assertNotNull($approved);
        self::assertSame('approved', (string) $approved->status);

        $rejected = $service->reject($id);
        self::assertNotNull($rejected);
        self::assertSame('rejected', (string) $rejected->status);

        self::assertSame(1, $service->countByStatus());
        self::assertCount(1, $service->list(1, 25));
        self::assertCount(1, $service->list(1, 25, 'rejected'));
        self::assertCount(0, $service->list(1, 25, 'approved'));

        self::assertTrue($service->delete($id));
        self::assertNull($service->find($id));
    }

    public function testFindForContentBuildsTreeAndPromotesOrphans(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $root = $service->create($this->payload(['body' => 'root', 'status' => 'approved']));
        $child = $service->create($this->payload(['body' => 'child', 'status' => 'approved', 'parent_id' => (int) $root->id]));
        $service->create($this->payload(['body' => 'hidden', 'status' => 'pending']));

        $tree = $service->findForContent('blog', 1);
        self::assertCount(1, $tree);
        self::assertSame('root', (string) $tree[0]->body);
        self::assertCount(1, $tree[0]->children);
        self::assertSame((int) $child->id, (int) $tree[0]->children[0]->id);

        // Orphaned child (parent deleted) renders at the top level.
        $service->delete((int) $root->id);
        $tree = $service->findForContent('blog', 1);
        self::assertCount(1, $tree);
        self::assertSame('child', (string) $tree[0]->body);
    }

    public function testDataForGating(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');

        // System disabled.
        $app->settings()->set('Comments.comments_enabled', '0');
        self::assertSame([], $service->dataFor('blog', 1));
        $app->settings()->set('Comments.comments_enabled', '1');

        // Host not opted in.
        self::assertSame([], $service->dataFor('blog', 1));

        // Opt in, item closed: the thread and a closed notice render, no form.
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);
        $closed = $service->dataFor('blog', 1, false);
        self::assertTrue($closed['comments_enabled']);
        self::assertFalse($closed['comments_open']);
        self::assertTrue($closed['comments_closed']);

        // Open thread for guests off: form closed but data present.
        $data = $service->dataFor('blog', 1);
        self::assertTrue($data['comments_enabled']);
        self::assertFalse($data['comments_open']);
        self::assertFalse($data['comments_closed']);
        self::assertSame('/comments/blog/1', $data['comment_post_url']);

        // Guests allowed: form open.
        $app->settings()->set('Comments.allow_guest_comments', '1');
        $data = $service->dataFor('blog', 1);
        self::assertTrue($data['comments_open']);
    }

    public function testRenderGating(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');

        self::assertSame('', $service->render('blog', 1));

        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);
        // stdClass view is not a PluginView, render bails to ''.
        self::assertSame('', $service->render('blog', 1));

        $app->settings()->set('Comments.comments_enabled', '0');
        self::assertSame('', $service->render('blog', 1));
    }

    public function testRenderOutputsNestingControls(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');

        $service->setHostEnabled('pubvana.blog', true);
        $app->settings()->set('Comments.allow_guest_comments', '1');
        $service = $this->service($app);

        $root = $service->create($this->payload(['status' => 'approved']));
        $service->create($this->payload([
            'status' => 'approved',
            'parent_id' => (int) $root->id,
        ]));

        $view = new PluginView(PROJECT_ROOT . '/app/Views');
        $view->addPluginPath('pubvana/comments', PROJECT_ROOT . '/plugins/Comments/Views');
        $app->map('view', static fn (): PluginView => $view);

        $html = $service->render('blog', 1);

        // The approved child renders one level deep and every comment carries
        // a reply control bound to its own id, with the form field to submit it.
        self::assertStringContainsString('pv-comment-depth-1', $html);
        self::assertStringContainsString('class="pv-comment-reply"', $html);
        self::assertStringContainsString('data-parent-id="' . (int) $root->id . '"', $html);
        self::assertStringContainsString('name="parent_id"', $html);

        // A per-item opt-out renders the thread with a closed notice and no
        // reply controls or form.
        $closedHtml = $service->render('blog', 1, false);
        self::assertStringContainsString('Comments are closed.', $closedHtml);
        self::assertStringNotContainsString('class="pv-comment-reply"', $closedHtml);
        self::assertStringNotContainsString('name="parent_id"', $closedHtml);
    }

    public function testUserAuthorsResolveInOneBatch(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $this->insertUser(1, 'ada');
        $this->insertUser(2, 'bob');
        $this->insertUser(3, 'gone', '2026-01-01 00:00:00');

        $root = $service->create($this->payload([
            'status' => 'approved', 'user_id' => 1, 'guest_name' => null, 'body' => 'from ada',
        ]));
        $service->create($this->payload([
            'status' => 'approved', 'user_id' => 2, 'guest_name' => null, 'body' => 'from bob',
            'parent_id' => (int) $root->id,
        ]));
        // A comment whose user row is gone: falls back, does not error.
        $service->create($this->payload([
            'status' => 'approved', 'user_id' => 999, 'guest_name' => null, 'body' => 'from nobody',
        ]));
        // A comment whose user row is soft-deleted: excluded the same way.
        $service->create($this->payload([
            'status' => 'approved', 'user_id' => 3, 'guest_name' => null, 'body' => 'from gone',
        ]));

        $tree = $service->findForContent('blog', 1);
        $flat = $this->invoke($service, 'flattenComments', [$tree, 0, $this->invoke($service, 'authorNames', [$tree])]);

        $byBody = [];
        foreach ($flat as $row) {
            $byBody[(string) $row['body']] = (string) $row['author'];
        }

        self::assertSame('ada', $byBody['from ada']);
        self::assertSame('bob', $byBody['from bob']);
        self::assertSame('Unknown', $byBody['from nobody']);
        self::assertSame('Unknown', $byBody['from gone']);
    }

    public function testRecentCommentsBlockResolvesUserAuthors(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->insertUser(7, 'carol');

        $service->create($this->payload([
            'status' => 'approved', 'user_id' => 7, 'guest_name' => null,
        ]));

        $block = $service->recentCommentsBlock([]);
        self::assertSame('carol', $block['comments'][0]['author']);
    }

    private function insertUser(int $id, string $username, ?string $deletedAt = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, username, active, created_at, deleted_at) VALUES (?, ?, 1, ?, ?)'
        );
        self::assertNotFalse($stmt);
        $stmt->execute([$id, $username, (new \DateTimeImmutable())->format('Y-m-d H:i:s'), $deletedAt]);
    }

    public function testHostRegistry(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog', 7);
        $app->adext()->register('comments.host', 'content', 'pubvana.broken', [
            'label' => 'Broken',
            'callable' => static fn (): string => 'nope',
        ]);

        self::assertFalse($service->isHostEnabled('pubvana.blog'));
        self::assertSame([], $service->hostItems());
        self::assertNull($service->hostItem('blog', 7));
        // Type-to-host mapping ignores opt-in state; gating happens in hostItems()/enabledTypes().
        self::assertSame('pubvana.blog', $service->hostKeyForType('blog'));
        self::assertSame([], $service->enabledTypes());
        self::assertFalse($service->isTypeEnabled('blog'));

        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);
        self::assertTrue($service->isHostEnabled('pubvana.blog'));

        $items = $service->hostItems();
        self::assertCount(1, $items);
        self::assertSame('Post 7', $items[0]['title']);
        self::assertSame('/blog/post-7', $items[0]['url']);

        self::assertNotNull($service->hostItem('blog', 7));
        self::assertNull($service->hostItem('blog', 999));
        self::assertSame('pubvana.blog', $service->hostKeyForType('blog'));
        self::assertSame(['blog'], $service->enabledTypes());
        self::assertTrue($service->isTypeEnabled('blog'));

        $hosts = $service->hosts();
        self::assertArrayHasKey('pubvana.blog', $hosts);
        self::assertArrayHasKey('pubvana.broken', $hosts);

        $decorated = $service->enabledHosts(true);
        self::assertTrue($decorated['pubvana.blog']['enabled']);
        self::assertFalse($decorated['pubvana.broken']['enabled']);

        $onlyOn = $service->enabledHosts(false);
        self::assertArrayHasKey('pubvana.blog', $onlyOn);
        self::assertArrayNotHasKey('pubvana.broken', $onlyOn);

        $service->setHostEnabled('pubvana.blog', false);
        $service = $this->service($app);
        self::assertFalse($service->isHostEnabled('pubvana.blog'));
        self::assertSame([], $service->enabledTypes());
    }

    public function testCountsByHost(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);

        $service->create($this->payload(['status' => 'approved']));
        $service->create($this->payload(['status' => 'pending']));

        $counts = $service->countsByHost();
        self::assertSame(2, $counts['pubvana.blog']['comments']);
        self::assertSame(1, $counts['pubvana.blog']['pending']);
    }

    public function testRecentCommentsBlock(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);

        $ada = $service->create($this->payload(['guest_name' => 'Ada', 'status' => 'approved']));
        $anon = $service->create($this->payload(['guest_name' => '', 'status' => 'approved']));
        $service->create($this->payload(['guest_name' => 'Nope', 'status' => 'pending']));
        $this->setCreatedAt((int) $ada->id, '2026-01-01 00:00:00');
        $this->setCreatedAt((int) $anon->id, '2026-01-02 00:00:00');

        $block = $service->recentCommentsBlock([]);
        self::assertSame('Recent Comments', $block['title']);
        self::assertCount(2, $block['comments']);
        self::assertSame('Anonymous', $block['comments'][0]['author']);
        self::assertSame('Post 1', $block['comments'][0]['post_title']);
        self::assertSame('/blog/post-1', $block['comments'][0]['url']);

        $custom = $service->recentCommentsBlock(['title' => 'Latest', 'count' => 1]);
        self::assertSame('Latest', $custom['title']);
        self::assertCount(1, $custom['comments']);

        $fallback = $service->recentCommentsBlock(['count' => 0]);
        self::assertCount(2, $fallback['comments']);
    }

    public function testRecentCommentsBlockFallsBackWithoutHost(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);

        $service->create($this->payload(['guest_name' => 'Ada', 'status' => 'approved']));

        $block = $service->recentCommentsBlock([]);
        self::assertCount(1, $block['comments']);
        self::assertSame('Blog #1', $block['comments'][0]['post_title']);
        self::assertSame('#', $block['comments'][0]['url']);
    }

    public function testFlattenIncludesDepthAndCounts(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);
        $app->settings()->set('Comments.allow_guest_comments', '1');

        $root = $service->create($this->payload(['body' => 'root', 'status' => 'approved']));
        $service->create($this->payload(['body' => 'child', 'status' => 'approved', 'parent_id' => (int) $root->id]));

        $data = $service->dataFor('blog', 1);
        self::assertCount(2, $data['comments']);
        self::assertSame(0, $data['comments'][0]['depth']);
        self::assertSame(1, $data['comments'][1]['depth']);
        self::assertSame(1, $data['comments'][0]['children']);
        self::assertSame('Ada', $data['comments'][0]['author']);
    }

    public function testUserCommentsShowUnknownWithoutUserRow(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $this->registerHost($app, 'pubvana.blog', 'blog');
        $service->setHostEnabled('pubvana.blog', true);
        $service = $this->service($app);

        $model = new Comment($this->pdo);
        $model->createRecord([
            'commentable_type' => 'blog',
            'commentable_id' => 1,
            'user_id' => 424242,
            'body' => 'from user',
            'status' => 'approved',
        ]);

        $data = $service->dataFor('blog', 1);
        self::assertCount(1, $data['comments']);
        self::assertSame('Unknown', $data['comments'][0]['author']);

        $block = $service->recentCommentsBlock([]);
        self::assertSame('Unknown', $block['comments'][0]['author']);
    }
}
