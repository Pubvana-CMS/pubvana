<?php

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

        $php = '/usr/local/bin/php';
        $migrateCmd = $php . ' ' . escapeshellarg($path . '/runway') . ' migrate:all';

        if (!__runCmd($migrateCmd, $path)) {
            exit(1);
        }

        $passInput = escapeshellarg($adminPass . "\n" . $adminPass);
        $userCmd = 'printf ' . $passInput . ' | ' . $php . ' '
            . escapeshellarg($path . '/runway') . ' shield:user create'
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