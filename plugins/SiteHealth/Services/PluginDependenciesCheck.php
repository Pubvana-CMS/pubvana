<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class PluginDependenciesCheck implements CheckInterface
{
    public function __construct(private string $projectRoot) {}

    public function run(): CheckResult
    {
        $installedFile = $this->projectRoot . '/vendor/composer/installed.json';
        if (!file_exists($installedFile)) {
            return new CheckResult(
                id: 'plugin-dependencies',
                name: 'Plugin Dependencies',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'Cannot read composer installed.json. Dependency check skipped.',
                remediation: 'Install dependencies via command line: composer install, or re-deploy from your CI/CD pipeline.',
            );
        }

        $installed = json_decode((string) file_get_contents($installedFile), true);
        $packages = $installed['packages'] ?? $installed;

        $packageNames = [];
        foreach ($packages as $pkg) {
            $packageNames[] = $pkg['name'] ?? '';
        }

        $missing = [];
        foreach ($packages as $pkg) {
            $type = $pkg['type'] ?? '';
            if (!str_starts_with($type, 'flightphp-')) {
                continue;
            }

            $name = $pkg['name'] ?? 'unknown';
            $requires = $pkg['require'] ?? [];
            foreach ($requires as $dep => $version) {
                if ($dep === 'php' || str_starts_with($dep, 'ext-')) {
                    continue;
                }
                if (!in_array($dep, $packageNames, true)) {
                    $missing[] = "{$name} requires {$dep}";
                }
            }
        }

        if (!empty($missing)) {
            return new CheckResult(
                id: 'plugin-dependencies',
                name: 'Plugin Dependencies',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::CRITICAL,
                message: 'Unmet plugin dependencies: ' . implode('; ', $missing),
                remediation: 'Install the missing packages. Via command line: composer require <missing-package>, or re-deploy from your CI/CD pipeline.',
            );
        }

        return new CheckResult(
            id: 'plugin-dependencies',
            name: 'Plugin Dependencies',
            category: CheckResult::CAT_PLUGINS,
            status: CheckResult::PASS,
            message: 'All plugin dependencies are satisfied.',
        );
    }
}
