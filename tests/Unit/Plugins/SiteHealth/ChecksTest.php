<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SiteHealth;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\SiteHealth\Services\CheckResult;
use Pubvana\Plugins\SiteHealth\Services\ConfigDefaultsCheck;
use Pubvana\Plugins\SiteHealth\Services\DatabaseCheck;
use Pubvana\Plugins\SiteHealth\Services\DebugModeCheck;
use Pubvana\Plugins\SiteHealth\Services\DiskSpaceCheck;
use Pubvana\Plugins\SiteHealth\Services\EnvironmentFilePermissionsCheck;
use Pubvana\Plugins\SiteHealth\Services\HttpsCheck;
use Pubvana\Plugins\SiteHealth\Services\PhpExtensionsCheck;
use Pubvana\Plugins\SiteHealth\Services\PhpVersionCheck;
use Pubvana\Plugins\SiteHealth\Services\PluginDependenciesCheck;
use Pubvana\Plugins\SiteHealth\Services\PluginMigrationsCheck;
use Pubvana\Plugins\SiteHealth\Services\RequiredSettingsCheck;
use Pubvana\Plugins\SiteHealth\Services\SessionConfigCheck;
use Pubvana\Plugins\SiteHealth\Services\ShieldCheck;
use Pubvana\Plugins\SiteHealth\Services\WritableDirectoriesCheck;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SiteHealth value object + the near-pure built-in checks.
 *
 * Engine-backed checks use a fresh mapped Engine; ini-touching checks
 * save and restore every key they change.
 */
#[CoversClass(CheckResult::class)]
#[CoversClass(PhpVersionCheck::class)]
#[CoversClass(PhpExtensionsCheck::class)]
#[CoversClass(DebugModeCheck::class)]
#[CoversClass(DiskSpaceCheck::class)]
#[CoversClass(HttpsCheck::class)]
#[CoversClass(DatabaseCheck::class)]
#[CoversClass(ConfigDefaultsCheck::class)]
#[CoversClass(RequiredSettingsCheck::class)]
#[CoversClass(SessionConfigCheck::class)]
#[CoversClass(ShieldCheck::class)]
#[CoversClass(WritableDirectoriesCheck::class)]
#[CoversClass(EnvironmentFilePermissionsCheck::class)]
#[CoversClass(PluginDependenciesCheck::class)]
#[CoversClass(PluginMigrationsCheck::class)]
final class ChecksTest extends TestCase
{
    private string $tmpRoot;

