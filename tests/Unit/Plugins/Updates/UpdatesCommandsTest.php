<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use Ahc\Cli\IO\Interactor;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\commands\UpdatesApplyCommand;
use Pubvana\Plugins\Updates\commands\UpdatesAutoUpdateCommand;
use Pubvana\Plugins\Updates\commands\UpdatesCheckCommand;
use Pubvana\Tests\Support\TestCase;

use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function file_get_contents;

/**
 * Updates CLI commands: option wiring + report branches.
 *
 * execute() instantiates UpdateService directly (HTTP feed), so the
 * glue is not exercised; the output formatting and return codes are,
 * through a real Interactor writing to a temp file.
 */
#[CoversClass(UpdatesCheckCommand::class)]
#[CoversClass(UpdatesApplyCommand::class)]
#[CoversClass(UpdatesAutoUpdateCommand::class)]
final class UpdatesCommandsTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    private ?string $outputFile = null;

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        $this->files = [];
        parent::tearDown();
    }

    public function testCheckCommandWiring(): void
    {
        $command = new UpdatesCheckCommand([]);

        self::assertSame('updates:check', $command->getName());
        self::assertSame('Check the Pubvana release feed for updates', $command->getDescription());
        self::assertSame(false, $command->force);
    }

    public function testApplyCommandWiring(): void
    {
        $command = new UpdatesApplyCommand([]);

        self::assertSame('updates:apply', $command->getName());
        self::assertSame('', $command->release);
        self::assertSame('cli', $command->user);
    }

    public function testAutoUpdateCommandWiring(): void
    {
        $command = new UpdatesAutoUpdateCommand([]);

        self::assertSame('updates:auto-update', $command->getName());
        self::assertSame('cron', $command->user);
    }

    public function testReportUpToDate(): void
    {
        $command = new UpdatesCheckCommand([]);
        $code = $this->invoke($command, 'reportUpToDate', [$this->interactor(), ['current_version' => '3.0.0']]);

        self::assertSame(0, $code);
        self::assertStringContainsString('Up to date.', $this->captured());
    }

    public function testReportUpToDateNamesCappedBy(): void
    {
        $command = new UpdatesCheckCommand([]);
        $code = $this->invoke($command, 'reportUpToDate', [
            $this->interactor(),
            ['current_version' => '3.0.0', 'capped_by' => 'pubvana/blog'],
        ]);

        self::assertSame(0, $code);
        self::assertStringContainsString('held back by pubvana/blog', $this->captured());
    }

    public function testReportAvailablePrintsTargetAndNotes(): void
    {
        $command = new UpdatesCheckCommand([]);
        $code = $this->invoke($command, 'reportAvailable', [
            $this->interactor(),
            [
                'target_version' => '3.1.0',
                'breaking_changes' => ['config moved'],
                'notices' => ['clear cache after'],
                'migration_notes' => ['run migrations'],
            ],
        ]);

        self::assertSame(0, $code);
        $out = $this->captured();
        self::assertStringContainsString('Version 3.1.0 is available.', $out);
        self::assertStringContainsString('Breaking: config moved', $out);
        self::assertStringContainsString('Notice: clear cache after', $out);
        self::assertStringContainsString('Note: run migrations', $out);
        self::assertStringContainsString('updates:apply', $out);
    }

    public function testReportError(): void
    {
        $command = new UpdatesCheckCommand([]);
        $code = $this->invoke($command, 'reportError', [$this->interactor(), ['error' => 'feed down']]);

        self::assertSame(1, $code);
        self::assertStringContainsString('Check failed: feed down', $this->captured());
    }

    private function interactor(): Interactor
    {
        $path = sys_get_temp_dir() . '/pv-cmd-' . uniqid() . '.out';
        $this->files[] = $path;
        $this->outputFile = $path;

        return new Interactor(null, $path);
    }

    private function captured(): string
    {
        self::assertNotNull($this->outputFile);

        return (string) file_get_contents($this->outputFile);
    }
}
