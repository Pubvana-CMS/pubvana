<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class RequiredSettingsCheck implements CheckInterface
{
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $missing = [];

        $siteUrl = $this->settingsValue('CMS.siteUrl');
        if (empty($siteUrl) || $siteUrl === 'http://example.com' || $siteUrl === 'https://example.com') {
            $missing[] = 'CMS.siteUrl (still set to placeholder or empty)';
        }

        $siteName = $this->settingsValue('CMS.siteName');
        if (empty($siteName) || $siteName === 'Pubvana' || $siteName === 'My Site') {
            $missing[] = 'CMS.siteName (still using default)';
        }

        if (!empty($missing)) {
            return new CheckResult(
                id: 'required-settings',
                name: 'Required Settings',
                category: CheckResult::CAT_CONFIGURATION,
                status: CheckResult::WARNING,
                message: 'Settings using default or placeholder values: ' . implode(', ', $missing),
                remediation: 'Update these settings in Admin > Settings with your actual site values.',
            );
        }

        return new CheckResult(
            id: 'required-settings',
            name: 'Required Settings',
            category: CheckResult::CAT_CONFIGURATION,
            status: CheckResult::PASS,
            message: 'All required settings are configured.',
        );
    }

    /**
     * Read a setting, preferring the settings service and falling back to
     * the app's value.
     */
    private function settingsValue(string $key): string
    {
        if (method_exists($this->app, 'settings') && $this->app->settings() !== null) {
            $value = $this->app->settings()->get($key, '');
            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        $value = $this->app->get($key);
        return is_scalar($value) ? (string) $value : '';
    }
}
