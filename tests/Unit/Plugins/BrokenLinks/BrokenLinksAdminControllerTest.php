<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\BrokenLinks;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\BrokenLinks\Controllers\BrokenLinksAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * BrokenLinksAdminController: every source links to its own editor.
 *
 * Pages registers the route prefix 'page' (plugins/Pages/Config/Config.php),
 * so a hardcoded '/admin/pages' builds a URL with no route behind it. The
 * controller reads each owner plugin's prefix instead.
 */
#[CoversClass(BrokenLinksAdminController::class)]
final class BrokenLinksAdminControllerTest extends TestCase
{
    /** @var list<array{view: string, data: array<string, mixed>}> */
    public array $fetches = [];

    /** Whether the last render asked for dismissed rows. */
    public bool $showDismissed = false;

    public function testIndexBuildsEditorUrlsFromPluginRoutePrefixes(): void
    {
        $this->renderIndex();

        $data = $this->fetches[0]['data'];

        self::assertSame('pubvana/brokenlinks/admin/index', $this->fetches[0]['view']);
        self::assertSame('/admin/broken-links', $data['adminBase']);
        self::assertSame('/admin/blog', $data['editBase']['post']);
        self::assertSame('/admin/page', $data['editBase']['page']);
    }

    public function testIndexPassesDismissedFlagToService(): void
    {
        $this->renderIndex(['dismissed' => '1']);

        self::assertTrue($this->showDismissed);
        self::assertTrue($this->fetches[0]['data']['showDismissed']);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function renderIndex(array $query = []): void
    {
        $test = $this;
        $app = $this->app([
            'settings' => static fn(): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return $default;
                }
            },
            'request' => static fn(): object => new class($query) {
                public Collection $query;
                /** @param array<string, mixed> $q */
                public function __construct(array $q)
                {
                    $this->query = new Collection($q);
                }
            },
            'brokenLinks' => static fn(): object => new class($test) {
                public function __construct(private BrokenLinksAdminControllerTest $t)
                {
                }

                /** @return list<array<string, mixed>> */
                public function all(bool $showDismissed = false): array
                {
                    $this->t->showDismissed = $showDismissed;

                    return [[
                        'source_type'  => 'page',
                        'source_id'    => 7,
                        'source_title' => 'About',
                        'links'        => [],
                    ]];
                }

                public function countBroken(): int
                {
                    return 1;
                }
            },
            // Prefixes as the plugins declare them: Blog '/blog', Pages '/page'.
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $pluginId): string
                {
                    return match ($pluginId) {
                        'pubvana/blog'  => '/blog',
                        'pubvana/pages' => '/page',
                        default         => '/broken-links',
                    };
                }
            },
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }
            },
            'adext' => static fn(): object => new class {
                /**
                 * @param array<string, mixed> $context
                 * @return array<string, mixed>
                 */
                public function get(string $type, string $slot, array $context = []): array
                {
                    return [];
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private BrokenLinksAdminControllerTest $t)
                    {
                    }

                    /** @param array<string, mixed>|null $data */
                    public function fetch(string $view, ?array $data = null): string
                    {
                        $this->t->fetches[] = ['view' => $view, 'data' => $data ?? []];

                        return 'C:' . $view;
                    }
                };
            },
        ]);

        $app->set('admin.topNav', []);
        $app->map('render', function (string $template, array $data) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $template, 'data' => $data];
        });
        \Flight::setEngine($app);

        (new BrokenLinksAdminController($app))->index();
    }
}
