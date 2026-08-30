<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class EnvironmentFilePermissionsCheck implements CheckInterface
{
    public function __construct(private string $envPath) {}

    public function run(): CheckResult
    {
        $displayPath = realpath($this->envPath) ?: $this->envPath;

        if (!file_exists($this->envPath)) {
            return new CheckResult(
                id: 'env-file-permissions',
                name: 'Environment File Permissions',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::CRITICAL,
                message: 'Environment file (.env) not found at expected path.',
                remediation: "Create .env with your credentials at: {$displayPath}",
            );
        }

        $perms = fileperms($this->envPath) & 0777;
        $worldReadable = ($perms & 0004) !== 0;
        $worldWritable = ($perms & 0002) !== 0;

        if ($worldWritable) {
            return new CheckResult(
                id: 'env-file-permissions',
                name: 'Environment File Permissions',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::CRITICAL,
                message: sprintf('Environment file (.env) is world-writable (permissions: %o). Anyone on the server can modify or read it.', $perms),
                remediation: 'The site only needs the web server to read .env, never write it. Set permissions to 600 (owner read/write) or 640 (owner read/write, group read). Via command line: chmod 640 ' . $displayPath . ', or adjust via your hosting panel/file manager.',
            );
        }

        if ($worldReadable) {
            return new CheckResult(
                id: 'env-file-permissions',
                name: 'Environment File Permissions',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::WARNING,
                message: sprintf('Environment file (.env) is world-readable (permissions: %o). Other users on the server can read credentials.', $perms),
                remediation: 'Restrict to the minimum needed for the site to function: 600 (owner read/write) or 640 (owner read/write, group read). Via command line: chmod 640 ' . $displayPath . ', or adjust via your hosting panel/file manager.',
            );
        }

        return new CheckResult(
            id: 'env-file-permissions',
            name: 'Environment File Permissions',
            category: CheckResult::CAT_SECURITY,
            status: CheckResult::PASS,
            message: sprintf('Environment file (.env) permissions are minimal (%o).', $perms),
        );
    }
}
