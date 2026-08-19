<?php

namespace Config;

use App\Services\PluginManager;
use App\Services\ThemeService;
use CodeIgniter\CLI\Commands;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function commands(bool $getShared = true): Commands
    {
        if ($getShared) {
            return static::getSharedInstance('commands');
        }

        if (is_cli()) {
            PluginManager::instance()->cliBoot();
        }

        return new Commands();
    }

    public static function theme(bool $getShared = true): ThemeService
    {
        if ($getShared) {
            return static::getSharedInstance('theme');
        }

        return new ThemeService();
    }
}
