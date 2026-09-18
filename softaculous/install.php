<?php

function __findPhp(): string
{
    $bins = [
        '/usr/local/bin/php',
        '/usr/bin/php',
        '/opt/cpanel/ea-php84/root/usr/bin/php',
        '/opt/cpanel/ea-php83/root/usr/bin/php',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/usr/bin/php8.4',
        '/usr/bin/php8.3',
        '/usr/bin/php8.2',
    ];

    if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '' && is_executable(PHP_BINARY)) {
        $bins[] = PHP_BINARY;
    }

    foreach ($bins as $bin) {
        if (is_executable($bin) && __isModernPhp($bin)) {
            return $bin;
        }
    }

    return 'php';
}

function __isModernPhp(string $bin): bool
{
    $out = @shell_exec($bin . ' -r "echo PHP_VERSION;" 2>/dev/null');

    return is_string($out) && version_compare(trim($out), '8.2.0', '>=');
}

function __runCmd(string $cmd, string $cwd): string
{
    $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';

    if (function_exists('sshell_exec')) {
        return (string) @sshell_exec($full);
    }

    $out = @shell_exec($full);

    return is_string($out) ? $out : '';
}

if (!function_exists('__install')) {
    function __install()
    {
        $s = isset($GLOBALS['__settings']) && is_array($GLOBALS['__settings'])
            ? $GLOBALS['__settings']
            : [];

        $path = rtrim((string) ($s['softpath'] ?? ''), '/');
        $dbHost = (string) ($s['dbhost'] ?? 'localhost');
        $dbName = (string) ($s['softdb'] ?? '');
        $dbUser = (string) ($s['softusername'] ?? '');
        $dbPass = (string) ($s['softpassword'] ?? '');
        $siteUrl = rtrim((string) ($s['softurl'] ?? ''), '/');
        $siteName = trim((string) ($s['site_name'] ?? ''));
        $adminUser = trim((string) ($s['admin_username'] ?? ''));
        $adminPass = (string) ($s['admin_pass'] ?? '');
        $adminEmail = trim((string) ($s['admin_email'] ?? ''));

        if ($path === '' || $dbName === '' || $dbUser === '' || $adminUser === '' || $adminPass === '' || $adminEmail === '') {
            exit(1);
        }

        if (!is_dir($path . '/writable')) {
            @mkdir($path . '/writable', 0777, true);
        }
        @chmod($path . '/writable', 0777);

        $env = "APP_ENV=production\n"
            . "APP_DEBUG=false\n"
            . "\n"
            . "DB_HOST=" . $dbHost . "\n"
            . "DB_PORT=3306\n"
            . "DB_NAME=" . $dbName . "\n"
            . "DB_USER=" . $dbUser . "\n"
            . "DB_PASS=" . $dbPass . "\n"
            . "\n"
            . "SITE_NAME=" . $siteName . "\n"
            . "SITE_URL=" . $siteUrl . "\n"
            . "ADMIN_EMAIL=" . $adminEmail . "\n"
            . "\n"
            . "SESSION_ENCRYPTION_KEY=" . bin2hex(random_bytes(32)) . "\n";

        if (@file_put_contents($path . '/.env', $env) === false) {
            exit(1);
        }
        @chmod($path . '/.env', 0644);

        $php = __findPhp();

        if (!__runCmd($migrateCmd, $path)) {
            exit(1);
        }

        $passInput = escapeshellarg($adminPass . "\n" . $adminPass);
        $userCmd = 'printf ' . $passInput . ' | '
            . $php . ' ' . escapeshellarg($path . '/runway') . ' shield:user create'
            . ' -n ' . escapeshellarg($adminUser)
            . ' -e ' . escapeshellarg($adminEmail)
            . ' -g superadmin';

        __runCmd($userCmd, $path);

        $checkCmd = $php . ' ' . escapeshellarg($path . '/runway') . ' shield:user show -n ' . escapeshellarg($adminUser);
        $checkOut = __runCmd($checkCmd, $path);

        if (strpos($checkOut, $adminUser) === false) {
            exit(1);
        }

        return;
    }
}