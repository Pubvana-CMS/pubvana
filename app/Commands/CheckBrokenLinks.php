<?php

namespace App\Commands;

use App\Services\BrokenLinkService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckBrokenLinks extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'links:check';
    protected $description = 'Scan all published posts and pages for broken external links.';
    protected $usage       = 'links:check';

    public function run(array $params): void
    {
        CLI::write('Pubvana Broken Link Checker', 'cyan');
        CLI::write(str_repeat('─', 60), 'dark_gray');

        $service = new BrokenLinkService();
        $result  = $service->scan();

        CLI::write(str_repeat('─', 60), 'dark_gray');
        CLI::write(sprintf(
            'Checked %d link%s across %d source%s. %d broken.',
            $result['total'],   $result['total']   !== 1 ? 's' : '',
            $result['sources'], $result['sources'] !== 1 ? 's' : '',
            $result['broken']
        ), $result['broken'] > 0 ? 'yellow' : 'green');
    }
}
