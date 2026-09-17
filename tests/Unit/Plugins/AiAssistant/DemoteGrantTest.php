<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Controllers\AiPagesApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiPostsApiController;
use Pubvana\Plugins\AiAssistant\Models\AiKey;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use flight\Engine;

/**
 * A denied grant terminates the request through fail(); in this harness it
 * throws so the denial is observable in the test.
 */
final class GrantDenied extends \RuntimeException
{
    public int $status;

    public function __construct(int $status, string $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }
}

/**
 * Harness controller: fail() throws instead of halting so denial paths can
 * be asserted. Everything else is the real gate logic.
 */
final class HarnessController extends AiPostsApiController
{
    protected function fail(int $status, string $message): never
    {
        throw new GrantDenied($status, $message);
    }
}

/**
 * Same harness over the pages controller for resolvePageStatus().
 */
final class PagesHarnessController extends AiPagesApiController
{
    protected function fail(int $status, string $message): never
    {
        throw new GrantDenied($status, $message);
    }
}

/**
 * Demote-gate tests for the AI API status resolution.
 *
 * An update-only key must not be able to tear down a live state: demoting
 * a published post to draft takes posts.publish, cancelling a scheduled
 * post takes posts.schedule, and the same published -> draft demote on a
 * page takes pages.publish. Omitting status leaves the current state
 * untouched (a bare content edit takes no publish-family grant), and
 * re-applying the current status is not a state change.
 */
