<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\PluginState;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PluginState model over the plugin_state table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(PluginState::class)]
final class PluginStateTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testFindByPluginIdReturnsHydratedRow(): void
    {
        $this->createState('pubvana/blog', true, 60, true);

        $state = (new PluginState($this->pdo))->findByPluginId('pubvana/blog');

        self::assertInstanceOf(PluginState::class, $state);
        self::assertTrue($state->enabled);
        self::assertTrue($state->required);
        self::assertSame(60, $state->priority);
    }

    public function testFindByPluginIdReturnsNullWhenMissing(): void
    {
        $this->createState('pubvana/blog', false, 50, false);

        self::assertNull((new PluginState($this->pdo))->findByPluginId('pubvana/unknown'));
    }

    public function testGetAllByPluginIdKeysRowsByPluginId(): void
    {
        $this->createState('pubvana/blog', true, 50, false);
        $this->createState('enlivenapp/flight-sessions', true, 5, true);

        $map = (new PluginState($this->pdo))->getAllByPluginId();

        self::assertCount(2, $map);
        self::assertArrayHasKey('pubvana/blog', $map);
        self::assertArrayHasKey('enlivenapp/flight-sessions', $map);
        self::assertInstanceOf(PluginState::class, $map['pubvana/blog']);
        self::assertSame(5, $map['enlivenapp/flight-sessions']->priority);
        self::assertTrue($map['enlivenapp/flight-sessions']->required);
    }

    private function createState(string $pluginId, bool $enabled, int $priority, bool $required): PluginState
    {
        $state = new PluginState($this->pdo);
        $state->plugin_id = $pluginId;
        $state->enabled = $enabled;
        $state->priority = $priority;
        $state->required = $required;
        $state->insert();

        return $state;
    }
}