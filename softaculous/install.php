<?php

if (!defined('SOFTACULOUS')) {
    die('Hacking Attempt');
}

if (!function_exists('__runCmd')) {
    function __runCmd(string $cmd, string $cwd): string
    {
        $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';

        if (function_exists('sshell_exec')) {
            return (string) @sshell_exec($full);
        }

        $out = @shell_exec($full);

        return is_string($out) ? $out : '';
    }
}

/**
 * Record a failure the way Softaculous reads it.
 *
 * Softaculous shows the messages it finds in $error and aborts the install,
 * so a bare exit() both hides the reason and skips that handling.
 */
if (!function_exists('__fail')) {
    function __fail(string $message): bool
    {
        if (!isset($GLOBALS['error']) || !is_array($GLOBALS['error'])) {
            $GLOBALS['error'] = [];
        }

        $GLOBALS['error'][] = $message;

        return false;
    }
}

/**
 * Encode a value for a .env line.
 *
 * Plain values stay bare. Anything with spaces, quotes, #, $ or backslashes
 * is double-quoted and escaped, which phpdotenv reads back as the original
 * string. Cutting this out lets a password with a space or # load wrong.
 */
function __envValue(string $value): string
{
    if ($value !== '' && preg_match('/^[A-Za-z0-9_.:\/@+-]+$/', $value) === 1) {
        return $value;
    }

    $escaped = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);

    return '"' . $escaped . '"';
}

if (!function_exists('__install')) {
    function __install()
    {
        $s = isset($GLOBALS['__settings']) && is_array($GLOBALS['__settings'])
            ? $GLOBALS['__settings']
            : [];

        $path = rtrim((string) ($s['softpath'] ?? ''), '/');
        $dbHost = (string) ($s['softdbhost'] ?? 'localhost');
        $dbName = (string) ($s['softdb'] ?? '');
        $dbUser = (string) ($s['softdbuser'] ?? '');
        $dbPass = (string) ($s['softdbpass'] ?? '');
        $siteUrl = rtrim((string) ($s['softurl'] ?? ''), '/');
        $adminUser = trim((string) ($s['admin_username'] ?? ''));
        $adminPass = (string) ($s['admin_pass'] ?? '');
        $adminEmail = trim((string) ($s['admin_email'] ?? ''));

        if ($path === '') {
            return __fail('The installation path was not passed to the installer.');
        }

        if ($dbName === '' || $dbUser === '') {
            return __fail('The database details Softaculous passed are incomplete.');
        }

        if ($adminUser === '' || $adminPass === '' || $adminEmail === '') {
            return __fail('The administrator account details are incomplete.');
        }

        if (!is_dir($path . '/writable')) {
            @mkdir($path . '/writable', 0777, true);
        }
        @chmod($path . '/writable', 0777);

        $env = "APP_ENV=production\n"
            . "APP_DEBUG=false\n"
            . "\n"
            . "DB_HOST=" . __envValue($dbHost) . "\n"
            . "DB_PORT=3306\n"
            . "DB_NAME=" . __envValue($dbName) . "\n"
            . "DB_USER=" . __envValue($dbUser) . "\n"
            . "DB_PASS=" . __envValue($dbPass) . "\n"
            . "\n"
            . "SITE_URL=" . __envValue($siteUrl) . "\n"
            . "\n"
            . "SESSION_ENCRYPTION_KEY=" . bin2hex(random_bytes(32)) . "\n";

        if (@file_put_contents($path . '/.env', $env) === false) {
            return __fail('The .env file could not be written in ' . $path . '.');
        }
        @chmod($path . '/.env', 0644);

        $php = '/usr/local/bin/php';
        $migrateCmd = $php . ' ' . escapeshellarg($path . '/pubvana') . ' migrate:all';

        if (!__runCmd($migrateCmd, $path)) {
            return __fail('The database migrations did not run.');
        }

        $passInput = escapeshellarg($adminPass . "\n" . $adminPass);
        $userCmd = 'printf ' . $passInput . ' | ' . $php . ' '
            . escapeshellarg($path . '/pubvana') . ' shield:user create'
            . ' -n ' . escapeshellarg($adminUser)
            . ' -e ' . escapeshellarg($adminEmail)
            . ' -g superadmin';

        __runCmd($userCmd, $path);

        $checkCmd = $php . ' ' . escapeshellarg($path . '/pubvana') . ' shield:user show -n ' . escapeshellarg($adminUser);
        $checkOut = __runCmd($checkCmd, $path);

        if (strpos($checkOut, $adminUser) === false) {
            return __fail('The administrator account could not be created.');
        }

        return true;
    }
}