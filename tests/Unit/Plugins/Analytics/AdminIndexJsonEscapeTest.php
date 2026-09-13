<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Analytics;

use PHPUnit\Framework\TestCase;

/**
 * Line 160/161 of the report view embed report data directly into an
 * inline <script> via json_encode(); <, >, &, " and ' there must stay
 * hex-escaped so a crafted tracked path or referrer cannot break out.
 */
final class AdminIndexJsonEscapeTest extends TestCase
{
    public function testInlineScriptJsonUsesHexEscapes(): void
    {
        $view = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Analytics/Views/admin/index.php'
        );

        self::assertSame(
            2,
            substr_count($view, 'JSON_HEX_TAG'),
            'both inline json_encode calls must carry the JSON_HEX_* flags'
        );
    }
}