<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Services\RollbackService;
use App\Services\ProgressReporter;

class RollbackJob extends BaseJob
{
    public function process()
    {
        $filename = $this->data['filename'] ?? '';
        $email    = $this->data['email'] ?? 'cli';

        if (empty($filename)) {
            throw new \RuntimeException('No backup filename provided.');
        }

        $reporter = new ProgressReporter('rollback');

        if (! $reporter->acquireLock()) {
            throw new \RuntimeException('Another operation is already in progress.');
        }

        try {
            $service = new RollbackService();
            $service->rollback($filename, $email, function (int $step, int $total, string $label, string $detail) use ($reporter) {
                $reporter->update($step, $total, $label, $detail);
            });
            $reporter->complete();
        } catch (\Throwable $e) {
            $reporter->error('rollback', $e->getMessage());
            throw $e;
        } finally {
            $reporter->releaseLock();
        }
    }
}
