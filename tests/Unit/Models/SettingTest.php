<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Setting;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use stdClass;

/**
 * Setting model over the settings table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(Setting::class)]
final class SettingTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testFindByKeyHitsAndMisses(): void
    {
        $this->createSetting('CMS.siteName', 'Pubvana', 'string');

        $hit = (new Setting($this->pdo))->findByKey('CMS.siteName');
        self::assertInstanceOf(Setting::class, $hit);
        self::assertSame('Pubvana', $hit->value);

        self::assertNull((new Setting($this->pdo))->findByKey('CMS.missing'));
    }

    public function testGetAutoloadRowsReturnsEveryRow(): void
    {
        $this->createSetting('CMS.siteName', 'Pubvana', 'string');
        $this->createSetting('CMS.logo', '', 'string');

        $rows = (new Setting($this->pdo))->getAutoloadRows();

        self::assertCount(2, $rows);
    }

    public function testGetForNamespaceReturnsOnlyMatchingKeys(): void
    {
        $this->createSetting('CMS.siteName', 'Pubvana', 'string');
        $this->createSetting('blog.postsPerPage', '10', 'integer');

        $rows = (new Setting($this->pdo))->getForNamespace('CMS');

        self::assertCount(1, $rows);
        self::assertSame('CMS.siteName', $rows[0]->key);
    }

    public function testGetAllDecodedMapsKeysToValues(): void
    {
        $this->createSetting('CMS.siteName', 'Pubvana', 'string');
        $this->createSetting('blog.postsPerPage', '10', 'integer');

        $map = (new Setting($this->pdo))->getAllDecoded();

        self::assertSame('Pubvana', $map['CMS.siteName']);
        self::assertSame(10, $map['blog.postsPerPage']);
    }

    public function testDecodedValueCastsPerStoredType(): void
    {
        self::assertTrue($this->createSetting('flag', '1', 'boolean')->decodedValue());
        self::assertSame(5, $this->createSetting('num', '5', 'integer')->decodedValue());
        self::assertSame(1.5, $this->createSetting('ratio', '1.5', 'double')->decodedValue());
        self::assertSame(['a' => 1], $this->createSetting('opts', '{"a":1}', 'array')->decodedValue());

        $row = $this->createSetting('cargo', '{"name":"x"}', 'object');
        self::assertInstanceOf(stdClass::class, $row->decodedValue());
        self::assertSame('x', $row->decodedValue()->name);

        self::assertNull($this->createSetting('nothing', '', 'NULL')->decodedValue());
    }

    public function testCastCoversEveryMatchArm(): void
    {
        self::assertTrue(Setting::cast('boolean', '1'));
        self::assertFalse(Setting::cast('boolean', '0'));

        self::assertSame(42, Setting::cast('integer', '42'));

        self::assertSame(2.5, Setting::cast('double', '2.5'));

        self::assertSame(['a' => 1], Setting::cast('array', '{"a":1}'));
        self::assertSame([], Setting::cast('array', 'garbage'));

        $object = Setting::cast('object', '{"b":2}');
        self::assertInstanceOf(stdClass::class, $object);
        self::assertSame(2, $object->b);
        self::assertInstanceOf(stdClass::class, Setting::cast('object', 'not-json'));

        self::assertNull(Setting::cast('NULL', 'ignored'));

        self::assertSame('text', Setting::cast('string', 'text'));
        self::assertSame('text', Setting::cast('anything-else', 'text'));
    }

    public function testCastNullStoredValueAlwaysReturnsNull(): void
    {
        self::assertNull(Setting::cast('boolean', null));
        self::assertNull(Setting::cast('integer', null));
        self::assertNull(Setting::cast('array', null));
    }

    private function createSetting(string $key, string $value, string $type): Setting
    {
        $setting = new Setting($this->pdo);
        $setting->key = $key;
        $setting->value = $value;
        $setting->type = $type;
        $setting->autoload = true;
        $setting->insert();

        return $setting;
    }
}