<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Profile write validation (AUDIT M5).
 *
 * The website field becomes a navigable href on the public profile page,
 * so only full http:// or https:// URLs are persisted; a rejected value
 * leaves the row untouched. Social handles are stripped of tags and
 * whitespace on write.
 */
#[CoversClass(Profile::class)]
final class ProfileWebsiteValidationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createProfilesSchema($this->pdo);
    }

    public function testJavascriptWebsiteIsRejectedAndRowUntouched(): void
    {
        $model = new Profile($this->pdo);
        $model->findOrCreate(7);

        $result = $model->updateProfile(7, [
            'display_name' => 'Ada Lovelace',
            'website'      => 'javascript:alert(1)',
        ]);

        self::assertNull($result, 'a javascript: URL must not be saved');

        $row = $this->profileRow(7);
        self::assertSame(null, $row['display_name'], 'a rejected write must save nothing, not a partial row');
        self::assertSame(null, $row['website']);
    }

    public function testHttpsWebsiteIsSaved(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, [
            'website' => 'https://example.com/page',
        ]);

        self::assertNotNull($result);
        self::assertSame('https://example.com/page', $this->profileRow(7)['website']);
    }

    public function testHttpWebsiteIsSaved(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, ['website' => 'http://example.com']);

        self::assertNotNull($result);
        self::assertSame('http://example.com', $this->profileRow(7)['website']);
    }

    public function testEmptyWebsiteIsAllowed(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, ['website' => '   ']);

        self::assertNotNull($result);
        self::assertSame(null, $this->profileRow(7)['website']);
    }

    public function testSchemelessAndOtherSchemesAreRejected(): void
    {
        $model = new Profile($this->pdo);

        foreach (['example.com', '//evil.com', 'data:text/html,hi', 'ftp://example.com'] as $bad) {
            $result = $model->updateProfile(7, ['website' => $bad]);
            self::assertNull($result, "value '{$bad}' must be rejected");
            self::assertSame(null, $this->profileRow(7)['website']);
        }
    }

    public function testRejectedWebsiteKeepsPreviouslySavedValue(): void
    {
        $model = new Profile($this->pdo);
        $model->updateProfile(7, ['website' => 'https://example.com']);

        $result = $model->updateProfile(7, ['website' => 'javascript:alert(1)']);

        self::assertNull($result);
        self::assertSame('https://example.com', $this->profileRow(7)['website'], 'a failed write must not clobber the stored value');
    }

    public function testSocialHandlesAreStrippedOfTagsAndWhitespace(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, [
            'twitter'   => "  <b>Ada</b> Lovelace\n\t ",
            'facebook'  => ' Ada.Pages ',
            'linkedin'  => 'in/ ada',
        ]);

        self::assertNotNull($result);
        $row = $this->profileRow(7);
        self::assertSame('AdaLovelace', $row['twitter']);
        self::assertSame('Ada.Pages', $row['facebook']);
        self::assertSame('in/ada', $row['linkedin']);
    }

    public function testBlankSocialHandleBecomesNull(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, ['twitter' => '   ']);

        self::assertNotNull($result);
        self::assertSame(null, $this->profileRow(7)['twitter']);
    }

    public function testOtherFieldsStillTrimToNull(): void
    {
        $model = new Profile($this->pdo);

        $result = $model->updateProfile(7, [
            'display_name' => '  Ada Lovelace  ',
            'bio'          => '   ',
            'job_title'    => 'Mathematician',
        ]);

        self::assertNotNull($result);
        $row = $this->profileRow(7);
        self::assertSame('Ada Lovelace', $row['display_name']);
        self::assertSame(null, $row['bio']);
        self::assertSame('Mathematician', $row['job_title']);
    }

    /**
     * profiles table mirroring the plugin migration column shapes.
     * Suite-owned because shared SQLite schema tests never touch this plugin.
     */
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

    /**
     * @return array<string, mixed>
     */
    private function profileRow(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM profiles WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertNotFalse($row, 'profile row must exist for user ' . $userId);

        /** @var array<string, mixed> $row */
        return $row;
    }
}
