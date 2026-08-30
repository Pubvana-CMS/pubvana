<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class ConfigDefaultsCheck implements CheckInterface
{
    public function __construct(private string $projectRoot) {}

    public function run(): CheckResult
    {
        $envFile = $this->projectRoot . '/.env';

        if (!file_exists($envFile)) {
            return new CheckResult(
                id: 'config-defaults',
                name: 'Configuration File',
                category: CheckResult::CAT_CONFIGURATION,
                status: CheckResult::CRITICAL,
                message: 'No .env file found. The application is running without proper environment configuration.',
                remediation: 'Copy .env.example to .env and fill in your settings.',
            );
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        $parsed = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $trimmed, 2);
            $parsed[trim($key)] = trim($value);
        }

        $issues = [];

        $siteUrl = $parsed['SITE_URL'] ?? '';
        if (empty($siteUrl) || str_contains($siteUrl, 'localhost') || rtrim($siteUrl, '/') === 'http://example.com') {
            $issues[] = 'SITE_URL (still using a localhost/example placeholder)';
        }

        $dbPass = $parsed['DB_PASS'] ?? '';
        if ($dbPass === '') {
            $issues[] = 'DB_PASS (empty)';
        }

        $sessionKey = $parsed['SESSION_ENCRYPTION_KEY'] ?? '';
        if ($sessionKey === '') {
            $issues[] = 'SESSION_ENCRYPTION_KEY (empty)';
        }

        $siteName = $parsed['SITE_NAME'] ?? '';
        if ($siteName === '' || $siteName === 'Pubvana') {
            $issues[] = 'SITE_NAME (still using default)';
        }

        $adminEmail = $parsed['ADMIN_EMAIL'] ?? '';
        if ($adminEmail === '' || str_contains($adminEmail, '@example.com')) {
            $issues[] = 'ADMIN_EMAIL (still using placeholder)';
        }

        if (!empty($issues)) {
            return new CheckResult(
                id: 'config-defaults',
                name: 'Configuration File',
                category: CheckResult::CAT_CONFIGURATION,
                status: CheckResult::WARNING,
                message: '.env contains placeholder or default values: ' . implode(', ', $issues),
                remediation: 'Edit .env and replace placeholder values with your real credentials and site values.',
            );
        }

        return new CheckResult(
            id: 'config-defaults',
            name: 'Configuration File',
            category: CheckResult::CAT_CONFIGURATION,
            status: CheckResult::PASS,
            message: 'Configuration file is present and contains no obvious placeholder values.',
        );
    }
}