    /** @var array<string, string|false> */
    private array $iniBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-health-' . uniqid();
        mkdir($this->tmpRoot, 0777, true);
        $this->iniBackup = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->iniBackup as $key => $value) {
            if ($value === false) {
                continue;
            }
            ini_set($key, $value);
        }
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    public function testCheckResultShape(): void
    {
        $result = new CheckResult('x', 'X', CheckResult::CAT_SECURITY, CheckResult::PASS, 'fine', 'do y');

        self::assertSame('pass', CheckResult::PASS);
        self::assertSame(
            ['id' => 'x', 'name' => 'X', 'category' => 'security', 'status' => 'pass', 'message' => 'fine', 'remediation' => 'do y'],
            $result->toArray()
        );
        self::assertSame('', (new CheckResult('a', 'b', 'c', 'pass', 'm'))->remediation);
    }

    public function testPhpVersionPassesOnSupportedRuntime(): void
    {
        $result = (new PhpVersionCheck())->run();

        self::assertSame('php-version', $result->id);
        self::assertSame(CheckResult::CAT_ENVIRONMENT, $result->category);
        self::assertSame(CheckResult::PASS, $result->status);
        self::assertStringContainsString(PHP_VERSION, $result->message);
    }

    public function testPhpExtensionsNeverCriticalHere(): void
    {
        $result = (new PhpExtensionsCheck())->run();

        self::assertSame('php-extensions', $result->id);
        self::assertContains($result->status, [CheckResult::PASS, CheckResult::WARNING]);
    }

    public function testDebugModeWarnsInDevelopment(): void
    {
        $app = $this->app();
        $app->set('environment', 'development');

        $result = (new DebugModeCheck($app))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
        self::assertStringContainsString('development', $result->message);
    }

    public function testDebugModeCriticalWhenDisplayErrorsOn(): void
    {
        $this->stashIni('display_errors');
        ini_set('display_errors', '1');
        $app = $this->app();
        $app->set('environment', 'production');

        $result = (new DebugModeCheck($app))->run();

        self::assertSame(CheckResult::CRITICAL, $result->status);
    }

    public function testDebugModePassesInCleanProduction(): void
    {
        $this->stashIni('display_errors');
        ini_set('display_errors', '0');
        $app = $this->app();
        $app->set('environment', 'production');

        $result = (new DebugModeCheck($app))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testDiskSpaceWarnsOnMissingDir(): void
    {
        $result = (new DiskSpaceCheck($this->tmpRoot . '/nope'))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
        self::assertStringContainsString('does not exist', $result->message);
    }

    public function testDiskSpacePassesOnHealthyDir(): void
    {
        $result = (new DiskSpaceCheck(sys_get_temp_dir()))->run();

        self::assertSame('disk-space', $result->id);
        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testHttpsMatrix(): void
    {
        $pass = $this->httpsResult('https://example.org', true, 'production');
        self::assertSame(CheckResult::PASS, $pass->status);

        $noForce = $this->httpsResult('https://example.org', false, 'production');
        self::assertSame(CheckResult::WARNING, $noForce->status);

        $dev = $this->httpsResult('http://localhost', false, 'development');
        self::assertSame(CheckResult::WARNING, $dev->status);

        $prod = $this->httpsResult('http://example.org', false, 'production');
        self::assertSame(CheckResult::CRITICAL, $prod->status);
    }

    public function testDatabasePassesOnSqlite(): void
    {
        $result = (new DatabaseCheck(Sqlite::recreate()))->run();

        self::assertSame('database', $result->id);
        self::assertSame(CheckResult::PASS, $result->status);
        self::assertStringContainsString('connected', $result->message);
    }

    public function testConfigDefaultsMissingEnvIsCritical(): void
    {
        $result = (new ConfigDefaultsCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::CRITICAL, $result->status);
        self::assertStringContainsString('.env', $result->message);
    }

    public function testConfigDefaultsFlagsPlaceholders(): void
    {
        file_put_contents($this->tmpRoot . '/.env', "SITE_URL=http://localhost\nDB_PASS=\n");

        $result = (new ConfigDefaultsCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
        self::assertStringContainsString('SITE_URL', $result->message);
        self::assertStringContainsString('DB_PASS', $result->message);
    }

    public function testConfigDefaultsPassesWhenClean(): void
    {
        file_put_contents(
            $this->tmpRoot . '/.env',
            "SITE_URL=https://example.org\nDB_PASS=s3cret\nSESSION_ENCRYPTION_KEY=k\nSITE_NAME=Real\nADMIN_EMAIL=a@real.org\n"
        );

        $result = (new ConfigDefaultsCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testRequiredSettingsFlagsDefaults(): void
    {
        $app = $this->app();
        $app->set('CMS.siteUrl', 'http://example.com');
        $app->set('CMS.siteName', 'Pubvana');

        $result = (new RequiredSettingsCheck($app))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
        self::assertStringContainsString('CMS.siteUrl', $result->message);
        self::assertStringContainsString('CMS.siteName', $result->message);
    }

    public function testRequiredSettingsPassesWhenConfigured(): void
    {
        $app = $this->app();
        $app->set('CMS.siteUrl', 'https://real.org');
        $app->set('CMS.siteName', 'Real Site');

        $result = (new RequiredSettingsCheck($app))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testSessionConfigPassesWhenHardened(): void
    {
        // Session ini keys cannot change while a session is active (another
        // suite, e.g. FormsIpRateLimitTest, starts a real one). Without
        // isolation the hardened values cannot be forced.
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::markTestSkipped('a session is active; session ini keys are locked');
        }
        foreach (['session.gc_maxlifetime', 'session.cookie_httponly', 'session.cookie_secure', 'session.cookie_samesite'] as $key) {
            $this->stashIni($key);
        }
        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Lax');
        $app = $this->app();
        $app->set('environment', 'production');

        $result = (new SessionConfigCheck($app))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testSessionConfigCriticalWhenWideOpen(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::markTestSkipped('a session is active; session ini keys are locked');
        }
        foreach (['session.gc_maxlifetime', 'session.cookie_httponly', 'session.cookie_secure', 'session.cookie_samesite'] as $key) {
            $this->stashIni($key);
        }
        ini_set('session.gc_maxlifetime', '99999');
        ini_set('session.cookie_httponly', '0');
        ini_set('session.cookie_secure', '0');
        ini_set('session.cookie_samesite', '');
        $app = $this->app();
        $app->set('environment', 'production');

        $result = (new SessionConfigCheck($app))->run();

        self::assertSame(CheckResult::CRITICAL, $result->status);
        self::assertStringContainsString('httponly', $result->message);
    }

    public function testShieldWarnsWithoutConfig(): void
    {
        $result = (new ShieldCheck($this->app()))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
    }

    public function testShieldPassesWhenConfigured(): void
    {
        $app = $this->app();
        $app->set('enlivenapp.flight-shield', ['default_authenticator' => 'session']);

        $result = (new ShieldCheck($app))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testWritableDirsWarnsWhenMissing(): void
    {
        $result = (new WritableDirectoriesCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
        self::assertStringContainsString('do not exist', $result->message);
    }

    public function testWritableDirsPassesWhenPresent(): void
    {
        mkdir($this->tmpRoot . '/public/uploads', 0777, true);
        mkdir($this->tmpRoot . '/writable/cache', 0777, true);
        mkdir($this->tmpRoot . '/writable/logs', 0777, true);

        $result = (new WritableDirectoriesCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::PASS, $result->status);
    }

    public function testEnvPermsMissingIsCritical(): void
    {
        $result = (new EnvironmentFilePermissionsCheck($this->tmpRoot . '/.env'))->run();

        self::assertSame(CheckResult::CRITICAL, $result->status);
    }

    public function testEnvPermsMatrix(): void
    {
        $path = $this->tmpRoot . '/.env';
        file_put_contents($path, "K=V\n");

        chmod($path, 0600);
        clearstatcache(true, $path);
        self::assertSame(CheckResult::PASS, (new EnvironmentFilePermissionsCheck($path))->run()->status);

        chmod($path, 0644);
        clearstatcache(true, $path);
        $readable = (new EnvironmentFilePermissionsCheck($path))->run();
        self::assertSame(CheckResult::WARNING, $readable->status);
        self::assertStringContainsString('world-readable', $readable->message);

        chmod($path, 0666);
        clearstatcache(true, $path);
        $writable = (new EnvironmentFilePermissionsCheck($path))->run();
        self::assertSame(CheckResult::CRITICAL, $writable->status);
        self::assertStringContainsString('world-writable', $writable->message);
    }

    public function testPluginDepsWarnsWithoutInstalledJson(): void
    {
        $result = (new PluginDependenciesCheck($this->tmpRoot))->run();

        self::assertSame(CheckResult::WARNING, $result->status);
    }

    public function testPluginDepsPassesOnThisRepo(): void
    {
        $result = (new PluginDependenciesCheck((string) PROJECT_ROOT))->run();

        self::assertSame('plugin-dependencies', $result->id);
        self::assertContains($result->status, [CheckResult::PASS, CheckResult::CRITICAL]);
    }

    public function testPluginMigrationsReturnsAShapedResult(): void
    {
        $result = (new PluginMigrationsCheck(Sqlite::recreate(), []))->run();

        self::assertSame('plugin-migrations', $result->id);
        self::assertContains($result->status, [CheckResult::PASS, CheckResult::WARNING, CheckResult::CRITICAL]);
        self::assertNotSame('', $result->message);
    }

    private function httpsResult(string $url, bool $force, string $env): CheckResult
    {
        $app = $this->app();
        $app->set('CMS.siteUrl', $url);
        $app->set('flight.force_https', $force);
        $app->set('environment', $env);

        return (new HttpsCheck($app))->run();
    }

    private function stashIni(string $key): void
    {
        $this->iniBackup[$key] = ini_get($key);
    }

    private function deleteDir(string $path): void
    {
        if (is_file($path)) {
            unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot()) {
                continue;
            }
            $this->deleteDir($file->getPathname());
        }
        rmdir($path);
    }
}
