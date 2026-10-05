<?php

/**
 * PHPStan bootstrap: constants that exist only at runtime.
 *
 * Loaded by phpstan.neon via bootstrapFiles. Keep in sync with the
 * runtime definition in the boot sequence.
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', '/var/www/html');
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', '/var/www/html/public');
}
