<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Profile model: finders, findOrCreate, updateProfile/updateFromArray.
 *
 * Website scheme validation and handle sanitization already have dedicated
 * coverage in ProfileWebsiteValidationTest; this suite covers the rest:
 * finders, creation, field writes, unknown-field tolerance, and the
 * avatar/job fields.
 */
#[CoversClass(Profile::class)]
final class ProfileModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
    }

    public function testFindByUserIdHitAndMiss(): void
    {
        $model = new Profile($this->pdo);
        self::assertNull($model->findByUserId(7));

        $model->findOrCreate(7);
        $found = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($found);
        self::assertSame(7, (int) $found->user_id);
    }

    public function testFindByUserIdRunsOnFreshInstance(): void
    {
        $a = (new Profile($this->pdo))->findOrCreate(7);
        $b = (new Profile($this->pdo))->findOrCreate(8);

        $model = new Profile($this->pdo);
        $first = $model->findByUserId((int) $a->user_id);
        $second = $model->findByUserId((int) $b->user_id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame(7, (int) $first->user_id);
        self::assertNull($model->findByUserId(99999));
    }

    public function testFindOrCreateReturnsExisting(): void
    {
        $model = new Profile($this->pdo);
        $first = $model->findOrCreate(7);
        $first->updateFromArray(['display_name' => 'Ada']);

        $second = (new Profile($this->pdo))->findOrCreate(7);
        self::assertSame((int) $first->id, (int) $second->id);
        self::assertSame('Ada', $second->display_name);
        self::assertSame(1, $this->countRows());
    }

    public function testUpdateProfileCreatesAndWrites(): void
    {
        $model = new Profile($this->pdo);
        $profile = $model->updateProfile(7, [
            'display_name' => 'Ada Lovelace',
            'bio' => 'Mathematician',
            'job_title' => 'Analyst',
            'works_for' => 'Navy',
            'avatar' => 'uploads/ada.png',
        ]);

        self::assertNotNull($profile);
        self::assertSame('Ada Lovelace', $profile->display_name);
        self::assertSame('uploads/ada.png', $profile->avatar);

        $row = $this->row(7);
        self::assertSame('Mathematician', $row['bio']);
        self::assertSame('Navy', $row['works_for']);
    }

    public function testUpdateProfileRejectsBadWebsite(): void
    {
        $model = new Profile($this->pdo);
        self::assertNull($model->updateProfile(7, ['website' => 'javascript:alert(1)']));
    }

    public function testUpdateFromArrayIgnoresUnknownFields(): void
    {
        $model = new Profile($this->pdo);
        $profile = $model->findOrCreate(7);

        self::assertTrue($profile->updateFromArray(['display_name' => 'Ada', 'hacker_field' => 'x']));
        self::assertSame('Ada', $this->row(7)['display_name']);
    }

    public function testUpdateFromArrayTrimsAndNulls(): void
    {
        $model = new Profile($this->pdo);
        $profile = $model->findOrCreate(7);

        self::assertTrue($profile->updateFromArray([
            'display_name' => '  Ada  ',
            'bio' => '   ',
            'avatar' => '',
        ]));

        $row = $this->row(7);
        self::assertSame('Ada', $row['display_name']);
        self::assertNull($row['bio']);
        self::assertNull($row['avatar']);
    }

    public function testUpdateFromArraySocialBranches(): void
    {
        $model = new Profile($this->pdo);
        $profile = $model->findOrCreate(7);

        self::assertTrue($profile->updateFromArray([
            'twitter' => '@ada ',
            'facebook' => '',
            'linkedin' => '  in/ada  ',
        ]));

        $row = $this->row(7);
        self::assertSame('@ada', $row['twitter']);
        self::assertNull($row['facebook']);
        self::assertSame('in/ada', $row['linkedin']);
    }

    public function testUpdateFromArrayEmptyWebsiteClears(): void
    {
        $model = new Profile($this->pdo);
        $model->updateProfile(7, ['website' => 'https://example.com']);
        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);

        self::assertTrue($profile->updateFromArray(['website' => '   ']));
        self::assertNull($this->row(7)['website']);
    }

    private function countRows(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) c FROM profiles')->fetch()['c'];
    }

    /** @return array<string, mixed> */
    private function row(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM profiles WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertNotFalse($row);

        /** @var array<string, mixed> $row */
        return $row;
    }
}
