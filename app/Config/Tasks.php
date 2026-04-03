<?php

namespace Config;

use CodeIgniter\Tasks\Config\Tasks as BaseTasks;
use CodeIgniter\Tasks\Scheduler;

class Tasks extends BaseTasks
{
    public function init(Scheduler $schedule)
    {
        // Publish scheduled posts every minute
        $schedule->command('posts:publish')->everyMinute()->named('posts-publish');

        // Daily update chain: CMS auto-update check + addon updates.
        // Cache-gated internally so running more frequently is harmless.
        $schedule->call(static function () {
            (new \App\Services\UpdateService())->checkAndAutoUpdateIfDue();
        })->daily()->named('pubvana-update-chain');
    }
}
