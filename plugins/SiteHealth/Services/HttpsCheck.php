<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class HttpsCheck implements CheckInterface
{
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $env = (string) ($this->app->get('environment') ?? 'production');
        $forceHttps = (bool) $this->app->get('flight.force_https');

        $siteUrl = $this->resolveSiteUrl();
        $isHttps = str_starts_with($siteUrl, 'https://');

        if ($isHttps && $forceHttps) {
            return new CheckResult(
                id: 'https',
                name: 'HTTPS',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::PASS,
                message: 'Site is configured for HTTPS with forced redirect enabled.',
            );
        }

        if ($isHttps && !$forceHttps) {
            return new CheckResult(
                id: 'https',
                name: 'HTTPS',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::WARNING,
                message: 'Site URL uses HTTPS but force_https is not enabled. HTTP requests will not be redirected.',
                remediation: "Set FORCE_HTTPS to true (via .env or the environment) to redirect all HTTP requests to HTTPS.",
            );
        }

        if ($env === 'development') {
            return new CheckResult(
                id: 'https',
                name: 'HTTPS',
                category: CheckResult::CAT_SECURITY,
                status: CheckResult::WARNING,
                message: 'Site is not using HTTPS. Acceptable for local development only.',
                remediation: 'Configure HTTPS before deploying to production.',
            );
        }

        return new CheckResult(
            id: 'https',
            name: 'HTTPS',
            category: CheckResult::CAT_SECURITY,
            status: CheckResult::CRITICAL,
            message: 'Site is not using HTTPS in a non-development environment.',
            remediation: 'Install an SSL certificate, update the site URL to https://, and set FORCE_HTTPS to true.',
        );
    }

    /**
     * Resolve the canonical site URL for HTTPS detection.
     *
     * Prefers the CMS.siteUrl setting; falls back to the current request
     * scheme and host.
     */
    private function resolveSiteUrl(): string
    {
        $siteUrl = '';

        if (method_exists($this->app, 'settings') && $this->app->settings() !== null) {
            $siteUrl = (string) $this->app->settings()->get('CMS.siteUrl', '');
        }
        if (empty($siteUrl)) {
            $siteUrl = (string) ($this->app->get('CMS.siteUrl') ?? '');
        }
        if (empty($siteUrl)) {
            $request = $this->app->request();
            $scheme = $request->secure ? 'https' : 'http';
            $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $siteUrl = $scheme . '://' . $host;
        }

        return rtrim($siteUrl, '/');
    }
}
