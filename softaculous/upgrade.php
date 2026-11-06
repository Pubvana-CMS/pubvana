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

if (!function_exists('__upgrade')) {
    function __upgrade()
    {
        $s = isset($GLOBALS['__settings']) && is_array($GLOBALS['__settings'])
            ? $GLOBALS['__settings']
            : [];

        $path = rtrim((string) ($s['softpath'] ?? ''), '/');

        if ($path === '' || !is_file($path . '/.env')) {
            return __fail('The existing installation could not be found.');
        }

        @chmod($path . '/writable', 0777);
        @chmod($path . '/.env', 0644);

        $php = '/usr/local/bin/php';

        __runCmd($php . ' ' . escapeshellarg($path . '/pubvana') . ' migrate:all', $path);

        return true;
    }
}
