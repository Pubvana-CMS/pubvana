<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Api;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Api\ApiBaseController;
use Pubvana\Tests\Support\TestCase;

/**
 * ApiBaseController coverage.
 */
#[CoversClass(ApiBaseController::class)]
final class ApiBaseControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $jsons = [];
    /** @var array<string, mixed> */
    public array $halts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->jsons = [];
        $this->halts = [];
    }

    public function testJsonEmitsWithoutHalting(): void
    {
        $c = $this->controller($this->engine());
        $this->invoke($c, 'json', [['ok' => true], 201]);

        self::assertSame([['data' => ['ok' => true], 'code' => 201]], $this->jsons);
        self::assertCount(0, $this->halts);
    }

    public function testJsonHaltEmitsAndHalts(): void
    {
        $c = $this->controller($this->engine());
        try {
            $this->invoke($c, 'jsonHalt', [['err' => 'x'], 422]);
            self::fail('must halt');
        } catch (ApiHaltProbe) {
        }
        self::assertSame([['data' => ['err' => 'x'], 'code' => 422]], $this->halts);
    }

    public function testBearerTokenVariants(): void
    {
        self::assertSame('abc123', $this->invoke($this->controller($this->engine(authHeader: 'Bearer abc123')), 'bearerToken'));
        self::assertSame('tok', $this->invoke($this->controller($this->engine(authHeader: 'bearer tok')), 'bearerToken'));
        self::assertNull($this->invoke($this->controller($this->engine(authHeader: '')), 'bearerToken'));
        self::assertNull($this->invoke($this->controller($this->engine(authHeader: 'Basic xyz')), 'bearerToken'));
        self::assertNull($this->invoke($this->controller($this->engine(authHeader: 'Bearer')), 'bearerToken'));
    }

    public function testMethodPathPayload(): void
    {
        $c = $this->controller($this->engine(method: 'POST', url: '/api/x?y=1', payload: ['a' => 1]));
        self::assertSame('POST', $this->invoke($c, 'method'));
        self::assertSame('/api/x', $this->invoke($c, 'path'));
        self::assertSame(['a' => 1], $this->invoke($c, 'payload'));
    }

    public function testPayloadThrowingReturnsEmpty(): void
    {
        $app = $this->engine();
        $app->map('request', static fn(): object => new class {
            public function __get(string $n): mixed
            {
                if ($n === 'data') {
                    throw new \RuntimeException('no body');
                }

                return null;
            }
        });
        $c = $this->controller($app);
        self::assertSame([], $this->invoke($c, 'payload'));
    }

    public function testApiPrefixAndGetConfig(): void
    {
        $app = $this->engine();
        $app->set('pubvana.perPage', 25);
        $c = $this->controller($app);

        self::assertSame('/api/test', $this->invoke($c, 'apiPrefix', ['pubvana/test']));
        self::assertSame(25, $this->invoke($c, 'getConfig', ['perPage']));
        self::assertSame('d', $this->invoke($c, 'getConfig', ['missing', 'd']));
    }

    private function controller(Engine $app): ApiBaseController
    {
        return new class($app) extends ApiBaseController {
        };
    }

    private function engine(string $authHeader = '', string $method = 'GET', string $url = '/', array $payload = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($authHeader, $method, $url, $payload) {
                public string $method;
                public string $url;
                public Collection $data;
                /** @param array<string, mixed> $p */
                public function __construct(private string $h, string $m, string $u, array $p)
                {
                    $this->method = $m;
                    $this->url = $u;
                    $this->data = new Collection($p);
                }

                public function getHeader(string $n): string
                {
                    return $n === 'Authorization' ? $this->h : '';
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function apiPrefix(string $id): string
                {
                    return '/api/test';
                }
            },
        ]);
        $app->map('json', function (mixed $d, int $c = 200) use ($test): void {
            $test->jsons[] = ['data' => $d, 'code' => $c];
        });
        $app->map('jsonHalt', function (mixed $d, int $c = 200) use ($test): never {
            $test->halts[] = ['data' => $d, 'code' => $c];
            throw new ApiHaltProbe();
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Probe for jsonHalt.
 */
final class ApiHaltProbe extends \RuntimeException
{
}
