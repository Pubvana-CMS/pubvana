<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Controllers\AiAdminController;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AiAdminController key creation.
 *
 * The plaintext token is revealed once, from the POST response that
 * creates the key. It must reach the view as a variable and must never be
 * written to the session (AGENTS.md rule 1).
 *
 * No session service is mapped in these tests. Engine::__call() throws
 * when an unmapped method is called, so a stray $app->session() anywhere
 * on the create-key path fails the test instead of passing quietly.
 */
#[CoversClass(AiAdminController::class)]
final class AiAdminControllerTest extends TestCase
{
    private const TOKEN = 'pvai1_exampleplaintexttoken';

    /** @var array{view: string, data: array<string, mixed>}|null */
    private ?array $rendered = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rendered = null;
    }

    /**
     * Build the controller with a stub engine and capture render() calls.
     *
     * @param array<string, mixed> $post
     */
    private function buildController(array $post): AiAdminController
    {
        $capture = function (string $view, array $data): void {
            $this->rendered = ['view' => $view, 'data' => $data];
        };

        $app = $this->app([
            'request' => static fn (): object => new class ($post) {
                public Collection $data;

                /** @param array<string, mixed> $data */
                public function __construct(array $data)
                {
                    $this->data = new Collection($data);
                }

                public function getBaseUrl(): string
                {
                    return 'https://example.test';
                }
            },
            'settings' => static fn (): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return 'https://example.test';
                }
            },
            'pluginLoader' => static fn (): object => new class {
                public function routePrefix(string $package): string
                {
                    return '/ai';
                }
            },
            'db' => static fn (): \PDO => Sqlite::recreate(),
            'ai' => static fn (): object => new class (self::TOKEN) {
                public function __construct(private string $plain)
                {
                }

                /** @return array{key: object, plain: string} */
                public function createKey(string $name): array
                {
                    return [
                        'key'   => (object) ['id' => 1, 'name' => $name],
                        'plain' => $this->plain,
                    ];
                }

                /** @return list<array<string, mixed>> */
                public function listKeys(): array
                {
                    return [];
                }

                /** @return list<array<string, mixed>> */
                public function helpGroups(): array
                {
                    return [];
                }

                /** @return list<array<string, mixed>> */
                public function recentLogs(int $limit): array
                {
                    return [];
                }

                public function defaultAuthorId(): ?int
                {
                    return null;
                }
            },
        ]);

        return new class ($app, $capture) extends AiAdminController {
            /** @var \Closure(string, array<string, mixed>): void */
            private \Closure $capture;

            /**
             * @param \Closure(string, array<string, mixed>): void $capture
             */
            public function __construct(Engine $app, \Closure $capture)
            {
                parent::__construct($app);
                $this->capture = $capture;
            }

            /** @param array<string, mixed> $data */
            protected function render(string $view, array $data = [], bool $layout = true): void
            {
                ($this->capture)($view, $data);
            }
        };
    }

    public function testCreateKeyPassesTheTokenToTheView(): void
    {
        $controller = $this->buildController(['name' => 'Alpha']);

        $controller->createKey();

        self::assertNotNull($this->rendered);
        self::assertSame('pubvana/ai/admin/manage', $this->rendered['view']);
        self::assertSame(self::TOKEN, $this->rendered['data']['plainToken']);
    }

    public function testCreateKeyDoesNotUseTheSession(): void
    {
        // 'session' is deliberately unmapped; Engine::__call() throws if
        // the controller reaches for it. Completing means no flash call.
        $controller = $this->buildController(['name' => 'Beta']);

        $controller->createKey();

        self::assertNotNull($this->rendered);
    }

    public function testManageRendersWithoutAToken(): void
    {
        $controller = $this->buildController([]);

        $controller->manage();

        self::assertNotNull($this->rendered);
        self::assertSame('pubvana/ai/admin/manage', $this->rendered['view']);
        self::assertNull($this->rendered['data']['plainToken']);
    }
}
