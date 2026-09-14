<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\SettingsController;
use Pubvana\Tests\Support\TestCase;

/**
 * SettingsController coverage.
 */
#[CoversClass(SettingsController::class)]
final class SettingsControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    public FakeSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->settings = new FakeSettings();
    }

    public function testGeneralRendersTabs(): void
    {
        $app = $this->engine(query: ['saved' => '1']);
        (new SettingsController($app))->general();

        self::assertSame('admin/settings/general', $this->fetches[0]['view']);
        self::assertSame('Settings', $this->fetches[0]['data']['pageTitle']);
        self::assertTrue($this->fetches[0]['data']['saved']);
        self::assertCount(1, $this->fetches[0]['data']['tabs']);
        self::assertSame('stored', $this->fetches[0]['data']['tabs'][0]['fields'][0]['value']);
    }

    public function testGeneralSkipsEmptyTabsAndBadFields(): void
    {
        $this->settings->tabs = [
            ['label' => 'Empty', 'fields' => [['key' => '', 'label' => '', 'type' => 'text']]],
            ['label' => 'Bad', 'fields' => [['key' => 'X.y', 'label' => 'Y', 'type' => 'nope']]],
        ];
        $app = $this->engine();
        (new SettingsController($app))->general();

        self::assertSame([], $this->fetches[0]['data']['tabs']);
    }

    public function testSaveWritesDeclaredAndHandlesCheckboxAbsent(): void
    {
        $app = $this->engine(data: ['settings' => ['CMS.siteName' => ' Hi ']]);
        (new SettingsController($app))->save();

        self::assertSame('Hi', $this->settings->stored['CMS.siteName']);
        self::assertFalse($this->settings->stored['CMS.flag']);
        self::assertSame(['/admin/settings'], $this->redirects);
        self::assertStringContainsString('Saved 2 settings.', $this->flashes['settings_flash'][0]);
    }

    public function testSaveRejectsBadValuesAndReportsNothing(): void
    {
        $this->settings->throwOn = ['CMS.count' => true];
        $app = $this->engine(data: ['settings' => ['CMS.count' => 'nan', 'CMS.siteName' => 'x']]);
        // Only post declared keys; drop siteName to force reject path only
        $app2 = $this->engine(data: ['settings' => ['CMS.count' => 'nan']]);
        // Remove checkbox default by making declared without checkbox
        $this->settings->declared = ['CMS.count' => ['key' => 'CMS.count', 'label' => 'Count', 'type' => 'number']];
        (new SettingsController($app2))->save();

        self::assertStringContainsString('Nothing to save.', $this->flashes['settings_flash'][0]);
        self::assertStringContainsString('Rejected:', $this->flashes['settings_flash'][0]);
    }

    public function testCoerceVariants(): void
    {
        $c = new SettingsController($this->engine());
        self::assertSame(5, $this->invoke($c, 'coerce', [['type' => 'number'], '5']));
        self::assertSame(5.5, $this->invoke($c, 'coerce', [['type' => 'number'], '5.5']));
        self::assertTrue($this->invoke($c, 'coerce', [['type' => 'checkbox'], '1']));
        self::assertSame('a@b.test', $this->invoke($c, 'coerce', [['type' => 'email'], 'a@b.test']));
        self::assertSame('hi', $this->invoke($c, 'coerce', [['type' => 'text'], ' hi ']));
        try {
            $this->invoke($c, 'coerce', [['type' => 'number'], 'nan']);
            self::fail('must throw');
        } catch (\InvalidArgumentException) {
        }
        try {
            $this->invoke($c, 'coerce', [['type' => 'email'], 'bad']);
            self::fail('must throw');
        } catch (\InvalidArgumentException) {
        }
        try {
            $this->invoke($c, 'coerce', [['type' => 'select', 'key' => 'CMS.homepagePageId', 'options' => []], '9']);
            self::fail('must throw');
        } catch (\InvalidArgumentException) {
        }
    }

    public function testResolveOptionsFillsHomepage(): void
    {
        $c = new SettingsController($this->engine(pagesOptions: ['1' => 'Home']));
        $field = ['type' => 'select', 'key' => 'CMS.homepagePageId', 'options' => []];
        $this->invoke($c, 'resolveOptions', [&$field]);
        self::assertSame(['1' => 'Home'], $field['options']);

        $other = ['type' => 'select', 'key' => 'X.y', 'options' => []];
        $this->invoke($c, 'resolveOptions', [&$other]);
        self::assertSame([], $other['options']);

        $filled = ['type' => 'select', 'key' => 'CMS.homepagePageId', 'options' => ['a' => 'b']];
        $this->invoke($c, 'resolveOptions', [&$filled]);
        self::assertSame(['a' => 'b'], $filled['options']);
    }

    private function engine(array $data = [], array $query = [], array $pagesOptions = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data, $query) {
                public Collection $data;
                public Collection $query;
                public function __construct(array $d, array $q)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($q);
                }
            },
            'settings' => fn(): FakeSettings => $this->settings,
            'adext' => fn(): object => new class($test) {
                public function __construct(private SettingsControllerTest $t)
                {
                }
                /** @return array<string, mixed> */
                public function get(string $type, string $slot, array $c = []): array
                {
                    return ['core' => ['label' => 'General', 'fields' => $this->t->settings->tabsFields()]];
                }
            },
            'pages' => static fn(): object => new class($pagesOptions) {
                public function __construct(private array $opts)
                {
                }
                /** @return array<string, string> */
                public function publishedOptions(): array
                {
                    return $this->opts;
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private SettingsControllerTest $t)
                {
                }
                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
                }
                public function pullFlash(string $k): mixed
                {
                    return 'pulled';
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private SettingsControllerTest $t)
                    {
                    }
                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->t->fetches[] = ['view' => $v, 'data' => $d ?? []];

                        return 'C:' . $v;
                    }
                };
            },
        ]);
        $app->set('admin.topNav', []);
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Settings double.
 */
final class FakeSettings
{
    /** @var array<string, mixed> */
    public array $stored = [];
    /** @var array<string, array<string, mixed>> */
    public array $declared = [
        'CMS.siteName' => ['key' => 'CMS.siteName', 'label' => 'Name', 'type' => 'text'],
        'CMS.flag' => ['key' => 'CMS.flag', 'label' => 'Flag', 'type' => 'checkbox'],
        'CMS.count' => ['key' => 'CMS.count', 'label' => 'Count', 'type' => 'number'],
    ];
    /** @var array<string, bool> */
    public array $throwOn = [];
    /** @var list<array<string, mixed>>|null */
    public ?array $tabs = null;

    /** @return list<array<string, mixed>> */
    public function tabsFields(): array
    {
        if ($this->tabs !== null) {
            $out = [];
            foreach ($this->tabs as $t) {
                $out = array_merge($out, $t['fields'] ?? []);
            }

            return $out;
        }

        return [
            ['key' => 'CMS.siteName', 'label' => 'Name', 'type' => 'text', 'default' => 'd'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function declaredFields(): array
    {
        return $this->declared;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->stored[$key] ?? 'stored';
    }

    public function set(string $key, mixed $value): void
    {
        if (isset($this->throwOn[$key])) {
            throw new \InvalidArgumentException('bad');
        }
        $this->stored[$key] = $value;
    }
}
