<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class DiskSpaceCheck implements CheckInterface
{
    private int $criticalMb = 100;
    private int $warningMb = 500;

    public function __construct(private string $uploadsPath) {}

    public function run(): CheckResult
    {
        if (!is_dir($this->uploadsPath)) {
            return new CheckResult(
                id: 'disk-space',
                name: 'Disk Space',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: "Uploads directory does not exist: {$this->uploadsPath}",
                remediation: 'Create the uploads directory and ensure it is writable by the web server.',
            );
        }

        $freeBytes = @disk_free_space($this->uploadsPath);
        if ($freeBytes === false) {
            return new CheckResult(
                id: 'disk-space',
                name: 'Disk Space',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: 'Unable to determine free disk space.',
                remediation: 'Check filesystem permissions or mount status.',
            );
        }

        $freeMb = (int) ($freeBytes / 1024 / 1024);

        if ($freeMb < $this->criticalMb) {
            return new CheckResult(
                id: 'disk-space',
                name: 'Disk Space',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::CRITICAL,
                message: "Only {$freeMb} MB free on uploads partition. Uploads and backups will fail.",
                remediation: 'Free up disk space immediately or expand the partition.',
            );
        }

        if ($freeMb < $this->warningMb) {
            return new CheckResult(
                id: 'disk-space',
                name: 'Disk Space',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: "{$freeMb} MB free on uploads partition. Consider freeing space soon.",
                remediation: 'Remove old files or expand storage before running low.',
            );
        }

        $freeGb = round($freeMb / 1024, 1);
        return new CheckResult(
            id: 'disk-space',
            name: 'Disk Space',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: "{$freeGb} GB free on uploads partition.",
        );
    }
}
