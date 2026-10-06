<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\BrokenLinks;

use Ahc\Cli\Application;
use Ahc\Cli\IO\Interactor;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\BrokenLinks\commands\BrokenLinksCheckCommand;
use Pubvana\Tests\Support\TestCase;

use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * broken-links:check resolves the service from the bootstrapped app.
 *
 * The command once built a fresh \flight\Engine, which has no mapped
 * services, so every run threw "brokenLinks must be a mapped method." It
 * reads the service off Flight::app() now. Output goes to a temp file so the
 * test does not print to the console.
 */
#[CoversClass(BrokenLinksCheckCommand::class)]
final class BrokenLinksCommandsTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        $this->files = [];
        parent::tearDown();
    }

    public function testCheckCommandScansViaAppService(): void
    {
        $this->setAppService([
            'total'     => 3,
            'broken'    => 1,
            'sources'   => 2,
            'timed_out' => false,
        ]);

        $command = new BrokenLinksCheckCommand([]);
        $command->bind($this->applicationWithTempOutput());

        self::assertSame(1, $command->execute());
    }

    public function testCheckCommandReturnsZeroWhenNothingBroken(): void
    {
        $this->setAppService([
            'total'     => 4,
            'broken'    => 0,
            'sources'   => 1,
            'timed_out' => false,
        ]);

        $command = new BrokenLinksCheckCommand([]);
        $command->bind($this->applicationWithTempOutput());

        self::assertSame(0, $command->execute());
    }

    /**
     * @param array{total: int, broken: int, sources: int, timed_out: bool} $result
     */
    private function setAppService(array $result): void
    {
        $app = $this->app([
            'brokenLinks' => static fn (): object => new class ($result) {
                /** @param array<string, mixed> $result */
                public function __construct(private array $result)
                {
                }

                /**
                 * @return array<string, mixed>
                 */
                public function scan(?int $maxSeconds = null): array
                {
                    return $this->result;
                }
            },
        ]);

        \Flight::setEngine($app);
    }

    private function applicationWithTempOutput(): Application
    {
        $path = sys_get_temp_dir() . '/pv-brokenlinks-cmd-' . uniqid() . '.out';
        $this->files[] = $path;

        $application = new Application('pubvana');
        $application->io(new Interactor(null, $path));

        return $application;
    }
}
