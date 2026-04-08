<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Cron extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'cron';
    protected $description = 'Run scheduled cron tasks by frequency.';
    protected $usage       = 'cron <minute|daily>';
    protected $arguments   = [
        'frequency' => 'The task group to run: minute or daily',
    ];

    public function run(array $params): void
    {
        $frequency = $params[0] ?? null;

        switch ($frequency) {
            case 'minute':
                $this->minute();
                break;
            case 'daily':
                $this->daily();
                break;
            default:
                CLI::error('Usage: php spark cron <minute|daily>');
                break;
        }
    }

    protected function minute(): void
    {
        $this->call('posts:publish');
    }

    protected function daily(): void
    {
        $this->call('pubvana:auto-update');
        $this->call('links:check');
        $this->call('marketplace:revalidate');
    }
}
