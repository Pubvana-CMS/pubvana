<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class DebugModeCheck implements CheckInterface
{
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $env = (string) ($this->app->get('environment') ?? 'production');

        if ($env === 'development') {
            return new CheckResult(
                id: 'debug-mode',
                name: 'Debug Mode',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::WARNING,
                message: 'Environment is set to "development". Debug information is exposed.',
                remediation: "Set the environment to 'production' before deploying. This disables error display and debug toolbars.",
            );
        }

        $displayErrors = (bool) ini_get('display_errors');
        if ($displayErrors) {
            return new CheckResult(
                id: 'debug-mode',
                name: 'Debug Mode',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::CRITICAL,
                message: 'display_errors is enabled in production. Stack traces and paths are visible to visitors.',
                remediation: 'Set display_errors = Off in php.ini or your config for production.',
            );
        }

        return new CheckResult(
            id: 'debug-mode',
            name: 'Debug Mode',
            category: CheckResult::CAT_SECURITY,
            status: CheckResult::PASS,
            message: 'Production mode with debug output disabled.',
        );
    }
}
