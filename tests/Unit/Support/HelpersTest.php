<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

require_once PROJECT_ROOT . '/app/Support/helpers.php';

/**
 * trust_badge() global helper, one framing shared by the plugins table and
 * the theme cards.
 *
 * @package Pubvana\Tests\Unit\Support
 */
#[CoversFunction('trust_badge')]
final class HelpersTest extends TestCase
{
    public function testTrustedBadge(): void
    {
        self::assertSame(
            '<span class="badge bg-green-lt text-success">'
            . '<i class="ti ti-shield-check me-1"></i>Trusted</span>',
            trust_badge('trusted')
        );
    }

    public function testKnownBadgeWithoutWarningHasNoTitleAttribute(): void
    {
        $html = trust_badge('known');

        self::assertStringContainsString('badge bg-azure-lt"', $html);
        self::assertStringNotContainsString('title=', $html);
        self::assertStringContainsString('<i class="ti ti-shield me-1"></i>Known</span>', $html);
    }

    public function testKnownBadgeEscapesWarningIntoTitleAttribute(): void
    {
        $html = trust_badge('known', 'License changed & owner "quoted"');

        self::assertSame(
            '<span class="badge bg-azure-lt" title="License changed &amp; owner &quot;quoted&quot;">'
            . '<i class="ti ti-shield me-1"></i>Known</span>',
            $html
        );
    }

    public function testMaliciousBadgeCarriesWarningAttribute(): void
    {
        $html = trust_badge('malicious', '<script>alert(1)</script>');

        self::assertSame(
            '<span class="badge bg-red-lt text-danger" title="&lt;script&gt;alert(1)&lt;/script&gt;">'
            . '<i class="ti ti-alert-triangle me-1"></i>Malicious</span>',
            $html
        );
    }

    public function testMaliciousBadgeWithoutWarningHasNoTitleAttribute(): void
    {
        $html = trust_badge('malicious');

        self::assertStringContainsString('badge bg-red-lt text-danger"', $html);
        self::assertStringNotContainsString('title=', $html);
        self::assertStringContainsString('Malicious</span>', $html);
    }

    public function testUnknownBadge(): void
    {
        self::assertSame(
            '<span class="badge bg-yellow-lt text-yellow">'
            . '<i class="ti ti-help me-1"></i>Unknown</span>',
            trust_badge('unknown')
        );
    }

    public function testDefaultFallsBackToNotChecked(): void
    {
        self::assertSame(
            '<span class="badge bg-secondary-lt">'
            . '<i class="ti ti-circle-dashed me-1"></i>Not checked</span>',
            trust_badge('none')
        );
        self::assertSame(
            '<span class="badge bg-secondary-lt">'
            . '<i class="ti ti-circle-dashed me-1"></i>Not checked</span>',
            trust_badge('anything-else')
        );
    }

    public function testEmptyWarningIsIgnored(): void
    {
        $html = trust_badge('known', '');

        self::assertStringNotContainsString('title=', $html);
    }
}
