<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\BackupService;
use App\Services\ProgressReporter;

class PubvanaBackup extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'pubvana:backup';
    protected $description = 'Create a full Pubvana backup (files + database).';
    protected $usage       = 'pubvana:backup [--trigger=manual] [--email=admin@example.com]';
    protected $options     = [
        '--trigger' => 'Trigger type: manual, pre-update, pre-rollback, post-rollback (default: manual)',
        '--email'   => 'Email of the admin who initiated the backup (default: cli)',
    ];

    public function run(array $params): void
    {
        $trigger = $params['trigger'] ?? 'manual';
        $email   = $params['email']   ?? 'cli';

        $reporter = new ProgressReporter('backup');

        if (! $reporter->acquireLock()) {
            CLI::error('Another operation is already in progress.');
            return;
        }

        try {
            CLI::write('Creating backup...', 'cyan');

            $service = new BackupService();
            $zipPath = $service->createBackup($trigger, $email, function (int $step, int $total, string $label, string $detail) use ($reporter) {
                $reporter->update($step, $total, $label, $detail);
                CLI::write("  [{$step}/{$total}] {$label}", 'white');
            });

            $reporter->complete(['detail' => basename($zipPath)]);
            CLI::write('Backup created: ' . basename($zipPath), 'green');
        } catch (\Throwable $e) {
            $reporter->error('backup', $e->getMessage());
            CLI::error('Backup failed: ' . $e->getMessage());
        } finally {
            $reporter->releaseLock();
        }
    }
}
