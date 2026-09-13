<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Theme;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Theme model over the themes table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(Theme::class)]
final class ThemeTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testFindActiveReturnsActiveTheme(): void
    {
        $this->createTheme('Default', 'default', 1, null);
        $this->createTheme('Dark', 'dark', 0, null);

        $active = (new Theme($this->pdo))->findActive();
        self::assertInstanceOf(Theme::class, $active);
        self::assertSame('default', $active->folder);
    }

    public function testFindActiveTreatsNullDisabledAsNotDisabled(): void
    {
        $this->createTheme('Default', 'default', 1, null);

        self::assertNotNull((new Theme($this->pdo))->findActive());
    }

    public function testFindActiveTreatsZeroDisabledAsNotDisabled(): void
    {
        $this->createTheme('Default', 'default', 1, 0);

        self::assertNotNull((new Theme($this->pdo))->findActive());
    }

    public function testFindActiveExcludesDisabledTheme(): void
    {
        $this->createTheme('Default', 'default', 0, null);
        $this->createTheme('Broken', 'broken', 1, 1);

        self::assertNull((new Theme($this->pdo))->findActive());
    }

    public function testFindActiveReturnsNullWhenNoneActive(): void
    {
        $this->createTheme('Default', 'default', 0, null);

        self::assertNull((new Theme($this->pdo))->findActive());
    }

    public function testFindByFolderHitsAndMisses(): void
    {
        $this->createTheme('Default', 'default', 1, null);

        $hit = (new Theme($this->pdo))->findByFolder('default');
        self::assertInstanceOf(Theme::class, $hit);
        self::assertSame('Default', $hit->name);

        self::assertNull((new Theme($this->pdo))->findByFolder('missing'));
    }

    public function testGetAllOrdersByName(): void
    {
        $this->createTheme('Zebra', 'zebra', 0, null);
        $this->createTheme('Apple', 'apple', 1, null);

        $rows = (new Theme($this->pdo))->getAll();

        self::assertSame(['Apple', 'Zebra'], array_column($rows, 'name'));
    }

    public function testActivateByIdDemotesOthersAndPromotesTarget(): void
    {
        $this->createTheme('Default', 'default', 1, null);
        $target = $this->createTheme('Dark', 'dark', 0, null);

        (new Theme($this->pdo))->activateById($target->id);

        $active = (new Theme($this->pdo))->findActive();
        self::assertSame('dark', $active?->folder);

        $other = (new Theme($this->pdo))->findByFolder('default');
        self::assertSame(0, $other?->is_active);
    }

    public function testActivateByIdWithUnknownIdLeavesStateUntouched(): void
    {
        $this->createTheme('Default', 'default', 1, null);

        (new Theme($this->pdo))->activateById(9999);

        self::assertSame('default', (new Theme($this->pdo))->findActive()?->folder);
        self::assertSame(1, (new Theme($this->pdo))->findByFolder('default')?->is_active);
    }

    public function testDeactivateAndFallbackActivatesDefaultTheme(): void
    {
        $this->createTheme('Default', 'default', 0, null);
        $active = $this->createTheme('Dark', 'dark', 1, null);

        (new Theme($this->pdo))->deactivateAndFallback($active->id);

        $fallback = (new Theme($this->pdo))->findActive();
        self::assertSame('default', $fallback?->folder);
        self::assertSame(0, (new Theme($this->pdo))->findByFolder('dark')?->is_active);
    }

    public function testDeactivateAndFallbackWithoutDefaultStillDeactivates(): void
    {
        $this->createTheme('Dark', 'dark', 1, null);

        (new Theme($this->pdo))->deactivateAndFallback(1);

        self::assertNull((new Theme($this->pdo))->findActive());
    }

    private function createTheme(string $name, string $folder, int $isActive, ?int $disabled): Theme
    {
        $theme = new Theme($this->pdo);
        $theme->name = $name;
        $theme->folder = $folder;
        $theme->description = 'Test theme';
        $theme->version = '1.0';
        $theme->author = 'Tester';
        $theme->is_active = $isActive;
        $theme->disabled = $disabled;
        $theme->insert();

        return $theme;
    }
}