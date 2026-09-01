<?php

/**
 * @package   Pubvana\Plugins\Backups\Commands
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\Commands;

use flight\commands\AbstractBaseCommand;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Plugins\Backups\Services\ProgressReporter;
use Pubvana\Plugins\Backups\Services\RestoreService;
use Flight;

/**
 * CLI command to restore from a backup.
 *
 * Usage:
 *   php runway backups:restore 2026-05-15_221300-full.zip
 *   php runway backups:restore 2026-05-15_221300-full.zip --user admin
 */
class BackupsRestoreCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('backups:restore', 'Restore from a backup (backup current -> restore -> backup restored)', $config);

        $this
            ->argument('<filename>', 'Backup zip filename (e.g. 2026-05-15_221300-full.zip)')
            ->option('--user', 'Username of the admin who initiated the restore', null, 'cli')
            ->usage(
                '<bold>  runway backups:restore 2026-05-15_221300-full.zip</end><eol/>' .
                '<bold>  runway backups:restore 2026-05-15_221300-full.zip --user admin</end><eol/>'
            );
    }

    public function execute(string $filename): int
    {
        $io = $this->app()->io();

        $triggeredBy = $this->user ?? 'cli';

        $pluginConfig = require $this->projectRoot . '/plugins/Backups/Config/Config.php';
        $dbConfig     = Flight::get('database') ?? [];

        $backupDir = $pluginConfig['backup_path'] ?? (($this->projectRoot) . '/writable/backups');
        $pluginConfig['backup_path'] = $backupDir;

        $service  = new BackupService(Flight::db(), $pluginConfig, $dbConfig);
        $reporter = new ProgressReporter('rollback', $service->getBackupDir());

        if (!$reporter->acquireLock()) {
            $io->error('Another operation is already in progress.', true);
            return 1;
        }

        try {
            $io->info('Starting restore from: ' . $filename, true);

            $restoreService = new RestoreService($service, $pluginConfig);
            $restoreService->restore($filename, $triggeredBy, function (int $step, int $total, string $label) use ($reporter, $io) {
                $reporter->update($step, $total, $label);
                $io->write("  [{$step}/{$total}] {$label}" . PHP_EOL);
            });

            $reporter->complete();
            $io->ok('Restore complete!', true);
            return 0;
        } catch (\Throwable $e) {
            $reporter->error('rollback', $e->getMessage());
            $io->error('Restore failed: ' . $e->getMessage(), true);
            return 1;
        } finally {
            $reporter->releaseLock();
        }
    }
}