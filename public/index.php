<?php

/**
 * Pubvana CMS - Entry Point
 *
 * This is the front controller. All requests hit this file via Apache/Nginx
 * rewrite rules. It loads the bootstrap which handles autoloading, config,
 * services, routes, and starts FlightPHP.
 *
 * @package Pubvana
 */

$ds = DIRECTORY_SEPARATOR;
require(__DIR__ . $ds . '..' . $ds . 'app' . $ds . 'config' . $ds . 'bootstrap.php');
