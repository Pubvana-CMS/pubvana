<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class SessionConfigCheck implements CheckInterface
{
    public function __construct(private Engine $app) {}

    public function run(): CheckResult
    {
        $issues = [];

        $lifetime = (int) ini_get('session.gc_maxlifetime');
        if ($lifetime > 86400) {
            $issues[] = "Session lifetime is {$lifetime}s (over 24 hours). Long sessions increase hijack risk.";
        }

        $cookieHttpOnly = (bool) ini_get('session.cookie_httponly');
        if (!$cookieHttpOnly) {
            $issues[] = 'session.cookie_httponly is off. Session cookies are accessible via JavaScript (XSS risk).';
        }

        $cookieSecure = (bool) ini_get('session.cookie_secure');
        $env = (string) ($this->app->get('environment') ?? 'production');
        if (!$cookieSecure && $env !== 'development') {
            $issues[] = 'session.cookie_secure is off. Session cookies sent over plain HTTP in production.';
        }

        $sameSite = ini_get('session.cookie_samesite');
        if (empty($sameSite) || strtolower($sameSite) === 'none') {
            $issues[] = "session.cookie_samesite is '{$sameSite}'. Should be 'Lax' or 'Strict' to prevent CSRF.";
        }

        if (!empty($issues)) {
            $severity = count($issues) >= 3 ? CheckResult::CRITICAL : CheckResult::WARNING;
            return new CheckResult(
                id: 'session-config',
                name: 'Session Configuration',
                category: CheckResult::CAT_SECURITY,
                status: $severity,
                message: implode(' ', $issues),
                remediation: 'Update php.ini or your session config: cookie_httponly=1, cookie_secure=1, cookie_samesite=Lax, gc_maxlifetime<=86400.',
            );
        }

        return new CheckResult(
            id: 'session-config',
            name: 'Session Configuration',
            category: CheckResult::CAT_SECURITY,
            status: CheckResult::PASS,
            message: 'Session configuration follows security best practices.',
        );
    }
}
