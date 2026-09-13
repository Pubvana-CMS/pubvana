<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\ThemeOption;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ThemeOption model over the theme_options table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(ThemeOption::class)]
final class ThemeOptionTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testGetForThemeReturnsOnlyThatThemesOptions(): void
    {
        $this->createOption(1, 'hero.show', 'true');
        $this->createOption(1, 'hero.title', 'Hi');
        $this->createOption(2, 'hero.show', 'false');

        $rows = (new ThemeOption($this->pdo))->getForTheme(1);

        self::assertCount(2, $rows);
        self::assertSame(['hero.show', 'hero.title'], array_column($rows, 'option_key'));
    }

    public function testGetOptionReturnsValueOrDefault(): void
    {
        $this->createOption(1, 'hero.show', 'true');

        self::assertSame('true', (new ThemeOption($this->pdo))->getOption(1, 'hero.show'));
        self::assertSame('fallback', (new ThemeOption($this->pdo))->getOption(1, 'missing', 'fallback'));
        self::assertNull((new ThemeOption($this->pdo))->getOption(1, 'missing'));
        self::assertNull((new ThemeOption($this->pdo))->getOption(2, 'hero.show'));
    }

    public function testSaveOptionInsertsWhenMissing(): void
    {
        (new ThemeOption($this->pdo))->saveOption(1, 'hero.show', 'true');

        $row = (new ThemeOption($this->pdo))->getOption(1, 'hero.show');
        self::assertSame('true', $row);
    }

    public function testSaveOptionUpdatesWhenPresent(): void
    {
        $this->createOption(1, 'hero.show', 'true');

        (new ThemeOption($this->pdo))->saveOption(1, 'hero.show', 'false');

        $options = (new ThemeOption($this->pdo))->getForTheme(1);
        self::assertCount(1, $options);
        self::assertSame('false', $options[0]->option_value);
    }

    public function testSaveOptionDoesNotCollideAcrossThemes(): void
    {
        $this->createOption(1, 'hero.show', 'true');

        (new ThemeOption($this->pdo))->saveOption(2, 'hero.show', 'false');

        self::assertSame('true', (new ThemeOption($this->pdo))->getOption(1, 'hero.show'));
        self::assertSame('false', (new ThemeOption($this->pdo))->getOption(2, 'hero.show'));
    }

    public function testSeedDefaultInsertsWhenMissing(): void
    {
        (new ThemeOption($this->pdo))->seedDefault(1, 'hero.show', 'true');

        self::assertSame('true', (new ThemeOption($this->pdo))->getOption(1, 'hero.show'));
    }

    public function testSeedDefaultDoesNotOverwriteExistingValue(): void
    {
        $this->createOption(1, 'hero.show', 'true');

        (new ThemeOption($this->pdo))->seedDefault(1, 'hero.show', 'false');

        self::assertSame('true', (new ThemeOption($this->pdo))->getOption(1, 'hero.show'));
    }

    private function createOption(int $themeId, string $key, string $value): ThemeOption
    {
        $option = new ThemeOption($this->pdo);
        $option->theme_id = $themeId;
        $option->option_key = $key;
        $option->option_value = $value;
        $option->insert();

        return $option;
    }
}