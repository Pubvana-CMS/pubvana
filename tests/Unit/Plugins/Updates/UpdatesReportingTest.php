<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Plugin;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * The dashboard card and the Site Health check read the cached check state
 * (never the network), so a newest release held back by an installed addon
 * must not be reported as "running the latest version".
 *
 * @package Pubvana\Tests\Unit\Plugins\Updates
 */
#[CoversClass(Plugin::class)]
final class UpdatesReportingTest extends TestCase
{
    /**
     * Boots the plugin against a facade that serves the given cached state.
     *
     * @param array<string, mixed> $state
     * @return array{0: ExtensionRegistry, 1: Engine}
     */
    private function bootWith(array $state): array
    {
        $app = new Engine();
        $app->init();
        $app->map('adext', static function (): ExtensionRegistry {
            static $registry = null;
            $registry ??= new ExtensionRegistry();

            return $registry;
        });
        $app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/admin/updates';
            }
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        $service = new class ($state) {
            /**
             * @param array<string, mixed> $state
             */
            public function __construct(private array $state) {}

            /**
             * @return array<string, mixed>
             */
            public function lastCheck(): array
            {
                return $this->state;
            }

            public function currentVersion(): string
            {
                return (string) ($this->state['current_version'] ?? '');
            }
        };

        $app->map('updates', static fn(): object => $service);

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();

        return [$adext, $app];
    }

    /**
     * @return array<string, mixed>
     */
    private function cappedState(): array
    {
        return [
            'status'          => 'up_to_date',
            'current_version' => '3.0.0-beta.2',
            'latest_version'  => '3.0.0-beta.3',
            'capped_by'       => 'some-vendor/some-addon',
        ];
    }

    /**
     * @param ExtensionRegistry $adext
     * @return list<array<string, mixed>>
     */
    private function cards(ExtensionRegistry $adext): array
    {
        $cards = $adext->get('admin.dashboard', 'cards');

        return $cards['pubvana.updates']['callable']([]);
    }

    public function testDashboardCardReportsHeldBackRelease(): void
    {
        [$adext] = $this->bootWith($this->cappedState());

        $rows = $this->cards($adext);

        self::assertSame('warning', $rows[0]['tone']);
        self::assertStringContainsString('held back', (string) $rows[0]['description']);
        self::assertStringContainsString('3.0.0-beta.3', (string) $rows[0]['description']);
    }

    public function testDashboardCardStaysGreenWithNothingHeldBack(): void
    {
        [$adext] = $this->bootWith([
            'status'          => 'up_to_date',
            'current_version' => '3.0.0-beta.3',
            'latest_version'  => '3.0.0-beta.3',
        ]);

        $rows = $this->cards($adext);

        self::assertSame('success', $rows[0]['tone']);
        self::assertSame('Running the latest version.', $rows[0]['description']);
    }

    public function testHealthCheckWarnsWhenTheNewestReleaseIsHeldBack(): void
    {
        [$adext] = $this->bootWith($this->cappedState());

        $checks = $adext->get('health', 'checks');
        $result = $checks['pubvana.updates']['callable']();

        self::assertSame('warning', $result->status);
        self::assertStringContainsString('held back', $result->message);
        self::assertStringContainsString('3.0.0-beta.3', $result->message);
        self::assertNotSame('', $result->remediation);
    }

    public function testHealthCheckPassesWhenUpToDate(): void
    {
        [$adext] = $this->bootWith([
            'status'          => 'up_to_date',
            'current_version' => '3.0.0-beta.3',
            'latest_version'  => '3.0.0-beta.3',
        ]);

        $checks = $adext->get('health', 'checks');
        $result = $checks['pubvana.updates']['callable']();

        self::assertSame('pass', $result->status);
        self::assertSame('Pubvana 3.0.0-beta.3 is up to date.', $result->message);
    }
}
