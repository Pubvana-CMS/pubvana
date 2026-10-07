<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class RequiredSettingsCheck implements CheckInterface
{
    /**
     * Names a fresh install ships with. A site still carrying one of these
     * has not been set up.
     *
     * @var list<string>
     */
    private array $defaultSiteNames = ['Pubvana v3', 'Pubvana', 'My Site'];

    /**
     * @param Engine<object> $app
     */
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $missing = [];

        // SITE_URL is deployment config from .env, never a setting.
        $siteUrl = (string) ($this->app->get('siteUrl') ?? '');
        if (empty($siteUrl) || $siteUrl === 'http://example.com' || $siteUrl === 'https://example.com') {
            $missing[] = 'SITE_URL (still set to placeholder or empty)';
        }

        // CMS.siteName is a database setting. Read it from the settings
        // store, never from the app KV store, which never holds it.
        $siteName = (string) $this->app->settings()->get('CMS.siteName', '');
        if ($siteName === '' || in_array($siteName, $this->defaultSiteNames, true)) {
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
}
