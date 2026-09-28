<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class RequiredSettingsCheck implements CheckInterface
{
    /**
     * @param Engine<object> $app
     */
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $missing = [];

        $siteUrl = (string) ($this->app->get('siteUrl') ?? '');
        if (empty($siteUrl) || $siteUrl === 'http://example.com' || $siteUrl === 'https://example.com') {
            $missing[] = 'SITE_URL (still set to placeholder or empty)';
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
                remediation: 'Update the listed settings in Admin > Settings. Set SITE_URL in .env.',
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
        if (method_exists($this->app, 'settings')) {
            $value = $this->app->settings()->get($key, '');
            if (is_scalar($value)) {
                return (string) $value;
            }
        }

        $value = $this->app->get($key);
        return is_scalar($value) ? (string) $value : '';
    }
}