#[CoversClass(AiPostsApiController::class)]
#[CoversClass(AiPagesApiController::class)]
final class DemoteGrantTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
    }

    // -----------------------------------------------------------------
    // Posts: demotion is gated
    // -----------------------------------------------------------------

    public function testUpdateOnlyKeyCannotDemotePublishedPost(): void
    {
        $post = $this->post(10, 'published', '2026-09-01 08:00:00');
        $controller = $this->controller(['posts.update']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'draft'], $post]);
            self::fail('Demoting a published post must require posts.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
            self::assertStringContainsString('posts.publish', $denied->getMessage());
        }
    }

    public function testUpdateOnlyKeyCannotCancelScheduledPost(): void
    {
        $post = $this->post(11, 'scheduled', '2026-12-01 09:00:00');
        $controller = $this->controller(['posts.update']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'draft'], $post]);
            self::fail('Cancelling a scheduled post must require posts.schedule.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
            self::assertStringContainsString('posts.schedule', $denied->getMessage());
        }
    }

    public function testPublishGrantDemotesPublishedPost(): void
    {
        $post = $this->post(10, 'published', '2026-09-01 08:00:00');
        $controller = $this->controller(['posts.update', 'posts.publish']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'draft'], $post]);

        self::assertSame(['draft', null], $result);
    }

    public function testScheduleGrantCancelsScheduledPost(): void
    {
        $post = $this->post(11, 'scheduled', '2026-12-01 09:00:00');
        $controller = $this->controller(['posts.update', 'posts.schedule']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'draft'], $post]);

        self::assertSame(['draft', null], $result);
    }

    // -----------------------------------------------------------------
    // Posts: omitted status is a no-op, not a demote
    // -----------------------------------------------------------------

    public function testOmittedStatusKeepsPublishedStateWithoutPublishGrant(): void
    {
        $post = $this->post(10, 'published', '2026-09-01 08:00:00');
        $controller = $this->controller(['posts.update']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['title' => 'Retitle'], $post]);

        self::assertSame(['published', '2026-09-01 08:00:00'], $result);
    }

    public function testOmittedStatusKeepsScheduledStateWithoutScheduleGrant(): void
    {
        $post = $this->post(11, 'scheduled', '2026-12-01 09:00:00');
        $controller = $this->controller(['posts.update']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['title' => 'Retitle'], $post]);

        self::assertSame(['scheduled', '2026-12-01 09:00:00'], $result);
    }

    public function testReApplyingPublishedWithoutGrantIsNotATransition(): void
    {
        $post = $this->post(10, 'published', '2026-09-01 08:00:00');
        $controller = $this->controller(['posts.update']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'published'], $post]);

        self::assertSame(['published', '2026-09-01 08:00:00'], $result);
    }

    // -----------------------------------------------------------------
    // Posts: promotion stays gated
    // -----------------------------------------------------------------

    public function testPromotionNeedsPublishGrant(): void
    {
        $post = $this->post(12, 'draft', null);
        $controller = $this->controller(['posts.update']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'published'], $post]);
            self::fail('Promoting to published must require posts.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
            self::assertStringContainsString('posts.publish', $denied->getMessage());
        }
    }

    public function testSchedulePromotionNeedsScheduleGrant(): void
    {
        $post = $this->post(12, 'draft', null);
        $controller = $this->controller(['posts.update', 'posts.publish']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'scheduled', 'publish_on' => '2026-12-25 09:00:00'], $post]);
            self::fail('Scheduling must require posts.schedule.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
            self::assertStringContainsString('posts.schedule', $denied->getMessage());
        }
    }

    public function testPromotionStampsPublishedAt(): void
    {
        $post = $this->post(12, 'draft', null);
        $controller = $this->controller(['posts.update', 'posts.publish']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'published'], $post]);

        self::assertSame('published', $result[0]);
        self::assertNotSame('', (string) $result[1]);
    }

    public function testCreateDefaultsToDraftWithoutPublishGrant(): void
    {
        $controller = $this->controller(['posts.create']);

        $result = $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['title' => 'New'], null]);

        self::assertSame(['draft', null], $result);
    }

    public function testCreatePublishedNeedsPublishGrant(): void
    {
        $controller = $this->controller(['posts.create']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'published'], null]);
            self::fail('Creating published must require posts.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
        }
    }

    // -----------------------------------------------------------------
    // Pages: demotion and promotion
    // -----------------------------------------------------------------

    public function testUpdateOnlyKeyCannotDemotePublishedPage(): void
    {
        $page = $this->page(20, 'published');
        $controller = $this->pageController(['pages.update']);

        try {
            $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'draft'], $page]);
            self::fail('Demoting a published page must require pages.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
            self::assertStringContainsString('pages.publish', $denied->getMessage());
        }
    }

    public function testPublishGrantDemotesPublishedPage(): void
    {
        $page = $this->page(20, 'published');
        $controller = $this->pageController(['pages.update', 'pages.publish']);

        $result = $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'draft'], $page]);

        self::assertSame('draft', $result);
    }

    public function testPagePromotionNeedsPublishGrant(): void
    {
        $page = $this->page(21, 'draft');
        $controller = $this->pageController(['pages.update']);

        try {
            $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'published'], $page]);
            self::fail('Promoting a page to published must require pages.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
        }
    }

    public function testPageCreateDefaultsToDraftWithoutGrant(): void
    {
        $controller = $this->pageController(['pages.create']);

        $result = $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['title' => 'New'], null]);

        self::assertSame('draft', $result);
    }

    public function testPageCreatePublishedNeedsPublishGrant(): void
    {
        $controller = $this->pageController(['pages.create']);

        try {
            $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'published'], null]);
            self::fail('Creating a published page must require pages.publish.');
        } catch (GrantDenied $denied) {
            self::assertSame(403, $denied->status);
        }
    }

    public function testPageCreatePublishedWithGrantPasses(): void
    {
        $controller = $this->pageController(['pages.create', 'pages.publish']);

        $result = $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'published'], null]);

        self::assertSame('published', $result);
    }

    // -----------------------------------------------------------------
    // Invalid status
    // -----------------------------------------------------------------

    public function testInvalidPostStatusFails422(): void
    {
        $post = $this->post(10, 'published', '2026-09-01 08:00:00');
        $controller = $this->controller(['posts.update', 'posts.publish']);

        try {
            $this->invoke($controller, 'resolvePostStatus', [$this->key(), ['status' => 'yolo'], $post]);
            self::fail('An unknown status must fail 422.');
        } catch (GrantDenied $denied) {
            self::assertSame(422, $denied->status);
        }
    }

    public function testInvalidPageStatusFails422(): void
    {
        $page = $this->page(20, 'published');
        $controller = $this->pageController(['pages.update', 'pages.publish']);

        try {
            $this->invoke($controller, 'resolvePageStatus', [$this->key(), ['status' => 'yolo'], $page]);
            self::fail('An unknown status must fail 422.');
        } catch (GrantDenied $denied) {
            self::assertSame(422, $denied->status);
        }
    }

    // -----------------------------------------------------------------
    // Harness
    // -----------------------------------------------------------------

    /**
     * Build the controller over a real AiService backed by the SQLite
     * tables. The key id is 1; $grants are its only granted permissions.
     *
     * @param string[] $grants
     */
    private function controller(array $grants): HarnessController
    {
        $app = $this->app([]);
        $app->map('ai', function () use ($app): AiService {
            return new AiService($this->pdo, $app);
        });

        $statement = $this->pdo->prepare('INSERT INTO ai_key_grants (key_id, permission) VALUES (1, :permission)');
        foreach ($grants as $permission) {
            $statement->execute(['permission' => $permission]);
        }

        return new HarnessController($app);
    }

    /**
     * Same builder over the pages harness.
     *
     * @param string[] $grants
     */
    private function pageController(array $grants): PagesHarnessController
    {
        $app = $this->app([]);
        $app->map('ai', function () use ($app): AiService {
            return new AiService($this->pdo, $app);
        });

        $statement = $this->pdo->prepare('INSERT INTO ai_key_grants (key_id, permission) VALUES (1, :permission)');
        foreach ($grants as $permission) {
            $statement->execute(['permission' => $permission]);
        }

        return new PagesHarnessController($app);
    }

    private function key(): AiKey
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO ai_keys (id, name, key_hash, key_prefix, enabled, failed_attempts)
             VALUES (1, 'test-key', 'hash-1', 'pvai1_test', 1, 0)"
        );
        $statement->execute();

        $key = (new AiKey($this->pdo))->findById(1);
        self::assertNotNull($key);

        return $key;
    }

    private function post(int $id, string $status, ?string $publishedAt): Post
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO posts (id, title, slug, content, excerpt, status, author_id, views, is_featured,
                allow_comments, ai_generated, created_at, updated_at, deleted_at)
             VALUES (:id, 'My Post', 'my-post', '<p>Body</p>', null, :status, 1, 0, 0, 1, 0,
                '2026-08-01 00:00:00', '2026-09-01 08:00:00', null)"
        );
        $statement->execute(['id' => $id, 'status' => $status]);
        $this->pdo->prepare('UPDATE posts SET published_at = :published_at WHERE id = :id')
            ->execute(['id' => $id, 'published_at' => $publishedAt]);

        $post = (new Post($this->pdo))->findById($id);
        self::assertNotNull($post);

        return $post;
    }

    private function page(int $id, string $status): Page
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO pages (id, title, slug, content, status, created_by, created_at, updated_at, deleted_at)
             VALUES (:id, 'About', 'about', '<p>About</p>', :status, 1, '2026-08-01 00:00:00',
                '2026-09-01 08:00:00', null)"
        );
        $statement->execute(['id' => $id, 'status' => $status]);

        $page = (new Page($this->pdo))->findById($id);
        self::assertNotNull($page);

        return $page;
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
                permission TEXT NOT NULL,
                UNIQUE(key_id, permission)
            )'
        );

        $pdo->exec(
            'CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT \'draft\',                published_at   TEXT,                author_id      INTEGER NOT NULL DEFAULT 1,
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
}
