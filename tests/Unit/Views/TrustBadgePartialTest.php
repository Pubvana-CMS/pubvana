<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

/**
 * The core trust badge partial: one markup definition for the themes and
 * plugins admin tables, warning tooltip included and escaped.
 *
 * @package Pubvana\Tests\Unit\Views
 */
final class TrustBadgePartialTest extends TestCase
{
    private const PARTIAL = PROJECT_ROOT . '/app/Views/admin/_trust_badge.php';

    private function render(string $status, ?string $warning = null): string
    {
        $badgeStatus  = $status;
        $badgeWarning = $warning;

        ob_start();
        include self::PARTIAL;

        return (string) ob_get_clean();
    }

    public function testTrusted(): void
    {
        self::assertSame(
            '<span class="badge bg-green-lt text-success">'
            . '<i class="ti ti-shield-check me-1"></i>Trusted</span>',
            $this->render('trusted')
        );
    }

    public function testKnownWithoutWarningHasNoTitleAttribute(): void
    {
        $html = $this->render('known');

        self::assertStringContainsString('badge bg-azure-lt"', $html);
        self::assertStringNotContainsString('title=', $html);
        self::assertStringContainsString('<i class="ti ti-shield me-1"></i>Known</span>', $html);
    }

    public function testKnownEscapesTheWarningIntoTheTitleAttribute(): void
    {
        self::assertSame(
            '<span class="badge bg-azure-lt" title="License changed &amp; owner &quot;quoted&quot;">'
            . '<i class="ti ti-shield me-1"></i>Known</span>',
            $this->render('known', 'License changed & owner "quoted"')
        );
    }

    public function testMaliciousWarningIsEscaped(): void
    {
        $html = $this->render('malicious', '<script>alert(1)</script>');

        self::assertStringContainsString('badge bg-red-lt text-danger', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringContainsString('<i class="ti ti-alert-triangle me-1"></i>Malicious</span>', $html);
    }

    public function testEmptyWarningIsTreatedAsAbsent(): void
    {
        self::assertStringNotContainsString('title=', $this->render('malicious', ''));
    }

    public function testUnknownAndEverythingElseReadAsNotCheckedStatuses(): void
    {
        $unknown = $this->render('unknown');
        self::assertStringContainsString('bg-yellow-lt text-yellow', $unknown);
        self::assertStringContainsString('>Unknown</span>', $unknown);

        foreach (['none', '', 'anything-else'] as $status) {
            $html = $this->render($status);
            self::assertStringContainsString('bg-secondary-lt', $html);
            self::assertStringContainsString('>Not checked</span>', $html);
        }
    }
}
