<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Public\AssetController;
use Pubvana\Tests\Support\TestCase;

/**
 * AssetController coverage.
 */
#[CoversClass(AssetController::class)]
final class AssetControllerTest extends TestCase
{
    /** @var list<string> */
    public array $served = [];
    /** @var array<string, mixed> */
    public array $halts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->served = [];
        $this->halts = [];
    }

    public function testServeStreamsResolvedFile(): void
    {
        $c = new AssetController($this->engine('/real/file.css'));
        $c->serve('theme', 'default', 'css/a.css');

        self::assertSame(['/real/file.css'], $this->served);
        self::assertCount(0, $this->halts);
    }

    public function testServeMissingHalts404(): void
    {
        $c = new AssetController($this->engine(null));
        $c->serve('plugin', 'Blog', 'missing.css');

        self::assertSame([['code' => 404, 'msg' => 'Asset not found']], $this->halts);
        self::assertCount(0, $this->served);
    }

    private function engine(?string $resolved): Engine
    {
        $test = $this;
        $app = $this->app([
            'asset' => static fn(): object => new class($test, $resolved) {
                public function __construct(private AssetControllerTest $t, private ?string $r)
                {
                }

                public function resolve(string $a, string $b, string $c): ?string
                {
                    return $this->r;
                }

                public function serve(string $f): void
                {
                    $this->t->served[] = $f;
                }
            },
        ]);
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->halts[] = ['code' => $code, 'msg' => $msg];
        });
        \Flight::setEngine($app);

        return $app;
    }
}
