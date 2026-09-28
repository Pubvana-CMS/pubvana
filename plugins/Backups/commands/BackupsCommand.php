<?php

/**
 * @package   Pubvana\Plugins\Backups\commands
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\commands;

use flight\commands\AbstractBaseCommand;

/**
 * Index command for the backups CLI group.
 *
 * Running `php runway backups` lists the available backup sub-commands with a
 * short description of each. The group name on its own is not a backup action,
 * so this only prints help.
 *
 * Usage:
 *   php runway backups
 */
class BackupsCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('backups', 'Pubvana Backups - create and restore site backups', $config);

        $this->usage(
            '<bold>Available commands:</end><eol/>' .
            '<eol/>' .
            '<bold>  backups:create</end>   <comment>Create a full Pubvana backup (files + database)</end><eol/>' .
            '<bold>  backups:restore</end>  <comment>Restore from a backup (backup current -> restore -> backup restored)</end><eol/>' .
            '<eol/>' .
            '<comment>Run any command without arguments to see its usage.</end>'
        );
    }

    public function execute(): void
    {
        $this->showHelp();
    }
}
