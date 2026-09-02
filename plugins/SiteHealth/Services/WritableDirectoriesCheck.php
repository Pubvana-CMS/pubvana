<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class WritableDirectoriesCheck implements CheckInterface
{
    /** @var array<string, string> */
    private array $directories;

    public function __construct(string $projectRoot)
    {
        $this->directories = [
            'uploads' => $projectRoot . '/public/uploads',
            'cache'   => $projectRoot . '/writable/cache',
            'logs'    => $projectRoot . '/writable/logs',
        ];
    }

    public function run(): CheckResult
    {
        $notWritable = [];
        $notExist = [];

        foreach ($this->directories as $label => $path) {
            if (!is_dir($path)) {
                $notExist[] = "{$label} ({$path})";
            } elseif (!is_writable($path)) {
                $notWritable[] = "{$label} ({$path})";
            }
        }

        if (!empty($notWritable)) {
            $commands = [];
            foreach ($this->directories as $path) {
                if (is_dir($path) && !is_writable($path)) {
                    $commands[] = "chown www-data:www-data {$path} && chmod 775 {$path}";
                }
            }

            return new CheckResult(
                id: 'writable-directories',
                name: 'Writable Directories',
                category: CheckResult::CAT_CONFIGURATION,
                status: CheckResult::CRITICAL,
                message: 'Directories exist but are not writable: ' . implode(', ', $notWritable),
                remediation: 'The web server needs write access to these directories. Via command line: ' . implode(' && ', $commands) . ', or adjust via your hosting panel/file manager.',
            );
        }

        if (!empty($notExist)) {
            return new CheckResult(
                id: 'writable-directories',
                name: 'Writable Directories',
                category: CheckResult::CAT_CONFIGURATION,
                status: CheckResult::WARNING,
                message: 'Directories do not exist: ' . implode(', ', $notExist),
                remediation: 'Create the missing directories and ensure the web server can write to them.',
            );
        }

        return new CheckResult(
            id: 'writable-directories',
            name: 'Writable Directories',
            category: CheckResult::CAT_CONFIGURATION,
            status: CheckResult::PASS,
            message: 'All required directories exist and are writable.',
        );
    }
}