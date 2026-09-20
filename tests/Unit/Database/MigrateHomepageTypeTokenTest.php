<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Database;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Database\Migrations\MigrateHomepageTypeToken;
use Pubvana\Models\Setting;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * MigrateHomepageTypeToken coverage over the in-memory settings table.
 *
 * The migration is guarded, so the cases that matter most are the ones it
 * must leave alone: a different value, an absent row, a re-run, and a
 * rollback that cannot tell a migrated value from a chosen one.
 *
 * @package Pubvana\Tests\Unit\Database
 */
#[CoversClass(MigrateHomepageTypeToken::class)]
final class MigrateHomepageTypeTokenTest extends TestCase
{
    /** Date-prefixed filename, so PSR-4 cannot find the class. */
    private const MIGRATION_FILE = 'app/Database/Migrations/2026-09-20-133834_MigrateHomepageTypeToken.php';

    private PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once PROJECT_ROOT . '/' . self::MIGRATION_FILE;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();

        // The migration reads the connection the runner boots the app with.
        \Flight::setEngine($this->app(['db' => fn(): PDO => $this->pdo]));
    }

    public function testItMovesTheOldEnumValueOntoTheProviderToken(): void
    {
        $this->insertRow('CMS.homepageType', 'pages');

        $this->migration()->up();

        self::assertSame('page', $this->value('CMS.homepageType'));
    }

    public function testItLeavesAnotherValueAlone(): void
    {
        $this->insertRow('CMS.homepageType', 'blog');

        $this->migration()->up();

        self::assertSame('blog', $this->value('CMS.homepageType'));
    }

    public function testItLeavesAnAbsentRowAlone(): void
    {
        $this->migration()->up();

        self::assertNull($this->value('CMS.homepageType'));
    }

    public function testASecondRunChangesNothing(): void
    {
        $this->insertRow('CMS.homepageType', 'pages');

        $this->migration()->up();
        $this->migration()->up();

        self::assertSame('page', $this->value('CMS.homepageType'));
    }

    public function testItOnlyRewritesTheValue(): void
    {
        $this->insertRow('CMS.homepageType', 'pages', autoload: false);

        $this->migration()->up();

        $row = $this->row('CMS.homepageType');
        self::assertSame('page', $row['value']);
        self::assertSame('string', $row['type']);
        self::assertSame(0, (int) $row['autoload']);
    }

    public function testRollbackIsANoOp(): void
    {
        $this->insertRow('CMS.homepageType', 'pages');
        $this->migration()->up();

        $this->migration()->down();

        self::assertSame('page', $this->value('CMS.homepageType'));
    }

    private function migration(): MigrateHomepageTypeToken
    {
        return new MigrateHomepageTypeToken();
    }

    /**
     * Insert a settings row directly, bypassing the service.
     */
    private function insertRow(string $key, ?string $value, bool $autoload = true): void
    {
        $setting = new Setting($this->pdo);
        $setting->key = $key;
        $setting->value = $value;
        $setting->type = 'string';
        $setting->autoload = $autoload;
        $setting->insert();
    }

    private function value(string $key): ?string
    {
        $row = $this->row($key);

        return $row === null ? null : (string) $row['value'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function row(string $key): ?array
    {
        $statement = $this->pdo->prepare('SELECT value, type, autoload FROM settings WHERE key = :key');
        $statement->execute(['key' => $key]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
