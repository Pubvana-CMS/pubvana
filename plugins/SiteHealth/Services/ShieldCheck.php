<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class ShieldCheck implements CheckInterface
{
    /**
     * @param Engine<object> $app
     */
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        if (!class_exists(\Enlivenapp\FlightShield\Plugin::class)) {
            return new CheckResult(
                id: 'shield',
                name: 'Authentication (Shield)',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::CRITICAL,
                message: 'Flight Shield is not installed. No authentication layer is protecting the admin area.',
                remediation: 'Install the flight-shield package. Via command line: composer require enlivenapp/flight-shield, or add it to your composer.json and deploy.',
            );
        }

        $config = $this->app->get('enlivenapp.flight-shield') ?? [];

        if (empty($config)) {
            return new CheckResult(
                id: 'shield',
                name: 'Authentication (Shield)',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::WARNING,
                message: 'Shield is installed but appears to have no configuration loaded.',
                remediation: 'Ensure the flight-shield plugin is enabled in your plugins config.',
            );
        }

        return new CheckResult(
            id: 'shield',
            name: 'Authentication (Shield)',
            category: CheckResult::CAT_SECURITY,
            status: CheckResult::PASS,
            message: 'Shield authentication is installed and configured.',
        );
    }
}
