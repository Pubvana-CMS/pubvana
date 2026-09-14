<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\Setting;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use stdClass;

/**
 * SettingsService over the in-memory settings table.
 *
 * Covers the four-tier resolution order (DB row > app store >
 * declaration default > caller default), namespace merges, write and
 * delete paths, per-type encoding round-trips, the failed-bulk-load
 * fallback read, and the adext declaration scan (validation, skip
 * logging, duplicate handling, select options normalization).
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(SettingsService::class)]
final class SettingsServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    // -----------------------------------------------------------------
    // Reads: resolution order
    // -----------------------------------------------------------------

    public function testGetDatabaseRowBeatsTheAppStore(): void
    {
        $app = $this->makeApp();
        $app->set('CMS.siteName', 'EnvValue');
        $this->insertRow('CMS.siteName', 'DbValue', 'string');

        $service = new SettingsService($app);

        self::assertSame('DbValue', $service->get('CMS.siteName'));
    }

    public function testGetFallsBackToTheAppStoreValue(): void
    {
        $app = $this->makeApp();
        $app->set('CMS.siteName', 'EnvValue');

        $service = new SettingsService($app);

        self::assertSame('EnvValue', $service->get('CMS.siteName'));
    }

    public function testGetUsesDeclarationFallbackKeyForAppStoreLookup(): void
    {
        $app = $this->makeApp();
        $app->set('CMS.byline', 'FromConfig');
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'core.tabs', [
            'label'  => 'General',
            'fields' => [
                [
                    'key'      => 'CMS.siteByline',
                    'label'    => 'Byline',
                    'type'     => 'text',
                    'fallback' => 'CMS.byline',
                ],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame('FromConfig', $service->get('CMS.siteByline'));
    }

    public function testGetDeclarationDefaultBeatsCallerDefault(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'core.tabs', [
            'label'  => 'General',
            'fields' => [
                [
                    'key'     => 'CMS.footer',
                    'label'   => 'Footer text',
                    'type'    => 'text',
                    'default' => 'Default footer',
                ],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame('Default footer', $service->get('CMS.footer', 'Caller footer'));
    }

    public function testGetCallerDefaultIsTheFinalSafetyNet(): void
    {
        $app = $this->makeApp();
        $service = new SettingsService($app);

        self::assertSame('fallback', $service->get('nothing.anywhere', 'fallback'));
        self::assertNull($service->get('nothing.anywhere'));
    }

    public function testHasResolvesThroughEveryTier(): void
    {
        $app = $this->makeApp();
        $this->insertRow('t.nullable', null, 'NULL');
        $service = new SettingsService($app);

        self::assertFalse($service->has('nothing.anywhere'));

        $app->set('CMS.copyright', '2026');
        self::assertTrue($service->has('CMS.copyright'));

        self::assertTrue($service->has('t.nullable'), 'a stored NULL row IS the value');
    }

    // -----------------------------------------------------------------
    // Reads: namespace merge (all())
    // -----------------------------------------------------------------

    public function testAllMergesDefaultsAppStoreAndRows(): void
    {
        $app = $this->makeApp();
        $app->set('CMS.siteName', 'EnvName');
        $app->set('CMS.byline', 'ConfigByline');
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'core.tabs', [
            'label'  => 'General',
            'fields' => [
                ['key' => 'CMS.siteName', 'label' => 'Site name', 'type' => 'text'],
                [
                    'key'      => 'CMS.siteByline',
                    'label'    => 'Byline',
                    'type'     => 'text',
                    'fallback' => 'CMS.byline',
                ],
                ['key' => 'CMS.footer', 'label' => 'Footer', 'type' => 'text', 'default' => 'Default footer'],
            ],
        ]);
        $this->insertRow('CMS.siteName', 'DbName', 'string');
        $this->insertRow('legacy.orphan', 'kept', 'string');

        $service = new SettingsService($app);

        $merged = $service->all('CMS');

        self::assertSame('DbName', $merged['CMS.siteName'], 'DB row is strongest');
        self::assertSame('ConfigByline', $merged['CMS.siteByline'], 'app store beats declaration default');
        self::assertSame('Default footer', $merged['CMS.footer'], 'declaration default is weakest');
        self::assertArrayNotHasKey('legacy.orphan', $merged, 'another namespace is not merged in');

        self::assertSame(['kept'], array_values($service->all('legacy')));
    }

    // -----------------------------------------------------------------
    // Writes
    // -----------------------------------------------------------------

    public function testSetInsertsRowAndIsImmediatelyReadable(): void
    {
        $app = $this->makeApp();
        $service = new SettingsService($app);

        $service->set('blog.postsPerPage', 10);

        self::assertSame(10, $service->get('blog.postsPerPage'));
        $row = $this->fetchRow('blog.postsPerPage');
        self::assertSame('10', $row['value']);
        self::assertSame('integer', $row['type']);
        self::assertSame(1, $row['autoload']);
    }

    public function testSetUpdatesAnExistingRow(): void
    {
        $app = $this->makeApp();
        $service = new SettingsService($app);

        $service->set('blog.postsPerPage', 10);
        $service->set('blog.postsPerPage', 20);

        self::assertSame(20, $service->get('blog.postsPerPage'));
        self::assertCount(1, $this->allRows());
    }

    public function testSetRejectsMalformedKeys(): void
    {
        $app = $this->makeApp();
        $service = new SettingsService($app);

        $bad = ['', 'noDot', '1bad.key', 'ns.', 'ns.white space'];

        foreach ($bad as $key) {
            try {
                $service->set($key, 'v');
                self::fail("Expected InvalidArgumentException for key '{$key}'");
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString($key, $e->getMessage());
            }
        }

        self::assertCount(0, $this->allRows(), 'no row written for a rejected key');
    }

    public function testSetRespectsDeclaredAutoloadFlag(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'email', 'core.smtp', [
            'label'  => 'Email',
            'fields' => [
                [
                    'key'      => 'Mail.transport',
                    'label'    => 'Transport',
                    'type'     => 'select',
                    'options'  => ['log' => 'Log'],
                    'autoload' => false,
                ],
            ],
        ]);

        $service = new SettingsService($app);
        $service->set('Mail.transport', 'log');

        self::assertSame(0, $this->fetchRow('Mail.transport')['autoload']);
        self::assertSame('log', $service->get('Mail.transport'));
    }

    public function testForgetRemovesTheRowAndFallsBackDownTheChain(): void
    {
        $app = $this->makeApp();
        $app->set('CMS.siteName', 'EnvValue');
        $this->insertRow('CMS.siteName', 'DbValue', 'string');

        $service = new SettingsService($app);
        self::assertSame('DbValue', $service->get('CMS.siteName'));

        $service->forget('CMS.siteName');

        self::assertSame('EnvValue', $service->get('CMS.siteName'));
        self::assertNull($this->fetchRow('CMS.siteName'));
    }

    // -----------------------------------------------------------------
    // Encoding round-trips
    // -----------------------------------------------------------------

    public function testEncodePerTypeRoundTripsThroughTheStore(): void
    {
        $app = $this->makeApp();
        $service = new SettingsService($app);

        $cargo = new stdClass();
        $cargo->name = 'x';

        $service->set('t.bool', true);
        $service->set('t.boolFalse', false);
        $service->set('t.int', 42);
        $service->set('t.float', 1.5);
        $service->set('t.string', 'hi');
        $service->set('t.array', ['a' => 1]);
        $service->set('t.object', $cargo);
        $service->set('t.null', null);

        self::assertTrue($service->get('t.bool'));
        self::assertFalse($service->get('t.boolFalse'));
        self::assertSame(42, $service->get('t.int'));
        self::assertSame(1.5, $service->get('t.float'));
        self::assertSame('hi', $service->get('t.string'));
        self::assertSame(['a' => 1], $service->get('t.array'));
        self::assertSame('x', $service->get('t.object')->name);
        self::assertNull($service->get('t.null'));

        self::assertSame('1', $this->fetchRow('t.bool')['value']);
        self::assertSame('boolean', $this->fetchRow('t.bool')['type']);
        self::assertSame('0', $this->fetchRow('t.boolFalse')['value']);
        self::assertSame('42', $this->fetchRow('t.int')['value']);
        self::assertSame('1.5', $this->fetchRow('t.float')['value']);
        self::assertSame('hi', $this->fetchRow('t.string')['value']);
        self::assertSame('{"a":1}', $this->fetchRow('t.array')['value']);
        self::assertSame('{"name":"x"}', $this->fetchRow('t.object')['value']);
        self::assertSame(null, $this->fetchRow('t.null')['value']);
        self::assertSame('NULL', $this->fetchRow('t.null')['type']);
    }

    // -----------------------------------------------------------------
    // Failed bulk load (fresh install, table missing)
    // -----------------------------------------------------------------

    public function testPerKeyFallbackReadWhenBulkLoadFailed(): void
    {
        $app = $this->makeApp();

        // Drop the table so the boot-time bulk load fails; the service
        // must degrade to per-key reads instead of breaking boot.
        $this->pdo->exec('DROP TABLE settings');
        $service = new SettingsService($app);
        self::assertNull($service->get('CMS.siteName'));

        // The table comes back (migrations run later during plugin
        // loading) and a row appears: the lazy per-key read finds it.
        Sqlite::recreate();
        $this->insertRow('CMS.siteName', 'LateRow', 'string');

        self::assertSame('LateRow', $service->get('CMS.siteName'));
    }

    public function testPerKeyReadFailureIsSwallowed(): void
    {
        $app = $this->makeApp();
        $this->pdo->exec('DROP TABLE settings');

        $service = new SettingsService($app);

        self::assertNull($service->get('CMS.anything'), 'missing table reads resolve to null, not an exception');
        self::assertNull($service->get('CMS.anything'), 'the negative cache keeps repeats silent and cheap');
    }

    // -----------------------------------------------------------------
    // Declarations (adext admin.settings scan)
    // -----------------------------------------------------------------

    public function testDeclaredFieldsGathersAcrossSlotsAndIndexesByKey(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'core.tabs', [
            'label'  => 'General',
            'fields' => [
                ['key' => 'CMS.siteName', 'label' => 'Site name', 'type' => 'text'],
            ],
        ]);
        $registry->register('admin.settings', 'email', 'core.smtp', [
            'label'  => 'Email',
            'fields' => [
                ['key' => 'Mail.from', 'label' => 'From', 'type' => 'email'],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame(
            ['CMS.siteName', 'Mail.from'],
            array_keys($service->declaredFields())
        );
        self::assertSame(
            $service->declaredFields(),
            $service->declaredFields(),
            'memoized: the scan runs once'
        );
    }

    public function testDeclaredFieldsSkipsInvalidContributions(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'bad.plugin', [
            'label'  => 'Broken',
            'fields' => [
                // Missing label
                ['key' => 'bad.one', 'type' => 'text'],
                // Missing key
                ['label' => 'No key', 'type' => 'text'],
                // Missing type
                ['key' => 'bad.two', 'label' => 'No type'],
                // Unknown type
                ['key' => 'bad.three', 'label' => 'Bad type', 'type' => 'color'],
                // Key is not namespaced dot notation
                ['key' => 'noDot', 'label' => 'Bad key', 'type' => 'text'],
            ],
        ]);
        $registry->register('admin.settings', 'general', 'good.plugin', [
            'label'  => 'Good',
            'fields' => [
                ['key' => 'good.key', 'label' => 'Fine', 'type' => 'textarea'],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame(['good.key'], array_keys($service->declaredFields()));
    }

    public function testDeclaredFieldsDuplicateKeysFirstContributorWins(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'first.plugin', [
            'label'  => 'First',
            'fields' => [
                ['key' => 'CMS.footer', 'label' => 'First footer', 'type' => 'text'],
            ],
        ]);
        $registry->register('admin.settings', 'general', 'second.plugin', [
            'label'  => 'Second',
            'fields' => [
                ['key' => 'CMS.footer', 'label' => 'Second footer', 'type' => 'text'],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame('First footer', $service->declaredFields()['CMS.footer']['label']);
    }

    public function testDeclaredFieldsNormalizesSelectOptionsInPlace(): void
    {
        $app = $this->makeApp();
        $registry = $this->adext($app);
        $registry->register('admin.settings', 'general', 'core.tabs', [
            'label'  => 'General',
            'fields' => [
                // Declared with no options: filled lazily, save-time
                // validation still guards the value.
                ['key' => 'CMS.layout', 'label' => 'Layout', 'type' => 'select'],
            ],
        ]);

        $service = new SettingsService($app);

        self::assertSame([], $service->declaredFields()['CMS.layout']['options']);
    }

    // -----------------------------------------------------------------
    // Setup helpers
    // -----------------------------------------------------------------

    /**
     * Fresh Engine with the in-memory PDO and a real ExtensionRegistry
     * mapped, the way services.php wires the store.
     *
     * @return \flight\Engine<object>
     */
    private function makeApp(): \flight\Engine
    {
        return $this->app([
            'db'    => fn(): PDO => $this->pdo,
            'adext' => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
        ]);
    }

    /**
     * The mapped ExtensionRegistry, so tests can register declarations.
     */
    private function adext(\flight\Engine $app): ExtensionRegistry
    {
        return $app->adext();
    }

    /**
     * Wrap a lazy provider in a per-engine singleton guard, the same
     * pattern PasswordResetServiceTest uses for Flight-mapped services.
     *
     * @param callable $provider Zero-argument factory
     * @return callable Singleton-guarded factory
     */
    private function singleton(callable $provider): callable
    {
        return function () use ($provider) {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }
            return $instance;
        };
    }

    /**
     * Insert a settings row directly, bypassing set().
     */
    private function insertRow(string $key, ?string $value, string $type): Setting
    {
        $setting = new Setting($this->pdo);
        $setting->key = $key;
        $setting->value = $value;
        $setting->type = $type;
        $setting->autoload = true;
        $setting->insert();

        return $setting;
    }

    /**
     * @return array{value: ?string, type: string, autoload: int}|null
     */
    private function fetchRow(string $key): ?array
    {
        $statement = $this->pdo->prepare('SELECT value, type, autoload FROM settings WHERE key = :key');
        $statement->execute(['key' => $key]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allRows(): array
    {
        return $this->pdo->query('SELECT * FROM settings')->fetchAll(PDO::FETCH_ASSOC);
    }
}
