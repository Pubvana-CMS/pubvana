<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PHPUnit\Framework\TestCase;

/**
 * Media admin views build markup with innerHTML from database-backed
 * strings (filename, alt_text, title, embed provider). Every interpolated
 * value in those strings must pass through the local esc() helper so a
 * hostile value cannot parse as markup in an admin session.
 */
final class AdminViewsEscapingTest extends TestCase
{
    public function testDetailPanelEscapesAdminControlledValues(): void
    {
        $view = $this->view('index');

        self::assertStringContainsString('function esc(', $view);
        self::assertStringContainsString('esc(media.alt_text || \'\')', $view);
        self::assertStringContainsString('esc(media.title || \'\')', $view);
        self::assertStringContainsString('esc(media.filename)', $view);
        self::assertStringNotContainsString('+ media.filename +', $view, 'bare filename concat must not return');
    }

    public function testPickerGridEscapesListingValues(): void
    {
        $view = $this->view('picker');

        self::assertStringContainsString('function esc(', $view);
        self::assertStringContainsString('esc(item.filename || \'\')', $view);
        self::assertStringContainsString('data-path="${esc(item.path)}"', $view);
        self::assertStringContainsString('esc(item.thumb_url)}', $view);
    }

    public function testJoditGridEscapesListingValues(): void
    {
        $view = $this->view('jodit');

        self::assertStringContainsString('function esc(', $view);
        self::assertStringContainsString('esc(item.filename || \'\')', $view);
        self::assertStringContainsString('esc(item.alt_text || \'\')', $view);
        self::assertStringContainsString('esc(alt)', $view);
    }

    private function view(string $key): string
    {
        $files = [
            'index'  => 'index.php',
            'picker' => 'picker.php',
            'jodit'  => 'jodit.php',
        ];

        return (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Media/Views/admin/' . $files[$key]
        );
    }
}