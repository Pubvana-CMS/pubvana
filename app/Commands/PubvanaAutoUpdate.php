<?php

namespace App\Commands;

use App\Services\UpdateService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PubvanaAutoUpdate extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'pubvana:auto-update';
    protected $description = 'Run the daily auto-update chain (addon updates + CMS update check).';
    protected $usage       = 'pubvana:auto-update';

    public function run(array $params): void
    {
        CLI::write('Running daily auto-update chain...', 'cyan');

        (new UpdateService())->checkAndAutoUpdateIfDue();

        CLI::write('Done.', 'green');
    }
}
