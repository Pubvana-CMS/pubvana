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
use Flight;

/**
 * CLI command to create a full-site backup.
 *
 * Usage:
 *   php runway backups:create
 *   php runway backups:create --trigger pre-update --user admin
 */
class BackupsCreateCommand extends AbstractBaseCommand
{
    public function __construct(array $config)
    {
        parent::__construct('backups:create', 'Create a full Pubvana backup (files + database)', $config);

        $this
            ->option('--trigger', 'Trigger type: manual, pre-update, pre-rollback, post-rollback', null, 'manual')
            ->option('--user', 'Username of the admin who initiated the backup', null, 'cli')
            ->usage(
                '<bold>  runway backups:create</end><eol/>' .
                '<bold>  runway backups:create --trigger manual --user admin</end><eol/>'
            );
    }

    public function execute(): int
    {
        $io = $this->app()->io();

        $trigger     = $this->trigger ?? 'manual';
        $triggeredBy = $this->user ?? 'cli';

        $pluginConfig = require $this->projectRoot . '/plugins/Backups/Config/Config.php';
        $dbConfig     = Flight::get('database') ?? [];

        $backupDir = $pluginConfig['backup_path'] ?? (($this->projectRoot) . '/writable/backups');
        $pluginConfig['backup_path'] = $backupDir;

        $service  = new BackupService(Flight::db(), $pluginConfig, $dbConfig);
        $reporter = new ProgressReporter('backup', $service->getBackupDir());

        if (!$reporter->acquireLock()) {
            $io->error('Another operation is already in progress.', true);
            return 1;
        }

        try {
            $io->info('Creating backup...', true);

            $zipPath = $service->createBackup($trigger, $triggeredBy, function (int $step, int $total, string $label) use ($reporter, $io) {
                $reporter->update($step, $total, $label);
                $io->write("  [{$step}/{$total}] {$label}" . PHP_EOL);
            });

            $reporter->complete(['detail' => basename($zipPath)]);
            $io->ok('Backup created: ' . basename($zipPath), true);
            return 0;
        } catch (\Throwable $e) {
            $reporter->error('backup', $e->getMessage());
            $io->error('Backup failed: ' . $e->getMessage(), true);
            return 1;
        } finally {
            $reporter->releaseLock();
        }
    }
}