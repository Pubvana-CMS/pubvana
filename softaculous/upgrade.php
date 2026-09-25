<?php

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

        $php = '/usr/local/bin/php';

        __runCmd($php . ' ' . escapeshellarg($path . '/runway') . ' migrate:all', $path);
    }
}
