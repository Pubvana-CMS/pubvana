<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * H4: Backups DB password on the command line.
 *
 * The mysqldump/mysql clients must never receive the password through argv
 * (visible in ps). It travels only in the MYSQL_PWD child environment, and
 * is unset when no password is configured. Binary dispatch happens via
 * proc_open so the whole child environment is under our control.
 */
#[CoversClass(BackupService::class)]
final class BackupCredentialsEnvTest extends TestCase
{
    /**
     * @param array{host: string, port: int, dbname: string, user: string, password: string} $credentials
     */
    private function service(array $credentials = []): BackupService
    {
        return new BackupService(
            Sqlite::recreate(),
            [
                'backup_path'        => sys_get_temp_dir() . '/backups_credentials_env_test',
                'max_backups'        => 5,
                'backup_dirs'        => [],
                'protected_configs'  => [],
            ],
            $credentials === [] ? ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'test', 'user' => 'test', 'password' => 's3cr3t'] : $credentials,
        );
    }

    public function testMysqlEnvCarriesPasswordWhenSet(): void
    {
        $env = $this->invoke($this->service(), 'mysqlEnv', [[
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'dbname'   => 'test',
            'user'     => 'test',
            'password' => 's3cr3t',
        ]]);

        $this->assertSame('s3cr3t', $env['MYSQL_PWD'] ?? null);
    }

    public function testMysqlEnvUnsetsPasswordWhenEmpty(): void
    {
        $env = $this->invoke($this->service(), 'mysqlEnv', [[
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'dbname'   => 'test',
            'user'     => 'test',
            'password' => '',
        ]]);

        $this->assertArrayNotHasKey('MYSQL_PWD', $env);
    }

    public function testRunProcDeliversPasswordViaEnvironmentNotArgv(): void
    {
        $argv = [PHP_BINARY, '-r', 'echo (string) getenv("MYSQL_PWD");'];
        $env  = array_merge(getenv() ?: [], ['MYSQL_PWD' => 's3cr3t']);

        $stdout = null;
        $stderr = null;
        $code = $this->invoke($this->service(), 'runProc', [$argv, $env, &$stdout, &$stderr]);

        $this->assertSame(0, $code);
        $this->assertSame('s3cr3t', $stdout);
        $this->assertSame('', $stderr);
    }
}