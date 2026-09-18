<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Config;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * env-overrides.php coverage.
 *
 * The file is idempotent include-glue over a Flight Engine: it seeds base
 * config, folds env vars in, and derives the HTTPS policy. Each test builds
 * a fresh Engine, sets process env, includes the file, and asserts the
 * resulting store. PUBVANA_ENV_LOADED is pre-defined so the .env load is
 * skipped (dotenv would otherwise read the repo .env).
 */
#[CoversNothing]
final class EnvOverridesTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $envBackup = [];

    /** @var list<string> */
    private const KEYS = [
        'APP_ENV', 'APP_DEBUG', 'FORCE_HTTPS', 'SITE_NAME', 'ADMIN_EMAIL', 'SITE_URL',
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SESSION_ENCRYPTION_KEY',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (!defined('PUBVANA_ENV_LOADED')) {
            define('PUBVANA_ENV_LOADED', true);
        }
        $this->envBackup = [];
        foreach (self::KEYS as $key) {
            $this->envBackup[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
            unset($_ENV[$key], $_SERVER[$key]);
        }
        parent::tearDown();
    }

    public function testSeedsBaseConfigWhenAbsent(): void
    {
        $app = $this->freshEngine();
        $this->includeOverrides($app);

        /** @var array<string, mixed> $db */
        $db = $app->get('database');
        self::assertSame('mysql', $db['driver']);
        self::assertSame('localhost', $db['host']);
        self::assertSame(3306, $db['port']);
        self::assertSame('production', $app->get('environment'));
        self::assertSame('/', $app->get('flight.base_url'));
        self::assertSame([], $app->get('plugins'));
        // No FORCE_HTTPS: off by default regardless of APP_ENV.
        self::assertFalse($app->get('flight.force_https'));
    }

    public function testScalarKeysFoldOntoStore(): void
    {
        $app = $this->freshEngine();
        putenv('APP_ENV=development');
        putenv('SITE_NAME=My Site');
        putenv('ADMIN_EMAIL=a@b.test');
        putenv('SITE_URL=https://example.test');
        $this->includeOverrides($app);

        self::assertSame('development', $app->get('environment'));
        self::assertSame('My Site', $app->get('CMS.siteName'));
        self::assertSame('a@b.test', $app->get('CMS.adminEmail'));
        self::assertSame('https://example.test', $app->get('CMS.siteUrl'));
        // No FORCE_HTTPS: off by default regardless of APP_ENV.
        self::assertFalse($app->get('flight.force_https'));
    }

    public function testDbVarsFoldIntoDatabaseArray(): void
    {
        $app = $this->freshEngine();
        putenv('DB_HOST=dbhost');
        putenv('DB_PORT=1234');
        putenv('DB_NAME=mydb');
        putenv('DB_USER=u');
        putenv('DB_PASS=p');
        $this->includeOverrides($app);

        /** @var array<string, mixed> $db */
        $db = $app->get('database');
        self::assertSame('dbhost', $db['host']);
        self::assertSame('1234', $db['port']);
        self::assertSame('mydb', $db['dbname']);
        self::assertSame('u', $db['user']);
        self::assertSame('p', $db['password']);
    }

    public function testSessionKeyFoldsIntoNestedPluginsArray(): void
    {
        $app = $this->freshEngine();
        putenv('SESSION_ENCRYPTION_KEY=secret123');
        $this->includeOverrides($app);

        /** @var array<string, mixed> $plugins */
        $plugins = $app->get('plugins');
        self::assertSame('secret123', $plugins['enlivenapp/flight-sessions']['encryption_key']);
    }

    public function testSessionKeyReseedsNonArrayPlugins(): void
    {
        $app = $this->freshEngine();
        // A non-array plugins value is reseeded to [] first, then the fold lands.
        $app->set('plugins', 'not-an-array');
        putenv('SESSION_ENCRYPTION_KEY=secret123');
        $this->includeOverrides($app);

        /** @var array<string, mixed> $plugins */
        $plugins = $app->get('plugins');
        self::assertSame('secret123', $plugins['enlivenapp/flight-sessions']['encryption_key']);
    }

    public function testAppDebugFollowsEnvironmentOnly(): void
    {
        // No APP_ENV, no APP_DEBUG: production default, debug off.
        $app = $this->freshEngine();
        $this->includeOverrides($app);
        self::assertSame('production', $app->get('environment'));
        self::assertFalse($app->get('flight.debug'));

        // APP_ENV=development with no APP_DEBUG: debug on.
        $app2 = $this->freshEngine();
        putenv('APP_ENV=development');
        $this->includeOverrides($app2);
        self::assertSame('development', $app2->get('environment'));
        self::assertTrue($app2->get('flight.debug'));
    }

    public function testAppDebugIsIgnored(): void
    {
        // Explicit APP_DEBUG=true cannot enable debug in production.
        $app = $this->freshEngine();
        putenv('APP_DEBUG=true');
        $this->includeOverrides($app);
        self::assertFalse($app->get('flight.debug'));

        // Explicit APP_DEBUG=false cannot disable debug in development.
        $app2 = $this->freshEngine();
        putenv('APP_ENV=development');
        putenv('APP_DEBUG=false');
        $this->includeOverrides($app2);
        self::assertTrue($app2->get('flight.debug'));

        // Junk APP_DEBUG is ignored entirely.
        $app3 = $this->freshEngine();
        putenv('APP_ENV=production');
        putenv('APP_DEBUG=banana');
        $this->includeOverrides($app3);
        self::assertFalse($app3->get('flight.debug'));
    }

    public function testForceHttpsExplicitBeatsDefault(): void
    {
        $app = $this->freshEngine();
        putenv('FORCE_HTTPS=false');
        $this->includeOverrides($app);
        self::assertFalse($app->get('flight.force_https'));

        $app2 = $this->freshEngine();
        putenv('APP_ENV=development');
        putenv('FORCE_HTTPS=true');
        $this->includeOverrides($app2);
        self::assertTrue($app2->get('flight.force_https'));
    }

    public function testForceHttpsJunkFallsBackToDefault(): void
    {
        $app = $this->freshEngine();
        putenv('FORCE_HTTPS=banana');
        $this->includeOverrides($app);
        // Junk is ignored: default off.
        self::assertFalse($app->get('flight.force_https'));
    }

    public function testIdempotentDoubleInclude(): void
    {
        $app = $this->freshEngine();
        putenv('SITE_NAME=Once');
        $this->includeOverrides($app);
        putenv('SITE_NAME=Twice');
        $this->includeOverrides($app);

        self::assertSame('Twice', $app->get('CMS.siteName'));
        /** @var array<string, mixed> $db */
        $db = $app->get('database');
        self::assertSame('mysql', $db['driver']);
    }

    public function testPreservesExistingBaseConfig(): void
    {
        $app = $this->freshEngine();
        $app->set('database', ['driver' => 'sqlite', 'host' => 'x']);
        $app->set('environment', 'staging');
        $app->set('flight.base_url', '/sub');
        $this->includeOverrides($app);

        /** @var array<string, mixed> $db */
        $db = $app->get('database');
        self::assertSame('sqlite', $db['driver']);
        self::assertSame('staging', $app->get('environment'));
        self::assertSame('/sub', $app->get('flight.base_url'));
    }

    private function freshEngine(): Engine
    {
        $app = new Engine();
        $app->init();
        \Flight::setEngine($app);

        return $app;
    }

    private function includeOverrides(Engine $app): void
    {
        require PROJECT_ROOT . '/app/config/env-overrides.php';
    }
}
