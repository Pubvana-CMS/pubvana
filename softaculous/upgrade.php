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

function __runCmd(string $cmd, string $cwd)
{
    $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';

    if (function_exists('sshell_exec')) {
        @sshell_exec($full);
    } else {
        @shell_exec($full);
    }
}

if (!function_exists('__upgrade')) {
    function __upgrade()
    {
        $s = isset($GLOBALS['__settings']) && is_array($GLOBALS['__settings'])
            ? $GLOBALS['__settings']
            : [];

        $path = rtrim((string) ($s['softpath'] ?? ''), '/');

        if ($path === '' || !is_file($path . '/.env')) {
            exit(1);
        }

        @chmod($path . '/writable', 0777);
        @chmod($path . '/.env', 0644);

        $php = __findPhp();

        __runCmd($php . ' ' . escapeshellarg($path . '/runway') . ' migrate:all', $path);
    }
}