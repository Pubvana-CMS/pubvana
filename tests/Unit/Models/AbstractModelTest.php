<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\AbstractModel;
use Pubvana\Models\Setting;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AbstractModel's typed return contract over the vendor ActiveRecord.
 *
 * The vendor hydrates rows with `new $called_class`, so find()/findAll()
 * must return instances of the concrete model, not the base class. These
 * tests lock that in using Setting as a representative concrete model.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(AbstractModel::class)]
final class AbstractModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testFindReturnsConcreteModelInstance(): void
    {
        $created = $this->createSetting('CMS.siteName', 'Pubvana', 'string');

        /** @var Setting $found */
        $found = (new Setting($this->pdo))->find($created->id);

        self::assertInstanceOf(Setting::class, $found);
        self::assertSame('Pubvana', $found->value);
    }

    public function testFindAllReturnsConcreteModelInstances(): void
    {
        $this->createSetting('CMS.siteName', 'Pubvana', 'string');
        $this->createSetting('CMS.logo', '', 'string');

        $rows = (new Setting($this->pdo))->findAll();

        self::assertContainsOnlyInstancesOf(Setting::class, $rows);
        self::assertCount(2, $rows);
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