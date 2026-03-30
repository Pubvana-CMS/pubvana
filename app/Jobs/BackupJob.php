<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Services\BackupService;
use App\Services\ProgressReporter;

class BackupJob extends BaseJob
{
    public function process()
    {
        $trigger = $this->data['trigger'] ?? 'manual';
        $email   = $this->data['email'] ?? 'cli';

        $reporter = new ProgressReporter('backup');

        if (! $reporter->acquireLock()) {
            throw new \RuntimeException('Another operation is already in progress.');
        }

        try {
            $service = new BackupService();
            $service->createBackup($trigger, $email, function (int $step, int $total, string $label, string $detail) use ($reporter) {
                $reporter->update($step, $total, $label, $detail);
            });
            $reporter->complete();
        } catch (\Throwable $e) {
            $reporter->error('backup', $e->getMessage());
            throw $e;
        } finally {
            $reporter->releaseLock();
        }
    }
}
