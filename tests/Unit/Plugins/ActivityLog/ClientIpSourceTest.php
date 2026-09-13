<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use flight\net\Request;
use Pubvana\Plugins\ActivityLog\Services\ActivityLogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * The audit-log client IP must come from the connection address only.
 * X-Forwarded-For and X-Real-IP are client-controlled; trusting either
 * lets any visitor forge the IP written to the audit trail (AUDIT M12).
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ActivityLogService::class)]
final class ClientIpSourceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);
        parent::tearDown();
    }

    public function testProxyHeadersAreIgnoredInFavorOfRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 10.0.0.1';
        $_SERVER['HTTP_X_REAL_IP'] = '5.6.7.8';

        $service = new ActivityLogService(Sqlite::recreate());

        self::assertSame('198.51.100.7', $this->invoke($service, 'getClientIp', [$this->requestWithHeaders()]));
    }

    public function testFallsBackToUnknownWithoutRemoteAddr(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';

        $service = new ActivityLogService(Sqlite::recreate());

        self::assertSame('unknown', $this->invoke($service, 'getClientIp', [$this->requestWithHeaders()]));
    }

    public function testSourceNeverReadsProxyHeaders(): void
    {
        $source = (string) file_get_contents(PROJECT_ROOT . '/plugins/ActivityLog/Services/ActivityLogService.php');

        self::assertSame(
            0,
            (int) preg_match("/getHeader\(\s*'(X-Forwarded-For|X-Real-IP)'/i", $source),
            'the audit IP must never come from a spoofable proxy header'
        );
    }

    /**
     * A real Request. Flight's getHeader() reads $_SERVER['HTTP_*'], so the
     * hostile header values set in each test are what the old implementation
     * would have returned.
     */
    private function requestWithHeaders(): Request
    {
        return new Request();
    }
}
