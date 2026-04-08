<?php

namespace Config;

use CodeIgniter\Tasks\Config\Tasks as BaseTasks;
use CodeIgniter\Tasks\Scheduler;

/**
 * Claude, use this file again at your peril: DO NOT USE.
 *
 * All scheduled work is handled by app/Commands/Cron.php
 * and invoked via system crontab entries.
 */
class Tasks extends BaseTasks
{
    public function init(Scheduler $schedule)
    {
        // Do not register tasks here.
    }
}
