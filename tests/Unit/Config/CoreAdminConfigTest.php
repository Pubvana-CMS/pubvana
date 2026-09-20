<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Config;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Controllers\Admin\GroupsController;
use Pubvana\Controllers\Admin\NavigationController;
use Pubvana\Controllers\Admin\PermissionsController;
use Pubvana\Controllers\Admin\PluginsController;
use Pubvana\Controllers\Admin\SettingsController;
use Pubvana\Controllers\Admin\ThemesController;
use Pubvana\Controllers\Admin\UsersController;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * core-admin.php coverage.
 *
 * Includes the file against a fresh Engine with a real ExtensionRegistry
 * and asserts the topNav declaration, menu entries, settings declarations,
 * dashboard contributions, captcha area, cron registration, and stored
 * core routes. Dashboard/cron callables are exercised with lightweight
 * auth/trust doubles.
 */
#[CoversNothing]
final class CoreAdminConfigTest extends TestCase
{
    public bool $trustChecked = false;

    public function testTopNavStructure(): void
    {
        $app = $this->boot();

        /** @var array<string, mixed> $topNav */
        $topNav = $app->get('admin.topNav');
        self::assertSame(['content', 'appearance', 'plugins', 'tools', 'settings'], array_keys($topNav));
        self::assertSame('Content', $topNav['content']['label']);
        self::assertTrue($topNav['content']['core']);
        self::assertSame(['links', 'reports', 'maintenance'], array_keys($topNav['tools']['subLabels']));
        self::assertSame(['general', 'access', 'captcha', 'email'], array_keys($topNav['settings']['subLabels']));
    }

    public function testMenuEntries(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();

        $general = $adext->get('admin.menu', 'settings.general');
        self::assertArrayHasKey('pubvana.settings', $general);
        self::assertSame('/admin/settings', $general['pubvana.settings']['url']);

        $access = $adext->get('admin.menu', 'settings.access');
        foreach (['pubvana.login', 'pubvana.users', 'pubvana.groups', 'pubvana.permissions'] as $key) {
            self::assertArrayHasKey($key, $access, $key);
        }
        self::assertSame('/admin/users', $access['pubvana.users']['url']);

        $plugins = $adext->get('admin.menu', 'plugins');
        self::assertArrayHasKey('pubvana.plugins', $plugins);

        $appearance = $adext->get('admin.menu', 'appearance');
        self::assertArrayHasKey('pubvana.themes', $appearance);
        self::assertArrayHasKey('pubvana.navigation', $appearance);
    }

    public function testSettingsDeclarations(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();

        $general = $adext->get('admin.settings', 'general');
        self::assertArrayHasKey('pubvana.cms.site', $general);
        $siteFields = $general['pubvana.cms.site']['fields'];
        $keys = array_column($siteFields, 'key');
        foreach (['CMS.siteName', 'CMS.siteByline', 'CMS.siteUrl', 'CMS.adminEmail', 'CMS.defaultTimezone', 'CMS.homepageType'] as $key) {
            self::assertContains($key, $keys, $key);
        }

        // The Homepage select is driven by the adext 'homepage' registrations,
        // so core declares the source rather than an option list. The page
        // picker is no longer a core setting: the Pages provider declares it
        // as a field of its own.
        $homepage = null;
        foreach ($siteFields as $field) {
            if (($field['key'] ?? '') === 'CMS.homepageType') {
                $homepage = $field;
            }
        }
        self::assertIsArray($homepage);
        self::assertSame(['type' => 'homepage', 'slot' => 'provider'], $homepage['providers']);
        self::assertSame([], $homepage['options']);
        self::assertNotContains('CMS.homepagePageId', $keys);

        $email = $adext->get('admin.settings', 'email');
        $emailKeys = array_column($email['pubvana.cms.mail']['fields'], 'key');
        foreach (['Mail.fromEmail', 'Mail.host', 'Mail.port', 'Mail.encryption', 'Mail.username', 'Mail.password'] as $key) {
            self::assertContains($key, $emailKeys, $key);
        }

        $login = $adext->get('admin.settings', 'login_sec');
        $loginKeys = array_column($login['pubvana.cms.login_sec']['fields'], 'key');
        foreach (['Shield.allow_registration', 'Shield.magic_link', 'Shield.remember_me', 'Shield.email_2fa', 'Shield.email_activation'] as $key) {
            self::assertContains($key, $loginKeys, $key);
        }

        $captcha = $adext->get('admin.settings', 'captcha');
        $captchaKeys = array_column($captcha['pubvana.cms.captcha']['fields'], 'key');
        self::assertSame(['Captcha.provider', 'Captcha.site_key', 'Captcha.secret_key'], $captchaKeys);
    }

    public function testCaptchaAreaAndCron(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();

        $areas = $adext->get('captcha.area', 'default');
        self::assertArrayHasKey('login', $areas);
        self::assertSame('Sign-in form', $areas['login']['label']);

        $cron = $adext->get('cron', '4h');
        self::assertArrayHasKey('pubvana.core', $cron);
        self::assertIsCallable($cron['pubvana.core']['callable']);
    }

    public function testDashboardContributions(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();

        $cards = $adext->get('admin.dashboard', 'cards');
        self::assertArrayHasKey('pubvana.users', $cards);

        $sections = $adext->get('admin.dashboard', 'sections');
        self::assertArrayHasKey('pubvana.logins', $sections);
        self::assertArrayHasKey('pubvana.admin', $sections);
    }

