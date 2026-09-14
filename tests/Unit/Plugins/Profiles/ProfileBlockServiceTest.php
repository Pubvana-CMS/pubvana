<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Plugins\Profiles\Services\ProfileBlockService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfileBlockService - author card block provider.
 *
 * The provider inspects the current request URI to detect blog vs page
 * context, checks the placement toggles, resolves the author through
 * models, and returns the profile card payload. This suite exercises
 * every branch: toggle gating, context detection, author resolution,
 * and the shape of the returned data.
 */
#[CoversClass(ProfileBlockService::class)]
final class ProfileBlockServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createProfilesSchema($this->pdo);
        $this->createPostsSchema($this->pdo);
        $this->createPagesSchema($this->pdo);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Happy paths
    // -----------------------------------------------------------------

    public function testBlogPostReturnsAuthorCard(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['display_name' => 'Ada Lovelace']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNotNull($payload['author']);
        self::assertSame('Ada Lovelace', $payload['author']['name']);
        self::assertSame('ada', $payload['author']['username']);
        self::assertSame('/profile/ada', $payload['author']['url']);
    }

    public function testPageReturnsAuthorCardWhenEnabled(): void
    {
        $this->insertUser(9, 'grace');
        $this->insertProfile(9, ['display_name' => 'Grace Hopper']);
        $this->insertPage(1, 'about', 9, 'published');

        $payload = $this->service('/page/about')->provide(['show_on_pages' => 1]);

        self::assertNotNull($payload['author']);
        self::assertSame('Grace Hopper', $payload['author']['name']);
        self::assertSame('grace', $payload['author']['username']);
        self::assertSame('/profile/grace', $payload['author']['url']);
    }

    // -----------------------------------------------------------------
    // Toggle gating
    // -----------------------------------------------------------------

    public function testBlogPostHiddenWhenShowOnBlogOff(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['display_name' => 'Ada']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 0]);

        self::assertNull($payload['author']);
    }

    public function testPageHiddenByDefault(): void
    {
        $this->insertUser(9, 'grace');
        $this->insertProfile(9, ['display_name' => 'Grace']);
        $this->insertPage(1, 'about', 9, 'published');

        $payload = $this->service('/page/about')->provide([]);

        self::assertNull($payload['author']);
    }

    // -----------------------------------------------------------------
    // URI context detection
    // -----------------------------------------------------------------

    public function testUnrelatedUriReturnsEmpty(): void
    {
        $payload = $this->service('/about')->provide(['show_on_blog' => 1, 'show_on_pages' => 1]);

        self::assertNull($payload['author']);
    }

    public function testBlogListingUriReturnsEmpty(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']);
    }

    public function testTrailingSlashIsHandled(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['display_name' => 'Ada']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world/')->provide(['show_on_blog' => 1]);

        self::assertNotNull($payload['author']);
    }

    public function testCustomPrefixesAreRespected(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['display_name' => 'Ada']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $app = $this->app([
            'db'           => fn () => $this->pdo,
            'profiles'     => fn () => new Profile($this->pdo),
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return $plugin === 'pubvana/blog' ? '/posts' : '/other';
                }
            },
        ]);
        $_SERVER['REQUEST_URI'] = '/posts/hello-world';

        $payload = (new ProfileBlockService($app))->provide(['show_on_blog' => 1]);

        self::assertNotNull($payload['author']);
        self::assertSame('ada', $payload['author']['username']);
    }

    // -----------------------------------------------------------------
    // Content missing or not published
    // -----------------------------------------------------------------

    public function testUnpublishedPostReturnsEmpty(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertPost(1, 'hello-world', 7, 'draft');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']);
    }

    public function testDeletedPostReturnsEmpty(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertPost(1, 'hello-world', 7, 'published', '2026-01-01 00:00:00');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']);
    }

    public function testNonexistentSlugReturnsEmpty(): void
    {
        $payload = $this->service('/blog/does-not-exist')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']);
    }

    public function testPageWithoutProfileReturnsEmpty(): void
    {
        $this->insertUser(9, 'grace');
        // No profile row for user 9.
        $this->insertPage(1, 'about', 9, 'published');

        $payload = $this->service('/page/about')->provide(['show_on_pages' => 1]);

        self::assertNull($payload['author']);
    }

    public function testDeletedUserReturnsEmpty(): void
    {
        $this->insertUser(7, 'ada', '2026-01-01 00:00:00');
        $this->insertProfile(7, ['display_name' => 'Ada']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']);
    }

    public function testPageWithZeroCreatedByReturnsEmpty(): void
    {
        $this->insertPage(1, 'about', 0, 'published');

        $payload = $this->service('/page/about')->provide(['show_on_pages' => 1]);

        self::assertNull($payload['author']);
    }

    // -----------------------------------------------------------------
    // Profile field resolution
    // -----------------------------------------------------------------

    public function testDisplayNameFallsBackToUsername(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['display_name' => null]);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertSame('ada', $payload['author']['name']);
    }

    public function testAvatarUrlIsComputed(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['avatar' => 'uploads/avatar.png']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertSame('/uploads/avatar.png', $payload['author']['avatar_url']);
    }

    public function testUnsafeWebsiteIsRejected(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['website' => 'javascript:alert(1)']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']['safe_website']);
    }

    public function testSafeWebsiteIsPassedThrough(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, ['website' => 'https://example.com']);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertSame('https://example.com', $payload['author']['safe_website']);
    }

    public function testSocialFullUrlsPassThrough(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, [
            'twitter'  => 'https://x.com/ada',
            'facebook' => 'https://facebook.com/ada',
            'linkedin' => 'https://linkedin.com/in/ada',
        ]);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertSame('https://x.com/ada', $payload['author']['twitter_url']);
        self::assertSame('https://facebook.com/ada', $payload['author']['facebook_url']);
        self::assertSame('https://linkedin.com/in/ada', $payload['author']['linkedin_url']);
    }

    public function testSocialHandlesGetBasePrepended(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7, [
            'twitter'  => '@ada',
            'facebook' => 'ada.pages',
            'linkedin' => 'ada',
        ]);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertSame('https://twitter.com/ada', $payload['author']['twitter_url']);
        self::assertSame('https://facebook.com/ada.pages', $payload['author']['facebook_url']);
        self::assertSame('https://linkedin.com/in/ada', $payload['author']['linkedin_url']);
    }

    public function testEmptySocialFieldsYieldNullUrls(): void
    {
        $this->insertUser(7, 'ada');
        $this->insertProfile(7);
        $this->insertPost(1, 'hello-world', 7, 'published');

        $payload = $this->service('/blog/hello-world')->provide(['show_on_blog' => 1]);

        self::assertNull($payload['author']['twitter_url']);
        self::assertNull($payload['author']['facebook_url']);
        self::assertNull($payload['author']['linkedin_url']);
    }

    // -----------------------------------------------------------------
    // Options flow through
    // -----------------------------------------------------------------

    public function testTitleOptionFlowsThrough(): void
    {
        $payload = $this->service('/about')->provide(['title' => 'Meet the Team']);
        self::assertSame('Meet the Team', $payload['title']);
    }

    public function testShowAvatarDefaultsToTrue(): void
    {
        $payload = $this->service('/about')->provide([]);
        self::assertTrue($payload['show_avatar']);
    }

    public function testShowAvatarWhenOff(): void
    {
        $payload = $this->service('/about')->provide(['show_avatar' => 0]);
        self::assertFalse($payload['show_avatar']);
    }

    public function testShowSocialsWhenOff(): void
    {
        $payload = $this->service('/about')->provide(['show_socials' => 0]);
        self::assertFalse($payload['show_socials']);
    }

    // -----------------------------------------------------------------
    // Schema helpers (same shapes as BlogSchema / PagesSchema)
    // -----------------------------------------------------------------

    private function createProfilesSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE profiles (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL UNIQUE,
                display_name TEXT,
                bio          TEXT,
                avatar       TEXT,
                website      TEXT,
                twitter      TEXT,
                facebook     TEXT,
                linkedin     TEXT,
                job_title    TEXT,
                works_for    TEXT,
                created_at   TEXT,
                updated_at   TEXT
            )'
        );
    }

    private function createPostsSchema(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL UNIQUE,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                featured_image TEXT,
                media_id       INTEGER,
                author_id      INTEGER NOT NULL,
                published_at   TEXT,
                views          INTEGER NOT NULL DEFAULT 0,
                is_featured    INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 1,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                preview_token  TEXT UNIQUE,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )"
        );
    }

    private function createPagesSchema(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE pages (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL UNIQUE,
                content        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                allow_comments INTEGER NOT NULL DEFAULT 0,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                created_by     INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )"
        );
    }

    private function insertUser(int $id, string $username, ?string $deletedAt = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, username, active, deleted_at) VALUES (?, ?, 1, ?)'
        );
        $stmt->execute([$id, $username, $deletedAt]);
    }

    private function insertProfile(int $userId, array $overrides = []): void
    {
        $columns = array_merge([
            'user_id'      => $userId,
            'display_name' => null,
            'bio'          => null,
            'avatar'       => null,
            'website'      => null,
            'twitter'      => null,
            'facebook'     => null,
            'linkedin'     => null,
            'job_title'    => null,
            'works_for'    => null,
        ], $overrides);

        $keys   = array_keys($columns);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $stmt = $this->pdo->prepare("INSERT INTO profiles ({$fields}) VALUES ({$placeholders})");
        $stmt->execute(array_values($columns));
    }

    private function insertPost(int $id, string $slug, int $authorId, string $status, ?string $deletedAt = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (id, title, slug, author_id, status, deleted_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, ucfirst(str_replace('-', ' ', $slug)), $slug, $authorId, $status, $deletedAt]);
    }

    private function insertPage(int $id, string $slug, int $createdBy, string $status, ?string $deletedAt = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pages (id, title, slug, created_by, status, deleted_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$id, ucfirst($slug), $slug, $createdBy, $status, $deletedAt]);
    }

    private function service(string $uri): ProfileBlockService
    {
        $_SERVER['REQUEST_URI'] = $uri;

        $app = $this->app([
            'db'           => fn () => $this->pdo,
            'profiles'     => fn () => new Profile($this->pdo),
            'pluginLoader' => fn () => new class {
                public function routePrefix(string $plugin): string
                {
                    return match ($plugin) {
                        'pubvana/blog'  => '/blog',
                        'pubvana/pages' => '/page',
                        default         => '',
                    };
                }
            },
        ]);

        return new ProfileBlockService($app);
    }
}