<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class PhpVersionCheck implements CheckInterface
{
    private string $minimum = '8.2.0';
    private string $recommended = '8.3.0';

    public function run(): CheckResult
    {
        $current = PHP_VERSION;

        if (version_compare($current, $this->minimum, '<')) {
            return new CheckResult(
                id: 'php-version',
                name: 'PHP Version',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::CRITICAL,
                message: "PHP {$current} is below the minimum required {$this->minimum}.",
                remediation: "Upgrade PHP to at least {$this->minimum}. Version {$this->recommended}+ is recommended.",
            );
        }

        if (version_compare($current, $this->recommended, '<')) {
            return new CheckResult(
                id: 'php-version',
                name: 'PHP Version',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: "PHP {$current} works but {$this->recommended}+ is recommended for best performance and security.",
                remediation: "Consider upgrading to PHP {$this->recommended} or newer.",
            );
        }

        return new CheckResult(
            id: 'php-version',
            name: 'PHP Version',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: "PHP {$current} meets requirements.",
        );
    }
}