    public function testUsersCardsCallableHappyPath(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $cards = $adext->get('admin.dashboard', 'cards');

        /** @var callable $callable */
        $callable = $cards['pubvana.users']['callable'];
        $result = $callable(['user' => null, 'site_name' => 'Test']);

        self::assertCount(5, $result);
        self::assertSame('total-users', $result[0]['id']);
        self::assertSame(42, $result[0]['value']);
        self::assertSame('people', $result[0]['group']);
        self::assertSame('/users', $result[0]['href']);
        self::assertSame('+5%', $result[4]['trend']['value']);
        self::assertSame('up', $result[4]['trend']['direction']);
    }

    public function testUsersCardsCallableFallsBackWhenStatsThrow(): void
    {
        $app = $this->boot(statsThrow: true);
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $cards = $adext->get('admin.dashboard', 'cards');

        /** @var callable $callable */
        $callable = $cards['pubvana.users']['callable'];
        $result = $callable([]);

        self::assertSame(7, $result[0]['value']);
        self::assertSame(7, $result[1]['value']);
    }

    public function testLoginActivityCallable(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $sections = $adext->get('admin.dashboard', 'sections');

        /** @var callable $callable */
        $callable = $sections['pubvana.logins']['callable'];
        $result = $callable([]);

        self::assertSame('login-activity', $result[0]['id']);
        self::assertSame('10', $result[0]['items'][0]['meta']);
        self::assertSame('2', $result[0]['items'][1]['meta']);
        self::assertSame('12', $result[0]['items'][2]['meta']);
    }

    public function testLoginActivityCallableDegrades(): void
    {
        $app = $this->boot(statsThrow: true);
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $sections = $adext->get('admin.dashboard', 'sections');

        /** @var callable $callable */
        $callable = $sections['pubvana.logins']['callable'];
        $result = $callable([]);

        self::assertSame('0', $result[0]['items'][0]['meta']);
    }

    public function testQuickActionsCallable(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $sections = $adext->get('admin.dashboard', 'sections');

        /** @var callable $callable */
        $callable = $sections['pubvana.admin']['callable'];
        $result = $callable([]);

        self::assertSame('quick-actions', $result[0]['id']);
        self::assertCount(4, $result[0]['items']);
        self::assertSame('New Page', $result[0]['items'][0]['label']);
    }

    public function testCronCallableRunsTrustCheck(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $cron = $adext->get('cron', '4h');

        /** @var callable $callable */
        $callable = $cron['pubvana.core']['callable'];
        $callable();

        self::assertTrue($this->trustChecked);
    }

    public function testStoredCoreRoutes(): void
    {
        $app = $this->boot();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();

        // 11 users + 6 groups + 4 permissions + 12 themes + 2 settings
        // + 2 login + 2 captcha + 3 email + 3 plugins + 4 navigation = 49.
        self::assertCount(49, $routes);
        foreach ($routes as $route) {
            self::assertSame('admin', $route['scope']);
            self::assertTrue($route['isCore']);
            self::assertSame('pubvana.core', $route['source']);
        }

        $handlers = array_map(static fn($r): string => $r['method'] . ' ' . $r['path'] . ' ' . $r['handler'][0] . '::' . $r['handler'][1], $routes);
        self::assertContains('GET /users ' . UsersController::class . '::index', $handlers);
        self::assertContains('POST /users/@id/ban ' . UsersController::class . '::ban', $handlers);
        self::assertContains('GET /groups ' . GroupsController::class . '::index', $handlers);
        self::assertContains('POST /permissions/@id/delete ' . PermissionsController::class . '::delete', $handlers);
        self::assertContains('GET /themes/regions ' . ThemesController::class . '::regions', $handlers);
        self::assertContains('POST /settings/save ' . SettingsController::class . '::save', $handlers);
        self::assertContains('GET /plugins ' . PluginsController::class . '::index', $handlers);
        self::assertContains('POST /navigation/reorder ' . NavigationController::class . '::reorder', $handlers);
    }

    private function boot(bool $statsThrow = false): Engine
    {
        $test = $this;
        $app = new Engine();
        $app->init();
        $app->map('adext', static function () use ($app): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $app->map('auth', static fn(): object => new class($statsThrow) {
            public function __construct(private bool $throw)
            {
            }

            public function stats(): object
            {
                if ($this->throw) {
                    throw new class extends \RuntimeException {
                    };
                }

                return new class {
                    public function totalUsers(): int
                    {
                        return 42;
                    }

                    public function activeUsers(): int
                    {
                        return 40;
                    }

                    public function inactiveUsers(): int
                    {
                        return 1;
                    }

                    public function bannedUsers(): int
                    {
                        return 1;
                    }

                    public function newUsersThisMonth(): int
                    {
                        return 3;
                    }

                    public function newUsersPercentChange(): float
                    {
                        return 5.0;
                    }

                    /** @return array{total: int, success: int, failed: int} */
                    public function loginAttempts(int $days): array
                    {
                        return ['total' => 12, 'success' => 10, 'failed' => 2];
                    }
                };
            }

            public function users(): object
            {
                return new class {
                    public function count(): int
                    {
                        return 7;
                    }
                };
            }
        });
        $app->map('trustClient', static fn(): object => new class($test) {
            public function __construct(private CoreAdminConfigTest $t)
            {
            }

            public function checkIfDue(): void
            {
                $this->t->trustChecked = true;
            }
        });
        \Flight::setEngine($app);

        require PROJECT_ROOT . '/app/config/core-admin.php';

        return $app;
    }
}
