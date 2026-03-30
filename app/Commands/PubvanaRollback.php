<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\RollbackService;
use App\Services\ProgressReporter;

class PubvanaRollback extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'pubvana:rollback';
    protected $description = 'Restore from a backup (backup → restore → backup).';
    protected $usage       = 'pubvana:rollback <filename> [--email=admin@example.com]';
    protected $arguments   = [
        'filename' => 'Backup zip filename (e.g., 2026-03-29_143022-full.zip)',
    ];
    protected $options     = [
        '--email' => 'Email of the admin who initiated the rollback (default: cli)',
    ];

    public function run(array $params): void
    {
        $filename = $params[0] ?? null;
        $email    = $params['email'] ?? 'cli';

        if (empty($filename)) {
            CLI::error('Please provide a backup filename.');
            return;
        }

        $reporter = new ProgressReporter('rollback');

        if (! $reporter->acquireLock()) {
            CLI::error('Another operation is already in progress.');
            return;
        }

        try {
            CLI::write('Starting rollback from: ' . $filename, 'cyan');

            $service = new RollbackService();
            $service->rollback($filename, $email, function (int $step, int $total, string $label, string $detail) use ($reporter) {
                $reporter->update($step, $total, $label, $detail);
                CLI::write("  [{$step}/{$total}] {$label}", 'white');
            });

            $reporter->complete();
            CLI::write('Rollback complete!', 'green');
        } catch (\Throwable $e) {
            $reporter->error('rollback', $e->getMessage());
            CLI::error('Rollback failed: ' . $e->getMessage());
        } finally {
            $reporter->releaseLock();
        }
    }
}
