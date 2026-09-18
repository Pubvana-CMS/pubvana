<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * shield.php + migrations.php config shape coverage.
 *
 * Both files are pure return-value configs with no Flight dependency,
 * so they load directly and assert on structure.
 */
#[CoversNothing]
final class ShieldMigrationsConfigTest extends TestCase
{
    public function testShieldConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/app/config/shield.php';

        self::assertSame('auth', $config['routePrepend']);
        self::assertSame('session', $config['default_authenticator']);
        self::assertArrayHasKey('session', $config['authenticators']);
        self::assertArrayHasKey('tokens', $config['authenticators']);
        self::assertArrayHasKey('hmac', $config['authenticators']);
        self::assertSame(['session', 'tokens', 'hmac'], $config['authentication_chain']);

        self::assertTrue($config['rate_limiting']['enabled']);
        self::assertSame(10, $config['rate_limiting']['max_attempts']);
        self::assertSame(30, $config['rate_limiting']['decay_minutes']);

        self::assertSame('all', $config['record_login_attempt']);
        self::assertTrue($config['record_active_date']);
        self::assertTrue($config['allow_registration']);
        self::assertFalse($config['allow_magic_link']);
        self::assertSame(3600, $config['magic_link_lifetime']);

        self::assertIsCallable($config['email_sender']);
        self::assertNull($config['actions']['login']);
        self::assertNull($config['actions']['register']);
        self::assertSame(['email'], $config['valid_login_fields']);
        self::assertSame([], $config['personal_fields']);
        self::assertSame('user', $config['default_group']);

        foreach (['login', 'logout', 'after_login', 'after_login_admin', 'after_register', 'after_logout', 'force_reset', 'permission_denied', 'group_denied'] as $key) {
            self::assertArrayHasKey($key, $config['redirects'], "redirect {$key} missing");
        }
        self::assertSame('/admin', $config['redirects']['after_login_admin']);
        self::assertSame('/auth/reset-password', $config['redirects']['force_reset']);

        self::assertSame(8, $config['passwords']['min_length']);
        self::assertContains(
            \Enlivenapp\FlightShield\Passwords\CompositionValidator::class,
            $config['passwords']['validators']
        );
    }

    public function testMigrationsConfigEnvPrecedence(): void
    {
        $backup = [];
        foreach (['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'] as $key) {
            $backup[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        }

        try {
            $_ENV['DB_DRIVER'] = 'sqlite';
            $_ENV['DB_HOST'] = 'envhost';
            $_ENV['DB_PORT'] = '1234';
            $_ENV['DB_NAME'] = 'envdb';
            $_ENV['DB_USER'] = 'envuser';
            $_ENV['DB_PASS'] = 'envpass';
            $_ENV['DB_CHARSET'] = 'utf8';

            /** @var array<string, mixed> $config */
            $config = require PROJECT_ROOT . '/app/config/migrations.php';

            self::assertSame('sqlite', $config['driver']);
            self::assertSame('envhost', $config['host']);
            self::assertSame('1234', $config['port']);
            self::assertSame('envdb', $config['dbname']);
            self::assertSame('envuser', $config['user']);
            self::assertSame('envpass', $config['password']);
            self::assertSame('utf8', $config['charset']);
        } finally {
            foreach ($backup as $key => [$env, $server, $get]) {
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
                if ($get === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $get);
                }
            }
        }
    }

    public function testMigrationsConfigDefaultsAndCorePaths(): void
    {
        // migrations.php loads the repo .env via dotenv (immutable: it never
        // overrides already-set vars). Empty strings survive dotenv and hit
        // the ?: fallbacks, proving the default chain.
        $backup = [];
        foreach (['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'] as $key) {
            $backup[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            $_ENV[$key] = '';
            $_SERVER[$key] = '';
            putenv($key . '=');
        }
        // DB_CHARSET uses ?? (not ?:), so an empty string would stick. Fully
        // unset it instead: the repo .env has no DB_CHARSET, so dotenv leaves
        // it absent and the 'utf8mb4' fallback answers.
        unset($_ENV['DB_CHARSET'], $_SERVER['DB_CHARSET']);
        putenv('DB_CHARSET');

        try {
            /** @var array<string, mixed> $config */
            $config = require PROJECT_ROOT . '/app/config/migrations.php';

            self::assertSame('mysql', $config['driver']);
            self::assertSame('localhost', $config['host']);
            self::assertSame(3306, $config['port']);
            self::assertSame('', $config['dbname']);
            self::assertSame('utf8mb4', $config['charset']);
            self::assertSame(['app/Database/Migrations'], $config['migrations']['paths']);
            self::assertSame([], $config['migrations']['seeds']['paths']);
            self::assertSame(['pubvana/pubvana' => '3.0.0-beta.1'], $config['migrations']['versions']);
            self::assertSame(
                ['app/Database/Migrations' => 'pubvana/pubvana'],
                $config['migrations']['module_names']
            );
        } finally {
            foreach ($backup as $key => [$env, $server, $get]) {
                if ($env !== null) {
                    $_ENV[$key] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$key] = $server;
                }
                if ($get !== false) {
                    putenv($key . '=' . $get);
                }
            }
        }
    }
}
