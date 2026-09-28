<?php

/**
 * @package   Pubvana\Plugins\BrokenLinks\commands
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\commands;

use flight\commands\AbstractBaseCommand;

/**
 * Index command for the broken-links CLI group.
 *
 * Running `php runway broken-links` lists the available scan sub-commands with
 * a short description of each. The group name on its own is not a scan, so this
 * only prints help.
 *
 * Usage:
 *   php runway broken-links
 */
class BrokenLinksCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('broken-links', 'Pubvana Broken Link Checker - scan content for broken links', $config);

        $this->usage(
            '<bold>Available commands:</end><eol/>' .
            '<eol/>' .
            '<bold>  broken-links:check</end>  <comment>Scan all published content for broken outbound links.</end><eol/>' .
            '<bold>  broken-links:cron</end>   <comment>Scan for broken links.</end><eol/>' .
            '<eol/>' .
            '<comment>Run any command without arguments to see its usage.</end>'
        );
    }

    public function execute(): void
    {
        $this->showHelp();
    }
}
