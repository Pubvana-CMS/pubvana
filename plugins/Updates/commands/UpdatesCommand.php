<?php

/**
 * @package   Pubvana\Plugins\Updates\commands
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\commands;

use flight\commands\AbstractBaseCommand;

/**
 * Index command for the updates CLI group.
 *
 * Running `php pubvana updates` lists the available update sub-commands with a
 * short description of each. The group name on its own is not an update action,
 * so this only prints help.
 *
 * Usage:
 *   php pubvana updates
 */
class UpdatesCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('updates', 'Pubvana Updates - check for and apply updates', $config);

        $this->usage(
            '<bold>Available commands:</end><eol/>' .
            '<eol/>' .
            '<bold>  updates:check</end>        <comment>Check the Pubvana release feed for updates</end><eol/>' .
            '<bold>  updates:apply</end>        <comment>Apply the pending Pubvana update (backup, download, copy, migrate)</end><eol/>' .
            '<bold>  updates:auto-update</end>  <comment>Run the automatic update chain (check, then apply when allowed)</end><eol/>' .
            '<eol/>' .
            '<comment>Run any command without arguments to see its usage.</end>'
        );
    }

    public function execute(): void
    {
        $this->showHelp();
    }
}
